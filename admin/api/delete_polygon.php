<?php
/** Xoá polygon hotspot. POST /admin/api/delete_polygon.php */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
auth_require();
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$p = $id ? db_one('SELECT scene_id FROM polygon_hotspots WHERE id = ?', [$id]) : null;
if ($p) {
    db()->prepare('DELETE FROM polygon_hotspots WHERE id = ?')->execute([$id]);
}
header('Location: /admin/modules/polygon/list.php?scene=' . (int)($p['scene_id'] ?? 0));
exit;
