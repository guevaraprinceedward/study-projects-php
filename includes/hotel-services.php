<?php
include_once 'hotel-config.php';
$activePage = 'services';
$tier = currentTier();

$suites = [];
$res = $conn->query("SELECT id, slug, name FROM suites ORDER BY id ASC");
while ($row = $res->fetch_assoc()) $suites[$row['slug']] = $row;

$spaBySuite = [];
$sr = $conn->query("SELECT * FROM services WHERE active = 1 AND type = 'spa' ORDER BY suite_id ASC, id ASC");
while ($row = $sr->fetch_assoc()) $spaBySuite[$row['suite_id']][] = $row;

$addonsByService = [];
$ar = $conn->query("SELECT * FROM service_addons WHERE active = 1 ORDER BY id ASC");
while ($row = $ar->fetch_assoc()) $addonsByService[$row['service_id']][] = $row;

$today = date('Y-m-d');
$offersBySuite = [];
$or = $conn->query("SELECT * FROM offers WHERE active=1 AND valid_from <= '$today' AND valid_to >= '$today'
                     AND (audience='all' OR audience='$tier')");
while ($row = $or->fetch_assoc()) $offersBySuite[$row['suite_id'] ?? 'all'][] = $row;

$wantedSlug = $_GET['suite'] ?? array_key_first($suites);
if (!isset($suites[$wantedSlug])) $wantedSlug = array_key_first($suites);

function svcPrice($s, $tier) { return $tier === 'vip' ? (float)$s['vip_price'] : (float)$s['base_price']; }
function addonPrice($a, $tier) { return $tier === 'vip' ? (float)$a['vip_price'] : (float)$a['price']; }

function render_spa_card($s, $tier, $suiteName, $addons) {
    $price = svcPrice($s, $tier);
    ob_start(); ?>
    <div class="svc-card">
        <img src="<?= htmlspecialchars($s['image_url']) ?>" alt="<?= htmlspecialchars($s['name']) ?>" loading="lazy">
        <div class="svc-body">
            <h4><?= htmlspecialchars($s['name']) ?></h4>
            <p><?= htmlspecialchars($s['description']) ?></p>
            <?php if ($s['duration_min']): ?><div class="svc-meta"><?= (int)$s['duration_min'] ?> min</div><?php endif; ?>
            <div class="svc-price">₱<?= number_format($price) ?> <span>/ session<?= $tier === 'vip' ? ' · VIP' : '' ?></span></div>
            <div class="svc-order" data-id="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['name']) ?>"
                 data-suite="<?= htmlspecialchars($suiteName) ?>" data-price="<?= $price ?>" data-unit="per_session">
                <div class="form-row">
                    <div class="field"><label>Date</label><input type="date" class="svc-date" min="<?= date('Y-m-d') ?>"></div>
                    <div class="field"><label>Time</label><input type="time" class="svc-time"></div>
                </div>
                <?php if (!empty($addons)): ?>
                <div class="addon-block">
                    <label class="addon-label">Optional Add-ons</label>
                    <div class="addon-list">
                        <?php foreach ($addons as $a): $ap = addonPrice($a, $tier); ?>
                        <label class="addon-item">
                            <input type="checkbox" class="svc-addon" value="<?= $a['id'] ?>" data-name="<?= htmlspecialchars($a['name']) ?>" data-price="<?= $ap ?>">
                            <span class="addon-name"><?= htmlspecialchars($a['name']) ?><br><small><?= htmlspecialchars($a['description']) ?></small></span>
                            <span class="addon-price">+₱<?= number_format($ap) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="form-row">
                    <div class="field">
                        <label>Guests</label>
                        <div class="qty-control">
                            <button type="button" class="qty-minus">−</button>
                            <input type="number" class="svc-qty" value="1" min="1" max="12" readonly>
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
<title>Spa, Shop &amp; Entertainment — Nocturne Manila Bay</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="hotel-theme.css">
<style>
.svc-offers{max-width:1240px;margin:0 auto 24px;padding:0 32px;display:flex;gap:8px;flex-wrap:wrap}
.svc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:20px}
.svc-card{background:var(--h-card);border:1px solid var(--h-line);border-radius:4px;overflow:hidden;display:flex;flex-direction:column}
.svc-card img{height:150px;object-fit:cover}
.svc-body{padding:18px;display:flex;flex-direction:column;gap:8px}
.svc-body h4{font-family:'Cormorant Garamond',serif;font-size:19px;color:var(--h-champagne)}
.svc-body p{font-size:12px;color:var(--h-muted);line-height:1.6}
.svc-meta{font-size:11px;color:var(--h-gold-dim);text-transform:uppercase;letter-spacing:0.05em}
.svc-price{font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--h-gold);font-weight:700}
.svc-price span{font-size:10px;font-family:'Jost',sans-serif;color:var(--h-muted);text-transform:uppercase}
.svc-order{border-top:1px solid var(--h-line);padding-top:12px;margin-top:4px;display:flex;flex-direction:column;gap:10px}
.svc-order .form-row{align-items:flex-end}
.svc-add{width:100%; margin-top:20px; }
.addon-block{display:flex;flex-direction:column;gap:6px}
.addon-label{font-size:10px;letter-spacing:0.12em;text-transform:uppercase;color:var(--h-gold-dim)}
.addon-list{display:flex;flex-direction:column;gap:6px}
.addon-item{display:flex;align-items:flex-start;gap:8px;font-size:11.5px;color:var(--h-text);padding:8px;border:1px solid var(--h-line);border-radius:3px;cursor:pointer}
.addon-item:hover{border-color:var(--h-gold-dim)}
.addon-item input{margin-top:2px}
.addon-name{flex:1}
.addon-name small{color:var(--h-muted);font-weight:300}
.addon-price{color:var(--h-gold);white-space:nowrap;font-weight:600}

