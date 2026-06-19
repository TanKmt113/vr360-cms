<?php
require_once dirname(__DIR__) . '/core/auth.php';
auth_logout();
header('Location: /admin/index.php');
exit;
