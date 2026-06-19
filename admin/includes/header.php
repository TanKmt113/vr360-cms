<?php
require_once dirname(__DIR__, 2) . '/core/auth.php';
auth_require();
send_security_headers();
$me = auth_user();

$uri = $_SERVER['REQUEST_URI'] ?? '';
$nav = [
    ['/admin/dashboard.php', 'Tổng quan', '<svg viewBox="0 0 24 24"><path d="M3 13h8V3H3zm0 8h8v-6H3zm10 0h8V11h-8zm0-18v6h8V3z"/></svg>'],
    ['/admin/modules/import/index.php', 'Nhập tour krpano', '<svg viewBox="0 0 24 24"><path d="M5 20h14v-2H5zm7-16l-5 5h3v6h4v-6h3z"/></svg>'],
    ['/admin/modules/scene/list.php', 'Cảnh (Scene)', '<svg viewBox="0 0 24 24"><path d="M4 5h16v14H4zm2 2v10h12V7zm3 2l3 3 2-2 2 4H8z"/></svg>'],
    ['/admin/modules/minimap/edit.php', 'Bản đồ', '<svg viewBox="0 0 24 24"><path d="M15 4l-6 2L3 4v16l6-2 6 2 6-2V2zm0 14l-6-2V6l6 2z"/></svg>'],
    ['/admin/modules/settings/edit.php', 'Cấu hình & Tự động', '<svg viewBox="0 0 24 24"><path d="M12 8a4 4 0 100 8 4 4 0 000-8zm9 4l-2-1.5.3-2.5-2.4-.7-1-2.3-2.4 1L12 3l-1.5 2.5-2.4-1-1 2.3-2.4.7.3 2.5L3 12l2 1.5-.3 2.5 2.4.7 1 2.3 2.4-1L12 21l1.5-2.5 2.4 1 1-2.3 2.4-.7-.3-2.5z"/></svg>'],
    ['/admin/modules/chatbot/list.php', 'Trợ lý hỏi đáp', '<svg viewBox="0 0 24 24"><path d="M4 4h16a1 1 0 011 1v12a1 1 0 01-1 1H8l-4 4V5a1 1 0 011-1zm3 6h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2z"/></svg>'],
    ['/admin/modules/language/list.php', 'Ngôn ngữ', '<svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm6.9 6h-2.8a15 15 0 00-1.3-3.2A8 8 0 0118.9 8zM12 4c.8 1.2 1.5 2.5 1.9 4h-3.8c.4-1.5 1.1-2.8 1.9-4zM4.3 14a8 8 0 010-4h3.2a16 16 0 000 4zm.8 2h2.8c.3 1.2.8 2.3 1.3 3.2A8 8 0 015.1 16zm2.8-8H5.1a8 8 0 014.1-3.2A15 15 0 007.9 8zM12 20c-.8-1.2-1.5-2.5-1.9-4h3.8c-.4 1.5-1.1 2.8-1.9 4zm2.4-6H9.6a14 14 0 010-4h4.8a14 14 0 010 4zm.5 5.2c.5-.9 1-2 1.3-3.2h2.8a8 8 0 01-4.1 3.2zM16.5 14a16 16 0 000-4h3.2a8 8 0 010 4z"/></svg>'],
    ['/admin/modules/permission/list.php', 'Phân quyền', '<svg viewBox="0 0 24 24"><path d="M12 1l9 4v6c0 5-3.8 9.7-9 11-5.2-1.3-9-6-9-11V5zm0 4a3 3 0 00-1 5.8V14h2v-3.2A3 3 0 0012 5z"/></svg>'],
    ['/admin/modules/analytic/dashboard.php', 'Thống kê', '<svg viewBox="0 0 24 24"><path d="M4 20h2v-8H4zm5 0h2V4H9zm5 0h2v-5h-2zm5 0h2V9h-2z"/></svg>'],
];
function nav_active(string $href, string $uri): bool
{
    // active nếu URI hiện tại thuộc cùng module
    $base = preg_replace('#/[^/]+\.php.*$#', '', $href);
    return $href !== '' && strpos($uri, $base) === 0 && $base !== '/admin';
}
$initial = strtoupper(substr($me['username'] ?? 'A', 0, 1));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'VR360 CMS') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/admin/assets/admin.css?v=6">
</head>
<body>
<!-- Topbar -->
<nav class="navbar app-topbar navbar-dark sticky-top px-3">
    <span class="navbar-brand d-flex align-items-center gap-2 fw-bold mb-0">
        <span class="app-logo">◎</span> VR360 CMS
    </span>
    <button class="btn btn-sm btn-outline-light d-lg-none" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-label="Menu">
        <i class="bi bi-list"></i>
    </button>
    <div class="d-none d-lg-flex align-items-center gap-3 small">
        <span class="d-flex align-items-center gap-2 app-user">
            <span class="app-avatar"><?= htmlspecialchars($initial) ?></span>
            <span><?= htmlspecialchars($me['username']) ?> · <?= htmlspecialchars($me['role']) ?></span>
        </span>
        <a class="btn btn-sm btn-outline-light" href="/admin/logout.php">Đăng xuất</a>
    </div>
</nav>

<div class="app-layout">
    <!-- Sidebar (offcanvas on mobile, static on desktop) -->
    <nav class="app-sidebar offcanvas-lg offcanvas-start text-bg-dark" tabindex="-1" id="appSidebar">
        <div class="offcanvas-header d-lg-none">
            <span class="offcanvas-title fw-bold">Menu</span>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar"></button>
        </div>
        <div class="offcanvas-body flex-column p-2">
        <?php
        $dashHref = '/admin/dashboard.php';
        foreach ($nav as [$href, $label, $icon]):
            $active = ($href === $dashHref)
                ? (strpos($uri, '/admin/dashboard.php') === 0)
                : nav_active($href, $uri);
        ?>
            <a href="<?= $href ?>" class="app-nav-link<?= $active ? ' active' : '' ?>"><?= $icon ?><span><?= htmlspecialchars($label) ?></span></a>
        <?php endforeach; ?>
        </div>
    </nav>
    <main class="app-content flex-fill">
