<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';

// ── SESSION CHECK (optional - guest allowed) ──────────────────────────────
$isLoggedIn = false;
$uid = 0;
if (isset($_SESSION["user"])) {
    $uid   = (int)($_SESSION["user"]["id"] ?? 0);
    $uname = $conn->real_escape_string($_SESSION["user"]["username"] ?? '');
    $chk   = $conn->query("SELECT id FROM users WHERE id = $uid AND username = '$uname' LIMIT 1");
    if ($chk && $chk->num_rows > 0) {
        $isLoggedIn = true;
    } else {
        session_unset(); session_destroy();
    }
}

$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS notes TEXT DEFAULT NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'pending'");
$conn->query("ALTER TABLE order_items ADD COLUMN IF NOT EXISTS price decimal(10,2) DEFAULT NULL");

// ── HANDLE REMOVE ─────────────────────────────────────────────────────────
if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][(int)$_GET['remove']]);
    header("Location: cart.php"); exit();
}

// ── HANDLE QTY UPDATE ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    foreach ($_POST['qty'] as $id => $qty) {
        $id  = (int)$id;
        $qty = (int)$qty;
        if ($qty <= 0) {
            unset($_SESSION['cart'][$id]);
        } else {
            // Clamp to actual product stock
            $stockRes = $conn->query("SELECT stock FROM products WHERE id = $id");
            $stockRow = $stockRes ? $stockRes->fetch_assoc() : null;
            $maxStock = $stockRow ? (int)$stockRow['stock'] : 100;
            $_SESSION['cart'][$id] = min($qty, $maxStock);
        }
    }
    header("Location: cart.php"); exit();
}

// ── BUILD CART ITEMS ───────────────────────────────────────────────────────
$cartRows = [];
$total    = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $id => $qty) {
        $id  = (int)$id;
        $qty = (int)$qty;
        if ($qty <= 0) continue;

        $res = $conn->query("SELECT * FROM products WHERE id = $id");
        if (!$res || !($row = $res->fetch_assoc())) continue;

        $maxStock = (int)($row['stock'] ?? 100);
        if ($maxStock <= 0) { unset($_SESSION['cart'][$id]); continue; }

        $qty = min($qty, $maxStock);
        $_SESSION['cart'][$id] = $qty;

        $subtotal   = $row['price'] * $qty;
        $total     += $subtotal;
        $cartRows[] = array_merge($row, [
            'qty'       => $qty,
            'subtotal'  => $subtotal,
            'max_stock' => $maxStock,
        ]);
    }
}

