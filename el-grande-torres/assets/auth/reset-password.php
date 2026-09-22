<?php
require_once __DIR__ . '/../config/app.php';

$token = clean_input($_GET['token'] ?? $_POST['token'] ?? '');
$errors = [];
$success = false;

if ($token === '') {
    header('Location: ' . SITE_URL . '/auth/forgot-password.php');
    exit;
}

$stmt = getDB()->prepare("SELECT user_id, reset_token_expires FROM users WHERE reset_token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch();

$token_valid = $user && strtotime($user['reset_token_expires']) > time();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    require_csrf_or_fail();
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters.';
    if ($password !== $confirm_password) $errors['confirm_password'] = 'Passwords do not match.';

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = getDB()->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE user_id = ?");
        $stmt->execute([$hash, $user['user_id']]);
        $success = true;
    }
}

$page_title = "Reset Your Password — El Grande De La Torres";
require_once __DIR__ . '/../includes/header.php';
?>
<section class="section" style="padding-top:calc(var(--nav-height) + 5rem); min-height:100vh;">
    <div class="container-lux" style="max-width:460px;">
        <div class="section-head centered reveal">
            <span class="eyebrow">Account Recovery</span>
            <h1 class="section-title">Set New Password</h1>
        </div>

        <?php if (!$token_valid): ?>
            <div class="card-lux" style="padding:2.2rem; text-align:center;">
                <p>This reset link is invalid or has expired.</p>
                <a href="<?= SITE_URL ?>/auth/forgot-password.php" class="btn-lux" style="margin-top:1.5rem;">Request a New Link</a>
            </div>
        <?php elseif ($success): ?>
            <div class="card-lux" style="padding:2.2rem; text-align:center;">
                <p>Your password has been updated successfully.</p>
                <a href="<?= SITE_URL ?>/auth/login.php" class="btn-lux btn-lux-filled" style="margin-top:1.5rem;">Sign In</a>
            </div>
        <?php else: ?>
        <form method="POST" class="form-lux reveal" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <div class="field-group <?= isset($errors['password']) ? 'field-error' : '' ?>">
                <label>New Password</label>
                <input type="password" name="password" required minlength="8">
                <?php if (isset($errors['password'])): ?><p class="error-text"><?= e($errors['password']) ?></p><?php endif; ?>
            </div>
            <div class="field-group <?= isset($errors['confirm_password']) ? 'field-error' : '' ?>">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required minlength="8">
                <?php if (isset($errors['confirm_password'])): ?><p class="error-text"><?= e($errors['confirm_password']) ?></p><?php endif; ?>
            </div>
            <button type="submit" class="btn-lux btn-lux-filled btn-lux-block">Update Password</button>
        </form>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>