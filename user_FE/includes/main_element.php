<?php
/**
 * GET /user_FE/includes/main_element.php?id=<id_path>
 * Trả HTML fragment nạp các module JS của theme (giống ontop_element của mẫu).
 * Viewer nhúng fragment này sau khi krpano sẵn sàng.
 */
require_once dirname(__DIR__, 2) . '/core/i18n.php';

header('Content-Type: text/html; charset=utf-8');

$idPath = trim((string)($_GET['id'] ?? ''));
$tour = $idPath !== '' ? tour_by_path($idPath) : null;
if (!$tour) {
    echo '<!-- tour not found -->';
    exit;
}

// Danh sách module FE sẽ kích hoạt (Phase 0: khai báo, code thật bổ sung dần)
$modules = ['hotspot', 'polygon_hotspot', 'minimap', 'audio_group', 'gallery_ddl',
            'video', 'iframe_button', 'autotour', 'settings', 'chatbot'];

$base = BASE_URL . '/user_FE/theme/' . rawurlencode($tour['theme']);
foreach ($modules as $m) {
    printf(
        '<script src="%s/%s/js/%s_function.js"></script>' . "\n",
        $base,
        rawurlencode($m),
        rawurlencode($m)
    );
}
