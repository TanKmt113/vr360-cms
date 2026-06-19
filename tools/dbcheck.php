<?php
/**
 * Chẩn đoán kết nối DB. Chạy:
 *   - CLI:  php tools/dbcheck.php
 *   - Web:  /tools/dbcheck.php   (XOÁ file này sau khi xong vì lộ thông tin cấu hình!)
 */
require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== VR360 CMS — Kiểm tra kết nối DB ===\n\n";

echo "Cấu hình đang dùng (.env):\n";
echo "  DB_HOST   = " . DB_HOST . "\n";
echo "  DB_PORT   = " . DB_PORT . "\n";
echo "  DB_NAME   = " . DB_NAME . "\n";
echo "  DB_USER   = " . DB_USER . "\n";
echo "  DB_PASS   = " . (DB_PASS === '' ? '(rỗng)' : '(' . strlen(DB_PASS) . ' ký tự)') . "\n";
echo "  DB_SOCKET = " . (DB_SOCKET === '' ? '(không dùng)' : DB_SOCKET) . "\n\n";

echo "Extension PHP:\n";
foreach (['pdo_mysql', 'mbstring', 'fileinfo', 'zip', 'gd'] as $ext) {
    echo "  $ext: " . (extension_loaded($ext) ? 'CÓ' : 'THIẾU') . "\n";
}
echo "\n";

// Các socket phổ biến nếu DB_SOCKET trống
if (DB_SOCKET === '') {
    echo "Dò socket MySQL phổ biến (nếu host:port không nối được, thử các đường dẫn này cho DB_SOCKET):\n";
    foreach (['/tmp/mysql.sock', '/www/server/data/mysql.sock', '/var/run/mysqld/mysqld.sock', '/var/lib/mysql/mysql.sock'] as $s) {
        echo "  $s: " . (file_exists($s) ? 'TỒN TẠI ✅' : 'không có') . "\n";
    }
    echo "\n";
}

if (DB_SOCKET !== '') {
    $dsn = sprintf('mysql:unix_socket=%s;dbname=%s;charset=utf8mb4', DB_SOCKET, DB_NAME);
} else {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
}
echo "DSN: $dsn\n\n";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "✅ KẾT NỐI THÀNH CÔNG\n";
    $v = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "   MySQL/MariaDB version: $v\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "   Số bảng trong DB '" . DB_NAME . "': " . count($tables) . "\n";
    if (!in_array('admins', $tables, true)) {
        echo "   ⚠️  CHƯA có bảng 'admins' — bạn cần chạy database/schema.sql + seed.sql\n";
    }
} catch (PDOException $e) {
    echo "❌ LỖI KẾT NỐI:\n   " . $e->getMessage() . "\n\n";
    echo "Gợi ý xử lý:\n";
    echo "  - 'Access denied': sai DB_USER/DB_PASS.\n";
    echo "  - 'Unknown database': DB_NAME sai hoặc chưa tạo DB (chạy schema.sql).\n";
    echo "  - 'Connection refused' / 'No such file': MySQL chưa chạy, hoặc sai host/port/socket.\n";
    echo "    → Thử DB_HOST=127.0.0.1, hoặc điền DB_SOCKET theo danh sách dò ở trên.\n";
}
