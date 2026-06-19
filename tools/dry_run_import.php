<?php
/** Phân tích thư mục krpano — không cần DB. */
declare(strict_types=1);

$src = rtrim($argv[1] ?? '_research/dinh-doc-lap', '/');
if ($src[0] !== '/') {
    $src = dirname(__DIR__) . '/' . ltrim($src, '/');
}

require_once dirname(__DIR__) . '/core/tour_import.php';

$xmlFile = krpano_find_xml($src);
if ($xmlFile === null) {
    exit("Không tìm thấy tour.xml có <scene> trong: $src\n");
}

$xml = (string)file_get_contents($xmlFile);
$panosDir = "$src/panos";
preg_match_all('#<scene\b(.*?)</scene>#s', $xml, $blocks, PREG_SET_ORDER);

$attr = static function (string $s, string $name): ?string {
    return preg_match('/\b' . preg_quote($name, '/') . '\s*=\s*"([^"]*)"/', $s, $m) ? $m[1] : null;
};

$imported = 0;
$skipped = ['no_cube' => [], 'no_local_tiles' => []];

foreach ($blocks as $b) {
    $head = $b[1];
    $name = $attr($head, 'name');
    if (!$name) {
        continue;
    }

    preg_match_all('#<cube\s+url="([^"]+)"(?:\s+multires="([^"]*)")?#', $head, $cubes, PREG_SET_ORDER);
    $cubeUrl = null;
    foreach ($cubes as $c) {
        if (strpos($c[1], '%s/l%l') !== false || strpos($c[1], '%l_%s') !== false) {
            $cubeUrl = $c[1];
            break;
        }
    }
    if ($cubeUrl === null) {
        $skipped['no_cube'][] = $name;
        continue;
    }

    $tilesFolder = basename(preg_replace('#/%s/.*$#', '', $cubeUrl));
    if (!is_dir("$panosDir/$tilesFolder")) {
        $skipped['no_local_tiles'][] = "$name → panos/$tilesFolder";
        continue;
    }
    $imported++;
}

echo "Thư mục : $src\n";
echo "XML     : $xmlFile\n";
echo "Scene XML: " . count($blocks) . "\n";
echo "Import OK: $imported\n";
echo "Bỏ qua   : " . (count($skipped['no_cube']) + count($skipped['no_local_tiles'])) . "\n";
echo "  - Không có cube tiles: " . count($skipped['no_cube']) . "\n";
echo "  - Thiếu panos/ local  : " . count($skipped['no_local_tiles']) . "\n";
if ($skipped['no_local_tiles']) {
    echo "\nVí dụ thiếu tiles local (panorama trên S3, chưa tải về server):\n";
    foreach (array_slice($skipped['no_local_tiles'], 0, 8) as $line) {
        echo "  · $line\n";
    }
}