.cat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;max-width:1240px;margin:0 auto;padding:0 32px 40px}
.cat-card{position:relative;border-radius:4px;overflow:hidden;border:1px solid var(--h-line);min-height:220px;display:flex;flex-direction:column;justify-content:flex-end;text-decoration:none;transition:border-color 0.2s}
.cat-card:hover{border-color:var(--h-gold-dim)}
.cat-card img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0.45;transition:opacity 0.3s}
.cat-card:hover img{opacity:0.6}
.cat-card::after{content:'';position:absolute;inset:0;background:linear-gradient(0deg,rgba(7,7,10,0.95) 15%,rgba(7,7,10,0.15) 85%)}
.cat-card-body{position:relative;z-index:1;padding:22px}
.cat-card h4{font-family:'Cormorant Garamond',serif;font-size:22px;color:var(--h-champagne);margin-bottom:6px}
.cat-card p{font-size:12px;color:var(--h-muted);line-height:1.6}
.cat-card .cat-cta{margin-top:10px;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:var(--h-gold);display:inline-flex;align-items:center;gap:6px}

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
    <div class="sec-eyebrow">In the House</div>
    <h1>Spa, <em>Shop</em> &amp; Entertainment</h1>
    <p>Each residence keeps its own spa menu, skincare shop, and entertainment lounge — priced automatically for your Regular or Nocturne Noir rate.</p>
</section>

<div class="tabs" id="suiteTabs" style="margin-top:30px">
    <?php foreach ($suites as $slug => $s): ?>
    <button class="tab-btn <?= $slug === $wantedSlug ? 'active' : '' ?>" data-tab="<?= $slug ?>"><?= htmlspecialchars($s['name']) ?></button>
    <?php endforeach; ?>
</div>

<div style="max-width:1240px;margin:0 auto;padding:0 32px 40px">
<?php foreach ($suites as $slug => $suiteRow): $sid = $suiteRow['id']; ?>
<div class="tab-panel <?= $slug === $wantedSlug ? 'active' : '' ?>" data-panel="<?= $slug ?>">

    <?php $suiteOffers = array_merge($offersBySuite['all'] ?? [], $offersBySuite[$sid] ?? []);
    if ($suiteOffers): ?>
    <div class="svc-offers">
        <?php foreach ($suiteOffers as $o): ?>
        <span class="badge-offer"><?= htmlspecialchars($o['title']) ?> — <?= rtrim(rtrim(number_format($o['discount_percent'], 1), '0'), '.') ?>% off</span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="sec-head" style="padding:0 0 20px;margin-bottom:20px">
        <div><div class="sec-eyebrow">Spa</div><h2>Treatments &amp; Rituals</h2></div>
        <p>Book a treatment, then choose optional add-on procedures where available.</p>
    </div>
    <div class="svc-grid">
        <?php foreach (($spaBySuite[$sid] ?? []) as $s) echo render_spa_card($s, $tier, $suiteRow['name'], $addonsByService[$s['id']] ?? []); ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<div class="sec-head" style="max-width:1240px;margin:0 auto;padding:0 32px;margin-bottom:24px">
    <div><div class="sec-eyebrow">Shop</div><h2>Skincare Shop</h2></div>
    <p>Women's and men's collections in one place — every residence carries its own range.</p>
