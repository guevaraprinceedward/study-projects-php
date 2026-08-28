<?php
$extra_css = ['checkout.css'];
require_once __DIR__ . '/includes/header.php';

$orderNumber = $_GET['order'] ?? '';
$order = get_order_by_number($orderNumber);
if (!$order) {
    set_flash('error', "We couldn't find that order.");
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}
$page_title = 'Order Confirmed · Kislap Booth ✨';

$paymentNotes = [
    'cash' => 'Please pay ' . CURRENCY_SYMBOL . number_format($order['total_amount'], 2) . ' in cash upon pickup at our counter.',
    'gcash' => "We're verifying your GCash payment (ref: " . e($order['payment_reference'] ?? '—') . '). We\'ll text you once confirmed!',
    'credit_card' => 'Please proceed to our counter terminal to complete your card payment of ' . CURRENCY_SYMBOL . number_format($order['total_amount'], 2) . '.',
];
?>

<section class="success-page">
    <div class="container">
        <div class="success-hero reveal">
            <div class="success-badge">🎉</div>
            <h1>Yay, you're all set!</h1>
            <p><?= $paymentNotes[$order['payment_method']] ?? 'Thank you for booking with Kislap Booth!' ?></p>
        </div>

        <div class="success-strip-wrap reveal reveal-delay-1">
            <img src="<?= SITE_URL . '/' . e($order['final_image_path']) ?>" alt="Your Kislap Booth photo strip">
        </div>

        <div class="card-cute order-recap reveal reveal-delay-2" style="max-width:420px; margin:0 auto 2rem;">
            <span class="order-num-chip">Order #<?= e($order['order_number']) ?></span>
            <div class="recap-row"><span><?= e($order['icon_emoji']) ?> <?= e($order['type_name']) ?></span><span><?= CURRENCY_SYMBOL . number_format($order['subtotal'], 2) ?></span></div>
            <div class="recap-row"><span>Frame: <?= e($order['design_name'] ?? 'Classic') ?></span><span><?= $order['design_fee'] > 0 ? CURRENCY_SYMBOL . number_format($order['design_fee'], 2) : 'Free' ?></span></div>
            <div class="recap-row total"><span>Total</span><span><?= CURRENCY_SYMBOL . number_format($order['total_amount'], 2) ?></span></div>
        </div>

        <div class="success-actions reveal reveal-delay-3">
            <a href="<?= SITE_URL . '/' . e($order['final_image_path']) ?>" download class="btn-pill btn-pill-filled btn-pill-lg">Download Photo Strip ⬇️</a>
            <a href="<?= SITE_URL ?>/index.php" class="btn-pill btn-pill-ghost btn-pill-lg">Book Another Session ✨</a>
        </div>
    </div>
</section>

<script>
window.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => confettiBurst(window.innerWidth / 2, 220, 26), 400);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>