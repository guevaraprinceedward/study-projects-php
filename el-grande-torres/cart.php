<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/cart-functions.php';
require_login();

$cart_data = get_cart_items(current_user_id());
$items = $cart_data['items'];
$subtotal = $cart_data['subtotal'];
$shipping = calculate_shipping($subtotal);

$coupon_discount = 0.00; $coupon_error = '';
$applied_coupon = $_SESSION['applied_coupon'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['coupon_code'])) {
    require_csrf_or_fail();
    $code = strtoupper(clean_input($_POST['coupon_code']));
    $valid_coupons = ['WELCOME10' => 0.10, 'TORRES15' => 0.15, 'SEVENSTAR20' => 0.20];

    if (isset($valid_coupons[$code])) {
        $_SESSION['applied_coupon'] = ['code' => $code, 'rate' => $valid_coupons[$code]];
        $applied_coupon = $_SESSION['applied_coupon'];
    } else {
        $coupon_error = 'Invalid or expired coupon code.';
        unset($_SESSION['applied_coupon']);
        $applied_coupon = null;
    }
}
if ($applied_coupon) $coupon_discount = $subtotal * $applied_coupon['rate'];
$total = max(0, $subtotal - $coupon_discount + $shipping);

$page_title = "Your Bag — El Grande De La Torres";
$extra_css = SITE_URL . '/assets/css/shop.css';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-lux cart-layout">
    <div>
        <div class="section-head" style="justify-content:flex-start; text-align:left; margin-bottom:2.5rem;">
            <div><span class="eyebrow">Your Selection</span><h1 class="section-title" style="font-size:2.2rem;">Shopping Bag</h1></div>
        </div>

        <?php if (empty($items)): ?>
            <div class="empty-state" style="text-align:left; padding:3rem 0;">
                <p style="font-family:var(--font-display); font-size:1.4rem; margin-bottom:0.8rem;">Your bag is empty.</p>
                <p>Discover pieces crafted for those who value excellence.</p>
                <a href="<?= SITE_URL ?>/clothing.php" class="btn-lux" style="margin-top:1.5rem;">Continue Shopping</a>
            </div>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
            <div class="cart-item-row" data-cart-item-id="<?= $item['cart_item_id'] ?>">
                <a href="<?= SITE_URL ?>/product.php?type=<?= $item['product_type'] ?>&slug=<?= e($item['slug']) ?>">
                    <img src="<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>">
                </a>
                <div>
                    <h3 class="cart-item-title"><?= e($item['name']) ?> <?php if ($item['is_limited']): ?><span class="badge-limited" style="margin-left:0.6rem;">Limited</span><?php endif; ?></h3>
                    <p class="cart-item-meta">
                        <?php if ($item['size']): ?>Size: <?= e($item['size']) ?><?php endif; ?>
                        <?php if ($item['color']): ?> &nbsp;·&nbsp; Color: <?= e($item['color']) ?><?php endif; ?>
                    </p>
                    <div class="qty-selector" style="height:38px;">
                        <button type="button" class="cart-qty-btn" data-delta="-1">&minus;</button>
                        <input type="number" class="cart-qty-input" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" readonly>
                        <button type="button" class="cart-qty-btn" data-delta="1">&plus;</button>
                    </div>
                    <button class="cart-item-remove" style="margin-top:0.8rem;" data-cart-item-id="<?= $item['cart_item_id'] ?>">Remove</button>
                </div>
                <div class="cart-item-price" data-unit-price="<?= $item['unit_price'] ?>"><?= CURRENCY_SYMBOL . number_format($item['line_total'], 2) ?></div>
            </div>
            <?php endforeach; ?>
            <a href="<?= SITE_URL ?>/clothing.php" style="display:inline-block; margin-top:2rem; font-size:0.85rem; color:var(--gold);">&larr; Continue Shopping</a>
        <?php endif; ?>
    </div>

    <?php if (!empty($items)): ?>
    <div class="order-summary">
        <h3>Order Summary</h3>
        <div class="summary-row"><span>Subtotal</span><span><?= CURRENCY_SYMBOL . number_format($subtotal, 2) ?></span></div>
        <?php if ($applied_coupon): ?>
        <div class="summary-row"><span>Coupon (<?= e($applied_coupon['code']) ?>)</span><span>&minus;<?= CURRENCY_SYMBOL . number_format($coupon_discount, 2) ?></span></div>
        <?php endif; ?>
        <div class="summary-row"><span>Shipping</span><span><?= $shipping > 0 ? CURRENCY_SYMBOL . number_format($shipping, 2) : 'Complimentary' ?></span></div>

        <form method="POST" class="coupon-row">
            <?= csrf_field() ?>
            <input type="text" name="coupon_code" placeholder="Coupon code" value="<?= e($applied_coupon['code'] ?? '') ?>">
            <button type="submit" class="btn-lux btn-lux-sm">Apply</button>
        </form>
        <?php if ($coupon_error): ?><p class="error-text" style="margin-top:-1rem; margin-bottom:1rem;"><?= e($coupon_error) ?></p><?php endif; ?>

        <div class="summary-row total"><span>Total</span><span><?= CURRENCY_SYMBOL . number_format($total, 2) ?></span></div>
        <a href="<?= SITE_URL ?>/checkout.php" class="btn-lux btn-lux-filled btn-lux-block" style="margin-top:1.5rem;">Proceed to Checkout</a>
    </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.cart-qty-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const row = this.closest('.cart-item-row');
        const input = row.querySelector('.cart-qty-input');
        let newQty = parseInt(input.value) + parseInt(this.dataset.delta);
        const max = parseInt(input.max) || 99;
        if (newQty < 1) newQty = 1;
        if (newQty > max) { showToast('Only ' + max + ' left in stock.'); return; }
        const data = new FormData();
        data.append('cart_item_id', row.dataset.cartItemId);
        data.append('quantity', newQty);
        data.append('csrf_token', window.CSRF_TOKEN);
        fetch(window.SITE_URL + '/api/cart_update.php', { method: 'POST', body: data })
            .then(r => r.json()).then(res => { if (res.success) window.location.reload(); else showToast(res.message || 'Could not update.'); });
    });
});
document.querySelectorAll('.cart-item-remove').forEach(btn => {
    btn.addEventListener('click', function () {
        if (!confirm('Remove this item from your bag?')) return;
        const data = new FormData();
        data.append('cart_item_id', this.dataset.cartItemId);
        data.append('csrf_token', window.CSRF_TOKEN);
        fetch(window.SITE_URL + '/api/cart_remove.php', { method: 'POST', body: data })
            .then(r => r.json()).then(res => { if (res.success) window.location.reload(); });
    });
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>