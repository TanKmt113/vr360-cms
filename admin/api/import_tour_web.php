<?php
/** Xử lý import tour krpano từ giao diện. POST /admin/api/import_tour_web.php */
require_once dirname(__DIR__, 2) . '/core/db.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
require_once dirname(__DIR__, 2) . '/core/tour_import.php';
auth_require();
csrf_check();

// Tour ZIP có thể chứa hàng nghìn tiles — tránh timeout mặc định 30s
@set_time_limit(600);
@ini_set('max_input_time', '600');
@ini_set('memory_limit', '512M');

function back(string $key, string $val): void
{
    header('Location: /admin/modules/import/index.php?' . $key . '=' . urlencode($val));
    exit;
}

$tourId = (int)($_POST['tour_id'] ?? 0);
$tour = $tourId ? db_one('SELECT id, id_path FROM tours WHERE id = ?', [$tourId]) : null;
if (!$tour) {
    back('err', 'Tour không hợp lệ.');
}
$doReset = !empty($_POST['reset']);
$mode = $_POST['mode'] ?? 'path';
$cleanup = null;

if ($mode === 'zip') {
    $f = $_FILES['zip'] ?? null;
    if (!$f || ($f['error'] ?? 1) !== UPLOAD_ERR_OK) {
        back('err', upload_file_error_message((int)($f['error'] ?? UPLOAD_ERR_NO_FILE)));
    }
    if (strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION)) !== 'zip') {
        back('err', 'Cần file .zip hợp lệ.');
    }
    $tmp = sys_get_temp_dir() . '/krpano_' . bin2hex(random_bytes(8));
    $cleanup = $tmp;
    $extractErr = extract_zip_safe((string)$f['tmp_name'], $tmp);
    if ($extractErr !== null) {
        back('err', $extractErr);
    }

    // Thư mục krpano có thể nằm lồng 1 cấp (vd zip chứa "tour/tour.xml")
    $src = $tmp;
    if (krpano_find_xml($src) === null) {
        foreach (glob("$tmp/*", GLOB_ONLYDIR) ?: [] as $sub) {
            if (krpano_find_xml($sub) !== null) { $src = $sub; break; }
        }
    }
} else {
    // Cách 1: đường dẫn server (tương đối so với gốc CMS, hoặc tuyệt đối)
    $src = trim((string)($_POST['src'] ?? ''));
    if ($src === '') {
        back('err', 'Cần nhập đường dẫn thư mục.');
    }
    if ($src[0] !== '/') {
        $src = BASE_PATH . '/' . $src;   // tương đối → ghép gốc dự án
    }
}

$r = import_krpano_tour($src, $tourId, (string)$tour['id_path'], true, $doReset);

if ($cleanup) {
    rrmdir_path($cleanup);
}

if (!$r['ok']) {
    back('err', $r['error'] ?? 'Import thất bại.');
}
back('msg', "Import xong: {$r['imported']} scene (bỏ qua {$r['skipped']}, copy tiles {$r['copied']}). Xem: /public/viewer.php?id={$tour['id_path']}");
