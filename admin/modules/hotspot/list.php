<?php
/** Danh sách hotspot của 1 scene. /admin/modules/hotspot/list.php?scene=<id> */
require_once dirname(__DIR__, 3) . '/core/db.php';
$pageTitle = 'Quản lý Hotspot';
require dirname(__DIR__, 2) . '/includes/header.php';

$sceneId = (int)($_GET['scene'] ?? 0);
$scene = $sceneId ? db_one('SELECT * FROM scenes WHERE id = ?', [$sceneId]) : null;
if (!$scene) { echo '<p>Không tìm thấy scene. <a href="/admin/modules/scene/list.php">Về danh sách scene</a></p>'; require dirname(__DIR__, 2) . '/includes/footer.php'; exit; }
$hotspots = db_all('SELECT * FROM hotspots WHERE scene_id = ? ORDER BY sort, id', [$sceneId]);
?>
<h1 class="h3 fw-bold mb-4">Hotspot — <?= htmlspecialchars($scene['title']) ?></h1>
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <p>
        <a href="place.php?scene=<?= $sceneId ?>">🎯 Đặt trực quan (click trên ảnh)</a> &nbsp;·&nbsp;
        <a href="edit.php?scene=<?= $sceneId ?>">➕ Thêm thủ công</a> &nbsp;·&nbsp;
        <a href="/admin/modules/scene/list.php?tour=<?= (int)$scene['tour_id'] ?>">← Về scene</a> &nbsp;·&nbsp;
        <a href="/public/viewer.php?id=<?= urlencode((string)(db_one('SELECT id_path FROM tours WHERE id=?', [$scene['tour_id']])['id_path'] ?? '')) ?>" target="_blank">▶ Xem tour</a>
    </p>
    <table class="table table-hover align-middle">
        <thead><tr><th>UUID</th><th>Type</th><th>Style</th><th>ath</th><th>atv</th><th>Link scene</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($hotspots as $h): ?>
        <tr>
            <td><code><?= htmlspecialchars($h['uuid']) ?></code></td>
            <td><?= htmlspecialchars($h['type']) ?></td>
            <td><?= htmlspecialchars($h['style']) ?></td>
            <td><?= (float)$h['ath'] ?></td>
            <td><?= (float)$h['atv'] ?></td>
            <td><?= htmlspecialchars((string)$h['link_scene']) ?></td>
            <td>
                <a href="edit.php?id=<?= $h['id'] ?>">Sửa</a> ·
                <form method="post" action="/admin/api/delete_hotspot.php" style="display:inline" onsubmit="return confirm('Xoá hotspot này?')">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id" value="<?= $h['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-link text-danger p-0" style="border:0;background:none;">Xoá</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$hotspots): ?><tr><td colspan="7">Chưa có hotspot.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
