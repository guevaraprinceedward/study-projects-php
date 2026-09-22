<?php
require_once __DIR__ . '/../config/app.php';
require_login();

$user_id = current_user_id();
$db = getDB();

$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$errors = []; $success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();
    $form_type = $_POST['form_type'] ?? '';

    if ($form_type === 'profile_info') {
        $first_name = clean_input($_POST['first_name'] ?? '');
        $last_name = clean_input($_POST['last_name'] ?? '');
        $phone_number = clean_input($_POST['phone_number'] ?? '');

        if ($first_name === '') $errors['first_name'] = 'First name is required.';
        if ($last_name === '') $errors['last_name'] = 'Last name is required.';
        if ($phone_number === '') $errors['phone_number'] = 'Phone number is required.';

        if (empty($errors)) {
            $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, phone_number = ? WHERE user_id = ?");
            $stmt->execute([$first_name, $last_name, $phone_number, $user_id]);
            $success_msg = 'Your profile has been updated.';
            $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        }
    }

    if ($form_type === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_new_password'] ?? '';

        if (!password_verify($current_password, $user['password_hash'])) $errors['current_password'] = 'Current password is incorrect.';
        if (strlen($new_password) < 8) $errors['new_password'] = 'New password must be at least 8 characters.';
        if ($new_password !== $confirm_password) $errors['confirm_new_password'] = 'Passwords do not match.';

        if (empty($errors)) {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt->execute([$hash, $user_id]);
            $success_msg = 'Your password has been changed.';
        }
    }
}

$page_title = "Account Settings — El Grande De La Torres";
$extra_css = SITE_URL . '/assets/css/account.css';
require_once __DIR__ . '/../includes/header.php';
$active_page = 'profile';
require_once __DIR__ . '/../includes/customer-header.php';
?>

<?php if ($success_msg): ?>
<div class="card-lux" style="padding:1.2rem 1.5rem; border-color:#7FA876; margin-bottom:2rem;"><p style="margin:0; color:#B4D4AC;"><?= e($success_msg) ?></p></div>
<?php endif; ?>

<div class="section-head" style="justify-content:flex-start; text-align:left; margin-bottom:1.5rem;"><h2 class="section-title" style="font-size:1.5rem;">Personal Information</h2></div>

<form method="POST" class="form-lux" style="max-width:520px; margin-bottom:4rem;">
    <?= csrf_field() ?>
    <input type="hidden" name="form_type" value="profile_info">
    <div class="row">
        <div class="col-sm-6 field-group <?= isset($errors['first_name']) ? 'field-error' : '' ?>">
            <label>First Name</label><input type="text" name="first_name" value="<?= e($user['first_name']) ?>" required>
            <?php if (isset($errors['first_name'])): ?><p class="error-text"><?= e($errors['first_name']) ?></p><?php endif; ?>
        </div>
        <div class="col-sm-6 field-group <?= isset($errors['last_name']) ? 'field-error' : '' ?>">
            <label>Last Name</label><input type="text" name="last_name" value="<?= e($user['last_name']) ?>" required>
            <?php if (isset($errors['last_name'])): ?><p class="error-text"><?= e($errors['last_name']) ?></p><?php endif; ?>
        </div>
    </div>
    <div class="field-group"><label>Username</label><input type="text" value="<?= e($user['username']) ?>" disabled style="opacity:0.5;"></div>
    <div class="field-group"><label>Email Address</label><input type="email" value="<?= e($user['email']) ?>" disabled style="opacity:0.5;"></div>
    <div class="field-group <?= isset($errors['phone_number']) ? 'field-error' : '' ?>">
        <label>Phone Number</label><input type="tel" name="phone_number" value="<?= e($user['phone_number']) ?>" required>
        <?php if (isset($errors['phone_number'])): ?><p class="error-text"><?= e($errors['phone_number']) ?></p><?php endif; ?>
    </div>
    <button type="submit" class="btn-lux btn-lux-filled">Save Changes</button>
</form>

<div class="section-head" style="justify-content:flex-start; text-align:left; margin-bottom:1.5rem;"><h2 class="section-title" style="font-size:1.5rem;">Change Password</h2></div>

<form method="POST" class="form-lux" style="max-width:520px;">
    <?= csrf_field() ?>
    <input type="hidden" name="form_type" value="change_password">
    <div class="field-group <?= isset($errors['current_password']) ? 'field-error' : '' ?>">
        <label>Current Password</label><input type="password" name="current_password" required>
        <?php if (isset($errors['current_password'])): ?><p class="error-text"><?= e($errors['current_password']) ?></p><?php endif; ?>
    </div>
    <div class="field-group <?= isset($errors['new_password']) ? 'field-error' : '' ?>">
        <label>New Password</label><input type="password" name="new_password" required minlength="8">
        <?php if (isset($errors['new_password'])): ?><p class="error-text"><?= e($errors['new_password']) ?></p><?php endif; ?>
    </div>
    <div class="field-group <?= isset($errors['confirm_new_password']) ? 'field-error' : '' ?>">
        <label>Confirm New Password</label><input type="password" name="confirm_new_password" required minlength="8">
        <?php if (isset($errors['confirm_new_password'])): ?><p class="error-text"><?= e($errors['confirm_new_password']) ?></p><?php endif; ?>
    </div>
    <button type="submit" class="btn-lux btn-lux-filled">Update Password</button>
</form>

<?php
require_once __DIR__ . '/../includes/customer-footer.php';
require_once __DIR__ . '/../includes/footer.php';
?>