<?php
require_once __DIR__ . '/../config/app.php';

if (is_logged_in()) {
    header('Location: ' . SITE_URL . '/customer/dashboard.php');
    exit;
}

$errors = [];
$old = ['first_name' => '', 'last_name' => '', 'username' => '', 'email' => '', 'phone_number' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();

    $old['first_name']   = clean_input($_POST['first_name'] ?? '');
    $old['last_name']    = clean_input($_POST['last_name'] ?? '');
    $old['username']     = clean_input($_POST['username'] ?? '');
    $old['email']        = clean_input($_POST['email'] ?? '');
    $old['phone_number'] = clean_input($_POST['phone_number'] ?? '');
    $password             = $_POST['password'] ?? '';
    $confirm_password     = $_POST['confirm_password'] ?? '';

    if ($old['first_name'] === '') $errors['first_name'] = 'First name is required.';
    if ($old['last_name'] === '') $errors['last_name'] = 'Last name is required.';

    if ($old['username'] === '') {
        $errors['username'] = 'Username is required.';
    } elseif (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $old['username'])) {
        $errors['username'] = 'Username must be 3-50 characters (letters, numbers, . or _ only).';
    }

    if ($old['email'] === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if ($old['phone_number'] === '') {
        $errors['phone_number'] = 'Phone number is required.';
    } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $old['phone_number'])) {
        $errors['phone_number'] = 'Please enter a valid phone number.';
    }

    if (strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters.';
    if ($password !== $confirm_password) $errors['confirm_password'] = 'Passwords do not match.';

    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT user_id, email, username FROM users WHERE email = ? OR username = ? LIMIT 1");
        $stmt->execute([$old['email'], $old['username']]);
        $existing = $stmt->fetch();
        if ($existing) {
            if (strcasecmp($existing['email'], $old['email']) === 0) $errors['email'] = 'An account with this email already exists.';
            if (strcasecmp($existing['username'], $old['username']) === 0) $errors['username'] = 'This username is already taken.';
        }
    }

    if (empty($errors)) {
        try {
            $db = getDB();
            $db->beginTransaction();

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (first_name, last_name, username, email, phone_number, password_hash)
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$old['first_name'], $old['last_name'], $old['username'], $old['email'], $old['phone_number'], $hash]);
            $user_id = (int)$db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO carts (user_id) VALUES (?)");
            $stmt->execute([$user_id]);

            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'system')");
            $stmt->execute([$user_id, 'Welcome to El Grande De La Torres', 'Your account has been created. Explore the house at your leisure.']);

            $db->commit();

            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $old['first_name'];
            session_regenerate_id(true);

            set_flash('success', 'Welcome to El Grande De La Torres, ' . $old['first_name'] . '.');
            header('Location: ' . SITE_URL . '/customer/dashboard.php');
            exit;
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('Registration error: ' . $e->getMessage());
            $errors['general'] = 'We could not create your account right now. Please try again.';
        }
    }
}

$page_title = "Create Account — El Grande De La Torres";
require_once __DIR__ . '/../includes/header.php';
?>
<style>
.auth-split { min-height: 100vh; display: grid; grid-template-columns: 1fr 1fr; }
.auth-visual { position: relative; overflow: hidden; background: #000; }
.auth-visual img { width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0; opacity: 0.75; }
.auth-visual::after { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(10,10,10,0.15) 0%, rgba(10,10,10,0.55) 65%, rgba(10,10,10,0.96) 100%); }
.auth-visual-content { position: relative; z-index: 1; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; padding: 4.5rem; }
.auth-visual-content h2 { font-size: clamp(2rem, 3vw, 2.9rem); margin: 0.8rem 0 1rem; line-height: 1.15; }
.auth-visual-content h2 em { color: var(--gold); font-style: italic; }
.auth-visual-content p { font-size: 0.88rem; color: var(--text-secondary); line-height: 1.8; max-width: 380px; }
.auth-perks { display: flex; flex-direction: column; gap: 0.9rem; margin-top: 1.8rem; }
.auth-perks div { display: flex; align-items: center; gap: 0.7rem; font-size: 0.85rem; }
.auth-perks svg { color: var(--gold); flex-shrink: 0; }
.auth-side { display: flex; align-items: center; justify-content: center; padding: 5rem 3.5rem; }
.auth-side-inner { width: 100%; max-width: 460px; }
.auth-side h1 { font-size: 2.1rem; margin-bottom: 0.5rem; }
.auth-side .sub { font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 2.2rem; }
.auth-switch { text-align: center; margin-top: 1.8rem; font-size: 0.85rem; color: var(--text-secondary); }
.auth-switch a { color: var(--gold); }
@media (max-width: 900px) { .auth-split { grid-template-columns: 1fr; } .auth-visual { min-height: 260px; } .auth-side { padding: 3rem 1.8rem; } }
</style>

