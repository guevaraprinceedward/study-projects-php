<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$page_title = ($page_title ?? 'Dashboard') . ' · Kislap Booth Admin';
$activeNav = $activeNav ?? '';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/design-system.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body">
<script>window.SITE_URL = "<?= SITE_URL ?>"; window.CSRF_TOKEN = "<?= csrf_token() ?>";</script>

<?php $flash = get_flash(); if ($flash): ?>
<div class="flash-toast flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>

<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="nav-logo"><span class="logo-badge">📸</span><span class="logo-text">Kislap <em>Booth</em></span></div>
        <nav>
            <a href="<?= SITE_URL ?>/admin/dashboard.php" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a>
            <a href="<?= SITE_URL ?>/admin/orders.php" class="<?= $activeNav === 'orders' ? 'active' : '' ?>">🧾 Orders</a>
            <a href="<?= SITE_URL ?>/index.php" target="_blank">🏠 View Shop</a>
        </nav>
        <div class="admin-user">
            <strong><?= e($_SESSION['admin_name'] ?? 'Admin') ?></strong>
            <a href="<?= SITE_URL ?>/admin/logout.php" style="color: var(--sunshine);">Log Out →</a>
        </div>
    </aside>
    <main class="admin-main">