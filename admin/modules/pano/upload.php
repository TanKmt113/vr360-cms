<?php
/** Upload ảnh panorama (equirect) cho scene. /admin/modules/pano/upload.php?scene=<id> */
require_once dirname(__DIR__, 3) . '/core/db.php';
require_once dirname(__DIR__, 3) . '/core/auth.php';
$pageTitle = 'Upload Panorama';
require dirname(__DIR__, 2) . '/includes/header.php';

$sceneId = (int)($_GET['scene'] ?? 0);
$scene = $sceneId ? db_one('SELECT * FROM scenes WHERE id = ?', [$sceneId]) : null;
if (!$scene) { echo '<p>Thiếu scene.</p>'; require dirname(__DIR__, 2) . '/includes/footer.php'; exit; }
?>
<h1 class="h3 fw-bold mb-4">Upload Panorama — <?= htmlspecialchars($scene['title']) ?></h1>
<div class="card border-0 shadow-sm mb-3" style="max-width:640px;">
    <div class="card-body">
    <p class="text-secondary small">
        Tải lên 1 ảnh <b>equirectangular</b> (tỉ lệ 2:1, .jpg/.png). Ảnh sẽ hiển thị ngay dạng cầu (sphere).
        Muốn chất lượng cao dạng tiles (cube multires), dùng krpano Tools để tạo tiles rồi điền đường dẫn template ở phần Sửa scene.
    </p>
    <?php if (!empty($_GET['err'])): ?>
        <p class="alert alert-danger"><?= htmlspecialchars($_GET['err']) ?></p>
    <?php endif; ?>
    <?php if ($scene['pano_url']): ?>
        <p>Pano hiện tại: <code><?= htmlspecialchars($scene['pano_url']) ?></code></p>
    <?php endif; ?>
    <form method="post" action="/admin/api/upload_pano.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="scene_id" value="<?= $sceneId ?>">
        <p><b>Cách 1 — Ảnh equirect đơn:</b><br>
            <input type="file" name="pano" accept=".jpg,.jpeg,.png" class="form-control"></p>
        <label class="text-secondary small"><input type="checkbox" name="as_thumb" value="1" class="form-check-input"> Dùng làm thumbnail luôn</label>
        <div style="margin-top:16px;">
            <button type="submit" class="btn btn-primary">Upload ảnh</button>
            <a href="/admin/modules/scene/list.php?tour=<?= (int)$scene['tour_id'] ?>" style="margin-left:12px;">Về scene</a>
        </div>
    </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3" style="max-width:640px;">
    <div class="card-body">
    <p class="text-secondary small">
        <b>Cách 2 — Tiles chất lượng cao (ZIP):</b> dùng <b>krpano Tools</b> (MAKE VTOUR) trên máy để tạo thư mục
        <code>*.tiles</code>, nén thành .zip rồi tải lên. Hệ thống tự giải nén và nhận template cube multires.
    </p>
    <form method="post" action="/admin/api/upload_pano.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="scene_id" value="<?= $sceneId ?>">
        <input type="hidden" name="mode" value="tiles">
        <p><input type="file" name="tiles_zip" accept=".zip" class="form-control" required></p>
        <button type="submit" class="btn btn-success">Upload tiles ZIP</button>
    </form>
    </div>
</div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
