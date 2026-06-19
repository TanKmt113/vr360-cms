<?php
/** Lưu video (URL hoặc upload). POST /admin/api/save_video.php */
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

$url = trim((string)($_POST['video_url'] ?? ''));
if ($url === '' && !empty($_FILES['video']['name'])) {
    $err = null;
    $url = save_upload($_FILES['video'], 'video', (string)$scene['id_path'], $err) ?? '';
    if ($url === '') {
        exit('Lỗi upload: ' . $err);
    }
}
if ($url === '') {
    exit('Cần URL hoặc file video');
}

$type = preg_match('/\.webm($|\?)/i', $url) ? 'webm' : 'mp4';
db()->prepare('INSERT INTO videos (scene_id, ath, atv, video_url, type) VALUES (?,?,?,?,?)')
    ->execute([$sceneId, (float)($_POST['ath'] ?? 0), (float)($_POST['atv'] ?? 0), $url, $type]);

header('Location: /admin/modules/video/list.php?scene=' . $sceneId);
exit;
