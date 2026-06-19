<?php
/** Quản lý gallery ảnh theo scene. /admin/modules/gallery/list.php?scene=<id> */
require_once dirname(__DIR__, 3) . '/core/db.php';
require_once dirname(__DIR__, 3) . '/core/auth.php';
$pageTitle = 'Gallery';
require dirname(__DIR__, 2) . '/includes/header.php';

$sceneId = (int)($_GET['scene'] ?? 0);
$scene = $sceneId ? db_one('SELECT s.*, t.id_path FROM scenes s JOIN tours t ON t.id=s.tour_id WHERE s.id=?', [$sceneId]) : null;
if (!$scene) { echo '<p>Thiếu scene. <a href="/admin/modules/scene/list.php">Chọn scene</a></p>'; require dirname(__DIR__, 2) . '/includes/footer.php'; exit; }
$items = db_all('SELECT * FROM gallery WHERE scene_id = ? ORDER BY sort, id', [$sceneId]);
?>
<h1 class="h3 fw-bold mb-4">Gallery — <?= htmlspecialchars($scene['title']) ?></h1>

<div class="card border-0 shadow-sm mb-3" style="max-width:680px;"><div class="card-body">
    <form method="post" action="/admin/api/save_gallery.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="scene_id" value="<?= $sceneId ?>">
        <p><b>Thêm ảnh</b> (chọn nhiều ảnh cùng lúc):</p>
        <input class="form-control" type="file" name="images[]" accept="image/*" multiple required>
        <p><input class="form-control" style="width:100%;" name="caption" placeholder="Chú thích (tùy chọn, áp cho tất cả ảnh vừa thêm)"></p>
        <button type="submit" class="btn btn-success">Upload</button>
    </form>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <p><a href="/admin/modules/scene/list.php?tour=<?= (int)$scene['tour_id'] ?>">← Về scene</a></p>
    <div style="display:flex;flex-wrap:wrap;gap:12px;">
        <?php foreach ($items as $g): ?>
            <div style="width:160px;border:1px solid #e3e8ee;border-radius:8px;overflow:hidden;background:#fff;">
                <img src="<?= htmlspecialchars($g['image_url']) ?>" style="width:100%;height:110px;object-fit:cover;display:block;">
                <div style="padding:6px;font-size:12px;">
                    <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars((string)$g['caption']) ?></div>
                    <form method="post" action="/admin/api/delete_gallery.php" onsubmit="return confirm('Xoá ảnh?')">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="id" value="<?= $g['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-link text-danger p-0">Xoá</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$items): ?><p>Chưa có ảnh.</p><?php endif; ?>
    </div>
</div></div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
