<?php
include_once 'hotel-config.php';
$activePage = 'services';
$tier = currentTier();

$suites = [];
$res = $conn->query("SELECT id, slug, name FROM suites ORDER BY id ASC");
while ($row = $res->fetch_assoc()) $suites[$row['slug']] = $row;

$productsBySuite = [];
$sr = $conn->query("SELECT * FROM services WHERE active = 1 AND type = 'skincare' ORDER BY suite_id ASC, gender ASC, id ASC");
while ($row = $sr->fetch_assoc()) $productsBySuite[$row['suite_id']][] = $row;

$wantedSlug = $_GET['suite'] ?? array_key_first($suites);
if (!isset($suites[$wantedSlug])) $wantedSlug = array_key_first($suites);

function svcPrice($s, $tier) { return $tier === 'vip' ? (float)$s['vip_price'] : (float)$s['base_price']; }

function render_product_card($s, $tier, $suiteName) {
    $price = svcPrice($s, $tier);
    $maxQty = $s['stock'] !== null ? (int)$s['stock'] : 20;
    ob_start(); ?>
    <div class="svc-card" data-gender="<?= htmlspecialchars($s['gender']) ?>">
        <div class="svc-media">
            <img src="<?= htmlspecialchars($s['image_url']) ?>" alt="<?= htmlspecialchars($s['name']) ?>" loading="lazy">
            <span class="svc-corner"><?= ucfirst($s['gender']) ?></span>
        </div>
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
                    
                </div>
                <button type="button" class="btn-gold svc-add">Add to Order</button>
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
<title>Skincare Shop — Nocturne Manila Bay</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="hotel-theme.css">
<noscript><style>.h-loader{display:none!important}body{opacity:1!important}</style></noscript>
<style>
.gender-toggle{display:flex;gap:6px;margin:0 auto 24px;max-width:1240px;padding:0 32px}
.gt-btn{padding:8px 20px;border:1px solid var(--h-line);border-radius:100px;background:transparent;color:var(--h-muted);font-size:11.5px;text-transform:uppercase;letter-spacing:0.06em;cursor:pointer;transition:all 0.2s}
.gt-btn:hover{color:var(--h-champagne)}
.gt-btn.active{background:var(--h-gold);border-color:var(--h-gold);color:#0a0a0c}
.svc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:20px;max-width:1240px;margin:0 auto;padding:0 32px}
.svc-card{background:var(--h-card);border:1px solid var(--h-line);border-radius:4px;overflow:hidden;display:flex;flex-direction:column}
.svc-card img{height:150px;object-fit:cover}
.svc-body{padding:18px;display:flex;flex-direction:column;gap:8px}
.svc-body h4{font-family:'Cormorant Garamond',serif;font-size:19px;color:var(--h-champagne)}
.svc-body p{font-size:12px;color:var(--h-muted);line-height:1.6}
.svc-price{font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--h-gold);font-weight:700}
.svc-price span{font-size:10px;font-family:'Jost',sans-serif;color:var(--h-muted);text-transform:uppercase}
.svc-order{border-top:1px solid var(--h-line);padding-top:20px;margin-top:auto;display:flex;flex-direction:column;gap:16px}
.svc-order .form-row{align-items:flex-end}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
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

/* ══════════ PREMIUM ENHANCEMENT: loader, luxury cards, 3D hover, reveal ══════════ */
/* IMPORTANT: fade only the page content, never the loader itself — the loader
   is also a child of <body>, so giving <body> opacity:0 would hide the loader
   too (opacity compounds through descendants), leaving a blank black screen
   until everything snaps in together. */
body > *:not(.h-loader){opacity:0;transition:opacity .6s ease}
body.h-loaded > *:not(.h-loader){opacity:1}
.h-loader{position:fixed;inset:0;z-index:9999;background:var(--h-bg);display:flex;align-items:center;justify-content:center;transition:opacity .7s ease, visibility 0s linear .7s}
.h-loader.done{opacity:0;visibility:hidden}
.h-loader-ring{position:relative;width:64px;height:64px}
.h-loader-ring svg{width:100%;height:100%;animation:hlSpin 1.6s linear infinite}
.h-loader-ring circle{fill:none;stroke:var(--h-gold-dim);stroke-width:1;opacity:.3}
.h-loader-ring .arc{stroke:var(--h-gold);stroke-width:1.4;stroke-linecap:round;stroke-dasharray:40 220}
@keyframes hlSpin{to{transform:rotate(360deg)}}
.h-loader-mark{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-size:15px;color:var(--h-gold);letter-spacing:.04em}
.h-loader-word{position:absolute;top:calc(50% + 52px);left:50%;transform:translateX(-50%);font-family:'Jost',sans-serif;font-size:10px;letter-spacing:.32em;text-transform:uppercase;color:var(--h-muted);white-space:nowrap}

.svc-card{position:relative;background:linear-gradient(165deg,rgba(207,167,107,.05),var(--h-card) 55%) !important;border:1px solid var(--h-line);border-radius:6px;overflow:hidden;display:flex;flex-direction:column;
  transform-style:preserve-3d;will-change:transform;
  transition:transform .45s cubic-bezier(.2,.8,.2,1),border-color .35s ease,box-shadow .45s ease;
  opacity:0;transform:translateY(28px)}
