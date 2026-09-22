<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/cart-functions.php';
require_once __DIR__ . '/includes/product-functions.php';
require_login();

$user_id = current_user_id();
$cart_data = get_cart_items($user_id);
$items = $cart_data['items'];
$subtotal = $cart_data['subtotal'];

if (empty($items)) { header('Location: ' . SITE_URL . '/cart.php'); exit; }

$applied_coupon = $_SESSION['applied_coupon'] ?? null;
$coupon_discount = $applied_coupon ? $subtotal * $applied_coupon['rate'] : 0.00;
$shipping = calculate_shipping($subtotal);
$total = max(0, $subtotal - $coupon_discount + $shipping);

$stmt = getDB()->prepare("SELECT * FROM shipping_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();

$stmt = getDB()->prepare("SELECT first_name, last_name, email, phone_number FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user_info = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();

    $recipient_name = clean_input($_POST['recipient_name'] ?? '');
    $phone = clean_input($_POST['phone_number'] ?? '');
    $address_line1 = clean_input($_POST['address_line1'] ?? '');
    $address_line2 = clean_input($_POST['address_line2'] ?? '');
    $city = clean_input($_POST['city'] ?? '');
    $province = clean_input($_POST['province'] ?? '');
    $postal_code = clean_input($_POST['postal_code'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';
    $payment_reference = clean_input($_POST['payment_reference'] ?? '') ?: null;
    $save_address = !empty($_POST['save_address']);

    if ($recipient_name === '') $errors['recipient_name'] = 'Recipient name is required.';
    if ($phone === '') $errors['phone_number'] = 'Phone number is required.';
    if ($address_line1 === '') $errors['address_line1'] = 'Address is required.';
    if ($city === '') $errors['city'] = 'City is required.';
    if ($province === '') $errors['province'] = 'Province is required.';
    if ($postal_code === '') $errors['postal_code'] = 'Postal code is required.';
    if (!in_array($payment_method, ['cod', 'gcash', 'maya'], true)) $errors['payment_method'] = 'Please select a payment method.';
    if (in_array($payment_method, ['gcash', 'maya'], true) && !$payment_reference) {
        $errors['payment_reference'] = 'Please enter your ' . strtoupper($payment_method) . ' reference number.';
    }

    if (empty($errors)) {
        $db = getDB();
        try {
            $db->beginTransaction();

            foreach ($items as $item) {
                $table = product_table($item['product_type']);
                $stmt = $db->prepare("SELECT stock_quantity, price FROM $table WHERE product_id = ? FOR UPDATE");
                $stmt->execute([$item['product_id']]);
                $live = $stmt->fetch();
                if (!$live || $live['stock_quantity'] < $item['quantity']) {
                    throw new Exception('"' . $item['name'] . '" no longer has enough stock. Please update your bag.');
                }
            }

            $stmt = $db->prepare("INSERT INTO shipping_addresses (user_id, recipient_name, phone_number, address_line1, address_line2, city, province, postal_code, is_default)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $recipient_name, $phone, $address_line1, $address_line2, $city, $province, $postal_code, $save_address ? 1 : 0]);
            $address_id = (int)$db->lastInsertId();

            $order_number = 'EGDLT-' . strtoupper(bin2hex(random_bytes(4)));
            $stmt = $db->prepare("INSERT INTO orders (order_number, user_id, address_id, subtotal, shipping_fee, discount_amount, coupon_code, total_amount, payment_method, payment_reference, order_status)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$order_number, $user_id, $address_id, $subtotal, $shipping, $coupon_discount, $applied_coupon['code'] ?? null, $total, $payment_method, $payment_reference]);
            $order_id = (int)$db->lastInsertId();

            foreach ($items as $item) {
                $stmt = $db->prepare("INSERT INTO order_items (order_id, product_type, product_id, product_name, product_image, size, color, quantity, unit_price, line_total)
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['product_type'], $item['product_id'], $item['name'], $item['image'], $item['size'], $item['color'], $item['quantity'], $item['unit_price'], $item['line_total']]);

                $table = product_table($item['product_type']);
                $stmt = $db->prepare("UPDATE $table SET stock_quantity = stock_quantity - ?, sales_count = sales_count + ? WHERE product_id = ?");
                $stmt->execute([$item['quantity'], $item['quantity'], $item['product_id']]);
            }

            // Payment record — status 'paid' for GCash/Maya (assumed confirmed via reference), 'pending' for COD until delivery
            $payment_status = $payment_method === 'cod' ? 'pending' : 'paid';
            $paid_at = $payment_method === 'cod' ? null : date('Y-m-d H:i:s');
            $stmt = $db->prepare("INSERT INTO payments (order_id, payment_method, reference_number, amount, payment_status, paid_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$order_id, $payment_method, $payment_reference, $total, $payment_status, $paid_at]);

            $stmt = $db->prepare("DELETE ci FROM cart_items ci JOIN carts c ON c.cart_id = ci.cart_id WHERE c.user_id = ?");
            $stmt->execute([$user_id]);

            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'order')");
            $stmt->execute([$user_id, 'Order Placed', "Your order $order_number has been received and is being processed."]);

            $db->commit();
            unset($_SESSION['applied_coupon']);

            header('Location: ' . SITE_URL . '/customer/receipt.php?order=' . $order_number);
            exit;
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            $errors['general'] = $e->getMessage() ?: 'We could not process your order. Please try again.';
        }
    }
}

$page_title = "Checkout — El Grande De La Torres";
$extra_css = SITE_URL . '/assets/css/shop.css';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-lux checkout-layout">
    <div>
        <div class="section-head" style="justify-content:flex-start; text-align:left; margin-bottom:2.5rem;">
            <div><span class="eyebrow">Final Step</span><h1 class="section-title" style="font-size:2.2rem;">Checkout</h1></div>
        </div>

        <?php if (!empty($errors['general'])): ?>
        <div class="card-lux" style="padding:1.2rem 1.5rem; border-color:#A75A5A; margin-bottom:2rem;">
            <p class="error-text" style="margin:0;"><?= e($errors['general']) ?></p>
        </div>
        <?php endif; ?>

        <form method="POST" class="form-lux" novalidate>
            <?= csrf_field() ?>

            <div class="checkout-step">
                <h3><span class="step-num">1</span> Shipping Address</h3>

                <?php if ($addresses): ?>
                <div class="field-group">
                    <label>Use a saved address</label>
                    <select id="savedAddressSelect" style="width:100%; background:rgba(255,255,255,0.03); border:1px solid var(--border-color); color:var(--text-primary); padding:0.95rem 1.1rem;">
                        <option value="">— Enter new address —</option>
                        <?php foreach ($addresses as $addr): ?>
                        <option value="<?= $addr['address_id'] ?>"
                            data-name="<?= e($addr['recipient_name']) ?>" data-phone="<?= e($addr['phone_number']) ?>"
                            data-line1="<?= e($addr['address_line1']) ?>" data-line2="<?= e($addr['address_line2']) ?>"
                            data-city="<?= e($addr['city']) ?>" data-province="<?= e($addr['province']) ?>" data-postal="<?= e($addr['postal_code']) ?>">
                            <?= e($addr['recipient_name']) ?> — <?= e($addr['city']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-sm-6 field-group <?= isset($errors['recipient_name']) ? 'field-error' : '' ?>">
                        <label>Recipient Name</label>
                        <input type="text" name="recipient_name" id="f_name" value="<?= e(($user_info['first_name'] ?? '') . ' ' . ($user_info['last_name'] ?? '')) ?>" required>
                        <?php if (isset($errors['recipient_name'])): ?><p class="error-text"><?= e($errors['recipient_name']) ?></p><?php endif; ?>
                    </div>
                    <div class="col-sm-6 field-group <?= isset($errors['phone_number']) ? 'field-error' : '' ?>">
                        <label>Phone Number</label>
                        <input type="tel" name="phone_number" id="f_phone" value="<?= e($user_info['phone_number'] ?? '') ?>" required>
                        <?php if (isset($errors['phone_number'])): ?><p class="error-text"><?= e($errors['phone_number']) ?></p><?php endif; ?>
                    </div>
                </div>

                <div class="field-group <?= isset($errors['address_line1']) ? 'field-error' : '' ?>">
                    <label>Address Line 1</label>
                    <input type="text" name="address_line1" id="f_line1" placeholder="House/Unit No., Street" required>
                    <?php if (isset($errors['address_line1'])): ?><p class="error-text"><?= e($errors['address_line1']) ?></p><?php endif; ?>
                </div>
                <div class="field-group">
                    <label>Address Line 2 (Optional)</label>
                    <input type="text" name="address_line2" id="f_line2" placeholder="Barangay, Subdivision">
                </div>
                <div class="row">
                    <div class="col-sm-4 field-group <?= isset($errors['city']) ? 'field-error' : '' ?>">
                        <label>City</label><input type="text" name="city" id="f_city" required>
                        <?php if (isset($errors['city'])): ?><p class="error-text"><?= e($errors['city']) ?></p><?php endif; ?>
                    </div>
                    <div class="col-sm-4 field-group <?= isset($errors['province']) ? 'field-error' : '' ?>">
                        <label>Province</label><input type="text" name="province" id="f_province" required>
                        <?php if (isset($errors['province'])): ?><p class="error-text"><?= e($errors['province']) ?></p><?php endif; ?>
                    </div>
                    <div class="col-sm-4 field-group <?= isset($errors['postal_code']) ? 'field-error' : '' ?>">
                        <label>Postal Code</label><input type="text" name="postal_code" id="f_postal" required>
                        <?php if (isset($errors['postal_code'])): ?><p class="error-text"><?= e($errors['postal_code']) ?></p><?php endif; ?>
                    </div>
                </div>
                <label style="display:flex; align-items:center; gap:0.6rem; font-size:0.82rem; color:var(--text-secondary); text-transform:none; margin-top:0.5rem;">
                    <input type="checkbox" name="save_address" style="width:auto; accent-color:var(--gold);" checked> Save this address for future orders
                </label>
            </div>

            <div class="checkout-step">
                <h3><span class="step-num">2</span> Payment Method</h3>
                <div class="payment-options <?= isset($errors['payment_method']) ? 'field-error' : '' ?>">
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="cod" checked data-needs-ref="0">
                        <div><strong style="display:block; margin-bottom:0.2rem;">Cash on Delivery</strong><span style="font-size:0.8rem; color:var(--text-secondary);">Pay when your order arrives.</span></div>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="gcash" data-needs-ref="1">
                        <div><strong style="display:block; margin-bottom:0.2rem;">GCash</strong><span style="font-size:0.8rem; color:var(--text-secondary);">Pay securely via GCash, then enter your reference number.</span></div>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="maya" data-needs-ref="1">
                        <div><strong style="display:block; margin-bottom:0.2rem;">Maya</strong><span style="font-size:0.8rem; color:var(--text-secondary);">Pay securely via Maya, then enter your reference number.</span></div>
                    </label>
                </div>
                <?php if (isset($errors['payment_method'])): ?><p class="error-text"><?= e($errors['payment_method']) ?></p><?php endif; ?>

                <div class="field-group <?= isset($errors['payment_reference']) ? 'field-error' : '' ?>" id="paymentRefGroup" style="display:none; margin-top:1.2rem;">
                    <label>Payment Reference Number</label>
                    <input type="text" name="payment_reference" id="f_payment_ref" placeholder="e.g. 0123456789012">
                    <?php if (isset($errors['payment_reference'])): ?><p class="error-text"><?= e($errors['payment_reference']) ?></p><?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn-lux btn-lux-filled btn-lux-block">Place Order</button>
        </form>
    </div>

    <div class="order-summary">
        <h3>Order Summary</h3>
        <?php foreach ($items as $item): ?>
        <div style="display:flex; gap:1rem; margin-bottom:1.2rem; padding-bottom:1.2rem; border-bottom:1px solid var(--border-color);">
            <img src="<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>" style="width:56px; height:70px; object-fit:cover;">
            <div style="flex:1;">
                <p style="font-size:0.85rem; margin-bottom:0.2rem;"><?= e($item['name']) ?></p>
                <p style="font-size:0.75rem; color:var(--text-secondary);">Qty: <?= $item['quantity'] ?><?= $item['size'] ? ' · ' . e($item['size']) : '' ?></p>
            </div>
            <p style="font-size:0.85rem; color:var(--gold);"><?= CURRENCY_SYMBOL . number_format($item['line_total'], 2) ?></p>
        </div>
        <?php endforeach; ?>
        <div class="summary-row"><span>Subtotal</span><span><?= CURRENCY_SYMBOL . number_format($subtotal, 2) ?></span></div>
        <?php if ($applied_coupon): ?><div class="summary-row"><span>Coupon (<?= e($applied_coupon['code']) ?>)</span><span>&minus;<?= CURRENCY_SYMBOL . number_format($coupon_discount, 2) ?></span></div><?php endif; ?>
        <div class="summary-row"><span>Shipping</span><span><?= $shipping > 0 ? CURRENCY_SYMBOL . number_format($shipping, 2) : 'Complimentary' ?></span></div>
        <div class="summary-row total"><span>Total</span><span><?= CURRENCY_SYMBOL . number_format($total, 2) ?></span></div>
        <p style="font-size:0.75rem; color:var(--text-secondary); margin-top:1rem;">Order Date: <?= date('F j, Y') ?></p>
    </div>
</div>

<script>
document.getElementById('savedAddressSelect')?.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    if (!opt.value) return;
    document.getElementById('f_name').value = opt.dataset.name || '';
    document.getElementById('f_phone').value = opt.dataset.phone || '';
    document.getElementById('f_line1').value = opt.dataset.line1 || '';
    document.getElementById('f_line2').value = opt.dataset.line2 || '';
    document.getElementById('f_city').value = opt.dataset.city || '';
    document.getElementById('f_province').value = opt.dataset.province || '';
    document.getElementById('f_postal').value = opt.dataset.postal || '';
});

const refGroup = document.getElementById('paymentRefGroup');
const refInput = document.getElementById('f_payment_ref');
function toggleRefField() {
    const checked = document.querySelector('input[name="payment_method"]:checked');
    const needsRef = checked && checked.dataset.needsRef === '1';
    refGroup.style.display = needsRef ? 'block' : 'none';
    refInput.required = needsRef;
}
document.querySelectorAll('.payment-option').forEach(label => {
    const input = label.querySelector('input');
    input.addEventListener('change', () => {
        document.querySelectorAll('.payment-option').forEach(l => l.classList.remove('active'));
        label.classList.add('active');
        toggleRefField();
    });
    if (input.checked) label.classList.add('active');
});
toggleRefField();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>