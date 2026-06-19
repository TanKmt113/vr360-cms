<?php
/** Lưu phân quyền: mật khẩu tour (mode=password) hoặc khoá scene (mode=scenes). POST */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
auth_require();
csrf_check();

$tourId = (int)($_POST['tour_id'] ?? 0);
if (!db_one('SELECT id FROM tours WHERE id = ?', [$tourId])) {
    exit('Tour không hợp lệ');
}
$mode = $_POST['mode'] ?? '';

if ($mode === 'password') {
    $pw = (string)($_POST['password'] ?? '');
    $hash = $pw === '' ? null : auth_hash($pw);
    db()->prepare('UPDATE tours SET access_password_hash = ? WHERE id = ?')->execute([$hash, $tourId]);

} elseif ($mode === 'scenes') {
    // Reset toàn bộ rule của tour rồi ghi lại theo checkbox
    db()->prepare('DELETE FROM scene_permission WHERE tour_id = ?')->execute([$tourId]);
    $lock = $_POST['lock'] ?? [];
    if (is_array($lock)) {
        $ins = db()->prepare('INSERT INTO scene_permission (tour_id, scene_id, rule) VALUES (?,?,?)');
        foreach ($lock as $sceneId) {
            $sceneId = (int)$sceneId;
            // Chỉ chấp nhận scene thuộc tour này
            if (db_one('SELECT id FROM scenes WHERE id = ? AND tour_id = ?', [$sceneId, $tourId])) {
                $ins->execute([$tourId, $sceneId, 'login']);
            }
        }
    }
}

header('Location: /admin/modules/permission/list.php?tour=' . $tourId);
exit;