$itemCount = array_sum(array_column($cartRows, 'qty'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart — AyosCoffeeNegosyo</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{
            --bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;
            --gold:#c9a84c;--gold-dim:#8a6f2e;--green:#4a7a3a;--green-lt:#6aaa52;
            --red:#8b2e2e;--red-lt:#c0392b;--cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8;
            --amber:#d4820a;
        }
        html{scroll-behavior:smooth}
        body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden}
        body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 10% 0%,rgba(201,168,76,0.06) 0%,transparent 55%),radial-gradient(ellipse 50% 70% at 90% 100%,rgba(74,122,58,0.07) 0%,transparent 55%);pointer-events:none;z-index:0}
        header{position:sticky;top:0;z-index:100;background:rgba(11,11,9,0.88);backdrop-filter:blur(18px);border-bottom:1px solid var(--border)}
        .header-inner{max-width:1100px;margin:0 auto;padding:0 32px;height:68px;display:flex;align-items:center;justify-content:space-between}
        .brand{display:flex;align-items:center;gap:12px;text-decoration:none}
        .brand-icon{width:36px;height:36px;border:1px solid var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center}
        .brand-name{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream);letter-spacing:0.04em}
        .brand-name span{color:var(--gold)}
        nav{display:flex;align-items:center;gap:6px}
        nav a{font-size:12.5px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);text-decoration:none;padding:8px 14px;border-radius:3px;transition:color 0.2s,background 0.2s}
        nav a:hover{color:var(--cream);background:rgba(255,255,255,0.04)}
        .nav-back{display:flex!important;align-items:center;gap:8px;padding:8px 18px!important;border:1px solid var(--border)!important;border-radius:3px;color:var(--muted)!important;transition:all 0.2s!important}
        .nav-back:hover{border-color:var(--gold-dim)!important;color:var(--gold)!important;background:transparent!important}
        .page-hero{position:relative;z-index:1;text-align:center;padding:60px 32px 48px}
        .hero-eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:11px;letter-spacing:0.2em;text-transform:uppercase;color:var(--gold);margin-bottom:16px}
        .hero-eyebrow::before,.hero-eyebrow::after{content:'';width:28px;height:1px;background:var(--gold-dim)}
        .page-hero h1{font-family:'Cormorant Garamond',serif;font-size:clamp(36px,5vw,56px);font-weight:700;color:var(--cream);letter-spacing:-0.01em}
        .page-hero h1 em{font-style:italic;color:var(--gold)}
        .item-count{margin-top:10px;font-size:13px;color:var(--muted);letter-spacing:0.05em}
        .cart-layout{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:0 32px 80px;display:grid;grid-template-columns:1fr 340px;gap:28px;align-items:start}
        .cart-items{display:flex;flex-direction:column;gap:14px}
        .cart-item{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:20px 24px;display:flex;align-items:center;gap:20px;transition:border-color 0.25s;animation:slideIn 0.35s ease both}
        .cart-item:hover{border-color:var(--gold-dim)}
        @keyframes slideIn{from{opacity:0;transform:translateX(-12px)}to{opacity:1;transform:translateX(0)}}
        .item-icon{width:52px;height:52px;flex-shrink:0;background:var(--surface);border:1px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--gold-dim)}
        .item-info{flex:1;min-width:0}
        .item-name{font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:600;color:var(--cream);margin-bottom:4px}
        .item-unit-price{font-size:12px;color:var(--muted);letter-spacing:0.04em}
        .item-stock-note{font-size:11px;margin-top:3px}
        .item-stock-note.ok{color:var(--green-lt)}
        .item-stock-note.low{color:var(--amber)}
        .item-stock-note.limit{color:var(--red-lt)}
        .qty-wrap{display:flex;align-items:center;border:1px solid var(--border);border-radius:3px;overflow:hidden;flex-shrink:0}
        .qty-btn{width:36px;height:40px;background:var(--surface);border:none;color:var(--muted);font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background 0.15s,color 0.15s;flex-shrink:0;user-select:none;line-height:1}
        .qty-btn:hover:not(:disabled){background:var(--border);color:var(--cream)}
        .qty-btn:disabled{opacity:0.3;cursor:not-allowed}
        .qty-input{width:64px;height:40px;background:var(--card);border:none;border-left:1px solid var(--border);border-right:1px solid var(--border);color:var(--cream);font-family:'Jost',sans-serif;font-size:15px;font-weight:600;text-align:center;outline:none}
        .qty-input::-webkit-outer-spin-button,.qty-input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
        .item-subtotal{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:var(--gold);min-width:90px;text-align:right;flex-shrink:0}
        .item-subtotal small{font-family:'Jost',sans-serif;font-size:12px;color:var(--muted);font-weight:400}
        .remove-btn{width:34px;height:34px;flex-shrink:0;background:transparent;border:1px solid var(--border);border-radius:3px;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;text-decoration:none}
        .remove-btn:hover{background:var(--red);border-color:var(--red);color:#fff}
        .summary-card{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:28px 26px;position:sticky;top:88px}
        .summary-title{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;color:var(--cream);padding-bottom:16px;border-bottom:1px solid var(--border);margin-bottom:20px}
        .summary-row{display:flex;justify-content:space-between;align-items:center;font-size:13.5px;color:var(--muted);margin-bottom:12px}
        .summary-row.total{font-size:15px;color:var(--cream);font-weight:500;padding-top:14px;border-top:1px solid var(--border);margin-top:8px;margin-bottom:0}
        .summary-row .val{font-family:'Cormorant Garamond',serif;font-size:18px;color:var(--text);font-weight:600}
        .summary-row.total .val{font-size:26px;color:var(--gold)}
        .checkout-btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:14px;margin-top:22px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:#fff;text-decoration:none;cursor:pointer;transition:background 0.2s,transform 0.15s}
        .checkout-btn:hover{background:var(--green-lt)}
        .checkout-btn:active{transform:scale(0.98)}
        .continue-link{display:block;text-align:center;margin-top:14px;font-size:12.5px;color:var(--muted);text-decoration:none;letter-spacing:0.06em;transition:color 0.2s}
        .continue-link:hover{color:var(--gold)}
        .summary-note{margin-top:20px;padding-top:16px;border-top:1px solid var(--border);font-size:11.5px;color:var(--muted);line-height:1.6;text-align:center}
        .guest-note{background:rgba(201,168,76,0.06);border:1px solid var(--gold-dim);border-radius:3px;padding:10px 14px;font-size:12px;color:var(--muted);margin-top:14px;line-height:1.5;text-align:center}
        .guest-note a{color:var(--gold);text-decoration:none;font-weight:500}
        .guest-note a:hover{text-decoration:underline}
        .update-bar{position:fixed;bottom:0;left:0;right:0;z-index:200;background:var(--surface);border-top:1px solid var(--border);padding:14px 32px;display:flex;align-items:center;justify-content:space-between;transform:translateY(100%);transition:transform 0.3s ease}
        .update-bar.visible{transform:translateY(0)}
        .update-bar-msg{font-size:13px;color:var(--muted)}
        .update-bar-msg span{color:var(--gold);font-weight:500}
        .update-bar-btns{display:flex;gap:10px}
        .update-btn{padding:8px 20px;border-radius:3px;font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;cursor:pointer;transition:all 0.2s}
        .update-btn.save{background:var(--green);border:none;color:#fff}
        .update-btn.save:hover{background:var(--green-lt)}
        .update-btn.discard{background:transparent;border:1px solid var(--border);color:var(--muted)}
        .update-btn.discard:hover{border-color:var(--gold-dim);color:var(--gold)}
        .empty-state{position:relative;z-index:1;text-align:center;padding:80px 20px;max-width:420px;margin:0 auto}
        .empty-icon{width:80px;height:80px;margin:0 auto 24px;border:1px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--muted);opacity:0.5}
        .empty-state h3{font-family:'Cormorant Garamond',serif;font-size:28px;color:var(--cream);margin-bottom:10px}
        .empty-state p{font-size:14px;color:var(--muted);line-height:1.7;margin-bottom:28px}
        .browse-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:var(--green);border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:#fff;text-decoration:none;transition:background 0.2s}
        .browse-btn:hover{background:var(--green-lt)}
        /* Login Modal */
        #loginModal{position:fixed;inset:0;z-index:400;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.75);backdrop-filter:blur(6px);opacity:0;pointer-events:none;transition:opacity 0.3s ease}
        #loginModal.show{opacity:1;pointer-events:all}
        .modal-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:36px 32px;max-width:380px;width:90%;text-align:center;transform:translateY(20px);transition:transform 0.3s ease;position:relative}
        #loginModal.show .modal-card{transform:translateY(0)}
        .modal-card h3{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--cream);margin-bottom:10px}
        .modal-card h3 em{font-style:italic;color:var(--gold)}
        .modal-card p{font-size:13.5px;color:var(--muted);line-height:1.6;margin-bottom:24px}
        .modal-close{position:absolute;top:12px;right:12px;width:28px;height:28px;border:1px solid var(--border);border-radius:50%;background:transparent;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s}
        .modal-close:hover{border-color:var(--gold-dim);color:var(--cream)}
        .modal-btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
        .mb-primary{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:#fff;text-decoration:none;transition:background 0.2s}
        .mb-primary:hover{background:var(--green-lt)}
        .mb-ghost{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;border:1px solid var(--border);border-radius:3px;font-family:'Jost',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted);text-decoration:none;transition:all 0.2s;cursor:pointer;background:transparent}
        .mb-ghost:hover{border-color:var(--gold-dim);color:var(--gold)}
        .mb-guest{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border:1px solid var(--gold-dim);border-radius:3px;font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:var(--gold);text-decoration:none;transition:all 0.2s;cursor:pointer;background:transparent;margin-top:6px}
        .mb-guest:hover{background:rgba(201,168,76,0.08)}
        footer{position:relative;z-index:1;border-top:1px solid var(--border);padding:28px 32px;text-align:center}
        footer p{font-size:12px;color:var(--muted);letter-spacing:0.06em}
        footer p span{color:var(--gold-dim)}
        @media(max-width:768px){
            .cart-layout{grid-template-columns:1fr;padding:0 16px 80px}
            .summary-card{position:static}
            .header-inner{padding:0 16px}
            .cart-item{flex-wrap:wrap;gap:14px}
            .item-subtotal{min-width:auto}
            .update-bar{padding:14px 16px}
        }
    </style>
