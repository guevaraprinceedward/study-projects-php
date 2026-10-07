<?php
/**
 * checkout.php (v3) — checkout for the serve cart.
 *   - reads the line cart ($_SESSION['cart']) and the order type chosen in cart.php ($_SESSION['order_svc'])
 *   - payment: Cash / GCash / Maya / Card (only the last 4 digits of a card are kept, never the PIN)
 *   - creates orders + order_items (size, hot/iced, sugar, add-ons, note) and deducts stock in ONE transaction
 *   - then redirects to checkout.php?receipt=TOKEN (refresh never orders twice) and opens the receipt
 *   - delivery orders start as 'pending' and wait for staff approval (admin-delivery.php)
 */
if (session_status() === PHP_SESSION_NONE) session_start();
include_once 'config.php';
include_once 'customize-lib.php';
include_once 'delivery-checkout.inc.php';
include_once 'order-lib.php';
ordEnsureSchema($conn);

$uid        = ordValidUserId($conn);
$isLoggedIn = $uid > 0;
$sessName   = $isLoggedIn ? (string)($_SESSION['user']['username'] ?? '') : '';
$methods    = ordPayMethods();
$types      = custOrderTypes();
$h          = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);

// ══ RECEIPT VIEW (after a successful order) ═══════════════════════════════
$success = false; $receipt = null;
if (isset($_GET['receipt'])) {
    $tok = preg_replace('/[^a-f0-9]/', '', strtolower((string)$_GET['receipt']));
    $o   = ordLoadByToken($conn, $tok);
    if (!$o) { header('Location: menu.php'); exit(); }
    $mine    = $isLoggedIn && (int)$o['user_id'] === $uid;
    $receipt = ordReceiptData($conn, $o, $mine ? $sessName : '');
    $success = true;
}

$error = ''; $cartRows = []; $subtotal = 0.0; $osvc = null; $isDelivery = false; $fee = 0.0; $total = 0.0; $branch = 'laguna';
$payKey = 'cash'; $cashValue = ''; $refValue = '';

