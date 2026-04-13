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

// ── HANDLE REMOVE ──────────────────────────────────────────────────────────
if (isset($_GET['remove'])) {
    $removeId = (int)$_GET['remove'];
    unset($_SESSION['cart'][$removeId]);
    header("Location: cart.php");
    exit();
}

// ── HANDLE QTY UPDATE ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    foreach ($_POST['qty'] as $id => $qty) {
        $qty = (int)$qty;
        if ($qty <= 0) {
            unset($_SESSION['cart'][(int)$id]);
        } else {
            $_SESSION['cart'][(int)$id] = $qty;
        }
    }
    header("Location: cart.php");
    exit();
}

// ── SAMPLE PRODUCTS (same as index.php — fallback kung empty ang DB) ──────
$sampleProducts = [
    1  => ["id"=>1,  "name"=>"Espresso",           "price"=>85,  "category"=>"mains",    "image"=>"", "description"=>""],
    2  => ["id"=>2,  "name"=>"Americano",           "price"=>100, "category"=>"mains",    "image"=>"", "description"=>""],
    3  => ["id"=>3,  "name"=>"Cappuccino",          "price"=>130, "category"=>"mains",    "image"=>"", "description"=>""],
    4  => ["id"=>4,  "name"=>"Caffè Latte",         "price"=>140, "category"=>"mains",    "image"=>"", "description"=>""],
    5  => ["id"=>5,  "name"=>"Flat White",          "price"=>145, "category"=>"mains",    "image"=>"", "description"=>""],
    6  => ["id"=>6,  "name"=>"Caramel Macchiato",   "price"=>155, "category"=>"mains",    "image"=>"", "description"=>""],
    7  => ["id"=>7,  "name"=>"Mocha",               "price"=>150, "category"=>"mains",    "image"=>"", "description"=>""],
    8  => ["id"=>8,  "name"=>"Cold Brew",           "price"=>160, "category"=>"drinks",   "image"=>"", "description"=>""],
    9  => ["id"=>9,  "name"=>"Iced Americano",      "price"=>110, "category"=>"drinks",   "image"=>"", "description"=>""],
    10 => ["id"=>10, "name"=>"Iced Caramel Latte",  "price"=>165, "category"=>"drinks",   "image"=>"", "description"=>""],
    11 => ["id"=>11, "name"=>"Matcha Latte",        "price"=>150, "category"=>"drinks",   "image"=>"", "description"=>""],
    12 => ["id"=>12, "name"=>"Croissant",           "price"=>95,  "category"=>"sides",    "image"=>"", "description"=>""],
    13 => ["id"=>13, "name"=>"Banana Bread",        "price"=>80,  "category"=>"sides",    "image"=>"", "description"=>""],
    14 => ["id"=>14, "name"=>"Blueberry Muffin",    "price"=>85,  "category"=>"sides",    "image"=>"", "description"=>""],
    15 => ["id"=>15, "name"=>"Tiramisu",            "price"=>130, "category"=>"desserts", "image"=>"", "description"=>""],
    16 => ["id"=>16, "name"=>"Chocolate Lava Cake", "price"=>145, "category"=>"desserts", "image"=>"", "description"=>""],
];

// ── BUILD CART ITEMS ───────────────────────────────────────────────────────
$cartRows = [];
$total    = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $id => $qty) {
        $id  = (int)$id;
        $qty = (int)$qty;

        // Subukan muna sa database
        $res = $conn->query("SELECT * FROM products WHERE id=$id");
        if ($res && $row = $res->fetch_assoc()) {
            // Nakita sa DB
        } elseif (isset($sampleProducts[$id])) {
            // Hindi sa DB — gamitin ang sample
            $row = $sampleProducts[$id];
        } else {
            continue; // Hindi mahanap kahit saan
        }

        $subtotal   = $row['price'] * $qty;
        $total     += $subtotal;
        $cartRows[] = array_merge($row, ['qty' => $qty, 'subtotal' => $subtotal]);
    }
}

