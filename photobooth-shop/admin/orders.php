<?php
$page_title = 'Orders';
$activeNav = 'orders';
require_once __DIR__ . '/_admin_header.php';

// Handle inline status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (verify_csrf()) {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $newOrderStatus = $_POST['order_status'] ?? '';
        $newPaymentStatus = $_POST['payment_status'] ?? '';
        $validOrderStatuses = ['pending','processing','ready','completed','cancelled'];
        $validPaymentStatuses = ['pending','verifying','paid','failed'];
        if ($orderId && in_array($newOrderStatus, $validOrderStatuses, true) && in_array($newPaymentStatus, $validPaymentStatuses, true)) {
            $stmt = getDB()->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE order_id = ?");
            $stmt->execute([$newOrderStatus, $newPaymentStatus, $orderId]);
            set_flash('success', 'Order updated! ✨');
        }
    }
    header('Location: ' . SITE_URL . '/admin/orders.php' . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit;
}

$filter = $_GET['status'] ?? 'all';
$validFilters = ['all','pending','processing','ready','completed','cancelled'];
if (!in_array($filter, $validFilters, true)) $filter = 'all';

$db = getDB();
if ($filter === 'all') {
    $orders = $db->query("
        SELECT o.*, pt.name AS type_name, pt.icon_emoji
        FROM orders o JOIN photo_types pt ON pt.type_id = o.photo_type_id
        ORDER BY o.created_at DESC
    ")->fetchAll();
} else {
    $stmt = $db->prepare("
        SELECT o.*, pt.name AS type_name, pt.icon_emoji
        FROM orders o JOIN photo_types pt ON pt.type_id = o.photo_type_id
        WHERE o.order_status = ? ORDER BY o.created_at DESC
    ");
    $stmt->execute([$filter]);
    $orders = $stmt->fetchAll();
}

$statusLabels = ['pending' => 'Pending', 'processing' => 'Processing', 'ready' => 'Ready', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
$paymentLabels = ['pending' => 'Unpaid', 'verifying' => 'Verifying', 'paid' => 'Paid', 'failed' => 'Failed'];
?>

<div class="admin-topbar">
    <div>
        <h1>Orders</h1>
        <p>View and manage every Kislap Booth booking.</p>
    </div>
</div>

<div class="admin-filters">
    <a href="?" class="<?= $filter === 'all' ? 'active' : '' ?>">All</a>
    <?php foreach (['pending','processing','ready','completed','cancelled'] as $s): ?>
    <a href="?status=<?= $s ?>" class="<?= $filter === $s ? 'active' : '' ?>"><?= $statusLabels[$s] ?></a>
    <?php endforeach; ?>
</div>

<div class="orders-table-wrap">
    <table class="orders-table">
        <thead><tr><th>Photo</th><th>Order #</th><th>Customer</th><th>Session</th><th>Total</th><th>Payment</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php if (!$orders): ?>
            <tr><td colspan="8" style="text-align:center; color:var(--ink-soft); padding:2rem;">No orders found for this filter.</td></tr>
        <?php else: foreach ($orders as $o): ?>
            <tr>
                <td><?php if ($o['final_image_path']): ?><img class="thumb" src="<?= SITE_URL . '/' . e($o['final_image_path']) ?>" alt=""><?php endif; ?></td>
                <td><strong><?= e($o['order_number']) ?></strong><br><span style="color:var(--ink-soft); font-size:0.76rem;"><?= date('M j, g:ia', strtotime($o['created_at'])) ?></span></td>
                <td><?= e($o['customer_name']) ?><br><span style="color:var(--ink-soft); font-size:0.76rem;"><?= e($o['customer_phone']) ?></span></td>
                <td><?= e($o['icon_emoji']) ?> <?= e($o['type_name']) ?></td>
                <td><?= CURRENCY_SYMBOL . number_format($o['total_amount'], 2) ?></td>
                <td><span class="status-chip status-<?= e($o['payment_status']) ?>"><?= e($paymentLabels[$o['payment_status']] ?? $o['payment_status']) ?></span></td>
                <td><span class="status-chip status-<?= e($o['order_status']) ?>"><?= e($statusLabels[$o['order_status']] ?? $o['order_status']) ?></span></td>
                <td>
                    <form method="POST" style="display:flex; gap:0.4rem; align-items:center;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
                        <select name="order_status" class="status-select">
                            <?php foreach ($statusLabels as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $o['order_status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="payment_status" class="status-select">
                            <?php foreach ($paymentLabels as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $o['payment_status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-pill btn-pill-filled" style="padding:0.4rem 0.9rem; font-size:0.72rem;">Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/_admin_footer.php'; ?>