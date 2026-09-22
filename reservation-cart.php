<?php
/**
 * reservation-cart.php  (v2)
 * -------------------------------------------------------------------------
 * Flow
 *   reservation cart -> "Reserve" (details modal) -> saved -> redirect back here
 *   with ?reserved=ID -> receipt modal "Your order has been reserved!"
 *   with two options at the bottom:
 *        Pick up now   -> reservation-checkout.php (pay: cash / GCash / Maya / card)
 *        Pick up later -> my-reservations.php (items stay at the counter)
 *
 * Fixes in v2
 *   - "Pay now" used to go to checkout.php, which is the SERVE checkout and sends
 *     everybody with an empty serve cart back to cart.php. It now goes to
 *     reservation-checkout.php.
 *   - The reserve modal is one step; the pick-up choice is made in the receipt.
 *   - After saving, the page redirects (?reserved=ID) so refreshing does not
 *     submit the reservation twice.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';
include_once 'reservation-init.php';

// ── SESSION CHECK (guest allowed) ─────────────────────────────────────────
$uid         = resValidUserId($conn);
$isLoggedIn  = $uid > 0;
$defaultName = $isLoggedIn ? trim((string)($_SESSION['user']['username'] ?? '')) : '';

if (!isset($_SESSION['res_cart']) || !is_array($_SESSION['res_cart']))               $_SESSION['res_cart'] = [];
if (!isset($_SESSION['res_cart_addons']) || !is_array($_SESSION['res_cart_addons'])) $_SESSION['res_cart_addons'] = [];

$error   = '';
$success = false;
$receipt = null;
$resData = null;

// ── JUST RESERVED? (we arrive here after the redirect) ────────────────────
if (isset($_GET['reserved'])) {
    $justRes = resLoadReservation($conn, (int)$_GET['reserved']);
    if ($justRes && $justRes['status'] === 'reserved' && resCanAccess($justRes, $uid)) {
        $resData = $justRes;
        $receipt = resReceiptData($justRes);
        $success = true;
    } else {
        header("Location: reservation-cart.php"); exit();
    }
}

// ── HANDLE REMOVE ─────────────────────────────────────────────────────────
if (isset($_GET['remove'])) {
    $rid = (int)$_GET['remove'];
    unset($_SESSION['res_cart'][$rid], $_SESSION['res_cart_addons'][$rid]);
    header("Location: reservation-cart.php"); exit();
}

// ── HANDLE QTY UPDATE ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update']) && isset($_POST['qty']) && is_array($_POST['qty'])) {
    $stockSt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
    foreach ($_POST['qty'] as $id => $qty) {
        $id  = (int)$id;
        $qty = (int)$qty;
        if (!isset($_SESSION['res_cart'][$id])) continue;
        if ($qty <= 0) {
            unset($_SESSION['res_cart'][$id], $_SESSION['res_cart_addons'][$id]);
            continue;
        }
        $stockSt->bind_param('i', $id);
        $stockSt->execute();
        $sRow = $stockSt->get_result()->fetch_assoc();
        $newQty = $sRow ? min($qty, (int)$sRow['stock']) : 0;   // clamp to real stock
        if ($newQty <= 0) unset($_SESSION['res_cart'][$id], $_SESSION['res_cart_addons'][$id]);
        else              $_SESSION['res_cart'][$id] = $newQty;
    }
    header("Location: reservation-cart.php"); exit();
}

// ── BUILD CART ITEMS ──────────────────────────────────────────────────────
$cartRows = [];
$total    = 0.0;

foreach ($_SESSION['res_cart'] as $id => $qty) {
    $id  = (int)$id;
    $qty = (int)$qty;
    if ($qty <= 0) continue;

    $st = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $st->bind_param('i', $id);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$row) { unset($_SESSION['res_cart'][$id], $_SESSION['res_cart_addons'][$id]); continue; }

    $maxStock = (int)($row['stock'] ?? 0);
    if ($maxStock <= 0) { unset($_SESSION['res_cart'][$id], $_SESSION['res_cart_addons'][$id]); continue; }

    $qty = min($qty, $maxStock);
    $_SESSION['res_cart'][$id] = $qty;

    // Selected add-ons for this line (names + prices from the DB, never from the browser)
    $savedAddonIds = json_decode($_SESSION['res_cart_addons'][$id] ?? '[]', true);
    if (!is_array($savedAddonIds)) $savedAddonIds = [];
    $savedAddonIds = array_values(array_filter(array_map('intval', $savedAddonIds), fn($x) => $x > 0));
    $addonRows = [];
    $addonUnitTotal = 0.0;
    if (!empty($savedAddonIds)) {
        $idList = implode(',', $savedAddonIds);
        $aRes = $conn->query("SELECT id, name, price FROM product_addons WHERE id IN ($idList) AND is_active = 1 ORDER BY name ASC");
        if ($aRes) while ($a = $aRes->fetch_assoc()) {
            $addonRows[] = ['name' => $a['name'], 'price' => (float)$a['price']];
            $addonUnitTotal += (float)$a['price'];
        }
    }

    $subtotal = ((float)$row['price'] + $addonUnitTotal) * $qty;
    $total   += $subtotal;
    $cartRows[] = array_merge($row, [
        'qty'              => $qty,
        'subtotal'         => $subtotal,
        'max_stock'        => $maxStock,
        'addon_ids'        => $savedAddonIds,
        'addon_rows'       => $addonRows,
        'addon_unit_total' => $addonUnitTotal,
    ]);
}

$itemCount = array_sum(array_column($cartRows, 'qty'));

// One reservation = one branch
$cartBranches  = array_values(array_unique(array_map(fn($r) => $r['branch'], $cartRows)));
$mixedBranches = count($cartBranches) > 1;
$cartBranch    = $cartBranches[0] ?? ($_SESSION['branch'] ?? 'laguna');

// All active add-ons once, filtered per category
$allAddons = $conn->query("SELECT id, name, price, applies_to FROM product_addons WHERE is_active = 1 ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
function addonsForCategory($allAddons, $category) {
    return array_values(array_filter($allAddons, fn($a) => $a['applies_to'] === 'all' || $a['applies_to'] === $category));
}

// ── HANDLE RESERVE (form inside the modal) ────────────────────────────────
$old = ['name' => $defaultName, 'phone' => '', 'date' => '', 'time' => '', 'party' => 2, 'notes' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reservation'])) {
    $old = [
        'name'  => trim((string)($_POST['guest_name'] ?? '')),
        'phone' => trim((string)($_POST['guest_phone'] ?? '')),
        'date'  => trim((string)($_POST['res_date'] ?? '')),
        'time'  => trim((string)($_POST['res_time'] ?? '')),
        'party' => (int)($_POST['party_size'] ?? 0),
        'notes' => mb_substr(trim((string)($_POST['notes'] ?? '')), 0, 500),
    ];
    if (preg_match('/^(\d{2}:\d{2})(:\d{2})?$/', $old['time'], $m)) $old['time'] = $m[1];

    do {
        if (empty($cartRows))  { $error = 'Your reservation cart is empty. Add some items first.'; break; }
        if ($mixedBranches)    { $error = 'Your cart has items from more than one branch. Please keep one branch per reservation.'; break; }
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
            // lock + re-check stock for every line so two customers can't grab the last item
            $lock = $conn->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
            foreach ($cartRows as $item) {
                $pid = (int)$item['id'];
                $lock->bind_param('i', $pid);
                $lock->execute();
                $p = $lock->get_result()->fetch_assoc();
                if (!$p || (int)$p['stock'] < (int)$item['qty']) {
                    throw new Exception('Sorry, "' . $item['name'] . '" no longer has enough stock. Please update your reservation cart.');
                }
            }
            $lock->close();

            $userIdParam = $isLoggedIn ? $uid : null;
            $st = $conn->prepare("INSERT INTO reservations
                (user_id, guest_name, guest_phone, branch, reservation_date, reservation_time, party_size, notes, total, status, payment_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'reserved', 'unpaid')");
            $st->bind_param('isssssisd', $userIdParam, $old['name'], $old['phone'], $cartBranch, $old['date'], $timeSql, $old['party'], $old['notes'], $total);
            if (!$st->execute()) throw new Exception('Could not create the reservation. Please try again.');
            $resId = (int)$conn->insert_id;
            $st->close();
            if (!$resId) throw new Exception('Could not create the reservation. Please try again.');

            $code = 'AYS-RSV-' . date('Ymd') . '-' . str_pad((string)$resId, 5, '0', STR_PAD_LEFT);
            $st = $conn->prepare("UPDATE reservations SET reservation_code = ? WHERE id = ?");
            $st->bind_param('si', $code, $resId);
            $st->execute();
            $st->close();

            $insItem = $conn->prepare("INSERT INTO reservation_items (reservation_id, product_id, product_name, quantity, price, addons, addons_total)
                                       VALUES (?, ?, ?, ?, ?, ?, ?)");
            $hold = $conn->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?");
            foreach ($cartRows as $item) {
                $pid    = (int)$item['id'];
                $qty    = (int)$item['qty'];
                $name   = (string)$item['name'];
                $price  = (float)$item['price'];
                $addons = json_encode($item['addon_rows'], JSON_UNESCAPED_UNICODE);
                $addTot = (float)$item['addon_unit_total'];
                $insItem->bind_param('iisidsd', $resId, $pid, $name, $qty, $price, $addons, $addTot);
                if (!$insItem->execute()) throw new Exception('Could not save the reservation items. Please try again.');
                $hold->bind_param('ii', $qty, $pid);   // reserved = held at the counter: stock is taken off the shelf now
                $hold->execute();
            }
            $insItem->close();
            $hold->close();

            $conn->commit();

            unset($_SESSION['res_cart'], $_SESSION['res_cart_addons']);
            if (!$isLoggedIn) {                                   // lets a guest see / pay this one later
                if (!isset($_SESSION['guest_reservations']) || !is_array($_SESSION['guest_reservations'])) $_SESSION['guest_reservations'] = [];
                $_SESSION['guest_reservations'][] = $resId;
            }

            // Redirect so a refresh never creates a second reservation.
            // The receipt modal opens on the page we land on.
            header("Location: reservation-cart.php?reserved=" . $resId);
            exit();

        } catch (Exception $e) {
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
    <title>Reservation Cart — AyosCoffeeNegosyo</title>
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
        .page-hero h1{font-family:'Cormorant Garamond',serif;font-size:clamp(36px,5vw,56px);font-weight:700;color:var(--cream);letter-spacing:-0.01em}
        .page-hero h1 em{font-style:italic;color:var(--gold)}
        .item-count{margin-top:10px;font-size:13px;color:var(--muted);letter-spacing:0.05em}
        .alert{position:relative;z-index:1;max-width:1100px;margin:0 auto 22px;padding:0 32px}
        .alert-inner{display:flex;align-items:flex-start;gap:10px;border-radius:3px;padding:12px 16px;font-size:13px;line-height:1.55}
        .alert-inner.error{background:rgba(139,46,46,0.15);border:1px solid var(--red);color:#e07b7b}
        .alert-inner.warn{background:rgba(212,130,10,0.1);border:1px solid var(--amber);color:#e0a552}
        .alert-inner svg{flex-shrink:0;margin-top:2px}
        .cart-layout{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:0 32px 80px;display:grid;grid-template-columns:1fr 340px;gap:28px;align-items:start}
        .cart-items{display:flex;flex-direction:column;gap:14px}
        .cart-item{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:20px 24px;display:flex;align-items:flex-start;gap:20px;transition:border-color 0.25s;animation:slideIn 0.35s ease both;flex-wrap:wrap}
        .cart-item:hover{border-color:var(--gold-dim)}
        @keyframes slideIn{from{opacity:0;transform:translateX(-12px)}to{opacity:1;transform:translateX(0)}}
        .item-icon{width:52px;height:52px;flex-shrink:0;background:var(--surface);border:1px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--gold-dim)}
        .item-info{flex:1;min-width:220px}
        .item-name{font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:600;color:var(--cream);margin-bottom:4px}
        .item-pkg{display:inline-block;margin-left:8px;vertical-align:middle;background:var(--gold);color:#1a1400;font-family:'Jost',sans-serif;font-size:9.5px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;padding:2px 8px;border-radius:3px}
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
        .checkout-btn:hover:not(:disabled){background:var(--green-lt)}
        .checkout-btn:active:not(:disabled){transform:scale(0.98)}
        .checkout-btn:disabled{opacity:.45;cursor:not-allowed}
        .continue-link{display:block;text-align:center;margin-top:14px;font-size:12.5px;color:var(--muted);text-decoration:none;letter-spacing:0.06em;transition:color 0.2s}
        .continue-link:hover{color:var(--gold)}
        .summary-note{margin-top:20px;padding-top:16px;border-top:1px solid var(--border);font-size:11.5px;color:var(--muted);line-height:1.6;text-align:center}
        .guest-note{background:rgba(201,168,76,0.06);border:1px solid var(--gold-dim);border-radius:3px;padding:10px 14px;font-size:12px;color:var(--muted);margin-top:14px;line-height:1.5;text-align:center}
        .guest-note a{color:var(--gold);text-decoration:none}
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
        footer{position:relative;z-index:1;border-top:1px solid var(--border);padding:28px 32px;text-align:center}
        footer p{font-size:12px;color:var(--muted);letter-spacing:0.06em}
        footer p span{color:var(--gold-dim)}

        /* add-ons picker (same look as cart.php) */
        .addon-picker{margin-top:10px;width:100%}
        .addon-toggle{display:inline-flex;align-items:center;gap:6px;background:transparent;border:1px solid var(--gold-dim);color:var(--gold);font-family:'Jost',sans-serif;font-size:11px;font-weight:500;letter-spacing:0.06em;text-transform:uppercase;padding:5px 12px;border-radius:3px;cursor:pointer;transition:all 0.2s}
        .addon-toggle:hover{background:rgba(201,168,76,0.08)}
        .addon-count{background:var(--gold);color:#000;border-radius:8px;padding:0 6px;font-size:10px;font-weight:700;min-width:16px;text-align:center;display:inline-block}
        .addon-count:empty{display:none}
        .addon-panel{background:var(--surface);border:1px solid var(--border);border-radius:4px;padding:12px 14px;margin-top:8px;display:flex;flex-direction:column;gap:8px}
        .addon-row{display:flex;align-items:center;gap:9px;font-size:12.5px;color:var(--text);cursor:pointer;padding:3px 0}
        .addon-row input{accent-color:var(--gold);width:15px;height:15px;cursor:pointer}
        .addon-price{margin-left:auto;color:var(--gold-dim);font-family:'Cormorant Garamond',serif;font-size:14px;font-weight:600}
        .addon-empty{font-size:12px;color:var(--muted);font-style:italic}

        /* ══ RESERVE MODAL (one step: who is coming and when) ══ */
        #reserveModal{position:fixed;inset:0;z-index:400;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.78);backdrop-filter:blur(6px);opacity:0;pointer-events:none;transition:opacity .3s ease;padding:16px}
        #reserveModal.show{opacity:1;pointer-events:all}
        .rv-card{position:relative;background:var(--card);border:1px solid var(--border);border-radius:6px;padding:32px 30px 28px;max-width:440px;width:100%;max-height:92vh;overflow-y:auto;transform:translateY(20px);transition:transform .3s ease}
        #reserveModal.show .rv-card{transform:translateY(0)}
        .rv-close{position:absolute;top:12px;right:12px;width:28px;height:28px;border:1px solid var(--border);border-radius:50%;background:transparent;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s}
        .rv-close:hover{border-color:var(--gold-dim);color:var(--cream)}
        .rv-card h3{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--cream);margin-bottom:6px}
        .rv-card h3 em{font-style:italic;color:var(--gold)}
        .rv-lead{font-size:13px;color:var(--muted);line-height:1.6;margin-bottom:20px}
        .rv-field{margin-bottom:14px}
        .rv-field label{display:block;font-size:11px;font-weight:500;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-bottom:7px}
        .rv-field input,.rv-field textarea{width:100%;background:var(--surface);border:1px solid var(--border);border-radius:3px;color:var(--cream);font-family:'Jost',sans-serif;font-size:14px;padding:10px 13px;outline:none;transition:border-color .2s;color-scheme:dark}
        .rv-field input:focus,.rv-field textarea:focus{border-color:var(--gold-dim)}
        .rv-field textarea{resize:vertical;min-height:64px}
        .rv-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .rv-hint{font-size:11px;color:var(--muted);margin-top:5px}
        .rv-primary{display:flex;align-items:center;justify-content:center;gap:9px;width:100%;padding:13px;margin-top:6px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;color:#fff;cursor:pointer;transition:background .2s}
        .rv-primary:hover:not(:disabled){background:var(--green-lt)}
        .rv-primary:disabled{opacity:.6;cursor:not-allowed}
        .rv-after{font-size:11.5px;color:var(--muted);line-height:1.6;text-align:center;margin-top:12px}
        #rvToast{position:fixed;bottom:84px;left:50%;transform:translate(-50%,16px);z-index:300;background:var(--card);border:1px solid var(--gold-dim);border-radius:4px;padding:11px 18px;font-size:13px;color:var(--cream);opacity:0;pointer-events:none;transition:all .3s ease;max-width:calc(100vw - 32px)}
        #rvToast.show{opacity:1;transform:translate(-50%,0)}

        /* ══ SUCCESS (reserved) ══ */
        .success-wrap{position:relative;z-index:1;max-width:520px;margin:0 auto;text-align:center;padding:10px 32px 80px}
        .success-icon{width:88px;height:88px;margin:0 auto 26px;border:1px solid var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--green-lt);animation:popIn 0.5s cubic-bezier(0.34,1.56,0.64,1) both}
        @keyframes popIn{from{opacity:0;transform:scale(0.5)}to{opacity:1;transform:scale(1)}}
        .success-wrap h2{font-family:'Cormorant Garamond',serif;font-size:40px;font-weight:700;color:var(--cream);margin-bottom:12px}
        .success-wrap h2 em{font-style:italic;color:var(--gold)}
        .success-wrap p{font-size:14px;color:var(--muted);line-height:1.7;margin-bottom:8px}
        .success-wrap p strong{color:var(--gold);font-weight:500}
        .order-badge{display:inline-flex;align-items:center;gap:8px;background:var(--card);border:1px solid var(--gold-dim);border-radius:3px;padding:8px 18px;font-size:13px;color:var(--gold);margin:16px 0 24px;letter-spacing:0.08em}
        .success-items{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:10px 16px;margin:0 auto 26px;max-width:420px;text-align:left}
        .success-item-row{display:flex;align-items:center;gap:12px;font-size:13px;color:var(--muted);padding:10px 4px;border-bottom:1px solid rgba(44,44,36,0.4)}
        .success-item-row:last-child{border-bottom:none}
        .success-item-row .sname{flex:1;color:var(--cream);font-size:13.5px}
        .success-item-row .saddons{color:var(--gold-dim);font-size:11px;font-style:italic;margin-top:2px}
        .success-item-row .sq{color:var(--gold-dim);font-size:12px}
        .success-item-row .sp{color:var(--text);min-width:70px;text-align:right}
        .success-item-row.grand .sname{font-weight:600}
        .success-item-row.grand .sp{color:var(--gold);font-family:'Cormorant Garamond',serif;font-size:19px}
        .success-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
        .btn-primary{display:inline-flex;align-items:center;gap:8px;padding:13px 26px;background:var(--green);border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:#fff;text-decoration:none;transition:background 0.2s;border:none;cursor:pointer}
        .btn-primary:hover{background:var(--green-lt)}
        .btn-ghost{display:inline-flex;align-items:center;gap:8px;padding:13px 26px;border:1px solid var(--border);border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted);text-decoration:none;transition:all 0.2s;cursor:pointer;background:transparent}
        .btn-ghost:hover{border-color:var(--gold-dim);color:var(--gold)}

        @media(max-width:768px){
            .cart-layout{grid-template-columns:1fr;padding:0 16px 80px}
            .summary-card{position:static}
            .header-inner{padding:0 16px}
            nav a:not(.nav-back){display:none}
            .cart-item{flex-wrap:wrap;gap:14px}
            .item-subtotal{min-width:auto}
            .update-bar{padding:14px 16px}
            .alert{padding:0 16px}
            .rv-row{grid-template-columns:1fr}
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
            <a href="my-reservations.php">Reservations</a>
            <a href="reservation-orders.php">Orders</a>
            <?php if ($isLoggedIn): ?>
                <a href="log-out.php">Logout</a>
            <?php else: ?>
                <a href="log-in.php">Login</a>
                <a href="register.php">Sign Up</a>
            <?php endif; ?>
            <a href="reservation-menu.php" class="nav-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back to Menu
            </a>
        </nav>
    </div>
</header>

<?php if ($success): ?>
<!-- ══════════ SUCCESS: reserved (the receipt modal opens on top) ══════════ -->
<div class="page-hero">
    <div class="hero-eyebrow">Reservation confirmed</div>
    <h1>You're <em>Reserved</em></h1>
</div>
<div class="success-wrap">
    <div class="success-icon">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <h2>Order <em>Reserved!</em></h2>
    <p>Thank you, <strong><?= htmlspecialchars($resData['guest_name']) ?></strong>. Your order is reserved at the <strong><?= ucfirst(htmlspecialchars($resData['branch'])) ?> branch</strong> for
       <strong><?= htmlspecialchars(resFormatDate($resData['reservation_date'])) ?> at <?= htmlspecialchars(resFormatTime($resData['reservation_time'])) ?></strong>.</p>
    <p>It stays at the counter until you pick it up. Pick up and pay now, or pay later when you arrive.</p>

    <div class="order-badge">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <?= htmlspecialchars($receipt['code']) ?>
    </div>

    <div class="success-items">
        <?php foreach ($resData['items'] as $it): ?>
        <div class="success-item-row">
            <div class="sname"><?= htmlspecialchars($it['product_name']) ?>
                <?php if (!empty($it['addon_list'])): ?><div class="saddons">+ <?= htmlspecialchars(implode(', ', array_column($it['addon_list'], 'name'))) ?></div><?php endif; ?>
            </div>
            <div class="sq">×<?= (int)$it['quantity'] ?></div>
            <div class="sp">₱<?= number_format($it['line_total'], 2) ?></div>
        </div>
        <?php endforeach; ?>
        <div class="success-item-row grand"><div class="sname">Total</div><div class="sp">₱<?= number_format((float)$resData['total'], 2) ?></div></div>
    </div>

    <?php if (!$isLoggedIn): ?>
    <p style="margin-bottom:22px"><a href="register.php" style="color:var(--gold);text-decoration:none">Create an account</a> to keep all your reservations in one place. Please keep your reservation code.</p>
    <?php endif; ?>

    <div class="success-actions">
        <button type="button" class="btn-primary" onclick="openReservationReceipt(RES_RECEIPT, {fresh:true})">View Receipt</button>
        <a href="my-reservations.php" class="btn-ghost">My Reservations</a>
        <a href="reservation-menu.php" class="btn-ghost">Browse Menu</a>
    </div>
</div>
<script>
const RES_RECEIPT = <?= resJson($receipt) ?>;
document.addEventListener('DOMContentLoaded', function(){ openReservationReceipt(RES_RECEIPT, {fresh:true}); });
</script>

<?php elseif (empty($cartRows)): ?>
<!-- ══════════ EMPTY ══════════ -->
<div class="page-hero">
    <div class="hero-eyebrow">Review your reservation</div>
    <h1>Reservation <em>Cart</em></h1>
</div>
<?php if ($error): ?>
<div class="alert"><div class="alert-inner error"><?= htmlspecialchars($error) ?></div></div>
<?php endif; ?>
<div class="empty-state">
    <div class="empty-icon">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
    </div>
    <h3>Nothing reserved yet</h3>
    <p>Pick your favorites or a Packages Combo from the reservation menu.</p>
    <a href="reservation-menu.php" class="browse-btn">Browse Reservation Menu</a>
</div>

<?php else: ?>
<!-- ══════════ CART ══════════ -->
<div class="page-hero">
    <div class="hero-eyebrow">Review your reservation</div>
    <h1>Reservation <em>Cart</em></h1>
    <p class="item-count" id="heroItemCount"><?= $itemCount ?> item<?= $itemCount !== 1 ? 's' : '' ?> · <?= ucfirst(htmlspecialchars($cartBranch)) ?> branch</p>
</div>

<?php if ($error): ?>
<div class="alert"><div class="alert-inner error">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <span><?= htmlspecialchars($error) ?></span>
</div></div>
<?php endif; ?>
<?php if ($mixedBranches): ?>
<div class="alert"><div class="alert-inner warn">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    <span>Your cart has items from both branches. Remove the items from one branch to continue.</span>
</div></div>
<?php endif; ?>

<form method="POST" action="reservation-cart.php" id="cartForm">
    <input type="hidden" name="update" value="1">
    <div class="cart-layout">
        <div class="cart-items">
            <?php foreach ($cartRows as $i => $item):
                $maxStock  = (int)$item['max_stock'];
                $atLimit   = $item['qty'] >= $maxStock;
                $isLow     = $maxStock <= 10;
                $stockNote = $atLimit ? "Max qty reached ($maxStock in stock)" : ($isLow ? "Only $maxStock left in stock" : "$maxStock in stock");
                $noteClass = $atLimit ? 'limit' : ($isLow ? 'low' : 'ok');
                $itemAddonOptions = addonsForCategory($allAddons, $item['category'] ?? 'mains');
            ?>
            <div class="cart-item" style="animation-delay:<?= $i * 0.06 ?>s">
                <div class="item-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
                </div>
                <div class="item-info">
                    <div class="item-name"><?= htmlspecialchars($item['name']) ?><?php if (($item['category'] ?? '') === 'packages'): ?><span class="item-pkg">Combo</span><?php endif; ?></div>
                    <div class="item-unit-price" id="unit-<?= $item['id'] ?>">₱<?= number_format($item['price'], 2) ?> each<?= $item['addon_unit_total'] > 0 ? ' + ₱' . number_format($item['addon_unit_total'], 2) . ' add-ons' : '' ?></div>
                    <div class="item-stock-note <?= $noteClass ?>" id="note-<?= $item['id'] ?>"><?= $stockNote ?></div>

                    <div class="addon-picker">
                        <button type="button" class="addon-toggle" onclick="toggleAddonPanel(<?= $item['id'] ?>)">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add-ons
                            <span class="addon-count" id="addonCount-<?= $item['id'] ?>"><?= count($item['addon_ids']) ?: '' ?></span>
                        </button>
                        <div class="addon-panel" id="addonPanel-<?= $item['id'] ?>" style="display:none">
                            <?php if (empty($itemAddonOptions)): ?>
                                <div class="addon-empty">No add-ons available for this item.</div>
                            <?php else: foreach ($itemAddonOptions as $a):
                                $checked = in_array((int)$a['id'], $item['addon_ids']) ? 'checked' : '';
                            ?>
                            <label class="addon-row">
                                <input type="checkbox" value="<?= (int)$a['id'] ?>" data-price="<?= htmlspecialchars($a['price']) ?>" <?= $checked ?>
                                       onchange="updateAddons(<?= $item['id'] ?>)">
                                <span><?= htmlspecialchars($a['name']) ?></span>
                                <span class="addon-price">+₱<?= number_format($a['price'], 2) ?></span>
                            </label>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
                <div class="qty-wrap">
                    <button type="button" class="qty-btn" id="minus-<?= $item['id'] ?>" onclick="changeQty('<?= $item['id'] ?>', -1)" <?= $item['qty'] <= 1 ? 'disabled' : '' ?>>−</button>
                    <input type="text" inputmode="numeric" autocomplete="off" class="qty-input" name="qty[<?= $item['id'] ?>]" id="qty-<?= $item['id'] ?>" value="<?= $item['qty'] ?>" data-max="<?= $maxStock ?>" data-price="<?= $item['price'] ?>" data-addon-total="<?= $item['addon_unit_total'] ?>" data-original="<?= $item['qty'] ?>" oninput="onQtyInput(this)" onblur="onQtyBlur(this)" aria-label="Quantity">
                    <button type="button" class="qty-btn" id="plus-<?= $item['id'] ?>" onclick="changeQty('<?= $item['id'] ?>', 1)" <?= $atLimit ? 'disabled' : '' ?>>+</button>
                </div>
                <div class="item-subtotal" id="sub-<?= $item['id'] ?>"><small>₱</small><?= number_format($item['subtotal'], 2) ?></div>
                <a href="reservation-cart.php?remove=<?= $item['id'] ?>" class="remove-btn" title="Remove" aria-label="Remove <?= htmlspecialchars($item['name']) ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="summary-card">
            <div class="summary-title">Reservation Summary</div>
            <div class="summary-row">
                <span>Items (<span id="summaryCount"><?= $itemCount ?></span>)</span>
                <span class="val" id="summarySubtotal">₱<?= number_format($total, 2) ?></span>
            </div>
            <div class="summary-row">
                <span>Branch</span>
                <span class="val" style="font-size:15px"><?= ucfirst(htmlspecialchars($cartBranch)) ?></span>
            </div>
            <div class="summary-row total">
                <span>Total</span>
                <span class="val" id="summaryTotal">₱<?= number_format($total, 2) ?></span>
            </div>

            <button type="button" class="checkout-btn" onclick="openReserve()" <?= $mixedBranches ? 'disabled' : '' ?>>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Reserve
            </button>
            <?php if (!$isLoggedIn): ?>
            <div class="guest-note"><a href="log-in.php">Login</a> or <a href="register.php">Sign Up</a> to keep your reservations in one place. You can also reserve as a guest.</div>
            <?php endif; ?>

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

<!-- ══ RESERVE MODAL ══ -->
<div id="reserveModal" role="dialog" aria-modal="true" aria-labelledby="rvTitle1">
    <div class="rv-card">
        <button type="button" class="rv-close" onclick="closeReserve()" aria-label="Close">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>

        <form method="POST" action="reservation-cart.php" id="reserveForm" novalidate>
            <input type="hidden" name="confirm_reservation" value="1">

            <h3 id="rvTitle1">Reservation <em>details</em></h3>
            <p class="rv-lead">Tell us who is coming and when. The <?= ucfirst(htmlspecialchars($cartBranch)) ?> branch will hold your items at the counter.</p>

            <div class="rv-field">
                <label for="rvName">Name for the reservation</label>
                <input type="text" id="rvName" name="guest_name" required maxlength="100" value="<?= htmlspecialchars($old['name']) ?>" placeholder="Full name">
            </div>
            <div class="rv-field">
                <label for="rvPhone">Phone number</label>
                <input type="tel" id="rvPhone" name="guest_phone" required maxlength="20" pattern="[0-9+\-\s()]{7,20}" value="<?= htmlspecialchars($old['phone']) ?>" placeholder="09XX-XXX-XXXX">
            </div>
            <div class="rv-row">
                <div class="rv-field">
                    <label for="rvDate">Pick-up date</label>
                    <input type="date" id="rvDate" name="res_date" required min="<?= $minDate ?>" max="<?= $maxDate ?>" value="<?= htmlspecialchars($old['date']) ?>">
                </div>
                <div class="rv-field">
                    <label for="rvTime">Pick-up time</label>
                    <input type="time" id="rvTime" name="res_time" required min="<?= RES_OPEN ?>" max="<?= RES_CLOSE ?>" value="<?= htmlspecialchars($old['time']) ?>">
                </div>
            </div>
            <div class="rv-hint" style="margin:-6px 0 14px">Open <?= date('g:i A', strtotime(RES_OPEN)) ?> to <?= date('g:i A', strtotime(RES_CLOSE)) ?>. Book at least <?= RES_MIN_LEAD_MIN ?> minutes ahead.</div>
            <div class="rv-field">
                <label for="rvParty">Party size</label>
                <input type="number" id="rvParty" name="party_size" required min="1" max="<?= RES_MAX_PARTY ?>" value="<?= (int)$old['party'] ?: 2 ?>">
            </div>
            <div class="rv-field">
                <label for="rvNotes">Notes <span style="text-transform:none;letter-spacing:0">(optional)</span></label>
                <textarea id="rvNotes" name="notes" maxlength="500" placeholder="Occasion, seating request, allergies…"><?= htmlspecialchars($old['notes']) ?></textarea>
            </div>
            <button type="button" class="rv-primary" id="rvSubmit" onclick="submitReserve()">Confirm reservation</button>
            <p class="rv-after">Next you'll get your receipt and can choose to pick up now or later.</p>
        </form>
    </div>
</div>
<div id="rvToast" role="status"></div>
<?php endif; ?>

<footer><p>© 2026 <span>My AyosCoffeeNegosyo</span> — All rights reserved.</p></footer>

<?php include 'reservation-receipt.inc.php'; ?>

<?php if (!$success && !empty($cartRows)): ?>
<script>
var RES_OPEN_T  = '<?= RES_OPEN ?>', RES_CLOSE_T = '<?= RES_CLOSE ?>', RES_LEAD = <?= (int)RES_MIN_LEAD_MIN ?>;
var RES_OPEN_LBL = '<?= date('g:i A', strtotime(RES_OPEN)) ?>', RES_CLOSE_LBL = '<?= date('g:i A', strtotime(RES_CLOSE)) ?>';

function fmt(v){return '₱'+v.toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2})}
function $(id){return document.getElementById(id)}

