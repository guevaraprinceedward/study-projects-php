<?php
require_once __DIR__ . '/../config/app.php';
require_login();

$order_number = clean_input($_GET['order'] ?? '');
$stmt = getDB()->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->execute([$order_number, current_user_id()]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . SITE_URL . '/customer/dashboard.php');
    exit;
}

$page_title = "Order Confirmed — El Grande De La Torres";
$extra_css = SITE_URL . '/assets/css/shop.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-lux" style="padding-top:calc(var(--nav-height) + 5rem); padding-bottom:6rem; text-align:center; max-width:600px;">
    <div style="width:70px; height:70px; border:1px solid var(--gold); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 2rem;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#C8A96A" stroke-width="1.5"><path d="M20 6L9 17l-5-5"/></svg>
    </div>
    <span class="eyebrow">Thank You</span>
    <h1 class="section-title" style="font-size:2.2rem;">Your Order Has Been Placed</h1>
    <p class="section-sub" style="margin:1rem auto 2rem;">
        Order <strong style="color:var(--text-primary);"><?= e($order['order_number']) ?></strong> has been received and is being prepared with care. A confirmation has been noted on your account.
    </p>

    <div class="card-lux" style="padding:2rem; text-align:left; margin-bottom:2rem;">
        <div class="summary-row"><span>Order Number</span><span><?= e($order['order_number']) ?></span></div>
        <div class="summary-row"><span>Payment Method</span><span style="text-transform:uppercase;"><?= e($order['payment_method']) ?></span></div>
        <div class="summary-row total"><span>Total Paid</span><span><?= CURRENCY_SYMBOL . number_format($order['total_amount'], 2) ?></span></div>
    </div>

    <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
        <a href="<?= SITE_URL ?>/customer/orders.php?view=<?= e($order['order_number']) ?>" class="btn-lux">View Order</a>
        <a href="<?= SITE_URL ?>/clothing.php" class="btn-lux btn-lux-filled">Continue Shopping</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>