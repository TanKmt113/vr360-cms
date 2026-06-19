<?php
/**
 * Kết nối DB qua PDO (singleton). Luôn dùng prepared statements.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    // Nếu có DB_SOCKET (vd aaPanel: /tmp/mysql.sock) thì kết nối qua socket,
    // ngược lại kết nối TCP qua host:port.
    $socket = defined('DB_SOCKET') ? DB_SOCKET : '';
    if ($socket !== '') {
        $dsn = sprintf('mysql:unix_socket=%s;dbname=%s;charset=utf8mb4', $socket, DB_NAME);
    } else {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    }
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // Ghi log lỗi thật để chẩn đoán (không lộ ra ngoài)
        $logDir = BASE_PATH . '/storage';
        @is_dir($logDir) || @mkdir($logDir, 0775, true);
        @file_put_contents(
            $logDir . '/db_error.log',
            '[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . ' | DSN=' . $dsn . "\n",
            FILE_APPEND
        );
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        $debug = (defined('APP_DEBUG') && APP_DEBUG);
        echo json_encode(
            $debug ? ['error' => 'DB connection failed', 'detail' => $e->getMessage(), 'dsn' => $dsn]
                   : ['error' => 'DB connection failed'],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
    return $pdo;
}

/** Lấy 1 dòng */
function db_one(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

/** Lấy nhiều dòng */
function db_all(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}
