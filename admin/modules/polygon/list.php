<?php
/** Danh sách polygon hotspot của scene. ?scene=<id> */
require_once dirname(__DIR__, 3) . '/core/db.php';
require_once dirname(__DIR__, 3) . '/core/auth.php';
$pageTitle = 'Polygon Hotspot';
require dirname(__DIR__, 2) . '/includes/header.php';

$sceneId = (int)($_GET['scene'] ?? 0);
$scene = $sceneId ? db_one('SELECT * FROM scenes WHERE id=?', [$sceneId]) : null;
if (!$scene) { echo '<p>Thiếu scene. <a href="/admin/modules/scene/list.php">Chọn scene</a></p>'; require dirname(__DIR__, 2) . '/includes/footer.php'; exit; }
$items = db_all('SELECT * FROM polygon_hotspots WHERE scene_id = ? ORDER BY id', [$sceneId]);
?>
<h1 class="h3 fw-bold mb-4">Polygon Hotspot — <?= htmlspecialchars($scene['title']) ?></h1>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
    <p>
        <a href="place.php?scene=<?= $sceneId ?>">🎯 Vẽ polygon trực quan</a> &nbsp;·&nbsp;
        <a href="/admin/modules/scene/list.php?tour=<?= (int)$scene['tour_id'] ?>">← Về scene</a>
    </p>
    <table class="table table-hover align-middle">
        <thead>
        <tr><th>#</th><th>Số điểm</th><th>Action</th><th>Link</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($items as $p):
            $pts = json_decode((string)$p['points_json'], true) ?: []; ?>
        <tr>
            <td><?= $p['id'] ?></td>
            <td><?= count($pts) ?></td>
            <td><?= htmlspecialchars($p['action']) ?></td>
            <td><?= htmlspecialchars((string)$p['link']) ?></td>
            <td>
                <form method="post" action="/admin/api/delete_polygon.php" onsubmit="return confirm('Xoá?')">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Xoá</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?><tr><td colspan="5">Chưa có polygon.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
