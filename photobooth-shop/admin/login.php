<?php
require_once __DIR__ . '/../config/app.php';

if (is_admin_logged_in()) { header('Location: ' . SITE_URL . '/admin/dashboard.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    } else {
        $username = clean_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = getDB()->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            $errors[] = 'Incorrect username or password.';
        } else {
            $_SESSION['admin_id'] = (int)$admin['admin_id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $upd = getDB()->prepare("UPDATE admins SET last_login = NOW() WHERE admin_id = ?");
            $upd->execute([$admin['admin_id']]);
            header('Location: ' . SITE_URL . '/admin/dashboard.php');
            exit;
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login · Kislap Booth ✨</title>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/design-system.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/auth.css">
</head>
<body>
<div class="bg-decor" aria-hidden="true">
    <span class="decor-shape shape-1"></span>
    <span class="decor-shape shape-2"></span>
    <span class="decor-shape shape-3"></span>
</div>
<section class="auth-page" style="min-height:100vh; align-items:center;">
    <div class="card-cute auth-card">
        <div class="auth-icon">🔐</div>
        <h2>Admin Login</h2>
        <p class="auth-sub">Kislap Booth staff dashboard</p>

        <?php if ($errors): ?>
        <div class="flash-toast flash-error" style="position:static; transform:none; width:100%; margin-bottom:1.2rem; text-align:center; animation:none;">
            <?= e(implode(' ', $errors)) ?>
        </div>
        <?php endif; ?>

        <form class="form-cute" method="POST">
            <?= csrf_field() ?>
            <div class="field-group"><label for="username">Username</label><input type="text" id="username" name="username" required autofocus></div>
            <div class="field-group"><label for="password">Password</label><input type="password" id="password" name="password" required></div>
            <button type="submit" class="btn-pill btn-pill-filled btn-pill-block btn-pill-lg">Log In</button>
        </form>
        <p class="auth-switch"><a href="<?= SITE_URL ?>/index.php">← Back to Shop</a></p>
    </div>
</section>
</body>
</html>