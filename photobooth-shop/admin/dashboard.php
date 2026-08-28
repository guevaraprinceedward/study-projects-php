<?php
$page_title = 'Dashboard';
$activeNav = 'dashboard';
require_once __DIR__ . '/_admin_header.php';

$stats = admin_dashboard_stats();
$maxChartVal = max(1, ...array_column($stats['chart'], 'value'));
$maxTypeCnt = max(1, ...array_column($stats['byType'], 'cnt'));

$statusLabels = [
    'pending' => 'Pending', 'processing' => 'Processing', 'ready' => 'Ready', 'completed' => 'Completed', 'cancelled' => 'Cancelled',
];
$paymentLabels = ['pending' => 'Unpaid', 'verifying' => 'Verifying', 'paid' => 'Paid', 'failed' => 'Failed'];
?>

<div class="admin-topbar">
    <div>
        <h1>Welcome back, <?= e(explode(' ', $_SESSION['admin_name'] ?? 'Admin')[0]) ?> 👋</h1>
        <p>Here's how Kislap Booth is doing today.</p>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value"><?= CURRENCY_SYMBOL . number_format($stats['totalSales'], 2) ?></div><div class="stat-label">Total Sales</div></div>
    <div class="stat-card"><div class="stat-icon">🧾</div><div class="stat-value"><?= (int)$stats['totalOrders'] ?></div><div class="stat-label">Total Orders</div></div>
    <div class="stat-card"><div class="stat-icon">⏳</div><div class="stat-value"><?= (int)$stats['pendingOrders'] ?></div><div class="stat-label">Pending Orders</div></div>
    <div class="stat-card"><div class="stat-icon">📅</div><div class="stat-value"><?= (int)$stats['todayOrders'] ?></div><div class="stat-label">Orders Today</div></div>
</div>

<div class="admin-panels">
    <div class="admin-card">
        <h3>Sales — Last 7 Days</h3>
        <div class="mini-chart">
            <?php foreach ($stats['chart'] as $point): $h = $point['value'] > 0 ? max(6, ($point['value'] / $maxChartVal) * 140) : 4; ?>
            <div class="bar-col">
                <div class="bar" style="height: <?= $h ?>px;" title="<?= CURRENCY_SYMBOL . number_format($point['value'], 2) ?>"></div>
                <div class="bar-label"><?= e($point['label']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="admin-card">
        <h3>Sessions by Type</h3>
        <div class="type-breakdown">
            <?php foreach ($stats['byType'] as $t): ?>
            <div class="tb-row">
                <span class="tb-name"><?= e($t['icon_emoji']) ?> <?= e($t['name']) ?></span>
                <div class="tb-bar-track"><div class="tb-bar-fill" style="width: <?= ((int)$t['cnt'] / $maxTypeCnt) * 100 ?>%;"></div></div>
                <span class="tb-count"><?= (int)$t['cnt'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="admin-card" style="margin-top:1.4rem;">
    <h3>Recent Orders</h3>
    <div class="orders-table-wrap">
        <table class="orders-table">
            <thead><tr><th>Order #</th><th>Customer</th><th>Session</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php if (!$stats['recentOrders']): ?>
                <tr><td colspan="7" style="text-align:center; color:var(--ink-soft); padding:2rem;">No orders yet — once customers book a session, they'll show up here! ✨</td></tr>
            <?php else: foreach ($stats['recentOrders'] as $o): ?>
                <tr>
                    <td><strong><?= e($o['order_number']) ?></strong></td>
                    <td><?= e($o['customer_name']) ?></td>
                    <td><?= e($o['icon_emoji']) ?> <?= e($o['type_name']) ?></td>
                    <td><?= CURRENCY_SYMBOL . number_format($o['total_amount'], 2) ?></td>
                    <td><span class="status-chip status-<?= e($o['payment_status']) ?>"><?= e($paymentLabels[$o['payment_status']] ?? $o['payment_status']) ?></span></td>
                    <td><span class="status-chip status-<?= e($o['order_status']) ?>"><?= e($statusLabels[$o['order_status']] ?? $o['order_status']) ?></span></td>
                    <td><?= date('M j, g:ia', strtotime($o['created_at'])) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/_admin_footer.php'; ?>