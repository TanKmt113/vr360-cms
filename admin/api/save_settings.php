<?php
/** Lưu cấu hình tour. POST /admin/api/save_settings.php */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
auth_require();
csrf_check();

$tourId = (int)($_POST['tour_id'] ?? 0);
if (!db_one('SELECT id FROM tours WHERE id = ?', [$tourId])) {
    exit('Tour không hợp lệ');
}

// Checkbox → 'true'/'false'; số → giữ nguyên (đã validate kiểu)
$boolKeys = ['gyro', 'webvr', 'littleplanetintro', 'autotour'];
$numKeys  = ['autotour_dwell', 'autotour_rotate_speed'];

$pairs = [];
foreach ($boolKeys as $k) {
    $pairs[$k] = isset($_POST[$k]) ? 'true' : 'false';
}
foreach ($numKeys as $k) {
    if (isset($_POST[$k]) && is_numeric($_POST[$k])) {
        $pairs[$k] = (string)(float)$_POST[$k];
    }
}

$up = db()->prepare(
    'INSERT INTO settings (tour_id, `key`, `value`) VALUES (?,?,?)
     ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
);
foreach ($pairs as $k => $v) {
    $up->execute([$tourId, $k, $v]);
}

header('Location: /admin/modules/settings/edit.php?tour=' . $tourId);
exit;
