<?php
/** Quản lý audio theo scene (hoặc toàn tour nếu không chọn scene). ?scene=<id> */
require_once dirname(__DIR__, 3) . '/core/db.php';
require_once dirname(__DIR__, 3) . '/core/auth.php';
$pageTitle = 'Audio';
require dirname(__DIR__, 2) . '/includes/header.php';

$sceneId = (int)($_GET['scene'] ?? 0);
$scene = $sceneId ? db_one('SELECT s.*, t.id_path FROM scenes s JOIN tours t ON t.id=s.tour_id WHERE s.id=?', [$sceneId]) : null;
if (!$scene) { echo '<p>Thiếu scene. <a href="/admin/modules/scene/list.php">Chọn scene</a></p>'; require dirname(__DIR__, 2) . '/includes/footer.php'; exit; }
$items = db_all('SELECT * FROM audio_groups WHERE scene_id = ? ORDER BY id', [$sceneId]);
?>
<h1 class="h3 fw-bold mb-4">Audio — <?= htmlspecialchars($scene['title']) ?></h1>

<div class="card border-0 shadow-sm mb-3" style="max-width:680px;"><div class="card-body">
    <form method="post" action="/admin/api/save_audio.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="scene_id" value="<?= $sceneId ?>">
        <p><b>Thêm audio</b></p>
        <p><input class="form-control" style="width:100%;" name="name" placeholder="Tên (vd: Thuyết minh phòng A)"></p>
        <p>File: <input class="form-control" type="file" name="audio" accept="audio/*" required style="display:inline-block;width:auto;"></p>
        <label class="form-check-label" style="font-size:13px;"><input class="form-check-input" type="checkbox" name="loop" value="1" checked> Lặp</label>
        <label class="form-check-label" style="font-size:13px;margin-left:14px;"><input class="form-check-input" type="checkbox" name="autoplay" value="1"> Tự phát</label>
        <p><button type="submit" class="btn btn-success">Upload</button></p>
    </form>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <p><a href="/admin/modules/scene/list.php?tour=<?= (int)$scene['tour_id'] ?>">← Về scene</a></p>
    <table class="table table-hover align-middle">
        <thead><tr><th>Tên</th><th>File</th><th>Loop</th><th>Auto</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $a): ?>
        <tr>
            <td><?= htmlspecialchars((string)$a['name']) ?></td>
            <td><audio controls src="<?= htmlspecialchars($a['audio_url']) ?>" style="height:30px;"></audio></td>
            <td><?= $a['loop'] ? '✓' : '' ?></td>
            <td><?= $a['autoplay'] ? '✓' : '' ?></td>
            <td>
                <form method="post" action="/admin/api/delete_audio.php" onsubmit="return confirm('Xoá?')">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-link text-danger p-0">Xoá</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?><tr><td colspan="5">Chưa có audio.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