<div class="auth-split">
    <div class="auth-visual">
        <img src="https://images.unsplash.com/photo-1490114538077-0a7f8cb49891?q=80&w=1200&auto=format&fit=crop" alt="El Grande De La Torres">
        <div class="auth-visual-content">
            <span class="eyebrow">El Grande De La Torres</span>
            <h2>A House of<br><em>Quiet Distinction</em></h2>
            <p>Create your account to unlock the full house — early collection access, saved wishlists, and a refined checkout built around you.</p>
            <div class="auth-perks">
                <div><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M20 6L9 17l-5-5"/></svg> Early access to limited editions</div>
                <div><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M20 6L9 17l-5-5"/></svg> Saved wishlists across devices</div>
                <div><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M20 6L9 17l-5-5"/></svg> Order history &amp; digital receipts</div>
            </div>
        </div>
    </div>

    <div class="auth-side">
        <div class="auth-side-inner">
            <h1>Create Your Account</h1>
            <p class="sub">Join the house for early access and a refined shopping experience.</p>

            <?php if (!empty($errors['general'])): ?>
                <div class="card-lux" style="padding:1.2rem 1.5rem; border-color:#A75A5A; margin-bottom:2rem;">
                    <p class="error-text" style="margin:0;"><?= e($errors['general']) ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" class="form-lux" novalidate>
                <?= csrf_field() ?>
                <div class="row">
                    <div class="col-sm-6 field-group <?= isset($errors['first_name']) ? 'field-error' : '' ?>">
                        <label>First Name</label>
                        <input type="text" name="first_name" value="<?= e($old['first_name']) ?>" required>
                        <?php if (isset($errors['first_name'])): ?><p class="error-text"><?= e($errors['first_name']) ?></p><?php endif; ?>
                    </div>
                    <div class="col-sm-6 field-group <?= isset($errors['last_name']) ? 'field-error' : '' ?>">
                        <label>Last Name</label>
                        <input type="text" name="last_name" value="<?= e($old['last_name']) ?>" required>
                        <?php if (isset($errors['last_name'])): ?><p class="error-text"><?= e($errors['last_name']) ?></p><?php endif; ?>
                    </div>
                </div>

                <div class="field-group <?= isset($errors['username']) ? 'field-error' : '' ?>">
                    <label>Username</label>
                    <input type="text" name="username" value="<?= e($old['username']) ?>" required>
                    <?php if (isset($errors['username'])): ?><p class="error-text"><?= e($errors['username']) ?></p><?php endif; ?>
                </div>

                <div class="field-group <?= isset($errors['email']) ? 'field-error' : '' ?>">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= e($old['email']) ?>" required>
                    <?php if (isset($errors['email'])): ?><p class="error-text"><?= e($errors['email']) ?></p><?php endif; ?>
                </div>

                <div class="field-group <?= isset($errors['phone_number']) ? 'field-error' : '' ?>">
                    <label>Phone Number</label>
                    <input type="tel" name="phone_number" value="<?= e($old['phone_number']) ?>" required>
                    <?php if (isset($errors['phone_number'])): ?><p class="error-text"><?= e($errors['phone_number']) ?></p><?php endif; ?>
                </div>

                <div class="row">
                    <div class="col-sm-6 field-group <?= isset($errors['password']) ? 'field-error' : '' ?>">
                        <label>Password</label>
                        <input type="password" name="password" required minlength="8">
                        <?php if (isset($errors['password'])): ?><p class="error-text"><?= e($errors['password']) ?></p><?php endif; ?>
                    </div>
                    <div class="col-sm-6 field-group <?= isset($errors['confirm_password']) ? 'field-error' : '' ?>">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" required minlength="8">
                        <?php if (isset($errors['confirm_password'])): ?><p class="error-text"><?= e($errors['confirm_password']) ?></p><?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="btn-lux btn-lux-filled btn-lux-block" style="margin-top:0.5rem;">Create Account</button>
                <p class="auth-switch">Already have an account? <a href="<?= SITE_URL ?>/auth/login.php">Sign in</a></p>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>