<?php
/**
 * Global header: <head>, loading screen, nav, sidebar
 */
require_once __DIR__ . '/../config/app.php';

$page_title = $page_title ?? SITE_NAME . ' — Luxury Fashion House';
$meta_description = $meta_description ?? 'El Grande De La Torres — a prestigious fashion house for clothing and accessories.';

$cart_count = 0;
if (is_logged_in()) {
    try {
        $stmt = getDB()->prepare("SELECT COALESCE(SUM(ci.quantity),0) AS cnt FROM cart_items ci
                                   JOIN carts c ON c.cart_id = ci.cart_id WHERE c.user_id = ?");
        $stmt->execute([current_user_id()]);
        $cart_count = (int)($stmt->fetch()['cnt'] ?? 0);
    } catch (Throwable $e) { $cart_count = 0; }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title) ?></title>
<meta name="description" content="<?= e($meta_description) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/design-system.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/home.css">
<?php if (!empty($extra_css)): ?><link rel="stylesheet" href="<?= e($extra_css) ?>"><?php endif; ?>
</head>
<body>

<div id="loading-screen">
    <div class="mono">El Grande De La Torres</div>
    <div class="loading-bar"></div>
</div>

<div class="scroll-progress"></div>

<header class="navbar-lux" id="mainNav">
    <div class="nav-inner">
        <ul class="nav-links">
            <li><a href="<?= SITE_URL ?>/clothing.php">Clothing</a></li>
            <li><a href="<?= SITE_URL ?>/accessories.php">Accessories</a></li>
            <li><a href="<?= SITE_URL ?>/new-arrivals.php">New Arrivals</a></li>
            <li><a href="<?= SITE_URL ?>/about.php">Maison</a></li>
        </ul>

        <a href="<?= SITE_URL ?>/index.php" class="nav-logo">El Grande De La Torres</a>

        <div class="nav-actions">
            <button class="nav-icon-btn" aria-label="Search" data-sidebar-open>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </button>
            <a class="nav-icon-btn" aria-label="Wishlist" href="<?= SITE_URL ?>/customer/wishlist.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.6z"/></svg>
            </a>
            <a class="nav-icon-btn" aria-label="Account" href="<?= is_logged_in() ? SITE_URL.'/customer/dashboard.php' : SITE_URL.'/auth/login.php' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
            </a>
            <a class="nav-icon-btn" aria-label="Cart" href="<?= SITE_URL ?>/cart.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6L4 3H2"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                <?php if ($cart_count > 0): ?><span class="cart-count"><?= $cart_count ?></span><?php endif; ?>
            </a>
            <button class="nav-toggle" aria-label="Menu" data-sidebar-open>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
        </div>
    </div>
</header>

<div class="sidebar-overlay" data-sidebar-close></div>
<aside class="sidebar-lux" aria-label="Site menu">
    <button class="sidebar-close" aria-label="Close menu" data-sidebar-close>
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <nav>
        <ul>
            <li><a href="<?= SITE_URL ?>/clothing.php">Clothing</a></li>
            <li><a href="<?= SITE_URL ?>/accessories.php">Accessories</a></li>
            <li><a href="<?= SITE_URL ?>/new-arrivals.php">New Arrivals</a></li>
            <li><a href="<?= SITE_URL ?>/best-sellers.php">Best Sellers</a></li>
            <li><a href="<?= SITE_URL ?>/about.php">The Maison</a></li>
            <li><a href="<?= SITE_URL ?>/contact.php">Contact</a></li>
        </ul>
    </nav>
    <div class="sidebar-sub">
        <?php if (is_logged_in()): ?>
            <a href="<?= SITE_URL ?>/customer/dashboard.php" class="btn-lux btn-lux-block">My Account</a>
        <?php else: ?>
            <a href="<?= SITE_URL ?>/auth/login.php" class="btn-lux btn-lux-block">Sign In</a>
            <a href="<?= SITE_URL ?>/auth/register.php" class="btn-lux btn-lux-ghost btn-lux-block">Create Account</a>
        <?php endif; ?>
        <div class="sidebar-meta">Manila · Est. 2026</div>
    </div>
</aside>
<script>window.CSRF_TOKEN = "<?= csrf_token() ?>"; window.IS_LOGGED_IN = <?= is_logged_in() ? 'true' : 'false' ?>;</script>