<?php
/** Lưu iframe button. POST /admin/api/save_iframe.php */
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
$url = trim((string)($_POST['iframe_url'] ?? ''));
if ($url === '') {
    exit('Cần URL nhúng');
}

$icon = null;
if (!empty($_FILES['icon']['name'])) {
    $err = null;
    $icon = save_upload($_FILES['icon'], 'image', (string)$scene['id_path'], $err);
}

db()->prepare('INSERT INTO iframe_buttons (scene_id, ath, atv, iframe_url, icon) VALUES (?,?,?,?,?)')
    ->execute([$sceneId, (float)($_POST['ath'] ?? 0), (float)($_POST['atv'] ?? 0), $url, $icon]);

header('Location: /admin/modules/iframe/list.php?scene=' . $sceneId);
exit;
