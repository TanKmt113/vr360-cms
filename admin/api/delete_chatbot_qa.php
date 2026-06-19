<?php
/** Xoá 1 cặp Q&A chatbot. POST /admin/api/delete_chatbot_qa.php */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
auth_require();
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$q = $id ? db_one('SELECT tour_id FROM chatbot_qa WHERE id = ?', [$id]) : null;
if ($q) {
    db()->prepare('DELETE FROM chatbot_qa WHERE id = ?')->execute([$id]);
}
header('Location: /admin/modules/chatbot/list.php?tour=' . (int)($q['tour_id'] ?? 0));
exit;
