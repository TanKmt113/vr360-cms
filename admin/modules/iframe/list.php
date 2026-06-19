<?php
/** Quản lý iframe button theo scene. ?scene=<id> */
require_once dirname(__DIR__, 3) . '/core/db.php';
require_once dirname(__DIR__, 3) . '/core/auth.php';
$pageTitle = 'Iframe Button';
require dirname(__DIR__, 2) . '/includes/header.php';

$sceneId = (int)($_GET['scene'] ?? 0);
$scene = $sceneId ? db_one('SELECT s.*, t.id_path FROM scenes s JOIN tours t ON t.id=s.tour_id WHERE s.id=?', [$sceneId]) : null;
if (!$scene) { echo '<p>Thiếu scene. <a href="/admin/modules/scene/list.php">Chọn scene</a></p>'; require dirname(__DIR__, 2) . '/includes/footer.php'; exit; }
$items = db_all('SELECT * FROM iframe_buttons WHERE scene_id = ? ORDER BY id', [$sceneId]);
?>
<h1 class="h3 fw-bold mb-4">Iframe Button — <?= htmlspecialchars($scene['title']) ?></h1>
<div class="card border-0 shadow-sm mb-3" style="max-width:680px;"><div class="card-body">
    <p class="text-secondary small">Nút trong panorama mở nội dung nhúng (video YouTube, Google Maps, trang web...). Lấy toạ độ ath/atv bằng
        <a href="../hotspot/place.php?scene=<?= $sceneId ?>" target="_blank">trang đặt trực quan</a>.</p>
    <form method="post" action="/admin/api/save_iframe.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="scene_id" value="<?= $sceneId ?>">
        <p><input class="form-control" style="width:100%;" name="iframe_url" placeholder="https://... (URL nhúng)" required></p>
        <p>
            ath <input class="form-control" name="ath" type="number" step="any" value="0" style="width:90px;display:inline-block;">
            atv <input class="form-control" name="atv" type="number" step="any" value="0" style="width:90px;display:inline-block;">
            &nbsp; Icon (tùy chọn): <input class="form-control" type="file" name="icon" accept="image/*" style="display:inline-block;width:auto;">
        </p>
        <button type="submit" class="btn btn-success">Thêm</button>
    </form>
</div></div>
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <p><a href="/admin/modules/scene/list.php?tour=<?= (int)$scene['tour_id'] ?>">← Về scene</a></p>
    <table class="table table-hover align-middle">
        <thead><tr><th>URL</th><th>ath</th><th>atv</th><th>Icon</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $b): ?>
        <tr>
            <td style="max-width:340px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($b['iframe_url']) ?></td>
            <td><?= (float)$b['ath'] ?></td>
            <td><?= (float)$b['atv'] ?></td>
            <td><?= $b['icon'] ? '<img src="' . htmlspecialchars($b['icon']) . '" style="height:24px;">' : '—' ?></td>
            <td>
                <form method="post" action="/admin/api/delete_iframe.php" onsubmit="return confirm('Xoá?')">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-link text-danger p-0">Xoá</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?><tr><td colspan="5">Chưa có.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