$itemCount = array_sum(array_column($cartRows, 'qty'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart — My Restaurant</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #0b0b09;
            --surface:  #131310;
            --card:     #1a1a16;
            --border:   #2c2c24;
            --gold:     #c9a84c;
            --gold-dim: #8a6f2e;
            --green:    #4a7a3a;
            --green-lt: #6aaa52;
            --red:      #8b2e2e;
            --red-lt:   #c0392b;
            --cream:    #f0ead8;
            --muted:    #6b6b58;
            --text:     #e8e4d8;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Jost', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

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

        /* ── HEADER ── */
        header {
            position: sticky; top: 0; z-index: 100;
            background: rgba(11,11,9,0.88);
            backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--border);
        }

        .header-inner {
            max-width: 1100px; margin: 0 auto;
            padding: 0 32px; height: 68px;
            display: flex; align-items: center; justify-content: space-between;
        }

        .brand {
            display: flex; align-items: center; gap: 12px; text-decoration: none;
        }

        .brand-icon {
            width: 36px; height: 36px;
            border: 1px solid var(--gold-dim); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }

        .brand-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px; font-weight: 600;
            color: var(--cream); letter-spacing: 0.04em;
        }

        .brand-name span { color: var(--gold); }

        nav { display: flex; align-items: center; gap: 6px; }

        nav a {
            font-size: 12.5px; font-weight: 500;
            letter-spacing: 0.1em; text-transform: uppercase;
            color: var(--muted); text-decoration: none;
            padding: 8px 14px; border-radius: 3px;
            transition: color 0.2s, background 0.2s;
        }

        nav a:hover { color: var(--cream); background: rgba(255,255,255,0.04); }
        nav a.active { color: var(--gold); }

        .nav-back {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 18px !important;
            border: 1px solid var(--border) !important;
            border-radius: 3px; color: var(--muted) !important;
            transition: all 0.2s !important;
        }

        .nav-back:hover {
            border-color: var(--gold-dim) !important;
            color: var(--gold) !important;
            background: transparent !important;
        }

        /* ── PAGE TITLE ── */
        .page-hero {
            position: relative; z-index: 1;
            text-align: center;
            padding: 60px 32px 48px;
        }

        .hero-eyebrow {
            display: inline-flex; align-items: center; gap: 10px;
            font-size: 11px; letter-spacing: 0.2em;
            text-transform: uppercase; color: var(--gold);
            margin-bottom: 16px;
        }

        .hero-eyebrow::before, .hero-eyebrow::after {
            content: ''; width: 28px; height: 1px; background: var(--gold-dim);
        }

        .page-hero h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(36px, 5vw, 56px);
            font-weight: 700; color: var(--cream);
            letter-spacing: -0.01em;
        }

        .page-hero h1 em { font-style: italic; color: var(--gold); }

        .item-count {
            margin-top: 10px; font-size: 13px;
            color: var(--muted); letter-spacing: 0.05em;
        }

        /* ── LAYOUT ── */
        .cart-layout {
            position: relative; z-index: 1;
            max-width: 1100px; margin: 0 auto;
            padding: 0 32px 80px;
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 28px;
            align-items: start;
        }

        /* ── CART ITEMS ── */
        .cart-items { display: flex; flex-direction: column; gap: 14px; }

        .cart-item {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: border-color 0.25s;
            animation: slideIn 0.35s ease both;
        }

        .cart-item:hover { border-color: var(--gold-dim); }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-12px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .item-icon {
            width: 52px; height: 52px; flex-shrink: 0;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: var(--gold-dim);
        }

        .item-info { flex: 1; min-width: 0; }

        .item-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 19px; font-weight: 600;
            color: var(--cream); margin-bottom: 4px;
        }

        .item-unit-price {
            font-size: 12px; color: var(--muted);
            letter-spacing: 0.04em;
        }

        /* Qty controls */
        .qty-wrap {
            display: flex; align-items: center; gap: 0;
            border: 1px solid var(--border); border-radius: 3px;
            overflow: hidden;
        }

        .qty-btn {
            width: 34px; height: 34px;
            background: var(--surface);
            border: none; color: var(--muted);
            font-size: 18px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.15s, color 0.15s;
            flex-shrink: 0;
        }

        .qty-btn:hover { background: var(--border); color: var(--cream); }

        .qty-input {
            width: 48px; height: 34px;
            background: var(--card);
            border: none; border-left: 1px solid var(--border); border-right: 1px solid var(--border);
            color: var(--cream);
            font-family: 'Jost', sans-serif;
            font-size: 14px; font-weight: 500;
            text-align: center;
            outline: none;
        }

        .item-subtotal {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px; font-weight: 700;
            color: var(--gold); min-width: 90px; text-align: right;
        }

        .item-subtotal small {
            font-family: 'Jost', sans-serif;
            font-size: 12px; color: var(--muted); font-weight: 400;
        }

        .remove-btn {
            width: 34px; height: 34px; flex-shrink: 0;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 3px; color: var(--muted);
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: all 0.2s;
        }

        .remove-btn:hover { background: var(--red); border-color: var(--red); color: #fff; }

        /* ── ORDER SUMMARY ── */
        .summary-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 28px 26px;
            position: sticky;
            top: 88px;
        }

        .summary-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px; font-weight: 600;
            color: var(--cream);
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }

        .summary-title::after {
            content: ''; flex: 1; height: 1px; background: var(--border);
        }

        .summary-row {
            display: flex; justify-content: space-between; align-items: center;
            font-size: 13.5px; color: var(--muted);
            margin-bottom: 12px;
        }

        .summary-row.total {
            font-size: 15px; color: var(--cream); font-weight: 500;
            padding-top: 14px; border-top: 1px solid var(--border);
            margin-top: 8px; margin-bottom: 0;
        }

        .summary-row .val {
            font-family: 'Cormorant Garamond', serif;
            font-size: 18px; color: var(--text); font-weight: 600;
        }

        .summary-row.total .val {
            font-size: 26px; color: var(--gold);
        }

        .checkout-btn {
            display: flex; align-items: center; justify-content: center;
            gap: 10px; width: 100%;
            padding: 14px;
            margin-top: 22px;
            background: var(--green);
            border: none; border-radius: 3px;
            font-family: 'Jost', sans-serif;
            font-size: 13px; font-weight: 500;
            letter-spacing: 0.1em; text-transform: uppercase;
            color: #fff; text-decoration: none;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
            position: relative; overflow: hidden;
        }

        .checkout-btn:hover  { background: var(--green-lt); }
        .checkout-btn:active { transform: scale(0.98); }

        .checkout-btn::after {
            content: '';
            position: absolute; top: 0; left: -100%;
            width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.4s ease;
        }

        .checkout-btn:hover::after { left: 160%; }

        .continue-link {
            display: block; text-align: center;
            margin-top: 14px; font-size: 12.5px;
            color: var(--muted); text-decoration: none;
            letter-spacing: 0.06em;
            transition: color 0.2s;
        }

        .continue-link:hover { color: var(--gold); }

        .summary-note {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
            font-size: 11.5px; color: var(--muted);
            line-height: 1.6; text-align: center;
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            position: relative; z-index: 1;
            text-align: center;
            padding: 80px 20px;
            max-width: 420px; margin: 0 auto;
        }

        .empty-icon {
            width: 80px; height: 80px; margin: 0 auto 24px;
            border: 1px solid var(--border); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: var(--muted); opacity: 0.5;
        }

        .empty-state h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px; color: var(--cream); margin-bottom: 10px;
        }

        .empty-state p {
            font-size: 14px; color: var(--muted);
            line-height: 1.7; margin-bottom: 28px;
        }

        .browse-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 28px;
            background: var(--green); border-radius: 3px;
            font-family: 'Jost', sans-serif;
            font-size: 13px; font-weight: 500;
            letter-spacing: 0.08em; text-transform: uppercase;
            color: #fff; text-decoration: none;
            transition: background 0.2s;
        }

        .browse-btn:hover { background: var(--green-lt); }

        /* ── FOOTER ── */
        footer {
            position: relative; z-index: 1;
            border-top: 1px solid var(--border);
            padding: 28px 32px; text-align: center;
        }

        footer p { font-size: 12px; color: var(--muted); letter-spacing: 0.06em; }
        footer p span { color: var(--gold-dim); }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .cart-layout { grid-template-columns: 1fr; padding: 0 16px 60px; }
            .summary-card { position: static; }
            .header-inner { padding: 0 16px; }
            nav a:not(.nav-back) { display: none; }
            .cart-item { flex-wrap: wrap; gap: 14px; }
            .item-subtotal { min-width: auto; }
        }
    </style>
