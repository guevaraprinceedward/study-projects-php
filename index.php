<?php
// Safe session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user"])) {
    header("Location: log-in.php");
    exit();
}

include 'config.php';

// Fetch products
$result = $conn->query("SELECT * FROM products");
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// ── SAMPLE COFFEE PRODUCTS (shown only if database is empty) ──
if (empty($products)) {
    $products = [
        ["id"=>1,  "name"=>"Espresso",           "price"=>85,  "category"=>"mains",    "image"=>"", "description"=>"Bold and concentrated shot of pure espresso, brewed from freshly ground arabica beans."],
        ["id"=>2,  "name"=>"Americano",           "price"=>100, "category"=>"mains",    "image"=>"", "description"=>"Espresso diluted with hot water for a smooth, full-bodied black coffee experience."],
        ["id"=>3,  "name"=>"Cappuccino",          "price"=>130, "category"=>"mains",    "image"=>"", "description"=>"Equal parts espresso, steamed milk, and thick velvety foam — a classic Italian favourite."],
        ["id"=>4,  "name"=>"Caffè Latte",         "price"=>140, "category"=>"mains",    "image"=>"", "description"=>"Silky steamed milk poured over a double shot of espresso with a light layer of foam."],
        ["id"=>5,  "name"=>"Flat White",          "price"=>145, "category"=>"mains",    "image"=>"", "description"=>"Stronger and creamier than a latte — micro-foamed milk over a rich ristretto shot."],
        ["id"=>6,  "name"=>"Caramel Macchiato",   "price"=>155, "category"=>"mains",    "image"=>"", "description"=>"Vanilla-infused steamed milk, espresso, and a generous drizzle of rich caramel."],
        ["id"=>7,  "name"=>"Mocha",               "price"=>150, "category"=>"mains",    "image"=>"", "description"=>"Espresso blended with chocolate syrup and steamed milk, topped with whipped cream."],
        ["id"=>8,  "name"=>"Cold Brew",           "price"=>160, "category"=>"drinks",   "image"=>"", "description"=>"Steeped 18 hours in cold water for a smooth, naturally sweet concentrate over ice."],
        ["id"=>9,  "name"=>"Iced Americano",      "price"=>110, "category"=>"drinks",   "image"=>"", "description"=>"Double espresso pulled over ice and chilled water — clean, crisp, and refreshing."],
        ["id"=>10, "name"=>"Iced Caramel Latte",  "price"=>165, "category"=>"drinks",   "image"=>"", "description"=>"Chilled latte with caramel syrup and milk poured over a glass of crushed ice."],
        ["id"=>11, "name"=>"Matcha Latte",        "price"=>150, "category"=>"drinks",   "image"=>"", "description"=>"Ceremonial-grade matcha whisked with oat milk — earthy, smooth, and lightly sweet."],
        ["id"=>12, "name"=>"Croissant",           "price"=>95,  "category"=>"sides",    "image"=>"", "description"=>"Buttery, flaky all-butter croissant baked fresh every morning."],
        ["id"=>13, "name"=>"Banana Bread",        "price"=>80,  "category"=>"sides",    "image"=>"", "description"=>"Moist homemade banana bread with a golden crust, perfect with your morning coffee."],
        ["id"=>14, "name"=>"Blueberry Muffin",    "price"=>85,  "category"=>"sides",    "image"=>"", "description"=>"Fluffy muffin bursting with fresh blueberries and a crumbly sugar topping."],
        ["id"=>15, "name"=>"Tiramisu",            "price"=>130, "category"=>"desserts", "image"=>"", "description"=>"Classic Italian dessert with espresso-soaked ladyfingers and mascarpone cream."],
        ["id"=>16, "name"=>"Chocolate Lava Cake", "price"=>145, "category"=>"desserts", "image"=>"", "description"=>"Warm dark chocolate cake with a molten centre, served with a dusting of cocoa powder."],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Menu — My Restaurant</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0b0b09;
            --surface:   #131310;
            --card:      #1a1a16;
            --border:    #2c2c24;
            --gold:      #c9a84c;
            --gold-dim:  #8a6f2e;
            --green:     #4a7a3a;
            --green-lt:  #6aaa52;
            --cream:     #f0ead8;
            --muted:     #6b6b58;
            --text:      #e8e4d8;
            --shadow:    rgba(0,0,0,0.6);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Jost', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* ── Ambient background ── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 70% 50% at 10% 0%, rgba(201,168,76,0.06) 0%, transparent 55%),
                radial-gradient(ellipse 50% 70% at 90% 100%, rgba(74,122,58,0.07) 0%, transparent 55%);
            pointer-events: none;
            z-index: 0;
        }

        /* ─────────────────────── HEADER ─────────────────────── */
        header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(11,11,9,0.88);
            backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--border);
        }

        .header-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 32px;
            height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand-icon {
            width: 36px; height: 36px;
            border: 1px solid var(--gold-dim);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }

        .brand-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 600;
            color: var(--cream);
            letter-spacing: 0.04em;
        }

        .brand-name span { color: var(--gold); }

        nav { display: flex; align-items: center; gap: 6px; }

        nav a {
            font-size: 12.5px;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--muted);
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 3px;
            transition: color 0.2s, background 0.2s;
        }

        nav a:hover { color: var(--cream); background: rgba(255,255,255,0.04); }

        nav a.active { color: var(--gold); }

        .nav-cart {
            margin-left: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px !important;
            background: var(--green) !important;
            color: #fff !important;
            border-radius: 3px;
            font-weight: 500 !important;
            transition: background 0.2s !important;
        }

        .nav-cart:hover { background: var(--green-lt) !important; }

        .cart-badge {
            background: var(--gold);
            color: #000;
            font-size: 10px;
            font-weight: 700;
            min-width: 18px;
            height: 18px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
        }

        /* ─────────────────────── HERO ─────────────────────── */
        .hero {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 90px 32px 70px;
            max-width: 700px;
            margin: 0 auto;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 11px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 22px;
        }

        .hero-eyebrow::before,
        .hero-eyebrow::after {
            content: '';
            width: 28px;
            height: 1px;
            background: var(--gold-dim);
        }

        .hero h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(44px, 6vw, 72px);
            font-weight: 700;
            line-height: 1.08;
            color: var(--cream);
            letter-spacing: -0.01em;
            margin-bottom: 18px;
        }

        .hero h1 em {
            font-style: italic;
            color: var(--gold);
        }

        .hero p {
            font-size: 15px;
            color: var(--muted);
            line-height: 1.7;
            font-weight: 300;
        }

        /* ─────────────────────── FILTER BAR ─────────────────────── */
        .filter-bar {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 0 32px 48px;
            flex-wrap: wrap;
        }

        .filter-btn {
            font-family: 'Jost', sans-serif;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 8px 20px;
            border-radius: 100px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn:hover,
        .filter-btn.active {
            border-color: var(--gold);
            color: var(--gold);
            background: rgba(201,168,76,0.07);
        }

        /* ─────────────────────── MENU GRID ─────────────────────── */
        .menu-section {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 32px 100px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }

        /* ─────────────────────── CARD ─────────────────────── */
        .menu-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 4px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
            animation: cardIn 0.5s ease both;
        }

        .menu-card:hover {
            transform: translateY(-6px);
            border-color: var(--gold-dim);
            box-shadow: 0 20px 50px rgba(0,0,0,0.5), 0 0 0 1px rgba(201,168,76,0.08);
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Stagger animation delays */
        .menu-card:nth-child(1)  { animation-delay: 0.05s; }
        .menu-card:nth-child(2)  { animation-delay: 0.10s; }
        .menu-card:nth-child(3)  { animation-delay: 0.15s; }
        .menu-card:nth-child(4)  { animation-delay: 0.20s; }
        .menu-card:nth-child(5)  { animation-delay: 0.25s; }
        .menu-card:nth-child(6)  { animation-delay: 0.30s; }
        .menu-card:nth-child(7)  { animation-delay: 0.35s; }
        .menu-card:nth-child(8)  { animation-delay: 0.40s; }
        .menu-card:nth-child(9)  { animation-delay: 0.45s; }

        .card-img-wrap {
            position: relative;
            height: 210px;
            overflow: hidden;
            background: var(--surface);
        }

        .card-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
            display: block;
        }

        .menu-card:hover .card-img-wrap img {
            transform: scale(1.06);
        }

        /* Gold shimmer overlay on hover */
        .card-img-wrap::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                180deg,
                transparent 50%,
                rgba(0,0,0,0.5) 100%
            );
        }

        /* No image placeholder */
        .img-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: var(--muted);
            font-size: 13px;
            letter-spacing: 0.05em;
        }

        .img-placeholder svg {
            opacity: 0.3;
        }

        .card-body {
            padding: 22px 24px 24px;
            display: flex;
            flex-direction: column;
            flex: 1;
            gap: 6px;
        }

        .card-category {
            font-size: 10px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--gold);
            font-weight: 500;
        }

        .card-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 600;
            color: var(--cream);
            line-height: 1.2;
            margin-bottom: 4px;
        }

        .card-desc {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.6;
            font-weight: 300;
            flex: 1;
            margin-bottom: 10px;
        }

        .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 16px;
            border-top: 1px solid var(--border);
            margin-top: auto;
        }

        .card-price {
            font-family: 'Cormorant Garamond', serif;
            font-size: 26px;
            font-weight: 700;
            color: var(--gold);
            letter-spacing: -0.01em;
        }

        .card-price span {
            font-size: 14px;
            font-weight: 400;
            font-family: 'Jost', sans-serif;
            color: var(--muted);
            margin-right: 2px;
        }

        .add-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: var(--green);
            border: none;
            border-radius: 3px;
            font-family: 'Jost', sans-serif;
            font-size: 12.5px;
            font-weight: 500;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #fff;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
            position: relative;
            overflow: hidden;
        }

        .add-btn:hover  { background: var(--green-lt); }
        .add-btn:active { transform: scale(0.95); }

        /* Ripple on click */
        .add-btn .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: scale(0);
            animation: ripple 0.5s linear;
            pointer-events: none;
        }

        @keyframes ripple {
            to { transform: scale(4); opacity: 0; }
        }

        /* ─── Added feedback ─── */
        .add-btn.added {
            background: var(--gold);
            color: #000;
        }

        /* ─────────────────────── EMPTY STATE ─────────────────────── */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--muted);
        }

        .empty-state svg { opacity: 0.2; margin-bottom: 20px; }

        .empty-state h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 26px;
            color: var(--text);
            margin-bottom: 8px;
        }

        /* ─────────────────────── TOAST ─────────────────────── */
        #toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 999;
            background: var(--card);
            border: 1px solid var(--gold-dim);
            border-radius: 4px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: var(--cream);
            box-shadow: 0 8px 32px rgba(0,0,0,0.5);
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        #toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        #toast svg { color: var(--green-lt); flex-shrink: 0; }

        /* ─────────────────────── FOOTER ─────────────────────── */
        footer {
            position: relative;
            z-index: 1;
            border-top: 1px solid var(--border);
            padding: 30px 32px;
            text-align: center;
        }

        footer p {
            font-size: 12px;
            color: var(--muted);
            letter-spacing: 0.06em;
        }

        footer p span { color: var(--gold-dim); }

        /* ─────────────────────── RESPONSIVE ─────────────────────── */
        @media (max-width: 640px) {
            .header-inner { padding: 0 16px; }
            .brand-name { font-size: 18px; }
            nav a:not(.nav-cart) { display: none; }
            .hero { padding: 60px 20px 50px; }
            .menu-section { padding: 0 16px 60px; }
            .menu-grid { grid-template-columns: 1fr; gap: 16px; }
        }
    </style>
