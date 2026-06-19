<?php
/**
 * Import tour krpano có sẵn (thư mục tour StarGlobal/krpano) vào CMS.
 *
 * Cách dùng (CLI):
 *   php tools/import_tour.php --src=/duong/dan/dinh-doc-lap --tour=20240705 [--copy] [--reset]
 *
 *   --src    Thư mục tour krpano (chứa tour_xml/tour.xml hoặc tour.xml + panos/)
 *   --tour   id_path của tour trong CMS (phải đã tạo tour này trong admin/DB)
 *   --copy   Copy tiles từ <src>/panos vào upload/panos/<id_path>/ (bỏ qua nếu chỉ test)
 *   --reset  Xoá hết scene cũ của tour trước khi import
 *
 * Lưu ý: hotspot điều hướng KHÔNG có trong XML (StarGlobal nạp động) → chỉ import
 * scene + pano. Thêm hotspot sau bằng admin (đặt trực quan).
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Chỉ chạy qua dòng lệnh (CLI).\n");
}

require_once dirname(__DIR__) . '/core/db.php';
require_once dirname(__DIR__) . '/core/tour_import.php';   // hàm rcopy_dir (copy tiles thuần PHP)

// ---- Parse tham số ----
$args = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z]+)(?:=(.*))?$/', $a, $m)) {
        $args[$m[1]] = $m[2] ?? true;
    }
}
$src   = isset($args['src']) ? rtrim((string)$args['src'], '/') : '';
$tour  = (string)($args['tour'] ?? '');
$doCopy = isset($args['copy']);
$doReset = isset($args['reset']);

if ($src === '' || $tour === '') {
    exit("Thiếu tham số. Ví dụ:\n  php tools/import_tour.php --src=/home/tandev/Downloads/dinh-doc-lap --tour=20240705 --copy\n");
}
if (!is_dir($src)) {
    exit("Không tìm thấy thư mục src: $src\n");
}

// ---- Tìm file XML chứa scene ----
$xmlFile = null;
foreach (["$src/tour_xml/tour.xml", "$src/tour.xml", "$src/ngan-hack.xml"] as $cand) {
    if (is_file($cand) && substr_count((string)@file_get_contents($cand), '<scene ') > 0) {
        $xmlFile = $cand;
        break;
    }
}
if ($xmlFile === null) {
    exit("Không tìm thấy file XML có <scene> trong: $src (đã thử tour_xml/tour.xml, tour.xml, ngan-hack.xml)\n");
}
echo "Đọc scene từ: $xmlFile\n";

$xml = (string)file_get_contents($xmlFile);

// ---- Tìm tour trong DB ----
$tourRow = db_one('SELECT id FROM tours WHERE id_path = ?', [$tour]);
if (!$tourRow) {
    exit("Tour '$tour' chưa tồn tại trong CMS. Hãy tạo tour này trong admin trước (hoặc seed).\n");
}
$tourId = (int)$tourRow['id'];

if ($doReset) {
    db()->prepare('DELETE FROM scenes WHERE tour_id = ?')->execute([$tourId]);
    echo "Đã xoá scene cũ của tour.\n";
}

$panosDir = "$src/panos";

// ---- Tách từng <scene ...>...</scene> ----
preg_match_all('#<scene\b(.*?)</scene>#s', $xml, $blocks, PREG_SET_ORDER);
echo "Tìm thấy " . count($blocks) . " scene trong XML.\n\n";

$attr = function (string $s, string $name): ?string {
    if (preg_match('/\b' . preg_quote($name, '/') . '\s*=\s*"([^"]*)"/', $s, $m)) {
        return $m[1];
    }
    return null;
};

$insert = db()->prepare(
    'INSERT INTO scenes (tour_id, name, title, thumb_url, pano_url, pano_multires, hlookat, vlookat, fov, sort)
     VALUES (?,?,?,?,?,?,?,?,?,?)
     ON DUPLICATE KEY UPDATE title=VALUES(title), thumb_url=VALUES(thumb_url),
       pano_url=VALUES(pano_url), pano_multires=VALUES(pano_multires),
       hlookat=VALUES(hlookat), vlookat=VALUES(vlookat), fov=VALUES(fov), sort=VALUES(sort)'
);

$humanize = function (string $title): string {
    $t = preg_replace('/^\d+[_\s]*/', '', $title);   // bỏ tiền tố "00_"
    $t = str_replace(['_', '-'], ' ', $t);
    $t = trim(preg_replace('/\s+/', ' ', $t));
    return $t === '' ? $title : ucwords($t);
};

