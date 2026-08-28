<?php
$extra_css = ['auth.css'];
require_once __DIR__ . '/../includes/header.php';

if (is_logged_in()) { header('Location: ' . SITE_URL . '/index.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    } else {
        $firstName = clean_input($_POST['first_name'] ?? '');
        $lastName = clean_input($_POST['last_name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $phone = clean_input($_POST['phone_number'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($firstName === '' || $lastName === '') $errors[] = 'Please enter your full name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

        if (!$errors) {
            $stmt = getDB()->prepare("SELECT customer_id FROM customers WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with that email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = getDB()->prepare("INSERT INTO customers (first_name, last_name, email, phone_number, password_hash) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$firstName, $lastName, $email, $phone, $hash]);
                $_SESSION['customer_id'] = (int)getDB()->lastInsertId();
                $_SESSION['customer_name'] = $firstName;
                $_SESSION['customer_email'] = $email;
                set_flash('success', "Welcome to Kislap Booth, $firstName! ✨");
                header('Location: ' . SITE_URL . '/index.php');
                exit;
            }
        }
    }
}
?>

<section class="auth-page">
    <div class="card-cute auth-card reveal">
        <div class="auth-icon">🎀</div>
        <h2>Create Your Account</h2>
        <p class="auth-sub">Save your info for faster checkout next time!</p>

        <?php if ($errors): ?>
        <div class="flash-toast flash-error" style="position:static; transform:none; width:100%; margin-bottom:1.2rem; text-align:center; animation:none;">
            <?= e(implode(' ', $errors)) ?>
        </div>
        <?php endif; ?>

        <form class="form-cute" method="POST">
            <?= csrf_field() ?>
            <div style="display:flex; gap:1rem;">
                <div class="field-group" style="flex:1;"><label for="first_name">First Name</label><input type="text" id="first_name" name="first_name" required></div>
                <div class="field-group" style="flex:1;"><label for="last_name">Last Name</label><input type="text" id="last_name" name="last_name" required></div>
            </div>
            <div class="field-group"><label for="email">Email</label><input type="email" id="email" name="email" required></div>
            <div class="field-group"><label for="phone_number">Phone Number</label><input type="tel" id="phone_number" name="phone_number" placeholder="09XX XXX XXXX"></div>
            <div class="field-group"><label for="password">Password</label><input type="password" id="password" name="password" minlength="6" required></div>
            <button type="submit" class="btn-pill btn-pill-filled btn-pill-block btn-pill-lg">Create Account ✨</button>
        </form>
        <p class="auth-switch">Already have an account? <a href="<?= SITE_URL ?>/auth/login.php">Log In</a></p>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>