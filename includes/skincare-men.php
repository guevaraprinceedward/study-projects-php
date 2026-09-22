<?php
include_once 'hotel-config.php';
$activePage = 'services';
$tier = currentTier();

$suites = [];
$res = $conn->query("SELECT id, slug, name FROM suites ORDER BY id ASC");
while ($row = $res->fetch_assoc()) $suites[$row['slug']] = $row;

$productsBySuite = [];
$sr = $conn->query("SELECT * FROM services WHERE active = 1 AND type = 'skincare' AND gender = 'men' ORDER BY suite_id ASC, id ASC");
while ($row = $sr->fetch_assoc()) $productsBySuite[$row['suite_id']][] = $row;

$wantedSlug = $_GET['suite'] ?? array_key_first($suites);
if (!isset($suites[$wantedSlug])) $wantedSlug = array_key_first($suites);

function svcPrice($s, $tier) { return $tier === 'vip' ? (float)$s['vip_price'] : (float)$s['base_price']; }

function render_product_card($s, $tier, $suiteName) {
    $price = svcPrice($s, $tier);
    $maxQty = $s['stock'] !== null ? (int)$s['stock'] : 20;
    ob_start(); ?>
    <div class="svc-card">
        <img src="<?= htmlspecialchars($s['image_url']) ?>" alt="<?= htmlspecialchars($s['name']) ?>" loading="lazy">
        <div class="svc-body">
            <h4><?= htmlspecialchars($s['name']) ?></h4>
            <p><?= htmlspecialchars($s['description']) ?></p>
            <div class="svc-price">₱<?= number_format($price) ?> <span>/ item<?= $tier === 'vip' ? ' · VIP' : '' ?></span></div>
            <div class="svc-order" data-id="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['name']) ?>"
                 data-suite="<?= htmlspecialchars($suiteName) ?>" data-price="<?= $price ?>" data-unit="per_item">
                <div class="form-row">
                    <div class="field">
                        <label>Qty</label>
                        <div class="qty-control">
                            <button type="button" class="qty-minus">−</button>
                            <input type="number" class="svc-qty" value="1" min="1" max="<?= $maxQty ?>" readonly>
                            <button type="button" class="qty-plus">+</button>
                        </div>
                    </div>
                    <button type="button" class="btn-gold svc-add">Add to Order</button>
                </div>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Skincare Shop — Men — Nocturne Manila Bay</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="hotel-theme.css">
<style>
.svc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:20px;max-width:1240px;margin:0 auto;padding:0 32px}
.svc-card{background:var(--h-card);border:1px solid var(--h-line);border-radius:4px;overflow:hidden;display:flex;flex-direction:column}
.svc-card img{height:150px;object-fit:cover}
.svc-body{padding:18px;display:flex;flex-direction:column;gap:8px}
.svc-body h4{font-family:'Cormorant Garamond',serif;font-size:19px;color:var(--h-champagne)}
.svc-body p{font-size:12px;color:var(--h-muted);line-height:1.6}
.svc-price{font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--h-gold);font-weight:700}
.svc-price span{font-size:10px;font-family:'Jost',sans-serif;color:var(--h-muted);text-transform:uppercase}
.svc-order{border-top:1px solid var(--h-line);padding-top:12px;margin-top:4px;display:flex;flex-direction:column;gap:10px}
.svc-order .form-row{align-items:flex-end}
.svc-add{width:100%}
.cart-fab{position:fixed;bottom:26px;right:26px;z-index:300;background:var(--h-gold);color:#0a0a0c;border:none;border-radius:100px;padding:14px 22px;font-size:13px;font-weight:600;cursor:pointer;box-shadow:0 10px 30px rgba(0,0,0,0.4)}
.cart-drawer{position:fixed;top:0;right:0;height:100vh;width:360px;background:#0c0c0e;border-left:1px solid var(--h-line);z-index:301;transform:translateX(100%);transition:transform 0.35s ease;display:flex;flex-direction:column}
.cart-drawer.open{transform:translateX(0)}
.cart-head{display:flex;justify-content:space-between;align-items:center;padding:20px;border-bottom:1px solid var(--h-line)}
.cart-head h3{font-family:'Cormorant Garamond',serif;color:var(--h-champagne)}
.cart-head button{background:none;border:none;color:var(--h-muted);font-size:20px;cursor:pointer}
.cart-items{flex:1;overflow-y:auto;padding:16px 20px;display:flex;flex-direction:column;gap:12px}
.cart-item{display:flex;justify-content:space-between;gap:10px;font-size:12.5px;color:var(--h-text);border-bottom:1px solid var(--h-line);padding-bottom:10px}
.cart-remove{background:none;border:none;color:var(--h-bad);cursor:pointer;margin-left:6px}
.cart-empty{color:var(--h-muted);font-size:12.5px}
.cart-foot{padding:18px 20px;border-top:1px solid var(--h-line)}
</style>
</head>
<body>
<?php include_once 'sidebar.php'; ?>
<?php include_once 'topheader.php'; ?>

<section class="page-hero">
    <div class="sec-eyebrow"><a href="hotel-services.php" style="color:var(--h-gold-dim);text-decoration:none">Spa, Shop &amp; Entertainment</a> / Men</div>
    <h1>Skincare Shop — <em>Men</em></h1>
    <p>Grooming essentials and formulas — distinct to each residence.</p>
</section>

<div class="tabs" id="suiteTabs">
    <?php foreach ($suites as $slug => $s): ?>
    <button class="tab-btn <?= $slug === $wantedSlug ? 'active' : '' ?>" data-tab="<?= $slug ?>"><?= htmlspecialchars($s['name']) ?></button>
    <?php endforeach; ?>
</div>

<div style="padding-bottom:120px">
<?php foreach ($suites as $slug => $suiteRow): $sid = $suiteRow['id']; ?>
<div class="tab-panel <?= $slug === $wantedSlug ? 'active' : '' ?>" data-panel="<?= $slug ?>">
    <div class="svc-grid">
        <?php foreach (($productsBySuite[$sid] ?? []) as $s) echo render_product_card($s, $tier, $suiteRow['name']); ?>
        <?php if (empty($productsBySuite[$sid])): ?><p style="color:var(--h-muted);font-size:13px">No products yet for this residence.</p><?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<footer><p>© 2026 <span>Nocturne Manila Bay</span> — A Property of AyosCoffeeNegosyo Hospitality.</p></footer>

<button class="cart-fab" id="cartFab">🛎 Order (<span id="cartCount">0</span>)</button>
<div class="cart-drawer" id="cartDrawer">
    <div class="cart-head"><h3>Your Order</h3><button id="cartClose">&times;</button></div>
    <div class="cart-items" id="cartItems"><p class="cart-empty">No items yet.</p></div>
    <div class="cart-foot">
        <div class="summary-row total"><span>Total</span><span id="cartTotal">₱0</span></div>
        <?php if (!isHotelLoggedIn()): ?>
        <p style="font-size:12px;color:var(--h-muted);margin:12px 0">
            Please <a href="register.php?next=skincare-men.php" style="color:var(--h-gold)">create an account</a>
            or <a href="hotel-login.php?next=skincare-men.php" style="color:var(--h-gold)">log in</a> to complete your order.
        </p>
        <button class="btn-gold" style="width:100%" disabled>Confirm Order</button>
        <?php else: ?>
        <button class="btn-gold" style="width:100%;margin-top:14px" id="cartCheckout">Confirm Order</button>
        <?php endif; ?>
        <div id="cartMsg" style="font-size:12px;margin-top:8px"></div>
    </div>
</div>

<script>
document.querySelectorAll('#suiteTabs .tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#suiteTabs .tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.querySelector(`.tab-panel[data-panel="${btn.dataset.tab}"]`).classList.add('active');
        history.replaceState(null, '', `?suite=${btn.dataset.tab}`);
    });
});

