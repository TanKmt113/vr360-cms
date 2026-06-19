<?php
/**
 * Tạo/xoá nhanh hotspot điều hướng (AJAX, trả JSON). Dùng cho trang đặt trực quan.
 * POST action=add:  scene_id, ath, atv, link_scene, tooltip  → tạo nav hotspot, trả {id, uuid}
 * POST action=del:  id                                       → xoá hotspot
 */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
require_once dirname(__DIR__, 2) . '/core/response.php';
auth_require();
csrf_check();

$action = $_POST['action'] ?? 'add';

if ($action === 'del') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        db()->prepare('DELETE FROM hotspots WHERE id = ?')->execute([$id]);
    }
    json_out(['ok' => true]);
}

$sceneId = (int)($_POST['scene_id'] ?? 0);
$scene = $sceneId ? db_one('SELECT id, tour_id, name FROM scenes WHERE id = ?', [$sceneId]) : null;
if (!$scene) {
    json_error('Scene không hợp lệ', 400);
}

$linkScene = trim((string)($_POST['link_scene'] ?? ''));
if ($linkScene === '' || !db_one('SELECT id FROM scenes WHERE tour_id = ? AND name = ?', [(int)$scene['tour_id'], $linkScene])) {
    json_error('Phòng đích không hợp lệ', 400);
}

// UUID tự sinh không trùng
$uuid = 'nav_' . substr(bin2hex(random_bytes(4)), 0, 8);

$ath = (float)($_POST['ath'] ?? 0);
$atv = (float)($_POST['atv'] ?? 0);
$tooltip = trim((string)($_POST['tooltip'] ?? '')) ?: null;

// Style nút điều hướng — chỉ chấp nhận các icon có sẵn
$allowedStyles = ['mui_ten', 'vongtron', 'giotnuoc', 'default'];
$style = (string)($_POST['style'] ?? '');
if (!in_array($style, $allowedStyles, true)) {
    $style = 'mui_ten';
}

db()->prepare(
    'INSERT INTO hotspots (tour_id, scene_id, uuid, uuid_parent, type, style, style_hover, ath, atv, link_scene, tooltip, sort)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
)->execute([
    (int)$scene['tour_id'], $sceneId, $uuid, $scene['name'], 'nav', $style, 'callout',
    $ath, $atv, $linkScene, $tooltip, 0,
]);

json_out(['ok' => true, 'id' => (int)db()->lastInsertId(), 'uuid' => $uuid, 'ath' => $ath, 'atv' => $atv, 'link' => $linkScene]);
