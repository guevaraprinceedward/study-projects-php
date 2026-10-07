<?php
/**
 * reservation-cart.php (v3) — line-based reservation cart.
 * Each line = product + size + options (hot/iced, sugar...) + add-ons + note, chosen in the
 * customize modal. The reservation is marked Dine-in / Take-out / Pick-up.
 * Flow: cart -> "Reserve" (details modal) -> saved -> redirect ?reserved=ID -> receipt modal
 *       -> Pick up now (reservation-checkout.php) / Pick up later (my-reservations.php)
 */
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';
include_once 'customize-lib.php';
include_once 'reservation-init.php';

// columns added by v3 (run once per session)
if (empty($_SESSION['res_schema_v3'])) {
    $ok = true;
    foreach ([
        "ALTER TABLE reservation_items ADD COLUMN IF NOT EXISTS spec VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE reservation_items ADD COLUMN IF NOT EXISTS item_note VARCHAR(120) DEFAULT NULL",
        "ALTER TABLE reservations ADD COLUMN IF NOT EXISTS service_type VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE order_items ADD COLUMN IF NOT EXISTS spec VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE order_items ADD COLUMN IF NOT EXISTS item_note VARCHAR(120) DEFAULT NULL",
    ] as $sql) { try { if (!$conn->query($sql)) $ok = false; } catch (Throwable $e) { $ok = false; } }
    if ($ok) $_SESSION['res_schema_v3'] = 1;
}

$uid         = resValidUserId($conn);
$isLoggedIn  = $uid > 0;
$defaultName = $isLoggedIn ? trim((string)($_SESSION['user']['username'] ?? '')) : '';
$svcTypes    = custOrderTypes(); unset($svcTypes['delivery']);   // reservations are picked up at the branch

if (!isset($_SESSION['res_cart']) || !is_array($_SESSION['res_cart'])) $_SESSION['res_cart'] = [];
custMigrateCart($_SESSION['res_cart']);

$error = ''; $success = false; $receipt = null; $resData = null;

// ── JUST RESERVED? ────────────────────────────────────────────────────────
if (isset($_GET['reserved'])) {
    $justRes = resLoadReservation($conn, (int)$_GET['reserved']);
    if ($justRes && $justRes['status'] === 'reserved' && resCanAccess($justRes, $uid)) {
        $resData = $justRes; $receipt = resReceiptData($justRes); $success = true;
    } else { header("Location: reservation-cart.php"); exit(); }
}

// ── REMOVE ────────────────────────────────────────────────────────────────
if (isset($_GET['remove'])) {
    unset($_SESSION['res_cart'][preg_replace('/[^A-Za-z0-9]/', '', (string)$_GET['remove'])]);
    header("Location: reservation-cart.php"); exit();
}

// ── QTY UPDATE ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    custApplyQtyUpdate($conn, $_SESSION['res_cart'], is_array($_POST['qty'] ?? null) ? $_POST['qty'] : []);
    header("Location: reservation-cart.php"); exit();
}

// ── BUILD CART ────────────────────────────────────────────────────────────
[$cartRows, $total] = custBuildCart($conn, $_SESSION['res_cart']);
$itemCount     = custCartCount($_SESSION['res_cart']);
$cartBranches  = array_values(array_unique(array_map(fn($r) => (string)$r['branch'], $cartRows)));
$mixedBranches = count($cartBranches) > 1;
$cartBranch    = $cartBranches[0] ?? ($_SESSION['branch'] ?? 'laguna');
$selSvc        = (string)($_SESSION['res_svc_type'] ?? 'pick-up');
if (!isset($svcTypes[$selSvc])) $selSvc = 'pick-up';

