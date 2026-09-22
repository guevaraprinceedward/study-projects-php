<?php
require_once __DIR__ . '/../config/app.php';
require_login();

$order_number = clean_input($_GET['order'] ?? '');
$stmt = getDB()->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->execute([$order_number, current_user_id()]);
$order = $stmt->fetch();

if (!$order) { header('Location: ' . SITE_URL . '/customer/dashboard.php'); exit; }

$stmt = getDB()->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$order['order_id']]);
$order_items = $stmt->fetchAll();

$stmt = getDB()->prepare("SELECT * FROM shipping_addresses WHERE address_id = ?");
$stmt->execute([$order['address_id']]);
$address = $stmt->fetch();

$stmt = getDB()->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY payment_id DESC LIMIT 1");
$stmt->execute([$order['order_id']]);
$payment = $stmt->fetch();

$stmt = getDB()->prepare("SELECT first_name, last_name, email FROM users WHERE user_id = ?");
$stmt->execute([current_user_id()]);
$customer = $stmt->fetch();

$page_title = "Receipt — " . $order['order_number'];
$extra_css = SITE_URL . '/assets/css/shop.css';
require_once __DIR__ . '/../includes/header.php';

$payment_labels = ['cod' => 'Cash on Delivery', 'gcash' => 'GCash', 'maya' => 'Maya'];
$is_paid = $payment && $payment['payment_status'] === 'paid';
?>
<style>
/* ============================================================
   RECEIPT — 7★ ELEGANCE
   Preserves existing --gold / --bg-card / --text-primary etc.
   from shop.css so it stays consistent with the rest of the site.
   ============================================================ */

.receipt-stage {
    max-width: 780px;
    margin: 0 auto;
    padding: calc(var(--nav-height) + 4rem) 5vw 7rem;
    perspective: 1800px;
    position: relative;
}

/* soft ambient glow behind the card */
.receipt-stage::before,
.receipt-stage::after {
    content: '';
    position: absolute;
    width: 420px;
    height: 420px;
    border-radius: 50%;
    filter: blur(110px);
    opacity: 0.16;
    pointer-events: none;
    z-index: 0;
}
.receipt-stage::before {
    background: var(--gold);
    top: -80px;
    left: -120px;y
    animation: driftA 14s ease-in-out infinite alternate;
}
.receipt-stage::after {
    background: #8FC9A0;
    bottom: -80px;
    right: -120px;
    animation: driftB 16s ease-in-out infinite alternate;
}
@keyframes driftA { from { transform: translate(0,0); } to { transform: translate(40px,30px); } }
@keyframes driftB { from { transform: translate(0,0); } to { transform: translate(-30px,-40px); } }

.receipt-box {
    background: linear-gradient(165deg, var(--bg-card) 0%, rgba(255,255,255,0.02) 100%);
    border: 1px solid var(--gold);
    padding: 3.2rem 3rem;
    position: relative;
    z-index: 1;
    border-radius: 2px;
    transform-style: preserve-3d;
    box-shadow:
        0 2px 0 rgba(212, 175, 55, 0.15) inset,
        0 30px 80px -20px rgba(0,0,0,0.55),
        0 0 0 1px rgba(212,175,55,0.05);
    opacity: 0;
    transform: translateY(60px) rotateX(-14deg) scale(0.94);
    animation: riseIn 1s cubic-bezier(.16,1,.3,1) 0.15s forwards;
    transition: transform 0.15s ease-out, box-shadow 0.4s ease;
    will-change: transform;
}

@keyframes riseIn {
    to { opacity: 1; transform: translateY(0) rotateX(0deg) scale(1); }
}

.receipt-box:hover {
    box-shadow:
        0 2px 0 rgba(212, 175, 55, 0.25) inset,
        0 40px 100px -20px rgba(0,0,0,0.65),
        0 0 60px -10px rgba(212,175,55,0.18);
}

