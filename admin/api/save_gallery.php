<?php
/** Upload nhiều ảnh gallery cho scene. POST /admin/api/save_gallery.php */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
require_once dirname(__DIR__, 2) . '/core/upload.php';
auth_require();
csrf_check();

$sceneId = (int)($_POST['scene_id'] ?? 0);
$scene = $sceneId ? db_one('SELECT s.*, t.id_path FROM scenes s JOIN tours t ON t.id=s.tour_id WHERE s.id=?', [$sceneId]) : null;
if (!$scene) {
    exit('Không tìm thấy scene');
}
$caption = trim((string)($_POST['caption'] ?? '')) ?: null;

$files = $_FILES['images'] ?? null;
if ($files && is_array($files['name'])) {
    $st = db()->prepare('INSERT INTO gallery (tour_id, scene_id, image_url, caption, sort) VALUES (?,?,?,?,0)');
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        $one = [
            'name'     => $files['name'][$i],
            'type'     => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error'    => $files['error'][$i],
            'size'     => $files['size'][$i],
        ];
        $err = null;
        $url = save_upload($one, 'image', (string)$scene['id_path'], $err);
        if ($url !== null) {
            $st->execute([(int)$scene['tour_id'], $sceneId, $url, $caption]);
        }
    }
}

header('Location: /admin/modules/gallery/list.php?scene=' . $sceneId);
exit;
