<?php
/**
 * GET /user/includes/get_meta.php?id=<id_path>
 * Trả meta tour. Khớp định dạng mẫu: [{"audio":"/upload/audio/xxx.mp3"}]
 */
require_once dirname(__DIR__, 2) . '/core/response.php';
require_once dirname(__DIR__, 2) . '/core/i18n.php';

$idPath = require_tour_id();
$tour = tour_by_path($idPath);
if (!$tour) {
    json_out([]); // mẫu trả mảng rỗng khi không có dữ liệu
}

json_out([
    ['audio' => $tour['bg_audio_url'] ?? '']
]);
