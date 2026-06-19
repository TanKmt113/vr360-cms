<?php
/** Lưu chatbot: cài đặt (mode=config) hoặc thêm Q&A (mode=qa). POST */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
auth_require();
csrf_check();

$tourId = (int)($_POST['tour_id'] ?? 0);
if (!db_one('SELECT id FROM tours WHERE id = ?', [$tourId])) {
    exit('Tour không hợp lệ');
}
$mode = $_POST['mode'] ?? '';

if ($mode === 'config') {
    $up = db()->prepare(
        'INSERT INTO settings (tour_id, `key`, `value`) VALUES (?,?,?)
         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
    );
    $up->execute([$tourId, 'chatbot_enabled', isset($_POST['chatbot_enabled']) ? 'true' : 'false']);
    $up->execute([$tourId, 'chatbot_greeting', trim((string)($_POST['chatbot_greeting'] ?? ''))]);

} elseif ($mode === 'qa') {
    $question = trim((string)($_POST['question'] ?? ''));
    $answer   = trim((string)($_POST['answer'] ?? ''));
    if ($question === '' || $answer === '') {
        exit('Cần câu hỏi và câu trả lời');
    }
    db()->prepare('INSERT INTO chatbot_qa (tour_id, question, answer, keywords, sort) VALUES (?,?,?,?,0)')
        ->execute([$tourId, $question, $answer, trim((string)($_POST['keywords'] ?? '')) ?: null]);
}

header('Location: /admin/modules/chatbot/list.php?tour=' . $tourId);
exit;
