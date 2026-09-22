<?php
require_admin();
$admin_active = $admin_active ?? '';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title ?? 'Admin — El Grande De La Torres') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/design-system.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
<?php if (!empty($extra_css)): ?><link rel="stylesheet" href="<?= e($extra_css) ?>"><?php endif; ?>
</head>
<body class="admin-body">
<script>window.CSRF_TOKEN = "<?= csrf_token() ?>"; window.SITE_URL = "<?= SITE_URL ?>";</script>

<aside class="admin-sidebar">
    <div class="admin-logo">El Grande <em>Admin</em></div>
    <nav class="admin-nav">
        <a href="<?= SITE_URL ?>/admin/dashboard.php" class="<?= $admin_active === 'dashboard' ? 'active' : '' ?>">Analytics</a>
        <div class="nav-section">Catalog</div>
        <a href="<?= SITE_URL ?>/admin/products.php" class="<?= $admin_active === 'products' ? 'active' : '' ?>">Products</a>
        <a href="<?= SITE_URL ?>/admin/categories.php" class="<?= $admin_active === 'categories' ? 'active' : '' ?>">Categories</a>
        <a href="<?= SITE_URL ?>/admin/inventory.php" class="<?= $admin_active === 'inventory' ? 'active' : '' ?>">Inventory</a>
        <div class="nav-section">Sales</div>
        <a href="<?= SITE_URL ?>/admin/orders.php" class="<?= $admin_active === 'orders' ? 'active' : '' ?>">Orders</a>
        <a href="<?= SITE_URL ?>/admin/customers.php" class="<?= $admin_active === 'customers' ? 'active' : '' ?>">Customers</a>
        <a href="<?= SITE_URL ?>/admin/reports.php" class="<?= $admin_active === 'reports' ? 'active' : '' ?>">Reports</a>
        <div class="nav-section">Account</div>
        <a href="<?= SITE_URL ?>/admin/logout.php" style="color:#E39A9A;">Log Out</a>
    </nav>
</aside>

<main class="admin-main">