</div>
<div class="cat-grid" style="margin-bottom:50px">
    <a href="skincare.php" class="cat-card">
        <img src="https://images.unsplash.com/photo-1556228720-195a672e8a03?w=800" alt="Skincare Shop">
        <div class="cat-card-body">
            <h4>Skincare Shop</h4>
            <p>Serums, creams, and grooming essentials — choose Women or Men inside.</p>
            <span class="cat-cta">Browse the shop →</span>
        </div>
    </a>
</div>

<div class="sec-head" style="max-width:1240px;margin:0 auto;padding:0 32px;margin-bottom:24px">
    <div><div class="sec-eyebrow">Entertainment</div><h2>Choose Your Evening</h2></div>
    <p>Entertainment, local snacks, or the mini bar — each has its own dedicated menu.</p>
</div>
<div class="cat-grid" style="padding-bottom:110px">
    <a href="entertainment.php" class="cat-card">
        <img src="https://images.unsplash.com/photo-1596838132731-3301c3fd4317?w=800" alt="Entertainment">
        <div class="cat-card-body"><h4>Entertainment</h4><p>Choose between billiards or the casino floor.</p><span class="cat-cta">Enter →</span></div>
    </a>
    <a href="snacks.php" class="cat-card">
        <img src="https://images.unsplash.com/photo-1567234669003-dce7a7a88821?w=800" alt="Local Snacks">
        <div class="cat-card-body"><h4>Local Snacks</h4><p>Sandwiches and drinks, delivered to your suite.</p><span class="cat-cta">See the menu →</span></div>
    </a>
    <a href="minibar.php" class="cat-card">
        <img src="https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=800" alt="Mini Bar">
        <div class="cat-card-body"><h4>Mini Bar &amp; Disco</h4><p>Wine, champagne, and a private late-night lounge.</p><span class="cat-cta">Enter the lounge →</span></div>
    </a>
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
            Please <a href="register.php?next=hotel-services.php" style="color:var(--h-gold)">create an account</a>
            or <a href="hotel-login.php?next=hotel-services.php" style="color:var(--h-gold)">log in</a> to complete your order.
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
        const addonsTotal = (it.addons || []).reduce((s, a) => s + a.price, 0);
        const lineTotal = (it.price + addonsTotal) * it.qty;
        total += lineTotal;
        const unitTxt = it.unit === 'per_hour' ? 'hr(s)' : it.unit === 'per_item' ? 'pc(s)' : 'pax';
        const addonTxt = (it.addons || []).length ? `<br><span style="color:var(--h-gold);font-size:10.5px">+ ${it.addons.map(a=>a.name).join(', ')}</span>` : '';
        return `<div class="cart-item">
            <div><strong>${it.name}</strong><br><span style="color:var(--h-muted);font-size:11px">${it.suite} · ${it.qty} ${unitTxt}${it.date ? ' · ' + it.date + ' ' + (it.time || '') : ''}</span>${addonTxt}</div>
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
        const dateEl = box.querySelector('.svc-date'), timeEl = box.querySelector('.svc-time');
        const addons = Array.from(box.querySelectorAll('.svc-addon:checked')).map(cb => ({
            id: cb.value, name: cb.dataset.name, price: parseFloat(cb.dataset.price)
        }));
        cart.push({
            id: box.dataset.id, name: box.dataset.name, suite: box.dataset.suite,
            price: parseFloat(box.dataset.price), unit: box.dataset.unit,
            qty: parseInt(qty.value), date: dateEl ? dateEl.value : null, time: timeEl ? timeEl.value : null,
            addons,
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