</head>
<body>
<header>
    <div class="header-inner">
        <a href="index.php" class="brand">
            <div class="brand-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
            </div>
            <span class="brand-name">My <span>AyosCoffeeNegosyo</span></span>
        </a>
        <nav>
            <a href="index.php">Menu</a>
            <?php if ($isLoggedIn): ?>
                <a href="profile.php">Profile</a>
                <a href="orders.php">Orders</a>
                <a href="log-out.php">Logout</a>
            <?php else: ?>
                <a href="log-in.php">Login</a>
                <a href="register.php">Sign Up</a>
            <?php endif; ?>
            <a href="index.php" class="nav-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back to Menu
            </a>
        </nav>
    </div>
</header>

<div class="page-hero">
    <div class="hero-eyebrow">Review your order</div>
    <h1>Your <em>Cart</em></h1>
    <?php if ($itemCount > 0): ?>
        <p class="item-count" id="heroItemCount"><?= $itemCount ?> item<?= $itemCount !== 1 ? 's' : '' ?> in your cart</p>
    <?php endif; ?>
</div>

<?php if (empty($cartRows)): ?>
<div class="empty-state">
    <div class="empty-icon">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
    </div>
    <h3>Your cart is empty</h3>
    <p>Looks like you haven't added anything yet.<br>Browse our menu and pick your favourites.</p>
    <a href="index.php" class="browse-btn">Browse Menu</a>
