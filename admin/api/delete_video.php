<?php
/** Xoá video. POST /admin/api/delete_video.php */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
auth_require();
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$v = $id ? db_one('SELECT scene_id, video_url FROM videos WHERE id = ?', [$id]) : null;
if ($v) {
    db()->prepare('DELETE FROM videos WHERE id = ?')->execute([$id]);
    $path = BASE_PATH . ($v['video_url'] ?? '');
    if (strpos((string)$v['video_url'], '/upload/') === 0 && is_file($path)) {
        @unlink($path);
    }
}
header('Location: /admin/modules/video/list.php?scene=' . (int)($v['scene_id'] ?? 0));
exit;
