<?php
/**
 * reservation-checkout.php  (v2)
 * -------------------------------------------------------------------------
 * Payment page for a RESERVATION. Open it directly:
 *
 *      reservation-checkout.php?reservation=ID
 *
 * (It no longer goes through checkout.php. checkout.php is the SERVE checkout
 *  and sends anyone with an empty serve cart back to cart.php. That was the
 *  bug behind "Pay now" landing on cart.php.)
 *
 * Payment methods
 *   - Cash on Hand        : amount handed over (must cover the total), change is shown
 *   - GCash Ref. No.      : reference number from the GCash receipt
 *   - Maya Ref. No.       : reference number from the Maya receipt
 *   - Credit / Debit Card : card number + card PIN
 *
 * Card data: only the LAST 4 DIGITS of the card are saved. The PIN and the full
 * card number are checked for format and then thrown away. They are never
 * written to the database, a log or the session.
 *
 * What paying does
 *   - creates a normal row in `orders` + `order_items` (so it shows up in the
 *     dashboard sales and in My Orders like any other paid order),
 *   - marks the reservation as paid and links it via reservations.order_id,
 *   - does NOT touch stock again (it was already held when reserved),
 *   - redirects to ?reservation=ID&paid=1 so refreshing never pays twice.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
include_once 'config.php';
include_once 'reservation-init.php';

// Same safety-net columns checkout.php makes sure of
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS notes TEXT DEFAULT NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'pending'");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS branch VARCHAR(20) DEFAULT 'laguna'");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT 'cash'");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS guest_name VARCHAR(100) DEFAULT NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS guest_phone VARCHAR(20) DEFAULT NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS receipt_code VARCHAR(40) DEFAULT NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_ref VARCHAR(60) DEFAULT NULL");
$conn->query("ALTER TABLE order_items ADD COLUMN IF NOT EXISTS price decimal(10,2) DEFAULT NULL");
$conn->query("ALTER TABLE order_items ADD COLUMN IF NOT EXISTS addons TEXT DEFAULT NULL");
$conn->query("ALTER TABLE order_items ADD COLUMN IF NOT EXISTS addons_total DECIMAL(10,2) DEFAULT 0");

$uid        = resValidUserId($conn);
$isLoggedIn = $uid > 0;
$resId      = (int)($_POST['reservation'] ?? $_GET['reservation'] ?? 0);
$res        = resLoadReservation($conn, $resId);

// Must exist and belong to this visitor (logged-in owner, or the guest who made it)
if (!$res || !resCanAccess($res, $uid)) {
    header('Location: ' . ($isLoggedIn ? 'my-reservations.php' : 'reservation-menu.php'));
    exit();
}

// Just paid? (we land here after the redirect)
$justPaid = isset($_GET['paid']) && $res['payment_status'] === 'paid' && $res['status'] !== 'cancelled';

// Nothing to pay if it is already paid / cancelled / picked up
if (!$justPaid && ($res['status'] !== 'reserved' || $res['payment_status'] !== 'unpaid')) {
    header('Location: my-reservations.php');
    exit();
}

$methods   = resPayMethods();
$error     = '';
$success   = $justPaid;
$receipt   = $justPaid ? resReceiptData($res) : null;
$total     = (float)$res['total'];
$branch    = (string)$res['branch'];
$whenLabel = resFormatDate($res['reservation_date']) . ' · ' . resFormatTime($res['reservation_time']);

$postPay   = is_string($_POST['payment'] ?? null) ? $_POST['payment'] : '';
$payKey    = isset($methods[$postPay]) ? $postPay : 'cash';
$cashValue = $_POST['cash_amount'] ?? number_format($total, 2, '.', '');
$refValue  = $_POST['pay_ref'] ?? '';

// ── HANDLE PAYMENT ────────────────────────────────────────────────────────
if (!$success && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $custNotes    = mb_substr(trim((string)($_POST['notes'] ?? '')), 0, 400);
    $payRef       = null;   // GCash / Maya reference (saved)
    $cardLast4    = null;   // card: last 4 digits only (saved)
    $cashTendered = null;   // cash: amount handed over (saved)

    do {
        if (!isset($methods[$postPay])) { $error = 'Please choose a payment method.'; break; }

        if ($payKey === 'cash') {
            $raw = str_replace([',', ' ', '₱'], '', (string)($_POST['cash_amount'] ?? ''));
            if ($raw === '' || !is_numeric($raw)) { $error = 'Enter the cash amount you are handing over.'; break; }
            $cashTendered = round((float)$raw, 2);
            if ($cashTendered + 0.001 < $total) { $error = 'The cash amount is less than the total of ₱' . number_format($total, 2) . '.'; break; }
            if ($cashTendered > 9999999) { $error = 'Please enter a valid cash amount.'; break; }

        } elseif ($payKey === 'gcash' || $payKey === 'maya') {
            $ref = strtoupper(preg_replace('/\s+/', '', trim((string)($_POST['pay_ref'] ?? ''))));
            if (!preg_match('/^[A-Z0-9]{8,20}$/', $ref)) {
                $error = 'Enter a valid ' . $methods[$payKey] . ' reference number (8 to 20 letters or numbers).'; break;
            }
            // the same reference number can't pay two reservations
            $dup = $conn->prepare("SELECT id FROM reservations WHERE payment_ref = ? AND payment_method = ? AND id <> ? LIMIT 1");
            $dup->bind_param('ssi', $ref, $payKey, $resId);
            $dup->execute();
            $dup->store_result();
            $isDup = $dup->num_rows > 0;
            $dup->close();
            if ($isDup) { $error = 'That reference number was already used for another payment.'; break; }
            $payRef = $ref;

        } else { // card
            $num = preg_replace('/\D/', '', (string)($_POST['card_number'] ?? ''));
            $pin = (string)($_POST['card_pin'] ?? '');
            if (strlen($num) < 13 || strlen($num) > 19) { $error = 'Enter a valid card number (13 to 19 digits).'; break; }
            if (!preg_match('/^(\d{4}|\d{6})$/', $pin))  { $error = 'Enter your 4 or 6 digit card PIN.'; break; }
            $cardLast4 = substr($num, -4);
            // Card number and PIN are NOT stored anywhere. Only $cardLast4 is kept.
            $num = $pin = null;
            unset($_POST['card_number'], $_POST['card_pin']);
        }

        // what goes into orders.payment_ref
        $orderRef = $payRef ?? ($cardLast4 ? 'CARD ****' . $cardLast4 : null);

        $conn->begin_transaction();
        try {
            // lock the reservation row and make sure nobody paid/cancelled it meanwhile
            $st = $conn->prepare("SELECT status, payment_status FROM reservations WHERE id = ? FOR UPDATE");
            $st->bind_param('i', $resId);
            $st->execute();
            $cur = $st->get_result()->fetch_assoc();
            $st->close();
            if (!$cur || $cur['status'] !== 'reserved' || $cur['payment_status'] !== 'unpaid') {
                throw new Exception('This reservation can no longer be paid. It may already be paid or cancelled.');
            }

            $code   = $res['reservation_code'] ?: ('RSV-' . str_pad((string)$resId, 5, '0', STR_PAD_LEFT));
            $notes  = '[Reservation ' . $code . ' · ' . $whenLabel . ' · Party of ' . (int)$res['party_size'] . ']';
            if (!empty($res['notes'])) $notes .= ' ' . $res['notes'];
            if ($custNotes !== '')     $notes .= ' | ' . $custNotes;

            $userIdParam = $isLoggedIn ? $uid : null;
            $gName  = $isLoggedIn ? null : $res['guest_name'];
            $gPhone = $isLoggedIn ? null : $res['guest_phone'];

            $st = $conn->prepare("INSERT INTO orders (user_id, total, notes, status, branch, payment_method, payment_ref, guest_name, guest_phone, created_at)
                                  VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, NOW())");
            $st->bind_param('idssssss', $userIdParam, $total, $notes, $branch, $payKey, $orderRef, $gName, $gPhone);
            if (!$st->execute()) throw new Exception('Could not create the order. Please try again.');
            $orderId = (int)$conn->insert_id;
            $st->close();
            if (!$orderId) throw new Exception('Could not create the order. Please try again.');

            // Same receipt code format as normal checkout: AYS-YYYYMMDD-00019
            $receiptCode = 'AYS-' . date('Ymd') . '-' . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT);
            $st = $conn->prepare("UPDATE orders SET receipt_code = ? WHERE id = ?");
            $st->bind_param('si', $receiptCode, $orderId);
            $st->execute();
            $st->close();

            $ins = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, addons, addons_total) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($res['items'] as $it) {
                $pid    = (int)$it['product_id'];
                $qty    = (int)$it['quantity'];
                $price  = (float)$it['price'];
                $addons = json_encode(array_column($it['addon_list'], 'name'), JSON_UNESCAPED_UNICODE);
                $addTot = (float)$it['addons_total'];
                $ins->bind_param('iiidsd', $orderId, $pid, $qty, $price, $addons, $addTot);
                if (!$ins->execute()) throw new Exception('Could not save the order items. Please try again.');
            }
            $ins->close();

            $st = $conn->prepare("UPDATE reservations
                                  SET status = 'paid', payment_status = 'paid', payment_method = ?, payment_ref = ?, card_last4 = ?,
                                      cash_tendered = ?, order_id = ?, paid_at = NOW()
                                  WHERE id = ?");
            $st->bind_param('sssdii', $payKey, $payRef, $cardLast4, $cashTendered, $orderId, $resId);
            $st->execute();
            $st->close();

            $conn->commit();

            header('Location: reservation-checkout.php?reservation=' . $resId . '&paid=1');
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    } while (false);
}

// Data for the brewing animation (one entry per line)
$brewQueue = array_map(function ($i) {
    return ['name' => (string)$i['product_name'], 'category' => (string)($i['p_category'] ?? 'mains')];
}, $res['items']);

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
    <title>Reservation Payment — AyosCoffeeNegosyo</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{
            --bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;
            --gold:#c9a84c;--gold-dim:#8a6f2e;--green:#4a7a3a;--green-lt:#6aaa52;
            --red:#8b2e2e;--red-lt:#c0392b;--cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8;
        }
        html{scroll-behavior:smooth}
        body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden}
        body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 10% 0%,rgba(201,168,76,0.06) 0%,transparent 55%),radial-gradient(ellipse 50% 70% at 90% 100%,rgba(74,122,58,0.07) 0%,transparent 55%);pointer-events:none;z-index:0}
        :focus-visible{outline:2px solid var(--gold);outline-offset:3px}
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
        .page-hero h1{font-family:'Cormorant Garamond',serif;font-size:clamp(36px,5vw,56px);font-weight:700;color:var(--cream)}
        .page-hero h1 em{font-style:italic;color:var(--gold)}

        .checkout-layout{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:0 32px 80px;display:grid;grid-template-columns:1fr 360px;gap:28px;align-items:start}
        .form-card{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:32px}
        .section-label{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--cream);display:flex;align-items:center;gap:12px;margin-bottom:22px}
        .section-label::after{content:'';flex:1;height:1px;background:var(--border)}
        .res-info{background:var(--surface);border:1px solid var(--gold-dim);border-radius:4px;padding:16px 18px;margin-bottom:28px;display:grid;grid-template-columns:1fr 1fr;gap:12px 18px;font-size:13px}
        .res-info .k{display:block;font-size:9.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);margin-bottom:2px}
        .res-info .v{color:var(--cream);word-break:break-word}
        .res-info .full{grid-column:1/-1}
        .field-row{margin-bottom:18px}
        .field-row label{display:block;font-size:11px;font-weight:500;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted);margin-bottom:8px}
        .field-row textarea,.field-row input[type=text],.field-row input[type=password]{width:100%;background:var(--surface);border:1px solid var(--border);border-radius:3px;color:var(--cream);font-family:'Jost',sans-serif;font-size:14px;padding:11px 14px;outline:none;transition:border-color 0.2s}
        .field-row textarea{resize:vertical;min-height:80px}
        .field-row textarea:focus,.field-row input:focus{border-color:var(--gold-dim)}
        .field-row .hint{font-size:11.5px;color:var(--muted);margin-top:7px;line-height:1.55}
        .pay-methods{display:flex;gap:10px;flex-wrap:wrap}
        .pay-method{position:relative;flex:1;min-width:130px;background:var(--surface);border:1px solid var(--border);border-radius:3px;padding:14px 16px;cursor:pointer;display:flex;align-items:center;gap:10px;font-size:13px;font-weight:500;color:var(--muted);transition:all 0.2s;user-select:none}
        .pay-method input{position:absolute;opacity:0;pointer-events:none}
        .pay-method:has(input:checked){border-color:var(--gold-dim);background:rgba(201,168,76,0.06);color:var(--cream)}
        .pay-method:has(input:focus-visible){outline:2px solid var(--gold);outline-offset:2px}
        .pay-icon{width:28px;height:28px;background:var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--gold-dim)}
        /* the fields that belong to the chosen method */
        .pay-panel{margin-top:18px;background:var(--surface);border:1px solid var(--border);border-radius:4px;padding:18px 18px 4px}
        .pay-panel[hidden]{display:none}
        .pay-panel .field-row input{background:var(--card)}
        .pay-two{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .change-line{display:flex;justify-content:space-between;align-items:baseline;font-size:12.5px;color:var(--muted);margin:-4px 0 16px}
        .change-line strong{font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--green-lt);font-weight:700}
        .change-line strong.short{color:var(--red-lt)}
        .pay-error{display:none;margin-top:16px;background:rgba(139,46,46,0.15);border:1px solid var(--red);border-radius:3px;padding:11px 14px;color:#e07b7b;font-size:13px;line-height:1.5}
        .pay-error.show{display:block}
        .error-banner{max-width:1100px;margin:0 auto 24px;padding:0 32px;position:relative;z-index:1}
        .error-banner div{background:rgba(139,46,46,0.15);border:1px solid var(--red);border-radius:3px;padding:12px 16px;color:#e07b7b;font-size:13px;display:flex;align-items:center;gap:10px}

        .summary-card{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:28px 26px;position:sticky;top:88px}
        .summary-title{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;color:var(--cream);padding-bottom:16px;border-bottom:1px solid var(--border);margin-bottom:20px}
        .order-item-row{display:flex;justify-content:space-between;font-size:13px;color:var(--muted);margin-bottom:4px;gap:10px}
        .order-item-row .name{flex:1}
        .order-item-row .qty{color:var(--gold-dim);min-width:30px}
        .order-item-row .price{font-weight:500;color:var(--text)}
        .order-item-addons{font-size:11px;color:var(--gold-dim);margin:0 0 10px 2px;font-style:italic}
        .divider{height:1px;background:var(--border);margin:14px 0}
        .summary-row{display:flex;justify-content:space-between;align-items:center;font-size:13.5px;color:var(--muted);margin-bottom:10px}
        .summary-row.total{font-size:15px;color:var(--cream);font-weight:500;padding-top:14px;border-top:1px solid var(--border);margin-top:6px;margin-bottom:0}
        .summary-row .val{font-family:'Cormorant Garamond',serif;font-size:18px;color:var(--text);font-weight:600}
        .summary-row.total .val{font-size:26px;color:var(--gold)}
        .place-btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:15px;margin-top:22px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:#fff;cursor:pointer;transition:background 0.2s,transform 0.15s}
        .place-btn:hover{background:var(--green-lt)}
        .place-btn:active{transform:scale(0.98)}
        .place-btn:disabled{opacity:0.6;cursor:not-allowed}
        .back-link{display:block;text-align:center;margin-top:14px;font-size:12.5px;color:var(--muted);text-decoration:none;letter-spacing:0.06em;transition:color 0.2s}
        .back-link:hover{color:var(--gold)}
        .summary-note{margin-top:20px;padding-top:16px;border-top:1px solid var(--border);font-size:11.5px;color:var(--muted);line-height:1.6;text-align:center}

        .success-wrap{position:relative;z-index:1;max-width:520px;margin:0 auto;text-align:center;padding:10px 32px 80px}
        .success-icon{width:88px;height:88px;margin:0 auto 26px;border:1px solid var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--green-lt);animation:popIn 0.5s cubic-bezier(0.34,1.56,0.64,1) both}
        @keyframes popIn{from{opacity:0;transform:scale(0.5)}to{opacity:1;transform:scale(1)}}
        .success-wrap h2{font-family:'Cormorant Garamond',serif;font-size:40px;font-weight:700;color:var(--cream);margin-bottom:12px}
        .success-wrap h2 em{font-style:italic;color:var(--gold)}
        .success-wrap p{font-size:14px;color:var(--muted);line-height:1.7;margin-bottom:8px}
        .success-wrap p strong{color:var(--gold);font-weight:500}
        .order-badge{display:inline-flex;align-items:center;gap:8px;background:var(--card);border:1px solid var(--gold-dim);border-radius:3px;padding:8px 18px;font-size:13px;color:var(--gold);margin:6px 4px 20px;letter-spacing:0.08em}
        .success-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:8px}
        .btn-primary{display:inline-flex;align-items:center;gap:8px;padding:13px 26px;background:var(--green);border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:#fff;text-decoration:none;transition:background 0.2s;border:none;cursor:pointer}
        .btn-primary:hover{background:var(--green-lt)}
        .btn-ghost{display:inline-flex;align-items:center;gap:8px;padding:13px 26px;border:1px solid var(--border);border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted);text-decoration:none;transition:all 0.2s;cursor:pointer;background:transparent}
        .btn-ghost:hover{border-color:var(--gold-dim);color:var(--gold)}

        footer{position:relative;z-index:1;border-top:1px solid var(--border);padding:28px 32px;text-align:center}
        footer p{font-size:12px;color:var(--muted);letter-spacing:0.06em}
        footer p span{color:var(--gold-dim)}

        /* brewing overlay */
        #checkoutBrewOverlay{position:fixed;inset:0;z-index:500;display:flex;align-items:center;justify-content:center;background:rgba(6,6,5,0.92);backdrop-filter:blur(12px);opacity:0;pointer-events:none;transition:opacity 0.35s ease}
        #checkoutBrewOverlay.show{opacity:1;pointer-events:all}
        .brew-stage{text-align:center;max-width:300px}
        .brew-ring-wrap{position:relative;width:140px;height:140px;margin:0 auto 30px}
        .brew-ring{position:absolute;inset:0;border-radius:50%;border:2px solid rgba(201,168,76,0.15);border-top-color:var(--gold);animation:brewspin 1.1s linear infinite}
        .brew-ring.r2{inset:14px;border-top-color:var(--green-lt);animation:brewspin 1.7s linear infinite reverse;opacity:0.6}
        @keyframes brewspin{to{transform:rotate(360deg)}}
        #brewSvg{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);animation:brewpulse 1.4s ease-in-out infinite}
        @keyframes brewpulse{0%,100%{transform:translate(-50%,-50%) scale(1)}50%{transform:translate(-50%,-50%) scale(1.12)}}
        #brewItemName{font-family:'Cormorant Garamond',serif;font-size:21px;font-weight:600;color:var(--cream)}
        #brewStepText{font-size:12.5px;color:var(--muted);margin-top:6px}
        #brewDots{display:flex;gap:6px;justify-content:center;margin-top:18px}
        .brew-dot{width:6px;height:6px;border-radius:50%;background:var(--border);transition:background 0.3s,transform 0.3s}
        .brew-dot.active{background:var(--gold);transform:scale(1.3)}

        @media(max-width:768px){
            .checkout-layout{grid-template-columns:1fr;padding:0 16px 60px}
            .summary-card{position:static}
            .header-inner{padding:0 16px}
            nav a:not(.nav-back){display:none}
            .form-card{padding:24px 20px}
            .error-banner{padding:0 16px}
            .pay-two{grid-template-columns:1fr}
        }
        @media (prefers-reduced-motion: reduce){*{animation-duration:.01ms !important;transition-duration:.01ms !important}}
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
            <a href="reservation-menu.php">Menu</a>
            <a href="reservation-orders.php">Orders</a>
            <?php if ($isLoggedIn): ?>
                <a href="log-out.php">Logout</a>
            <?php endif; ?>
            <a href="my-reservations.php" class="nav-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                My Reservations
            </a>
        </nav>
    </div>
