<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';

$page_title = $page_title ?? SITE_NAME . ' ✨ — Cute Photobooth Shop';
$body_class = $body_class ?? '';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/design-system.css">
<?php if (!empty($extra_css)): foreach ((array)$extra_css as $css): ?>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/<?= e($css) ?>">
<?php endforeach; endif; ?>
</head>
<body class="<?= e($body_class) ?>">
<script>window.SITE_URL = "<?= SITE_URL ?>"; window.CSRF_TOKEN = "<?= csrf_token() ?>"; window.IS_LOGGED_IN = <?= is_logged_in() ? 'true' : 'false' ?>;</script>

<div id="page-transition"><div class="pt-blob"></div><span class="pt-emoji">📸</span></div>

<div class="bg-decor" aria-hidden="true">
    <span class="decor-shape shape-1"></span>
    <span class="decor-shape shape-2"></span>
    <span class="decor-shape shape-3"></span>
    <span class="decor-sparkle sparkle-1">✦</span>
    <span class="decor-sparkle sparkle-2">✦</span>
    <span class="decor-sparkle sparkle-3">✧</span>
</div>

<header class="site-nav">
    <div class="nav-inner">
        <a href="<?= SITE_URL ?>/index.php" class="nav-logo">
            <span class="logo-badge">📸</span>
            <span class="logo-text">Kislap <em>Booth</em></span>
        </a>
        <nav class="nav-links">
            <a href="<?= SITE_URL ?>/index.php#sessions">Sessions</a>
            <a href="<?= SITE_URL ?>/index.php#how">How it Works</a>
            <a href="<?= SITE_URL ?>/index.php#designs">Frame Designs</a>
        </nav>
        <div class="nav-actions">
            <?php if (is_logged_in()): ?>
                <span class="nav-hello">Hi, <?= e($_SESSION['customer_name'] ?? 'friend') ?> 👋</span>
                <a href="<?= SITE_URL ?>/auth/logout.php" class="btn-pill btn-pill-ghost">Log Out</a>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/auth/login.php" class="btn-pill btn-pill-ghost">Log In</a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/index.php#sessions" class="btn-pill btn-pill-filled">Book a Session ✨</a>
        </div>
    </div>
</header>

<?php $flash = get_flash(); if ($flash): ?>
<div class="flash-toast flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>