<?php
require_once __DIR__ . '/../config/app.php';
require_admin();
$db = getDB();

$total_revenue = (float)($db->query("SELECT COALESCE(SUM(total_amount),0) AS t FROM orders WHERE order_status != 'cancelled'")->fetch()['t'] ?? 0);
$total_orders = (int)($db->query("SELECT COUNT(*) AS c FROM orders")->fetch()['c'] ?? 0);
$total_customers = (int)($db->query("SELECT COUNT(*) AS c FROM users")->fetch()['c'] ?? 0);
$pending_orders = (int)($db->query("SELECT COUNT(*) AS c FROM orders WHERE order_status = 'pending'")->fetch()['c'] ?? 0);

// Revenue by month (last 6 months)
$stmt = $db->query("SELECT DATE_FORMAT(placed_at, '%b %Y') AS m, SUM(total_amount) AS total
                     FROM orders WHERE order_status != 'cancelled' AND placed_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                     GROUP BY DATE_FORMAT(placed_at, '%Y-%m') ORDER BY DATE_FORMAT(placed_at, '%Y-%m') ASC");
$monthly_revenue = $stmt->fetchAll();
$max_month = 1;
foreach ($monthly_revenue as $m) $max_month = max($max_month, (float)$m['total']);

// Best sellers (combined clothing + essentials)
$stmt = $db->query("
    (SELECT name, sales_count, 'clothing' AS ptype FROM clothing_products ORDER BY sales_count DESC LIMIT 5)
    UNION ALL
    (SELECT name, sales_count, 'essentials' AS ptype FROM essentials_products ORDER BY sales_count DESC LIMIT 5)
    ORDER BY sales_count DESC LIMIT 6
");
$best_sellers = $stmt->fetchAll();
$max_sales = 1;
foreach ($best_sellers as $b) $max_sales = max($max_sales, (int)$b['sales_count']);

$recent_orders_stmt = $db->query("SELECT o.*, u.first_name, u.last_name FROM orders o JOIN users u ON u.user_id = o.user_id ORDER BY o.placed_at DESC LIMIT 8");
$recent_orders = $recent_orders_stmt->fetchAll();

$admin_active = 'dashboard';
$page_title = "Analytics — Admin";
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-topbar">
    <h1>Analytics Overview</h1>
    <div class="admin-user">Signed in as <?= e($_SESSION['admin_name']) ?></div>
</div>

<div class="admin-stat-grid">
    <div class="admin-stat-card"><div class="num"><?= CURRENCY_SYMBOL . number_format($total_revenue, 0) ?></div><div class="lbl">Total Revenue</div></div>
    <div class="admin-stat-card"><div class="num"><?= $total_orders ?></div><div class="lbl">Total Orders</div></div>
    <div class="admin-stat-card"><div class="num"><?= $total_customers ?></div><div class="lbl">Registered Customers</div></div>
    <div class="admin-stat-card"><div class="num"><?= $pending_orders ?></div><div class="lbl">Pending Orders</div></div>
</div>

<div class="admin-panel">
    <h3>Revenue — Last 6 Months</h3>
    <?php if (empty($monthly_revenue)): ?>
        <p style="color:var(--text-secondary); font-size:0.85rem;">No revenue data yet.</p>
    <?php else: foreach ($monthly_revenue as $m): $pct = ($m['total'] / $max_month) * 100; ?>
        <div class="chart-bar-row">
            <div class="chart-bar-label"><?= e($m['m']) ?></div>
            <div class="chart-bar-track"><div class="chart-bar-fill" style="width:<?= $pct ?>%"></div></div>
            <div class="chart-bar-value"><?= CURRENCY_SYMBOL . number_format($m['total'], 0) ?></div>
        </div>
    <?php endforeach; endif; ?>
</div>

<div class="admin-panel">
    <h3>Best Sellers</h3>
    <?php if (empty($best_sellers)): ?>
        <p style="color:var(--text-secondary); font-size:0.85rem;">No sales data yet.</p>
    <?php else: foreach ($best_sellers as $b): $pct = ((int)$b['sales_count'] / $max_sales) * 100; ?>
        <div class="chart-bar-row">
            <div class="chart-bar-label"><?= e($b['name']) ?></div>
            <div class="chart-bar-track"><div class="chart-bar-fill" style="width:<?= $pct ?>%"></div></div>
            <div class="chart-bar-value"><?= (int)$b['sales_count'] ?> sold</div>
        </div>
    <?php endforeach; endif; ?>
</div>

<div class="admin-panel">
    <h3>Recent Orders</h3>
    <table class="admin-table">
        <thead><tr><th>Order #</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recent_orders as $o): ?>
            <tr>
                <td><?= e($o['order_number']) ?></td>
                <td><?= e($o['first_name'] . ' ' . $o['last_name']) ?></td>
                <td><?= date('M j, Y', strtotime($o['placed_at'])) ?></td>
                <td style="color:var(--gold);"><?= CURRENCY_SYMBOL . number_format($o['total_amount'], 2) ?></td>
                <td><span class="order-status-badge <?= e($o['order_status']) ?>"><?= ucfirst(e($o['order_status'])) ?></span></td>
                <td><a href="<?= SITE_URL ?>/admin/orders.php?view=<?= e($o['order_number']) ?>" class="admin-btn admin-btn-sm">View</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($recent_orders)): ?><tr><td colspan="6" style="text-align:center; color:var(--text-secondary);">No orders yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>