</head>
<body>

<!-- ══ HEADER ══ -->
<header>
    <div class="header-inner">
        <a href="index.php" class="brand">
            <div class="brand-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                     stroke="#c9a84c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 11l19-9-9 19-2-8-8-2z"/>
                </svg>
            </div>
            <span class="brand-name">My <span>Restaurant</span></span>
        </a>

        <nav>
            <a href="index.php">Menu</a>
            <a href="profile.php">Profile</a>
            <a href="log-out.php">Logout</a>
            <a href="index.php" class="nav-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
                </svg>
                Back to Menu
            </a>
        </nav>
    </div>
</header>

<!-- ══ PAGE HERO ══ -->
<div class="page-hero">
    <div class="hero-eyebrow">Review your order</div>
    <h1>Your <em>Cart</em></h1>
    <?php if ($itemCount > 0): ?>
        <p class="item-count"><?= $itemCount ?> item<?= $itemCount !== 1 ? 's' : '' ?> in your cart</p>
    <?php endif; ?>
</div>

<?php if (empty($cartRows)): ?>
<!-- ══ EMPTY STATE ══ -->
<div class="empty-state">
    <div class="empty-icon">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
        </svg>
    </div>
    <h3>Your cart is empty</h3>
    <p>Looks like you haven't added anything yet.<br>Browse our menu and pick your favourites.</p>
    <a href="index.php" class="browse-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
        </svg>
        Browse Menu
    </a>
