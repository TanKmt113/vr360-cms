<?php
/** Xử lý upload panorama: ảnh equirect (mặc định) hoặc tiles ZIP (mode=tiles). POST */
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
$idPath = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$scene['id_path']);
$redirect = '/admin/modules/pano/upload.php?scene=' . $sceneId;

// ---------- Cách 2: tiles ZIP ----------
if (($_POST['mode'] ?? '') === 'tiles') {
    if (!class_exists('ZipArchive')) {
        header('Location: ' . $redirect . '&err=' . urlencode('Server thiếu ZipArchive'));
        exit;
    }
    $f = $_FILES['tiles_zip'] ?? null;
    if (!$f || ($f['error'] ?? 1) !== UPLOAD_ERR_OK || strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) !== 'zip') {
        header('Location: ' . $redirect . '&err=' . urlencode('Cần file .zip hợp lệ'));
        exit;
    }
    if ($f['size'] > 500_000_000) {
        header('Location: ' . $redirect . '&err=' . urlencode('ZIP quá lớn (>500MB)'));
        exit;
    }

    $destRoot = UPLOAD_PATH . '/panos/' . $idPath . '/' . preg_replace('/[^A-Za-z0-9_.-]/', '_', (string)$scene['name']);
    if (!is_dir($destRoot)) {
        @mkdir($destRoot, 0775, true);
    }

    $zip = new ZipArchive();
    if ($zip->open($f['tmp_name']) !== true) {
        header('Location: ' . $redirect . '&err=' . urlencode('Không mở được ZIP'));
        exit;
    }
    $realRoot = realpath($destRoot);
    $tilesDir = null;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        // Chống zip-slip + bỏ đường dẫn tuyệt đối/..
        if ($name === false || str_contains($name, '..') || str_starts_with($name, '/')) {
            continue;
        }
        // Chỉ cho phép đuôi ảnh/xml an toàn
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($ext !== '' && !in_array($ext, ['jpg', 'jpeg', 'png', 'xml', 'gif', 'webp'], true)) {
            continue;
        }
        $target = $destRoot . '/' . $name;
        if (substr($name, -1) === '/') {
            @mkdir($target, 0775, true);
            continue;
        }
        $dir = dirname($target);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        // Xác nhận target nằm trong destRoot sau khi chuẩn hoá
        $checkDir = realpath($dir);
        if ($checkDir === false || strpos($checkDir, $realRoot) !== 0) {
            continue;
        }
        copy("zip://" . $f['tmp_name'] . "#" . $name, $target);
        // Ghi nhận thư mục .tiles để dựng template
        if (preg_match('#(^|/)([^/]+\.tiles)/#', $name, $m) && $tilesDir === null) {
            $tilesDir = $m[2];
        }
    }
    $zip->close();

    if ($tilesDir === null) {
        header('Location: ' . $redirect . '&err=' . urlencode('Không tìm thấy thư mục *.tiles trong ZIP'));
        exit;
    }

    // Template cube multires chuẩn krpano, đường dẫn tương đối trong /upload/panos/<id>/
    $rel = preg_replace('/[^A-Za-z0-9_.-]/', '_', (string)$scene['name']) . '/' . $tilesDir
         . '/%s/l%l/%v/l%l_%s_%v_%h.jpg';
    $thumbRel = preg_replace('/[^A-Za-z0-9_.-]/', '_', (string)$scene['name']) . '/' . $tilesDir . '/thumb.jpg';
    db()->prepare('UPDATE scenes SET pano_url = ?, thumb_url = ? WHERE id = ?')
        ->execute([$rel, $thumbRel, $sceneId]);

    header('Location: /admin/modules/scene/list.php?tour=' . (int)$scene['tour_id']);
    exit;
}

// ---------- Cách 1: ảnh equirect đơn ----------
$err = null;
$url = save_upload($_FILES['pano'] ?? [], 'pano', $idPath, $err);
if ($url === null) {
    header('Location: ' . $redirect . '&err=' . urlencode((string)$err));
    exit;
}

$fields = 'pano_url = ?';
$params = [$url];
if (!empty($_POST['as_thumb'])) {
    $fields .= ', thumb_url = ?';
    $params[] = $url;
}
$params[] = $sceneId;
db()->prepare("UPDATE scenes SET $fields WHERE id = ?")->execute($params);

header('Location: /admin/modules/scene/list.php?tour=' . (int)$scene['tour_id']);
exit;
