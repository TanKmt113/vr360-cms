<?php
/**
 * Đa ngôn ngữ: tải danh sách ngôn ngữ theo tour.
 * Trả về định dạng khớp get_language.php của mẫu:
 *   [{id, name, display_name, stt, img}, ...]
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function tour_languages(int $tourId): array
{
    $rows = db_all(
        'SELECT code AS id, name, display_name, sort AS stt, flag_img AS img
         FROM languages WHERE tour_id = ? ORDER BY sort ASC',
        [$tourId]
    );
    return $rows;
}

/** Lấy tour theo id_path (slug công khai), trả bản ghi hoặc null */
function tour_by_path(string $idPath): ?array
{
    return db_one('SELECT * FROM tours WHERE id_path = ? AND status = "active" LIMIT 1', [$idPath]);
}
