<?php
require_once __DIR__ . '/../config/app.php';
$_SESSION['admin_id'] = null; $_SESSION['admin_name'] = null;
unset($_SESSION['admin_id'], $_SESSION['admin_name']);
header('Location: ' . SITE_URL . '/admin/login.php');
exit;