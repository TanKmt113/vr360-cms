<?php
/** Xoá audio. POST /admin/api/delete_audio.php */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
auth_require();
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$a = $id ? db_one('SELECT scene_id, audio_url FROM audio_groups WHERE id = ?', [$id]) : null;
if ($a) {
    db()->prepare('DELETE FROM audio_groups WHERE id = ?')->execute([$id]);
    $path = BASE_PATH . ($a['audio_url'] ?? '');
    if (strpos((string)$a['audio_url'], '/upload/') === 0 && is_file($path)) {
        @unlink($path);
    }
}
header('Location: /admin/modules/audio/list.php?scene=' . (int)($a['scene_id'] ?? 0));
exit;
