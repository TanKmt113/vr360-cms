<?php
/**
 * Tiện ích bảo mật: security headers + cấu hình session an toàn.
 */
declare(strict_types=1);

/** Gửi các HTTP security header chung. Gọi đầu mỗi trang HTML/API. */
function send_security_headers(bool $isHtml = true): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 0');
    // Không lộ phiên bản PHP
    header_remove('X-Powered-By');
}

/** Khởi tạo session với cờ bảo mật (HttpOnly, SameSite). */
function secure_session_start(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    // Lưu session vào thư mục riêng trong project → luôn ghi được, không vướng open_basedir (aaPanel)
    if (defined('BASE_PATH')) {
        $sessDir = BASE_PATH . '/storage/sessions';
        if (!is_dir($sessDir)) {
            @mkdir($sessDir, 0775, true);
        }
        if (is_dir($sessDir) && is_writable($sessDir)) {
            session_save_path($sessDir);
        }
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $https,
        'samesite' => 'Lax',
    ]);
    session_start();
}
