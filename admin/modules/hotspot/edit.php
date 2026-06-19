<?php
/** Form thêm/sửa hotspot. ?id=<hotspot> | ?scene=<scene> */
require_once dirname(__DIR__, 3) . '/core/db.php';
require_once dirname(__DIR__, 3) . '/core/auth.php';
$pageTitle = 'Sửa Hotspot';
require dirname(__DIR__, 2) . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$h  = $id ? db_one('SELECT * FROM hotspots WHERE id = ?', [$id]) : null;
$sceneId = (int)($h['scene_id'] ?? ($_GET['scene'] ?? 0));
$scene = db_one('SELECT * FROM scenes WHERE id = ?', [$sceneId]);
if (!$scene) { echo '<p>Thiếu scene.</p>'; require dirname(__DIR__, 2) . '/includes/footer.php'; exit; }

// Danh sách scene khác cùng tour để chọn link
$otherScenes = db_all('SELECT name, title FROM scenes WHERE tour_id = ? ORDER BY sort', [(int)$scene['tour_id']]);

// Ngôn ngữ của tour + bản dịch hiện có của hotspot
$langs = db_all('SELECT code, display_name FROM languages WHERE tour_id = ? ORDER BY sort', [(int)$scene['tour_id']]);
$i18n = [];
if ($id) {
    foreach (db_all('SELECT lang_code, title, content FROM hotspot_i18n WHERE hotspot_id = ?', [$id]) as $r) {
        $i18n[$r['lang_code']] = $r;
    }
}

$f = $h ?: ['uuid' => '', 'type' => 'nav', 'style' => 'default', 'style_hover' => 'callout',
            'ath' => 0, 'atv' => 0, 'link_scene' => '', 'tooltip' => '', 'sort' => 0];
