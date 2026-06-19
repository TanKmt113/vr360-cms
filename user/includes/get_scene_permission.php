<?php
/**
 * GET /user/includes/get_scene_permission.php?id=<id_path>
 * Trả map { scene_name: rule } cho các scene bị khoá. Mặc định rỗng = public hết.
 */
require_once dirname(__DIR__, 2) . '/core/response.php';
require_once dirname(__DIR__, 2) . '/core/i18n.php';

secure_session_start();

$idPath = require_tour_id();
$tour = tour_by_path($idPath);
if (!$tour) {
    json_out([]);
}

// Nếu khách đã đăng nhập tour này → không scene nào bị khoá nữa
if (!empty($_SESSION['tour_access'][(int)$tour['id']])) {
    json_out([]);
}

$rows = db_all(
    'SELECT s.name, sp.rule
     FROM scene_permission sp
     JOIN scenes s ON s.id = sp.scene_id
     WHERE sp.tour_id = ?',
    [(int)$tour['id']]
);

$map = [];
foreach ($rows as $r) {
    $map[$r['name']] = $r['rule'];
}
json_out($map);
