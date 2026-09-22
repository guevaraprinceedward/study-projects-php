<?php
require_once __DIR__ . '/../config/app.php';
require_login();

$user_id = current_user_id();
$db = getDB();

$view_order_number = clean_input($_GET['view'] ?? '');
$order_detail = null; $order_items = []; $shipping_address = null; $payment = null;

if ($view_order_number) {
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
    $stmt->execute([$view_order_number, $user_id]);
    $order_detail = $stmt->fetch();

    if ($order_detail) {
        $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_detail['order_id']]);
        $order_items = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT * FROM shipping_addresses WHERE address_id = ?");
        $stmt->execute([$order_detail['address_id']]);
        $shipping_address = $stmt->fetch();

        $stmt = $db->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY payment_id DESC LIMIT 1");
        $stmt->execute([$order_detail['order_id']]);
        $payment = $stmt->fetch();
    }
}

$status_filter = clean_input($_GET['status'] ?? '');
$sql = "SELECT * FROM orders WHERE user_id = ?";
$params = [$user_id];
if ($status_filter && in_array($status_filter, ['pending','processing','shipped','delivered','cancelled','returned'], true)) {
    $sql .= " AND order_status = ?"; $params[] = $status_filter;
}
$sql .= " ORDER BY placed_at DESC";
$stmt = $db->prepare($sql); $stmt->execute($params);
$orders = $stmt->fetchAll();

$page_title = "My Orders — El Grande De La Torres";
$extra_css = SITE_URL . '/assets/css/account.css';
require_once __DIR__ . '/../includes/header.php';
$active_page = 'orders';
require_once __DIR__ . '/../includes/customer-header.php';
?>

<?php if ($order_detail): ?>
    <a href="<?= SITE_URL ?>/customer/orders.php" style="font-size:0.85rem; color:var(--gold); display:inline-block; margin-bottom:2rem;">&larr; Back to Orders</a>

    <div class="card-lux" style="padding:2.2rem; margin-bottom:2rem;">
        <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:1.6rem;">
            <div>
                <h2 style="font-size:1.4rem;">Order <?= e($order_detail['order_number']) ?></h2>
                <p style="font-size:0.82rem; color:var(--text-secondary); margin-top:0.3rem;">Placed on <?= date('F j, Y g:i A', strtotime($order_detail['placed_at'])) ?></p>
            </div>
            <span class="order-status-badge <?= e($order_detail['order_status']) ?>" style="align-self:flex-start;"><?= ucfirst(e($order_detail['order_status'])) ?></span>
        </div>

        <?php if ($shipping_address): ?>
        <div style="margin-bottom:1.5rem; padding-bottom:1.5rem; border-bottom:1px solid var(--border-color);">
            <h4 style="font-size:0.72rem; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-secondary); margin-bottom:0.6rem;">Shipping To</h4>
            <p style="font-size:0.9rem;"><?= e($shipping_address['recipient_name']) ?> · <?= e($shipping_address['phone_number']) ?></p>
            <p style="font-size:0.9rem; color:var(--text-secondary);"><?= e($shipping_address['address_line1']) ?><?= $shipping_address['address_line2'] ? ', ' . e($shipping_address['address_line2']) : '' ?>, <?= e($shipping_address['city']) ?>, <?= e($shipping_address['province']) ?> <?= e($shipping_address['postal_code']) ?></p>
        </div>
        <?php endif; ?>

        <?php foreach ($order_items as $item): ?>
        <div style="display:flex; gap:1rem; padding:1rem 0; border-bottom:1px solid var(--border-color);">
            <img src="<?= e($item['product_image']) ?>" alt="<?= e($item['product_name']) ?>" style="width:64px; height:80px; object-fit:cover;">
            <div style="flex:1;">
                <p style="font-size:0.9rem;"><?= e($item['product_name']) ?></p>
                <p style="font-size:0.78rem; color:var(--text-secondary); margin-top:0.2rem;">Qty: <?= $item['quantity'] ?><?= $item['size'] ? ' · Size: ' . e($item['size']) : '' ?><?= $item['color'] ? ' · Color: ' . e($item['color']) : '' ?></p>
            </div>
            <p style="color:var(--gold);"><?= CURRENCY_SYMBOL . number_format($item['line_total'], 2) ?></p>
        </div>
        <?php endforeach; ?>

        <div style="margin-top:1.5rem; padding-top:1.5rem; border-top:1px solid var(--border-color);">
            <div class="summary-row"><span>Subtotal</span><span><?= CURRENCY_SYMBOL . number_format($order_detail['subtotal'], 2) ?></span></div>
            <?php if ($order_detail['discount_amount'] > 0): ?><div class="summary-row"><span>Discount<?= $order_detail['coupon_code'] ? ' (' . e($order_detail['coupon_code']) . ')' : '' ?></span><span>&minus;<?= CURRENCY_SYMBOL . number_format($order_detail['discount_amount'], 2) ?></span></div><?php endif; ?>
            <div class="summary-row"><span>Shipping</span><span><?= $order_detail['shipping_fee'] > 0 ? CURRENCY_SYMBOL . number_format($order_detail['shipping_fee'], 2) : 'Complimentary' ?></span></div>
            <div class="summary-row total"><span>Total</span><span><?= CURRENCY_SYMBOL . number_format($order_detail['total_amount'], 2) ?></span></div>
        </div>

        <p style="font-size:0.8rem; color:var(--text-secondary); margin-top:1.5rem;">
            Payment Method: <strong style="color:var(--text-primary); text-transform:uppercase;"><?= e($order_detail['payment_method']) ?></strong>
            <?php if ($payment): ?> · Status: <strong style="color:<?= $payment['payment_status'] === 'paid' ? '#8FC9A0' : 'var(--gold)' ?>; text-transform:capitalize;"><?= e($payment['payment_status']) ?></strong><?php endif; ?>
        </p>
        <a href="<?= SITE_URL ?>/customer/receipt.php?order=<?= e($order_detail['order_number']) ?>" class="btn-lux btn-lux-sm" style="margin-top:1.5rem;">View Receipt</a>
    </div>

<?php else: ?>
    <div class="section-head" style="justify-content:space-between; text-align:left; margin-bottom:1.5rem;">
        <h2 class="section-title" style="font-size:1.5rem; margin:0;">Order History</h2>
        <select onchange="window.location.href='?status='+this.value" class="sort-select">
            <option value="">All Statuses</option>
            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="processing" <?= $status_filter === 'processing' ? 'selected' : '' ?>>Processing</option>
            <option value="shipped" <?= $status_filter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
            <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
            <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            <option value="returned" <?= $status_filter === 'returned' ? 'selected' : '' ?>>Returned</option>
        </select>
    </div>

    <?php if (empty($orders)): ?>
        <div class="empty-state" style="text-align:left; padding:2rem 0;"><p>No orders found.</p></div>
    <?php else: foreach ($orders as $order): ?>
        <div class="order-row">
            <div><div class="order-num"><?= e($order['order_number']) ?></div><div class="order-date"><?= date('M j, Y', strtotime($order['placed_at'])) ?></div></div>
            <div style="font-size:0.9rem; color:var(--gold);"><?= CURRENCY_SYMBOL . number_format($order['total_amount'], 2) ?></div>
            <span class="order-status-badge <?= e($order['order_status']) ?>"><?= ucfirst(e($order['order_status'])) ?></span>
            <a href="?view=<?= e($order['order_number']) ?>" class="btn-lux btn-lux-sm">View</a>
        </div>
    <?php endforeach; endif; ?>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/customer-footer.php';
require_once __DIR__ . '/../includes/footer.php';
?>