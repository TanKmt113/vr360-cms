<?php
/** Danh sách + chọn tour, quản lý scene. /admin/modules/scene/list.php?tour=<id> */
require_once dirname(__DIR__, 3) . '/core/db.php';
$pageTitle = 'Quản lý Scene';
require dirname(__DIR__, 2) . '/includes/header.php';

$tours = db_all('SELECT id, id_path, title FROM tours ORDER BY title');
$tourId = (int)($_GET['tour'] ?? ($tours[0]['id'] ?? 0));
$currentTour = $tourId ? db_one('SELECT id, id_path, title FROM tours WHERE id = ?', [$tourId]) : null;
$scenes = $tourId ? db_all('SELECT * FROM scenes WHERE tour_id = ? ORDER BY sort, id', [$tourId]) : [];
$msg = $_GET['msg'] ?? '';
?>
<h1 class="h3 fw-bold mb-1">Quản lý Scene</h1>
<?php if ($currentTour): ?>
<p class="section-sub mb-3">Tour #<?= (int)$currentTour['id'] ?> · <code><?= htmlspecialchars($currentTour['id_path']) ?></code> · <?= htmlspecialchars($currentTour['title']) ?> · <?= count($scenes) ?> scene</p>
<?php endif; ?>
<?php if ($msg): ?><div class="alert alert-success"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <form method="get" style="margin:0;">
        <label>Chọn tour:
            <select class="form-select" name="tour" onchange="this.form.submit()">
                <?php foreach ($tours as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $t['id'] == $tourId ? 'selected' : '' ?>>
                        #<?= $t['id'] ?> — <?= htmlspecialchars($t['title']) ?> (<?= htmlspecialchars($t['id_path']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <p><a href="edit.php?tour=<?= $tourId ?>">➕ Thêm scene</a></p>
    <table class="table table-hover align-middle">
        <thead>
        <tr><th>#</th><th>Name</th><th>Tiêu đề</th><th>fov</th><th>Hotspot</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($scenes as $s):
            $hc = (int)(db_one('SELECT COUNT(*) c FROM hotspots WHERE scene_id = ?', [$s['id']])['c'] ?? 0); ?>
        <tr>
            <td><?= $s['sort'] ?></td>
            <td><code><?= htmlspecialchars($s['name']) ?></code></td>
            <td><?= htmlspecialchars($s['title']) ?></td>
            <td><?= (float)$s['fov'] ?></td>
            <td><a href="../hotspot/list.php?scene=<?= $s['id'] ?>"><?= $hc ?> hotspot</a></td>
            <td style="white-space:nowrap;">
                <a href="edit.php?id=<?= $s['id'] ?>">Sửa</a> ·
                <a href="../pano/upload.php?scene=<?= $s['id'] ?>">Pano</a> ·
                <a href="../gallery/list.php?scene=<?= $s['id'] ?>">Ảnh</a> ·
                <a href="../audio/list.php?scene=<?= $s['id'] ?>">Audio</a> ·
                <a href="../video/list.php?scene=<?= $s['id'] ?>">Video</a> ·
                <a href="../iframe/list.php?scene=<?= $s['id'] ?>">Iframe</a> ·
                <a href="../polygon/list.php?scene=<?= $s['id'] ?>">Polygon</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$scenes): ?><tr><td colspan="6">Chưa có scene.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
