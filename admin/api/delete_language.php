<?php
/** Xoá ngôn ngữ. POST /admin/api/delete_language.php */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
auth_require();
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$l = $id ? db_one('SELECT tour_id, code FROM languages WHERE id = ?', [$id]) : null;
if ($l) {
    db()->prepare('DELETE FROM languages WHERE id = ?')->execute([$id]);
    // Dọn bản dịch hotspot theo mã ngôn ngữ đó (trong cùng tour)
    db()->prepare(
        'DELETE hi FROM hotspot_i18n hi JOIN hotspots h ON h.id = hi.hotspot_id
         WHERE h.tour_id = ? AND hi.lang_code = ?'
    )->execute([(int)$l['tour_id'], $l['code']]);
}
header('Location: /admin/modules/language/list.php?tour=' . (int)($l['tour_id'] ?? 0));
exit;