.receipt-corner { position: absolute; width: 26px; height: 26px; opacity: 0; animation: cornerFade 0.6s ease 0.9s forwards; }
.receipt-corner.tl { top: 12px; left: 12px; border-top: 1px solid var(--gold); border-left: 1px solid var(--gold); }
.receipt-corner.tr { top: 12px; right: 12px; border-top: 1px solid var(--gold); border-right: 1px solid var(--gold); }
.receipt-corner.bl { bottom: 12px; left: 12px; border-bottom: 1px solid var(--gold); border-left: 1px solid var(--gold); }
.receipt-corner.br { bottom: 12px; right: 12px; border-bottom: 1px solid var(--gold); border-right: 1px solid var(--gold); }
@keyframes cornerFade { to { opacity: 1; } }

/* ---------- checkmark seal ---------- */
.seal-wrap {
    display: flex;
    justify-content: center;
    margin-bottom: 0.6rem;
    transform: translateZ(40px);
}
.seal {
    width: 76px;
    height: 76px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: radial-gradient(circle at 30% 30%, rgba(143,201,160,0.25), rgba(143,201,160,0.05));
    border: 1px solid #8FC9A0;
    box-shadow: 0 0 0 0 rgba(143,201,160,0.4);
    animation: sealPulse 2.2s ease-out 1.1s 1, sealFloat 4s ease-in-out 3.3s infinite;
    opacity: 0;
    transform: scale(0.5);
    animation-fill-mode: forwards;
}
.seal { animation-name: sealPop, sealGlow; animation-duration: 0.6s, 2s; animation-delay: 0.75s, 1.4s; animation-timing-function: cubic-bezier(.34,1.56,.64,1), ease-out; animation-iteration-count: 1, 1; animation-fill-mode: forwards, forwards; }
@keyframes sealPop { 0% { opacity: 0; transform: scale(0.3) rotate(-30deg);} 100% { opacity: 1; transform: scale(1) rotate(0deg);} }
@keyframes sealGlow { 0% { box-shadow: 0 0 0 0 rgba(143,201,160,0.45);} 100% { box-shadow: 0 0 0 18px rgba(143,201,160,0);} }
.seal svg { width: 34px; height: 34px; }
.seal svg path {
    stroke: #8FC9A0;
    stroke-width: 3;
    fill: none;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-dasharray: 40;
    stroke-dashoffset: 40;
    animation: drawCheck 0.5s ease 1.25s forwards;
}
@keyframes drawCheck { to { stroke-dashoffset: 0; } }

.receipt-head { text-align: center; margin-bottom: 2.2rem; transform: translateZ(30px); }
.receipt-head .eyebrow {
    justify-content: center;
    opacity: 0;
    animation: fadeUp 0.6s ease 0.55s forwards;
}
.receipt-head h1 {
    font-size: 2.1rem;
    margin: 0.5rem 0;
    background: linear-gradient(100deg, var(--text-primary) 20%, var(--gold) 50%, var(--text-primary) 80%);
    background-size: 220% auto;
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    animation: fadeUp 0.6s ease 0.65s forwards, shimmerText 5s linear 1.3s infinite;
    opacity: 0;
}
@keyframes shimmerText { to { background-position: -220% center; } }
.receipt-head .status-ok {
    color: #8FC9A0;
    font-size: 0.78rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    opacity: 0;
    animation: fadeUp 0.6s ease 0.8s forwards;
}
@keyframes fadeUp { from { opacity: 0; transform: translateY(10px);} to { opacity: 1; transform: translateY(0);} }

/* ---------- staggered reveal for content blocks ---------- */
.reveal { opacity: 0; transform: translateY(16px); animation: fadeUp 0.55s ease forwards; }
.receipt-row.reveal:nth-of-type(1) { animation-delay: 0.95s; }
.r-meta .receipt-row:nth-child(1) { animation-delay: 0.95s; }
.r-meta .receipt-row:nth-child(2) { animation-delay: 1.02s; }
.r-meta .receipt-row:nth-child(3) { animation-delay: 1.09s; }
.r-meta .receipt-row:nth-child(4) { animation-delay: 1.16s; }
.r-ship { animation-delay: 1.25s; }
.r-items { animation-delay: 1.35s; }
.receipt-item-row.reveal { animation-delay: calc(1.4s + var(--i, 0) * 0.06s); }
.r-totals { animation-delay: 1.55s; }
.r-payment .receipt-row:nth-child(1) { animation-delay: 1.65s; }
.r-payment .receipt-row:nth-child(2) { animation-delay: 1.72s; }
.r-payment .receipt-row:nth-child(3) { animation-delay: 1.79s; }
.r-actions { animation-delay: 1.9s; }

