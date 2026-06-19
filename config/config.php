<?php
/**
 * Cấu hình trung tâm: đọc .env, khai báo hằng số đường dẫn.
 * Mọi file backend nên require file này đầu tiên.
 */
declare(strict_types=1);

// ---- Đọc .env (không dùng putenv/getenv vì có thể bị disable) ----
function &env_store(): array
{
    static $store = [];
    return $store;
}

function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    $store = &env_store();
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value);
        if ($key !== '') {
            $store[$key] = $value;
        }
    }
}

function env(string $key, ?string $default = null): ?string
{
    $store = &env_store();
    if (array_key_exists($key, $store)) {
        return $store[$key];
    }
    // Cho phép override qua biến môi trường thật nếu có
    if (isset($_ENV[$key])) {
        return $_ENV[$key];
    }
    return $default;
}

load_env(__DIR__ . '/.env');

// ---- Hằng số đường dẫn ----
define('BASE_PATH', dirname(__DIR__));          // .../vr360-cms
define('UPLOAD_PATH', BASE_PATH . '/upload');
define('BASE_URL', rtrim(env('BASE_URL', 'http://localhost:8000'), '/'));

// ---- Cấu hình DB ----
define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'vr360_cms'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_SOCKET', env('DB_SOCKET', ''));   // vd aaPanel: /tmp/mysql.sock

define('APP_SECRET', env('APP_SECRET', 'dev-secret'));
define('APP_DEBUG', env('APP_DEBUG', 'false') === 'true');

date_default_timezone_set('Asia/Ho_Chi_Minh');

// Polyfill mb_* nếu thiếu mbstring (an toàn cho mọi môi trường)
require_once __DIR__ . '/../core/mbcompat.php';
