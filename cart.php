<?php
/**
 * cart.php (v3) — line-based serve cart.
 * Each line = product + size + options (hot/iced, sugar...) + add-ons + note.
 * The order is marked Dine-in / Take-out / Pick-up / Delivery here.
 * Delivery needs address, phone, date and time. "Proceed to checkout" validates
 * everything and stores it in $_SESSION['order_svc'] for checkout.php.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';
include_once 'customize-lib.php';
include_once 'delivery-checkout.inc.php';
include_once 'service-type.inc.php';

// ── SESSION CHECK (guest allowed) ─────────────────────────────────────────
$isLoggedIn = false;
if (isset($_SESSION["user"])) {
    $uid = (int)($_SESSION["user"]["id"] ?? 0);
    $st  = $conn->prepare("SELECT id FROM users WHERE id = ? AND username = ? LIMIT 1");
    $un  = (string)($_SESSION["user"]["username"] ?? '');
    $st->bind_param('is', $uid, $un);
    $st->execute(); $st->store_result();
    if ($st->num_rows > 0) $isLoggedIn = true; else { session_unset(); session_destroy(); session_start(); }
    $st->close();
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
custMigrateCart($_SESSION['cart']);
$types = custOrderTypes();
$error = '';

// ── REMOVE ────────────────────────────────────────────────────────────────
if (isset($_GET['remove'])) {
    $k = preg_replace('/[^A-Za-z0-9]/', '', (string)$_GET['remove']);
    unset($_SESSION['cart'][$k]);
    header("Location: cart.php"); exit();
}

// ── UPDATE QTY / PROCEED ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    custApplyQtyUpdate($conn, $_SESSION['cart'], is_array($_POST['qty'] ?? null) ? $_POST['qty'] : []);
    $picked = (string)($_POST['service_type'] ?? '');
    if (isset($types[$picked])) $_SESSION['svc_type'] = $picked;

    if (empty($_POST['go'])) { header("Location: cart.php"); exit(); }

    // proceed to checkout: validate the service type (+ delivery details)
    do {
        [$chkRows] = custBuildCart($conn, $_SESSION['cart']);
        if (!$chkRows) { $error = 'Your cart is empty.'; break; }
        $svc = svcResolve($_POST);
        if (!$svc['ok']) { $error = $svc['error']; break; }
        $sched = null;
        if ($svc['type'] === 'delivery') {
            $r = dlvResolveSchedule((string)($_POST['delivery_date'] ?? ''), (string)($_POST['delivery_time'] ?? ''));
            if (!$r['ok']) { $error = $r['error']; break; }
            $sched = $r['scheduled_for'];
        }
        $_SESSION['order_svc'] = [
            'type' => $svc['type'], 'label' => $svc['label'], 'address' => $svc['address'],
            'phone' => $svc['phone'], 'fee' => $svc['fee'], 'scheduled_for' => $sched,
        ];
        header('Location: checkout.php' . (!empty($_POST['guest']) ? '?guest=1' : '')); exit();
    } while (false);
}

// ── BUILD CART ────────────────────────────────────────────────────────────
[$cartRows, $total] = custBuildCart($conn, $_SESSION['cart']);
$itemCount = custCartCount($_SESSION['cart']);

$selSvc   = (string)($_POST['service_type'] ?? ($_SESSION['order_svc']['type'] ?? ($_SESSION['svc_type'] ?? '')));
if (!isset($types[$selSvc])) $selSvc = '';
$addr     = (string)($_POST['delivery_address'] ?? ($_SESSION['order_svc']['address'] ?? ''));
$phone    = (string)($_POST['delivery_phone']   ?? ($_SESSION['order_svc']['phone'] ?? ''));
$dDate    = (string)($_POST['delivery_date'] ?? (isset($_SESSION['order_svc']['scheduled_for']) ? substr((string)$_SESSION['order_svc']['scheduled_for'], 0, 10) : ''));
$dTime    = (string)($_POST['delivery_time'] ?? (isset($_SESSION['order_svc']['scheduled_for']) ? substr((string)$_SESSION['order_svc']['scheduled_for'], 11, 5) : ''));
$minDate  = date('Y-m-d');
$maxDate  = date('Y-m-d', strtotime('+' . DLV_MAX_DAYS_AHEAD . ' days'));
$feeAmt   = (float)DELIVERY_FEE;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Cart — SIPPERÉ Café</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;--gold:#c9a84c;--gold-dim:#8a6f2e;--green:#4a7a3a;--green-lt:#6aaa52;--red:#8b2e2e;--red-lt:#c0392b;--cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8;--amber:#d4820a}
html{scroll-behavior:smooth}
body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 10% 0%,rgba(201,168,76,.06) 0%,transparent 55%),radial-gradient(ellipse 50% 70% at 90% 100%,rgba(74,122,58,.07) 0%,transparent 55%);pointer-events:none;z-index:0}
:focus-visible{outline:2px solid var(--gold);outline-offset:3px}
header{position:sticky;top:0;z-index:100;background:rgba(11,11,9,.88);backdrop-filter:blur(18px);border-bottom:1px solid var(--border)}
.header-inner{max-width:1100px;margin:0 auto;padding:0 32px;height:68px;display:flex;align-items:center;justify-content:space-between}
.brand{display:flex;align-items:center;gap:12px;text-decoration:none}
.brand-icon{width:36px;height:36px;border:1px solid var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center}
.brand-name{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream);letter-spacing:.04em}
.brand-name span{color:var(--gold)}
nav{display:flex;align-items:center;gap:6px}
nav a{font-size:12.5px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);text-decoration:none;padding:8px 14px;border-radius:3px;transition:color .2s,background .2s}
nav a:hover{color:var(--cream);background:rgba(255,255,255,.04)}
.nav-back{display:flex!important;align-items:center;gap:8px;border:1px solid var(--border)!important}
.page-hero{position:relative;z-index:1;text-align:center;padding:60px 32px 40px}
.hero-eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:16px}
.hero-eyebrow::before,.hero-eyebrow::after{content:'';width:28px;height:1px;background:var(--gold-dim)}
.page-hero h1{font-family:'Cormorant Garamond',serif;font-size:clamp(36px,5vw,56px);font-weight:700;color:var(--cream)}
.page-hero h1 em{font-style:italic;color:var(--gold)}
.item-count{margin-top:10px;font-size:13px;color:var(--muted);letter-spacing:.05em}
.alert{position:relative;z-index:1;max-width:1100px;margin:0 auto 22px;padding:0 32px}
.alert div{background:rgba(139,46,46,.15);border:1px solid var(--red);border-radius:3px;padding:12px 16px;color:#e07b7b;font-size:13px}
.cart-layout{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:0 32px 80px;display:grid;grid-template-columns:1fr 360px;gap:28px;align-items:start}
.cart-items{display:flex;flex-direction:column;gap:14px}
.cart-item{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:20px 22px;display:flex;align-items:flex-start;gap:18px;flex-wrap:wrap;transition:border-color .25s}
.cart-item:hover{border-color:var(--gold-dim)}
.item-icon{width:48px;height:48px;flex-shrink:0;background:var(--surface);border:1px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--gold-dim)}
.item-info{flex:1;min-width:200px}
.item-name{font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:600;color:var(--cream);margin-bottom:4px;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.svc-chip{font-family:'Jost',sans-serif;font-size:9.5px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;padding:3px 8px;border-radius:3px;background:var(--gold);color:#1a1400}
.svc-chip:empty{display:none}
.item-spec{font-size:12.5px;color:var(--text);margin-bottom:2px}
.item-addons,.item-note{font-size:11.5px;color:var(--gold-dim);font-style:italic;margin-bottom:2px}
.item-unit-price{font-size:12px;color:var(--muted);letter-spacing:.04em;margin-top:2px}
.item-stock-note{font-size:11px;margin-top:3px}
.item-stock-note.ok{color:var(--green-lt)}.item-stock-note.low{color:var(--amber)}.item-stock-note.limit{color:var(--red-lt)}
.qty-wrap{display:flex;align-items:center;border:1px solid var(--border);border-radius:3px;overflow:hidden;flex-shrink:0}
.qty-btn{width:36px;height:40px;background:var(--surface);border:none;color:var(--muted);font-size:20px;cursor:pointer;line-height:1}
.qty-btn:hover:not(:disabled){background:var(--border);color:var(--cream)}
.qty-btn:disabled{opacity:.3;cursor:not-allowed}
.qty-input{width:60px;height:40px;background:var(--card);border:none;border-left:1px solid var(--border);border-right:1px solid var(--border);color:var(--cream);font-family:'Jost',sans-serif;font-size:15px;font-weight:600;text-align:center;outline:none}
.item-subtotal{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:var(--gold);min-width:90px;text-align:right;flex-shrink:0}
.item-subtotal small{font-family:'Jost',sans-serif;font-size:12px;color:var(--muted);font-weight:400}
.remove-btn{width:34px;height:34px;flex-shrink:0;border:1px solid var(--border);border-radius:3px;color:var(--muted);display:flex;align-items:center;justify-content:center;text-decoration:none;transition:all .2s}
.remove-btn:hover{background:var(--red);border-color:var(--red);color:#fff}
.summary-card{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:26px 24px;position:sticky;top:88px}
.summary-title{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;color:var(--cream);padding-bottom:14px;border-bottom:1px solid var(--border);margin-bottom:18px}
.sec-label{font-size:11px;font-weight:500;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);margin:0 0 10px}
.dlv-when{margin-top:0;background:var(--surface);border:1px solid var(--border);border-top:none;border-radius:0 0 4px 4px;padding:4px 16px 4px;margin-bottom:6px}
.dlv-when[hidden]{display:none}
.dlv-when label{display:block;font-size:11px;font-weight:500;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-bottom:7px}
.dlv-when input{width:100%;background:var(--card);border:1px solid var(--border);border-radius:3px;color:var(--cream);font-family:'Jost',sans-serif;font-size:14px;padding:10px 12px;outline:none;margin-bottom:12px;color-scheme:dark}
.dlv-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.dlv-hint{font-size:11.5px;color:var(--muted);line-height:1.55;margin:-4px 0 12px}
.summary-row{display:flex;justify-content:space-between;align-items:center;font-size:13.5px;color:var(--muted);margin:12px 0}
.summary-row.total{font-size:15px;color:var(--cream);font-weight:500;padding-top:14px;border-top:1px solid var(--border);margin-bottom:0}
.summary-row .val{font-family:'Cormorant Garamond',serif;font-size:18px;color:var(--text);font-weight:600}
.summary-row.total .val{font-size:26px;color:var(--gold)}
.checkout-btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:14px;margin-top:20px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;color:#fff;cursor:pointer;transition:background .2s}
.checkout-btn:hover{background:var(--green-lt)}
.continue-link{display:block;text-align:center;margin-top:14px;font-size:12.5px;color:var(--muted);text-decoration:none}
.continue-link:hover{color:var(--gold)}
.summary-note{margin-top:18px;padding-top:14px;border-top:1px solid var(--border);font-size:11.5px;color:var(--muted);line-height:1.6;text-align:center}
.guest-note{background:rgba(201,168,76,.06);border:1px solid var(--gold-dim);border-radius:3px;padding:10px 14px;font-size:12px;color:var(--muted);margin-top:14px;line-height:1.5;text-align:center}
.guest-note a{color:var(--gold);text-decoration:none}
.update-bar{position:fixed;bottom:0;left:0;right:0;z-index:200;background:var(--surface);border-top:1px solid var(--border);padding:14px 32px;display:flex;align-items:center;justify-content:space-between;transform:translateY(100%);transition:transform .3s ease}
.update-bar.visible{transform:none}
.update-bar-msg{font-size:13px;color:var(--muted)}.update-bar-msg span{color:var(--gold);font-weight:500}
.update-bar-btns{display:flex;gap:10px}
.update-btn{padding:8px 20px;border-radius:3px;font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:.08em;text-transform:uppercase;cursor:pointer}
.update-btn.save{background:var(--green);border:none;color:#fff}
.update-btn.discard{background:transparent;border:1px solid var(--border);color:var(--muted)}
.empty-state{position:relative;z-index:1;text-align:center;padding:80px 20px;max-width:420px;margin:0 auto}
.empty-icon{width:80px;height:80px;margin:0 auto 24px;border:1px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--muted);opacity:.5}
.empty-state h3{font-family:'Cormorant Garamond',serif;font-size:28px;color:var(--cream);margin-bottom:10px}
.empty-state p{font-size:14px;color:var(--muted);line-height:1.7;margin-bottom:28px}
.browse-btn{display:inline-flex;padding:12px 28px;background:var(--green);border-radius:3px;font-size:13px;font-weight:500;letter-spacing:.08em;text-transform:uppercase;color:#fff;text-decoration:none}
#loginModal{position:fixed;inset:0;z-index:400;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.75);backdrop-filter:blur(6px);opacity:0;pointer-events:none;transition:opacity .3s}
#loginModal.show{opacity:1;pointer-events:all}
.modal-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:36px 32px;max-width:380px;width:90%;text-align:center;position:relative}
.modal-card h3{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--cream);margin-bottom:10px}
.modal-card h3 em{font-style:italic;color:var(--gold)}
.modal-card p{font-size:13.5px;color:var(--muted);line-height:1.6;margin-bottom:24px}
.modal-close{position:absolute;top:12px;right:12px;width:28px;height:28px;border:1px solid var(--border);border-radius:50%;background:transparent;color:var(--muted);cursor:pointer}
.modal-btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
.mb-primary,.mb-ghost,.mb-guest{display:inline-flex;align-items:center;padding:11px 22px;border-radius:3px;font-family:'Jost',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:.08em;text-transform:uppercase;text-decoration:none;cursor:pointer}
.mb-primary{background:var(--green);border:none;color:#fff}
.mb-ghost{border:1px solid var(--border);color:var(--muted);background:transparent}
.mb-guest{border:1px solid var(--gold-dim);color:var(--gold);background:transparent;margin-top:6px}
footer{position:relative;z-index:1;border-top:1px solid var(--border);padding:28px 32px;text-align:center}
footer p{font-size:12px;color:var(--muted)}footer p span{color:var(--gold-dim)}
@media(max-width:768px){.cart-layout{grid-template-columns:1fr;padding:0 16px 80px}.summary-card{position:static}.header-inner{padding:0 16px}nav a:not(.nav-back){display:none}.alert{padding:0 16px}.update-bar{padding:14px 16px}}
</style>
</head>
<body>
<header>
    <div class="header-inner">
        <a href="index.php" class="brand">
            <div class="brand-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg></div>
            <span class="brand-name">SIPPERÉ <span>Café</span></span>
        </a>
        <nav>
            <a href="menu.php">Menu</a>
            <?php if ($isLoggedIn): ?><a href="profile.php">Profile</a><a href="orders.php">Orders</a><a href="log-out.php">Logout</a>
            <?php else: ?><a href="log-in.php">Login</a><a href="register.php">Sign Up</a><?php endif; ?>
            <a href="menu.php" class="nav-back">← Back to Menu</a>
        </nav>
    </div>
</header>

<div class="page-hero">
    <div class="hero-eyebrow">Review your order</div>
    <h1>Your <em>Cart</em></h1>
    <?php if ($itemCount > 0): ?><p class="item-count" id="heroItemCount"><?= $itemCount ?> item<?= $itemCount !== 1 ? 's' : '' ?> in your cart</p><?php endif; ?>
</div>

<?php if ($error): ?><div class="alert"><div><?= htmlspecialchars($error) ?></div></div><?php endif; ?>

<?php if (empty($cartRows)): ?>
<div class="empty-state">
    <div class="empty-icon"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg></div>
    <h3>Your cart is empty</h3>
    <p>Looks like you haven't added anything yet.<br>Browse our menu and pick your favourites.</p>
    <a href="menu.php" class="browse-btn">Browse Menu</a>
</div>
<?php else: ?>
<form method="POST" action="cart.php" id="cartForm" novalidate>
<input type="hidden" name="update" value="1">
<input type="hidden" name="go" id="goFlag" value="0">
<input type="hidden" name="guest" id="guestFlag" value="0">
<div class="cart-layout">
    <div class="cart-items">
    <?php foreach ($cartRows as $it):
        $k = $it['key']; $max = (int)$it['max_stock']; $atLimit = $it['qty'] >= $max; $low = $max <= 10;
        $note = $atLimit ? "Max qty reached ($max in stock)" : ($low ? "Only $max left in stock" : "$max in stock");
        $cls  = $atLimit ? 'limit' : ($low ? 'low' : 'ok');
        $unit = (float)$it['price'] + (float)$it['addon_unit_total'];
    ?>
        <div class="cart-item">
            <div class="item-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg></div>
            <div class="item-info">
                <div class="item-name"><?= htmlspecialchars($it['name']) ?><span class="svc-chip"><?= htmlspecialchars($types[$selSvc] ?? '') ?></span></div>
                <?php if ($it['spec'] !== ''): ?><div class="item-spec"><?= htmlspecialchars($it['spec']) ?></div><?php endif; ?>
                <?php if ($it['addon_names']): ?><div class="item-addons">+ <?= htmlspecialchars(implode(', ', array_column($it['addon_names'], 'name'))) ?></div><?php endif; ?>
                <?php if ($it['note'] !== ''): ?><div class="item-note">“<?= htmlspecialchars($it['note']) ?>”</div><?php endif; ?>
                <div class="item-unit-price">₱<?= number_format($it['price'], 2) ?> each<?= $it['addon_unit_total'] > 0 ? ' + ₱' . number_format($it['addon_unit_total'], 2) . ' add-ons' : '' ?></div>
                <div class="item-stock-note <?= $cls ?>" id="note-<?= $k ?>"><?= $note ?></div>
            </div>
            <div class="qty-wrap">
                <button type="button" class="qty-btn" id="minus-<?= $k ?>" onclick="changeQty('<?= $k ?>',-1)" <?= $it['qty'] <= 1 ? 'disabled' : '' ?>>−</button>
                <input type="text" inputmode="numeric" autocomplete="off" class="qty-input" name="qty[<?= $k ?>]" id="qty-<?= $k ?>" value="<?= $it['qty'] ?>" data-max="<?= $max ?>" data-unit="<?= $unit ?>" data-original="<?= $it['qty'] ?>" oninput="onQtyInput(this)" onblur="onQtyBlur(this)" aria-label="Quantity">
                <button type="button" class="qty-btn" id="plus-<?= $k ?>" onclick="changeQty('<?= $k ?>',1)" <?= $atLimit ? 'disabled' : '' ?>>+</button>
            </div>
            <div class="item-subtotal" id="sub-<?= $k ?>"><small>₱</small><?= number_format($it['subtotal'], 2) ?></div>
            <a href="cart.php?remove=<?= $k ?>" class="remove-btn" title="Remove" aria-label="Remove <?= htmlspecialchars($it['name']) ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
            </a>
        </div>
    <?php endforeach; ?>
    </div>

    <div class="summary-card">
        <div class="summary-title">Order Summary</div>

        <p class="sec-label">Dine-in, Take-out, Pick-up or Delivery</p>
        <?php svcWidget($selSvc, $addr, $phone, true); ?>
        <div class="dlv-when" id="dlvWhen" hidden>
            <div class="dlv-row">
                <div><label for="dDate">Delivery date</label><input type="date" id="dDate" name="delivery_date" min="<?= $minDate ?>" max="<?= $maxDate ?>" value="<?= htmlspecialchars($dDate) ?>"></div>
                <div><label for="dTime">Time</label><input type="time" id="dTime" name="delivery_time" min="<?= DLV_OPEN ?>" max="<?= DLV_CLOSE ?>" value="<?= htmlspecialchars($dTime) ?>"></div>
            </div>
            <p class="dlv-hint">Delivery runs <?= date('g:i A', strtotime(DLV_OPEN)) ?> to <?= date('g:i A', strtotime(DLV_CLOSE)) ?>, at least <?= DLV_MIN_LEAD_MIN ?> minutes ahead. The staff will approve your order and tell you how long it will take.</p>
        </div>

        <div class="summary-row" style="margin-top:18px"><span>Items (<span id="summaryCount"><?= $itemCount ?></span>)</span><span class="val" id="summarySubtotal">₱<?= number_format($total, 2) ?></span></div>
        <div class="summary-row" id="svcFeeRow" style="display:none"><span>Delivery fee</span><span class="val" style="font-size:15px"><?= $feeAmt > 0 ? '₱' . number_format($feeAmt, 2) : 'Free' ?></span></div>
        <div class="summary-row total"><span>Total</span><span class="val" id="svcTotal" data-base="<?= $total ?>">₱<?= number_format($total, 2) ?></span></div>

        <?php if ($isLoggedIn): ?>
            <button type="button" class="checkout-btn" onclick="goCheckout(0)">Proceed to Checkout</button>
        <?php else: ?>
            <button type="button" class="checkout-btn" onclick="showLoginModal()">Proceed to Checkout</button>
            <div class="guest-note"><a href="log-in.php">Login</a> or <a href="register.php">Sign Up</a> to checkout, or continue as guest.</div>
        <?php endif; ?>
        <a href="menu.php" class="continue-link">← Continue shopping</a>
        <p class="summary-note">🔒 Secure checkout · Orders prepared fresh upon confirmation.</p>
    </div>
</div>
</form>

<div class="update-bar" id="updateBar">
    <div class="update-bar-msg">Quantities changed. <span>Save to apply?</span></div>
    <div class="update-bar-btns">
        <button type="button" class="update-btn discard" onclick="discardChanges()">Discard</button>
        <button type="button" class="update-btn save" onclick="saveChanges()">Save Changes</button>
    </div>
</div>
<?php endif; ?>

<div id="loginModal">
    <div class="modal-card">
        <button type="button" class="modal-close" onclick="closeLoginModal()">✕</button>
        <h3>Ready to <em>Order?</em></h3>
        <p>Login or create a free account to checkout. You can also continue as a guest.</p>
        <div class="modal-btns"><a href="log-in.php" class="mb-primary">Login</a><a href="register.php" class="mb-ghost">Sign Up</a></div>
        <div style="margin-top:14px"><button type="button" class="mb-guest" onclick="goCheckout(1)">Continue as Guest</button></div>
    </div>
</div>

<footer><p>© 2026 <span>SIPPERÉ Café</span> — All rights reserved.</p></footer>

<script>
var TYPES = <?= json_encode($types) ?>, FEE = <?= json_encode($feeAmt) ?>;
function $(i){return document.getElementById(i)}
function fmt(v){return '₱'+v.toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2})}
function curSvc(){var r=document.querySelector('input[name="service_type"]:checked');return r?r.value:''}

function recalcAll(){
    var grand=0,n=0;
    document.querySelectorAll('.qty-input').forEach(function(inp){
        var id=inp.id.replace('qty-',''),q=parseInt(inp.value)||0,sub=(parseFloat(inp.dataset.unit)||0)*q;
        var el=$('sub-'+id); if(el) el.innerHTML='<small>₱</small>'+sub.toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
        grand+=sub;n+=q;
    });
    $('summarySubtotal').textContent=fmt(grand);
    $('summaryCount').textContent=n;
    var t=$('svcTotal'); t.dataset.base=grand; t.textContent=fmt(grand+(curSvc()==='delivery'?FEE:0));
    var h=$('heroItemCount'); if(h) h.textContent=n+' item'+(n!==1?'s':'')+' in your cart';
}
function updateControls(inp){
    var id=inp.id.replace('qty-',''),q=parseInt(inp.value)||0,max=parseInt(inp.dataset.max)||99,lim=q>=max,low=max<=10;
    if($('minus-'+id)) $('minus-'+id).disabled=q<=1;
    if($('plus-'+id))  $('plus-'+id).disabled=lim;
    var note=$('note-'+id);
    if(note){ if(lim){note.textContent='Max qty reached ('+max+' in stock)';note.className='item-stock-note limit'}
      else if(low){note.textContent='Only '+max+' left in stock';note.className='item-stock-note low'}
      else{note.textContent=max+' in stock';note.className='item-stock-note ok'} }
}
function checkChanges(){
    var ch=false; document.querySelectorAll('.qty-input').forEach(function(i){if(parseInt(i.value)!==parseInt(i.dataset.original))ch=true});
    $('updateBar').classList.toggle('visible',ch);
}
function onQtyInput(inp){
    inp.value=inp.value.replace(/[^0-9]/g,''); var v=parseInt(inp.value),max=parseInt(inp.dataset.max)||99;
    if(!isNaN(v)&&v>max){inp.value=max;v=max}
    if(!isNaN(v)&&v>=1){updateControls(inp);recalcAll();checkChanges()}
}
function onQtyBlur(inp){
    var max=parseInt(inp.dataset.max)||99,v=parseInt(inp.value); if(isNaN(v)||v<1)v=1; if(v>max)v=max;
    inp.value=v;updateControls(inp);recalcAll();checkChanges();
}
function changeQty(id,d){
    var inp=$('qty-'+id),max=parseInt(inp.dataset.max)||99,v=Math.max(1,Math.min((parseInt(inp.value)||0)+d,max));
    inp.value=v;updateControls(inp);recalcAll();checkChanges();
}
function saveChanges(){$('goFlag').value=0;$('cartForm').submit()}
function discardChanges(){
    document.querySelectorAll('.qty-input').forEach(function(i){i.value=i.dataset.original;updateControls(i)});
    recalcAll();$('updateBar').classList.remove('visible');
}
function goCheckout(guest){ $('goFlag').value=1; $('guestFlag').value=guest?1:0; $('cartForm').submit(); }

// service type: show delivery date/time, mark every line, update fee
function onSvc(){
    var s=curSvc(), d=s==='delivery';
    $('dlvWhen').hidden=!d;
    $('dDate').required=d; $('dTime').required=d;
    document.querySelectorAll('.svc-chip').forEach(function(c){c.textContent=TYPES[s]||''});
    recalcAll();
}
document.querySelectorAll('input[name="service_type"]').forEach(function(i){i.addEventListener('change',onSvc)});
onSvc();

function showLoginModal(){$('loginModal').classList.add('show')}
function closeLoginModal(){$('loginModal').classList.remove('show')}
$('loginModal').addEventListener('click',function(e){if(e.target===this)closeLoginModal()});
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeLoginModal()});
</script>
</body>
</html>
