<?php
/**
 * GET /user/includes/get_config.php?id=<id_path>
 * Trả config tổng cho viewer: tour + danh sách scene + hotspot + settings.
 * Tương ứng object util.general.allScenes của mẫu.
 */
require_once dirname(__DIR__, 2) . '/core/response.php';
require_once dirname(__DIR__, 2) . '/core/i18n.php';

$idPath = require_tour_id();
$tour = tour_by_path($idPath);
if (!$tour) {
    json_error('Tour not found', 404);
}
$tourId = (int)$tour['id'];

$scenes = db_all('SELECT * FROM scenes WHERE tour_id = ? ORDER BY sort ASC', [$tourId]);

$allScenes = [];
foreach ($scenes as $s) {
    $hsRows = db_all(
        'SELECT id, uuid, uuid_parent, style, style_hover, ath, atv, link_scene, tooltip
         FROM hotspots WHERE scene_id = ? ORDER BY sort ASC',
        [(int)$s['id']]
    );
    $hotspots = [];
    foreach ($hsRows as $r) {
        // Bản dịch đa ngôn ngữ: { lang_code: {title, content} }
        $tr = [];
        foreach (db_all('SELECT lang_code, title, content FROM hotspot_i18n WHERE hotspot_id = ?', [(int)$r['id']]) as $t) {
            $tr[$t['lang_code']] = ['title' => $t['title'], 'content' => $t['content']];
        }
        $hotspots[$r['uuid']] = [
            'UUID'        => $r['uuid'],
            'UUID_parent' => $r['uuid_parent'],
            'style'       => $r['style'],
            'style_hover' => $r['style_hover'],
            'ath'         => (float)$r['ath'],
            'atv'         => (float)$r['atv'],
            'Link'        => $r['link_scene'],
            'tooltip'     => $r['tooltip'],
            'i18n'        => $tr,
        ];
    }
    $gallery = db_all('SELECT image_url AS image, caption FROM gallery WHERE scene_id = ? ORDER BY sort, id', [(int)$s['id']]);
    $audio   = db_all('SELECT audio_url AS audio, name, `loop`, autoplay FROM audio_groups WHERE scene_id = ? ORDER BY id', [(int)$s['id']]);
    $videos  = db_all('SELECT video_url AS video, type, ath, atv FROM videos WHERE scene_id = ? ORDER BY id', [(int)$s['id']]);
    $iframes = db_all('SELECT ath, atv, iframe_url, icon FROM iframe_buttons WHERE scene_id = ? ORDER BY id', [(int)$s['id']]);

    $polyRows = db_all('SELECT points_json, action, link FROM polygon_hotspots WHERE scene_id = ? ORDER BY id', [(int)$s['id']]);
    $polygons = [];
    foreach ($polyRows as $pr) {
        $polygons[] = [
            'points' => json_decode((string)$pr['points_json'], true) ?: [],
            'action' => $pr['action'],
            'link'   => $pr['link'],
        ];
    }

    $allScenes[$s['name']] = [
        'name'      => $s['name'],
        'title'     => $s['title'],
        'thumb'     => $s['thumb_url'],
        'pano'      => $s['pano_url'],
        'view'      => ['hlookat' => (float)$s['hlookat'], 'vlookat' => (float)$s['vlookat'], 'fov' => (float)$s['fov']],
        'hotspots'  => $hotspots,
        'gallery'   => $gallery,
        'audio'     => $audio,
        'videos'    => $videos,
        'iframes'   => $iframes,
        'polygons'  => $polygons,
    ];
}

$settingsRows = db_all('SELECT `key`, `value` FROM settings WHERE tour_id = ?', [$tourId]);
$settings = [];
foreach ($settingsRows as $r) {
    $settings[$r['key']] = $r['value'];
}

// Minimap cấp tour
$mapRow = db_one('SELECT image_url, spots_json FROM minimaps WHERE tour_id = ? LIMIT 1', [$tourId]);
$minimap = $mapRow ? [
    'image' => $mapRow['image_url'],
    'spots' => json_decode((string)$mapRow['spots_json'], true) ?: [],
] : null;

json_out([
    'tour'      => ['id_path' => $tour['id_path'], 'title' => $tour['title'], 'default_lang' => $tour['default_lang']],
    'languages' => tour_languages($tourId),
    'settings'  => $settings,
    'minimap'   => $minimap,
    'allScenes' => $allScenes,
]);
