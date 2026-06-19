<?php
/**
 * Đặt hotspot điều hướng trực quan (nhiều nút trong 1 phiên, lưu AJAX).
 * /admin/modules/hotspot/place.php?scene=<id>
 */
require_once dirname(__DIR__, 3) . '/core/db.php';
require_once dirname(__DIR__, 3) . '/core/auth.php';
auth_require();

$sceneId = (int)($_GET['scene'] ?? 0);
$scene = $sceneId ? db_one('SELECT * FROM scenes WHERE id=?', [$sceneId]) : null;
if (!$scene) { exit('Không tìm thấy scene.'); }
$tour = db_one('SELECT id_path FROM tours WHERE id=?', [(int)$scene['tour_id']]);

// Danh sách phòng đích (trừ chính nó) + hotspot hiện có
$others = db_all('SELECT name, title FROM scenes WHERE tour_id=? AND id<>? ORDER BY sort', [(int)$scene['tour_id'], $sceneId]);
$existing = db_all('SELECT id, uuid, ath, atv, link_scene, tooltip, style FROM hotspots WHERE scene_id=? ORDER BY id', [$sceneId]);

// Các style nút điều hướng (khớp ảnh /public/theme/hotspots/<style>.png và tour_xml.php)
$navStyles = [
    'mui_ten'  => 'Mũi tên',
    'vongtron' => 'Vòng tròn',
    'giotnuoc' => 'Giọt nước',
    'default'  => 'Mặc định',
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đặt hotspot — <?= htmlspecialchars($scene['title']) ?></title>
<style>
  html,body{margin:0;height:100%;font-family:Arial;background:#1a1a1a;color:#fff}
  #bar{background:#2D3E50;padding:10px 16px;display:flex;gap:12px;align-items:center;flex-wrap:wrap}
  #bar b{color:#9fe} a.back{color:#cfd8e3;text-decoration:none}
  select,input{padding:6px 8px;border-radius:5px;border:1px solid #557;background:#fff;font-size:14px}
  #wrap{display:flex;height:calc(100vh - 52px)}
  #pano{flex:1}
  #side{width:300px;background:#1e2128;overflow-y:auto;padding:16px 14px;border-left:1px solid #2c313a}
  .side-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
  .side-head h3{font-size:12px;margin:0;color:#aeb8c7;font-weight:700;letter-spacing:.05em;text-transform:uppercase}
  .count-badge{background:#2d6cdf;color:#fff;font-size:12px;font-weight:700;border-radius:20px;padding:2px 10px;min-width:24px;text-align:center}
  .hs{display:flex;align-items:center;gap:11px;background:#262b34;border:1px solid #323844;border-radius:11px;padding:10px 11px;margin-bottom:9px;transition:border-color .15s,transform .15s,background .15s}
  .hs:hover{border-color:#3d7bef;background:#2a313c;transform:translateY(-1px)}
  .hs .ico{flex-shrink:0;width:40px;height:40px;border-radius:9px;background:#11141a center/22px no-repeat;border:1px solid #2c313a}
  .hs-body{flex:1;min-width:0}
  .hs-title{font-size:14px;font-weight:600;color:#fff;display:flex;align-items:center;gap:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .hs-title .arrow{color:#3d7bef;font-weight:700}
  .hs-meta{display:flex;align-items:center;gap:8px;margin-top:5px;flex-wrap:wrap}
  .chip{font-size:11px;color:#cdd6e3;background:#333a46;border-radius:6px;padding:1px 8px}
  .coord{font-size:11px;color:#7e8a9c;font-family:ui-monospace,Menlo,Consolas,monospace}
  .hs-tip{font-size:12px;color:#8fa;margin-top:5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .hs .del{flex-shrink:0;width:26px;height:26px;border:0;border-radius:7px;background:transparent;color:#8a93a3;cursor:pointer;font-size:13px;line-height:1;opacity:.5;transition:.15s}
  .hs:hover .del{opacity:1}
  .hs .del:hover{background:#e74c3c;color:#fff}
  .empty{font-size:13px;color:#7e8a9c;text-align:center;padding:26px 10px;border:1px dashed #323844;border-radius:10px}
  .hint{font-size:12px;color:#9aa}
  .mode{background:#27ae60;color:#fff;padding:4px 10px;border-radius:4px}
</style>
</head>
<body>
<div id="bar">
  <a class="back" href="list.php?scene=<?= $sceneId ?>">← Danh sách</a>
  <span>Phòng: <b><?= htmlspecialchars($scene['title']) ?></b></span>
  <span>→ Đặt nút sang:</span>
  <select id="target">
    <?php foreach ($others as $o): ?><option value="<?= htmlspecialchars($o['name']) ?>"><?= htmlspecialchars($o['title']) ?></option><?php endforeach; ?>
  </select>
  <span>Kiểu nút:</span>
  <select id="style">
    <?php foreach ($navStyles as $k => $label): ?><option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($label) ?></option><?php endforeach; ?>
  </select>
  <input id="tip" placeholder="Tooltip (tùy chọn)" style="width:160px">
  <span class="mode">Click lên ảnh để đặt nút</span>
  <span class="hint" id="status"></span>
</div>
<div id="wrap">
  <div id="pano"></div>
  <div id="side">
    <div class="side-head">
      <h3>Nút đã đặt</h3>
      <span class="count-badge" id="count"><?= count($existing) ?></span>
    </div>
    <div id="list"></div>
  </div>
</div>

<script src="/js/tour.js?v=2"></script>
<script>
const sceneId=<?= $sceneId ?>, sceneName=<?= json_encode($scene['name']) ?>, idPath=<?= json_encode($tour['id_path'] ?? '') ?>;
const CSRF=<?= json_encode(csrf_token()) ?>;
const titleOf={}; <?php foreach ($others as $o): ?>titleOf[<?= json_encode($o['name']) ?>]=<?= json_encode($o['title']) ?>;<?php endforeach; ?>
let hotspots=<?= json_encode(array_map(fn($h)=>['id'=>(int)$h['id'],'ath'=>(float)$h['ath'],'atv'=>(float)$h['atv'],'link'=>$h['link_scene'],'tip'=>$h['tooltip'],'style'=>$h['style']], $existing), JSON_UNESCAPED_UNICODE) ?>;
const styleLabel=<?= json_encode($navStyles, JSON_UNESCAPED_UNICODE) ?>;
const STYLES=Object.keys(styleLabel);
let kr=null;

function status(t){document.getElementById('status').textContent=t;}
function iconUrl(style){return '/public/theme/hotspots/'+(STYLES.includes(style)?style:'mui_ten')+'.png';}
function marker(h){
  if(!kr)return;
  const n='edit_'+h.id;
  kr.call(`addhotspot(${n}); set(hotspot[${n}].url,'${iconUrl(h.style)}'); set(hotspot[${n}].ath,${h.ath}); set(hotspot[${n}].atv,${h.atv}); set(hotspot[${n}].scale,0.5); set(hotspot[${n}].edge,center); set(hotspot[${n}].distorted,true);`);
}
function esc(s){return String(s==null?'':s).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));}
function renderList(){
  document.getElementById('count').textContent=hotspots.length;
  document.getElementById('list').innerHTML = hotspots.map(h=>
    `<div class="hs">
      <span class="ico" style="background-image:url('${iconUrl(h.style)}')"></span>
      <div class="hs-body">
        <div class="hs-title"><span class="arrow">→</span> ${esc(titleOf[h.link]||h.link)}</div>
        <div class="hs-meta">
          <span class="chip">${esc(styleLabel[h.style]||h.style||'Mũi tên')}</span>
          <span class="coord">${(+h.ath).toFixed(1)}, ${(+h.atv).toFixed(1)}</span>
        </div>
        ${h.tip?`<div class="hs-tip">💬 ${esc(h.tip)}</div>`:''}
      </div>
      <button class="del" title="Xoá nút" onclick="delHs(${h.id})">✕</button>
    </div>`
  ).join('') || '<div class="empty">Chưa có nút nào.<br>Click lên ảnh để đặt nút đầu tiên.</div>';
}
function delHs(id){
  if(!confirm('Xoá nút này?'))return;
  const fd=new FormData(); fd.append('csrf',CSRF); fd.append('action','del'); fd.append('id',id);
  fetch('/admin/api/quick_hotspot.php',{method:'POST',body:fd}).then(r=>r.json()).then(()=>{
    if(kr)kr.call(`removehotspot(edit_${id})`);
    hotspots=hotspots.filter(h=>h.id!==id); renderList();
  });
}
window.delHs=delHs;

embedpano({xml:"/public/tour_xml.php?id="+encodeURIComponent(idPath),target:"pano",html5:"auto",basepath:"/js/",
  onready:function(k){
    kr=k;
    // Ẩn toàn bộ UI vtourskin (editor chỉ cần click đặt nút) — quét mọi layer skin_*/webvr_*
    const hide=()=>{try{const n=k.get('layer.count')||0;for(let i=0;i<n;i++){const nm=k.get('layer['+i+'].name')||'';if(nm.indexOf('skin_')===0||nm.indexOf('webvr_')===0){k.set('layer['+nm+'].visible',false);k.set('layer['+nm+'].enabled',false);}}}catch(e){}};
    hide(); let _c=0; const _iv=setInterval(()=>{hide(); if(++_c>20)clearInterval(_iv);},250); // ẩn liên tục ~5s
    k.set("events.onnewpano","js(window.__hideEditSkin && window.__hideEditSkin());");
    window.__hideEditSkin=hide;
    k.call("loadscene("+sceneName+", null, MERGE, BLEND(0.3));");
    setTimeout(()=>hotspots.forEach(marker),800);   // vẽ marker sẵn có
    // screentosphere ghi kết quả vào biến hs_ath/hs_atv rồi mới truyền sang JS
    k.set("events.onclick","screentosphere(mouse.x, mouse.y, hs_ath, hs_atv); js(window.__place(get(hs_ath), get(hs_atv)));");
    renderList();
  }});

window.__place=function(a, v){
  const ath=+parseFloat(a).toFixed(2), atv=+parseFloat(v).toFixed(2);
  if(isNaN(ath)||isNaN(atv)){status('Không lấy được toạ độ, thử click lại.');return;}
  const target=document.getElementById('target').value;
  if(!target){status('Chưa có phòng đích.');return;}
  const style=document.getElementById('style').value;
  const fd=new FormData();
  fd.append('csrf',CSRF); fd.append('action','add'); fd.append('scene_id',sceneId);
  fd.append('ath',ath); fd.append('atv',atv); fd.append('link_scene',target);
  fd.append('tooltip',document.getElementById('tip').value); fd.append('style',style);
  status('Đang lưu...');
  fetch('/admin/api/quick_hotspot.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
    if(!d.ok){status('Lỗi: '+(d.error||'?'));return;}
    const h={id:d.id,ath:ath,atv:atv,link:target,tip:document.getElementById('tip').value,style:style};
    hotspots.push(h); marker(h); renderList();
    status('✓ Đã đặt nút sang '+(titleOf[target]||target));
  }).catch(e=>status('Lỗi mạng'));
};
</script>
</body>
</html>
