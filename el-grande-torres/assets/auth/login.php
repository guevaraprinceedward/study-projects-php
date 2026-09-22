<?php
require_once __DIR__ . '/../config/app.php';

if (is_logged_in()) {
    header('Location: ' . SITE_URL . '/customer/dashboard.php');
    exit;
}

$errors = [];
$old_identity = '';

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
        $stmt = getDB()->prepare("SELECT user_id, first_name, username, password_hash, status FROM users WHERE email = ? OR username = ? LIMIT 1");
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
                    'expires' => time() + (86400 * 30), 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
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
<style>
.auth-split { min-height: 100vh; display: grid; grid-template-columns: 1fr 1fr; }
.auth-visual { position: relative; overflow: hidden; background: #000; order: 2; }
.auth-visual img { width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0; opacity: 0.75; }
.auth-visual::after { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(10,10,10,0.15) 0%, rgba(10,10,10,0.55) 65%, rgba(10,10,10,0.96) 100%); }
.auth-visual-content { position: relative; z-index: 1; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; padding: 4.5rem; }
.auth-visual-content h2 { font-size: clamp(2rem, 3vw, 2.9rem); margin: 0.8rem 0 1rem; line-height: 1.15; }
.auth-visual-content h2 em { color: var(--gold); font-style: italic; }
.auth-visual-content p { font-size: 0.88rem; color: var(--text-secondary); line-height: 1.8; max-width: 380px; }
.auth-quote { margin-top: 2.2rem; padding-top: 1.8rem; border-top: 1px solid rgba(200,169,106,0.2); }
.auth-quote blockquote { font-family: var(--font-display); font-style: italic; font-size: 1.15rem; color: var(--text-primary); line-height: 1.5; max-width: 380px; }
.auth-quote cite { display: block; margin-top: 0.7rem; font-size: 0.7rem; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-secondary); font-style: normal; }
.auth-side { order: 1; display: flex; align-items: center; justify-content: center; padding: 5rem 3.5rem; }
.auth-side-inner { width: 100%; max-width: 420px; }
.auth-side h1 { font-size: 2.1rem; margin-bottom: 0.5rem; }
.auth-side .sub { font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 2.2rem; }
.auth-row-between { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; font-size: 0.82rem; }
.auth-switch { text-align: center; margin-top: 1.8rem; font-size: 0.85rem; color: var(--text-secondary); }
.auth-switch a { color: var(--gold); }
@media (max-width: 900px) { .auth-split { grid-template-columns: 1fr; } .auth-visual { order: 1; min-height: 240px; } .auth-side { order: 2; padding: 3rem 1.8rem; } }
</style>

<div class="auth-split">
    <div class="auth-side">
        <div class="auth-side-inner">
            <h1>Welcome Back</h1>
            <p class="sub">Sign in to continue browsing the house.</p>

            <?php if (!empty($errors['general'])): ?>
                <div class="card-lux" style="padding:1.2rem 1.5rem; border-color:#A75A5A; margin-bottom:2rem;">
                    <p class="error-text" style="margin:0;"><?= e($errors['general']) ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" class="form-lux" novalidate>
                <?= csrf_field() ?>
                <div class="field-group">
                    <label>Username or Email</label>
                    <input type="text" name="identity" value="<?= e($old_identity) ?>" required autofocus>
                </div>
                <div class="field-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="auth-row-between">
                    <label style="display:flex; align-items:center; gap:0.6rem; color:var(--text-secondary); text-transform:none;">
                        <input type="checkbox" name="remember" style="width:auto; accent-color:var(--gold);"> Remember me
                    </label>
                    <a href="<?= SITE_URL ?>/auth/forgot-password.php" style="color:var(--gold);">Forgot password?</a>
                </div>
                <button type="submit" class="btn-lux btn-lux-filled btn-lux-block">Sign In</button>
                <p class="auth-switch">New here? <a href="<?= SITE_URL ?>/auth/register.php">Create an account</a></p>
            </form>
        </div>
    </div>

    <div class="auth-visual">
        <img src="https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?q=80&w=1200&auto=format&fit=crop" alt="El Grande De La Torres">
        <div class="auth-visual-content">
            <span class="eyebrow">El Grande De La Torres</span>
            <h2>The House<br><em>Remembers You</em></h2>
            <p>Your cart, wishlist, and order history — carried forward the moment you sign back in.</p>
            <div class="auth-quote">
                <blockquote>Excellence is not an act, but a habit — worn quietly, every day.</blockquote>
                The House Philosophy
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>