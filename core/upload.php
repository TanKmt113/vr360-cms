<?php
/**
 * Helper upload file an toàn cho admin.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

const UPLOAD_RULES = [
    'image' => ['ext' => ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'max' => 20_000_000],
    'audio' => ['ext' => ['mp3', 'm4a', 'ogg', 'wav'],          'max' => 50_000_000],
    'video' => ['ext' => ['mp4', 'webm'],                       'max' => 200_000_000],
    'pano'  => ['ext' => ['jpg', 'jpeg', 'png'],                'max' => 60_000_000],
];

/**
 * Lưu 1 file upload vào /upload/<kind>/<tour>/...
 * Trả về URL công khai (bắt đầu bằng /upload/...) hoặc null + thông báo lỗi qua $err.
 */
function save_upload(array $file, string $kind, string $tourPath, ?string &$err = null): ?string
{
    $err = null;
    if (!isset(UPLOAD_RULES[$kind])) {
        $err = 'Loại upload không hợp lệ';
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $err = 'Lỗi upload (mã ' . ($file['error'] ?? '?') . ')';
        return null;
    }
    $rule = UPLOAD_RULES[$kind];
    if ($file['size'] > $rule['max']) {
        $err = 'File quá lớn';
        return null;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $rule['ext'], true)) {
        $err = 'Định dạng không cho phép (.' . $ext . ')';
        return null;
    }

    // --- Kiểm tra nội dung thực tế (chống đổi đuôi để chèn mã) ---
    // Lớp 1: với ảnh → getimagesize() (luôn có, trả false nếu không phải ảnh thật)
    if (in_array($kind, ['image', 'pano'], true) && is_file($file['tmp_name'])) {
        $info = @getimagesize($file['tmp_name']);
        $imgType = [
            'jpg' => IMAGETYPE_JPEG, 'jpeg' => IMAGETYPE_JPEG, 'png' => IMAGETYPE_PNG,
            'gif' => IMAGETYPE_GIF, 'webp' => IMAGETYPE_WEBP,
        ];
        if ($info === false || (isset($imgType[$ext]) && (int)$info[2] !== $imgType[$ext])) {
            $err = 'File không phải ảnh hợp lệ hoặc sai định dạng';
            return null;
        }
    }
    // Lớp 2: nếu có fileinfo → kiểm tra MIME cho mọi loại (audio/video...)
    $allowedMime = [
        'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'],
        'gif' => ['image/gif'], 'webp' => ['image/webp'],
        'mp3' => ['audio/mpeg', 'application/octet-stream'], 'm4a' => ['audio/mp4', 'audio/x-m4a', 'application/octet-stream'],
        'ogg' => ['audio/ogg', 'application/ogg'], 'wav' => ['audio/x-wav', 'audio/wav'],
        'mp4' => ['video/mp4', 'application/octet-stream'], 'webm' => ['video/webm'],
    ];
    if (is_file($file['tmp_name']) && function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($fi, $file['tmp_name']);
        finfo_close($fi);
        if (isset($allowedMime[$ext]) && !in_array($mime, $allowedMime[$ext], true)) {
            $err = 'Nội dung file không khớp định dạng (' . $mime . ')';
            return null;
        }
    }

    $safeTour = preg_replace('/[^A-Za-z0-9_-]/', '', $tourPath) ?: 'misc';
    $dir = UPLOAD_PATH . '/' . $kind . '/' . $safeTour;
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        $err = 'Không tạo được thư mục lưu';
        return null;
    }

    $base = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
    $name = $base . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
    $dest = $dir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        // Fallback khi chạy CLI test (không phải HTTP upload thật)
        if (!@rename($file['tmp_name'], $dest)) {
            $err = 'Không lưu được file';
            return null;
        }
    }
    return '/upload/' . $kind . '/' . $safeTour . '/' . $name;
}
