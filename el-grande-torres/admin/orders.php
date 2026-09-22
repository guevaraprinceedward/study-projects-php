<?php
require_once __DIR__ . '/../config/app.php';
require_admin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    require_csrf_or_fail();
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['order_status'];
    if (in_array($new_status, ['pending','processing','shipped','delivered','cancelled','returned'], true)) {
        $stmt = $db->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $stmt->execute([$new_status, $order_id]);

        $stmt = $db->prepare("SELECT user_id, order_number FROM orders WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $o = $stmt->fetch();
        if ($o) {
            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'order')");
            $stmt->execute([$o['user_id'], 'Order Update', "Your order {$o['order_number']} status is now: " . ucfirst($new_status) . "."]);
        }
    }
    header('Location: ' . SITE_URL . '/admin/orders.php' . (isset($_GET['view']) ? '?view=' . urlencode($_GET['view']) : ''));
    exit;
}

$view_order = clean_input($_GET['view'] ?? '');
$order_detail = null; $order_items = []; $address = null; $payment = null;

if ($view_order) {
    $stmt = $db->prepare("SELECT o.*, u.first_name, u.last_name, u.email FROM orders o JOIN users u ON u.user_id = o.user_id WHERE o.order_number = ?");
    $stmt->execute([$view_order]);
    $order_detail = $stmt->fetch();
    if ($order_detail) {
        $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_detail['order_id']]);
        $order_items = $stmt->fetchAll();
        $stmt = $db->prepare("SELECT * FROM shipping_addresses WHERE address_id = ?");
        $stmt->execute([$order_detail['address_id']]);
        $address = $stmt->fetch();
        $stmt = $db->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY payment_id DESC LIMIT 1");
        $stmt->execute([$order_detail['order_id']]);
        $payment = $stmt->fetch();
    }
}

$status_filter = clean_input($_GET['status'] ?? '');
$sql = "SELECT o.*, u.first_name, u.last_name FROM orders o JOIN users u ON u.user_id = o.user_id";
$params = [];
if ($status_filter) { $sql .= " WHERE o.order_status = ?"; $params[] = $status_filter; }
$sql .= " ORDER BY o.placed_at DESC LIMIT 100";
$stmt = $db->prepare($sql); $stmt->execute($params);
$orders = $stmt->fetchAll();

$admin_active = 'orders';
$page_title = "Orders — Admin";
require_once __DIR__ . '/../includes/admin-header.php';
?>
<div class="admin-topbar"><h1>Orders</h1></div>

<?php if ($order_detail): ?>
    <a href="<?= SITE_URL ?>/admin/orders.php" style="font-size:0.85rem; color:var(--gold); display:inline-block; margin-bottom:1.5rem;">&larr; Back to Orders</a>
    <div class="admin-panel">
        <h3>Order <?= e($order_detail['order_number']) ?></h3>
        <p style="color:var(--text-secondary); font-size:0.85rem; margin-bottom:1.5rem;">
            <?= e($order_detail['first_name'] . ' ' . $order_detail['last_name']) ?> · <?= e($order_detail['email']) ?> · Placed <?= date('F j, Y g:i A', strtotime($order_detail['placed_at'])) ?>
        </p>

        <?php if ($address): ?>
        <p style="font-size:0.85rem; margin-bottom:1.5rem;">
            <strong>Ship to:</strong> <?= e($address['recipient_name']) ?>, <?= e($address['address_line1']) ?>, <?= e($address['city']) ?>, <?= e($address['province']) ?> <?= e($address['postal_code']) ?>
        </p>
        <?php endif; ?>

        <table class="admin-table">
            <thead><tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($order_items as $item): ?>
            <tr><td><?= e($item['product_name']) ?><?= $item['size'] ? ' (' . e($item['size']) . ')' : '' ?></td><td><?= $item['quantity'] ?></td><td><?= CURRENCY_SYMBOL . number_format($item['unit_price'], 2) ?></td><td><?= CURRENCY_SYMBOL . number_format($item['line_total'], 2) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <p style="margin-top:1.5rem; font-size:0.9rem;">Total: <strong style="color:var(--gold);"><?= CURRENCY_SYMBOL . number_format($order_detail['total_amount'], 2) ?></strong></p>
        <p style="font-size:0.85rem; color:var(--text-secondary); margin-top:0.4rem;">Payment: <?= strtoupper(e($order_detail['payment_method'])) ?><?= $order_detail['payment_reference'] ? ' · Ref: ' . e($order_detail['payment_reference']) : '' ?><?= $payment ? ' · Status: ' . ucfirst(e($payment['payment_status'])) : '' ?></p>

        <form method="POST" style="margin-top:1.8rem; display:flex; gap:1rem; align-items:center;">
            <?= csrf_field() ?>
            <input type="hidden" name="update_status" value="1">
            <input type="hidden" name="order_id" value="<?= $order_detail['order_id'] ?>">
            <select name="order_status" style="background:rgba(255,255,255,0.03); border:1px solid var(--border-color); color:var(--text-primary); padding:0.8rem;">
                <?php foreach (['pending','processing','shipped','delivered','cancelled','returned'] as $s): ?>
                <option value="<?= $s ?>" <?= $order_detail['order_status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="admin-btn admin-btn-gold">Update Status</button>
        </form>
    </div>
<?php else: ?>
    <div class="admin-tabs">
        <a href="?" class="<?= !$status_filter ? 'active' : '' ?>">All</a>
        <a href="?status=pending" class="<?= $status_filter === 'pending' ? 'active' : '' ?>">Pending</a>
        <a href="?status=processing" class="<?= $status_filter === 'processing' ? 'active' : '' ?>">Processing</a>
        <a href="?status=shipped" class="<?= $status_filter === 'shipped' ? 'active' : '' ?>">Shipped</a>
        <a href="?status=delivered" class="<?= $status_filter === 'delivered' ? 'active' : '' ?>">Delivered</a>
        <a href="?status=cancelled" class="<?= $status_filter === 'cancelled' ? 'active' : '' ?>">Cancelled</a>
    </div>
    <div class="admin-panel">
        <table class="admin-table">
            <thead><tr><th>Order #</th><th>Customer</th><th>Date</th><th>Payment</th><th>Total</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td><?= e($o['order_number']) ?></td>
                <td><?= e($o['first_name'] . ' ' . $o['last_name']) ?></td>
                <td><?= date('M j, Y', strtotime($o['placed_at'])) ?></td>
                <td style="text-transform:uppercase;"><?= e($o['payment_method']) ?></td>
                <td style="color:var(--gold);"><?= CURRENCY_SYMBOL . number_format($o['total_amount'], 2) ?></td>
                <td><span class="order-status-badge <?= e($o['order_status']) ?>"><?= ucfirst(e($o['order_status'])) ?></span></td>
                <td><a href="?view=<?= e($o['order_number']) ?>" class="admin-btn admin-btn-sm">View</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?><tr><td colspan="7" style="text-align:center; color:var(--text-secondary);">No orders found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>