<?php
/**
 * GET /user/modules/hotspot/php/get_gallery_image.php?id=<id_path>&scene=<name>[&hotspot=<uuid>]
 * Trả danh sách ảnh gallery: [{image, caption}, ...]
 */
require_once dirname(__DIR__, 4) . '/core/response.php';
require_once dirname(__DIR__, 4) . '/core/i18n.php';

$idPath = require_tour_id();
$tour = tour_by_path($idPath);
if (!$tour) {
    json_out([]);
}

$sceneName = trim((string)($_GET['scene'] ?? ''));
$hotspotUuid = trim((string)($_GET['hotspot'] ?? ''));

if ($hotspotUuid !== '') {
    $rows = db_all(
        'SELECT g.image_url AS image, g.caption
         FROM gallery g JOIN hotspots h ON h.id = g.hotspot_id
         WHERE g.tour_id = ? AND h.uuid = ? ORDER BY g.sort, g.id',
        [(int)$tour['id'], $hotspotUuid]
    );
} elseif ($sceneName !== '') {
    $rows = db_all(
        'SELECT g.image_url AS image, g.caption
         FROM gallery g JOIN scenes s ON s.id = g.scene_id
         WHERE g.tour_id = ? AND s.name = ? ORDER BY g.sort, g.id',
        [(int)$tour['id'], $sceneName]
    );
} else {
    $rows = db_all('SELECT image_url AS image, caption FROM gallery WHERE tour_id = ? ORDER BY sort, id', [(int)$tour['id']]);
}

json_out($rows);