// Toạ độ điền sẵn từ trang đặt trực quan (place.php)
if (!$h && isset($_GET['ath'])) {
    $f['ath'] = (float)$_GET['ath'];
    $f['atv'] = (float)($_GET['atv'] ?? 0);
}
// Style nút — khớp ảnh /public/theme/hotspots/<style>.png
$styles = ['mui_ten' => 'Mũi tên', 'vongtron' => 'Vòng tròn', 'giotnuoc' => 'Giọt nước', 'info' => 'Thông tin', 'default' => 'Mặc định'];
$curStyle = (string)$f['style'];
if ($curStyle !== '' && !isset($styles[$curStyle])) { $styles[$curStyle] = $curStyle; }   // giữ giá trị cũ lạ
$types = ['nav' => 'Điều hướng (nav)', 'info' => 'Thông tin (info)', 'media' => 'Media'];
$curType = (string)$f['type'];
if ($curType !== '' && !isset($types[$curType])) { $types[$curType] = $curType; }
$iconBase = '/public/theme/hotspots/';
?>
<style>
  .hs-form{ max-width:760px; }
  .section-title{ font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.05em;
    color:#8a94a6; margin:0 0 16px; display:flex; align-items:center; gap:8px; }
  .section-title .bi{ color:#2d6cdf; font-size:15px; }
  .field-help{ font-size:12px; color:#9aa3b2; margin-top:4px; }
  .ctx-bar{ display:flex; flex-wrap:wrap; gap:8px; margin-bottom:18px; }
  .ctx-chip{ background:#eef3fc; color:#2d6cdf; border-radius:20px; padding:5px 13px; font-size:13px; font-weight:600; }
  .ctx-chip.muted{ background:#eef1f6; color:#52607a; }
  /* Style picker trực quan */
  .style-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(104px,1fr)); gap:12px; }
  .style-opt input{ position:absolute; opacity:0; pointer-events:none; }
  .style-card{ display:flex; flex-direction:column; align-items:center; gap:8px; padding:14px 8px;
    border:1.5px solid #e6eaf0; border-radius:12px; cursor:pointer; transition:.15s; background:#fff; height:100%; }
  .style-card:hover{ border-color:#bcd0f5; }
  .style-card .thumb{ width:52px; height:52px; border-radius:11px; background:#2b3445;
    display:flex; align-items:center; justify-content:center; }
  .style-card .thumb img{ width:32px; height:32px; object-fit:contain; }
  .style-card span{ font-size:12.5px; color:#52607a; font-weight:500; text-align:center; }
  .style-opt input:checked + .style-card{ border-color:#2d6cdf; background:#f3f8ff;
    box-shadow:0 0 0 3px rgba(45,108,223,.14); }
  .style-opt input:checked + .style-card span{ color:#2d6cdf; font-weight:700; }
  /* Khối ngôn ngữ */
  .lang-block{ border:1px solid #e6eaf0; border-radius:10px; padding:14px; margin-bottom:12px; background:#fbfcfe; }
  .lang-head{ display:flex; align-items:center; gap:8px; font-size:13px; font-weight:600; color:#2b3445; margin-bottom:10px; }
  .lang-head .code{ font-size:11px; font-weight:700; color:#2d6cdf; background:#eef3fc; border-radius:5px; padding:1px 7px; }
  /* Thanh lưu dính đáy */
  .save-bar{ position:sticky; bottom:0; background:#fff; border-top:1px solid #e6eaf0;
    padding:14px 0; margin-top:6px; display:flex; gap:10px; align-items:center; }
</style>

<h1 class="h3 fw-bold mb-1"><?= $id ? '✏️ Sửa' : '➕ Thêm' ?> Hotspot</h1>
<div class="ctx-bar">
    <span class="ctx-chip"><i class="bi bi-camera-reels me-1"></i><?= htmlspecialchars($scene['title']) ?></span>
    <?php if ($id): ?><span class="ctx-chip muted">#<?= $id ?></span><?php endif; ?>
    <span class="ctx-chip muted"><?= htmlspecialchars($f['uuid'] ?: 'hotspot mới') ?></span>
</div>

<form method="post" action="/admin/api/save_hotspot.php" class="hs-form">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="scene_id" value="<?= $sceneId ?>">
    <input type="hidden" name="tour_id" value="<?= (int)$scene['tour_id'] ?>">

    <!-- Thông tin cơ bản -->
    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <div class="section-title"><i class="bi bi-info-circle"></i> Thông tin cơ bản</div>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">UUID <span class="text-secondary fw-normal">— mã định danh, không trùng</span></label>
                <input class="form-control" name="uuid" type="text" value="<?= htmlspecialchars((string)$f['uuid']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Thứ tự</label>
                <input class="form-control" name="sort" type="number" step="any" value="<?= htmlspecialchars((string)$f['sort']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Loại hotspot</label>
                <select class="form-select" name="type">
                    <?php foreach ($types as $tv => $tl): ?>
                        <option value="<?= htmlspecialchars($tv) ?>" <?= $tv == $f['type'] ? 'selected' : '' ?>><?= htmlspecialchars($tl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Tooltip <span class="text-secondary fw-normal">— gợi ý khi rê chuột</span></label>
                <input class="form-control" name="tooltip" type="text" value="<?= htmlspecialchars((string)$f['tooltip']) ?>">
            </div>
        </div>
    </div></div>

    <!-- Kiểu hiển thị -->
    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <div class="section-title"><i class="bi bi-palette"></i> Kiểu hiển thị (icon)</div>
        <div class="style-grid">
            <?php foreach ($styles as $sv => $sl): ?>
                <label class="style-opt">
                    <input type="radio" name="style" value="<?= htmlspecialchars($sv) ?>" <?= $sv == $curStyle ? 'checked' : '' ?>>
                    <span class="style-card">
                        <span class="thumb"><img src="<?= $iconBase . htmlspecialchars($sv) ?>.png" alt=""
                              onerror="this.src='<?= $iconBase ?>default.png'"></span>
                        <span><?= htmlspecialchars($sl) ?></span>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </div></div>

    <!-- Vị trí & điều hướng -->
    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <div class="section-title"><i class="bi bi-geo-alt"></i> Vị trí &amp; điều hướng</div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Link scene <span class="text-secondary fw-normal">— đích khi click (type=nav)</span></label>
                <select class="form-select" name="link_scene">
                    <option value="">— không —</option>
                    <?php foreach ($otherScenes as $o): ?>
                        <option value="<?= htmlspecialchars($o['name']) ?>" <?= $o['name'] == $f['link_scene'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($o['title']) ?> (<?= htmlspecialchars($o['name']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">ath <span class="text-secondary fw-normal">(ngang)</span></label>
                <input class="form-control" name="ath" type="number" step="any" value="<?= htmlspecialchars((string)$f['ath']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">atv <span class="text-secondary fw-normal">(dọc)</span></label>
                <input class="form-control" name="atv" type="number" step="any" value="<?= htmlspecialchars((string)$f['atv']) ?>">
            </div>
        </div>
        <div class="field-help"><i class="bi bi-lightbulb"></i> Mẹo: dùng trang <a href="place.php?scene=<?= $sceneId ?>">Đặt nút trực quan</a> để lấy toạ độ bằng cách click lên ảnh.</div>
    </div></div>

    <!-- Đa ngôn ngữ -->
    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <div class="section-title"><i class="bi bi-translate"></i> Nội dung đa ngôn ngữ</div>
        <?php if ($langs): ?>
            <?php foreach ($langs as $lg):
                $tt = $i18n[$lg['code']]['title'] ?? '';
                $ct = $i18n[$lg['code']]['content'] ?? ''; ?>
                <div class="lang-block">
                    <div class="lang-head"><i class="bi bi-globe2"></i> <?= htmlspecialchars($lg['display_name']) ?>
                        <span class="code"><?= htmlspecialchars($lg['code']) ?></span></div>
                    <input class="form-control mb-2"
                           name="i18n[<?= htmlspecialchars($lg['code']) ?>][title]" placeholder="Tiêu đề" value="<?= htmlspecialchars((string)$tt) ?>">
                    <textarea class="form-control" style="min-height:64px;"
                              name="i18n[<?= htmlspecialchars($lg['code']) ?>][content]" placeholder="Nội dung mô tả"><?= htmlspecialchars((string)$ct) ?></textarea>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-light border mb-0 small">Chưa có ngôn ngữ nào — thêm tại
                <a href="/admin/modules/language/list.php?tour=<?= (int)$scene['tour_id'] ?>">Quản lý ngôn ngữ</a>.</div>
        <?php endif; ?>
    </div></div>

    <div class="save-bar">
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Lưu hotspot</button>
        <a href="list.php?scene=<?= $sceneId ?>" class="btn btn-light">Hủy</a>
    </div>
</form>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
