<?php
/**
 * GET /user_FE/theme/api/audio_group/get/get_scene_name.php?id=<id_path>&scene=<name>
 * Trả tên hiển thị của scene (khớp mẫu: trả chuỗi text thuần).
 */
require_once dirname(__DIR__, 5) . '/core/i18n.php';

header('Content-Type: text/plain; charset=utf-8');

$idPath = trim((string)($_GET['id'] ?? ''));
$sceneName = trim((string)($_GET['scene'] ?? ''));
$tour = $idPath !== '' ? tour_by_path($idPath) : null;
if (!$tour || $sceneName === '') {
    echo '';
    exit;
}
$row = db_one('SELECT title FROM scenes WHERE tour_id = ? AND name = ? LIMIT 1', [(int)$tour['id'], $sceneName]);
echo $row['title'] ?? '';