let cart = [];
function renderCart() {
    const list = document.getElementById('cartItems');
    document.getElementById('cartCount').textContent = cart.length;
    if (!cart.length) { list.innerHTML = '<p class="cart-empty">No items yet.</p>'; document.getElementById('cartTotal').textContent = '₱0'; return; }
    let total = 0;
    list.innerHTML = cart.map((it, i) => {
        const lineTotal = it.price * it.qty;
        total += lineTotal;
        return `<div class="cart-item">
            <div><strong>${it.name}</strong><br><span style="color:var(--h-muted);font-size:11px">${it.suite} · ${it.qty} pc(s)</span></div>
            <div>₱${lineTotal.toLocaleString('en-PH')} <button type="button" data-i="${i}" class="cart-remove">✕</button></div>
        </div>`;
    }).join('');
    document.getElementById('cartTotal').textContent = '₱' + total.toLocaleString('en-PH');
    list.querySelectorAll('.cart-remove').forEach(b => b.addEventListener('click', () => { cart.splice(+b.dataset.i, 1); renderCart(); }));
}

document.querySelectorAll('.svc-order').forEach(box => {
    const minus = box.querySelector('.qty-minus'), plus = box.querySelector('.qty-plus'), qty = box.querySelector('.svc-qty');
    minus?.addEventListener('click', () => { qty.value = Math.max(1, parseInt(qty.value) - 1); });
    plus?.addEventListener('click', () => { qty.value = Math.min(parseInt(qty.max) || 99, parseInt(qty.value) + 1); });
    box.querySelector('.svc-add').addEventListener('click', () => {
        cart.push({
            id: box.dataset.id, name: box.dataset.name, suite: box.dataset.suite,
            price: parseFloat(box.dataset.price), unit: box.dataset.unit, qty: parseInt(qty.value),
        });
        renderCart();
        document.getElementById('cartDrawer').classList.add('open');
    });
});

document.getElementById('cartFab').addEventListener('click', () => document.getElementById('cartDrawer').classList.toggle('open'));
document.getElementById('cartClose').addEventListener('click', () => document.getElementById('cartDrawer').classList.remove('open'));

document.getElementById('cartCheckout')?.addEventListener('click', async () => {
    if (!cart.length) return;
    const msg = document.getElementById('cartMsg');
    msg.style.color = 'var(--h-muted)'; msg.textContent = 'Processing...';
    try {
        const r = await fetch('process-service-order.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: cart })
        });
        const data = await r.json();
        if (data.ok) {
            msg.style.color = '#7fa876';
            msg.textContent = `Order confirmed — reference ${data.ref}.`;
            cart = []; renderCart();
        } else {
            msg.style.color = '#c0574a';
            msg.textContent = data.error || 'Something went wrong.';
        }
    } catch (e) { msg.style.color = '#c0574a'; msg.textContent = 'Network error, please try again.'; }
});
</script>
</body>
</html>