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

    // ---- Validation ----
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

    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // ---- Duplicate detection ----
    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT user_id, email, username FROM users WHERE email = ? OR username = ? LIMIT 1");
        $stmt->execute([$old['email'], $old['username']]);
        $existing = $stmt->fetch();
        if ($existing) {
            if (strcasecmp($existing['email'], $old['email']) === 0) {
                $errors['email'] = 'An account with this email already exists.';
            }
            if (strcasecmp($existing['username'], $old['username']) === 0) {
                $errors['username'] = 'This username is already taken.';
            }
        }
    }

    // ---- Create account ----
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
            $stmt->execute([$user_id, 'Welcome to El Grande De La Torres', 'Your account has been created. Explore the collections at your leisure.']);

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
<section class="section" style="padding-top:calc(var(--nav-height) + 4rem); min-height:100vh;">
    <div class="container-lux" style="max-width:560px;">
        <div class="section-head reveal" style="margin-bottom:3rem;">
            <span class="eyebrow">Become a Client</span>
            <h1 class="section-title">Create Your Account</h1>
            <p class="section-sub" style="margin:0 auto;">Join the house for early access, saved wishlists, and a refined checkout experience.</p>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="card-lux reveal" style="padding:1.2rem 1.5rem; border-color:#A75A5A; margin-bottom:2rem;">
                <p class="error-text" style="margin:0;"><?= e($errors['general']) ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" class="form-lux reveal" novalidate>
            <?= csrf_field() ?>
            <div class="row">
                <div class="col-sm-6 field-group <?= isset($errors['first_name']) ? 'field-error' : '' ?>">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" value="<?= e($old['first_name']) ?>" required>
                    <?php if (isset($errors['first_name'])): ?><p class="error-text"><?= e($errors['first_name']) ?></p><?php endif; ?>
                </div>
                <div class="col-sm-6 field-group <?= isset($errors['last_name']) ? 'field-error' : '' ?>">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" value="<?= e($old['last_name']) ?>" required>
                    <?php if (isset($errors['last_name'])): ?><p class="error-text"><?= e($errors['last_name']) ?></p><?php endif; ?>
                </div>
            </div>

            <div class="field-group <?= isset($errors['username']) ? 'field-error' : '' ?>">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= e($old['username']) ?>" required>
                <?php if (isset($errors['username'])): ?><p class="error-text"><?= e($errors['username']) ?></p><?php endif; ?>
            </div>

            <div class="field-group <?= isset($errors['email']) ? 'field-error' : '' ?>">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>
                <?php if (isset($errors['email'])): ?><p class="error-text"><?= e($errors['email']) ?></p><?php endif; ?>
            </div>

            <div class="field-group <?= isset($errors['phone_number']) ? 'field-error' : '' ?>">
                <label for="phone_number">Phone Number</label>
                <input type="tel" id="phone_number" name="phone_number" value="<?= e($old['phone_number']) ?>" required>
                <?php if (isset($errors['phone_number'])): ?><p class="error-text"><?= e($errors['phone_number']) ?></p><?php endif; ?>
            </div>

            <div class="row">
                <div class="col-sm-6 field-group <?= isset($errors['password']) ? 'field-error' : '' ?>">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="8">
                    <?php if (isset($errors['password'])): ?><p class="error-text"><?= e($errors['password']) ?></p><?php endif; ?>
                </div>
                <div class="col-sm-6 field-group <?= isset($errors['confirm_password']) ? 'field-error' : '' ?>">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                    <?php if (isset($errors['confirm_password'])): ?><p class="error-text"><?= e($errors['confirm_password']) ?></p><?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn-lux btn-lux-filled btn-lux-block" style="margin-top:1rem;">Create Account</button>

            <p style="text-align:center; margin-top:2rem; font-size:0.85rem; color:var(--text-secondary);">
                Already have an account? <a href="<?= SITE_URL ?>/auth/login.php" style="color:var(--gold);">Sign in</a>
            </p>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>