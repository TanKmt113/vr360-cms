<?php
require_once dirname(__DIR__) . '/core/auth.php';
require_once dirname(__DIR__) . '/core/ratelimit.php';

send_security_headers();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Chống dò mật khẩu: tối đa 8 lần đăng nhập / 5 phút / IP
    if (!rate_limit_ok('login', 8, 300)) {
        $error = 'Quá nhiều lần thử. Vui lòng đợi vài phút rồi thử lại.';
    } else {
        $u = trim($_POST['username'] ?? '');
        $p = $_POST['password'] ?? '';
        if (auth_attempt($u, $p)) {
            header('Location: /admin/dashboard.php');
            exit;
        }
        $error = 'Sai tài khoản hoặc mật khẩu.';
    }
}
// Nếu đã đăng nhập thì vào thẳng dashboard
if (auth_user() !== null) {
    header('Location: /admin/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VR360 CMS — Đăng nhập</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body{ background:#2D3E50; min-height:100vh; display:flex; align-items:center; justify-content:center; }
    </style>
</head>
<body>
    <form class="card border-0 shadow-lg p-4" method="post" style="width:360px;">
        <h1 class="h4 text-center mb-4" style="color:#2D3E50;">VR360 CMS</h1>
        <?php if ($error): ?><div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <div class="mb-3">
            <label class="form-label">Tài khoản</label>
            <input class="form-control" name="username" autofocus required>
        </div>
        <div class="mb-3">
            <label class="form-label">Mật khẩu</label>
            <input class="form-control" name="password" type="password" required>
        </div>
        <button type="submit" class="btn w-100 text-white" style="background:#2D3E50;">Đăng nhập</button>
    </form>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