</div>

<?php else: ?>
<form method="POST" action="cart.php" id="cartForm">
    <input type="hidden" name="update" value="1">
    <div class="cart-layout">
        <div class="cart-items">
            <?php foreach ($cartRows as $i => $item):
                $maxStock  = (int)$item['max_stock'];
                $atLimit   = $item['qty'] >= $maxStock;
                $isLow     = $maxStock <= 10;
                $stockNote = $atLimit ? "Max qty reached ($maxStock in stock)" : ($isLow ? "Only $maxStock left in stock" : "$maxStock in stock");
                $noteClass = $atLimit ? 'limit' : ($isLow ? 'low' : 'ok');
            ?>
            <div class="cart-item" style="animation-delay:<?= $i * 0.06 ?>s">
                <div class="item-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
                </div>
                <div class="item-info">
                    <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                    <div class="item-unit-price">₱<?= number_format($item['price'], 2) ?> each</div>
                    <div class="item-stock-note <?= $noteClass ?>" id="note-<?= $item['id'] ?>"><?= $stockNote ?></div>
                </div>
                <div class="qty-wrap">
                    <button type="button" class="qty-btn" id="minus-<?= $item['id'] ?>" onclick="changeQty('<?= $item['id'] ?>', -1)" <?= $item['qty'] <= 1 ? 'disabled' : '' ?>>−</button>
                    <input type="text" inputmode="numeric" autocomplete="off" class="qty-input" name="qty[<?= $item['id'] ?>]" id="qty-<?= $item['id'] ?>" value="<?= $item['qty'] ?>" data-max="<?= $maxStock ?>" data-price="<?= $item['price'] ?>" data-original="<?= $item['qty'] ?>" oninput="onQtyInput(this)" onblur="onQtyBlur(this)">
                    <button type="button" class="qty-btn" id="plus-<?= $item['id'] ?>" onclick="changeQty('<?= $item['id'] ?>', 1)" <?= $atLimit ? 'disabled' : '' ?>>+</button>
                </div>
                <div class="item-subtotal" id="sub-<?= $item['id'] ?>"><small>₱</small><?= number_format($item['subtotal'], 2) ?></div>
                <a href="cart.php?remove=<?= $item['id'] ?>" class="remove-btn" title="Remove">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="summary-card">
            <div class="summary-title">Order Summary</div>
            <div class="summary-row">
                <span>Items (<span id="summaryCount"><?= $itemCount ?></span>)</span>
                <span class="val" id="summarySubtotal">₱<?= number_format($total, 2) ?></span>
            </div>
            <div class="summary-row">
                <span>Delivery fee</span>
                <span class="val" style="color:var(--green-lt);font-size:14px;">Free</span>
            </div>
            <div class="summary-row total">
                <span>Total</span>
                <span class="val" id="summaryTotal">₱<?= number_format($total, 2) ?></span>
            </div>

            <?php if ($isLoggedIn): ?>
                <a href="checkout.php" class="checkout-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Proceed to Checkout
                </a>
            <?php else: ?>
                <button type="button" class="checkout-btn" onclick="showLoginModal()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Proceed to Checkout
                </button>
                <div class="guest-note">
                    <a href="log-in.php">Login</a> or <a href="register.php">Sign Up</a> to checkout, or continue as guest.
                </div>
            <?php endif; ?>

            <a href="index.php" class="continue-link">← Continue shopping</a>
            <p class="summary-note">🔒 Secure checkout &nbsp;·&nbsp; Orders prepared fresh upon confirmation.</p>
        </div>
    </div>
</form>

<div class="update-bar" id="updateBar">
    <div class="update-bar-msg">Quantities changed. <span>Save to apply?</span></div>
    <div class="update-bar-btns">
        <button class="update-btn discard" onclick="discardChanges()">Discard</button>
        <button class="update-btn save" onclick="saveChanges()">Save Changes</button>
    </div>
