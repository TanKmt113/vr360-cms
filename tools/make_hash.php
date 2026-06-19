<?php
// Tạo hash mật khẩu: php tools/make_hash.php 'matkhau'
$pw = $argv[1] ?? '';
if ($pw === '') { fwrite(STDERR, "Cách dùng: php tools/make_hash.php '<mat-khau>'\n"); exit(1); }
echo password_hash($pw, PASSWORD_DEFAULT), "\n";
