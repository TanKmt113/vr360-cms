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
?>
<h1 class="h3 fw-bold mb-4">Tổng quan</h1>

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
                    <tr><th>id_path</th><th>Tiêu đề</th><th>Ngôn ngữ mặc định</th><th>Số scene</th><th>Trạng thái</th><th>Xem</th></tr>
                </thead>
                <tbody>
                <?php foreach ($tours as $t): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($t['id_path']) ?></code></td>
                        <td><?= htmlspecialchars($t['title']) ?></td>
                        <td><?= htmlspecialchars($t['default_lang']) ?></td>
                        <td><?= (int)$t['scene_count'] ?></td>
                        <td><span class="badge text-bg-secondary"><?= htmlspecialchars($t['status']) ?></span></td>
                        <td><a href="/public/viewer.php?id=<?= urlencode($t['id_path']) ?>" target="_blank">▶ Mở</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$tours): ?><tr><td colspan="6" class="text-secondary">Chưa có tour. Hãy chạy seed.sql.</td></tr><?php endif; ?>
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

<?php require __DIR__ . '/includes/footer.php'; ?>
