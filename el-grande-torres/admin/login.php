<?php
require_once __DIR__ . '/../config/app.php';

if (is_admin_logged_in()) { header('Location: ' . SITE_URL . '/admin/dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();
    $identity = clean_input($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = getDB()->prepare("SELECT admin_id, full_name, username, password_hash FROM admins WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$identity, $identity]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        $error = 'Incorrect username/email or password.';
    } else {
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        session_regenerate_id(true);
        $stmt = getDB()->prepare("UPDATE admins SET last_login = NOW() WHERE admin_id = ?");
        $stmt->execute([$admin['admin_id']]);
        header('Location: ' . SITE_URL . '/admin/dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Sign In — El Grande De La Torres</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/design-system.css">
</head>
<body style="min-height:100vh; display:flex; align-items:center; justify-content:center;">
<div style="width:100%; max-width:420px; padding:2rem;">
    <div style="text-align:center; margin-bottom:2.5rem;">
        <span class="eyebrow" style="justify-content:center;">Administration</span>
        <h1 class="section-title" style="font-size:1.9rem;">El Grande De La Torres</h1>
        <p class="section-sub" style="margin:0 auto;">House Management Portal</p>
    </div>
    <?php if ($error): ?><div class="card-lux" style="padding:1.1rem 1.4rem; border-color:#A75A5A; margin-bottom:1.6rem;"><p class="error-text" style="margin:0;"><?= e($error) ?></p></div><?php endif; ?>
    <form method="POST" class="form-lux">
        <?= csrf_field() ?>
        <div class="field-group"><label>Username or Email</label><input type="text" name="identity" required autofocus></div>
        <div class="field-group"><label>Password</label><input type="password" name="password" required></div>
        <button type="submit" class="btn-lux btn-lux-filled btn-lux-block">Sign In</button>
    </form>
    <p style="text-align:center; margin-top:2rem; font-size:0.8rem;"><a href="<?= SITE_URL ?>/index.php" style="color:var(--text-secondary);">&larr; Back to Storefront</a></p>
</div>
</body>
</html>