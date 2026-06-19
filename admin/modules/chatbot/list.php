<?php
/** Quản lý chatbot: lời chào + cặp hỏi-đáp. ?tour=<id> */
require_once dirname(__DIR__, 3) . '/core/db.php';
require_once dirname(__DIR__, 3) . '/core/auth.php';
$pageTitle = 'Chatbot';
require dirname(__DIR__, 2) . '/includes/header.php';

$tours = db_all('SELECT id, id_path, title FROM tours ORDER BY title');
$tourId = (int)($_GET['tour'] ?? ($tours[0]['id'] ?? 0));

$cfg = [];
foreach (db_all('SELECT `key`, `value` FROM settings WHERE tour_id = ?', [$tourId]) as $r) {
    $cfg[$r['key']] = $r['value'];
}
$qas = db_all('SELECT * FROM chatbot_qa WHERE tour_id = ? ORDER BY sort, id', [$tourId]);
?>
<?php $cbOn = (($cfg['chatbot_enabled'] ?? 'false') === 'true'); ?>
<h1 class="h3 fw-bold mb-1"><i class="bi bi-robot"></i> Trợ lý hỏi đáp (Chatbot)</h1>
<div class="ctx-bar">
    <form method="get" class="d-flex align-items-center gap-2 m-0">
        <span class="text-secondary small">Tour:</span>
        <select name="tour" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <?php foreach ($tours as $t): ?><option value="<?= $t['id'] ?>" <?= $t['id'] == $tourId ? 'selected' : '' ?>><?= htmlspecialchars($t['title']) ?></option><?php endforeach; ?>
        </select>
    </form>
    <span class="ctx-chip <?= $cbOn ? 'ok' : 'muted' ?>"><i class="bi bi-<?= $cbOn ? 'check-circle' : 'slash-circle' ?> me-1"></i><?= $cbOn ? 'Đang bật' : 'Đang tắt' ?></span>
    <span class="ctx-chip muted"><?= count($qas) ?> câu hỏi</span>
</div>

<div class="row g-3 mb-3">
    <!-- Cài đặt chung -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="section-title mb"><i class="bi bi-gear"></i> Cài đặt chung</div>
            <form method="post" action="/admin/api/save_chatbot.php">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="tour_id" value="<?= $tourId ?>">
                <input type="hidden" name="mode" value="config">
                <div class="set-row">
                    <div class="set-info">
                        <div class="set-name">Bật chatbot</div>
                        <div class="set-desc">Hiện bong bóng trợ lý hỏi đáp trên trình xem tour.</div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="chatbot_enabled" value="true" <?= $cbOn ? 'checked' : '' ?>>
                    </div>
                </div>
                <div class="mt-2">
                    <label class="form-label">Lời chào</label>
                    <input name="chatbot_greeting" class="form-control" value="<?= htmlspecialchars((string)($cfg['chatbot_greeting'] ?? 'Xin chào! Tôi có thể giúp gì cho bạn?')) ?>">
                    <div class="field-help"><i class="bi bi-chat-dots"></i> Câu mở đầu hiển thị khi khách mở khung chat.</div>
                </div>
                <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-check-lg me-1"></i>Lưu cài đặt</button>
            </form>
        </div></div>
    </div>

    <!-- Thêm Q&A -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="section-title mb"><i class="bi bi-plus-circle"></i> Thêm câu hỏi – trả lời</div>
            <form method="post" action="/admin/api/save_chatbot.php">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="tour_id" value="<?= $tourId ?>">
                <input type="hidden" name="mode" value="qa">
                <div class="mb-2">
                    <label class="form-label">Câu hỏi mẫu</label>
                    <input name="question" class="form-control" placeholder="VD: Giờ mở cửa?" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Câu trả lời</label>
                    <textarea name="answer" class="form-control" placeholder="Nội dung trả lời" required style="min-height:70px;"></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label">Từ khoá</label>
                    <input name="keywords" class="form-control" placeholder="giờ mở cửa, vé, giá">
                    <div class="field-help"><i class="bi bi-tags"></i> Phân tách bằng dấu phẩy — bot dùng để khớp câu hỏi của khách.</div>
                </div>
                <button type="submit" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Thêm</button>
            </form>
        </div></div>
    </div>
</div>

<!-- Danh sách Q&A -->
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="section-title mb"><i class="bi bi-list-ul"></i> Danh sách câu hỏi</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Câu hỏi</th><th>Trả lời</th><th>Từ khoá</th><th class="text-end"></th></tr></thead>
            <tbody>
            <?php foreach ($qas as $q): ?>
            <tr>
                <td class="fw-semibold"><?= htmlspecialchars($q['question']) ?></td>
                <td style="max-width:280px;" class="text-secondary"><?= htmlspecialchars(mb_strimwidth((string)$q['answer'], 0, 80, '…')) ?></td>
                <td><?php foreach (array_filter(array_map('trim', explode(',', (string)$q['keywords']))) as $kw): ?><span class="badge text-bg-light border me-1"><?= htmlspecialchars($kw) ?></span><?php endforeach; ?></td>
                <td class="text-end">
                    <form method="post" action="/admin/api/delete_chatbot_qa.php" onsubmit="return confirm('Xoá?')" class="d-inline">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="id" value="<?= $q['id'] ?>">
                        <button type="submit" class="icon-del" title="Xoá"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$qas): ?><tr><td colspan="4"><div class="empty">Chưa có câu hỏi nào.</div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div></div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
