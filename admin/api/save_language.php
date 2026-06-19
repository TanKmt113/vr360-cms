<?php
/** Thêm ngôn ngữ. POST /admin/api/save_language.php */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
auth_require();
csrf_check();

$tourId = (int)($_POST['tour_id'] ?? 0);
if (!db_one('SELECT id FROM tours WHERE id=?', [$tourId])) {
    exit('Tour không hợp lệ');
}
$code = strtolower(trim((string)($_POST['code'] ?? '')));
if (!preg_match('/^[a-z]{2,8}$/', $code)) {
    exit('Mã ngôn ngữ không hợp lệ (2-8 chữ cái)');
}

try {
    db()->prepare('INSERT INTO languages (tour_id, code, name, display_name, flag_img, sort) VALUES (?,?,?,?,?,?)')
        ->execute([
            $tourId, $code,
            trim((string)($_POST['name'] ?? '')),
            trim((string)($_POST['display_name'] ?? '')),
            trim((string)($_POST['flag_img'] ?? '')) ?: null,
            (int)($_POST['sort'] ?? 0),
        ]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        exit('Ngôn ngữ này đã tồn tại trong tour');
    }
    throw $e;
}

header('Location: /admin/modules/language/list.php?tour=' . $tourId);
exit;
