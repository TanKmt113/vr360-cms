<?php
/** Vẽ polygon trực quan: click nhiều điểm trên panorama. ?scene=<id> */
require_once dirname(__DIR__, 3) . '/core/db.php';
require_once dirname(__DIR__, 3) . '/core/auth.php';
auth_require();

$sceneId = (int)($_GET['scene'] ?? 0);
$scene = $sceneId ? db_one('SELECT * FROM scenes WHERE id=?', [$sceneId]) : null;
if (!$scene) { exit('Không tìm thấy scene.'); }
$tour = db_one('SELECT id_path FROM tours WHERE id=?', [(int)$scene['tour_id']]);
// Danh sách scene khác để chọn link
$otherScenes = db_all('SELECT name, title FROM scenes WHERE tour_id=? ORDER BY sort', [(int)$scene['tour_id']]);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vẽ polygon — <?= htmlspecialchars($scene['title']) ?></title>
    <style>
        html,body{margin:0;height:100%;font-family:Arial;background:#1a1a1a;color:#fff}
        #bar{background:#2D3E50;padding:10px 16px;display:flex;gap:14px;align-items:center;flex-wrap:wrap}
        #pano{width:100%;height:calc(100vh - 52px)}
        button,select,input{font-size:14px}
        .btn{background:#27ae60;color:#fff;border:0;border-radius:5px;padding:7px 14px;cursor:pointer}
        .btn.gray{background:#7f8c8d}
        .pts{font-family:monospace;background:#0006;padding:4px 8px;border-radius:4px;max-width:340px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        a.back{color:#cfd8e3}
    </style>
</head>
<body>
<div id="bar">
    <a class="back" href="list.php?scene=<?= $sceneId ?>">← Danh sách</a>
    <span>Click lên ảnh để thêm điểm. Tối thiểu 3 điểm.</span>
    <span class="pts">Điểm: <b id="cnt">0</b></span>
    <label>Action:
        <select id="action">
            <option value="link">Chuyển scene</option>
            <option value="info">Hiện thông tin</option>
        </select>
    </label>
    <label>Link:
        <select id="link">
            <option value="">—</option>
            <?php foreach ($otherScenes as $o): ?>
                <option value="<?= htmlspecialchars($o['name']) ?>"><?= htmlspecialchars($o['title']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button class="btn gray" id="undo">↶ Bỏ điểm cuối</button>
    <button class="btn" id="save" disabled style="opacity:.5">💾 Lưu polygon</button>
</div>
<div id="pano"></div>

<script src="/js/tour.js?v=2"></script>
<script>
const sceneId=<?= $sceneId ?>, sceneName=<?= json_encode($scene['name']) ?>, idPath=<?= json_encode($tour['id_path'] ?? '') ?>;
let points=[];
function refresh(){
    document.getElementById('cnt').textContent=points.length;
    const s=document.getElementById('save');
    const ok=points.length>=3; s.disabled=!ok; s.style.opacity=ok?1:.5;
}
embedpano({xml:"/public/tour_xml.php?id="+encodeURIComponent(idPath),target:"pano",html5:"auto",basepath:"/js/",
    onready:function(k){
        const hide=()=>{try{const n=k.get('layer.count')||0;for(let i=0;i<n;i++){const nm=k.get('layer['+i+'].name')||'';if(nm.indexOf('skin_')===0||nm.indexOf('webvr_')===0){k.set('layer['+nm+'].visible',false);k.set('layer['+nm+'].enabled',false);}}}catch(e){}};
        hide(); let _c=0; const _iv=setInterval(()=>{hide(); if(++_c>20)clearInterval(_iv);},250);
        k.call("loadscene("+sceneName+", null, MERGE, BLEND(0.3));");
        k.set("events.onclick","screentosphere(mouse.x, mouse.y, p_ath, p_atv); js(window.__add(get(p_ath), get(p_atv)));");
    }});
window.__add=function(a, v){
    const ath=+parseFloat(a).toFixed(2), atv=+parseFloat(v).toFixed(2);
    if(isNaN(ath)||isNaN(atv))return;
    points.push([ath, atv]);
    refresh();
};
document.getElementById('undo').onclick=()=>{points.pop();refresh();};
document.getElementById('save').onclick=function(){
    if(points.length<3)return;
    const f=document.createElement('form');f.method='POST';f.action='/admin/api/save_polygon.php';
    const add=(n,v)=>{const i=document.createElement('input');i.name=n;i.value=v;f.appendChild(i);};
    add('scene_id',sceneId);
    add('points_json',JSON.stringify(points));
    add('action',document.getElementById('action').value);
    add('link',document.getElementById('link').value);
    add('csrf',<?= json_encode(csrf_token()) ?>);
    document.body.appendChild(f);f.submit();
};
</script>
</body>
</html>
