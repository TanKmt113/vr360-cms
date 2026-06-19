<?php
/** Xoá iframe button. POST /admin/api/delete_iframe.php */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
auth_require();
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$b = $id ? db_one('SELECT scene_id FROM iframe_buttons WHERE id = ?', [$id]) : null;
if ($b) {
    db()->prepare('DELETE FROM iframe_buttons WHERE id = ?')->execute([$id]);
}
header('Location: /admin/modules/iframe/list.php?scene=' . (int)($b['scene_id'] ?? 0));
exit;