.receipt-divider {
    display: flex; align-items: center; gap: 0.8rem; margin: 1.8rem 0;
    opacity: 0; animation: fadeUp 0.5s ease forwards;
}
.receipt-divider::before, .receipt-divider::after {
    content: ''; flex: 1; height: 1px;
    background: linear-gradient(90deg, transparent, var(--border-color), transparent);
}
.receipt-diamond {
    width: 6px; height: 6px; background: var(--gold);
    transform: rotate(45deg) translateZ(0); flex-shrink: 0;
    animation: diamondSpin 3.5s linear infinite;
}
@keyframes diamondSpin { 0%,85% { transform: rotate(45deg);} 92% { transform: rotate(225deg) scale(1.4);} 100% { transform: rotate(405deg);} }

.receipt-row { display: flex; justify-content: space-between; gap: 1rem; padding: 0.5rem 0; font-size: 0.88rem; }
.receipt-row .lbl { color: var(--text-secondary); flex-shrink: 0; }
.receipt-row .val { color: var(--text-primary); font-family: var(--font-display); font-size: 1rem; text-align: right; }

.section-label {
    font-size: 0.72rem; letter-spacing: 0.14em; text-transform: uppercase;
    color: var(--gold); margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.5rem;
}
.section-label::before { content: ''; width: 3px; height: 3px; background: var(--gold); border-radius: 50%; }

.receipt-item-row {
    display: flex; justify-content: space-between; gap: 1rem; padding: 0.75rem 0.4rem;
    border-bottom: 1px dotted var(--border-color); font-size: 0.85rem;
    border-radius: 4px;
    transition: background 0.3s ease, transform 0.3s ease, padding 0.3s ease;
}
.receipt-item-row:hover { background: rgba(212,175,55,0.06); transform: translateX(4px); }
.receipt-item-row:last-child { border-bottom: none; }

.receipt-totals { margin-top: 1.2rem; }
.receipt-totals .rt { display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--text-secondary); padding: 0.4rem 0; }
.receipt-totals .rt.grand {
    border-top: 1px double var(--gold); margin-top: 0.8rem; padding-top: 1rem;
    font-size: 1.55rem; color: var(--gold); font-family: var(--font-display);
    text-shadow: 0 0 24px rgba(212,175,55,0.35);
}

.receipt-actions {
    display: flex; gap: 1rem; justify-content: center; margin-top: 2.6rem; flex-wrap: wrap;
    transform: translateZ(20px);
}
.receipt-actions .btn-lux { position: relative; overflow: hidden; transition: transform 0.25s cubic-bezier(.34,1.56,.64,1), box-shadow 0.25s ease; }
.receipt-actions .btn-lux:hover { transform: translateY(-3px); box-shadow: 0 14px 28px -10px rgba(212,175,55,0.35); }
.receipt-actions .btn-lux:active { transform: translateY(-1px) scale(0.97); }
.receipt-actions .btn-lux::after {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(115deg, transparent 40%, rgba(255,255,255,0.35) 50%, transparent 60%);
    transform: translateX(-120%);
    transition: transform 0.6s ease;
}
.receipt-actions .btn-lux:hover::after { transform: translateX(120%); }

/* confetti canvas */
#confettiCanvas { position: fixed; inset: 0; pointer-events: none; z-index: 5; }

@media (max-width: 640px) {
    .receipt-box { padding: 2.2rem 1.4rem; }
    .receipt-box:hover { transform: none !important; }
}

@media print {
    body * { visibility: hidden; }
    .receipt-box, .receipt-box * { visibility: visible; }
    .receipt-box { position: fixed; inset: 0; border: none; transform: none !important; animation: none !important; box-shadow: none !important; }
    .receipt-actions, .navbar-lux, .footer-lux, .scroll-progress, #confettiCanvas { display: none !important; }
}

@media (prefers-reduced-motion: reduce) {
    .receipt-box, .receipt-head *, .reveal, .receipt-divider, .seal, .receipt-corner {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
    }
}
</style>

