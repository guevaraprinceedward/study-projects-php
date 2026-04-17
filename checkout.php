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

// ── GUARD: empty cart ────────────────────────────────────────────────────────
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// ── SAMPLE PRODUCTS (fallback) ────────────────────────────────────────────────
$sampleProducts = [
    1  => ["id"=>1,  "name"=>"Espresso",           "price"=>85,  "category"=>"mains"],
    2  => ["id"=>2,  "name"=>"Americano",           "price"=>100, "category"=>"mains"],
    3  => ["id"=>3,  "name"=>"Cappuccino",          "price"=>130, "category"=>"mains"],
    4  => ["id"=>4,  "name"=>"Caffè Latte",         "price"=>140, "category"=>"mains"],
    5  => ["id"=>5,  "name"=>"Flat White",          "price"=>145, "category"=>"mains"],
    6  => ["id"=>6,  "name"=>"Caramel Macchiato",   "price"=>155, "category"=>"mains"],
    7  => ["id"=>7,  "name"=>"Mocha",               "price"=>150, "category"=>"mains"],
    8  => ["id"=>8,  "name"=>"Cold Brew",           "price"=>160, "category"=>"drinks"],
    9  => ["id"=>9,  "name"=>"Iced Americano",      "price"=>110, "category"=>"drinks"],
    10 => ["id"=>10, "name"=>"Iced Caramel Latte",  "price"=>165, "category"=>"drinks"],
    11 => ["id"=>11, "name"=>"Matcha Latte",        "price"=>150, "category"=>"drinks"],
    12 => ["id"=>12, "name"=>"Croissant",           "price"=>95,  "category"=>"sides"],
    13 => ["id"=>13, "name"=>"Banana Bread",        "price"=>80,  "category"=>"sides"],
    14 => ["id"=>14, "name"=>"Blueberry Muffin",    "price"=>85,  "category"=>"sides"],
    15 => ["id"=>15, "name"=>"Tiramisu",            "price"=>130, "category"=>"desserts"],
    16 => ["id"=>16, "name"=>"Chocolate Lava Cake", "price"=>145, "category"=>"desserts"],
];

// ── BUILD CART ROWS ───────────────────────────────────────────────────────────
$cartRows = [];
$total    = 0;

foreach ($_SESSION['cart'] as $id => $qty) {
    $id  = (int)$id;
    $qty = (int)$qty;
    if ($qty <= 0) continue;

    $res = $conn->query("SELECT * FROM products WHERE id=$id");
    if ($res && $row = $res->fetch_assoc()) {
        // from DB
    } elseif (isset($sampleProducts[$id])) {
        $row = $sampleProducts[$id];
    } else {
        continue;
    }

    $subtotal   = $row['price'] * $qty;
    $total     += $subtotal;
    $cartRows[] = array_merge($row, ['qty' => $qty, 'subtotal' => $subtotal]);
}

if (empty($cartRows)) {
    header("Location: cart.php");
    exit();
}

// ── GET USER INFO ─────────────────────────────────────────────────────────────
$user    = $_SESSION['user'];
$userId  = (int)($user['id'] ?? 0);
$userName = htmlspecialchars($user['name'] ?? $user['username'] ?? 'Guest');

