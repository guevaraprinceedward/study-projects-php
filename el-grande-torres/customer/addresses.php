<?php
require_once __DIR__ . '/../config/app.php';
require_login();

$user_id = current_user_id();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();

    if (isset($_POST['delete_address_id'])) {
        $stmt = $db->prepare("DELETE FROM shipping_addresses WHERE address_id = ? AND user_id = ?");
        $stmt->execute([(int)$_POST['delete_address_id'], $user_id]);
        header('Location: ' . SITE_URL . '/customer/addresses.php'); exit;
    }
    if (isset($_POST['set_default_id'])) {
        $stmt = $db->prepare("UPDATE shipping_addresses SET is_default = 0 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stmt = $db->prepare("UPDATE shipping_addresses SET is_default = 1 WHERE address_id = ? AND user_id = ?");
        $stmt->execute([(int)$_POST['set_default_id'], $user_id]);
        header('Location: ' . SITE_URL . '/customer/addresses.php'); exit;
    }
    if (isset($_POST['add_new'])) {
        $label = clean_input($_POST['label'] ?? 'Home');
        $recipient_name = clean_input($_POST['recipient_name'] ?? '');
        $phone_number = clean_input($_POST['phone_number'] ?? '');
        $address_line1 = clean_input($_POST['address_line1'] ?? '');
        $address_line2 = clean_input($_POST['address_line2'] ?? '');
        $city = clean_input($_POST['city'] ?? '');
        $province = clean_input($_POST['province'] ?? '');
        $postal_code = clean_input($_POST['postal_code'] ?? '');

        if ($recipient_name && $phone_number && $address_line1 && $city && $province && $postal_code) {
            $stmt = $db->prepare("INSERT INTO shipping_addresses (user_id, label, recipient_name, phone_number, address_line1, address_line2, city, province, postal_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $label, $recipient_name, $phone_number, $address_line1, $address_line2, $city, $province, $postal_code]);
        }
        header('Location: ' . SITE_URL . '/customer/addresses.php'); exit;
    }
}

$stmt = $db->prepare("SELECT * FROM shipping_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();

$page_title = "Saved Addresses — El Grande De La Torres";
$extra_css = SITE_URL . '/assets/css/account.css';
require_once __DIR__ . '/../includes/header.php';
$active_page = 'addresses';
require_once __DIR__ . '/../includes/customer-header.php';
?>

<div class="section-head" style="justify-content:space-between; text-align:left; margin-bottom:1.5rem;">
    <h2 class="section-title" style="font-size:1.5rem; margin:0;">Saved Addresses</h2>
    <button type="button" class="btn-lux btn-lux-sm" onclick="document.getElementById('newAddressForm').style.display='block'">+ Add New</button>
</div>

<?php if (empty($addresses)): ?>
    <div class="empty-state" style="text-align:left; padding:1rem 0 2rem;"><p>You have no saved addresses yet.</p></div>
<?php else: ?>
    <div class="wishlist-grid" style="margin-bottom:2.5rem;">
        <?php foreach ($addresses as $addr): ?>
        <div class="address-card">
            <?php if ($addr['is_default']): ?><span class="default-badge">Default</span><?php endif; ?>
            <p style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; color:var(--gold); margin-bottom:0.5rem;"><?= e($addr['label']) ?></p>
            <p style="font-size:0.9rem;"><?= e($addr['recipient_name']) ?></p>
            <p style="font-size:0.82rem; color:var(--text-secondary); margin-top:0.3rem;"><?= e($addr['phone_number']) ?></p>
            <p style="font-size:0.82rem; color:var(--text-secondary); margin-top:0.3rem;"><?= e($addr['address_line1']) ?><?= $addr['address_line2'] ? ', ' . e($addr['address_line2']) : '' ?>, <?= e($addr['city']) ?>, <?= e($addr['province']) ?> <?= e($addr['postal_code']) ?></p>
            <div class="address-actions">
                <?php if (!$addr['is_default']): ?>
                <form method="POST" style="display:inline;"><?= csrf_field() ?><input type="hidden" name="set_default_id" value="<?= $addr['address_id'] ?>"><button type="submit">Set as Default</button></form>
                <?php endif; ?>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this address?')"><?= csrf_field() ?><input type="hidden" name="delete_address_id" value="<?= $addr['address_id'] ?>"><button type="submit" style="color:#E39A9A;">Delete</button></form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div id="newAddressForm" class="card-lux" style="padding:2.2rem; display:none; max-width:600px;">
    <h3 style="font-size:1.1rem; margin-bottom:1.5rem;">Add New Address</h3>
    <form method="POST" class="form-lux">
        <?= csrf_field() ?>
        <input type="hidden" name="add_new" value="1">
        <div class="field-group">
            <label>Label</label>
            <select name="label" style="width:100%; background:rgba(255,255,255,0.03); border:1px solid var(--border-color); color:var(--text-primary); padding:0.95rem 1.1rem;">
                <option value="Home">Home</option><option value="Office">Office</option><option value="Other">Other</option>
            </select>
        </div>
        <div class="row">
            <div class="col-sm-6 field-group"><label>Recipient Name</label><input type="text" name="recipient_name" required></div>
            <div class="col-sm-6 field-group"><label>Phone Number</label><input type="tel" name="phone_number" required></div>
        </div>
        <div class="field-group"><label>Address Line 1</label><input type="text" name="address_line1" required></div>
        <div class="field-group"><label>Address Line 2 (Optional)</label><input type="text" name="address_line2"></div>
        <div class="row">
            <div class="col-sm-4 field-group"><label>City</label><input type="text" name="city" required></div>
            <div class="col-sm-4 field-group"><label>Province</label><input type="text" name="province" required></div>
            <div class="col-sm-4 field-group"><label>Postal Code</label><input type="text" name="postal_code" required></div>
        </div>
        <div style="display:flex; gap:1rem;">
            <button type="submit" class="btn-lux btn-lux-filled">Save Address</button>
            <button type="button" class="btn-lux btn-lux-ghost" onclick="document.getElementById('newAddressForm').style.display='none'">Cancel</button>
        </div>
    </form>
</div>

<?php
require_once __DIR__ . '/../includes/customer-footer.php';
require_once __DIR__ . '/../includes/footer.php';
?>
