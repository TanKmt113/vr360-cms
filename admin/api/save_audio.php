<?php
/** Upload audio cho scene. POST /admin/api/save_audio.php */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
require_once dirname(__DIR__, 2) . '/core/upload.php';
auth_require();
csrf_check();

$sceneId = (int)($_POST['scene_id'] ?? 0);
$scene = $sceneId ? db_one('SELECT s.*, t.id_path FROM scenes s JOIN tours t ON t.id=s.tour_id WHERE s.id=?', [$sceneId]) : null;
if (!$scene) {
    exit('Không tìm thấy scene');
}

$err = null;
$url = save_upload($_FILES['audio'] ?? [], 'audio', (string)$scene['id_path'], $err);
if ($url === null) {
    exit('Lỗi: ' . $err);
}

db()->prepare(
    'INSERT INTO audio_groups (tour_id, scene_id, audio_url, name, `loop`, autoplay) VALUES (?,?,?,?,?,?)'
)->execute([
    (int)$scene['tour_id'], $sceneId, $url,
    trim((string)($_POST['name'] ?? '')) ?: null,
    !empty($_POST['loop']) ? 1 : 0,
    !empty($_POST['autoplay']) ? 1 : 0,
]);

header('Location: /admin/modules/audio/list.php?scene=' . $sceneId);
exit;
