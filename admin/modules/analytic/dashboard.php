<?php
/** Dashboard thống kê theo tour. ?tour=<id> */
require_once dirname(__DIR__, 3) . '/core/db.php';
require_once dirname(__DIR__, 3) . '/core/auth.php';
$pageTitle = 'Thống kê';
require dirname(__DIR__, 2) . '/includes/header.php';

$tours = db_all('SELECT id, id_path, title FROM tours ORDER BY title');
$tourId = (int)($_GET['tour'] ?? ($tours[0]['id'] ?? 0));

$total   = (int)(db_one('SELECT COUNT(*) c FROM analytics WHERE tour_id = ?', [$tourId])['c'] ?? 0);
$uniques = (int)(db_one('SELECT COUNT(DISTINCT ip) c FROM analytics WHERE tour_id = ?', [$tourId])['c'] ?? 0);
$today   = (int)(db_one('SELECT COUNT(*) c FROM analytics WHERE tour_id = ? AND DATE(ts) = CURDATE()', [$tourId])['c'] ?? 0);

$byScene = db_all(
    'SELECT s.title, COUNT(a.id) AS views
     FROM analytics a LEFT JOIN scenes s ON s.id = a.scene_id
     WHERE a.tour_id = ? GROUP BY a.scene_id ORDER BY views DESC LIMIT 30',
    [$tourId]
);
$byDay = db_all(
    'SELECT DATE(ts) AS d, COUNT(*) AS c FROM analytics
     WHERE tour_id = ? AND ts >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
     GROUP BY DATE(ts) ORDER BY d',
    [$tourId]
);
$maxDay = 0;
foreach ($byDay as $d) {
    $maxDay = max($maxDay, (int)$d['c']);
}
?>
<?php $maxViews = 0; foreach ($byScene as $r) { $maxViews = max($maxViews, (int)$r['views']); } ?>
<style>
  .stat-card{ position:relative; overflow:hidden; }
  .stat-card .stat-ico{ position:absolute; right:-6px; top:-6px; font-size:64px; opacity:.08; }
  .stat-card .num{ font-size:34px; font-weight:800; line-height:1.1; }
  .stat-card .lbl{ color:#8a94a6; font-size:13px; margin-top:2px; }
  .bar-chart{ display:flex; align-items:flex-end; gap:6px; height:150px; }
  .bar-chart .col{ flex:1; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; height:100%; }
  .bar-chart .bar{ width:100%; max-width:34px; border-radius:5px 5px 0 0; min-height:3px;
    background:linear-gradient(180deg,#5b8def,#2d6cdf); transition:.15s; }
  .bar-chart .col:hover .bar{ background:linear-gradient(180deg,#7aa6ff,#1f57bd); }
  .bar-chart .v{ font-size:11px; font-weight:700; color:#2b3445; margin-bottom:3px; }
  .bar-chart .dt{ font-size:10px; color:#aab; margin-top:4px; }
  .scene-bar{ height:6px; border-radius:4px; background:#eef1f6; overflow:hidden; min-width:80px; }
  .scene-bar > div{ height:100%; background:linear-gradient(90deg,#2d6cdf,#5b8def); border-radius:4px; }
</style>

<h1 class="h3 fw-bold mb-1"><i class="bi bi-bar-chart-line"></i> Thống kê</h1>
<div class="ctx-bar">
    <form method="get" class="d-flex align-items-center gap-2 m-0">
        <span class="text-secondary small">Tour:</span>
        <select name="tour" onchange="this.form.submit()" class="form-select form-select-sm" style="width:auto;">
            <?php foreach ($tours as $t): ?><option value="<?= $t['id'] ?>" <?= $t['id'] == $tourId ? 'selected' : '' ?>><?= htmlspecialchars($t['title']) ?></option><?php endforeach; ?>
        </select>
    </form>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-4"><div class="card border-0 shadow-sm stat-card"><div class="card-body">
        <i class="bi bi-eye stat-ico text-primary"></i>
        <div class="num text-dark"><?= number_format($total) ?></div><div class="lbl">Tổng lượt xem</div>
    </div></div></div>
    <div class="col-sm-4"><div class="card border-0 shadow-sm stat-card"><div class="card-body">
        <i class="bi bi-people stat-ico text-success"></i>
        <div class="num text-dark"><?= number_format($uniques) ?></div><div class="lbl">Khách (theo IP)</div>
    </div></div></div>
    <div class="col-sm-4"><div class="card border-0 shadow-sm stat-card"><div class="card-body">
        <i class="bi bi-calendar-check stat-ico text-warning"></i>
        <div class="num text-dark"><?= number_format($today) ?></div><div class="lbl">Hôm nay</div>
    </div></div></div>
</div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="section-title mb"><i class="bi bi-graph-up"></i> Lượt xem 14 ngày gần nhất</div>
    <?php if ($byDay): ?>
    <div class="bar-chart">
        <?php foreach ($byDay as $d): $h = $maxDay ? round((int)$d['c'] / $maxDay * 100) : 0; ?>
            <div class="col" title="<?= htmlspecialchars($d['d']) ?>: <?= (int)$d['c'] ?> lượt">
                <div class="v"><?= (int)$d['c'] ?></div>
                <div class="bar" style="height:<?= $h ?>%;"></div>
                <div class="dt"><?= date('d/m', strtotime($d['d'])) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?><div class="empty">Chưa có dữ liệu.</div><?php endif; ?>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="section-title mb"><i class="bi bi-list-ol"></i> Lượt xem theo scene</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Scene</th><th style="width:45%;">Mức độ</th><th class="text-end" style="width:90px;">Lượt</th></tr></thead>
            <tbody>
            <?php foreach ($byScene as $r): $pct = $maxViews ? round((int)$r['views'] / $maxViews * 100) : 0; ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($r['title'] ?? '(không xác định)') ?></td>
                    <td><div class="scene-bar"><div style="width:<?= $pct ?>%;"></div></div></td>
                    <td class="text-end"><?= number_format((int)$r['views']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$byScene): ?><tr><td colspan="3"><div class="empty">Chưa có dữ liệu.</div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div></div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
