<?php
require_once __DIR__ . '/../config/app.php';
require_login();

$order_number = clean_input($_GET['order'] ?? '');
$stmt = getDB()->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->execute([$order_number, current_user_id()]);
$order = $stmt->fetch();

if (!$order) { header('Location: ' . SITE_URL . '/customer/dashboard.php'); exit; }

$stmt = getDB()->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$order['order_id']]);
$order_items = $stmt->fetchAll();

$stmt = getDB()->prepare("SELECT * FROM shipping_addresses WHERE address_id = ?");
$stmt->execute([$order['address_id']]);
$address = $stmt->fetch();

$stmt = getDB()->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY payment_id DESC LIMIT 1");
$stmt->execute([$order['order_id']]);
$payment = $stmt->fetch();

$stmt = getDB()->prepare("SELECT first_name, last_name, email FROM users WHERE user_id = ?");
$stmt->execute([current_user_id()]);
$customer = $stmt->fetch();

$page_title = "Receipt — " . $order['order_number'];
$extra_css = SITE_URL . '/assets/css/shop.css';
require_once __DIR__ . '/../includes/header.php';

$payment_labels = ['cod' => 'Cash on Delivery', 'gcash' => 'GCash', 'maya' => 'Maya'];
?>
<style>
.receipt-wrap { max-width: 720px; margin: 0 auto; padding: calc(var(--nav-height) + 3.5rem) 5vw 6rem; }
.receipt-box { background: var(--bg-card); border: 1px solid var(--gold); padding: 3rem; position: relative; }
.receipt-corner { position: absolute; width: 22px; height: 22px; }
.receipt-corner.tl { top: 10px; left: 10px; border-top: 1px solid var(--gold); border-left: 1px solid var(--gold); }
.receipt-corner.tr { top: 10px; right: 10px; border-top: 1px solid var(--gold); border-right: 1px solid var(--gold); }
.receipt-corner.bl { bottom: 10px; left: 10px; border-bottom: 1px solid var(--gold); border-left: 1px solid var(--gold); }
.receipt-corner.br { bottom: 10px; right: 10px; border-bottom: 1px solid var(--gold); border-right: 1px solid var(--gold); }
.receipt-head { text-align: center; margin-bottom: 2rem; }
.receipt-head .eyebrow { justify-content: center; }
.receipt-head h1 { font-size: 1.9rem; margin: 0.5rem 0; }
.receipt-head .status-ok { color: #8FC9A0; font-size: 0.8rem; letter-spacing: 0.1em; text-transform: uppercase; }
.receipt-divider { display: flex; align-items: center; gap: 0.8rem; margin: 1.8rem 0; }
.receipt-divider::before, .receipt-divider::after { content: ''; flex: 1; height: 1px; background: linear-gradient(90deg, transparent, var(--border-color), transparent); }
.receipt-diamond { width: 6px; height: 6px; background: var(--gold); transform: rotate(45deg); flex-shrink: 0; }
.receipt-row { display: flex; justify-content: space-between; gap: 1rem; padding: 0.5rem 0; font-size: 0.88rem; }
.receipt-row .lbl { color: var(--text-secondary); flex-shrink: 0; }
.receipt-row .val { color: var(--text-primary); font-family: var(--font-display); font-size: 1rem; text-align: right; }
.receipt-item-row { display: flex; justify-content: space-between; gap: 1rem; padding: 0.7rem 0; border-bottom: 1px dotted var(--border-color); font-size: 0.85rem; }
.receipt-item-row:last-child { border-bottom: none; }
.receipt-totals .rt { display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--text-secondary); padding: 0.4rem 0; }
.receipt-totals .rt.grand { border-top: 1px double var(--gold); margin-top: 0.8rem; padding-top: 1rem; font-size: 1.4rem; color: var(--gold); font-family: var(--font-display); }
.receipt-actions { display: flex; gap: 1rem; justify-content: center; margin-top: 2.5rem; flex-wrap: wrap; }
@media print {
    body * { visibility: hidden; }
    .receipt-box, .receipt-box * { visibility: visible; }
    .receipt-box { position: fixed; inset: 0; border: none; }
    .receipt-actions, .navbar-lux, .footer-lux, .scroll-progress { display: none !important; }
}
</style>