var toastT;
function rvToast(msg){var t=$('rvToast');t.textContent=msg;t.classList.add('show');clearTimeout(toastT);toastT=setTimeout(function(){t.classList.remove('show')},2800)}

function recalcAll(){
    var grand=0,totalItems=0;
    document.querySelectorAll('.qty-input').forEach(function(inp){
        var id=inp.id.replace('qty-',''),qty=parseInt(inp.value)||0,price=parseFloat(inp.dataset.price)||0;
        var addonTotal=parseFloat(inp.dataset.addonTotal)||0;
        var sub=(price+addonTotal)*qty;
        var subEl=$('sub-'+id);
        if(subEl)subEl.innerHTML='<small>₱</small>'+sub.toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
        var unitEl=$('unit-'+id);
        if(unitEl){unitEl.textContent=fmt(price)+' each'+(addonTotal>0?' + '+fmt(addonTotal)+' add-ons':'');}
        grand+=sub;totalItems+=qty;
    });
    $('summarySubtotal').textContent=fmt(grand);
    $('summaryTotal').textContent=fmt(grand);
    $('summaryCount').textContent=totalItems;
    var hero=$('heroItemCount');
    if(hero)hero.textContent=totalItems+' item'+(totalItems!==1?'s':'')+' · <?= ucfirst(htmlspecialchars($cartBranch)) ?> branch';
}

