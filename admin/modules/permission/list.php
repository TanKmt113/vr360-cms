<?php
/** Phân quyền scene + mật khẩu truy cập tour. ?tour=<id> */
require_once dirname(__DIR__, 3) . '/core/db.php';
require_once dirname(__DIR__, 3) . '/core/auth.php';
$pageTitle = 'Phân quyền';
require dirname(__DIR__, 2) . '/includes/header.php';

$tours = db_all('SELECT id, id_path, title, access_password_hash FROM tours ORDER BY title');
$tourId = (int)($_GET['tour'] ?? ($tours[0]['id'] ?? 0));
$tour = null;
foreach ($tours as $t) {
    if ($t['id'] == $tourId) { $tour = $t; }
}
$scenes = $tourId ? db_all('SELECT * FROM scenes WHERE tour_id = ? ORDER BY sort', [$tourId]) : [];
// Map scene_id => rule
$locked = [];
foreach (db_all('SELECT scene_id, rule FROM scene_permission WHERE tour_id = ?', [$tourId]) as $r) {
    $locked[(int)$r['scene_id']] = $r['rule'];
}
?>
<?php
$hasPwd = $tour && $tour['access_password_hash'];
$lockedCount = count($locked);
?>
<h1 class="h3 fw-bold mb-1"><i class="bi bi-shield-lock"></i> Phân quyền &amp; Đăng nhập</h1>
<div class="ctx-bar">
    <form method="get" class="d-flex align-items-center gap-2 m-0">
        <span class="text-secondary small">Tour:</span>
        <select name="tour" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <?php foreach ($tours as $t): ?><option value="<?= $t['id'] ?>" <?= $t['id'] == $tourId ? 'selected' : '' ?>><?= htmlspecialchars($t['title']) ?></option><?php endforeach; ?>
        </select>
    </form>
    <span class="ctx-chip <?= $hasPwd ? 'ok' : 'warn' ?>"><i class="bi bi-key me-1"></i><?= $hasPwd ? 'Đã đặt mật khẩu' : 'Chưa đặt mật khẩu' ?></span>
    <span class="ctx-chip muted"><i class="bi bi-lock me-1"></i><?= $lockedCount ?> scene bị khoá</span>
</div>

<!-- Mật khẩu truy cập -->
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="section-title"><i class="bi bi-key"></i> Mật khẩu truy cập tour</div>
    <p class="section-sub">Khách phải nhập mật khẩu này để mở các scene bị khoá bên dưới.</p>
    <form method="post" action="/admin/api/save_permission.php">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="tour_id" value="<?= $tourId ?>">
        <input type="hidden" name="mode" value="password">
        <div class="d-flex gap-2 align-items-center flex-wrap" style="max-width:520px;">
            <input name="password" type="text" class="form-control" placeholder="Mật khẩu mới (để trống = xoá)">
            <button type="submit" class="btn btn-primary text-nowrap"><i class="bi bi-check-lg me-1"></i>Lưu</button>
        </div>
        <div class="field-help"><i class="bi bi-info-circle"></i> Để trống và bấm Lưu để gỡ bỏ mật khẩu hiện tại.</div>
    </form>
</div></div>

<!-- Khoá scene -->
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="section-title"><i class="bi bi-lock"></i> Khoá scene</div>
    <p class="section-sub">Bật công tắc để yêu cầu khách nhập mật khẩu trước khi xem scene.</p>
    <form method="post" action="/admin/api/save_permission.php">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="tour_id" value="<?= $tourId ?>">
        <input type="hidden" name="mode" value="scenes">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Scene</th><th class="text-end" style="width:160px;">Yêu cầu đăng nhập</th></tr></thead>
                <tbody>
                <?php foreach ($scenes as $s): $isLocked = isset($locked[(int)$s['id']]); ?>
                <tr>
                    <td class="fw-semibold"><?= $isLocked ? '<i class="bi bi-lock-fill text-warning me-1"></i>' : '<i class="bi bi-unlock text-secondary me-1"></i>' ?><?= htmlspecialchars($s['title']) ?></td>
                    <td class="text-end">
                        <div class="form-check form-switch d-inline-block m-0">
                            <input type="checkbox" role="switch" class="form-check-input" name="lock[]" value="<?= $s['id'] ?>" <?= $isLocked ? 'checked' : '' ?>>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$scenes): ?><tr><td colspan="2"><div class="empty">Chưa có scene.</div></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <button type="submit" class="btn btn-success mt-3"><i class="bi bi-check-lg me-1"></i>Lưu khoá scene</button>
    </form>
</div></div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
