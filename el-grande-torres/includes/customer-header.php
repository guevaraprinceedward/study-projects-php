<?php
require_login();
$active_page = $active_page ?? '';

$stmt = getDB()->prepare("SELECT first_name, last_name, email FROM users WHERE user_id = ?");
$stmt->execute([current_user_id()]);
$account = $stmt->fetch();

$unread_count = 0;
try {
    $stmt = getDB()->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([current_user_id()]);
    $unread_count = (int)($stmt->fetch()['cnt'] ?? 0);
} catch (Throwable $e) {}
?>
<div class="container-lux" style="padding-top:calc(var(--nav-height) + 3.5rem); padding-bottom:6rem;">
    <div class="section-head" style="justify-content:flex-start; text-align:left; margin-bottom:3rem;">
        <div><span class="eyebrow">My Account</span><h1 class="section-title" style="font-size:2.2rem;">Welcome, <?= e($account['first_name'] ?? '') ?></h1></div>
    </div>

    <div class="account-layout">
        <aside class="account-sidebar">
            <nav>
                <a href="<?= SITE_URL ?>/customer/dashboard.php" class="<?= $active_page === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
                <a href="<?= SITE_URL ?>/customer/orders.php" class="<?= $active_page === 'orders' ? 'active' : '' ?>">Orders</a>
                <a href="<?= SITE_URL ?>/customer/wishlist.php" class="<?= $active_page === 'wishlist' ? 'active' : '' ?>">Wishlist</a>
                <a href="<?= SITE_URL ?>/cart.php">Cart</a>
                <a href="<?= SITE_URL ?>/customer/notifications.php" class="<?= $active_page === 'notifications' ? 'active' : '' ?>">
                    Notifications <?php if ($unread_count > 0): ?><span class="acct-badge"><?= $unread_count ?></span><?php endif; ?>
                </a>
                <a href="<?= SITE_URL ?>/customer/addresses.php" class="<?= $active_page === 'addresses' ? 'active' : '' ?>">Saved Addresses</a>
                <a href="<?= SITE_URL ?>/customer/profile.php" class="<?= $active_page === 'profile' ? 'active' : '' ?>">Account Settings</a>
                <a href="<?= SITE_URL ?>/auth/logout.php" style="color:#E39A9A;">Log Out</a>
            </nav>
        </aside>
        <div class="account-content">