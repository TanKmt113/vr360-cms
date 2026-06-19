<?php
/** Lưu minimap: upload bản đồ (mode=map) hoặc lưu điểm (mode=spots). POST */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
require_once dirname(__DIR__, 2) . '/core/upload.php';
auth_require();
csrf_check();

$tourId = (int)($_POST['tour_id'] ?? 0);
$tour = $tourId ? db_one('SELECT id, id_path FROM tours WHERE id = ?', [$tourId]) : null;
if (!$tour) {
    exit('Không tìm thấy tour');
}
$mode = $_POST['mode'] ?? '';

// Đảm bảo có 1 bản ghi minimap cho tour
$map = db_one('SELECT * FROM minimaps WHERE tour_id = ? LIMIT 1', [$tourId]);

if ($mode === 'map') {
    $err = null;
    $url = save_upload($_FILES['map'] ?? [], 'image', (string)$tour['id_path'], $err);
    if ($url === null) {
        exit('Lỗi upload: ' . $err);
    }
    if ($map) {
        db()->prepare('UPDATE minimaps SET image_url = ? WHERE id = ?')->execute([$url, (int)$map['id']]);
    } else {
        db()->prepare('INSERT INTO minimaps (tour_id, image_url, spots_json) VALUES (?,?,?)')->execute([$tourId, $url, '[]']);
    }
} elseif ($mode === 'spots') {
    $spots = json_decode((string)($_POST['spots_json'] ?? ''), true);
    if (!is_array($spots)) {
        $spots = [];
    }
    $clean = [];
    foreach ($spots as $sp) {
        if (isset($sp['scene'], $sp['x'], $sp['y'])) {
            $clean[] = ['scene' => (string)$sp['scene'], 'x' => (float)$sp['x'], 'y' => (float)$sp['y']];
        }
    }
    $json = json_encode($clean, JSON_UNESCAPED_UNICODE);
    if ($map) {
        db()->prepare('UPDATE minimaps SET spots_json = ? WHERE id = ?')->execute([$json, (int)$map['id']]);
    } else {
        db()->prepare('INSERT INTO minimaps (tour_id, image_url, spots_json) VALUES (?,?,?)')->execute([$tourId, '', $json]);
    }
}

header('Location: /admin/modules/minimap/edit.php?tour=' . $tourId);
exit;
