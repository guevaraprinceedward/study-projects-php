<?php
require_once __DIR__ . '/../config/app.php';
unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['customer_email']);
set_flash('success', 'You have been logged out. See you soon! 👋');
header('Location: ' . SITE_URL . '/index.php');
exit;