</head>
<body>

<!-- ═══════════════════════ HEADER ═══════════════════════ -->
<header>
    <div class="header-inner">
        <a href="index.php" class="brand">
            <div class="brand-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                     stroke="#c9a84c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 11l19-9-9 19-2-8-8-2z"/>
                </svg>
            </div>
            <span class="brand-name">My <span>AyosCoffeeNegosyo</span></span>
        </a>

        <nav>
            <a href="index.php" class="active">Menu</a>
            <a href="profile.php">Profile</a>
            <a href="log-out.php">Logout</a>
            <a href="cart.php" class="nav-cart">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                Cart
                <span class="cart-badge" id="cartCount">0</span>
            </a>
        </nav>
    </div>
</header>

<!-- ═══════════════════════ HERO ═══════════════════════ -->
<section class="hero">
    <div class="hero-eyebrow">Today's Selection</div>
    <h1>Our <em>Menu</em></h1>
    <p>Carefully crafted dishes made with the finest ingredients.<br>Order your favorites and enjoy a taste worth remembering.</p>
</section>

<!-- ═══════════════════════ FILTER BAR ═══════════════════════ -->
<div class="filter-bar">
    <button class="filter-btn active" data-filter="all">All Items</button>
    <button class="filter-btn" data-filter="mains">Mains</button>
    <button class="filter-btn" data-filter="sides">Sides</button>
    <button class="filter-btn" data-filter="drinks">Drinks</button>
    <button class="filter-btn" data-filter="desserts">Desserts</button>
