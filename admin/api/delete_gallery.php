<?php
/** Xoá ảnh gallery. POST /admin/api/delete_gallery.php */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
auth_require();
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$g = $id ? db_one('SELECT scene_id, image_url FROM gallery WHERE id = ?', [$id]) : null;
if ($g) {
    db()->prepare('DELETE FROM gallery WHERE id = ?')->execute([$id]);
    // Xoá file vật lý nếu nằm trong /upload
    $path = BASE_PATH . ($g['image_url'] ?? '');
    if (strpos((string)$g['image_url'], '/upload/') === 0 && is_file($path)) {
        @unlink($path);
    }
}
header('Location: /admin/modules/gallery/list.php?scene=' . (int)($g['scene_id'] ?? 0));
exit;
