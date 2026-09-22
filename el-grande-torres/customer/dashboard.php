<?php
require_once __DIR__ . '/../config/app.php';
require_login();

$user_id = current_user_id();
$db = getDB();

$stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_orders = (int)($stmt->fetch()['cnt'] ?? 0);

$stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE user_id = ? AND order_status IN ('pending','processing','shipped')");
$stmt->execute([$user_id]);
$active_orders = (int)($stmt->fetch()['cnt'] ?? 0);

$stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM wishlist WHERE user_id = ?");
$stmt->execute([$user_id]);
$wishlist_count = (int)($stmt->fetch()['cnt'] ?? 0);

$stmt = $db->prepare("SELECT COALESCE(SUM(total_amount),0) AS total FROM orders WHERE user_id = ? AND order_status != 'cancelled'");
$stmt->execute([$user_id]);
$total_spent = (float)($stmt->fetch()['total'] ?? 0);

$stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY placed_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll();

$page_title = "Dashboard — El Grande De La Torres";
$extra_css = SITE_URL . '/assets/css/account.css';
require_once __DIR__ . '/../includes/header.php';
$active_page = 'dashboard';
require_once __DIR__ . '/../includes/customer-header.php';
?>

<div class="stat-cards">
    <div class="stat-card"><div class="num"><?= $total_orders ?></div><div class="lbl">Total Orders</div></div>
    <div class="stat-card"><div class="num"><?= $active_orders ?></div><div class="lbl">Active Orders</div></div>
    <div class="stat-card"><div class="num"><?= $wishlist_count ?></div><div class="lbl">Wishlist Items</div></div>
    <div class="stat-card"><div class="num"><?= CURRENCY_SYMBOL . number_format($total_spent, 0) ?></div><div class="lbl">Total Spent</div></div>
</div>

<div class="section-head" style="justify-content:flex-start; text-align:left; margin-bottom:1.5rem;">
    <h2 class="section-title" style="font-size:1.5rem;">Recent Orders</h2>
</div>

<?php if (empty($recent_orders)): ?>
    <div class="empty-state" style="text-align:left; padding:2rem 0;">
        <p>You haven't placed any orders yet.</p>
        <a href="<?= SITE_URL ?>/clothing.php" class="btn-lux" style="margin-top:1.5rem;">Start Shopping</a>
    </div>
<?php else: ?>
    <?php foreach ($recent_orders as $order): ?>
    <div class="order-row">
        <div><div class="order-num"><?= e($order['order_number']) ?></div><div class="order-date"><?= date('M j, Y', strtotime($order['placed_at'])) ?></div></div>
        <div style="font-size:0.9rem; color:var(--gold);"><?= CURRENCY_SYMBOL . number_format($order['total_amount'], 2) ?></div>
        <span class="order-status-badge <?= e($order['order_status']) ?>"><?= ucfirst(e($order['order_status'])) ?></span>
        <a href="<?= SITE_URL ?>/customer/orders.php?view=<?= e($order['order_number']) ?>" class="btn-lux btn-lux-sm">View</a>
    </div>
    <?php endforeach; ?>
    <a href="<?= SITE_URL ?>/customer/orders.php" style="display:inline-block; margin-top:2rem; font-size:0.85rem; color:var(--gold);">View All Orders &rarr;</a>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/customer-footer.php';
require_once __DIR__ . '/../includes/footer.php';
?>