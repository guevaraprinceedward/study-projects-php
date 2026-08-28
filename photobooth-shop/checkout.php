<?php
$extra_css = ['checkout.css'];
require_once __DIR__ . '/includes/header.php';

$orderNumber = $_GET['order'] ?? '';
$order = get_order_by_number($orderNumber);
if (!$order) {
    set_flash('error', "We couldn't find that order. Let's start a new session! ✨");
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}
if ($order['payment_status'] !== 'pending') {
    header('Location: ' . SITE_URL . '/order-success.php?order=' . urlencode($orderNumber));
    exit;
}
$page_title = 'Checkout · Kislap Booth ✨';
?>

<section class="checkout-page">
    <div class="container checkout-grid">

        <div>
            <div class="section-head-cute" style="text-align:left; margin-bottom:1.8rem;">
                <span class="eyebrow-cute">Last step!</span>
                <h2>How would you like to pay?</h2>
                <p>Choose the payment method that works best for you.</p>
            </div>

            <form class="card-cute form-cute" id="paymentForm" style="padding:1.8rem;">
                <?= csrf_field() ?>
                <input type="hidden" name="order_number" value="<?= e($order['order_number']) ?>">

                <div class="payment-methods">
                    <div class="pm-option">
                        <input type="radio" name="payment_method" id="pmCash" value="cash" checked>
                        <label for="pmCash">
                            <span class="pm-icon">💵</span>
                            <span class="pm-text"><strong>Cash on Hand</strong><span>Pay in person when you pick up your prints</span></span>
                        </label>
                    </div>
                    <div class="pm-option">
                        <input type="radio" name="payment_method" id="pmGcash" value="gcash">
                        <label for="pmGcash">
                            <span class="pm-icon">📱</span>
                            <span class="pm-text"><strong>GCash</strong><span>Send payment and enter your reference number</span></span>
                        </label>
                    </div>
                    <div class="pm-option">
                        <input type="radio" name="payment_method" id="pmCard" value="credit_card">
                        <label for="pmCard">
                            <span class="pm-icon">💳</span>
                            <span class="pm-text"><strong>Credit / Debit Card</strong><span>Tap or swipe at our counter terminal</span></span>
                        </label>
                    </div>
                </div>

                <div class="pm-detail" id="gcashDetail">
                    <div class="gcash-qr">📱</div>
                    <p style="text-align:center; font-size:0.85rem; color:var(--ink-soft); margin-bottom:1rem;">
                        Scan the QR at our counter or send to <strong>0917-XXX-XXXX</strong> (Kislap Booth), then enter your reference number below.
                    </p>
                    <div class="field-group">
                        <label for="gcashRef">GCash Reference Number</label>
                        <input type="text" id="gcashRef" name="payment_reference" placeholder="e.g. 1234567890123">
                    </div>
                </div>

                <div class="pm-detail" id="cashDetail">
                    <p style="font-size:0.85rem; color:var(--ink-soft);">You'll pay <strong><?= CURRENCY_SYMBOL . number_format($order['total_amount'], 2) ?></strong> in cash upon pickup at our counter. No prepayment needed!</p>
                </div>

                <div class="pm-detail" id="cardDetail">
                    <p style="font-size:0.85rem; color:var(--ink-soft);">Please proceed to our counter terminal to tap or swipe your card. Your total is <strong><?= CURRENCY_SYMBOL . number_format($order['total_amount'], 2) ?></strong>.</p>
                </div>

                <button type="submit" class="btn-pill btn-pill-sunny btn-pill-lg btn-pill-block" id="payBtn">Confirm Order 🎀</button>
            </form>
        </div>

        <aside>
            <div class="card-cute order-recap">
                <span class="order-num-chip">Order #<?= e($order['order_number']) ?></span>
                <img src="<?= SITE_URL . '/' . e($order['final_image_path']) ?>" alt="Your photo strip">
                <div class="recap-row"><span><?= e($order['icon_emoji']) ?> <?= e($order['type_name']) ?></span><span><?= CURRENCY_SYMBOL . number_format($order['subtotal'], 2) ?></span></div>
                <div class="recap-row"><span>Frame: <?= e($order['design_name'] ?? 'Classic') ?></span><span><?= $order['design_fee'] > 0 ? CURRENCY_SYMBOL . number_format($order['design_fee'], 2) : 'Free' ?></span></div>
                <div class="recap-row total"><span>Total</span><span><?= CURRENCY_SYMBOL . number_format($order['total_amount'], 2) ?></span></div>
            </div>
        </aside>

    </div>
</section>

<script>
(function () {
    const radios = document.querySelectorAll('input[name="payment_method"]');
    const details = { cash: document.getElementById('cashDetail'), gcash: document.getElementById('gcashDetail'), credit_card: document.getElementById('cardDetail') };
    function updateDetail() {
        Object.values(details).forEach(d => d.classList.remove('active'));
        const checked = document.querySelector('input[name="payment_method"]:checked');
        if (checked && details[checked.value]) details[checked.value].classList.add('active');
    }
    radios.forEach(r => r.addEventListener('change', updateDetail));
    updateDetail();

    const form = document.getElementById('paymentForm');
    const payBtn = document.getElementById('payBtn');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const method = document.querySelector('input[name="payment_method"]:checked').value;
        if (method === 'gcash' && !document.getElementById('gcashRef').value.trim()) {
            showToast('Please enter your GCash reference number.', 'error');
            return;
        }
        payBtn.disabled = true;
        payBtn.textContent = 'Confirming… ✨';
        try {
            const res = await fetch(window.SITE_URL + '/api/process_payment.php', { method: 'POST', body: new FormData(form) });
            const data = await res.json();
            if (data.success) {
                window.location.href = window.SITE_URL + '/order-success.php?order=' + encodeURIComponent(data.order_number);
            } else {
                showToast(data.message || 'Could not confirm your order.', 'error');
                payBtn.disabled = false;
                payBtn.textContent = 'Confirm Order 🎀';
            }
        } catch (err) {
            showToast('Network error — please try again.', 'error');
            payBtn.disabled = false;
            payBtn.textContent = 'Confirm Order 🎀';
        }
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>