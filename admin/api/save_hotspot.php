<?php
/** Lưu hotspot (thêm/sửa). POST /admin/api/save_hotspot.php */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
auth_require();
csrf_check();

$id      = (int)($_POST['id'] ?? 0);
$sceneId = (int)($_POST['scene_id'] ?? 0);
$tourId  = (int)($_POST['tour_id'] ?? 0);
if ($sceneId <= 0 || $tourId <= 0) {
    exit('Thiếu scene_id/tour_id');
}

$uuid = trim((string)($_POST['uuid'] ?? ''));
if ($uuid === '') {
    exit('UUID không được rỗng');
}

$data = [
    'uuid'        => $uuid,
    'uuid_parent' => (string)(db_one('SELECT name FROM scenes WHERE id=?', [$sceneId])['name'] ?? ''),
    'type'        => trim((string)($_POST['type'] ?? 'nav')),
    'style'       => trim((string)($_POST['style'] ?? 'default')),
    'style_hover' => 'callout',
    'ath'         => (float)($_POST['ath'] ?? 0),
    'atv'         => (float)($_POST['atv'] ?? 0),
    'link_scene'  => trim((string)($_POST['link_scene'] ?? '')) ?: null,
    'tooltip'     => trim((string)($_POST['tooltip'] ?? '')) ?: null,
    'sort'        => (int)($_POST['sort'] ?? 0),
];

if ($id > 0) {
    $st = db()->prepare(
        'UPDATE hotspots SET uuid=?, uuid_parent=?, type=?, style=?, style_hover=?, ath=?, atv=?, link_scene=?, tooltip=?, sort=?
         WHERE id=? AND scene_id=?'
    );
    $st->execute([$data['uuid'], $data['uuid_parent'], $data['type'], $data['style'], $data['style_hover'],
                  $data['ath'], $data['atv'], $data['link_scene'], $data['tooltip'], $data['sort'], $id, $sceneId]);
    $hotspotId = $id;
} else {
    $st = db()->prepare(
        'INSERT INTO hotspots (tour_id, scene_id, uuid, uuid_parent, type, style, style_hover, ath, atv, link_scene, tooltip, sort)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
    );
    $st->execute([$tourId, $sceneId, $data['uuid'], $data['uuid_parent'], $data['type'], $data['style'], $data['style_hover'],
                  $data['ath'], $data['atv'], $data['link_scene'], $data['tooltip'], $data['sort']]);
    $hotspotId = (int)db()->lastInsertId();
}

// Lưu bản dịch đa ngôn ngữ (upsert theo lang_code)
$i18n = $_POST['i18n'] ?? [];
if (is_array($i18n)) {
    $up = db()->prepare(
        'INSERT INTO hotspot_i18n (hotspot_id, lang_code, title, content) VALUES (?,?,?,?)
         ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content)'
    );
    foreach ($i18n as $code => $tr) {
        $code = preg_replace('/[^a-z]/', '', strtolower((string)$code));
        if ($code === '') {
            continue;
        }
        $up->execute([$hotspotId, $code, trim((string)($tr['title'] ?? '')), trim((string)($tr['content'] ?? ''))]);
    }
}

header('Location: /admin/modules/hotspot/list.php?scene=' . $sceneId);
exit;
