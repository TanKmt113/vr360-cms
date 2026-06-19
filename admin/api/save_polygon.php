<?php
/** Lưu polygon hotspot. POST /admin/api/save_polygon.php */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
auth_require();
csrf_check();

$sceneId = (int)($_POST['scene_id'] ?? 0);
if ($sceneId <= 0 || !db_one('SELECT id FROM scenes WHERE id=?', [$sceneId])) {
    exit('Scene không hợp lệ');
}

// Validate points JSON: phải là mảng các cặp số, >= 3 điểm
$points = json_decode((string)($_POST['points_json'] ?? ''), true);
if (!is_array($points) || count($points) < 3) {
    exit('Cần tối thiểu 3 điểm hợp lệ');
}
$clean = [];
foreach ($points as $p) {
    if (is_array($p) && count($p) >= 2 && is_numeric($p[0]) && is_numeric($p[1])) {
        $clean[] = [(float)$p[0], (float)$p[1]];
    }
}
if (count($clean) < 3) {
    exit('Dữ liệu điểm không hợp lệ');
}

db()->prepare('INSERT INTO polygon_hotspots (scene_id, points_json, action, link) VALUES (?,?,?,?)')
    ->execute([
        $sceneId,
        json_encode($clean, JSON_UNESCAPED_UNICODE),
        trim((string)($_POST['action'] ?? 'link')),
        trim((string)($_POST['link'] ?? '')) ?: null,
    ]);

header('Location: /admin/modules/polygon/list.php?scene=' . $sceneId);
exit;