</header>

<?php if ($success): ?>
<!-- ══ SUCCESS: paid ══ -->
<div class="page-hero">
    <div class="hero-eyebrow">Payment received</div>
    <h1>Reservation <em>Paid</em></h1>
</div>
<div class="success-wrap">
    <div class="success-icon">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <h2>Reserved &amp; <em>Paid!</em></h2>
    <p>Thank you, <strong><?= htmlspecialchars($res['guest_name']) ?></strong>. Your payment is confirmed. Your order is waiting at the counter of the <strong><?= ucfirst(htmlspecialchars($branch)) ?> branch</strong> for
       <strong><?= htmlspecialchars($whenLabel) ?></strong>.</p>
    <p>Show your receipt at the counter to pick it up.</p>
    <div>
        <span class="order-badge">Reservation <?= htmlspecialchars($receipt['code']) ?></span>
        <?php if ($receipt['order_code'] !== ''): ?><span class="order-badge">Order <?= htmlspecialchars($receipt['order_code']) ?></span><?php endif; ?>
    </div>
    <div class="success-actions">
        <button type="button" class="btn-primary" onclick="openReservationReceipt(RES_RECEIPT, {fresh:true, title:'Payment received'})">View Receipt</button>
        <a href="reservation-orders.php#order-<?= (int)$resId ?>" class="btn-ghost">My Reservation Orders</a>
        <a href="my-reservations.php" class="btn-ghost">My Reservations</a>
    </div>
