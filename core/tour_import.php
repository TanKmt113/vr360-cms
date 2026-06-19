<?php
/**
 * Import tour krpano (dùng chung cho CLI và giao diện web).
 * Đọc tour.xml → tạo scene → (tuỳ chọn) copy tiles vào upload/panos/<id_path>/.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/** Copy đệ quy thư mục (thuần PHP, không cần exec) */
function rcopy_dir(string $src, string $dst): bool
{
    if (!is_dir($src)) {
        return false;
    }
    if (!is_dir($dst) && !@mkdir($dst, 0775, true) && !is_dir($dst)) {
        return false;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        $target = $dst . '/' . $it->getSubPathName();
        if ($item->isDir()) {
            if (!is_dir($target)) {
                @mkdir($target, 0775, true);
            }
        } else {
            @copy($item->getPathname(), $target);
        }
    }
    return true;
}

/** Xoá đệ quy thư mục (thuần PHP) */
function rrmdir_path(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
        $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }
    @rmdir($dir);
}

/**
 * Giải nén ZIP an toàn (extractTo nhanh hơn copy từng file qua zip://).
 * @return null nếu OK, hoặc thông báo lỗi.
 */
function extract_zip_safe(string $zipPath, string $dest): ?string
{
    if (!class_exists('ZipArchive')) {
        return 'Server thiếu ZipArchive.';
    }
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return 'Không mở được ZIP.';
    }
    if (!is_dir($dest) && !@mkdir($dest, 0775, true)) {
        $zip->close();
        return 'Không tạo được thư mục giải nén.';
    }
    if (!$zip->extractTo($dest)) {
        $zip->close();
        return 'Giải nén ZIP thất bại.';
    }
    $zip->close();

    $realDest = realpath($dest);
    if ($realDest === false) {
        rrmdir_path($dest);
        return 'Thư mục giải nén không hợp lệ.';
    }
    $prefix = $realDest . DIRECTORY_SEPARATOR;
    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dest, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $item) {
            $path = $item->getRealPath();
            if ($path === false || (!str_starts_with($path, $prefix) && $path !== $realDest)) {
                rrmdir_path($dest);
                return 'ZIP chứa đường dẫn không an toàn (zip-slip).';
            }
        }
    } catch (Throwable) {
        rrmdir_path($dest);
        return 'Không đọc được nội dung ZIP sau giải nén.';
    }
    return null;
}

/** Thông báo lỗi upload PHP (UPLOAD_ERR_*). */
function upload_file_error_message(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
            'File vượt giới hạn upload (upload_max_filesize = ' . ini_get('upload_max_filesize') . ').',
        UPLOAD_ERR_PARTIAL => 'Upload bị gián đoạn — thử lại hoặc dùng import từ thư mục server.',
        UPLOAD_ERR_NO_FILE => 'Chưa chọn file .zip.',
        UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'Server không ghi được file tạm khi upload.',
        default => 'Upload thất bại (mã lỗi ' . $code . ').',
    };
}

/** Tìm file XML chứa <scene> trong thư mục krpano */
function krpano_find_xml(string $src): ?string
{
    foreach (["$src/tour_xml/tour.xml", "$src/tour.xml", "$src/ngan-hack.xml"] as $cand) {
        if (is_file($cand) && strpos((string)@file_get_contents($cand), '<scene ') !== false) {
            return $cand;
        }
    }
    // Dò thêm: bất kỳ .xml nào ở gốc có <scene>
    foreach (glob("$src/*.xml") ?: [] as $cand) {
        if (strpos((string)@file_get_contents($cand), '<scene ') !== false) {
            return $cand;
        }
    }
    return null;
}

/**
 * Thực hiện import.
 * @return array{ok:bool, imported:int, skipped:int, copied:int, error:?string, log:array}
 */
