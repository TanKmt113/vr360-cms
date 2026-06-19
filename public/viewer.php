<?php
/**
 * Viewer krpano: GET /public/viewer.php?id=<id_path>
 * Nhúng krpano, nạp tour.xml động (tour_xml.php), rồi kích hoạt module FE.
 */
require_once dirname(__DIR__) . '/core/i18n.php';

$idPath = trim((string)($_GET['id'] ?? ''));
$tour = $idPath !== '' ? tour_by_path($idPath) : null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title><?= $tour ? htmlspecialchars($tour['title']) : 'VR360 Viewer' ?></title>
    <style>
        html, body { height:100%; margin:0; padding:0; overflow:hidden; background:#000; color:#fff; font-family:Arial, sans-serif; }
        #pano { width:100%; height:100%; }
        .placeholder { display:flex; align-items:center; justify-content:center; height:100%; flex-direction:column; gap:12px; text-align:center; padding:20px; }
    </style>
    <link rel="stylesheet" href="/public/theme/viewer-ui.css">
</head>
<body>
<?php if (!$tour): ?>
    <div class="placeholder"><h2>Không tìm thấy tour</h2><p>Thiếu hoặc sai tham số <code>?id=</code></p></div>
<?php else: ?>
    <div id="pano">
        <noscript>Vui lòng bật JavaScript để xem tour 360°.</noscript>
    </div>

    <script src="/js/tour.js?v=2"></script>
    <script src="/public/theme/viewer-ui.js"></script>
    <script>
        const idPath = <?= json_encode($tour['id_path']) ?>;

        // 1) Nạp config tổng (allScenes) cho các module FE dùng
        window.util = window.util || { general: {} };

        // 2) Nhúng krpano với tour.xml sinh động từ DB
        embedpano({
            xml: "/public/tour_xml.php?id=" + encodeURIComponent(idPath),
            target: "pano",
            html5: "auto",
            basepath: "/js/",
            passQueryParameters: "startscene,startlookat",
            onready: function (krpano) {
                window.krpano = krpano;
                // Ghi thống kê mỗi khi đổi scene
                window.trackView = function () {
                    const scene = krpano.get("xml.scene") || "";
                    const fd = new FormData();
                    fd.append("id", idPath); fd.append("scene", scene); fd.append("event", "view");
                    navigator.sendBeacon
                        ? navigator.sendBeacon("/user_FE/theme/api/analytic/post.php", fd)
                        : fetch("/user_FE/theme/api/analytic/post.php", { method: "POST", body: fd });
                };
                // 3) Tải config rồi dựng giao diện viewer (thanh công cụ, minimap, gallery, audio...)
                fetch("/user/includes/get_config.php?id=" + encodeURIComponent(idPath))
                    .then(r => r.json())
                    .then(cfg => {
                        window.util.general.allScenes = cfg.allScenes;
                        window.util.general.settings  = cfg.settings;
                        // Dựng UI heritage (tự lo onnewpano + trackView + audio)
                        if (window.HeritageUI) window.HeritageUI.init(cfg, krpano);
                    })
                    .catch(e => console.error("Lỗi tải config:", e));
            }
        });
    </script>
<?php endif; ?>
</body>
</html>