</div>

<?php else: ?>
<!-- ══ CART LAYOUT ══ -->
<form method="POST" action="cart.php">
<div class="cart-layout">

    <!-- LEFT: items -->
    <div class="cart-items">
        <?php foreach ($cartRows as $i => $item): ?>
        <div class="cart-item" style="animation-delay: <?= $i * 0.06 ?>s">

            <!-- Icon -->
            <div class="item-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                    <line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/>
                </svg>
            </div>

            <!-- Info -->
            <div class="item-info">
                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                <div class="item-unit-price">₱<?= number_format($item['price'], 2) ?> each</div>
            </div>

            <!-- Qty -->
            <div class="qty-wrap">
                <button type="button" class="qty-btn" onclick="changeQty(<?= $item['id'] ?>, -1)">−</button>
                <input
                    type="number"
                    class="qty-input"
                    name="qty[<?= $item['id'] ?>]"
                    id="qty-<?= $item['id'] ?>"
                    value="<?= $item['qty'] ?>"
                    min="0" max="99"
                    onchange="recalc(<?= $item['id'] ?>, <?= $item['price'] ?>)"
                >
                <button type="button" class="qty-btn" onclick="changeQty(<?= $item['id'] ?>, 1)">+</button>
            </div>

            <!-- Subtotal -->
            <div class="item-subtotal" id="sub-<?= $item['id'] ?>">
                <small>₱</small><?= number_format($item['subtotal'], 2) ?>
            </div>

            <!-- Remove -->
            <a href="cart.php?remove=<?= $item['id'] ?>" class="remove-btn" title="Remove item">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                    <path d="M10 11v6"/><path d="M14 11v6"/>
                    <path d="M9 6V4h6v2"/>
                </svg>
            </a>

        </div>
        <?php endforeach; ?>
    </div>

    <!-- RIGHT: summary -->
    <div class="summary-card">
        <div class="summary-title">Order Summary</div>

        <div class="summary-row">
            <span>Items (<?= $itemCount ?>)</span>
            <span class="val" id="summarySubtotal">₱<?= number_format($total, 2) ?></span>
        </div>
        <div class="summary-row">
            <span>Delivery fee</span>
            <span class="val" style="color:var(--green-lt); font-size:14px;">Free</span>
        </div>

        <div class="summary-row total">
            <span>Total</span>
            <span class="val" id="summaryTotal">₱<?= number_format($total, 2) ?></span>
        </div>

        <a href="checkout.php" class="checkout-btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            Proceed to Checkout
        </a>

        <a href="index.php" class="continue-link">← Continue shopping</a>

        <p class="summary-note">
            🔒 Secure checkout &nbsp;·&nbsp; Orders prepared fresh upon confirmation.
        </p>
    </div>

</div>
</form>
<?php endif; ?>

<!-- ══ FOOTER ══ -->
<footer>
    <p>© 2026 <span>My AyosCoffeeNegosyo</span> — All rights reserved.</p>
</footer>

<script>
// Price map for live recalculation
const prices = {
    <?php foreach ($cartRows as $item): ?>
    <?= $item['id'] ?>: <?= $item['price'] ?>,
    <?php endforeach; ?>
};

function changeQty(id, delta) {
    const input = document.getElementById('qty-' + id);
    let val = parseInt(input.value) + delta;
    if (val < 0) val = 0;
    if (val > 99) val = 99;
    input.value = val;
    recalc(id, prices[id]);
}

function recalc(id, price) {
    const qty      = Math.max(0, parseInt(document.getElementById('qty-' + id).value) || 0);
    const subtotal = qty * price;

    // Update line subtotal
    const subEl = document.getElementById('sub-' + id);
    if (subEl) subEl.innerHTML = '<small>₱</small>' + subtotal.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});

    // Recalculate grand total
    let grand = 0;
    document.querySelectorAll('.qty-input').forEach(inp => {
        const iid = parseInt(inp.name.match(/\d+/)[0]);
        grand += (parseInt(inp.value) || 0) * (prices[iid] || 0);
    });

    const fmt = v => '₱' + v.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
    document.getElementById('summarySubtotal').textContent = fmt(grand);
    document.getElementById('summaryTotal').textContent    = fmt(grand);
}
</script>

</body>
</html>