// ── HANDLE RESERVE ────────────────────────────────────────────────────────
$old = ['name' => $defaultName, 'phone' => '', 'date' => '', 'time' => '', 'party' => 2, 'notes' => '', 'svc' => $selSvc];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reservation'])) {
    $old = [
        'name'  => trim((string)($_POST['guest_name'] ?? '')),
        'phone' => trim((string)($_POST['guest_phone'] ?? '')),
        'date'  => trim((string)($_POST['res_date'] ?? '')),
        'time'  => trim((string)($_POST['res_time'] ?? '')),
        'party' => (int)($_POST['party_size'] ?? 0),
        'notes' => mb_substr(trim((string)($_POST['notes'] ?? '')), 0, 500),
        'svc'   => (string)($_POST['service_type'] ?? ''),
    ];
    if (preg_match('/^(\d{2}:\d{2})(:\d{2})?$/', $old['time'], $m)) $old['time'] = $m[1];

    do {
        if (empty($cartRows))  { $error = 'Your reservation cart is empty. Add some items first.'; break; }
        if ($mixedBranches)    { $error = 'Your cart has items from more than one branch. Please keep one branch per reservation.'; break; }
        if (!isset($svcTypes[$old['svc']])) { $error = 'Please choose Dine-in, Take-out or Pick-up.'; break; }
        if ($old['name'] === '' || mb_strlen($old['name']) > 100) { $error = 'Please enter the name for the reservation (max 100 characters).'; break; }
        if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $old['phone']))   { $error = 'Please enter a valid phone number so the branch can reach you.'; break; }
        if ($old['party'] < 1 || $old['party'] > RES_MAX_PARTY)      { $error = 'Party size must be between 1 and ' . RES_MAX_PARTY . '.'; break; }

        $when = DateTime::createFromFormat('Y-m-d H:i', $old['date'] . ' ' . $old['time']);
        if (!$when || $when->format('Y-m-d H:i') !== $old['date'] . ' ' . $old['time']) { $error = 'Please choose a valid date and time.'; break; }
        if ($old['time'] < RES_OPEN || $old['time'] > RES_CLOSE) {
            $error = 'Reservations are accepted between ' . date('g:i A', strtotime(RES_OPEN)) . ' and ' . date('g:i A', strtotime(RES_CLOSE)) . '.'; break;
        }
        if ($when->getTimestamp() < time() + RES_MIN_LEAD_MIN * 60) { $error = 'Please choose a time at least ' . RES_MIN_LEAD_MIN . ' minutes from now.'; break; }
        if ($when->getTimestamp() > strtotime('+' . RES_MAX_DAYS_AHEAD . ' days')) { $error = 'Reservations can be made up to ' . RES_MAX_DAYS_AHEAD . ' days ahead.'; break; }

        $timeSql = $old['time'] . ':00';
        $conn->begin_transaction();
        try {
            // lock + re-check stock per PRODUCT (several lines can share one product)
            $need = [];
            foreach ($cartRows as $r) { $need[(int)$r['id']] = ($need[(int)$r['id']] ?? 0) + (int)$r['qty']; }
            ksort($need);
            $lock = $conn->prepare("SELECT name, stock FROM products WHERE id = ? FOR UPDATE");
            foreach ($need as $pid => $qty) {
                $lock->bind_param('i', $pid); $lock->execute();
                $p = $lock->get_result()->fetch_assoc();
                if (!$p || (int)$p['stock'] < $qty) throw new Exception('Sorry, "' . ($p['name'] ?? 'an item') . '" no longer has enough stock. Please update your reservation cart.');
            }
            $lock->close();

            $userIdParam = $isLoggedIn ? $uid : null;
            $st = $conn->prepare("INSERT INTO reservations
                (user_id, guest_name, guest_phone, branch, reservation_date, reservation_time, party_size, notes, total, status, payment_status, service_type)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'reserved', 'unpaid', ?)");
            $st->bind_param('isssssisds', $userIdParam, $old['name'], $old['phone'], $cartBranch, $old['date'], $timeSql, $old['party'], $old['notes'], $total, $old['svc']);
            if (!$st->execute()) throw new Exception('Could not create the reservation. Please try again.');
            $resId = (int)$conn->insert_id;
            $st->close();
            if (!$resId) throw new Exception('Could not create the reservation. Please try again.');

            $code = 'AYS-RSV-' . date('Ymd') . '-' . str_pad((string)$resId, 5, '0', STR_PAD_LEFT);
            $st = $conn->prepare("UPDATE reservations SET reservation_code = ? WHERE id = ?");
            $st->bind_param('si', $code, $resId); $st->execute(); $st->close();

            $insItem = $conn->prepare("INSERT INTO reservation_items (reservation_id, product_id, product_name, quantity, price, addons, addons_total, spec, item_note)
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($cartRows as $r) {
                $pid = (int)$r['id']; $qty = (int)$r['qty']; $name = (string)$r['name']; $price = (float)$r['price'];
                $addons = json_encode($r['addon_names'], JSON_UNESCAPED_UNICODE);
                $addTot = (float)$r['addon_unit_total']; $spec = (string)$r['spec']; $inote = (string)$r['note'];
                $insItem->bind_param('iisidsdss', $resId, $pid, $name, $qty, $price, $addons, $addTot, $spec, $inote);
                if (!$insItem->execute()) throw new Exception('Could not save the reservation items. Please try again.');
            }
            $insItem->close();

            // reserved = held at the counter: the stock leaves the shelf now
            $hold = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
            foreach ($need as $pid => $qty) {
                $hold->bind_param('iii', $qty, $pid, $qty); $hold->execute();
                if ($hold->affected_rows < 1) throw new Exception('Stock changed while you were reserving. Please review your cart.');
            }
            $hold->close();

            $conn->commit();

            unset($_SESSION['res_cart']);
            $_SESSION['res_svc_type'] = $old['svc'];
            if (!$isLoggedIn) {
                if (!isset($_SESSION['guest_reservations']) || !is_array($_SESSION['guest_reservations'])) $_SESSION['guest_reservations'] = [];
                $_SESSION['guest_reservations'][] = $resId;
            }
            header("Location: reservation-cart.php?reserved=" . $resId);   // refresh never reserves twice
            exit();
        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    } while (false);
}

