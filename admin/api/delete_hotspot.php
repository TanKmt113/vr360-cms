<?php
/** Xoá hotspot. POST /admin/api/delete_hotspot.php */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
auth_require();
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$sceneId = (int)(db_one('SELECT scene_id FROM hotspots WHERE id=?', [$id])['scene_id'] ?? 0);
if ($id > 0) {
    db()->prepare('DELETE FROM hotspots WHERE id = ?')->execute([$id]);
}
header('Location: /admin/modules/hotspot/list.php?scene=' . $sceneId);
exit;
