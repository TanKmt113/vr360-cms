<?php
require_once dirname(__DIR__) . '/core/db.php';
require_once dirname(__DIR__) . '/core/auth.php';
$pageTitle = 'Tổng quan — VR360 CMS';
require __DIR__ . '/includes/header.php';

$tours  = db_all('SELECT t.*, (SELECT COUNT(*) FROM scenes s WHERE s.tour_id=t.id) AS scene_count FROM tours t ORDER BY t.created_at DESC');
$counts = [
    'tours'    => (int) (db_one('SELECT COUNT(*) c FROM tours')['c'] ?? 0),
    'scenes'   => (int) (db_one('SELECT COUNT(*) c FROM scenes')['c'] ?? 0),
    'hotspots' => (int) (db_one('SELECT COUNT(*) c FROM hotspots')['c'] ?? 0),
];
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';
?>
<h1 class="h3 fw-bold mb-4">Tổng quan</h1>
<?php if ($msg): ?><div class="alert alert-success"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="display-6 fw-bold text-dark"><?= number_format($counts['tours']) ?></div>
            <div class="text-secondary small">Tour</div>
        </div></div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="display-6 fw-bold text-dark"><?= number_format($counts['scenes']) ?></div>
            <div class="text-secondary small">Scene</div>
        </div></div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="display-6 fw-bold text-dark"><?= number_format($counts['hotspots']) ?></div>
            <div class="text-secondary small">Hotspot</div>
        </div></div>
    </div>
</div>

<div class="card border-0 shadow-sm" id="tours">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
            <h2 class="h5 mb-0">Danh sách Tour</h2>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#tourModal">
                <i class="bi bi-plus-lg"></i> Thêm tour mới
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr><th>#</th><th>id_path</th><th>Tiêu đề</th><th>Ngôn ngữ mặc định</th><th>Số scene</th><th>Trạng thái</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($tours as $t): ?>
                    <tr>
                        <td><?= (int)$t['id'] ?></td>
                        <td><code><?= htmlspecialchars($t['id_path']) ?></code></td>
                        <td><?= htmlspecialchars($t['title']) ?></td>
                        <td><?= htmlspecialchars($t['default_lang']) ?></td>
                        <td><?= (int)$t['scene_count'] ?></td>
                        <td><span class="badge text-bg-secondary"><?= htmlspecialchars($t['status']) ?></span></td>
                        <td class="text-nowrap">
                            <a href="/public/viewer.php?id=<?= urlencode($t['id_path']) ?>" target="_blank">▶ Mở</a>
                            ·
                            <a href="/admin/modules/scene/list.php?tour=<?= (int)$t['id'] ?>">Scene</a>
                            ·
                            <button type="button" class="btn btn-link btn-sm text-danger p-0 align-baseline"
                                    data-bs-toggle="modal" data-bs-target="#deleteTourModal"
                                    data-tour-id="<?= (int)$t['id'] ?>"
                                    data-tour-title="<?= htmlspecialchars($t['title'], ENT_QUOTES) ?>"
                                    data-tour-path="<?= htmlspecialchars($t['id_path'], ENT_QUOTES) ?>">
                                Xóa
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$tours): ?><tr><td colspan="7" class="text-secondary">Chưa có tour. Hãy chạy seed.sql.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Thêm tour mới -->
<div class="modal fade" id="tourModal" tabindex="-1" aria-labelledby="tourModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="tourModalTitle">➕ Thêm tour mới</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form method="post" action="/admin/api/save_tour.php">
                <div class="modal-body">
                    <p class="text-secondary small">Tạo tour rồi dùng <code>tools/import_tour.php</code> để nạp thư mục krpano vào.</p>
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <div class="mb-3">
                        <label class="form-label">id_path</label>
                        <input class="form-control" name="id_path" placeholder="vd: dinhdoclap, bao_tang_1" required pattern="[A-Za-z0-9_-]+">
                        <div class="form-text">Chỉ chữ/số/gạch — dùng trong URL ?id=</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tiêu đề tour</label>
                        <input class="form-control" name="title" placeholder="Tiêu đề tour" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ngôn ngữ mặc định</label>
                        <input class="form-control" name="default_lang" value="vn" style="max-width:120px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success">Tạo tour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Xóa tour -->
<div class="modal fade" id="deleteTourModal" tabindex="-1" aria-labelledby="deleteTourModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title h5 text-danger" id="deleteTourModalTitle">Xóa tour</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form method="post" action="/admin/api/delete_tour.php" id="deleteTourForm">
                <div class="modal-body">
                    <p class="mb-2">Bạn sắp xóa tour <strong id="deleteTourLabel"></strong>.</p>
                    <p class="small text-muted">Toàn bộ scene, hotspot, cấu hình, thống kê của tour sẽ bị xóa vĩnh viễn.</p>
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="tour_id" id="deleteTourId" value="">
                    <div class="mb-3">
                        <label class="form-label">Gõ <code id="deleteTourPathHint"></code> để xác nhận</label>
                        <input class="form-control" name="confirm_id_path" id="deleteTourConfirm" required autocomplete="off">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remove_files" value="1" id="deleteTourFiles" checked>
                        <label class="form-check-label small" for="deleteTourFiles">Xóa luôn file upload (<code>upload/panos/…</code>, ảnh, audio…)</label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xóa tour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('deleteTourModal')?.addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    if (!btn) return;
    const id = btn.getAttribute('data-tour-id') || '';
    const title = btn.getAttribute('data-tour-title') || '';
    const path = btn.getAttribute('data-tour-path') || '';
    document.getElementById('deleteTourId').value = id;
    document.getElementById('deleteTourLabel').textContent = title + ' (' + path + ')';
    document.getElementById('deleteTourPathHint').textContent = path;
    document.getElementById('deleteTourConfirm').value = '';
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