.svc-card.in-view{opacity:1;transform:translateY(0)}
.svc-card:hover{border-color:var(--h-gold-dim);box-shadow:0 36px 70px -24px rgba(0,0,0,.7)}
.svc-media{position:relative;aspect-ratio:4/3;overflow:hidden}
.svc-card img{width:100%;height:100%;object-fit:cover;transition:transform .8s cubic-bezier(.2,.8,.2,1);filter:saturate(1.03)}
.svc-card:hover img{transform:scale(1.08)}
.svc-media::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 55%,rgba(7,7,10,.75))}
.svc-corner{position:absolute;top:12px;left:12px;z-index:2;font-family:'Jost',sans-serif;font-size:9px;letter-spacing:.14em;text-transform:uppercase;color:var(--h-gold);background:rgba(7,7,10,.62);border:1px solid rgba(207,167,107,.35);backdrop-filter:blur(6px);padding:5px 11px;border-radius:100px}
.svc-body{padding:20px 20px 22px;transform:translateZ(18px);flex:1;display:flex;flex-direction:column}
.svc-body h4{position:relative}
.svc-body h4::after{content:'';display:block;width:26px;height:1px;background:var(--h-gold-dim);margin-top:9px}
.svc-price{position:relative;padding-top:13px;margin-top:2px;border-top:1px solid var(--h-line)}
.svc-add{position:relative;overflow:hidden;letter-spacing:.12em}
.svc-add::after{content:'';position:absolute;top:0;left:-60%;width:35%;height:100%;background:linear-gradient(115deg,transparent,rgba(255,255,255,.35),transparent);transform:skewX(-18deg);transition:left .6s ease}
.svc-add:hover::after{left:130%}

.page-hero{opacity:0;transform:translateY(18px);transition:opacity .9s cubic-bezier(.2,.8,.2,1), transform .9s cubic-bezier(.2,.8,.2,1)}
body.h-loaded .page-hero{opacity:1;transform:translateY(0)}
.gender-toggle,#suiteTabs{opacity:0;transform:translateY(14px);transition:opacity .8s cubic-bezier(.2,.8,.2,1) .15s, transform .8s cubic-bezier(.2,.8,.2,1) .15s}
body.h-loaded .gender-toggle,body.h-loaded #suiteTabs{opacity:1;transform:translateY(0)}

@media (prefers-reduced-motion: reduce){
    .h-loader{display:none !important}
    body > *:not(.h-loader){opacity:1 !important;transition:none !important}
    .svc-card,.page-hero,.gender-toggle,#suiteTabs{opacity:1 !important;transform:none !important;transition:none !important}
}
</style>
</head>
<body>

<div class="h-loader" id="hLoader" aria-hidden="true">
    <div class="h-loader-ring">
        <svg viewBox="0 0 64 64">
            <circle cx="32" cy="32" r="28"></circle>
            <circle class="arc" cx="32" cy="32" r="28"></circle>
        </svg>
        <div class="h-loader-mark">N</div>
    </div>
    <div class="h-loader-word">Skincare</div>
</div>

<?php include_once 'sidebar.php'; ?>
<?php include_once 'topheader.php'; ?>

<section class="page-hero">
    <div class="sec-eyebrow"><a href="hotel-services.php" style="color:var(--h-gold-dim);text-decoration:none">Spa, Shop &amp; Entertainment</a> / Skincare</div>
    <h1>Skincare <em>Shop</em></h1>
    <p>Choose your collection, then your residence — every range is exclusive to Nocturne.</p>
</section>

<div class="gender-toggle" style="margin-top:30px">
    <button class="gt-btn active" data-gender="all">All</button>
    <button class="gt-btn" data-gender="women">Women</button>
    <button class="gt-btn" data-gender="men">Men</button>
</div>

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
            Please <a href="register.php?next=skincare.php" style="color:var(--h-gold)">create an account</a>
            or <a href="hotel-login.php?next=skincare.php" style="color:var(--h-gold)">log in</a> to complete your order.
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

document.querySelectorAll('.gt-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.gt-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const g = btn.dataset.gender;
        document.querySelectorAll('.svc-card').forEach(card => {
            card.style.display = (g === 'all' || card.dataset.gender === g) ? '' : 'none';
        });
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

<script>
(function(){
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function finish(){
        document.body.classList.add('h-loaded');
        var l = document.getElementById('hLoader');
        if (l){ l.classList.add('done'); setTimeout(function(){ l.remove(); }, 750); }
    }
    if (reduced) finish();
    else { window.addEventListener('load', function(){ setTimeout(finish, 1100); }); setTimeout(finish, 2800); }

    var cards = document.querySelectorAll('.svc-card');
    if (reduced || !('IntersectionObserver' in window)) {
        cards.forEach(function(c){ c.classList.add('in-view'); });
    } else {
        var io = new IntersectionObserver(function(entries){
            entries.forEach(function(en){ if (en.isIntersecting){ en.target.classList.add('in-view'); io.unobserve(en.target); } });
        }, { threshold: .12, rootMargin: '0px 0px -40px 0px' });
        cards.forEach(function(c){ io.observe(c); });
    }

    if (!reduced && window.matchMedia('(hover:hover) and (pointer:fine)').matches) {
        cards.forEach(function(card){
            card.addEventListener('mousemove', function(e){
                /* Never tilt while the pointer is over the order form — native
                   date/time pickers render their icon in the wrong spot under
                   a 3D transform, making them hard or impossible to click. */
                if (e.target.closest('.svc-order')) { card.style.transform = ''; return; }
                var r = card.getBoundingClientRect();
                var px = (e.clientX-r.left)/r.width - .5, py = (e.clientY-r.top)/r.height - .5;
                card.style.transform = 'perspective(1000px) rotateY('+(px*6).toFixed(1)+'deg) rotateX('+(-py*6).toFixed(1)+'deg) translateY(-4px)';
            });
            card.addEventListener('mouseleave', function(){ card.style.transform = ''; });
        });
    }
})();
</script>
</body>
</html>