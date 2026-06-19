<?php
/** Tạo tour mới. POST /admin/api/save_tour.php */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
auth_require();
csrf_check();

$idPath = trim((string)($_POST['id_path'] ?? ''));
$title  = trim((string)($_POST['title'] ?? ''));
$lang   = trim((string)($_POST['default_lang'] ?? 'vn')) ?: 'vn';

if (!preg_match('/^[A-Za-z0-9_-]+$/', $idPath)) {
    exit('id_path không hợp lệ (chỉ chữ, số, gạch ngang/dưới).');
}
if ($title === '') {
    exit('Cần tiêu đề tour.');
}
if (db_one('SELECT id FROM tours WHERE id_path = ?', [$idPath])) {
    exit('id_path này đã tồn tại, hãy chọn tên khác.');
}

db()->prepare('INSERT INTO tours (id_path, title, default_lang, theme, status) VALUES (?,?,?,?,?)')
    ->execute([$idPath, $title, $lang, 'heritage', 'active']);
$tourId = (int)db()->lastInsertId();

// Ngôn ngữ mặc định
db()->prepare('INSERT INTO languages (tour_id, code, name, display_name, sort) VALUES (?,?,?,?,1)')
    ->execute([$tourId, $lang, strtoupper($lang), $lang === 'vn' ? 'Tiếng Việt' : strtoupper($lang)]);

header('Location: /admin/dashboard.php');
exit;
