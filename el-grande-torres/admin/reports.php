<?php
require_once __DIR__ . '/../config/app.php';
require_admin();
$db = getDB();

$total_revenue = (float)($db->query("SELECT COALESCE(SUM(total_amount),0) AS t FROM orders WHERE order_status != 'cancelled'")->fetch()['t'] ?? 0);
$avg_order_value = (float)($db->query("SELECT COALESCE(AVG(total_amount),0) AS t FROM orders WHERE order_status != 'cancelled'")->fetch()['t'] ?? 0);

$payment_breakdown = $db->query("SELECT payment_method, COUNT(*) AS cnt, SUM(total_amount) AS total FROM orders WHERE order_status != 'cancelled' GROUP BY payment_method")->fetchAll();
$status_breakdown = $db->query("SELECT order_status, COUNT(*) AS cnt FROM orders GROUP BY order_status")->fetchAll();

$clothing_revenue = (float)($db->query("
    SELECT COALESCE(SUM(oi.line_total),0) AS t FROM order_items oi
    JOIN orders o ON o.order_id = oi.order_id
    WHERE oi.product_type = 'clothing' AND o.order_status != 'cancelled'
")->fetch()['t'] ?? 0);
$essentials_revenue = (float)($db->query("
    SELECT COALESCE(SUM(oi.line_total),0) AS t FROM order_items oi
    JOIN orders o ON o.order_id = oi.order_id
    WHERE oi.product_type = 'essentials' AND o.order_status != 'cancelled'
")->fetch()['t'] ?? 0);
$store_max = max($clothing_revenue, $essentials_revenue, 1);

$admin_active = 'reports';
$page_title = "Reports — Admin";
require_once __DIR__ . '/../includes/admin-header.php';
?>
<div class="admin-topbar"><h1>Reports</h1></div>

<div class="admin-stat-grid">
    <div class="admin-stat-card"><div class="num"><?= CURRENCY_SYMBOL . number_format($total_revenue, 0) ?></div><div class="lbl">Total Revenue</div></div>
    <div class="admin-stat-card"><div class="num"><?= CURRENCY_SYMBOL . number_format($avg_order_value, 0) ?></div><div class="lbl">Avg. Order Value</div></div>
</div>

<div class="admin-panel">
    <h3>Revenue by Store</h3>
    <div class="chart-bar-row"><div class="chart-bar-label">Clothing</div><div class="chart-bar-track"><div class="chart-bar-fill" style="width:<?= ($clothing_revenue/$store_max)*100 ?>%"></div></div><div class="chart-bar-value"><?= CURRENCY_SYMBOL . number_format($clothing_revenue, 0) ?></div></div>
    <div class="chart-bar-row"><div class="chart-bar-label">Essentials</div><div class="chart-bar-track"><div class="chart-bar-fill" style="width:<?= ($essentials_revenue/$store_max)*100 ?>%"></div></div><div class="chart-bar-value"><?= CURRENCY_SYMBOL . number_format($essentials_revenue, 0) ?></div></div>
</div>

<div class="admin-panel">
    <h3>Payment Method Breakdown</h3>
    <table class="admin-table">
        <thead><tr><th>Method</th><th>Orders</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($payment_breakdown as $p): ?>
        <tr><td style="text-transform:uppercase;"><?= e($p['payment_method']) ?></td><td><?= (int)$p['cnt'] ?></td><td style="color:var(--gold);"><?= CURRENCY_SYMBOL . number_format($p['total'], 2) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="admin-panel">
    <h3>Order Status Breakdown</h3>
    <table class="admin-table">
        <thead><tr><th>Status</th><th>Count</th></tr></thead>
        <tbody>
        <?php foreach ($status_breakdown as $s): ?>
        <tr><td><span class="order-status-badge <?= e($s['order_status']) ?>"><?= ucfirst(e($s['order_status'])) ?></span></td><td><?= (int)$s['cnt'] ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>    