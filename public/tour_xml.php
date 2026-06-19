<?php
/**
 * Sinh tour.xml động từ DB cho krpano.
 * GET /public/tour_xml.php?id=<id_path>
 * Cấu trúc scene/hotspot khớp định dạng krpano của mẫu.
 */
require_once dirname(__DIR__) . '/core/i18n.php';

header('Content-Type: text/xml; charset=utf-8');

$idPath = trim((string)($_GET['id'] ?? ''));
$tour = $idPath !== '' ? tour_by_path($idPath) : null;
if (!$tour) {
    echo '<krpano></krpano>';
    exit;
}
$tourId = (int)$tour['id'];

/** Escape an toàn cho thuộc tính XML */
function x(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

$scenes = db_all('SELECT * FROM scenes WHERE tour_id = ? ORDER BY sort ASC', [$tourId]);

// Đường dẫn upload panos của tour: /upload/panos/<id_path>/...
$panoBase = BASE_URL . '/upload/panos/' . rawurlencode($tour['id_path']);

// Settings của tour
$cfg = [];
foreach (db_all('SELECT `key`, `value` FROM settings WHERE tour_id = ?', [$tourId]) as $r) {
    $cfg[$r['key']] = $r['value'];
}
$flag = fn(string $k, string $d): string => (($cfg[$k] ?? $d) === 'true') ? 'true' : 'false';

// noskin=1 → không nạp skin krpano (dùng cho trang đặt hotspot: chỉ cần pano + click)
$noskin = isset($_GET['noskin']);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<krpano version="1.23" title="' . x($tour['title']) . '">' . "\n";
if (!$noskin) {
    echo '    <include url="' . BASE_URL . '/public/vtour/skin/vtourskin.xml" />' . "\n";
    // Tắt thumbnail + control bar mặc định của krpano — ta dùng giao diện tự viết (filmstrip + toolbar)
    printf(
        '    <skin_settings thumbs="false" gyro="%s" webvr="%s" littleplanetintro="%s" autotour="%s" />' . "\n\n",
        $flag('gyro', 'true'),
        $flag('webvr', 'true'),
        $flag('littleplanetintro', 'false'),
        $flag('autotour', 'false')
    );
}

// Hiệu ứng nhấp nháy nhẹ cho nút điều hướng (code riêng, không dùng plugin StarGlobal)
echo '    <action name="navpulse" args="hs,base">' . "\n";
echo '        tween(hotspot[get(hs)].scale, calc:%2 * 1.18, 0.9, easeInOutSine,' . "\n";
echo '          tween(hotspot[get(hs)].scale, %2, 0.9, easeInOutSine, navpulse(get(hs), %2)));' . "\n";
echo '    </action>' . "\n\n";

// Map tên scene → tiêu đề (để hiện nhãn tên phòng đích trên nút điều hướng)
$sceneTitles = [];
foreach ($scenes as $sc) {
    $sceneTitles[$sc['name']] = $sc['title'];
}

foreach ($scenes as $s) {
    $sceneName = x($s['name']);
    $panoUrl = (string)$s['pano_url'];
    // Ảnh equirect đơn (đã upload) bắt đầu bằng /upload/... → dùng tuyệt đối.
    // Template tiles tương đối → ghép với panoBase của tour.
    $isSphere = strpos($panoUrl, '%s') === false; // không có placeholder tiles = ảnh phẳng
    if (strpos($panoUrl, '/upload/') === 0) {
        $fullPano = BASE_URL . $panoUrl;
    } else {
        $fullPano = $panoBase . '/' . ltrim($panoUrl, '/');
    }
    $previewUrl = $isSphere ? '' : preg_replace('#/%s/.*$#', '/preview.jpg', $fullPano);
    $thumbUrl = $s['thumb_url']
        ? (strpos((string)$s['thumb_url'], '/upload/') === 0 ? BASE_URL . $s['thumb_url'] : $panoBase . '/' . ltrim((string)$s['thumb_url'], '/'))
        : '';

    echo '    <scene name="' . $sceneName . '" title="' . x($s['title']) . '"';
    echo ' thumburl="' . x($thumbUrl) . '" onstart="">' . "\n";
    echo '        <control bouncinglimits="calc:image.cube ? true : false" />' . "\n";
    printf(
        '        <view hlookat="%s" vlookat="%s" fovtype="MFOV" fov="%s" maxpixelzoom="2.0" fovmin="70" fovmax="140" limitview="auto" />' . "\n",
        (float)$s['hlookat'],
        (float)$s['vlookat'],
        (float)$s['fov']
    );
    if ($previewUrl !== '') {
        echo '        <preview url="' . x($previewUrl) . '" />' . "\n";
    }
    echo '        <image>' . "\n";
    if ($isSphere) {
        echo '            <sphere url="' . x($fullPano) . '" />' . "\n";
    } else {
        $multires = trim((string)($s['pano_multires'] ?? '')) ?: '512,640';
        echo '            <cube url="' . x($fullPano) . '" multires="' . x($multires) . '" />' . "\n";
    }
    echo '        </image>' . "\n";

    // Hotspot: icon theo style + nhấp nháy + nav/info
    $iconStyles = ['default', 'vongtron', 'giotnuoc', 'info', 'mui_ten'];
    $hotspots = db_all('SELECT * FROM hotspots WHERE scene_id = ? ORDER BY sort ASC', [(int)$s['id']]);
    foreach ($hotspots as $h) {
        $hsName = 'hs_' . x($h['uuid']);
        // info → info.png; nav → mũi tên đỏ trên sàn (mặc định) hoặc style đã chọn
        if ($h['type'] === 'info') {
            $style = 'info';
        } else {
            $style = in_array($h['style'], $iconStyles, true) ? $h['style'] : 'mui_ten';
        }
        $iconUrl = BASE_URL . '/public/theme/hotspots/' . $style . '.png';

        $onclick = '';
        if ($h['type'] === 'nav' && $h['link_scene']) {
            $onclick = 'loadscene(' . x($h['link_scene']) . ', null, MERGE, BLEND(0.5));';
        } elseif ($h['type'] === 'info') {
            // Mở popup thông tin (JS HeritageUI đọc hotspot.i18n theo uuid)
            $onclick = 'js(window.HeritageUI &amp;&amp; window.HeritageUI.showInfo(' . json_encode((string)$h['uuid']) . '));';
        }

        // Nút điều hướng nằm phẳng trên bề mặt (distorted) như mẫu; nút info luôn quay mặt về người xem
        $distorted = $h['type'] === 'info' ? 'false' : 'true';
        $baseScale = $h['type'] === 'info' ? '0.42' : '0.6';
        printf(
            '        <hotspot name="%s" url="%s" ath="%s" atv="%s" scale="%s" edge="center" distorted="%s" zorder="1"',
            $hsName,
            x($iconUrl),
            (float)$h['ath'],
            (float)$h['atv'],
            $baseScale,
            $distorted
        );
        echo ' onloaded="navpulse(get(name), ' . $baseScale . ');"';
        echo ' onover="tween(scale, ' . ($h['type'] === 'info' ? '0.55' : '0.72') . ', 0.2);" onout="tween(scale, ' . $baseScale . ', 0.2);"';
        if ($onclick !== '') {
            echo ' onclick="' . $onclick . '"';
        }
        if ($h['tooltip']) {
            echo ' tooltip="' . x($h['tooltip']) . '"';
        }
        echo ' />' . "\n";

        // Nhãn tên phòng đích phía trên nút điều hướng (như "Conference hall" trong mẫu)
        if ($h['type'] === 'nav' && $h['link_scene']) {
            $label = $h['tooltip'] ?: ($sceneTitles[$h['link_scene']] ?? '');
            if ($label !== '') {
                printf(
                    '        <hotspot name="lbl_%s" type="text" html="%s" ath="%s" atv="%s" '
                    . 'css="color:#FFFFFF; font-size:15px; font-weight:bold; text-align:center;" '
                    . 'bg="false" border="0" enabled="false" distorted="false" zorder="2" '
                    . 'edge="bottom" txtshadow="0 1 3 0x000000 0.9" />' . "\n",
                    x($h['uuid']),
                    x($label),
                    (float)$h['ath'],
                    (float)$h['atv'] - 7
                );
            }
        }
    }

    // Iframe buttons → hotspot click gọi JS module FE (openIframe)
    $iframes = db_all('SELECT * FROM iframe_buttons WHERE scene_id = ? ORDER BY id', [(int)$s['id']]);
    foreach ($iframes as $idx => $b) {
        $icon = $b['icon'] ? (strpos((string)$b['icon'], '/upload/') === 0 ? BASE_URL . $b['icon'] : $b['icon'])
                           : BASE_URL . '/public/vtour/skin/vtourskin.png';
        printf(
            '        <hotspot name="iframe_%d" url="%s" ath="%s" atv="%s" scale="0.5" edge="center" onclick="js(window.openIframe &amp;&amp; window.openIframe(get(iframe_url_%d)));" />' . "\n",
            $idx,
            x($icon),
            (float)$b['ath'],
            (float)$b['atv'],
            $idx
        );
        // Lưu URL vào biến krpano để onclick lấy (tránh nhúng URL phức tạp vào action)
        echo '        <data name="iframe_url_' . $idx . '">' . x($b['iframe_url']) . '</data>' . "\n";
    }

    // Polygon hotspots
    $polys = db_all('SELECT * FROM polygon_hotspots WHERE scene_id = ? ORDER BY id', [(int)$s['id']]);
    foreach ($polys as $pidx => $p) {
        $pts = json_decode((string)$p['points_json'], true) ?: [];
        if (count($pts) < 3) {
            continue;
        }
        $onclick = '';
        if ($p['action'] === 'link' && $p['link']) {
            $onclick = ' onclick="loadscene(' . x($p['link']) . ', null, MERGE, BLEND(0.5));"';
        }
        echo '        <hotspot name="poly_' . $pidx . '" renderer="webgl" polyline="false" fillcolor="0xFFFFFF" fillalpha="0.15" bordercolor="0xFFCC00" borderwidth="2.0"' . $onclick . '>' . "\n";
        foreach ($pts as $pt) {
            printf('            <point ath="%s" atv="%s" />' . "\n", (float)$pt[0], (float)$pt[1]);
        }
        echo '        </hotspot>' . "\n";
    }

    echo '    </scene>' . "\n\n";
}

echo '</krpano>' . "\n";