function updateControls(inp){
    var id=inp.id.replace('qty-',''),qty=parseInt(inp.value)||0,maxStock=parseInt(inp.dataset.max)||99,atLimit=qty>=maxStock,isLow=maxStock<=10;
    var minus=$('minus-'+id),plus=$('plus-'+id);
    if(minus)minus.disabled=qty<=1;if(plus)plus.disabled=atLimit;
    var note=$('note-'+id);
    if(note){
        if(atLimit){note.textContent='Max qty reached ('+maxStock+' in stock)';note.className='item-stock-note limit';}
        else if(isLow){note.textContent='Only '+maxStock+' left in stock';note.className='item-stock-note low';}
        else{note.textContent=maxStock+' in stock';note.className='item-stock-note ok';}
    }
}

function checkChanges(){
    var changed=false;
    document.querySelectorAll('.qty-input').forEach(function(inp){if(parseInt(inp.value)!==parseInt(inp.dataset.original))changed=true;});
    $('updateBar').classList.toggle('visible',changed);
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
    var inp=$('qty-'+id),maxStock=parseInt(inp.dataset.max)||99,val=(parseInt(inp.value)||0)+delta;
    val=Math.max(1,Math.min(val,maxStock));inp.value=val;updateControls(inp);recalcAll();checkChanges();
}
function saveChanges(){$('cartForm').submit();}
function discardChanges(){
    document.querySelectorAll('.qty-input').forEach(function(inp){inp.value=inp.dataset.original;updateControls(inp);});
    recalcAll();$('updateBar').classList.remove('visible');
}

