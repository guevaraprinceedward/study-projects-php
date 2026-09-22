<?php
require_once __DIR__ . '/../config/app.php';

$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();
    $email = clean_input($_POST['email'] ?? '');

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = getDB()->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600);
            $stmt = getDB()->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE user_id = ?");
            $stmt->execute([$token, $expires, $user['user_id']]);

            // NOTE: Wire up PHPMailer/SMTP here to actually send the email.
            // Reset link: SITE_URL . '/auth/reset-password.php?token=' . $token
            error_log("Password reset link for user {$user['user_id']}: " . SITE_URL . "/auth/reset-password.php?token=$token");
        }
    }
    $sent = true; // Always show the same confirmation — prevents account enumeration
}

$page_title = "Reset Password — El Grande De La Torres";
require_once __DIR__ . '/../includes/header.php';
?>
<section class="section" style="padding-top:calc(var(--nav-height) + 5rem); min-height:100vh;">
    <div class="container-lux" style="max-width:460px;">
        <div class="section-head centered reveal">
            <span class="eyebrow">Account Recovery</span>
            <h1 class="section-title">Forgot Password</h1>
            <p class="section-sub" style="margin:0 auto;">Enter your email and we'll send a link to reset your password.</p>
        </div>

        <?php if ($sent): ?>
            <div class="card-lux reveal" style="padding:2.2rem; text-align:center;">
                <p>If an account exists with that email, a reset link has been sent.</p>
                <a href="<?= SITE_URL ?>/auth/login.php" class="btn-lux" style="margin-top:1.5rem;">Back to Sign In</a>
            </div>
        <?php else: ?>
        <form method="POST" class="form-lux reveal" novalidate>
            <?= csrf_field() ?>
            <div class="field-group">
                <label>Email Address</label>
                <input type="email" name="email" required autofocus>
            </div>
            <button type="submit" class="btn-lux btn-lux-filled btn-lux-block">Send Reset Link</button>
        </form>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
