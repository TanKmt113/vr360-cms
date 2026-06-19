<?php
/**
 * GET /user/modules/iframe_button/php/iframe_button_list.php?id=<id_path>&scene=<name>
 * Trả danh sách iframe button của scene: [{ath, atv, iframe_url, icon}, ...]
 * (khớp endpoint mẫu — mẫu trả [] khi rỗng)
 */
require_once dirname(__DIR__, 4) . '/core/response.php';
require_once dirname(__DIR__, 4) . '/core/i18n.php';

$idPath = require_tour_id();
$tour = tour_by_path($idPath);
if (!$tour) {
    json_out([]);
}
$sceneName = trim((string)($_GET['scene'] ?? ''));
if ($sceneName === '') {
    json_out([]);
}

$rows = db_all(
    'SELECT b.ath, b.atv, b.iframe_url, b.icon
     FROM iframe_buttons b JOIN scenes s ON s.id = b.scene_id
     WHERE s.tour_id = ? AND s.name = ? ORDER BY b.id',
    [(int)$tour['id'], $sceneName]
);
foreach ($rows as &$r) {
    $r['ath'] = (float)$r['ath'];
    $r['atv'] = (float)$r['atv'];
}
json_out($rows);