// ── add-ons (saved on the server so the reservation uses them) ──
function toggleAddonPanel(id){
    var p=$('addonPanel-'+id);
    if(!p)return;
    p.style.display = p.style.display==='none' ? 'flex' : 'none';
}
function updateAddons(itemId){
    var panel=$('addonPanel-'+itemId);
    var checked=[].slice.call(panel.querySelectorAll('input:checked'));
    var ids=checked.map(function(c){return parseInt(c.value);});
    var addonUnitTotal=checked.reduce(function(s,c){return s+parseFloat(c.dataset.price);},0);
    var countEl=$('addonCount-'+itemId);
    if(countEl) countEl.textContent = ids.length || '';
    var qtyInput=$('qty-'+itemId);
    if(qtyInput){ qtyInput.dataset.addonTotal = addonUnitTotal; }
    recalcAll();
    fetch('reservation_handler.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=addons&item_id='+encodeURIComponent(itemId)+'&addons='+encodeURIComponent(JSON.stringify(ids))
    }).then(function(r){return r.json()}).then(function(d){
        if(!d.success) rvToast(d.message||'Could not save add-ons.');
    }).catch(function(){ rvToast('Network error — add-ons may not be saved.'); });
}

// ── reserve modal ──
function openReserve(){
    if($('updateBar').classList.contains('visible')){ rvToast('Save or discard your quantity changes first.'); return; }
    $('reserveModal').classList.add('show');
}
function closeReserve(){ $('reserveModal').classList.remove('show'); }
function checkWhen(){
    var d=$('rvDate').value,t=$('rvTime').value,ti=$('rvTime');
    ti.setCustomValidity('');
    if(!d||!t) return;
    if(t<RES_OPEN_T||t>RES_CLOSE_T){ ti.setCustomValidity('Please choose a time between '+RES_OPEN_LBL+' and '+RES_CLOSE_LBL+'.'); return; }
    var when=new Date(d+'T'+t+':00');
    if(when.getTime()<Date.now()+RES_LEAD*60000){ ti.setCustomValidity('Please choose a time at least '+RES_LEAD+' minutes from now.'); }
}
function submitReserve(){
    var form=$('reserveForm');
    checkWhen();
    if(!form.reportValidity()) return;
    var btn=$('rvSubmit');
    btn.disabled=true; btn.textContent='Reserving…';   // no double submit
    form.submit();
}
$('rvDate').addEventListener('input',checkWhen);
$('rvTime').addEventListener('input',checkWhen);
$('reserveModal').addEventListener('click',function(e){if(e.target===this)closeReserve();});
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeReserve();});
<?php if ($error && isset($_POST['confirm_reservation'])): ?>
window.addEventListener('load',function(){ openReserve(); });
<?php endif; ?>
</script>
<?php endif; ?>
</body>
</html>