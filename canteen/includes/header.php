<?php
/**
 * includes/header.php — public page header.
 * Set these BEFORE including:
 *   $page = ['title' => '...', 'active' => 'home|menu', 'cart' => bool, 'cart_count' => int, 'scripts' => ['assets/js/menu.js'], 'body' => 'class']
 *   $user = getLoggedInUser($conn)   (or null)
 */
$page      = $page ?? [];
$user      = $user ?? null;
$title     = $page['title'] ?? 'COPS';
$active    = $page['active'] ?? '';
$cartCount = (int)($page['cart_count'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en" class="js-off">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> — COPS Canteen</title>
<meta name="description" content="Browse the school canteen menu and reserve your meal ahead of time with COPS.">
<script>document.documentElement.classList.remove('js-off');</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=Jost:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<?= $page['head'] ?? '' ?>
</head>
<body class="<?= e($page['body'] ?? '') ?>" data-logged-in="<?= $user ? '1' : '0' ?>">
<a class="skip-link" href="#main">Skip to main content</a>

<header class="site-header" id="siteHeader">
    <div class="container header-inner">
        <a href="index.php" class="brand" aria-label="COPS — home">
            <span class="brand-mark"><?= icon('shield', 20) ?></span>
            <span class="brand-text"><span class="brand-name">COPS</span><span class="brand-tag">Canteen reservations</span></span>
        </a>

        <nav class="nav" id="mainNav" aria-label="Main">
            <a href="index.php" <?= $active === 'home' ? 'aria-current="page"' : '' ?>>Home</a>
            <a href="menu.php" <?= $active === 'menu' ? 'aria-current="page"' : '' ?>>Menu</a>
            <a href="index.php#how">How it works</a>
            <?php if (!$user): ?><a href="log-in.php">Log in</a><?php endif; ?>
        </nav>

        <div class="header-actions">
            <?php if ($user): ?>
                <span class="who">Hello, <b><?= e($user['first_name']) ?></b></span>
                <a class="icon-btn" href="menu.php?view=orders" id="ordersBtn" data-panel-link="orders" aria-label="My reservations" title="My reservations"><?= icon('receipt', 20) ?><span class="dot" hidden></span></a>
            <?php endif; ?>
            <?php if (!empty($page['cart'])): ?>
                <a class="icon-btn" href="menu.php?view=cart" id="cartBtn" data-panel-link="cart" aria-label="Open reservation cart" title="Reservation cart"><?= icon('cart', 20) ?><span class="count" data-n="<?= $cartCount ?>" id="cartCount"><?= $cartCount ?></span></a>
            <?php endif; ?>
            <?php if ($user): ?>
                <a class="btn btn-outline btn-sm" href="log-out.php">Log out</a>
            <?php else: ?>
                <a class="btn btn-dark btn-sm" href="menu.php">Order now</a>
            <?php endif; ?>
            <button class="icon-btn nav-toggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="mainNav"><?= icon('menu', 22) ?></button>
        </div>
    </div>
</header>

<main id="main">