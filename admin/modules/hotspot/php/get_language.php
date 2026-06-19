<?php
/**
 * GET /admin/modules/hotspot/php/get_language.php?id=<id_path>
 * Trả danh sách ngôn ngữ của tour (khớp mẫu: [{id,name,display_name,stt,img}, ...]).
 */
require_once dirname(__DIR__, 4) . '/core/response.php';
require_once dirname(__DIR__, 4) . '/core/i18n.php';

$idPath = require_tour_id();
$tour = tour_by_path($idPath);
if (!$tour) {
    json_out([]);
}
json_out(tour_languages((int)$tour['id']));
