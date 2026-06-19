<?php
/**
 * POST /user_FE/theme/api/analytic/post.php
 * Body: id=<id_path>&scene=<name>&event=<view|click|...>
 * Ghi 1 sự kiện thống kê. Trả {ok:true}.
 */
require_once dirname(__DIR__, 4) . '/core/response.php';
require_once dirname(__DIR__, 4) . '/core/i18n.php';
require_once dirname(__DIR__, 4) . '/core/ratelimit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('POST only', 405);
}

// Chống spam thống kê: tối đa 120 sự kiện / phút / IP
if (!rate_limit_ok('analytic', 120, 60)) {
    json_out(['ok' => false]); // bỏ qua im lặng, không tính lỗi
}

$idPath = trim((string)($_POST['id'] ?? ''));
$tour = $idPath !== '' ? tour_by_path($idPath) : null;
if (!$tour) {
    json_out(['ok' => false]);
}

$sceneName = trim((string)($_POST['scene'] ?? ''));
$event = substr(trim((string)($_POST['event'] ?? 'view')), 0, 64) ?: 'view';

$sceneId = null;
if ($sceneName !== '') {
    $row = db_one('SELECT id FROM scenes WHERE tour_id = ? AND name = ? LIMIT 1', [(int)$tour['id'], $sceneName]);
    $sceneId = $row ? (int)$row['id'] : null;
}

// IP dạng nhị phân (hỗ trợ IPv4/IPv6), UA cắt ngắn
$ipBin = @inet_pton($_SERVER['REMOTE_ADDR'] ?? '') ?: null;
$ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512);

db()->prepare('INSERT INTO analytics (tour_id, scene_id, event, ip, ua) VALUES (?,?,?,?,?)')
    ->execute([(int)$tour['id'], $sceneId, $event, $ipBin, $ua]);

json_out(['ok' => true]);
