<?php
/** Quản lý ngôn ngữ theo tour. ?tour=<id> */
require_once dirname(__DIR__, 3) . '/core/db.php';
require_once dirname(__DIR__, 3) . '/core/auth.php';
$pageTitle = 'Ngôn ngữ';
require dirname(__DIR__, 2) . '/includes/header.php';

$tours = db_all('SELECT id, id_path, title FROM tours ORDER BY title');
$tourId = (int)($_GET['tour'] ?? ($tours[0]['id'] ?? 0));
$langs = $tourId ? db_all('SELECT * FROM languages WHERE tour_id = ? ORDER BY sort, id', [$tourId]) : [];
?>
<h1 class="h3 fw-bold mb-1"><i class="bi bi-translate"></i> Ngôn ngữ</h1>
<div class="ctx-bar">
    <form method="get" class="d-flex align-items-center gap-2 m-0">
        <span class="text-secondary small">Tour:</span>
        <select name="tour" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <?php foreach ($tours as $t): ?><option value="<?= $t['id'] ?>" <?= $t['id'] == $tourId ? 'selected' : '' ?>><?= htmlspecialchars($t['title']) ?></option><?php endforeach; ?>
        </select>
    </form>
    <span class="ctx-chip muted"><?= count($langs) ?> ngôn ngữ</span>
</div>

<!-- Thêm ngôn ngữ -->
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="section-title mb"><i class="bi bi-plus-circle"></i> Thêm ngôn ngữ</div>
    <form method="post" action="/admin/api/save_language.php">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="tour_id" value="<?= $tourId ?>">
        <div class="row g-3">
            <div class="col-6 col-md-2">
                <label class="form-label">Mã</label>
                <input name="code" class="form-control" placeholder="vn, en…" required>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Tên ngắn</label>
                <input name="name" class="form-control" placeholder="VIE" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Tên hiển thị</label>
                <input name="display_name" class="form-control" placeholder="Tiếng Việt" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Thứ tự</label>
                <input name="sort" type="number" class="form-control" value="0">
            </div>
            <div class="col-12 col-md-8">
                <label class="form-label">URL ảnh cờ <span class="text-secondary fw-normal">(tùy chọn)</span></label>
                <input name="flag_img" class="form-control" placeholder="https://…/vn.png">
            </div>
        </div>
        <button type="submit" class="btn btn-success mt-3"><i class="bi bi-plus-lg me-1"></i>Thêm</button>
    </form>
</div></div>

<!-- Danh sách ngôn ngữ -->
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="section-title mb"><i class="bi bi-list-ul"></i> Danh sách ngôn ngữ</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Cờ</th><th>Mã</th><th>Tên</th><th>Hiển thị</th><th>Thứ tự</th><th class="text-end"></th></tr></thead>
            <tbody>
            <?php foreach ($langs as $l): ?>
            <tr>
                <td><?= $l['flag_img'] ? '<img src="' . htmlspecialchars($l['flag_img']) . '" style="height:20px;border-radius:3px;">' : '<span class="text-secondary">—</span>' ?></td>
                <td><span class="badge text-bg-light border"><?= htmlspecialchars($l['code']) ?></span></td>
                <td><?= htmlspecialchars($l['name']) ?></td>
                <td class="fw-semibold"><?= htmlspecialchars($l['display_name']) ?></td>
                <td><?= (int)$l['sort'] ?></td>
                <td class="text-end">
                    <form method="post" action="/admin/api/delete_language.php" onsubmit="return confirm('Xoá ngôn ngữ này?')" class="d-inline">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="id" value="<?= $l['id'] ?>">
                        <button type="submit" class="icon-del" title="Xoá"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$langs): ?><tr><td colspan="6"><div class="empty">Chưa có ngôn ngữ.</div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div></div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