if (!$success) {
    // guests must come through the "Continue as Guest" button of the cart
    if (isset($_GET['guest']) && !$isLoggedIn) $_SESSION['guest_checkout'] = 1;
    if ($isLoggedIn) unset($_SESSION['guest_checkout']);
    if (!$isLoggedIn && empty($_SESSION['guest_checkout'])) { header('Location: cart.php'); exit(); }

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
    custMigrateCart($_SESSION['cart']);
    [$cartRows, $subtotal] = custBuildCart($conn, $_SESSION['cart']);
    $osvc = $_SESSION['order_svc'] ?? null;
    if (!$cartRows || !is_array($osvc) || !isset($types[$osvc['type'] ?? ''])) { header('Location: cart.php'); exit(); }

    $isDelivery = $osvc['type'] === 'delivery';
    $fee        = $isDelivery ? (float)$osvc['fee'] : 0.0;
    $total      = $subtotal + $fee;
    $branches   = array_values(array_unique(array_map(fn($r) => (string)$r['branch'], $cartRows)));
    $mixed      = count($branches) > 1;
    $branch     = $branches[0] ?? 'laguna';
    $whenLabel  = ($isDelivery && !empty($osvc['scheduled_for'])) ? date('D, M j · g:i A', strtotime($osvc['scheduled_for'])) : '';

    $postPay   = is_string($_POST['payment'] ?? null) ? $_POST['payment'] : '';
    $payKey    = isset($methods[$postPay]) ? $postPay : 'cash';
    $cashValue = $_POST['cash_amount'] ?? number_format($total, 2, '.', '');
    $refValue  = $_POST['pay_ref'] ?? '';
    $gName     = trim((string)($_POST['guest_name'] ?? ''));
    $gPhone    = trim((string)($_POST['guest_phone'] ?? ($isDelivery ? $osvc['phone'] : '')));

    // ── PLACE ORDER ──
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
        $custNotes = mb_substr(trim((string)($_POST['notes'] ?? '')), 0, 400);
        $payRef = null; $cardLast4 = null; $cashTendered = null;

        do {
            if ($mixed) { $error = 'Your cart has items from both branches. Please keep one branch per order.'; break; }
            if (!$isLoggedIn) {
                if ($gName === '' || mb_strlen($gName) > 100) { $error = 'Please enter your name.'; break; }
                if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $gPhone)) { $error = 'Please enter a valid phone number.'; break; }
            }
            if ($isDelivery) {                                   // time may have passed while the customer was paying
                $chk = dlvResolveSchedule(substr((string)$osvc['scheduled_for'], 0, 10), substr((string)$osvc['scheduled_for'], 11, 5));
                if (!$chk['ok']) { $error = $chk['error'] . ' Please go back to the cart and choose a new delivery time.'; break; }
            }
            if (!isset($methods[$postPay])) { $error = 'Please choose a payment method.'; break; }

            if ($payKey === 'cash') {
                $raw = str_replace([',', ' ', '₱'], '', (string)($_POST['cash_amount'] ?? ''));
                if ($raw === '' || !is_numeric($raw)) { $error = 'Enter the cash amount you will pay.'; break; }
                $cashTendered = round((float)$raw, 2);
                if ($cashTendered + 0.001 < $total) { $error = 'The cash amount is less than the total of ₱' . number_format($total, 2) . '.'; break; }
                if ($cashTendered > 9999999) { $error = 'Please enter a valid cash amount.'; break; }
            } elseif ($payKey === 'gcash' || $payKey === 'maya') {
                $ref = strtoupper(preg_replace('/\s+/', '', trim((string)($_POST['pay_ref'] ?? ''))));
                if (!preg_match('/^[A-Z0-9]{8,20}$/', $ref)) { $error = 'Enter a valid ' . $methods[$payKey] . ' reference number (8 to 20 letters or numbers).'; break; }
                $dup = $conn->prepare("SELECT id FROM orders WHERE payment_ref = ? AND payment_method = ? LIMIT 1");
                $dup->bind_param('ss', $ref, $payKey);
                $dup->execute(); $dup->store_result();
                $isDup = $dup->num_rows > 0; $dup->close();
                if ($isDup) { $error = 'That reference number was already used for another payment.'; break; }
                $payRef = $ref;
            } else {
                $num = preg_replace('/\D/', '', (string)($_POST['card_number'] ?? ''));
                $pin = (string)($_POST['card_pin'] ?? '');
                if (strlen($num) < 13 || strlen($num) > 19) { $error = 'Enter a valid card number (13 to 19 digits).'; break; }
                if (!preg_match('/^(\d{4}|\d{6})$/', $pin)) { $error = 'Enter your 4 or 6 digit card PIN.'; break; }
                $payRef = 'CARD ****' . substr($num, -4);   // the full number and PIN are thrown away
                $num = $pin = null;
                unset($_POST['card_number'], $_POST['card_pin']);
            }

            $conn->begin_transaction();
            try {
                // lock + re-check stock (one customer can't take what another just bought)
                $need = [];
                foreach ($cartRows as $r) { $need[(int)$r['id']] = ($need[(int)$r['id']] ?? 0) + (int)$r['qty']; }
                ksort($need);
                $lock = $conn->prepare("SELECT name, stock FROM products WHERE id = ? FOR UPDATE");
                foreach ($need as $pid => $qty) {
                    $lock->bind_param('i', $pid); $lock->execute();
                    $p = $lock->get_result()->fetch_assoc();
                    if (!$p || (int)$p['stock'] < $qty) throw new Exception('Sorry, "' . ($p['name'] ?? 'an item') . '" no longer has enough stock. Please update your cart.');
                }
                $lock->close();

                $userParam = $isLoggedIn ? $uid : null;
                $nameParam = $isLoggedIn ? null : $gName;
                $phoneParam = $isLoggedIn ? null : $gPhone;
                $svcType = $osvc['type'];
                $dAddr   = $isDelivery ? $osvc['address'] : null;
                $dPhone  = $isDelivery ? $osvc['phone'] : null;
                $dFee    = $isDelivery ? $fee : 0.0;
                $sched   = $isDelivery ? $osvc['scheduled_for'] : null;
                $token   = dlvNewToken();
                $notes   = $custNotes;

                $st = $conn->prepare("INSERT INTO orders
                    (user_id, total, notes, status, branch, payment_method, payment_ref, guest_name, guest_phone,
                     service_type, delivery_address, delivery_phone, delivery_fee, scheduled_for, track_token, cash_tendered, created_at)
                    VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $st->bind_param('idsssssssssdssd', $userParam, $total, $notes, $branch, $payKey, $payRef, $nameParam, $phoneParam,
                                $svcType, $dAddr, $dPhone, $dFee, $sched, $token, $cashTendered);
                if (!$st->execute()) throw new Exception('Could not create the order. Please try again.');
                $orderId = (int)$conn->insert_id;
                $st->close();
                if (!$orderId) throw new Exception('Could not create the order. Please try again.');

                $code = 'AYS-' . date('Ymd') . '-' . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT);
                $st = $conn->prepare("UPDATE orders SET receipt_code = ? WHERE id = ?");
                $st->bind_param('si', $code, $orderId); $st->execute(); $st->close();

                $ins = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, addons, addons_total, spec, item_note)
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($cartRows as $r) {
                    $pid = (int)$r['id']; $qty = (int)$r['qty']; $price = (float)$r['price'];
                    $ad  = json_encode(array_column($r['addon_names'], 'name'), JSON_UNESCAPED_UNICODE);
                    $adT = (float)$r['addon_unit_total']; $spec = (string)$r['spec']; $inote = (string)$r['note'];
                    $ins->bind_param('iiidsdss', $orderId, $pid, $qty, $price, $ad, $adT, $spec, $inote);
                    if (!$ins->execute()) throw new Exception('Could not save the order items. Please try again.');
                }
                $ins->close();

                $dec = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                foreach ($need as $pid => $qty) {
                    $dec->bind_param('iii', $qty, $pid, $qty); $dec->execute();
                    if ($dec->affected_rows < 1) throw new Exception('Stock changed while you were paying. Please review your cart.');
                }
                $dec->close();

                $conn->commit();
                unset($_SESSION['cart'], $_SESSION['order_svc'], $_SESSION['svc_type']);
                header('Location: checkout.php?receipt=' . $token); exit();
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        } while (false);
    }
}