<div class="receipt-wrap">
    <div class="receipt-box" id="receiptContent">
        <span class="receipt-corner tl"></span><span class="receipt-corner tr"></span><span class="receipt-corner bl"></span><span class="receipt-corner br"></span>

        <div class="receipt-head">
            <span class="eyebrow">El Grande De La Torres</span>
            <h1>Order Receipt</h1>
            <p class="status-ok">✓ Order Confirmed</p>
        </div>

        <div class="receipt-row"><span class="lbl">Order Number</span><span class="val"><?= e($order['order_number']) ?></span></div>
        <div class="receipt-row"><span class="lbl">Order Date</span><span class="val"><?= date('F j, Y — g:i A', strtotime($order['placed_at'])) ?></span></div>
        <div class="receipt-row"><span class="lbl">Customer</span><span class="val"><?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></span></div>
        <div class="receipt-row"><span class="lbl">Email</span><span class="val" style="font-size:0.85rem;"><?= e($customer['email']) ?></span></div>

        <div class="receipt-divider"><span class="receipt-diamond"></span></div>

        <?php if ($address): ?>
        <p style="font-size:0.72rem; letter-spacing:0.12em; text-transform:uppercase; color:var(--gold); margin-bottom:0.6rem;">Shipping To</p>
        <p style="font-size:0.9rem;"><?= e($address['recipient_name']) ?> · <?= e($address['phone_number']) ?></p>
        <p style="font-size:0.85rem; color:var(--text-secondary); margin-top:0.3rem;">
            <?= e($address['address_line1']) ?><?= $address['address_line2'] ? ', ' . e($address['address_line2']) : '' ?>, <?= e($address['city']) ?>, <?= e($address['province']) ?> <?= e($address['postal_code']) ?>
        </p>
        <div class="receipt-divider"><span class="receipt-diamond"></span></div>
        <?php endif; ?>

        <p style="font-size:0.72rem; letter-spacing:0.12em; text-transform:uppercase; color:var(--gold); margin-bottom:0.6rem;">Items</p>
        <?php foreach ($order_items as $item): ?>
        <div class="receipt-item-row">
            <span><?= e($item['product_name']) ?> <?= $item['size'] ? '(' . e($item['size']) . ')' : '' ?> × <?= $item['quantity'] ?></span>
            <span><?= CURRENCY_SYMBOL . number_format($item['line_total'], 2) ?></span>
        </div>
        <?php endforeach; ?>

        <div class="receipt-totals" style="margin-top:1.2rem;">
            <div class="rt"><span>Subtotal</span><span><?= CURRENCY_SYMBOL . number_format($order['subtotal'], 2) ?></span></div>
            <?php if ($order['discount_amount'] > 0): ?><div class="rt"><span>Discount<?= $order['coupon_code'] ? ' (' . e($order['coupon_code']) . ')' : '' ?></span><span>&minus;<?= CURRENCY_SYMBOL . number_format($order['discount_amount'], 2) ?></span></div><?php endif; ?>
            <div class="rt"><span>Shipping</span><span><?= $order['shipping_fee'] > 0 ? CURRENCY_SYMBOL . number_format($order['shipping_fee'], 2) : 'Complimentary' ?></span></div>
            <div class="rt grand"><span>Total Paid</span><span><?= CURRENCY_SYMBOL . number_format($order['total_amount'], 2) ?></span></div>
        </div>

        <div class="receipt-divider"><span class="receipt-diamond"></span></div>

        <div class="receipt-row"><span class="lbl">Payment Method</span><span class="val" style="text-transform:uppercase;"><?= e($payment_labels[$order['payment_method']] ?? $order['payment_method']) ?></span></div>
        <?php if ($order['payment_reference']): ?>
        <div class="receipt-row"><span class="lbl">Reference No.</span><span class="val"><?= e($order['payment_reference']) ?></span></div>
        <?php endif; ?>
        <div class="receipt-row"><span class="lbl">Payment Status</span><span class="val" style="color:<?= $payment && $payment['payment_status'] === 'paid' ? '#8FC9A0' : 'var(--gold)' ?>; text-transform:capitalize;"><?= e($payment['payment_status'] ?? 'pending') ?></span></div>
    </div>

    <div class="receipt-actions">
        <button type="button" class="btn-lux" onclick="window.print()">Print Receipt</button>
        <a href="<?= SITE_URL ?>/customer/orders.php?view=<?= e($order['order_number']) ?>" class="btn-lux btn-lux-ghost">View Order</a>
        <a href="<?= SITE_URL ?>/clothing.php" class="btn-lux btn-lux-filled">Continue Shopping</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>