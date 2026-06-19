<?php
/**
 * POST /user/includes/tour_login.php
 * Body: id=<id_path>&password=<...>
 * Khách nhập mật khẩu để mở các scene bị khoá. Đặt cờ session, trả {ok}.
 */
require_once dirname(__DIR__, 2) . '/core/response.php';
require_once dirname(__DIR__, 2) . '/core/i18n.php';
require_once dirname(__DIR__, 2) . '/core/ratelimit.php';

secure_session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('POST only', 405);
}

// Chống dò mật khẩu tour: 10 lần / 5 phút / IP
rate_limit_enforce('tour_login', 10, 300);

$idPath = trim((string)($_POST['id'] ?? ''));
$tour = $idPath !== '' ? tour_by_path($idPath) : null;
if (!$tour) {
    json_out(['ok' => false, 'error' => 'tour not found']);
}

$pw = (string)($_POST['password'] ?? '');
$hash = $tour['access_password_hash'] ?? null;

if ($hash && password_verify($pw, $hash)) {
    $_SESSION['tour_access'][(int)$tour['id']] = true;
    json_out(['ok' => true]);
}
json_out(['ok' => false, 'error' => 'wrong password'], 401);
