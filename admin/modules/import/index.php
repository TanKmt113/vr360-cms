<?php
/** Giao diện import tour krpano (từ đường dẫn server hoặc upload ZIP). */
require_once dirname(__DIR__, 3) . '/core/db.php';
require_once dirname(__DIR__, 3) . '/core/auth.php';
$pageTitle = 'Import tour krpano';
require dirname(__DIR__, 2) . '/includes/header.php';

$tours = db_all('SELECT id, id_path, title FROM tours ORDER BY title');
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';
?>
<style>
  .method-num{ width:26px; height:26px; border-radius:50%; background:#2d6cdf; color:#fff;
    font-size:13px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; }
  .method-num.green{ background:#1aa260; }
</style>

<h1 class="h3 fw-bold mb-1"><i class="bi bi-box-arrow-in-down"></i> Nhập tour krpano</h1>
<p class="section-sub mb-3">Nạp thư mục krpano (chứa <code>tour.xml</code> + <code>panos/</code>) vào một tour. Hệ thống tự đọc scene, view, multires và copy tiles.</p>

<?php if ($msg): ?><div class="alert alert-success"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($err) ?></div><?php endif; ?>

<?php if (!$tours): ?>
    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <div class="empty">Chưa có tour nào.<br>Hãy <a href="/admin/dashboard.php">tạo tour</a> trước khi import.</div>
    </div></div>
<?php else: ?>

<div class="row g-3 mb-3">
    <!-- Cách 1: từ thư mục server -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100"><div class="card-body d-flex flex-column">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="method-num">1</span>
                <span class="fw-semibold">Từ thư mục có sẵn trên server</span>
                <span class="badge text-bg-light border ms-auto">tour lớn</span>
            </div>
            <p class="section-sub">Khuyên dùng cho tour dung lượng lớn — không qua giới hạn upload.</p>
            <p class="small text-muted mb-2">Đường dẫn tương đối tính từ gốc CMS (<code><?= htmlspecialchars(BASE_PATH) ?></code>) hoặc đường dẫn tuyệt đối. Thư mục phải có <code>tour.xml</code> (hoặc <code>tour_xml/tour.xml</code>) + <code>panos/</code>.</p>
            <form method="post" action="/admin/api/import_tour_web.php" class="d-flex flex-column flex-fill" id="path-import-form">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="mode" value="path">
                <div class="mb-2">
                    <label class="form-label">Chọn tour</label>
                    <select class="form-select" name="tour_id">
                        <?php foreach ($tours as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?> (<?= htmlspecialchars($t['id_path']) ?>)</option><?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Đường dẫn thư mục krpano trên server</label>
                    <input class="form-control" name="src" placeholder="_research/dinh-doc-lap hoặc /home/.../public_html/tour" required>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="reset1" name="reset" value="1">
                    <label class="form-check-label small" for="reset1">Xoá scene cũ trước khi import</label>
                </div>
                <button type="submit" class="btn btn-primary mt-auto" id="path-import-btn"><i class="bi bi-folder-symlink me-1"></i>Import từ thư mục</button>
                <p class="small text-muted mt-2 mb-0 d-none" id="path-import-status"><span class="spinner-border spinner-border-sm me-1"></span>Đang đọc XML và copy tiles, có thể mất vài phút…</p>
            </form>
        </div></div>
    </div>

    <!-- Cách 2: upload ZIP -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100"><div class="card-body d-flex flex-column">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="method-num green">2</span>
                <span class="fw-semibold">Upload file ZIP</span>
                <span class="badge text-bg-light border ms-auto">≤ <?= htmlspecialchars(ini_get('upload_max_filesize')) ?></span>
            </div>
            <p class="section-sub">Nén cả thư mục krpano thành .zip rồi tải lên. Tour lớn nên dùng cách 1.</p>
            <form method="post" action="/admin/api/import_tour_web.php" enctype="multipart/form-data" class="d-flex flex-column flex-fill" id="zip-import-form">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="mode" value="zip">
                <div class="mb-2">
                    <label class="form-label">Chọn tour</label>
                    <select class="form-select" name="tour_id">
                        <?php foreach ($tours as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?> (<?= htmlspecialchars($t['id_path']) ?>)</option><?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">File .zip</label>
                    <input class="form-control" type="file" name="zip" accept=".zip" required>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="reset2" name="reset" value="1">
                    <label class="form-check-label small" for="reset2">Xoá scene cũ trước khi import</label>
                </div>
                <button type="submit" class="btn btn-success mt-auto" id="zip-import-btn"><i class="bi bi-upload me-1"></i>Upload &amp; Import</button>
                <p class="small text-muted mt-2 mb-0 d-none" id="zip-import-status"><span class="spinner-border spinner-border-sm me-1"></span>Đang upload và giải nén, có thể mất vài phút…</p>
            </form>
        </div></div>
    </div>
</div>

<script>
document.getElementById('path-import-form')?.addEventListener('submit', function () {
    const btn = document.getElementById('path-import-btn');
    const status = document.getElementById('path-import-status');
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang xử lý…'; }
    status?.classList.remove('d-none');
});
document.getElementById('zip-import-form')?.addEventListener('submit', function () {
    const btn = document.getElementById('zip-import-btn');
    const status = document.getElementById('zip-import-status');
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang xử lý…'; }
    status?.classList.remove('d-none');
});
</script>
<?php endif; ?>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