</div>
<?php endif; ?>

<!-- Login Modal (shown to guests when clicking checkout) -->
<div id="loginModal">
    <div class="modal-card">
        <button class="modal-close" onclick="closeLoginModal()">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <h3>Ready to <em>Order?</em></h3>
        <p>Login or create a free account to proceed to checkout. You can also continue as a guest — you'll just need your phone number or GCash details.</p>
        <div class="modal-btns">
            <a href="log-in.php" class="mb-primary">Login</a>
            <a href="register.php" class="mb-ghost">Sign Up</a>
        </div>
        <div style="margin-top:14px">
            <a href="checkout.php?guest=1" class="mb-guest">Continue as Guest</a>
        </div>
    </div>
</div>

<footer><p>© 2026 <span>My AyosCoffeeNegosyo</span> — All rights reserved.</p></footer>

<script>
function fmt(v){return '₱'+v.toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2})}

function recalcAll(){
    let grand=0,totalItems=0;
    document.querySelectorAll('.qty-input').forEach(function(inp){
        var id=inp.id.replace('qty-',''),qty=parseInt(inp.value)||0,price=parseFloat(inp.dataset.price)||0,sub=qty*price;
        var subEl=document.getElementById('sub-'+id);
        if(subEl)subEl.innerHTML='<small>₱</small>'+sub.toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
        grand+=sub;totalItems+=qty;
    });
    document.getElementById('summarySubtotal').textContent=fmt(grand);
    document.getElementById('summaryTotal').textContent=fmt(grand);
    document.getElementById('summaryCount').textContent=totalItems;
    var hero=document.getElementById('heroItemCount');
    if(hero)hero.textContent=totalItems+' item'+(totalItems!==1?'s':'')+' in your cart';
}

function updateControls(inp){
    var id=inp.id.replace('qty-',''),qty=parseInt(inp.value)||0,maxStock=parseInt(inp.dataset.max)||99,atLimit=qty>=maxStock,isLow=maxStock<=10;
    var minus=document.getElementById('minus-'+id),plus=document.getElementById('plus-'+id);
    if(minus)minus.disabled=qty<=1;if(plus)plus.disabled=atLimit;
    var note=document.getElementById('note-'+id);
    if(note){
        if(atLimit){note.textContent='Max qty reached ('+maxStock+' in stock)';note.className='item-stock-note limit';}
        else if(isLow){note.textContent='Only '+maxStock+' left in stock';note.className='item-stock-note low';}
        else{note.textContent=maxStock+' in stock';note.className='item-stock-note ok';}
    }
}

function checkChanges(){
    var changed=false;
    document.querySelectorAll('.qty-input').forEach(function(inp){if(parseInt(inp.value)!==parseInt(inp.dataset.original))changed=true;});
    document.getElementById('updateBar').classList.toggle('visible',changed);
}

function onQtyInput(inp){
    inp.value=inp.value.replace(/[^0-9]/g,'');
    var val=parseInt(inp.value),maxStock=parseInt(inp.dataset.max)||99;
    if(!isNaN(val)&&val>maxStock){inp.value=maxStock;val=maxStock;}
    if(!isNaN(val)&&val>=1){updateControls(inp);recalcAll();checkChanges();}
}

function onQtyBlur(inp){
    var maxStock=parseInt(inp.dataset.max)||99,val=parseInt(inp.value);
    if(isNaN(val)||val<1)val=1;if(val>maxStock)val=maxStock;
    inp.value=val;updateControls(inp);recalcAll();checkChanges();
}

function changeQty(id,delta){
    var inp=document.getElementById('qty-'+id),maxStock=parseInt(inp.dataset.max)||99,val=(parseInt(inp.value)||0)+delta;
    val=Math.max(1,Math.min(val,maxStock));inp.value=val;updateControls(inp);recalcAll();checkChanges();
}

function saveChanges(){document.getElementById('cartForm').submit();}
function discardChanges(){
    document.querySelectorAll('.qty-input').forEach(function(inp){inp.value=inp.dataset.original;updateControls(inp);});
    recalcAll();document.getElementById('updateBar').classList.remove('visible');
}

function showLoginModal(){document.getElementById('loginModal').classList.add('show');}
function closeLoginModal(){document.getElementById('loginModal').classList.remove('show');}
document.getElementById('loginModal').addEventListener('click',function(e){if(e.target===this)closeLoginModal();});
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeLoginModal();});
</script>
</body>
</html>