$imported = 0;
$skipped = 0;
$copied = 0;
$sort = 0;

foreach ($blocks as $b) {
    $head = $b[1];                       // phần thuộc tính + nội dung scene
    $name = $attr($head, 'name');
    if (!$name) {
        continue;
    }
    $title = $attr($head, 'title') ?: $name;

    // Lấy cube url tiles (loại bản VR 'vr/pano_%s.jpg')
    preg_match_all('#<cube\s+url="([^"]+)"(?:\s+multires="([^"]*)")?#', $head, $cubes, PREG_SET_ORDER);
    $cubeUrl = null;
    $multires = '512,640';
    foreach ($cubes as $c) {
        if (strpos($c[1], '%s/l%l') !== false || strpos($c[1], '%l_%s') !== false) {
            $cubeUrl = $c[1];
            $multires = !empty($c[2]) ? $c[2] : $multires;
            break;
        }
    }
    if ($cubeUrl === null) {
        $skipped++;
        echo "  - BỎ QUA $name (không thấy cube tiles)\n";
        continue;
    }

    // Tên thư mục tiles: phần trước "/%s" trong url, bỏ tiền tố "panos/"
    $tilesPath = preg_replace('#/%s/.*$#', '', $cubeUrl);     // panos/xxx.tiles
    $tilesFolder = basename($tilesPath);                      // xxx.tiles
    $relTemplate = ltrim(substr($cubeUrl, strpos($cubeUrl, $tilesFolder)), '/'); // xxx.tiles/%s/...

    // Chỉ import nếu tiles tồn tại trong panos/
    $srcTiles = "$panosDir/$tilesFolder";
    if (!is_dir($srcTiles)) {
        $skipped++;
        echo "  - BỎ QUA $name (thiếu tiles: panos/$tilesFolder)\n";
        continue;
    }

    $view = $head;
    $hlookat = (float)($attr($view, 'hlookat') ?? 0);
    $vlookat = (float)($attr($view, 'vlookat') ?? 0);
    $fov     = (float)($attr($view, 'fov') ?? 120);

    $thumb = "$tilesFolder/thumb.jpg";
    $sort++;

    $insert->execute([
        $tourId, $name, $humanize($title), $thumb, $relTemplate, $multires,
        $hlookat, $vlookat, $fov, $sort,
    ]);
    $imported++;
    echo "  ✓ $name  (multires=$multires)\n";

    // Copy tiles (thuần PHP, không dùng exec)
    if ($doCopy) {
        $destTiles = UPLOAD_PATH . "/panos/$tour/$tilesFolder";
        if (!is_dir($destTiles)) {
            if (rcopy_dir($srcTiles, $destTiles)) {
                $copied++;
            } else {
                echo "    ⚠️ copy tiles lỗi cho $name\n";
            }
        }
    }
}

echo "\n=== KẾT QUẢ ===\n";
echo "Import scene: $imported | Bỏ qua: $skipped" . ($doCopy ? " | Copy tiles: $copied" : " (không copy tiles)") . "\n";
echo $doCopy ? "Tiles đã vào: " . UPLOAD_PATH . "/panos/$tour/\n" : "Chạy lại với --copy để copy tiles.\n";
echo "Xem tour: /public/viewer.php?id=$tour\n";