function import_krpano_tour(string $src, int $tourId, string $tourPath, bool $doCopy = true, bool $doReset = false): array
{
    $res = ['ok' => false, 'imported' => 0, 'skipped' => 0, 'copied' => 0, 'error' => null, 'log' => []];
    $src = rtrim($src, '/');

    if (!is_dir($src)) {
        $res['error'] = 'Không tìm thấy thư mục nguồn: ' . $src;
        return $res;
    }
    $xmlFile = krpano_find_xml($src);
    if ($xmlFile === null) {
        $res['error'] = 'Không tìm thấy file XML có <scene> (đã thử tour_xml/tour.xml, tour.xml, *.xml).';
        return $res;
    }
    $xml = (string)file_get_contents($xmlFile);
    $panosDir = "$src/panos";
    $idPath = preg_replace('/[^A-Za-z0-9_-]/', '', $tourPath);

    if ($doReset) {
        db()->prepare('DELETE FROM scenes WHERE tour_id = ?')->execute([$tourId]);
        $res['log'][] = 'Đã xoá scene cũ của tour.';
    }

    $destPanosRoot = UPLOAD_PATH . "/panos/$idPath";
    if ($doCopy && is_dir("$src/panos") && !is_dir($destPanosRoot)) {
        @mkdir(dirname($destPanosRoot), 0775, true);
        if (rcopy_dir("$src/panos", $destPanosRoot)) {
            $res['log'][] = 'Đã copy thư mục panos/ sang upload.';
        }
    }

    $attr = function (string $s, string $name): ?string {
        return preg_match('/\b' . preg_quote($name, '/') . '\s*=\s*"([^"]*)"/', $s, $m) ? $m[1] : null;
    };
    $humanize = function (string $title): string {
        $t = preg_replace('/^\d+[_\s]*/', '', $title);
        $t = trim(preg_replace('/\s+/', ' ', str_replace(['_', '-'], ' ', $t)));
        return $t === '' ? $title : ucwords($t);
    };

    $insert = db()->prepare(
        'INSERT INTO scenes (tour_id, name, title, thumb_url, pano_url, pano_multires, hlookat, vlookat, fov, sort)
         VALUES (?,?,?,?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE title=VALUES(title), thumb_url=VALUES(thumb_url),
           pano_url=VALUES(pano_url), pano_multires=VALUES(pano_multires),
           hlookat=VALUES(hlookat), vlookat=VALUES(vlookat), fov=VALUES(fov), sort=VALUES(sort)'
    );

    preg_match_all('#<scene\b(.*?)</scene>#s', $xml, $blocks, PREG_SET_ORDER);
    $sort = 0;
    foreach ($blocks as $b) {
        $head = $b[1];
        $name = $attr($head, 'name');
        if (!$name) {
            continue;
        }
        $title = $attr($head, 'title') ?: $name;

        preg_match_all('#<cube\s+url="([^"]+)"(?:\s+multires="([^"]*)")?#', $head, $cubes, PREG_SET_ORDER);
        $cubeUrl = null; $multires = '512,640';
        foreach ($cubes as $c) {
            if (strpos($c[1], '%s/l%l') !== false || strpos($c[1], '%l_%s') !== false) {
                $cubeUrl = $c[1];
                $multires = !empty($c[2]) ? $c[2] : $multires;
                break;
            }
        }
        if ($cubeUrl === null) { $res['skipped']++; continue; }

        $tilesFolder = basename(preg_replace('#/%s/.*$#', '', $cubeUrl));
        $relTemplate = ltrim(substr($cubeUrl, strpos($cubeUrl, $tilesFolder)), '/');
        $srcTiles = "$panosDir/$tilesFolder";
        if (!is_dir($srcTiles)) { $res['skipped']++; $res['log'][] = "Bỏ qua $name (thiếu panos/$tilesFolder)"; continue; }

        $insert->execute([
            $tourId, $name, $humanize($title),
            "$tilesFolder/thumb.jpg", $relTemplate, $multires,
            (float)($attr($head, 'hlookat') ?? 0), (float)($attr($head, 'vlookat') ?? 0),
            (float)($attr($head, 'fov') ?? 120), ++$sort,
        ]);
        $res['imported']++;

        if ($doCopy) {
            $dest = UPLOAD_PATH . "/panos/$idPath/$tilesFolder";
            if (!is_dir($dest)) {
                if (rcopy_dir($srcTiles, $dest)) {
                    $res['copied']++;
                }
            }
        }
    }

    $res['ok'] = true;
    return $res;
}