// ── HANDLE FORM SUBMIT ────────────────────────────────────────────────────────
$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {

    $notes = trim($_POST['notes'] ?? '');
    $notes = $conn->real_escape_string($notes);

    // Use a transaction for safety
    $conn->begin_transaction();
    try {
        // Insert order
        $conn->query("INSERT INTO orders (user_id, total, notes, status, created_at)
                      VALUES ($userId, $total, '$notes', 'pending', NOW())");
        $orderId = $conn->insert_id;

        if (!$orderId) throw new Exception("Could not create order.");

        // Insert order items
        foreach ($cartRows as $item) {
            $pid   = (int)$item['id'];
            $qty   = (int)$item['qty'];
            $price = (float)$item['price'];
            $conn->query("INSERT INTO order_items (order_id, product_id, quantity, price)
                          VALUES ($orderId, $pid, $qty, $price)");
        }

        $conn->commit();
        unset($_SESSION['cart']);
        $success  = true;
        $successOrderId = $orderId;

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Something went wrong. Please try again.";
    }
}

$itemCount = array_sum(array_column($cartRows, 'qty'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — My Restaurant</title>
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
            position: fixed; inset: 0;
            background:
                radial-gradient(ellipse 70% 50% at 10% 0%, rgba(201,168,76,0.06) 0%, transparent 55%),
                radial-gradient(ellipse 50% 70% at 90% 100%, rgba(74,122,58,0.07) 0%, transparent 55%);
            pointer-events: none; z-index: 0;
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
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
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

        /* ── PAGE HERO ── */
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
        }
        .page-hero h1 em { font-style: italic; color: var(--gold); }

        /* ── LAYOUT ── */
        .checkout-layout {
            position: relative; z-index: 1;
            max-width: 1100px; margin: 0 auto;
            padding: 0 32px 80px;
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 28px;
            align-items: start;
        }

        /* ── FORM CARD ── */
        .form-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 32px;
        }
        .section-label {
            font-family: 'Cormorant Garamond', serif;
            font-size: 18px; font-weight: 600;
            color: var(--cream);
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 22px;
        }
        .section-label::after {
            content: ''; flex: 1; height: 1px; background: var(--border);
        }
        .field-row { margin-bottom: 18px; }
        .field-row label {
            display: block;
            font-size: 11px; font-weight: 500;
            letter-spacing: 0.12em; text-transform: uppercase;
            color: var(--muted); margin-bottom: 8px;
        }
        .field-row input,
        .field-row textarea,
        .field-row select {
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 3px;
            color: var(--cream);
            font-family: 'Jost', sans-serif;
            font-size: 14px;
            padding: 11px 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        .field-row input:focus,
        .field-row textarea:focus,
        .field-row select:focus { border-color: var(--gold-dim); }
        .field-row input[readonly] { color: var(--muted); cursor: not-allowed; }
        .field-row textarea { resize: vertical; min-height: 80px; }

        /* payment methods */
        .pay-methods { display: flex; gap: 10px; flex-wrap: wrap; }
        .pay-method {
            flex: 1; min-width: 130px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 3px;
            padding: 14px 16px;
            cursor: pointer;
            display: flex; align-items: center; gap: 10px;
            font-size: 13px; font-weight: 500; color: var(--muted);
            transition: all 0.2s; user-select: none;
        }
        .pay-method input { display: none; }
        .pay-method:has(input:checked),
        .pay-method.selected {
            border-color: var(--gold-dim);
            background: rgba(201,168,76,0.06);
            color: var(--cream);
        }
        .pay-icon {
            width: 28px; height: 28px;
            background: var(--border);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; color: var(--gold-dim);
        }

        /* error banner */
        .error-banner {
            background: rgba(139,46,46,0.15);
            border: 1px solid var(--red);
            border-radius: 3px;
            padding: 12px 16px;
            color: #e07b7b;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }

        /* ── SUMMARY CARD ── */
        .summary-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 28px 26px;
            position: sticky; top: 88px;
        }
        .summary-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px; font-weight: 600;
            color: var(--cream);
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 20px;
        }
        .order-item-row {
            display: flex; justify-content: space-between;
            font-size: 13px; color: var(--muted);
            margin-bottom: 10px; gap: 10px;
        }
        .order-item-row .name { flex: 1; }
        .order-item-row .qty { color: var(--gold-dim); min-width: 30px; }
        .order-item-row .price { font-weight: 500; color: var(--text); }
        .divider {
            height: 1px; background: var(--border);
            margin: 14px 0;
        }
        .summary-row {
            display: flex; justify-content: space-between; align-items: center;
            font-size: 13.5px; color: var(--muted);
            margin-bottom: 10px;
        }
        .summary-row.total {
            font-size: 15px; color: var(--cream); font-weight: 500;
            padding-top: 14px; border-top: 1px solid var(--border);
            margin-top: 6px; margin-bottom: 0;
        }
        .summary-row .val {
            font-family: 'Cormorant Garamond', serif;
            font-size: 18px; color: var(--text); font-weight: 600;
        }
        .summary-row.total .val { font-size: 26px; color: var(--gold); }

        /* place order button */
        .place-btn {
            display: flex; align-items: center; justify-content: center;
            gap: 10px; width: 100%;
            padding: 15px;
            margin-top: 22px;
            background: var(--green);
            border: none; border-radius: 3px;
            font-family: 'Jost', sans-serif;
            font-size: 13px; font-weight: 500;
            letter-spacing: 0.1em; text-transform: uppercase;
            color: #fff; cursor: pointer;
            transition: background 0.2s, transform 0.15s;
            position: relative; overflow: hidden;
        }
        .place-btn:hover  { background: var(--green-lt); }
        .place-btn:active { transform: scale(0.98); }
        .place-btn::after {
            content: '';
            position: absolute; top: 0; left: -100%;
            width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.4s ease;
        }
        .place-btn:hover::after { left: 160%; }

        .back-link {
            display: block; text-align: center;
            margin-top: 14px; font-size: 12.5px;
            color: var(--muted); text-decoration: none;
            letter-spacing: 0.06em; transition: color 0.2s;
        }
        .back-link:hover { color: var(--gold); }
        .summary-note {
            margin-top: 20px; padding-top: 16px;
            border-top: 1px solid var(--border);
            font-size: 11.5px; color: var(--muted);
            line-height: 1.6; text-align: center;
        }

        /* ── SUCCESS OVERLAY ── */
        .success-wrap {
            position: relative; z-index: 1;
            max-width: 520px; margin: 0 auto;
            text-align: center;
            padding: 60px 32px 80px;
        }
        .success-icon {
            width: 88px; height: 88px; margin: 0 auto 28px;
            border: 1px solid var(--green);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: var(--green-lt);
            animation: popIn 0.5s cubic-bezier(0.34,1.56,0.64,1) both;
        }
        @keyframes popIn {
            from { opacity:0; transform: scale(0.5); }
            to   { opacity:1; transform: scale(1); }
        }
        .success-wrap h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 40px; font-weight: 700;
            color: var(--cream); margin-bottom: 12px;
        }
        .success-wrap h2 em { font-style: italic; color: var(--gold); }
        .success-wrap p { font-size: 14px; color: var(--muted); line-height: 1.7; margin-bottom: 8px; }
        .order-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--card); border: 1px solid var(--gold-dim);
            border-radius: 3px; padding: 8px 18px;
            font-size: 13px; color: var(--gold); margin: 18px 0 28px;
            letter-spacing: 0.08em;
        }
        .success-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 13px 28px;
            background: var(--green); border-radius: 3px;
            font-family: 'Jost', sans-serif; font-size: 13px;
            font-weight: 500; letter-spacing: 0.08em; text-transform: uppercase;
            color: #fff; text-decoration: none; transition: background 0.2s;
        }
        .btn-primary:hover { background: var(--green-lt); }
        .btn-ghost {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 13px 28px;
            border: 1px solid var(--border); border-radius: 3px;
            font-family: 'Jost', sans-serif; font-size: 13px;
            font-weight: 500; letter-spacing: 0.08em; text-transform: uppercase;
            color: var(--muted); text-decoration: none; transition: all 0.2s;
        }
        .btn-ghost:hover { border-color: var(--gold-dim); color: var(--gold); }

        /* footer */
        footer {
            position: relative; z-index: 1;
            border-top: 1px solid var(--border);
            padding: 28px 32px; text-align: center;
        }
        footer p { font-size: 12px; color: var(--muted); letter-spacing: 0.06em; }
        footer p span { color: var(--gold-dim); }

        /* responsive */
        @media (max-width: 768px) {
            .checkout-layout { grid-template-columns: 1fr; padding: 0 16px 60px; }
            .summary-card { position: static; }
            .header-inner { padding: 0 16px; }
            nav a:not(.nav-back) { display: none; }
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
            <a href="cart.php" class="nav-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
                </svg>
                Back to Cart
            </a>
        </nav>
    </div>
</header>

<?php if ($success): ?>
<!-- ══ SUCCESS STATE ══ -->
<div class="page-hero">
    <div class="hero-eyebrow">Thank you!</div>
    <h1>Order <em>Confirmed</em></h1>
</div>
<div class="success-wrap">
    <div class="success-icon">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
    </div>
    <h2>Order <em>Placed!</em></h2>
    <p>Your order has been received and is being prepared.</p>
    <p>We'll have it ready fresh for you shortly.</p>
    <div class="order-badge">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
            <circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
        </svg>
        Order #<?= str_pad($successOrderId, 5, '0', STR_PAD_LEFT) ?>
    </div>
    <div class="success-actions">
        <a href="orders.php" class="btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
            </svg>
            View My Orders
        </a>
        <a href="index.php" class="btn-ghost">
            Browse Menu
        </a>
    </div>
</div>

<?php else: ?>
<!-- ══ CHECKOUT PAGE ══ -->
<div class="page-hero">
    <div class="hero-eyebrow">Almost there</div>
    <h1>Complete your <em>Order</em></h1>
</div>

<form method="POST" action="checkout.php">
<div class="checkout-layout">

    <!-- LEFT: form -->
    <div class="form-card">

        <?php if ($error): ?>
        <div class="error-banner">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Customer details -->
        <div class="section-label">Customer Details</div>

        <div class="field-row">
            <label>Name</label>
            <input type="text" value="<?= $userName ?>" readonly>
        </div>

        <div class="field-row">
            <label>Email</label>
            <input type="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly>
        </div>

        <!-- Payment -->
        <div class="section-label" style="margin-top: 28px;">Payment Method</div>

        <div class="pay-methods">
            <label class="pay-method">
                <input type="radio" name="payment" value="cash" checked>
                <div class="pay-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                </div>
                Cash on Pickup
            </label>
            <label class="pay-method">
                <input type="radio" name="payment" value="gcash">
                <div class="pay-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2a10 10 0 1 0 10 10H12V2z"/>
                        <path d="M12 2a10 10 0 0 1 10 10"/>
                    </svg>
                </div>
                GCash
            </label>
            <label class="pay-method">
                <input type="radio" name="payment" value="card">
                <div class="pay-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                </div>
                Credit / Debit
            </label>
        </div>

        <!-- Notes -->
        <div class="field-row" style="margin-top: 24px;">
            <label>Order Notes <span style="color:var(--muted);text-transform:none;letter-spacing:0">(optional)</span></label>
            <textarea name="notes" placeholder="Any special requests? E.g. less ice, extra sugar..."></textarea>
        </div>

    </div>

    <!-- RIGHT: order summary -->
    <div class="summary-card">
        <div class="summary-title">Order Summary</div>

        <?php foreach ($cartRows as $item): ?>
        <div class="order-item-row">
            <span class="name"><?= htmlspecialchars($item['name']) ?></span>
            <span class="qty">×<?= $item['qty'] ?></span>
            <span class="price">₱<?= number_format($item['subtotal'], 2) ?></span>
        </div>
        <?php endforeach; ?>

        <div class="divider"></div>

        <div class="summary-row">
            <span>Subtotal</span>
            <span class="val">₱<?= number_format($total, 2) ?></span>
        </div>
        <div class="summary-row">
            <span>Delivery fee</span>
            <span class="val" style="color:var(--green-lt); font-size:14px;">Free</span>
        </div>
        <div class="summary-row total">
            <span>Total</span>
            <span class="val">₱<?= number_format($total, 2) ?></span>
        </div>

        <button type="submit" name="place_order" class="place-btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            Place Order
        </button>

        <a href="cart.php" class="back-link">← Edit cart</a>

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

</body>
</html>