</div>

<!-- ═══════════════════════ MENU GRID ═══════════════════════ -->
<main class="menu-section">
    <?php if (empty($products)): ?>
        <div class="empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 11l19-9-9 19-2-8-8-2z"/>
            </svg>
            <h3>No dishes available yet.</h3>
            <p>Check back soon — the kitchen is preparing something special.</p>
        </div>
    <?php else: ?>
        <div class="menu-grid" id="menuGrid">
            <?php foreach ($products as $row): ?>
            <div class="menu-card" data-category="<?= htmlspecialchars($row['category'] ?? 'mains') ?>">

                <div class="card-img-wrap">
                    <?php if (!empty($row['image'])): ?>
                        <img
                            src="<?= htmlspecialchars($row['image']) ?>"
                            alt="<?= htmlspecialchars($row['name']) ?>"
                            loading="lazy"
                            onerror="this.parentElement.innerHTML='<div class=\'img-placeholder\'><svg width=\'40\' height=\'40\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'1\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'M3 11l19-9-9 19-2-8-8-2z\'/></svg><span>No Image</span></div>'"
                        >
                    <?php else: ?>
                        <div class="img-placeholder">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 11l19-9-9 19-2-8-8-2z"/>
                            </svg>
                            <span>No Image</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-body">
                    <?php if (!empty($row['category'])): ?>
                        <div class="card-category"><?= htmlspecialchars($row['category']) ?></div>
                    <?php endif; ?>

                    <h3 class="card-name"><?= htmlspecialchars($row['name']) ?></h3>

                    <?php if (!empty($row['description'])): ?>
                        <p class="card-desc"><?= htmlspecialchars($row['description']) ?></p>
                    <?php else: ?>
                        <p class="card-desc">A signature dish from our kitchen, prepared fresh to order.</p>
                    <?php endif; ?>

                    <div class="card-footer">
                        <div class="card-price">
                            <span>₱</span><?= number_format((float)$row['price'], 2) ?>
                        </div>
                        <button
                            class="add-btn"
                            onclick="addToCart(<?= (int)$row['id'] ?>, this)"
                            data-id="<?= (int)$row['id'] ?>"
                        >
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            Add
                        </button>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<!-- ═══════════════════════ TOAST ═══════════════════════ -->
