<?php
/** Quản lý minimap cấp tour: bản đồ + điểm scene. ?tour=<id> */
require_once dirname(__DIR__, 3) . '/core/db.php';
require_once dirname(__DIR__, 3) . '/core/auth.php';
$pageTitle = 'Minimap';
require dirname(__DIR__, 2) . '/includes/header.php';

$tours = db_all('SELECT id, id_path, title FROM tours ORDER BY title');
$tourId = (int)($_GET['tour'] ?? ($tours[0]['id'] ?? 0));
$map = $tourId ? db_one('SELECT * FROM minimaps WHERE tour_id = ? LIMIT 1', [$tourId]) : null;
$scenes = $tourId ? db_all('SELECT name, title FROM scenes WHERE tour_id = ? ORDER BY sort', [$tourId]) : [];
$spots = $map ? (json_decode((string)$map['spots_json'], true) ?: []) : [];
?>
<style>
  .mm-wrap{ max-width:1120px; }
  .section-title{ font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.05em;
    color:#8a94a6; margin:0 0 16px; display:flex; align-items:center; gap:8px; }
  .section-title .bi{ color:#2d6cdf; font-size:15px; }
  .ctx-bar{ display:flex; flex-wrap:wrap; gap:8px; margin-bottom:18px; align-items:center; }
  .ctx-chip{ background:#eef3fc; color:#2d6cdf; border-radius:20px; padding:5px 13px; font-size:13px; font-weight:600; }
  .ctx-chip.muted{ background:#eef1f6; color:#52607a; }
  .field-help{ font-size:12px; color:#9aa3b2; margin-top:8px; }
  /* Bản đồ + điểm */
  .map-stage{ position:relative; display:inline-block; line-height:0; border-radius:12px; overflow:hidden;
    border:1px solid #e6eaf0; box-shadow:0 4px 14px rgba(20,30,50,.08); }
  .map-stage img{ max-width:100%; display:block; cursor:crosshair; }
  #spotLayer{ position:absolute; inset:0; }
  .spot{ position:absolute; transform:translate(-50%,-50%); width:24px; height:24px; border-radius:50%;
    background:#e74c3c; border:2px solid #fff; box-shadow:0 2px 6px rgba(0,0,0,.45); color:#fff;
    font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:.12s; }
  .spot:hover{ transform:translate(-50%,-50%) scale(1.3); background:#c0392b; z-index:5; }
  .spot .lbl{ position:absolute; bottom:135%; left:50%; transform:translateX(-50%); background:#1f2a3a; color:#fff;
    font-size:11px; font-weight:500; padding:2px 8px; border-radius:6px; white-space:nowrap; opacity:0; pointer-events:none; transition:.12s; }
  .spot:hover .lbl{ opacity:1; }
  /* Danh sách điểm */
  .pt-head{ display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
  .count-badge{ background:#2d6cdf; color:#fff; font-size:12px; font-weight:700; border-radius:20px; padding:2px 10px; min-width:24px; text-align:center; }
  .pt-item{ display:flex; align-items:center; gap:10px; padding:8px 10px; border:1px solid #e6eaf0;
    border-radius:9px; margin-bottom:7px; font-size:13px; background:#fff; transition:.12s; }
  .pt-item:hover{ border-color:#bcd0f5; background:#f7faff; }
  .pt-item .idx{ width:22px; height:22px; border-radius:50%; background:#e74c3c; color:#fff; font-size:11px;
    font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .pt-item .nm{ flex:1; min-width:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#2b3445; }
  .pt-item .del{ border:0; background:transparent; color:#aab; cursor:pointer; border-radius:6px; width:24px; height:24px; }
  .pt-item .del:hover{ background:#e74c3c; color:#fff; }
  .empty{ font-size:13px; color:#8a94a6; text-align:center; padding:24px 10px; border:1px dashed #d4dae4; border-radius:10px; }
  .save-bar{ position:sticky; bottom:0; background:#fff; border-top:1px solid #e6eaf0; padding:14px 0; margin-top:6px; }
</style>

<div class="mm-wrap">
<h1 class="h3 fw-bold mb-1"><i class="bi bi-map"></i> Minimap</h1>
<div class="ctx-bar">
    <form method="get" class="d-flex align-items-center gap-2 m-0">
        <span class="text-secondary small">Tour:</span>
        <select name="tour" onchange="this.form.submit()" class="form-select form-select-sm" style="width:auto;">
            <?php foreach ($tours as $t): ?>
                <option value="<?= $t['id'] ?>" <?= $t['id'] == $tourId ? 'selected' : '' ?>><?= htmlspecialchars($t['title']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php if ($map && $map['image_url']): ?>
        <span class="ctx-chip"><i class="bi bi-check-circle me-1"></i>Đã có bản đồ · <?= count($spots) ?> điểm</span>
    <?php else: ?>
        <span class="ctx-chip muted"><i class="bi bi-exclamation-circle me-1"></i>Chưa có bản đồ</span>
    <?php endif; ?>
</div>

<!-- Upload bản đồ -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="section-title"><i class="bi bi-image"></i> Ảnh bản đồ</div>
        <form method="post" action="/admin/api/save_minimap.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="tour_id" value="<?= $tourId ?>">
            <input type="hidden" name="mode" value="map">
            <div class="d-flex gap-2 align-items-center flex-wrap" style="max-width:560px;">
                <input type="file" name="map" accept="image/*" class="form-control" required>
                <button type="submit" class="btn btn-success text-nowrap"><i class="bi bi-upload me-1"></i>Upload</button>
            </div>
            <div class="field-help"><i class="bi bi-info-circle"></i> Tải lên ảnh sơ đồ mặt bằng (PNG/JPG). Upload ảnh mới sẽ thay ảnh cũ.</div>
        </form>
    </div>
</div>

<?php if ($map && $map['image_url']): ?>
<div class="row g-3 mb-3">
    <!-- Bản đồ + đặt điểm -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="section-title"><i class="bi bi-geo-alt"></i> Đặt điểm trên bản đồ</div>
            <div class="d-flex gap-2 align-items-center flex-wrap mb-3">
                <span class="text-secondary small">Scene:</span>
                <select id="sceneSel" class="form-select form-select-sm" style="width:auto;min-width:180px;">
                    <?php foreach ($scenes as $s): ?><option value="<?= htmlspecialchars($s['name']) ?>"><?= htmlspecialchars($s['title']) ?></option><?php endforeach; ?>
                </select>
                <span class="text-secondary small ms-1" id="status"></span>
            </div>
            <div class="map-stage">
                <img id="mapImg" src="<?= htmlspecialchars($map['image_url']) ?>" alt="Bản đồ">
                <div id="spotLayer"></div>
            </div>
            <div class="field-help"><i class="bi bi-cursor"></i> Chọn scene rồi click lên bản đồ để thêm điểm. Click vào điểm (hoặc nút ✕ bên phải) để xoá.</div>
        </div></div>
    </div>
    <!-- Danh sách điểm -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body d-flex flex-column">
            <div class="pt-head">
                <div class="section-title mb-0"><i class="bi bi-list-ol"></i> Điểm đã đặt</div>
                <span class="count-badge" id="count">0</span>
            </div>
            <div id="spotList" class="flex-fill"></div>
            <div class="save-bar">
                <button class="btnSave btn btn-primary w-100"><i class="bi bi-save me-1"></i>Lưu các điểm</button>
            </div>
        </div></div>
    </div>
</div>

<script>
let spots = <?= json_encode($spots ?: [], JSON_UNESCAPED_UNICODE) ?>;
const layer = document.getElementById('spotLayer');
const img = document.getElementById('mapImg');
const titleOf = {}; <?php foreach ($scenes as $s): ?>titleOf[<?= json_encode($s['name']) ?>]=<?= json_encode($s['title']) ?>;<?php endforeach; ?>
function esc(s){return String(s==null?'':s).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));}
function nameOf(sp){return titleOf[sp.scene]||sp.scene;}

function render(){
    layer.innerHTML = '';
    spots.forEach((sp, i) => {
        const d = document.createElement('div');
        d.className = 'spot';
        d.style.left = sp.x + '%'; d.style.top = sp.y + '%';
        d.innerHTML = (i+1) + '<span class="lbl">' + esc(nameOf(sp)) + '</span>';
        d.onclick = (e)=>{ e.stopPropagation(); spots.splice(i,1); render(); };
        layer.appendChild(d);
    });
    const list = document.getElementById('spotList');
    list.innerHTML = spots.map((sp,i)=>
        `<div class="pt-item"><span class="idx">${i+1}</span><span class="nm">${esc(nameOf(sp))}</span><button class="del" title="Xoá điểm" onclick="delSpot(${i})">✕</button></div>`
    ).join('') || '<div class="empty">Chưa có điểm nào.<br>Chọn scene và click lên bản đồ.</div>';
    document.getElementById('count').textContent = spots.length;
    document.getElementById('status').textContent = spots.length + ' điểm';
}
window.delSpot = function(i){ spots.splice(i,1); render(); };
img.addEventListener('click', (e)=>{
    const r = img.getBoundingClientRect();
    const x = ((e.clientX - r.left)/r.width*100).toFixed(2);
    const y = ((e.clientY - r.top)/r.height*100).toFixed(2);
    spots.push({scene: document.getElementById('sceneSel').value, x:+x, y:+y});
    render();
});
document.querySelector('.btnSave').onclick = ()=>{
    const f = document.createElement('form'); f.method='POST'; f.action='/admin/api/save_minimap.php';
    const add=(n,v)=>{const i=document.createElement('input');i.name=n;i.value=v;f.appendChild(i);};
    add('tour_id', <?= $tourId ?>); add('mode','spots');
    add('spots_json', JSON.stringify(spots)); add('csrf', <?= json_encode(csrf_token()) ?>);
    document.body.appendChild(f); f.submit();
};
render();
</script>
<?php else: ?>
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="empty"><i class="bi bi-map" style="font-size:22px;"></i><br>Hãy upload bản đồ trước để bắt đầu đặt điểm.</div>
</div></div>
<?php endif; ?>
</div>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