$minDate = date('Y-m-d');
$maxDate = date('Y-m-d', strtotime('+' . RES_MAX_DAYS_AHEAD . ' days'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reservation Cart — SIPPERÉ Café</title>
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
nav a{font-size:12.5px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);text-decoration:none;padding:8px 14px;border-radius:3px}
nav a:hover{color:var(--cream);background:rgba(255,255,255,.04)}
.nav-back{border:1px solid var(--border)}
.page-hero{position:relative;z-index:1;text-align:center;padding:60px 32px 44px}
.hero-eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:16px}
.hero-eyebrow::before,.hero-eyebrow::after{content:'';width:28px;height:1px;background:var(--gold-dim)}
.page-hero h1{font-family:'Cormorant Garamond',serif;font-size:clamp(36px,5vw,56px);font-weight:700;color:var(--cream)}
.page-hero h1 em{font-style:italic;color:var(--gold)}
.item-count{margin-top:10px;font-size:13px;color:var(--muted)}
.alert{position:relative;z-index:1;max-width:1100px;margin:0 auto 22px;padding:0 32px}
.alert div{border-radius:3px;padding:12px 16px;font-size:13px;line-height:1.55}
.alert .error{background:rgba(139,46,46,.15);border:1px solid var(--red);color:#e07b7b}
.alert .warn{background:rgba(212,130,10,.1);border:1px solid var(--amber);color:#e0a552}
.cart-layout{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:0 32px 80px;display:grid;grid-template-columns:1fr 340px;gap:28px;align-items:start}
.cart-items{display:flex;flex-direction:column;gap:14px}
.cart-item{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:20px 22px;display:flex;align-items:flex-start;gap:18px;flex-wrap:wrap}
.cart-item:hover{border-color:var(--gold-dim)}
.item-icon{width:48px;height:48px;flex-shrink:0;background:var(--surface);border:1px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--gold-dim)}
.item-info{flex:1;min-width:200px}
.item-name{font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:600;color:var(--cream);margin-bottom:4px}
.item-pkg{display:inline-block;margin-left:8px;vertical-align:middle;background:var(--gold);color:#1a1400;font-family:'Jost',sans-serif;font-size:9.5px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;padding:2px 8px;border-radius:3px}
.item-spec{font-size:12.5px;color:var(--text);margin-bottom:2px}
.item-addons,.item-note{font-size:11.5px;color:var(--gold-dim);font-style:italic;margin-bottom:2px}
.item-unit-price{font-size:12px;color:var(--muted);margin-top:2px}
.item-stock-note{font-size:11px;margin-top:3px}
.item-stock-note.ok{color:var(--green-lt)}.item-stock-note.low{color:var(--amber)}.item-stock-note.limit{color:var(--red-lt)}
.qty-wrap{display:flex;align-items:center;border:1px solid var(--border);border-radius:3px;overflow:hidden;flex-shrink:0}
.qty-btn{width:36px;height:40px;background:var(--surface);border:none;color:var(--muted);font-size:20px;cursor:pointer;line-height:1}
.qty-btn:hover:not(:disabled){background:var(--border);color:var(--cream)}.qty-btn:disabled{opacity:.3;cursor:not-allowed}
.qty-input{width:60px;height:40px;background:var(--card);border:none;border-left:1px solid var(--border);border-right:1px solid var(--border);color:var(--cream);font-family:'Jost',sans-serif;font-size:15px;font-weight:600;text-align:center;outline:none}
.item-subtotal{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:var(--gold);min-width:90px;text-align:right;flex-shrink:0}
.item-subtotal small{font-family:'Jost',sans-serif;font-size:12px;color:var(--muted);font-weight:400}
.remove-btn{width:34px;height:34px;flex-shrink:0;border:1px solid var(--border);border-radius:3px;color:var(--muted);display:flex;align-items:center;justify-content:center;text-decoration:none}
.remove-btn:hover{background:var(--red);border-color:var(--red);color:#fff}
.summary-card{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:26px 24px;position:sticky;top:88px}
.summary-title{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;color:var(--cream);padding-bottom:14px;border-bottom:1px solid var(--border);margin-bottom:18px}
.summary-row{display:flex;justify-content:space-between;align-items:center;font-size:13.5px;color:var(--muted);margin-bottom:12px}
.summary-row.total{font-size:15px;color:var(--cream);font-weight:500;padding-top:14px;border-top:1px solid var(--border);margin-top:8px;margin-bottom:0}
.summary-row .val{font-family:'Cormorant Garamond',serif;font-size:18px;color:var(--text);font-weight:600}
.summary-row.total .val{font-size:26px;color:var(--gold)}
.checkout-btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:14px;margin-top:22px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;color:#fff;cursor:pointer}
.checkout-btn:hover:not(:disabled){background:var(--green-lt)}.checkout-btn:disabled{opacity:.45;cursor:not-allowed}
.continue-link{display:block;text-align:center;margin-top:14px;font-size:12.5px;color:var(--muted);text-decoration:none}.continue-link:hover{color:var(--gold)}
.summary-note{margin-top:18px;padding-top:14px;border-top:1px solid var(--border);font-size:11.5px;color:var(--muted);line-height:1.6;text-align:center}
.guest-note{background:rgba(201,168,76,.06);border:1px solid var(--gold-dim);border-radius:3px;padding:10px 14px;font-size:12px;color:var(--muted);margin-top:14px;line-height:1.5;text-align:center}
.guest-note a{color:var(--gold);text-decoration:none}
.update-bar{position:fixed;bottom:0;left:0;right:0;z-index:200;background:var(--surface);border-top:1px solid var(--border);padding:14px 32px;display:flex;align-items:center;justify-content:space-between;transform:translateY(100%);transition:transform .3s ease}
.update-bar.visible{transform:none}
.update-bar-msg{font-size:13px;color:var(--muted)}.update-bar-msg span{color:var(--gold);font-weight:500}
.update-bar-btns{display:flex;gap:10px}
.update-btn{padding:8px 20px;border-radius:3px;font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:.08em;text-transform:uppercase;cursor:pointer}
.update-btn.save{background:var(--green);border:none;color:#fff}.update-btn.discard{background:transparent;border:1px solid var(--border);color:var(--muted)}
.empty-state{position:relative;z-index:1;text-align:center;padding:80px 20px;max-width:420px;margin:0 auto}
.empty-icon{width:80px;height:80px;margin:0 auto 24px;border:1px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--muted);opacity:.5}
.empty-state h3{font-family:'Cormorant Garamond',serif;font-size:28px;color:var(--cream);margin-bottom:10px}
.empty-state p{font-size:14px;color:var(--muted);line-height:1.7;margin-bottom:28px}
.browse-btn{display:inline-flex;padding:12px 28px;background:var(--green);border-radius:3px;font-size:13px;font-weight:500;letter-spacing:.08em;text-transform:uppercase;color:#fff;text-decoration:none}
footer{position:relative;z-index:1;border-top:1px solid var(--border);padding:28px 32px;text-align:center}
footer p{font-size:12px;color:var(--muted)}footer p span{color:var(--gold-dim)}
#reserveModal{position:fixed;inset:0;z-index:400;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.78);backdrop-filter:blur(6px);opacity:0;pointer-events:none;transition:opacity .3s;padding:16px}
#reserveModal.show{opacity:1;pointer-events:all}
.rv-card{position:relative;background:var(--card);border:1px solid var(--border);border-radius:6px;padding:30px 28px 26px;max-width:440px;width:100%;max-height:92vh;overflow-y:auto}
.rv-close{position:absolute;top:12px;right:12px;width:28px;height:28px;border:1px solid var(--border);border-radius:50%;background:transparent;color:var(--muted);cursor:pointer}
.rv-card h3{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--cream);margin-bottom:6px}
.rv-card h3 em{font-style:italic;color:var(--gold)}
.rv-lead{font-size:13px;color:var(--muted);line-height:1.6;margin-bottom:18px}
.rv-field{margin-bottom:14px}
.rv-field label{display:block;font-size:11px;font-weight:500;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-bottom:7px}
.rv-field input,.rv-field textarea,.rv-field select{width:100%;background:var(--surface);border:1px solid var(--border);border-radius:3px;color:var(--cream);font-family:'Jost',sans-serif;font-size:14px;padding:10px 13px;outline:none;color-scheme:dark}
.rv-field input:focus,.rv-field textarea:focus,.rv-field select:focus{border-color:var(--gold-dim)}
.rv-field textarea{resize:vertical;min-height:60px}
.rv-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.rv-hint{font-size:11px;color:var(--muted);margin:-6px 0 14px}
.rv-primary{display:flex;align-items:center;justify-content:center;width:100%;padding:13px;margin-top:6px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;color:#fff;cursor:pointer}
.rv-primary:hover:not(:disabled){background:var(--green-lt)}.rv-primary:disabled{opacity:.6;cursor:not-allowed}
.rv-after{font-size:11.5px;color:var(--muted);line-height:1.6;text-align:center;margin-top:12px}
#rvToast{position:fixed;bottom:84px;left:50%;transform:translate(-50%,16px);z-index:300;background:var(--card);border:1px solid var(--gold-dim);border-radius:4px;padding:11px 18px;font-size:13px;color:var(--cream);opacity:0;pointer-events:none;transition:all .3s;max-width:calc(100vw - 32px)}
#rvToast.show{opacity:1;transform:translate(-50%,0)}
.success-wrap{position:relative;z-index:1;max-width:520px;margin:0 auto;text-align:center;padding:10px 32px 80px}
.success-icon{width:88px;height:88px;margin:0 auto 26px;border:1px solid var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--green-lt)}
.success-wrap h2{font-family:'Cormorant Garamond',serif;font-size:40px;font-weight:700;color:var(--cream);margin-bottom:12px}
.success-wrap h2 em{font-style:italic;color:var(--gold)}
.success-wrap p{font-size:14px;color:var(--muted);line-height:1.7;margin-bottom:8px}.success-wrap p strong{color:var(--gold);font-weight:500}
.order-badge{display:inline-flex;align-items:center;gap:8px;background:var(--card);border:1px solid var(--gold-dim);border-radius:3px;padding:8px 18px;font-size:13px;color:var(--gold);margin:16px 0 24px;letter-spacing:.08em}
.success-items{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:10px 16px;margin:0 auto 26px;max-width:420px;text-align:left}
.success-item-row{display:flex;align-items:center;gap:12px;font-size:13px;color:var(--muted);padding:10px 4px;border-bottom:1px solid rgba(44,44,36,.4)}
.success-item-row:last-child{border-bottom:none}
.success-item-row .sname{flex:1;color:var(--cream);font-size:13.5px}
.success-item-row .saddons{color:var(--gold-dim);font-size:11px;font-style:italic;margin-top:2px}
.success-item-row .sq{color:var(--gold-dim);font-size:12px}.success-item-row .sp{color:var(--text);min-width:70px;text-align:right}
.success-item-row.grand .sname{font-weight:600}.success-item-row.grand .sp{color:var(--gold);font-family:'Cormorant Garamond',serif;font-size:19px}
.success-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
.btn-primary,.btn-ghost{display:inline-flex;align-items:center;padding:13px 26px;border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:.08em;text-transform:uppercase;text-decoration:none;cursor:pointer}
.btn-primary{background:var(--green);color:#fff;border:none}.btn-primary:hover{background:var(--green-lt)}
.btn-ghost{border:1px solid var(--border);color:var(--muted);background:transparent}.btn-ghost:hover{border-color:var(--gold-dim);color:var(--gold)}
@media(max-width:768px){.cart-layout{grid-template-columns:1fr;padding:0 16px 80px}.summary-card{position:static}.header-inner{padding:0 16px}nav a:not(.nav-back){display:none}.alert{padding:0 16px}.update-bar{padding:14px 16px}.rv-row{grid-template-columns:1fr}}
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
            <a href="reservation-menu.php">Menu</a><a href="my-reservations.php">Reservations</a><a href="reservation-orders.php">Orders</a>
            <?php if ($isLoggedIn): ?><a href="log-out.php">Logout</a><?php else: ?><a href="log-in.php">Login</a><a href="register.php">Sign Up</a><?php endif; ?>
            <a href="reservation-menu.php" class="nav-back">← Back to Menu</a>
        </nav>
    </div>
</header>

<?php if ($success): ?>
<div class="page-hero"><div class="hero-eyebrow">Reservation confirmed</div><h1>You're <em>Reserved</em></h1></div>
<div class="success-wrap">
    <div class="success-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
    <h2>Order <em>Reserved!</em></h2>
    <p>Thank you, <strong><?= htmlspecialchars($resData['guest_name']) ?></strong>. Your <?= htmlspecialchars(strtolower($svcTypes[$resData['service_type'] ?? ''] ?? 'order')) ?> is reserved at the <strong><?= ucfirst(htmlspecialchars($resData['branch'])) ?> branch</strong> for
       <strong><?= htmlspecialchars(resFormatDate($resData['reservation_date'])) ?> at <?= htmlspecialchars(resFormatTime($resData['reservation_time'])) ?></strong>.</p>
    <p>It stays at the counter until you pick it up. Pick up and pay now, or pay later when you arrive.</p>
    <div class="order-badge"><?= htmlspecialchars($receipt['code']) ?></div>
    <div class="success-items">
        <?php foreach ($resData['items'] as $it): ?>
        <div class="success-item-row">
            <div class="sname"><?= htmlspecialchars($it['product_name']) ?>
                <?php $sub = trim(trim((string)($it['spec'] ?? '')) . (!empty($it['addon_list']) ? ' · + ' . implode(', ', array_column($it['addon_list'], 'name')) : ''), ' ·');
                      if ($sub !== ''): ?><div class="saddons"><?= htmlspecialchars($sub) ?></div><?php endif; ?>
                <?php if (!empty($it['item_note'])): ?><div class="saddons">“<?= htmlspecialchars($it['item_note']) ?>”</div><?php endif; ?>
            </div>
            <div class="sq">×<?= (int)$it['quantity'] ?></div>
            <div class="sp">₱<?= number_format($it['line_total'], 2) ?></div>
        </div>
        <?php endforeach; ?>
        <div class="success-item-row grand"><div class="sname">Total</div><div class="sp">₱<?= number_format((float)$resData['total'], 2) ?></div></div>
    </div>
    <?php if (!$isLoggedIn): ?><p style="margin-bottom:22px"><a href="register.php" style="color:var(--gold);text-decoration:none">Create an account</a> to keep all your reservations in one place. Please keep your reservation code.</p><?php endif; ?>
    <div class="success-actions">
        <button type="button" class="btn-primary" onclick="openReservationReceipt(RES_RECEIPT,{fresh:true})">View Receipt</button>
        <a href="my-reservations.php" class="btn-ghost">My Reservations</a>
        <a href="reservation-menu.php" class="btn-ghost">Browse Menu</a>
    </div>
</div>
<script>
const RES_RECEIPT = <?= resJson($receipt) ?>;
document.addEventListener('DOMContentLoaded', function(){ openReservationReceipt(RES_RECEIPT, {fresh:true}); });
</script>

<?php elseif (empty($cartRows)): ?>
<div class="page-hero"><div class="hero-eyebrow">Review your reservation</div><h1>Reservation <em>Cart</em></h1></div>
<?php if ($error): ?><div class="alert"><div class="error"><?= htmlspecialchars($error) ?></div></div><?php endif; ?>
<div class="empty-state">
    <div class="empty-icon"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg></div>
    <h3>Nothing reserved yet</h3>
    <p>Pick your favorites or a Packages Combo from the reservation menu.</p>
    <a href="reservation-menu.php" class="browse-btn">Browse Reservation Menu</a>
</div>

<?php else: ?>
<div class="page-hero">
    <div class="hero-eyebrow">Review your reservation</div>
    <h1>Reservation <em>Cart</em></h1>
    <p class="item-count" id="heroItemCount"><?= $itemCount ?> item<?= $itemCount !== 1 ? 's' : '' ?> · <?= ucfirst(htmlspecialchars($cartBranch)) ?> branch</p>
</div>
<?php if ($error): ?><div class="alert"><div class="error"><?= htmlspecialchars($error) ?></div></div><?php endif; ?>
<?php if ($mixedBranches): ?><div class="alert"><div class="warn">Your cart has items from both branches. Remove the items from one branch to continue.</div></div><?php endif; ?>

<form method="POST" action="reservation-cart.php" id="cartForm">
<input type="hidden" name="update" value="1">
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
                <div class="item-name"><?= htmlspecialchars($it['name']) ?><?php if (($it['category'] ?? '') === 'packages'): ?><span class="item-pkg">Combo</span><?php endif; ?></div>
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
            <a href="reservation-cart.php?remove=<?= $k ?>" class="remove-btn" title="Remove" aria-label="Remove <?= htmlspecialchars($it['name']) ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
            </a>
        </div>
    <?php endforeach; ?>
    </div>

    <div class="summary-card">
        <div class="summary-title">Reservation Summary</div>
        <div class="summary-row"><span>Items (<span id="summaryCount"><?= $itemCount ?></span>)</span><span class="val" id="summarySubtotal">₱<?= number_format($total, 2) ?></span></div>
        <div class="summary-row"><span>Branch</span><span class="val" style="font-size:15px"><?= ucfirst(htmlspecialchars($cartBranch)) ?></span></div>
        <div class="summary-row total"><span>Total</span><span class="val" id="summaryTotal">₱<?= number_format($total, 2) ?></span></div>
        <button type="button" class="checkout-btn" onclick="openReserve()" <?= $mixedBranches ? 'disabled' : '' ?>>Reserve</button>
        <?php if (!$isLoggedIn): ?><div class="guest-note"><a href="log-in.php">Login</a> or <a href="register.php">Sign Up</a> to keep your reservations in one place. You can also reserve as a guest.</div><?php endif; ?>
        <a href="reservation-menu.php" class="continue-link">← Add more items</a>
        <p class="summary-note">Your items are held at the counter once reserved. After reserving, you choose to pick up now or later.</p>
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

<div id="reserveModal" role="dialog" aria-modal="true" aria-labelledby="rvTitle1">
    <div class="rv-card">
        <button type="button" class="rv-close" onclick="closeReserve()" aria-label="Close">✕</button>
        <form method="POST" action="reservation-cart.php" id="reserveForm" novalidate>
            <input type="hidden" name="confirm_reservation" value="1">
            <h3 id="rvTitle1">Reservation <em>details</em></h3>
            <p class="rv-lead">Tell us who is coming and when. The <?= ucfirst(htmlspecialchars($cartBranch)) ?> branch will hold your items at the counter.</p>
            <div class="rv-field"><label for="rvSvc">Order type</label>
                <select id="rvSvc" name="service_type">
                <?php foreach ($svcTypes as $k => $lab): ?><option value="<?= htmlspecialchars($k) ?>" <?= $old['svc'] === $k ? 'selected' : '' ?>><?= htmlspecialchars($lab) ?></option><?php endforeach; ?>
                </select></div>
            <div class="rv-field"><label for="rvName">Name for the reservation</label><input type="text" id="rvName" name="guest_name" required maxlength="100" value="<?= htmlspecialchars($old['name']) ?>" placeholder="Full name"></div>
            <div class="rv-field"><label for="rvPhone">Phone number</label><input type="tel" id="rvPhone" name="guest_phone" required maxlength="20" pattern="[0-9+\-\s()]{7,20}" value="<?= htmlspecialchars($old['phone']) ?>" placeholder="09XX-XXX-XXXX"></div>
            <div class="rv-row">
                <div class="rv-field"><label for="rvDate">Pick-up date</label><input type="date" id="rvDate" name="res_date" required min="<?= $minDate ?>" max="<?= $maxDate ?>" value="<?= htmlspecialchars($old['date']) ?>"></div>
                <div class="rv-field"><label for="rvTime">Pick-up time</label><input type="time" id="rvTime" name="res_time" required min="<?= RES_OPEN ?>" max="<?= RES_CLOSE ?>" value="<?= htmlspecialchars($old['time']) ?>"></div>
            </div>
            <div class="rv-hint">Open <?= date('g:i A', strtotime(RES_OPEN)) ?> to <?= date('g:i A', strtotime(RES_CLOSE)) ?>. Book at least <?= RES_MIN_LEAD_MIN ?> minutes ahead.</div>
            <div class="rv-field"><label for="rvParty">Party size</label><input type="number" id="rvParty" name="party_size" required min="1" max="<?= RES_MAX_PARTY ?>" value="<?= (int)$old['party'] ?: 2 ?>"></div>
            <div class="rv-field"><label for="rvNotes">Notes (optional)</label><textarea id="rvNotes" name="notes" maxlength="500" placeholder="Occasion, seating request, allergies…"><?= htmlspecialchars($old['notes']) ?></textarea></div>
            <button type="button" class="rv-primary" id="rvSubmit" onclick="submitReserve()">Confirm reservation</button>
            <p class="rv-after">Next you'll get your receipt and can choose to pick up now or later.</p>
        </form>
    </div>
</div>
<div id="rvToast" role="status"></div>
<?php endif; ?>

<footer><p>© 2026 <span>SIPPERÉ Café</span> — All rights reserved.</p></footer>

<?php include 'reservation-receipt.inc.php'; ?>

<?php if (!$success && !empty($cartRows)): ?>
<script>
var RES_OPEN_T='<?= RES_OPEN ?>', RES_CLOSE_T='<?= RES_CLOSE ?>', RES_LEAD=<?= (int)RES_MIN_LEAD_MIN ?>;
var RES_OPEN_LBL='<?= date('g:i A', strtotime(RES_OPEN)) ?>', RES_CLOSE_LBL='<?= date('g:i A', strtotime(RES_CLOSE)) ?>';
function $(i){return document.getElementById(i)}
function fmt(v){return '₱'+v.toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2})}
var toastT;
function rvToast(m){var t=$('rvToast');t.textContent=m;t.classList.add('show');clearTimeout(toastT);toastT=setTimeout(function(){t.classList.remove('show')},2800)}
function recalcAll(){
    var g=0,n=0;
    document.querySelectorAll('.qty-input').forEach(function(inp){
        var id=inp.id.replace('qty-',''),q=parseInt(inp.value)||0,sub=(parseFloat(inp.dataset.unit)||0)*q;
        var el=$('sub-'+id); if(el) el.innerHTML='<small>₱</small>'+sub.toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
        g+=sub;n+=q;
    });
    $('summarySubtotal').textContent=fmt(g);$('summaryTotal').textContent=fmt(g);$('summaryCount').textContent=n;
    var h=$('heroItemCount'); if(h) h.textContent=n+' item'+(n!==1?'s':'')+' · <?= ucfirst(htmlspecialchars($cartBranch)) ?> branch';
}
function updateControls(inp){
    var id=inp.id.replace('qty-',''),q=parseInt(inp.value)||0,max=parseInt(inp.dataset.max)||99,lim=q>=max,low=max<=10;
    if($('minus-'+id))$('minus-'+id).disabled=q<=1; if($('plus-'+id))$('plus-'+id).disabled=lim;
    var note=$('note-'+id);
    if(note){ if(lim){note.textContent='Max qty reached ('+max+' in stock)';note.className='item-stock-note limit'}
      else if(low){note.textContent='Only '+max+' left in stock';note.className='item-stock-note low'}
      else{note.textContent=max+' in stock';note.className='item-stock-note ok'} }
}
function checkChanges(){var ch=false;document.querySelectorAll('.qty-input').forEach(function(i){if(parseInt(i.value)!==parseInt(i.dataset.original))ch=true});$('updateBar').classList.toggle('visible',ch)}
function onQtyInput(inp){inp.value=inp.value.replace(/[^0-9]/g,'');var v=parseInt(inp.value),max=parseInt(inp.dataset.max)||99;if(!isNaN(v)&&v>max){inp.value=max;v=max}if(!isNaN(v)&&v>=1){updateControls(inp);recalcAll();checkChanges()}}
function onQtyBlur(inp){var max=parseInt(inp.dataset.max)||99,v=parseInt(inp.value);if(isNaN(v)||v<1)v=1;if(v>max)v=max;inp.value=v;updateControls(inp);recalcAll();checkChanges()}
function changeQty(id,d){var inp=$('qty-'+id),max=parseInt(inp.dataset.max)||99,v=Math.max(1,Math.min((parseInt(inp.value)||0)+d,max));inp.value=v;updateControls(inp);recalcAll();checkChanges()}
function saveChanges(){$('cartForm').submit()}
function discardChanges(){document.querySelectorAll('.qty-input').forEach(function(i){i.value=i.dataset.original;updateControls(i)});recalcAll();$('updateBar').classList.remove('visible')}
function openReserve(){ if($('updateBar').classList.contains('visible')){rvToast('Save or discard your quantity changes first.');return} $('reserveModal').classList.add('show') }
function closeReserve(){ $('reserveModal').classList.remove('show') }
function checkWhen(){
    var d=$('rvDate').value,t=$('rvTime').value,ti=$('rvTime'); ti.setCustomValidity('');
    if(!d||!t)return;
    if(t<RES_OPEN_T||t>RES_CLOSE_T){ti.setCustomValidity('Please choose a time between '+RES_OPEN_LBL+' and '+RES_CLOSE_LBL+'.');return}
    if(new Date(d+'T'+t+':00').getTime()<Date.now()+RES_LEAD*60000) ti.setCustomValidity('Please choose a time at least '+RES_LEAD+' minutes from now.');
}
function submitReserve(){
    var f=$('reserveForm'); checkWhen(); if(!f.reportValidity())return;
    var b=$('rvSubmit'); b.disabled=true; b.textContent='Reserving…'; f.submit();
}
$('rvDate').addEventListener('input',checkWhen); $('rvTime').addEventListener('input',checkWhen);
$('reserveModal').addEventListener('click',function(e){if(e.target===this)closeReserve()});
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeReserve()});
<?php if ($error && isset($_POST['confirm_reservation'])): ?>window.addEventListener('load',openReserve);<?php endif; ?>
</script>
<?php endif; ?>
</body>
</html>
