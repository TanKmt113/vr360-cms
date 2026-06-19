<?php
/** Form thêm/sửa scene. /admin/modules/scene/edit.php?id=<scene> | ?tour=<tour> */
require_once dirname(__DIR__, 3) . '/core/db.php';
require_once dirname(__DIR__, 3) . '/core/auth.php';
$pageTitle = 'Sửa Scene';
require dirname(__DIR__, 2) . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$scene = $id ? db_one('SELECT * FROM scenes WHERE id = ?', [$id]) : null;
$tourId = (int)($scene['tour_id'] ?? ($_GET['tour'] ?? 0));
$f = $scene ?: ['name' => '', 'title' => '', 'thumb_url' => '', 'pano_url' => '',
                'hlookat' => 0, 'vlookat' => 0, 'fov' => 120, 'sort' => 0];
?>
<h1 class="h3 fw-bold mb-4"><?= $id ? 'Sửa' : 'Thêm' ?> Scene</h1>
<div class="card border-0 shadow-sm mb-3" style="max-width:640px;"><div class="card-body">
    <form method="post" action="/admin/api/save_scene.php">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="tour_id" value="<?= $tourId ?>">
        <?php
        $fields = [
            'name'      => ['Name (krpano, không dấu)', 'text'],
            'title'     => ['Tiêu đề hiển thị', 'text'],
            'pano_url'  => ['Pano URL (template tiles, vd: phong.tiles/%s/l%l/%v/l%l_%s_%v_%h.jpg)', 'text'],
            'thumb_url' => ['Thumbnail URL', 'text'],
            'hlookat'   => ['hlookat (góc ngang mặc định)', 'number'],
            'vlookat'   => ['vlookat (góc dọc mặc định)', 'number'],
            'fov'       => ['fov (góc nhìn)', 'number'],
            'sort'      => ['Thứ tự', 'number'],
        ];
        foreach ($fields as $k => [$label, $type]): ?>
            <label class="form-label" style="display:block;margin:10px 0 4px;"><?= $label ?></label>
            <input class="form-control" style="width:100%;"
                   name="<?= $k ?>" type="<?= $type ?>" <?= $type === 'number' ? 'step="any"' : '' ?>
                   value="<?= htmlspecialchars((string)$f[$k]) ?>" <?= $k === 'name' ? 'required' : '' ?>>
        <?php endforeach; ?>
        <div style="margin-top:18px;">
            <button type="submit" class="btn btn-primary">Lưu</button>
            <a href="list.php?tour=<?= $tourId ?>" style="margin-left:12px;">Hủy</a>
        </div>
    </form>
</div></div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
