<?php
/** Xóa tour. POST /admin/api/delete_tour.php */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
require_once dirname(__DIR__, 2) . '/core/tour_delete.php';
auth_require();
csrf_check();

function back(string $key, string $msg): void
{
    header('Location: /admin/dashboard.php?' . $key . '=' . urlencode($msg));
    exit;
}

$tourId = (int)($_POST['tour_id'] ?? 0);
$confirm = trim((string)($_POST['confirm_id_path'] ?? ''));
$tour = $tourId ? db_one('SELECT id, id_path, title FROM tours WHERE id = ?', [$tourId]) : null;

if (!$tour) {
    back('err', 'Tour không hợp lệ.');
}
if (!hash_equals((string)$tour['id_path'], $confirm)) {
    back('err', 'Xác nhận sai id_path. Gõ chính xác: ' . $tour['id_path']);
}

$r = delete_tour_by_id($tourId, !empty($_POST['remove_files']));
if (!$r['ok']) {
    back('err', $r['error'] ?? 'Xóa tour thất bại.');
}

back('msg', 'Đã xóa tour "' . $tour['title'] . '" (' . $tour['id_path'] . ').');