<div id="toast">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="20 6 9 17 4 12"/>
    </svg>
    <span id="toastMsg">Added to cart!</span>
</div>

<!-- ═══════════════════════ FOOTER ═══════════════════════ -->
<footer>
    <p>© 2026 <span>My Restaurant</span> — All rights reserved.</p>
</footer>

<script>
// ─── Cart badge — kuha sa SESSION via cart_handler.php ────────────────────
function updateCartBadge() {
    fetch('cart_handler.php?count=1')
        .then(r => r.json())
        .then(data => {
            document.getElementById('cartCount').textContent = data.count || 0;
        })
        .catch(() => {});
}

updateCartBadge(); // ipakita agad sa page load

// ─── Add to cart ──────────────────────────────────────────────────────────
function addToCart(id, btn) {
    // Ripple
    const ripple = document.createElement('span');
    ripple.className = 'ripple';
    const rect = btn.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    ripple.style.cssText = `width:${size}px;height:${size}px;left:${(rect.width-size)/2}px;top:${(rect.height-size)/2}px`;
    btn.appendChild(ripple);
    setTimeout(() => ripple.remove(), 600);

    btn.disabled = true;

    // ✅ I-save sa PHP SESSION — gagamitin ng cart.php
    fetch(`cart_handler.php?add=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Update badge gamit ang count mula sa server
                document.getElementById('cartCount').textContent = data.count || 0;

                // Button feedback
                const orig = btn.innerHTML;
                btn.classList.add('added');
                btn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Added`;
                setTimeout(() => {
                    btn.classList.remove('added');
                    btn.innerHTML = orig;
                    btn.disabled = false;
                }, 1400);

                showToast('Added to cart!');
            } else {
                btn.disabled = false;
                showToast('Error — please try again.');
            }
        })
        .catch(() => {
            btn.disabled = false;
            showToast('Error — please try again.');
        });
}

// ─── Toast ────────────────────────────────────────────────────────────────
let toastTimer;
function showToast(msg) {
    const toast = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 2500);
}

// ─── Filter buttons ───────────────────────────────────────────────────────
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const filter = btn.dataset.filter;
        document.querySelectorAll('.menu-card').forEach(card => {
            const match = filter === 'all' || card.dataset.category === filter;
            card.style.display = match ? '' : 'none';
        });
    });
});
</script>

</body>
</html>
