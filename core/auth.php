<?php
/**
 * Xác thực admin bằng session. Dùng cho panel /admin.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';

function auth_start(): void
{
    secure_session_start();
}

function auth_attempt(string $username, string $password): bool
{
    auth_start();
    $admin = db_one('SELECT id, username, password_hash, role FROM admins WHERE username = ? LIMIT 1', [$username]);
    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = [
            'id'       => (int)$admin['id'],
            'username' => $admin['username'],
            'role'     => $admin['role'],
        ];
        return true;
    }
    return false;
}

function auth_user(): ?array
{
    auth_start();
    return $_SESSION['admin'] ?? null;
}

/** Gọi đầu mỗi trang admin cần đăng nhập */
function auth_require(): void
{
    if (auth_user() === null) {
        header('Location: /admin/index.php');
        exit;
    }
}

function auth_logout(): void
{
    auth_start();
    $_SESSION = [];
    session_destroy();
}

/** Tạo hash mật khẩu (dùng khi seed / đổi mật khẩu) */
function auth_hash(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/** Token CSRF cho form admin */
function csrf_token(): string
{
    auth_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

/** Kiểm tra CSRF; thoát nếu sai */
function csrf_check(): void
{
    auth_start();
    // Trường hợp POST vượt post_max_size → PHP vứt sạch $_POST/$_FILES (không phải lỗi CSRF thật)
    $cl = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && $cl > 0) {
        http_response_code(413);
        $max = ini_get('post_max_size');
        exit('Dữ liệu gửi lên quá lớn (vượt giới hạn post_max_size = ' . $max . '). '
            . 'Với tour krpano lớn, hãy dùng cách "Import từ thư mục trên server" thay vì upload ZIP, '
            . 'hoặc tăng post_max_size/upload_max_filesize.');
    }
    $sent = $_POST['csrf'] ?? '';
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)$sent)) {
        http_response_code(419);
        exit('CSRF token không hợp lệ.');
    }
}
