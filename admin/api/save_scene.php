<?php
/** Lưu scene (thêm/sửa). POST /admin/api/save_scene.php */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
auth_require();
csrf_check();

$id      = (int)($_POST['id'] ?? 0);
$tourId  = (int)($_POST['tour_id'] ?? 0);
if ($tourId <= 0) {
    exit('Thiếu tour_id');
}

$data = [
    'name'      => trim((string)($_POST['name'] ?? '')),
    'title'     => trim((string)($_POST['title'] ?? '')),
    'pano_url'  => trim((string)($_POST['pano_url'] ?? '')),
    'thumb_url' => trim((string)($_POST['thumb_url'] ?? '')),
    'hlookat'   => (float)($_POST['hlookat'] ?? 0),
    'vlookat'   => (float)($_POST['vlookat'] ?? 0),
    'fov'       => (float)($_POST['fov'] ?? 120),
    'sort'      => (int)($_POST['sort'] ?? 0),
];
if ($data['name'] === '') {
    exit('Name không được rỗng');
}

if ($id > 0) {
    $st = db()->prepare(
        'UPDATE scenes SET name=?, title=?, pano_url=?, thumb_url=?, hlookat=?, vlookat=?, fov=?, sort=?
         WHERE id=? AND tour_id=?'
    );
    $st->execute([$data['name'], $data['title'], $data['pano_url'], $data['thumb_url'],
                  $data['hlookat'], $data['vlookat'], $data['fov'], $data['sort'], $id, $tourId]);
} else {
    $st = db()->prepare(
        'INSERT INTO scenes (tour_id, name, title, pano_url, thumb_url, hlookat, vlookat, fov, sort)
         VALUES (?,?,?,?,?,?,?,?,?)'
    );
    $st->execute([$tourId, $data['name'], $data['title'], $data['pano_url'], $data['thumb_url'],
                  $data['hlookat'], $data['vlookat'], $data['fov'], $data['sort']]);
}

header('Location: /admin/modules/scene/list.php?tour=' . $tourId);
exit;