$payIcons = [
    'cash'  => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/>',
    'gcash' => '<path d="M12 2a10 10 0 1 0 10 10H12V2z"/><path d="M12 2a10 10 0 0 1 10 10"/>',
    'maya'  => '<circle cx="12" cy="12" r="9"/><path d="M8 15V9l4 4 4-4v6"/>',
    'card'  => '<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout — SIPPERÉ Café</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;--gold:#c9a84c;--gold-dim:#8a6f2e;--green:#4a7a3a;--green-lt:#6aaa52;--red:#8b2e2e;--red-lt:#c0392b;--cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8}
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
nav a{font-size:12.5px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);text-decoration:none;padding:8px 14px;border-radius:3px}
nav a:hover{color:var(--cream);background:rgba(255,255,255,.04)}
.nav-back{border:1px solid var(--border)}
.page-hero{position:relative;z-index:1;text-align:center;padding:56px 32px 40px}
.hero-eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:14px}
.hero-eyebrow::before,.hero-eyebrow::after{content:'';width:28px;height:1px;background:var(--gold-dim)}
.page-hero h1{font-family:'Cormorant Garamond',serif;font-size:clamp(36px,5vw,56px);font-weight:700;color:var(--cream)}
.page-hero h1 em{font-style:italic;color:var(--gold)}
.error-banner{max-width:1100px;margin:0 auto 22px;padding:0 32px;position:relative;z-index:1}
.error-banner div{background:rgba(139,46,46,.15);border:1px solid var(--red);border-radius:3px;padding:12px 16px;color:#e07b7b;font-size:13px;line-height:1.5}
.checkout-layout{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:0 32px 80px;display:grid;grid-template-columns:1fr 370px;gap:28px;align-items:start}
.form-card{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:30px}
.section-label{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--cream);display:flex;align-items:center;gap:12px;margin-bottom:18px}
.section-label::after{content:'';flex:1;height:1px;background:var(--border)}
.info{background:var(--surface);border:1px solid var(--gold-dim);border-radius:4px;padding:14px 16px;margin-bottom:26px;display:grid;grid-template-columns:1fr 1fr;gap:10px 18px;font-size:13px}
.info .k{display:block;font-size:9.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);margin-bottom:2px}
.info .v{color:var(--cream);word-break:break-word}.info .full{grid-column:1/-1}
.info a{color:var(--gold);font-size:12px;text-decoration:none}
.field-row{margin-bottom:16px}
.field-row label{display:block;font-size:11px;font-weight:500;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-bottom:8px}
.field-row textarea,.field-row input[type=text],.field-row input[type=tel],.field-row input[type=password]{width:100%;background:var(--surface);border:1px solid var(--border);border-radius:3px;color:var(--cream);font-family:'Jost',sans-serif;font-size:14px;padding:11px 14px;outline:none}
.field-row textarea{resize:vertical;min-height:76px}
.field-row input:focus,.field-row textarea:focus{border-color:var(--gold-dim)}
.hint{font-size:11.5px;color:var(--muted);margin-top:7px;line-height:1.55}
.pay-methods{display:flex;gap:10px;flex-wrap:wrap}
.pay-method{position:relative;flex:1;min-width:130px;background:var(--surface);border:1px solid var(--border);border-radius:3px;padding:13px 15px;cursor:pointer;display:flex;align-items:center;gap:10px;font-size:13px;font-weight:500;color:var(--muted);user-select:none}
.pay-method input{position:absolute;opacity:0;pointer-events:none}
.pay-method:has(input:checked){border-color:var(--gold-dim);background:rgba(201,168,76,.06);color:var(--cream)}
.pay-method:has(input:focus-visible){outline:2px solid var(--gold);outline-offset:2px}
.pay-icon{width:28px;height:28px;background:var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--gold-dim)}
.pay-panel{margin-top:16px;background:var(--surface);border:1px solid var(--border);border-radius:4px;padding:16px 16px 2px}
.pay-panel[hidden]{display:none}
.pay-panel input{background:var(--card)!important}
.change-line{display:flex;justify-content:space-between;align-items:baseline;font-size:12.5px;color:var(--muted);margin:-4px 0 14px}
.change-line strong{font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--green-lt)}.change-line strong.short{color:var(--red-lt)}
.pay-error{display:none;margin-top:14px;background:rgba(139,46,46,.15);border:1px solid var(--red);border-radius:3px;padding:10px 14px;color:#e07b7b;font-size:13px}
.pay-error.show{display:block}
.summary-card{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:26px 24px;position:sticky;top:88px}
.summary-title{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;color:var(--cream);padding-bottom:14px;border-bottom:1px solid var(--border);margin-bottom:16px}
.oi{display:flex;justify-content:space-between;gap:10px;font-size:13px;color:var(--text);margin-top:9px}
.oi .q{color:var(--gold-dim)}.oi .p{font-weight:500}
.oi-sub{font-size:11px;color:var(--gold-dim);font-style:italic;margin:1px 0 0 2px}
.divider{height:1px;background:var(--border);margin:14px 0}
.summary-row{display:flex;justify-content:space-between;align-items:center;font-size:13.5px;color:var(--muted);margin-bottom:10px}
.summary-row.total{font-size:15px;color:var(--cream);font-weight:500;padding-top:14px;border-top:1px solid var(--border);margin-top:6px;margin-bottom:0}
.summary-row .val{font-family:'Cormorant Garamond',serif;font-size:18px;color:var(--text);font-weight:600}
.summary-row.total .val{font-size:26px;color:var(--gold)}
.place-btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:15px;margin-top:20px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;color:#fff;cursor:pointer}
.place-btn:hover:not(:disabled){background:var(--green-lt)}.place-btn:disabled{opacity:.55;cursor:not-allowed}
.back-link{display:block;text-align:center;margin-top:14px;font-size:12.5px;color:var(--muted);text-decoration:none}.back-link:hover{color:var(--gold)}
.summary-note{margin-top:18px;padding-top:14px;border-top:1px solid var(--border);font-size:11.5px;color:var(--muted);line-height:1.6;text-align:center}
.success-wrap{position:relative;z-index:1;max-width:520px;margin:0 auto;text-align:center;padding:6px 32px 80px}
.success-icon{width:88px;height:88px;margin:0 auto 24px;border:1px solid var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--green-lt)}
.success-wrap h2{font-family:'Cormorant Garamond',serif;font-size:38px;font-weight:700;color:var(--cream);margin-bottom:12px}
.success-wrap h2 em{font-style:italic;color:var(--gold)}
.success-wrap p{font-size:14px;color:var(--muted);line-height:1.7;margin-bottom:8px}.success-wrap p strong{color:var(--gold);font-weight:500}
.order-badge{display:inline-flex;background:var(--card);border:1px solid var(--gold-dim);border-radius:3px;padding:8px 18px;font-size:13px;color:var(--gold);margin:12px 0 22px;letter-spacing:.08em}
.success-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
.btn-primary,.btn-ghost{display:inline-flex;align-items:center;padding:13px 26px;border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:.08em;text-transform:uppercase;text-decoration:none;cursor:pointer}
.btn-primary{background:var(--green);color:#fff;border:none}.btn-primary:hover{background:var(--green-lt)}
.btn-ghost{border:1px solid var(--border);color:var(--muted);background:transparent}.btn-ghost:hover{border-color:var(--gold-dim);color:var(--gold)}
footer{position:relative;z-index:1;border-top:1px solid var(--border);padding:28px 32px;text-align:center}
footer p{font-size:12px;color:var(--muted)}footer p span{color:var(--gold-dim)}
@media(max-width:768px){.checkout-layout{grid-template-columns:1fr;padding:0 16px 60px}.summary-card{position:static}.header-inner{padding:0 16px}nav a:not(.nav-back){display:none}.form-card{padding:22px 18px}.error-banner{padding:0 16px}.info{grid-template-columns:1fr}}
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
            <?php if ($isLoggedIn): ?><a href="orders.php">Orders</a><a href="log-out.php">Logout</a><?php endif; ?>
            <a href="<?= $success ? 'menu.php' : 'cart.php' ?>" class="nav-back">← <?= $success ? 'Back to Menu' : 'Back to Cart' ?></a>
        </nav>
    </div>
</header>

<?php if ($success): ?>
<div class="page-hero"><div class="hero-eyebrow">Order confirmed</div><h1>Order <em>Placed</em></h1></div>
<div class="success-wrap">
    <div class="success-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
    <h2>Thank <em>you!</em></h2>
    <p>Hi <strong><?= $h($receipt['name']) ?></strong>, we received your <strong><?= $h(strtolower($receipt['service_label'] ?: 'order')) ?></strong> order.</p>
    <?php if ($receipt['service'] === 'delivery'): ?>
    <p>The staff will review it first. Once approved you will see the estimated time on the tracking page.</p>
    <?php else: ?>
    <p>The staff will start on it shortly.</p>
    <?php endif; ?>
    <div class="order-badge"><?= $h($receipt['code']) ?></div>
    <div class="success-actions">
        <button type="button" class="btn-primary" onclick="openOrderReceipt(ORD_RECEIPT,{fresh:true})">View Receipt</button>
        <?php if ($isLoggedIn): ?><a href="orders.php" class="btn-ghost">My Orders</a><?php endif; ?>
        <?php if ($receipt['track_url']): ?><a href="<?= $h($receipt['track_url']) ?>" class="btn-ghost">Track Order</a><?php endif; ?>
        <a href="menu.php" class="btn-ghost">Back to Menu</a>
    </div>
</div>
<script>
var ORD_LOGGED_IN = <?= $isLoggedIn ? 'true' : 'false' ?>;
var ORD_RECEIPT = <?= ordJson($receipt) ?>;
document.addEventListener('DOMContentLoaded', function(){ openOrderReceipt(ORD_RECEIPT, {fresh:true}); });
</script>
<?php include 'order-receipt.inc.php'; ?>

<?php else: ?>
<div class="page-hero"><div class="hero-eyebrow">Almost there</div><h1>Check<em>out</em></h1></div>
<?php if ($error): ?><div class="error-banner"><div><?= $h($error) ?></div></div><?php endif; ?>
<?php if (!empty($mixed)): ?><div class="error-banner"><div>Your cart has items from both branches. <a href="cart.php" style="color:var(--gold)">Go back to the cart</a> and remove the items of one branch.</div></div><?php endif; ?>

<form method="POST" action="checkout.php" id="checkoutForm" autocomplete="off" novalidate>
<input type="hidden" name="place_order" value="1">
<div class="checkout-layout">
    <div class="form-card">
        <div class="section-label">Your Order</div>
        <div class="info">
            <div><span class="k">Order type</span><span class="v"><?= $h($osvc['label']) ?></span></div>
            <div><span class="k">Branch</span><span class="v"><?= $h(ucfirst($branch)) ?></span></div>
            <?php if ($isDelivery): ?>
            <div class="full"><span class="k">Deliver to</span><span class="v"><?= $h($osvc['address']) ?></span></div>
            <div><span class="k">Contact</span><span class="v"><?= $h($osvc['phone']) ?></span></div>
            <div><span class="k">Scheduled for</span><span class="v"><?= $h($whenLabel) ?></span></div>
            <?php endif; ?>
            <div class="full"><a href="cart.php">Change order type / items</a></div>
        </div>

        <?php if (!$isLoggedIn): ?>
        <div class="section-label">Your Details</div>
        <div class="field-row"><label for="gName">Name</label><input type="text" id="gName" name="guest_name" maxlength="100" value="<?= $h($gName) ?>" placeholder="Full name"></div>
        <div class="field-row"><label for="gPhone">Phone number</label><input type="tel" id="gPhone" name="guest_phone" maxlength="20" value="<?= $h($gPhone) ?>" placeholder="09XX-XXX-XXXX"></div>
        <?php endif; ?>

        <div class="section-label" style="margin-top:8px">Payment Method</div>
        <div class="pay-methods">
        <?php foreach ($methods as $key => $label): ?>
            <label class="pay-method">
                <input type="radio" name="payment" value="<?= $h($key) ?>" <?= $payKey === $key ? 'checked' : '' ?> onchange="showPanel()">
                <div class="pay-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $payIcons[$key] ?></svg></div>
                <?= $h($label) ?>
            </label>
        <?php endforeach; ?>
        </div>

        <div class="pay-panel" id="panelCash" <?= $payKey === 'cash' ? '' : 'hidden' ?>>
            <div class="field-row">
                <label for="cashAmount">Cash amount (₱)</label>
                <input type="text" id="cashAmount" name="cash_amount" inputmode="decimal" value="<?= $h($cashValue) ?>" oninput="updateChange()">
                <p class="hint"><?= $isDelivery ? 'Cash on delivery: enter the amount you will hand to the rider.' : 'Enter the amount you will hand over at the counter.' ?> It must cover the total.</p>
            </div>
            <div class="change-line"><span>Change</span><strong id="changeAmt">₱0.00</strong></div>
        </div>
        <div class="pay-panel" id="panelRef" <?= ($payKey === 'gcash' || $payKey === 'maya') ? '' : 'hidden' ?>>
            <div class="field-row">
                <label for="payRef" id="refLabel"><?= $payKey === 'maya' ? 'Maya' : 'GCash' ?> Ref. No.</label>
                <input type="text" id="payRef" name="pay_ref" maxlength="26" value="<?= $h($refValue) ?>" placeholder="e.g. 1234 567 890123" style="text-transform:uppercase">
                <p class="hint">Send the exact total of ₱<?= number_format($total, 2) ?> in your app, then type the reference number from the receipt.</p>
            </div>
        </div>
        <div class="pay-panel" id="panelCard" <?= $payKey === 'card' ? '' : 'hidden' ?>>
            <div class="field-row"><label for="cardNumber">Card number</label><input type="text" id="cardNumber" name="card_number" inputmode="numeric" maxlength="23" placeholder="0000 0000 0000 0000" autocomplete="off"></div>
            <div class="field-row"><label for="cardPin">Card PIN</label><input type="password" id="cardPin" name="card_pin" inputmode="numeric" maxlength="6" placeholder="4 or 6 digits" autocomplete="new-password"></div>
            <p class="hint" style="margin:-4px 0 14px">Only the last 4 digits of the card are saved. The PIN and full number are never stored.</p>
        </div>
        <div class="pay-error" id="payError" role="alert"></div>

        <div class="field-row" style="margin-top:22px">
            <label for="payNotes">Order Notes <span style="text-transform:none;letter-spacing:0">(optional)</span></label>
            <textarea id="payNotes" name="notes" maxlength="400" placeholder="Any special requests?"><?= $h($_POST['notes'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="summary-card">
        <div class="summary-title">Order Summary</div>
        <?php foreach ($cartRows as $r): ?>
        <div class="oi"><span><?= $h($r['name']) ?> <span class="q">×<?= (int)$r['qty'] ?></span></span><span class="p">₱<?= number_format($r['subtotal'], 2) ?></span></div>
        <?php $sub = trim($r['spec'] . ($r['addon_names'] ? ' · + ' . implode(', ', array_column($r['addon_names'], 'name')) : ''), ' ·');
              if ($sub !== ''): ?><div class="oi-sub"><?= $h($sub) ?></div><?php endif; ?>
        <?php if ($r['note'] !== ''): ?><div class="oi-sub">“<?= $h($r['note']) ?>”</div><?php endif; ?>
        <?php endforeach; ?>
        <div class="divider"></div>
        <div class="summary-row"><span>Subtotal</span><span class="val">₱<?= number_format($subtotal, 2) ?></span></div>
        <div class="summary-row"><span>Delivery fee</span><span class="val" style="font-size:15px"><?= $isDelivery && $fee > 0 ? '₱' . number_format($fee, 2) : ($isDelivery ? 'Free' : '—') ?></span></div>
        <div class="summary-row total"><span>Total</span><span class="val">₱<?= number_format($total, 2) ?></span></div>
        <button type="submit" class="place-btn" id="placeBtn" <?= !empty($mixed) ? 'disabled' : '' ?>>
            <?= $isDelivery ? 'Place order for approval' : 'Place order' ?> · ₱<?= number_format($total, 2) ?>
        </button>
        <a href="cart.php" class="back-link">← Back to cart</a>
        <p class="summary-note"><?= $isDelivery ? 'Delivery orders are approved by the staff first. You will see the estimated time right after.' : 'Your order is prepared fresh once confirmed.' ?></p>
    </div>
</div>
</form>

<script>
var TOTAL = <?= json_encode($total) ?>, GUEST = <?= $isLoggedIn ? 'false' : 'true' ?>, submitting = false;
function $(i){return document.getElementById(i)}
function peso(v){return '₱'+Number(v).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2})}
function method(){var r=document.querySelector('input[name="payment"]:checked');return r?r.value:'cash'}
function showPanel(){
    var m=method();
    $('panelCash').hidden=(m!=='cash'); $('panelRef').hidden=!(m==='gcash'||m==='maya'); $('panelCard').hidden=(m!=='card');
    $('refLabel').textContent=(m==='maya'?'Maya':'GCash')+' Ref. No.'; $('payError').classList.remove('show');
    if(m==='cash') updateChange();
}
function updateChange(){
    var v=parseFloat(String($('cashAmount').value).replace(/[^0-9.]/g,'')), el=$('changeAmt');
    if(isNaN(v)){el.textContent=peso(0);el.classList.remove('short');return}
    var d=v-TOTAL;
    if(d>=-0.001){el.textContent=peso(Math.max(0,d));el.classList.remove('short')} else {el.textContent='Short by '+peso(-d);el.classList.add('short')}
}
$('cardNumber').addEventListener('input',function(){this.value=this.value.replace(/\D/g,'').slice(0,19).replace(/(.{4})/g,'$1 ').trim()});
$('cardPin').addEventListener('input',function(){this.value=this.value.replace(/\D/g,'').slice(0,6)});
$('payRef').addEventListener('input',function(){this.value=this.value.toUpperCase().replace(/[^A-Z0-9 ]/g,'')});
function validate(){
    if(GUEST){
        if(!$('gName').value.trim()) return 'Please enter your name.';
        if(!/^[0-9+\-\s()]{7,20}$/.test($('gPhone').value.trim())) return 'Please enter a valid phone number.';
    }
    var m=method();
    if(m==='cash'){var v=parseFloat(String($('cashAmount').value).replace(/[^0-9.]/g,'')); if(isNaN(v)) return 'Enter the cash amount you will pay.'; if(v+0.001<TOTAL) return 'The cash amount is less than the total ('+peso(TOTAL)+').'}
    else if(m==='gcash'||m==='maya'){if(!/^[A-Za-z0-9]{8,20}$/.test($('payRef').value.replace(/\s+/g,''))) return 'Enter a valid reference number (8 to 20 letters or numbers).'}
    else {var n=$('cardNumber').value.replace(/\D/g,''); if(n.length<13||n.length>19) return 'Enter a valid card number (13 to 19 digits).'; if(!/^(\d{4}|\d{6})$/.test($('cardPin').value)) return 'Enter your 4 or 6 digit card PIN.'}
    return '';
}
$('checkoutForm').addEventListener('submit',function(e){
    if(submitting){e.preventDefault();return}
    var msg=validate(), box=$('payError');
    if(msg){e.preventDefault();box.textContent=msg;box.classList.add('show');box.scrollIntoView({behavior:'smooth',block:'center'});return}
    submitting=true; $('placeBtn').disabled=true; $('placeBtn').textContent='Placing your order…';
});
showPanel();
</script>
<?php endif; ?>

<footer><p>© 2026 <span>SIPPERÉ Café</span> — All rights reserved.</p></footer>
</body>
</html>
