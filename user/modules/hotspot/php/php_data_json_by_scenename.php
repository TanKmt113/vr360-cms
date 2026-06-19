<?php
/**
 * GET /user/modules/hotspot/php/php_data_json_by_scenename.php?id=<id_path>&scenename=<name>
 * Trả hotspot_data theo scene, khớp cấu trúc mẫu:
 *   { "<uuid>": { UUID, UUID_parent, style, style_hover, ath, atv, Link, tooltip }, ... }
 */
require_once dirname(__DIR__, 4) . '/core/response.php';
require_once dirname(__DIR__, 4) . '/core/i18n.php';

$idPath = require_tour_id();
$scenename = trim((string)($_GET['scenename'] ?? ''));
if ($scenename === '') {
    json_error('Missing scenename');
}

$tour = tour_by_path($idPath);
if (!$tour) {
    json_out([]);
}

$scene = db_one('SELECT id, name FROM scenes WHERE tour_id = ? AND name = ? LIMIT 1', [(int)$tour['id'], $scenename]);
if (!$scene) {
    json_out([]);
}

$rows = db_all(
    'SELECT uuid, uuid_parent, style, style_hover, ath, atv, link_scene, tooltip
     FROM hotspots WHERE scene_id = ? ORDER BY sort ASC',
    [(int)$scene['id']]
);

$out = [];
foreach ($rows as $r) {
    $out[$r['uuid']] = [
        'UUID'        => $r['uuid'],
        'UUID_parent' => $r['uuid_parent'],
        'style'       => $r['style'],
        'style_hover' => $r['style_hover'],
        'ath'         => (float)$r['ath'],
        'atv'         => (float)$r['atv'],
        'Link'        => $r['link_scene'],
        'tooltip'     => $r['tooltip'],
    ];
}
json_out($out);