</div>
<script>
const RES_RECEIPT = <?= resJson($receipt) ?>;
document.addEventListener('DOMContentLoaded', function(){ openReservationReceipt(RES_RECEIPT, {fresh:true, title:'Payment received'}); });
</script>

<?php else: ?>
<!-- ══ PAYMENT FORM ══ -->
<div class="page-hero">
    <div class="hero-eyebrow">Pick up now</div>
    <h1>Pay for your <em>Reservation</em></h1>
</div>

<?php if ($error): ?>
<div class="error-banner"><div>
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <?= htmlspecialchars($error) ?>
</div></div>
<?php endif; ?>

<form method="POST" action="reservation-checkout.php" id="checkoutForm" autocomplete="off" novalidate>
<input type="hidden" name="reservation" value="<?= (int)$resId ?>">
<input type="hidden" name="place_order" value="1">
<div class="checkout-layout">
    <div class="form-card">
        <div class="section-label">Reservation</div>
        <div class="res-info">
            <div><span class="k">Reservation code</span><span class="v"><?= htmlspecialchars($res['reservation_code'] ?: ('RSV-' . str_pad((string)$resId, 5, '0', STR_PAD_LEFT))) ?></span></div>
            <div><span class="k">Branch</span><span class="v"><?= ucfirst(htmlspecialchars($branch)) ?></span></div>
            <div><span class="k">Name</span><span class="v"><?= htmlspecialchars($res['guest_name']) ?></span></div>
            <div><span class="k">Party size</span><span class="v"><?= (int)$res['party_size'] ?></span></div>
            <div class="full"><span class="k">Pick-up date &amp; time</span><span class="v"><?= htmlspecialchars($whenLabel) ?></span></div>
        </div>

        <div class="section-label">Payment Method</div>
        <div class="pay-methods">
            <?php foreach ($methods as $key => $label): ?>
            <label class="pay-method">
                <input type="radio" name="payment" value="<?= htmlspecialchars($key) ?>" <?= $payKey === $key ? 'checked' : '' ?> onchange="showPanel()">
                <div class="pay-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $payIcons[$key] ?? '' ?></svg></div>
                <?= htmlspecialchars($label) ?>
            </label>
            <?php endforeach; ?>
        </div>

        <!-- Cash on Hand -->
        <div class="pay-panel" id="panelCash" <?= $payKey === 'cash' ? '' : 'hidden' ?>>
            <div class="field-row">
                <label for="cashAmount">Cash amount (₱)</label>
                <input type="text" id="cashAmount" name="cash_amount" inputmode="decimal" value="<?= htmlspecialchars((string)$cashValue) ?>" oninput="updateChange()">
                <p class="hint">Enter the amount you are handing over at the counter. It must cover the total.</p>
            </div>
            <div class="change-line"><span>Change</span><strong id="changeAmt">₱0.00</strong></div>
        </div>

        <!-- GCash / Maya -->
        <div class="pay-panel" id="panelRef" <?= ($payKey === 'gcash' || $payKey === 'maya') ? '' : 'hidden' ?>>
            <div class="field-row">
                <label for="payRef" id="refLabel"><?= $payKey === 'maya' ? 'Maya' : 'GCash' ?> Ref. No.</label>
                <input type="text" id="payRef" name="pay_ref" maxlength="26" value="<?= htmlspecialchars((string)$refValue) ?>" placeholder="e.g. 1234 567 890123" style="text-transform:uppercase">
                <p class="hint">Send the exact total of ₱<?= number_format($total, 2) ?> in your app, then type the reference number from the receipt.</p>
            </div>
        </div>

        <!-- Credit / Debit card -->
        <div class="pay-panel" id="panelCard" <?= $payKey === 'card' ? '' : 'hidden' ?>>
            <div class="field-row">
                <label for="cardNumber">Card number</label>
                <input type="text" id="cardNumber" name="card_number" inputmode="numeric" maxlength="23" placeholder="0000 0000 0000 0000" autocomplete="off">
            </div>
            <div class="pay-two">
                <div class="field-row">
                    <label for="cardPin">Card PIN</label>
                    <input type="password" id="cardPin" name="card_pin" inputmode="numeric" maxlength="6" placeholder="4 or 6 digits" autocomplete="new-password">
                </div>
            </div>
            <p class="hint" style="margin:-4px 0 16px">For your safety, only the last 4 digits of the card are saved. The PIN and full card number are never stored.</p>
        </div>

        <div class="pay-error" id="payError" role="alert"></div>

        <div class="field-row" style="margin-top:24px">
            <label for="payNotes">Order Notes <span style="color:var(--muted);text-transform:none;letter-spacing:0">(optional)</span></label>
            <textarea id="payNotes" name="notes" maxlength="400" placeholder="Any special requests? E.g. less ice, extra sugar..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="summary-card">
        <div class="summary-title">Order Summary</div>
        <?php foreach ($res['items'] as $it): ?>
        <div class="order-item-row">
            <span class="name"><?= htmlspecialchars($it['product_name']) ?></span>
            <span class="qty">×<?= (int)$it['quantity'] ?></span>
            <span class="price">₱<?= number_format($it['line_total'], 2) ?></span>
        </div>
        <?php if (!empty($it['addon_list'])): ?>
        <div class="order-item-addons">+ <?= htmlspecialchars(implode(', ', array_column($it['addon_list'], 'name'))) ?></div>
        <?php endif; ?>
        <?php endforeach; ?>

        <div class="divider"></div>
        <div class="summary-row"><span>Subtotal</span><span class="val">₱<?= number_format($total, 2) ?></span></div>
        <div class="summary-row total"><span>Total</span><span class="val">₱<?= number_format($total, 2) ?></span></div>

        <button type="submit" class="place-btn" id="placeOrderBtn" onclick="return startBrewThenSubmit(event)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Pay ₱<?= number_format($total, 2) ?>
        </button>
        <a href="my-reservations.php" class="back-link">← Pick up later instead</a>
        <p class="summary-note">Your order stays at the counter until you pick it up. Nothing is charged until you confirm.</p>
    </div>