<canvas id="confettiCanvas"></canvas>

<div class="receipt-stage">
    <div class="receipt-box" id="receiptContent">
        <span class="receipt-corner tl"></span><span class="receipt-corner tr"></span><span class="receipt-corner bl"></span><span class="receipt-corner br"></span>

        <div class="seal-wrap">
            <div class="seal">
                <svg viewBox="0 0 24 24"><path d="M4 12.5L9.5 18L20 6"/></svg>
            </div>
        </div>

        <div class="receipt-head">
            <span class="eyebrow">El Grande De La Torres</span>
            <h1>Order Receipt</h1>
            <p class="status-ok">✓ Order Confirmed</p>
        </div>

        <div class="r-meta">
            <div class="receipt-row reveal"><span class="lbl">Order Number</span><span class="val"><?= e($order['order_number']) ?></span></div>
            <div class="receipt-row reveal"><span class="lbl">Order Date</span><span class="val"><?= date('F j, Y — g:i A', strtotime($order['placed_at'])) ?></span></div>
            <div class="receipt-row reveal"><span class="lbl">Customer</span><span class="val"><?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></span></div>
            <div class="receipt-row reveal"><span class="lbl">Email</span><span class="val" style="font-size:0.85rem;"><?= e($customer['email']) ?></span></div>
        </div>

        <div class="receipt-divider reveal" style="animation-delay:1.2s;"><span class="receipt-diamond"></span></div>

        <?php if ($address): ?>
        <div class="r-ship reveal">
            <p class="section-label">Shipping To</p>
            <p style="font-size:0.9rem;"><?= e($address['recipient_name']) ?> · <?= e($address['phone_number']) ?></p>
            <p style="font-size:0.85rem; color:var(--text-secondary); margin-top:0.3rem;">
                <?= e($address['address_line1']) ?><?= $address['address_line2'] ? ', ' . e($address['address_line2']) : '' ?>, <?= e($address['city']) ?>, <?= e($address['province']) ?> <?= e($address['postal_code']) ?>
            </p>
        </div>
        <div class="receipt-divider reveal" style="animation-delay:1.3s;"><span class="receipt-diamond"></span></div>
        <?php endif; ?>

        <div class="r-items reveal">
            <p class="section-label">Items</p>
            <?php foreach ($order_items as $i => $item): ?>
            <div class="receipt-item-row reveal" style="--i:<?= $i ?>;">
                <span><?= e($item['product_name']) ?> <?= $item['size'] ? '(' . e($item['size']) . ')' : '' ?> × <?= $item['quantity'] ?></span>
                <span><?= CURRENCY_SYMBOL . number_format($item['line_total'], 2) ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="receipt-totals r-totals reveal">
            <div class="rt"><span>Subtotal</span><span><?= CURRENCY_SYMBOL . number_format($order['subtotal'], 2) ?></span></div>
            <?php if ($order['discount_amount'] > 0): ?><div class="rt"><span>Discount<?= $order['coupon_code'] ? ' (' . e($order['coupon_code']) . ')' : '' ?></span><span>&minus;<?= CURRENCY_SYMBOL . number_format($order['discount_amount'], 2) ?></span></div><?php endif; ?>
            <div class="rt"><span>Shipping</span><span><?= $order['shipping_fee'] > 0 ? CURRENCY_SYMBOL . number_format($order['shipping_fee'], 2) : 'Complimentary' ?></span></div>
            <div class="rt grand"><span>Total Paid</span><span><?= CURRENCY_SYMBOL . number_format($order['total_amount'], 2) ?></span></div>
        </div>

        <div class="receipt-divider reveal" style="animation-delay:1.6s;"><span class="receipt-diamond"></span></div>

        <div class="r-payment">
            <div class="receipt-row reveal"><span class="lbl">Payment Method</span><span class="val" style="text-transform:uppercase;"><?= e($payment_labels[$order['payment_method']] ?? $order['payment_method']) ?></span></div>
            <?php if ($order['payment_reference']): ?>
            <div class="receipt-row reveal"><span class="lbl">Reference No.</span><span class="val"><?= e($order['payment_reference']) ?></span></div>
            <?php endif; ?>
            <div class="receipt-row reveal"><span class="lbl">Payment Status</span><span class="val" style="color:<?= $is_paid ? '#8FC9A0' : 'var(--gold)' ?>; text-transform:capitalize;"><?= e($payment['payment_status'] ?? 'pending') ?></span></div>
        </div>
    </div>

    <div class="receipt-actions r-actions reveal">
        <button type="button" class="btn-lux" onclick="window.print()">Print Receipt</button>
        <a href="<?= SITE_URL ?>/customer/orders.php?view=<?= e($order['order_number']) ?>" class="btn-lux btn-lux-ghost">View Order</a>
        <a href="<?= SITE_URL ?>/clothing.php" class="btn-lux btn-lux-filled">Continue Shopping</a>
    </div>
