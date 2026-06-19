<?php
/**
 * Rate limiting đơn giản theo IP, lưu file (sliding window).
 * Không cần Redis — phù hợp shared hosting.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

/**
 * Trả về true nếu CÒN trong hạn mức (được phép), false nếu vượt.
 * @param string $bucket  tên nhóm (vd: 'login', 'chatbot')
 * @param int    $max     số request tối đa
 * @param int    $window  cửa sổ thời gian (giây)
 */
function rate_limit_ok(string $bucket, int $max, int $window): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    $key = preg_replace('/[^a-z0-9_]/i', '_', $bucket . '_' . $ip);
    $dir = defined('BASE_PATH') ? BASE_PATH . '/storage/ratelimit' : sys_get_temp_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $file = $dir . '/' . $key . '.json';

    $now = time();
    $hits = [];
    if (is_file($file)) {
        $hits = json_decode((string)@file_get_contents($file), true) ?: [];
    }
    // Giữ lại các lần trong cửa sổ
    $hits = array_values(array_filter($hits, fn($t) => ($now - (int)$t) < $window));

    if (count($hits) >= $max) {
        return false;
    }
    $hits[] = $now;
    @file_put_contents($file, json_encode($hits), LOCK_EX);
    return true;
}

/** Chặn và trả lỗi 429 nếu vượt hạn mức. */
function rate_limit_enforce(string $bucket, int $max, int $window, bool $json = true): void
{
    if (rate_limit_ok($bucket, $max, $window)) {
        return;
    }
    http_response_code(429);
    header('Retry-After: ' . $window);
    if ($json) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Quá nhiều yêu cầu, vui lòng thử lại sau.'], JSON_UNESCAPED_UNICODE);
    } else {
        echo 'Quá nhiều yêu cầu, vui lòng thử lại sau.';
    }
    exit;
}