</div>
</form>

<!-- Brewing overlay -->
<div id="checkoutBrewOverlay">
    <div class="brew-stage">
        <div class="brew-ring-wrap">
            <div class="brew-ring"></div>
            <div class="brew-ring r2"></div>
            <svg id="brewSvg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.6"></svg>
        </div>
        <div id="brewItemName">Confirming your payment…</div>
        <div id="brewStepText"></div>
        <div id="brewDots"></div>
    </div>
</div>

<script src="brew-icons.js"></script>
<script>
const CHECKOUT_ITEMS = <?= resJson($brewQueue) ?>;
const RES_TOTAL = <?= json_encode($total) ?>;
let brewSubmitting = false;

function $(id){ return document.getElementById(id); }
function fmtPeso(v){ return '₱' + Number(v).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function currentMethod(){ var r=document.querySelector('input[name="payment"]:checked'); return r ? r.value : 'cash'; }

// ── show only the fields of the chosen payment method ──
function showPanel(){
    var m = currentMethod();
    $('panelCash').hidden = (m !== 'cash');
    $('panelRef').hidden  = !(m === 'gcash' || m === 'maya');
    $('panelCard').hidden = (m !== 'card');
    $('refLabel').textContent = (m === 'maya' ? 'Maya' : 'GCash') + ' Ref. No.';
    $('payError').classList.remove('show');
    if (m === 'cash') updateChange();
}

// ── cash: live change ──
function updateChange(){
    var v = parseFloat(String($('cashAmount').value).replace(/[^0-9.]/g,''));
    var el = $('changeAmt');
    if (isNaN(v)) { el.textContent = fmtPeso(0); el.classList.remove('short'); return; }
    var diff = v - RES_TOTAL;
    if (diff >= -0.001) { el.textContent = fmtPeso(Math.max(0, diff)); el.classList.remove('short'); }
    else { el.textContent = 'Short by ' + fmtPeso(-diff); el.classList.add('short'); }
}

// ── input helpers ──
$('cardNumber').addEventListener('input', function(){
    var d = this.value.replace(/\D/g,'').slice(0,19);
    this.value = d.replace(/(.{4})/g,'$1 ').trim();
});
$('cardPin').addEventListener('input', function(){ this.value = this.value.replace(/\D/g,'').slice(0,6); });
$('payRef').addEventListener('input', function(){ this.value = this.value.toUpperCase().replace(/[^A-Z0-9 ]/g,''); });

// ── same rules as the server, so the customer gets the message before the animation ──
function validatePayment(){
    var m = currentMethod();
    if (m === 'cash') {
        var v = parseFloat(String($('cashAmount').value).replace(/[^0-9.]/g,''));
        if (isNaN(v)) return 'Enter the cash amount you are handing over.';
        if (v + 0.001 < RES_TOTAL) return 'The cash amount is less than the total (' + fmtPeso(RES_TOTAL) + ').';
    } else if (m === 'gcash' || m === 'maya') {
        var ref = $('payRef').value.replace(/\s+/g,'');
        if (!/^[A-Za-z0-9]{8,20}$/.test(ref)) return 'Enter a valid ' + (m === 'maya' ? 'Maya' : 'GCash') + ' reference number (8 to 20 letters or numbers).';
    } else if (m === 'card') {
        var num = $('cardNumber').value.replace(/\D/g,'');
        if (num.length < 13 || num.length > 19) return 'Enter a valid card number (13 to 19 digits).';
        if (!/^(\d{4}|\d{6})$/.test($('cardPin').value)) return 'Enter your 4 or 6 digit card PIN.';
    }
    return '';
}

// Plays the short "preparing" animation, then submits the real form.
// place_order is a hidden field, so a programmatic submit() still sends it.
function startBrewThenSubmit(evt){
    if (brewSubmitting) return true;

    var msg = validatePayment();
    var box = $('payError');
    if (msg) {
        evt.preventDefault();
        box.textContent = msg;
        box.classList.add('show');
        box.scrollIntoView({behavior:'smooth', block:'center'});
        return false;
    }
    box.classList.remove('show');

    if (!CHECKOUT_ITEMS.length || typeof BREW_ICON_PATHS === 'undefined') return true;
    evt.preventDefault();
    brewSubmitting = true;

    const form = $('checkoutForm');
    $('placeOrderBtn').disabled = true;
    $('checkoutBrewOverlay').classList.add('show');

    const svg = $('brewSvg'), nameEl = $('brewItemName'), stepEl = $('brewStepText');
    const dotsWrap = $('brewDots');
    dotsWrap.innerHTML = CHECKOUT_ITEMS.map(function(){ return '<span class="brew-dot"></span>'; }).join('');
    const dots = [].slice.call(dotsWrap.children);

    function renderStep(idx){
        const item = CHECKOUT_ITEMS[idx], icon = iconFor(item.category);
        svg.innerHTML = BREW_ICON_PATHS[icon] || '';
        nameEl.textContent = item.name;
        stepEl.textContent = BREW_LABELS[icon] || 'Preparing…';
        dots.forEach(function(d, di){ d.classList.toggle('active', di <= idx); });
    }
    renderStep(0);

    const perItemMs = Math.max(700, Math.min(1200, 3200 / CHECKOUT_ITEMS.length));
    let i = 0;
    const iv = setInterval(function(){ i++; if (i >= CHECKOUT_ITEMS.length) { clearInterval(iv); return; } renderStep(i); }, perItemMs);
    setTimeout(function(){ clearInterval(iv); form.submit(); }, perItemMs * CHECKOUT_ITEMS.length + 500);
    return false;
}

showPanel();
</script>
<?php endif; ?>

<footer><p>© 2026 <span>My AyosCoffeeNegosyo</span> — All rights reserved.</p></footer>

<?php if ($success): include 'reservation-receipt.inc.php'; endif; ?>
</body>
</html>