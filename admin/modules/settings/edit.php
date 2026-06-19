<?php
/** Cấu hình tour (gồm autotour). ?tour=<id> */
require_once dirname(__DIR__, 3) . '/core/db.php';
require_once dirname(__DIR__, 3) . '/core/auth.php';
$pageTitle = 'Cấu hình tour';
require dirname(__DIR__, 2) . '/includes/header.php';

$tours = db_all('SELECT id, id_path, title FROM tours ORDER BY title');
$tourId = (int)($_GET['tour'] ?? ($tours[0]['id'] ?? 0));

$cfg = [];
foreach (db_all('SELECT `key`, `value` FROM settings WHERE tour_id = ?', [$tourId]) as $r) {
    $cfg[$r['key']] = $r['value'];
}
$val = fn($k, $d = '') => htmlspecialchars((string)($cfg[$k] ?? $d));
$chk = fn($k, $d = 'false') => (($cfg[$k] ?? $d) === 'true') ? 'checked' : '';
?>
<style>
  .set-wrap{ max-width:760px; }
  .section-title{ font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.05em;
    color:#8a94a6; margin:0 0 6px; display:flex; align-items:center; gap:8px; }
  .section-title .bi{ color:#2d6cdf; font-size:15px; }
  .section-sub{ font-size:12.5px; color:#9aa3b2; margin:0 0 16px; }
  .ctx-bar{ display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:18px; }
  /* Hàng cấu hình bật/tắt */
  .set-row{ display:flex; align-items:center; gap:16px; padding:14px 0; border-top:1px solid #eef1f6; }
  .set-row:first-of-type{ border-top:0; }
  .set-info{ flex:1; min-width:0; }
  .set-name{ font-size:14px; font-weight:600; color:#2b3445; }
  .set-desc{ font-size:12.5px; color:#8a94a6; margin-top:2px; line-height:1.5; }
  .set-row .form-switch{ flex-shrink:0; padding-left:2.6em; }
  .set-row .form-switch .form-check-input{ width:2.4em; height:1.3em; cursor:pointer; }
  /* Hàng cấu hình nhập số */
  .num-row{ display:flex; align-items:center; gap:16px; padding:14px 0; border-top:1px solid #eef1f6; }
  .num-row .set-info{ flex:1; }
  .num-row input{ width:110px; flex-shrink:0; text-align:center; }
  .save-bar{ position:sticky; bottom:0; background:#fff; border-top:1px solid #e6eaf0; padding:14px 0; margin-top:6px; }
</style>

<div class="set-wrap">
<h1 class="h3 fw-bold mb-1"><i class="bi bi-sliders"></i> Cấu hình tour</h1>
<div class="ctx-bar">
    <form method="get" class="d-flex align-items-center gap-2 m-0">
        <span class="text-secondary small">Tour:</span>
        <select name="tour" onchange="this.form.submit()" class="form-select form-select-sm" style="width:auto;">
            <?php foreach ($tours as $t): ?><option value="<?= $t['id'] ?>" <?= $t['id'] == $tourId ? 'selected' : '' ?>><?= htmlspecialchars($t['title']) ?></option><?php endforeach; ?>
        </select>
    </form>
</div>

<form method="post" action="/admin/api/save_settings.php">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="tour_id" value="<?= $tourId ?>">

    <!-- Điều khiển -->
    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <div class="section-title"><i class="bi bi-controller"></i> Điều khiển &amp; trải nghiệm</div>
        <p class="section-sub">Các tuỳ chọn ảnh hưởng tới cách người xem tương tác với tour.</p>

        <div class="set-row">
            <div class="set-info">
                <div class="set-name">Con quay hồi chuyển (Gyro)</div>
                <div class="set-desc">Trên điện thoại/tablet, cho phép xoay góc nhìn bằng cách nghiêng thiết bị theo cảm biến chuyển động.</div>
            </div>
            <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" role="switch" name="gyro" value="true" <?= $chk('gyro', 'true') ?>>
            </div>
        </div>

        <div class="set-row">
            <div class="set-info">
                <div class="set-name">Chế độ thực tế ảo (WebVR)</div>
                <div class="set-desc">Hiện nút kính VR để xem bằng kính Cardboard/headset ở chế độ phân đôi màn hình (stereo 3D).</div>
            </div>
            <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" role="switch" name="webvr" value="true" <?= $chk('webvr', 'true') ?>>
            </div>
        </div>

        <div class="set-row">
            <div class="set-info">
                <div class="set-name">Hiệu ứng “Hành tinh nhỏ” khi vào</div>
                <div class="set-desc">Mở đầu mỗi cảnh bằng hiệu ứng “little planet” (thu toàn cảnh thành quả cầu) rồi bung ra góc nhìn bình thường.</div>
            </div>
            <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" role="switch" name="littleplanetintro" value="true" <?= $chk('littleplanetintro') ?>>
            </div>
        </div>
    </div></div>

    <!-- Autotour -->
    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <div class="section-title"><i class="bi bi-arrow-repeat"></i> Autotour — tự động tham quan</div>
        <p class="section-sub">Tự động lần lượt chuyển cảnh và xoay panorama khi người dùng không thao tác.</p>

        <div class="set-row">
            <div class="set-info">
                <div class="set-name">Bật autotour</div>
                <div class="set-desc">Khi bật, tour sẽ tự xoay và chuyển sang scene tiếp theo. Người xem chạm/kéo sẽ tạm dừng.</div>
            </div>
            <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" role="switch" name="autotour" value="true" <?= $chk('autotour') ?>>
            </div>
        </div>

        <div class="num-row">
            <div class="set-info">
                <div class="set-name">Thời gian dừng mỗi scene</div>
                <div class="set-desc">Số giây lưu lại ở mỗi cảnh trước khi tự chuyển sang cảnh kế tiếp.</div>
            </div>
            <div class="input-group" style="width:auto;">
                <input name="autotour_dwell" type="number" step="0.5" min="1" value="<?= $val('autotour_dwell', '8') ?>" class="form-control">
                <span class="input-group-text">giây</span>
            </div>
        </div>

        <div class="num-row">
            <div class="set-info">
                <div class="set-name">Tốc độ xoay</div>
                <div class="set-desc">Tốc độ panorama tự xoay. Giá trị âm sẽ xoay theo chiều ngược lại.</div>
            </div>
            <div class="input-group" style="width:auto;">
                <input name="autotour_rotate_speed" type="number" step="0.5" value="<?= $val('autotour_rotate_speed', '4') ?>" class="form-control">
                <span class="input-group-text">°/giây</span>
            </div>
        </div>
    </div></div>

    <div class="save-bar">
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Lưu cấu hình</button>
    </div>
</form>
</div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