</div>

<script>
(function () {
    /* ---------- 3D tilt on the receipt card ---------- */
    var box = document.getElementById('receiptContent');
    var stage = box.closest('.receipt-stage');
    var raf = null;

    function onMove(e) {
        var rect = box.getBoundingClientRect();
        var x = (e.clientX - rect.left) / rect.width;   // 0..1
        var y = (e.clientY - rect.top) / rect.height;    // 0..1
        var rotateY = (x - 0.5) * 10;   // left/right tilt
        var rotateX = (0.5 - y) * 8;    // up/down tilt
        if (raf) cancelAnimationFrame(raf);
        raf = requestAnimationFrame(function () {
            box.style.transform = 'rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg) translateY(0) scale(1.01)';
        });
    }
    function onLeave() {
        if (raf) cancelAnimationFrame(raf);
        box.style.transform = 'rotateX(0deg) rotateY(0deg) translateY(0) scale(1)';
    }
    if (window.matchMedia('(hover: hover)').matches && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        stage.addEventListener('mousemove', onMove);
        stage.addEventListener('mouseleave', onLeave);
    }

    /* ---------- confetti burst on load (only if order is paid/confirmed) ---------- */
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var canvas = document.getElementById('confettiCanvas');
    var ctx = canvas.getContext('2d');
    var W, H;
    function resize() { W = canvas.width = window.innerWidth; H = canvas.height = window.innerHeight; }
    resize();
    window.addEventListener('resize', resize);

    var colors = ['#D4AF37', '#8FC9A0', '#E8D9A0', '#ffffff'];
    var pieces = [];
    var COUNT = 90;
    for (var i = 0; i < COUNT; i++) {
        pieces.push({
            x: W / 2 + (Math.random() - 0.5) * 200,
            y: -20 - Math.random() * 200,
            r: 3 + Math.random() * 4,
            color: colors[Math.floor(Math.random() * colors.length)],
            vx: (Math.random() - 0.5) * 4,
            vy: 2 + Math.random() * 3,
            rot: Math.random() * 360,
            vr: (Math.random() - 0.5) * 8,
            life: 0,
            maxLife: 140 + Math.random() * 60
        });
    }

    var startDelay = 850; // sync with checkmark pop
    var started = null;

    function tick(ts) {
        if (!started) started = ts;
        var elapsed = ts - started;
        if (elapsed < startDelay) { requestAnimationFrame(tick); return; }

        ctx.clearRect(0, 0, W, H);
        var alive = false;
        for (var i = 0; i < pieces.length; i++) {
            var p = pieces[i];
            if (p.life >= p.maxLife) continue;
            alive = true;
            p.x += p.vx;
            p.y += p.vy;
            p.vy += 0.03;
            p.rot += p.vr;
            p.life++;
            var fade = 1 - Math.max(0, (p.life - p.maxLife * 0.7) / (p.maxLife * 0.3));
            ctx.save();
            ctx.globalAlpha = Math.max(0, fade);
            ctx.translate(p.x, p.y);
            ctx.rotate(p.rot * Math.PI / 180);
            ctx.fillStyle = p.color;
            ctx.fillRect(-p.r / 2, -p.r / 2, p.r, p.r * 1.6);
            ctx.restore();
        }
        if (alive) requestAnimationFrame(tick);
        else ctx.clearRect(0, 0, W, H);
    }
    requestAnimationFrame(tick);
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>