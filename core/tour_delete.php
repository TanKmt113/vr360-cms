<?php
/**
 * Xóa tour và dữ liệu liên quan (DB + thư mục upload theo id_path).
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/tour_import.php';

/**
 * @return array{ok:bool, error:?string, id_path:?string, title:?string}
 */
function delete_tour_by_id(int $tourId, bool $removeUploads = true): array
{
    $res = ['ok' => false, 'error' => null, 'id_path' => null, 'title' => null];
    $tour = db_one('SELECT id, id_path, title FROM tours WHERE id = ?', [$tourId]);
    if (!$tour) {
        $res['error'] = 'Tour không tồn tại.';
        return $res;
    }

    $res['id_path'] = (string)$tour['id_path'];
    $res['title'] = (string)$tour['title'];
    $safePath = preg_replace('/[^A-Za-z0-9_-]/', '', $res['id_path']);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        // analytics không có FK CASCADE
        $pdo->prepare('DELETE FROM analytics WHERE tour_id = ?')->execute([$tourId]);
        $pdo->prepare('DELETE FROM tours WHERE id = ?')->execute([$tourId]);
        $pdo->commit();
    } catch (Throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $res['error'] = 'Không xóa được tour trong DB.';
        return $res;
    }

    if ($removeUploads && $safePath !== '') {
        foreach (['panos', 'image', 'audio', 'video', 'pano'] as $kind) {
            $dir = UPLOAD_PATH . '/' . $kind . '/' . $safePath;
            if (is_dir($dir)) {
                rrmdir_path($dir);
            }
        }
    }

    $res['ok'] = true;
    return $res;
}
