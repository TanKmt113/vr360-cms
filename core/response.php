<?php
/**
 * Helper trả response JSON cho các API đọc của viewer.
 * Khớp định dạng mẫu (FE krpano fetch().json()).
 */
declare(strict_types=1);

require_once __DIR__ . '/security.php';

function json_out($data, int $status = 200): void
{
    send_security_headers(false);
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    // Cho phép viewer gọi từ domain khác (tour có thể nhúng iframe)
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error(string $message, int $status = 400): void
{
    json_out(['error' => $message], $status);
}

/** Lấy tham số tour id từ query (?id=), bắt buộc */
function require_tour_id(): string
{
    $id = $_GET['id'] ?? '';
    $id = trim((string)$id);
    if ($id === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
        json_error('Missing or invalid tour id', 400);
    }
    return $id;
}
