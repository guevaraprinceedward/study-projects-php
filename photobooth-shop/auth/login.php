<?php
$extra_css = ['auth.css'];
require_once __DIR__ . '/../includes/header.php';

if (is_logged_in()) { header('Location: ' . SITE_URL . '/index.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    } else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        $stmt = getDB()->prepare("SELECT * FROM customers WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $customer = $stmt->fetch();

        if (!$customer || !password_verify($password, $customer['password_hash'])) {
            $errors[] = 'Incorrect email or password.';
        } else {
            $_SESSION['customer_id'] = (int)$customer['customer_id'];
            $_SESSION['customer_name'] = $customer['first_name'];
            $_SESSION['customer_email'] = $customer['email'];
            set_flash('success', 'Welcome back, ' . $customer['first_name'] . '! ✨');
            header('Location: ' . SITE_URL . '/index.php');
            exit;
        }
    }
}
?>

<section class="auth-page">
    <div class="card-cute auth-card reveal">
        <div class="auth-icon">📸</div>
        <h2>Welcome Back!</h2>
        <p class="auth-sub">Log in to book your next cute session.</p>

        <?php if ($errors): ?>
        <div class="flash-toast flash-error" style="position:static; transform:none; width:100%; margin-bottom:1.2rem; text-align:center; animation:none;">
            <?= e(implode(' ', $errors)) ?>
        </div>
        <?php endif; ?>

        <form class="form-cute" method="POST">
            <?= csrf_field() ?>
            <div class="field-group"><label for="email">Email</label><input type="email" id="email" name="email" required autofocus></div>
            <div class="field-group"><label for="password">Password</label><input type="password" id="password" name="password" required></div>
            <button type="submit" class="btn-pill btn-pill-filled btn-pill-block btn-pill-lg">Log In ✨</button>
        </form>
        <p class="auth-switch">New here? <a href="<?= SITE_URL ?>/auth/register.php">Create an Account</a></p>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>