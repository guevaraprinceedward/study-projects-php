<?php
require_once __DIR__ . '/el-grande/config/app.php';

if (is_logged_in()) {
    header('Location: ' . SITE_URL . '/customer/dashboard.php');
    exit;
}

$errors = [];
$old_identity = '';

// ---- Remember-me auto-login ----
if (!is_logged_in() && !empty($_COOKIE['remember_token'])) {
    $stmt = getDB()->prepare("SELECT user_id, first_name FROM users WHERE remember_token = ? LIMIT 1");
    $stmt->execute([$_COOKIE['remember_token']]);
    $u = $stmt->fetch();
    if ($u) {
        $_SESSION['user_id'] = $u['user_id'];
        $_SESSION['user_name'] = $u['first_name'];
        header('Location: ' . SITE_URL . '/customer/dashboard.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();

    $identity = clean_input($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember']);
    $old_identity = $identity;

    if ($identity === '' || $password === '') {
        $errors['general'] = 'Please enter your username/email and password.';
    } else {
        $stmt = getDB()->prepare("SELECT user_id, first_name, username, password_hash, status FROM users
                                   WHERE email = ? OR username = ? LIMIT 1");
        $stmt->execute([$identity, $identity]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors['general'] = 'Incorrect username/email or password.';
        } elseif ($user['status'] !== 'active') {
            $errors['general'] = 'This account is currently suspended. Please contact support.';
        } else {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['first_name'];
            session_regenerate_id(true);

            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $stmt = getDB()->prepare("UPDATE users SET remember_token = ? WHERE user_id = ?");
                $stmt->execute([$token, $user['user_id']]);
                setcookie('remember_token', $token, [
                    'expires' => time() + (86400 * 30),
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }

            set_flash('success', 'Welcome back, ' . $user['first_name'] . '.');
            $redirect = $_SESSION['redirect_after_login'] ?? (SITE_URL . '/customer/dashboard.php');
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        }
    }
}

$page_title = "Sign In — El Grande De La Torres";
require_once __DIR__ . '/../includes/header.php';
?>
<section class="section" style="padding-top:calc(var(--nav-height) + 5rem); min-height:100vh;">
    <div class="container-lux" style="max-width:460px;">
        <div class="section-head reveal" style="margin-bottom:3rem;">
            <span class="eyebrow">Welcome Back</span>
            <h1 class="section-title">Sign In</h1>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="card-lux reveal" style="padding:1.2rem 1.5rem; border-color:#A75A5A; margin-bottom:2rem;">
                <p class="error-text" style="margin:0;"><?= e($errors['general']) ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" class="form-lux reveal" novalidate>
            <?= csrf_field() ?>
            <div class="field-group">
                <label for="identity">Username or Email</label>
                <input type="text" id="identity" name="identity" value="<?= e($old_identity) ?>" required autofocus>
            </div>
            <div class="field-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
                <label style="display:flex; align-items:center; gap:0.6rem; font-size:0.82rem; color:var(--text-secondary); text-transform:none; letter-spacing:0;">
                    <input type="checkbox" name="remember" style="width:auto; accent-color:var(--gold);"> Remember me
                </label>
                <a href="<?= SITE_URL ?>/auth/forgot-password.php" style="font-size:0.82rem; color:var(--gold);">Forgot password?</a>
            </div>
            <button type="submit" class="btn-lux btn-lux-filled btn-lux-block">Sign In</button>
            <p style="text-align:center; margin-top:2rem; font-size:0.85rem; color:var(--text-secondary);">
                New here? <a href="<?= SITE_URL ?>/auth/register.php" style="color:var(--gold);">Create an account</a>
            </p>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/el-grande/includes/footer.php'; ?>