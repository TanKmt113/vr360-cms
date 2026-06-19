<?php
/** Quản lý video gắn vị trí trong scene. ?scene=<id> */
require_once dirname(__DIR__, 3) . '/core/db.php';
require_once dirname(__DIR__, 3) . '/core/auth.php';
$pageTitle = 'Video';
require dirname(__DIR__, 2) . '/includes/header.php';

$sceneId = (int)($_GET['scene'] ?? 0);
$scene = $sceneId ? db_one('SELECT s.*, t.id_path FROM scenes s JOIN tours t ON t.id=s.tour_id WHERE s.id=?', [$sceneId]) : null;
if (!$scene) { echo '<p>Thiếu scene. <a href="/admin/modules/scene/list.php">Chọn scene</a></p>'; require dirname(__DIR__, 2) . '/includes/footer.php'; exit; }
$items = db_all('SELECT * FROM videos WHERE scene_id = ? ORDER BY id', [$sceneId]);
?>
<h1 class="h3 fw-bold mb-4">Video — <?= htmlspecialchars($scene['title']) ?></h1>

<div class="card border-0 shadow-sm mb-3" style="max-width:680px;"><div class="card-body">
    <form method="post" action="/admin/api/save_video.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="scene_id" value="<?= $sceneId ?>">
        <p><b>Thêm video</b> — dán URL hoặc upload file</p>
        <p><input class="form-control" style="width:100%;" name="video_url" placeholder="https://... (bỏ trống nếu upload file)"></p>
        <p>Hoặc upload: <input class="form-control" type="file" name="video" accept="video/mp4,video/webm" style="display:inline-block;width:auto;"></p>
        <p>
            ath <input class="form-control" name="ath" type="number" step="any" value="0" style="width:90px;display:inline-block;">
            atv <input class="form-control" name="atv" type="number" step="any" value="0" style="width:90px;display:inline-block;">
        </p>
        <button type="submit" class="btn btn-success">Lưu</button>
    </form>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <p><a href="/admin/modules/scene/list.php?tour=<?= (int)$scene['tour_id'] ?>">← Về scene</a></p>
    <table class="table table-hover align-middle">
        <thead><tr><th>URL</th><th>ath</th><th>atv</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $v): ?>
        <tr>
            <td style="max-width:360px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($v['video_url']) ?></td>
            <td><?= (float)$v['ath'] ?></td>
            <td><?= (float)$v['atv'] ?></td>
            <td>
                <form method="post" action="/admin/api/delete_video.php" onsubmit="return confirm('Xoá?')">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id" value="<?= $v['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-link text-danger p-0">Xoá</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?><tr><td colspan="4">Chưa có video.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
