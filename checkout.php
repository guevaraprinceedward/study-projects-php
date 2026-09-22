<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';

// ── GUEST OR LOGGED IN ────────────────────────────────────────────────────
$isLoggedIn = false;
$uid        = 0;
$userName   = 'Guest';
$isGuest    = isset($_GET['guest']) && $_GET['guest'] == '1';

if (isset($_SESSION["user"])) {
    $uid   = (int)($_SESSION["user"]["id"] ?? 0);
    $uname = $conn->real_escape_string($_SESSION["user"]["username"] ?? '');
    $chk   = $conn->query("SELECT id FROM users WHERE id = $uid AND username = '$uname' LIMIT 1");
    if ($chk && $chk->num_rows > 0) {
        $isLoggedIn = true;
        $userName   = htmlspecialchars($_SESSION["user"]["username"]);
        $isGuest    = false;
    } else {
        session_unset(); session_destroy();
    }
}

// If not logged in and not explicitly guest, redirect
if (!$isLoggedIn && !$isGuest) {
    header("Location: log-in.php"); exit();
}

// Ensure columns
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS notes TEXT DEFAULT NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'pending'");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS branch VARCHAR(20) DEFAULT 'laguna'");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT 'cash'");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS guest_name VARCHAR(100) DEFAULT NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS guest_phone VARCHAR(20) DEFAULT NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS receipt_code VARCHAR(40) DEFAULT NULL");
$conn->query("ALTER TABLE order_items ADD COLUMN IF NOT EXISTS price decimal(10,2) DEFAULT NULL");

// ── ADD-ONS: ensure table + order_items columns exist ─────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS product_addons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    applies_to VARCHAR(20) DEFAULT 'all',
    is_active TINYINT(1) DEFAULT 1
)");
$conn->query("ALTER TABLE order_items ADD COLUMN IF NOT EXISTS addons TEXT DEFAULT NULL");
$conn->query("ALTER TABLE order_items ADD COLUMN IF NOT EXISTS addons_total DECIMAL(10,2) DEFAULT 0");

if (empty($_SESSION['cart'])) { header("Location: cart.php"); exit(); }

// ── HELPER: pull the first non-empty column from a row, checking a list of
//    possible names — products tables vary (image / image_url / photo…),
//    so the receipt/detail modal degrades gracefully instead of breaking. ──
function pickField($row, array $candidates, $default = null) {
    foreach ($candidates as $c) {
        if (!empty($row[$c])) return $row[$c];
    }
    return $default;
}

// ── HELPER: strip stray HTML/quote artifacts from display text ────────────
// Defense-in-depth: some rows in `products` (name / ingredients / etc.) may
// contain leftover junk like  '">  or  ">  from old bad input that got saved
// verbatim (e.g. via an unescaped add/edit-product form). This keeps that
// junk from leaking into the customer-facing receipt/detail modal even if
// the underlying data hasn't been cleaned up yet. The real fix is cleaning
// the DB row itself — see the accompanying note.
function cleanDisplayText($str) {
    if ($str === null) return $str;
    $str = (string)$str;

    // Pass 1: strip a leading run of ASCII quote/bracket junk (straight quotes,
    // angle brackets, whitespace/control chars).
    $prev = null;
    while ($prev !== $str) {
        $prev = $str;
        $str = preg_replace('/^[\'"<>\x00-\x20]+/u', '', $str);
    }

    // Pass 2: also strip "smart"/curly quote variants some editors or copy-paste
    // sources introduce (’ ‘ “ ” and similar), which the ASCII-only class above
    // would miss.
    $prev = null;
    while ($prev !== $str) {
        $prev = $str;
        $str = preg_replace('/^[\x{2018}\x{2019}\x{201C}\x{201D}\x{FF02}\x{FF07}]+/u', '', $str);
    }

    // Trailing cleanup, same two passes in reverse.
    $prev = null;
    while ($prev !== $str) {
        $prev = $str;
        $str = preg_replace('/[\'"<>\x00-\x20]+$/u', '', $str);
    }
    $prev = null;
    while ($prev !== $str) {
        $prev = $str;
        $str = preg_replace('/[\x{2018}\x{2019}\x{201C}\x{201D}\x{FF02}\x{FF07}]+$/u', '', $str);
    }

    return trim($str);
}

// ── BUILD CART (with add-ons carried over from cart.php selections) ───────
$cartRows = [];
$total    = 0;
$branch   = $_SESSION['branch'] ?? 'laguna';

foreach ($_SESSION['cart'] as $id => $qty) {
    $id  = (int)$id;
    $qty = (int)$qty;
    if ($qty <= 0) continue;

    $res = $conn->query("SELECT * FROM products WHERE id = $id LIMIT 1");
    if (!$res || !($row = $res->fetch_assoc())) continue;

    $qty = min($qty, (int)($row['stock'] ?? 100));
    if ($qty <= 0) continue;

    // Resolve saved add-ons for this item
    $savedAddonIds = json_decode($_SESSION['cart_addons'][$id] ?? '[]', true);
    if (!is_array($savedAddonIds)) $savedAddonIds = [];
    $addonNames = [];
    $addonUnitTotal = 0.0;
    if (!empty($savedAddonIds)) {
        $idList = implode(',', array_map('intval', $savedAddonIds));
        $aRes = $conn->query("SELECT name, price FROM product_addons WHERE id IN ($idList) AND is_active = 1");
        if ($aRes) {
            while ($a = $aRes->fetch_assoc()) {
                $addonNames[] = ['name' => cleanDisplayText($a['name']), 'price' => (float)$a['price']];
                $addonUnitTotal += (float)$a['price'];
            }
        }
    }

    $subtotal  = ($row['price'] + $addonUnitTotal) * $qty;
    $total    += $subtotal;
    $cartRows[] = array_merge($row, [
        'name'                  => cleanDisplayText($row['name']),
        'qty'                   => $qty,
        'subtotal'              => $subtotal,
        'addon_ids'             => $savedAddonIds,
        'addon_names'           => $addonNames,
        'addon_unit_total'      => $addonUnitTotal,
        'image_resolved'        => pickField($row, ['image_url', 'image', 'photo', 'thumbnail', 'img'], null),
        'ingredients_resolved'  => cleanDisplayText(pickField($row, ['ingredients', 'description', 'details'], 'Made with our house blend, fresh milk, and signature syrups — crafted fresh per order.')),
    ]);
}

if (empty($cartRows)) { header("Location: cart.php"); exit(); }

$error          = '';
$success        = false;
$successOrderId = null;
$successReceiptCode = null;
$successName    = '';
$paymentUsed    = 'cash';

// ── HANDLE SUBMIT ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {

    $notes         = $conn->real_escape_string(trim($_POST['notes'] ?? ''));
    $paymentMethod = $conn->real_escape_string(trim($_POST['payment'] ?? 'cash'));
    $paymentUsed   = trim($_POST['payment'] ?? 'cash');
    // Branch travels with the form as a hidden field so it reflects what the
    // customer was actually browsing, falling back to the session value.
    $branchEsc     = $conn->real_escape_string(trim($_POST['branch'] ?? $branch) ?: 'laguna');

    // Guest info
    $guestName  = null;
    $guestPhone = null;

    if (!$isLoggedIn) {
        $guestName  = $conn->real_escape_string(trim($_POST['guest_name'] ?? ''));
        $guestPhone = $conn->real_escape_string(trim($_POST['guest_phone'] ?? ''));
        if (empty($guestName)) {
            $error = 'Please enter your name.';
            goto skipOrder;
        }
        $userName    = htmlspecialchars($_POST['guest_name']);
        $successName = $userName;
    } else {
        $successName = $userName;
    }

    // Re-validate stock BEFORE opening the transaction so a shortage produces
    // a clean, readable error instead of a mid-transaction rollback surprise.
    foreach ($cartRows as $chk) {
        $pid = (int)$chk['id'];
        $sRes = $conn->query("SELECT stock FROM products WHERE id = $pid LIMIT 1");
        $sRow = $sRes ? $sRes->fetch_assoc() : null;
        if (!$sRow || (int)$sRow['stock'] < (int)$chk['qty']) {
            $error = 'Sorry, "' . htmlspecialchars($chk['name']) . '" no longer has enough stock. Please update your cart.';
            goto skipOrder;
        }
    }

    $conn->begin_transaction();
    try {
        $userIdSql      = $isLoggedIn ? $uid : 'NULL';
        $guestNameSql   = $guestName  ? "'$guestName'"  : 'NULL';
        $guestPhoneSql  = $guestPhone ? "'$guestPhone'" : 'NULL';

        $conn->query("INSERT INTO orders (user_id, total, notes, status, branch, payment_method, guest_name, guest_phone, created_at)
                      VALUES ($userIdSql, $total, '$notes', 'pending', '$branchEsc', '$paymentMethod', $guestNameSql, $guestPhoneSql, NOW())");
        $orderId = $conn->insert_id;
        if (!$orderId) throw new Exception("Could not create order. Please try again.");

        // Build a human-friendly receipt/reference code now that we have the
        // real order id: BRANCHPREFIX-YYYYMMDD-00000. Example: AYS-20260826-00019.
        // Branch prefix defaults to "AYS" (AyosCoffeeNegosyo) for unrecognized
        // branch values so the format never breaks even for new branches.
        $branchPrefixMap = ['laguna' => 'AYS', 'manila' => 'AYS', 'cavite' => 'AYS'];
        $branchPrefix    = $branchPrefixMap[$branchEsc] ?? 'AYS';
        $receiptCode     = $branchPrefix . '-' . date('Ymd') . '-' . str_pad($orderId, 5, '0', STR_PAD_LEFT);
        $receiptCodeEsc  = $conn->real_escape_string($receiptCode);
        $conn->query("UPDATE orders SET receipt_code = '$receiptCodeEsc' WHERE id = $orderId");

        foreach ($cartRows as $item) {
            $pid         = (int)$item['id'];
            $qty         = (int)$item['qty'];
            $price       = (float)$item['price'];
            $addonNamesFlat = array_column($item['addon_names'], 'name');
            $addonsJson  = $conn->real_escape_string(json_encode($addonNamesFlat));
            $addonsTotal = (float)$item['addon_unit_total'];

            $stockCheck = $conn->query("SELECT stock FROM products WHERE id = $pid FOR UPDATE");
            $stockRow   = $stockCheck ? $stockCheck->fetch_assoc() : null;
            if (!$stockRow || (int)$stockRow['stock'] < $qty) {
                throw new Exception("Sorry, not enough stock for: " . htmlspecialchars($item['name']));
            }

            $conn->query("INSERT INTO order_items (order_id, product_id, quantity, price, addons, addons_total)
                          VALUES ($orderId, $pid, $qty, $price, '$addonsJson', $addonsTotal)");
            $conn->query("UPDATE products SET stock = GREATEST(0, stock - $qty) WHERE id = $pid");
        }

        $conn->commit();
        unset($_SESSION['cart']);
        unset($_SESSION['cart_addons']);
        $success        = true;
        $successOrderId = $orderId;
        $successReceiptCode = $receiptCode;

    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }

    skipOrder:;
}

$itemCount = array_sum(array_column($cartRows, 'qty'));

// Data for the JS-driven brewing overlay: one entry per cart item, with the
// category so brew-icons.js can pick the right icon/label per item.
$brewQueue = array_map(function ($i) {
    return ['name' => $i['name'], 'category' => $i['category'] ?? 'mains'];
}, $cartRows);

// Data for the product-detail modal (used by both the success list and the
// receipt) — one entry per line item, keyed by index.
$detailQueue = array_map(function ($i) {
    return [
        'name'        => $i['name'],
        'image'       => $i['image_resolved'],
        'ingredients' => $i['ingredients_resolved'],
        'qty'         => (int)$i['qty'],
        'unit_price'  => (float)$i['price'],
        'addons'      => $i['addon_names'],
        'addon_total' => (float)$i['addon_unit_total'],
        'subtotal'    => (float)$i['subtotal'],
    ];
}, $cartRows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — AyosCoffeeNegosyo</title>
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
        .field-row{margin-bottom:18px}
        .field-row label{display:block;font-size:11px;font-weight:500;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted);margin-bottom:8px}
        .field-row input,.field-row textarea,.field-row select{width:100%;background:var(--surface);border:1px solid var(--border);border-radius:3px;color:var(--cream);font-family:'Jost',sans-serif;font-size:14px;padding:11px 14px;outline:none;transition:border-color 0.2s}
        .field-row input:focus,.field-row textarea:focus{border-color:var(--gold-dim)}
        .field-row input[readonly]{color:var(--muted);cursor:not-allowed}
        .field-row textarea{resize:vertical;min-height:80px}
        .pay-methods{display:flex;gap:10px;flex-wrap:wrap}
        .pay-method{flex:1;min-width:130px;background:var(--surface);border:1px solid var(--border);border-radius:3px;padding:14px 16px;cursor:pointer;display:flex;align-items:center;gap:10px;font-size:13px;font-weight:500;color:var(--muted);transition:all 0.2s;user-select:none}
        .pay-method input{display:none}
        .pay-method:has(input:checked){border-color:var(--gold-dim);background:rgba(201,168,76,0.06);color:var(--cream)}
        .pay-icon{width:28px;height:28px;background:var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--gold-dim)}
        .error-banner{background:rgba(139,46,46,0.15);border:1px solid var(--red);border-radius:3px;padding:12px 16px;color:#e07b7b;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:10px}

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
        .place-btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:15px;margin-top:22px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:#fff;cursor:pointer;transition:background 0.2s,transform 0.15s;position:relative;overflow:hidden}
        .place-btn:hover{background:var(--green-lt)}
        .place-btn:active{transform:scale(0.98)}
        .place-btn::after{content:'';position:absolute;top:0;left:-100%;width:60%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.1),transparent);transition:left 0.4s ease}
        .place-btn:hover::after{left:160%}
        .place-btn:disabled{opacity:0.6;cursor:not-allowed}
        .back-link{display:block;text-align:center;margin-top:14px;font-size:12.5px;color:var(--muted);text-decoration:none;letter-spacing:0.06em;transition:color 0.2s}
        .back-link:hover{color:var(--gold)}
        .summary-note{margin-top:20px;padding-top:16px;border-top:1px solid var(--border);font-size:11.5px;color:var(--muted);line-height:1.6;text-align:center}

        /* success */
        .success-wrap{position:relative;z-index:1;max-width:520px;margin:0 auto;text-align:center;padding:60px 32px 80px}
        .success-icon{width:88px;height:88px;margin:0 auto 28px;border:1px solid var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--green-lt);animation:popIn 0.5s cubic-bezier(0.34,1.56,0.64,1) both}
        @keyframes popIn{from{opacity:0;transform:scale(0.5)}to{opacity:1;transform:scale(1)}}
        .success-wrap h2{font-family:'Cormorant Garamond',serif;font-size:40px;font-weight:700;color:var(--cream);margin-bottom:12px}
        .success-wrap h2 em{font-style:italic;color:var(--gold)}
        .success-wrap p{font-size:14px;color:var(--muted);line-height:1.7;margin-bottom:8px}
        .order-badge{display:inline-flex;align-items:center;gap:8px;background:var(--card);border:1px solid var(--gold-dim);border-radius:3px;padding:8px 18px;font-size:13px;color:var(--gold);margin:18px 0 28px;letter-spacing:0.08em}
        .success-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
        .btn-primary{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;background:var(--green);border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:#fff;text-decoration:none;transition:background 0.2s;border:none;cursor:pointer}
        .btn-primary:hover{background:var(--green-lt)}
        .btn-ghost{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;border:1px solid var(--border);border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted);text-decoration:none;transition:all 0.2s;cursor:pointer;background:transparent}
        .btn-ghost:hover{border-color:var(--gold-dim);color:var(--gold)}

        .success-items{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:12px 16px;margin:0 auto 28px;max-width:420px;text-align:left}
        .success-item-row{display:flex;align-items:center;gap:12px;font-size:13px;color:var(--muted);padding:10px 4px;border-bottom:1px solid rgba(44,44,36,0.4);cursor:pointer;border-radius:3px;transition:background .2s}
        .success-item-row:hover{background:rgba(255,255,255,0.03)}
        .success-item-row:last-child{border-bottom:none}
        .success-item-row.grand-total{cursor:default}
        .success-item-row.grand-total:hover{background:transparent}
        .row-thumb{width:38px;height:38px;border-radius:50%;flex-shrink:0;object-fit:cover;border:1px solid var(--gold-dim);background:var(--surface)}
        .row-thumb-fallback{width:38px;height:38px;border-radius:50%;flex-shrink:0;border:1px solid var(--gold-dim);background:var(--surface);display:flex;align-items:center;justify-content:center;color:var(--gold-dim)}
        .row-text{flex:1;min-width:0}
        .row-text .sname{color:var(--cream);font-size:13.5px}
        .row-text .saddons{color:var(--gold-dim);font-size:11px;font-style:italic;margin-top:2px}
        .row-qty{color:var(--gold-dim);font-size:12px;flex-shrink:0}
        .row-price{color:var(--text);font-size:13.5px;flex-shrink:0;min-width:70px;text-align:right}
        .success-item-row.grand-total .row-text .sname{font-weight:600}
        .success-item-row.grand-total .row-price{color:var(--gold);font-family:'Cormorant Garamond',serif;font-size:19px}

        .success-hint{font-size:11px;color:var(--muted);margin:-14px auto 24px;letter-spacing:.04em}

        footer{position:relative;z-index:1;border-top:1px solid var(--border);padding:28px 32px;text-align:center}
        footer p{font-size:12px;color:var(--muted);letter-spacing:0.06em}
        footer p span{color:var(--gold-dim)}

        /* ══ 3D BREWING OVERLAY (multi-item, category-aware) ══ */
        #checkoutBrewOverlay{position:fixed;inset:0;z-index:500;display:flex;align-items:center;justify-content:center;background:rgba(6,6,5,0.92);backdrop-filter:blur(12px);opacity:0;pointer-events:none;transition:opacity 0.35s ease}
        #checkoutBrewOverlay.show{opacity:1;pointer-events:all}
        .brew-stage{text-align:center;max-width:300px;perspective:800px}
        .brew-ring-wrap{position:relative;width:140px;height:140px;margin:0 auto 30px;transform-style:preserve-3d;animation:brewTilt 4s ease-in-out infinite}
        @keyframes brewTilt{0%,100%{transform:rotateY(-6deg) rotateX(3deg)}50%{transform:rotateY(6deg) rotateX(-3deg)}}
        .brew-ring{position:absolute;inset:0;border-radius:50%;border:2px solid rgba(201,168,76,0.15);border-top-color:var(--gold);animation:brewspin 1.1s linear infinite}
        .brew-ring.r2{inset:14px;border-top-color:var(--green-lt);animation:brewspin 1.7s linear infinite reverse;opacity:0.6}
        @keyframes brewspin{to{transform:rotate(360deg)}}
        #brewSvg{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);animation:brewpulse 1.4s ease-in-out infinite;filter:drop-shadow(0 4px 12px rgba(201,168,76,0.35))}
        @keyframes brewpulse{0%,100%{transform:translate(-50%,-50%) scale(1)}50%{transform:translate(-50%,-50%) scale(1.12)}}
        #brewItemName{font-family:'Cormorant Garamond',serif;font-size:21px;font-weight:600;color:var(--cream)}
        #brewStepText{font-size:12.5px;color:var(--muted);margin-top:6px;letter-spacing:0.02em}
        #brewDots{display:flex;gap:6px;justify-content:center;margin-top:18px}
        .brew-dot{width:6px;height:6px;border-radius:50%;background:var(--border);transition:background 0.3s,transform 0.3s}
        .brew-dot.active{background:var(--gold);transform:scale(1.3)}

        /* ══ RECEIPT MODAL ══ */
        #receiptModal{position:fixed;inset:0;z-index:600;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.75);backdrop-filter:blur(6px);opacity:0;pointer-events:none;transition:opacity 0.3s ease}
        #receiptModal.show{opacity:1;pointer-events:all}
        .receipt-card{background:var(--card);border:1px solid var(--gold-dim);border-radius:8px;padding:32px;max-width:380px;width:92%;transform:translateY(20px) scale(0.98);transition:transform 0.35s cubic-bezier(0.34,1.56,0.64,1);position:relative;box-shadow:0 30px 80px rgba(0,0,0,0.6);max-height:86vh;overflow-y:auto}
        #receiptModal.show .receipt-card{transform:translateY(0) scale(1)}
        .receipt-close{position:absolute;top:12px;right:12px;width:28px;height:28px;border:1px solid var(--border);border-radius:50%;background:transparent;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;z-index:2}
        .receipt-close:hover{border-color:var(--gold-dim);color:var(--cream)}
        .receipt-head{text-align:center;margin-bottom:18px}
        .receipt-brand{font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--gold);letter-spacing:0.02em}
        .receipt-sub{font-size:10.5px;color:var(--muted);letter-spacing:0.14em;margin-top:4px;text-transform:uppercase}
        .receipt-body{border-top:1px dashed var(--border);border-bottom:1px dashed var(--border);padding:14px 0;margin-bottom:14px}
        .receipt-line-wrap{padding:6px 4px;border-radius:3px;cursor:pointer;transition:background .2s}
        .receipt-line-wrap:hover{background:rgba(255,255,255,0.04)}
        .receipt-line{display:flex;justify-content:space-between;font-size:12.5px;color:var(--text);padding:0}
        .receipt-line-addon{font-size:10.5px;color:var(--gold-dim);padding-left:10px;font-style:italic}
        .receipt-tap-note{font-size:9.5px;color:var(--muted);padding-left:10px;letter-spacing:.04em}
        .receipt-total{display:flex;justify-content:space-between;font-family:'Cormorant Garamond',serif;font-size:22px;color:var(--gold);font-weight:700}
        .receipt-foot{text-align:center;font-size:11px;color:var(--muted);margin-top:16px}

        /* ══ PRODUCT DETAIL MODAL (image · ingredients · add-ons · subtotal) ══ */
        #itemDetailModal{position:fixed;inset:0;z-index:700;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.8);backdrop-filter:blur(6px);opacity:0;pointer-events:none;transition:opacity 0.3s ease;padding:20px}
        #itemDetailModal.show{opacity:1;pointer-events:all}
        .detail-card{background:var(--card);border:1px solid var(--gold-dim);border-radius:8px;max-width:400px;width:100%;overflow:hidden;transform:translateY(20px) scale(0.97);transition:transform 0.35s cubic-bezier(0.34,1.56,0.64,1);position:relative;box-shadow:0 30px 80px rgba(0,0,0,0.65);max-height:88vh;display:flex;flex-direction:column}
        #itemDetailModal.show .detail-card{transform:translateY(0) scale(1)}
        .detail-close{position:absolute;top:12px;right:12px;width:30px;height:30px;border:1px solid rgba(240,234,216,.25);border-radius:50%;background:rgba(11,11,9,.55);backdrop-filter:blur(4px);color:var(--cream);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;z-index:2}
        .detail-close:hover{border-color:var(--gold);color:var(--gold)}
        .detail-media{width:100%;height:190px;background:var(--surface);position:relative;flex-shrink:0}
        .detail-media img{width:100%;height:100%;object-fit:cover;display:block}
        .detail-media-fallback{width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--gold-dim)}
        .detail-body{padding:22px 24px 26px;overflow-y:auto}
        .detail-name{font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700;color:var(--cream);margin-bottom:4px}
        .detail-qtyprice{font-size:12.5px;color:var(--gold-dim);margin-bottom:16px;letter-spacing:.03em}
        .detail-block-label{font-size:10px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);margin-bottom:6px;margin-top:16px}
        .detail-block-label:first-of-type{margin-top:0}
        .detail-ingredients{font-size:13px;color:var(--text);line-height:1.65}
        .detail-addon-row{display:flex;justify-content:space-between;font-size:12.5px;color:var(--text);padding:4px 0;border-bottom:1px solid rgba(44,44,36,.5)}
        .detail-addon-row:last-child{border-bottom:none}
        .detail-addon-empty{font-size:12px;color:var(--muted);font-style:italic}
        .detail-subtotal{display:flex;justify-content:space-between;align-items:center;margin-top:18px;padding-top:16px;border-top:1px solid var(--border)}
        .detail-subtotal .lbl{font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:var(--muted)}
        .detail-subtotal .amt{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--gold)}

        @media(max-width:768px){
            .checkout-layout{grid-template-columns:1fr;padding:0 16px 60px}
            .summary-card{position:static}
            .header-inner{padding:0 16px}
            nav a:not(.nav-back){display:none}
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
            <a href="profile.php">Profile</a>
            <a href="log-out.php">Logout</a>
            <a href="cart.php" class="nav-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back to Cart
            </a>
        </nav>
    </div>
</header>

<?php if ($success): ?>
<!-- ══ SUCCESS ══ -->
<div class="page-hero">
    <div class="hero-eyebrow">Thank you!</div>
    <h1>Order <em>Confirmed</em></h1>
</div>
<div class="success-wrap">
    <div class="success-icon">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <h2>Order <em>Placed!</em></h2>
    <p>
        <?php if ($isLoggedIn): ?>
            Thank you, <strong style="color:var(--gold)"><?= $successName ?></strong>! Your order has been received and is being prepared.
        <?php else: ?>
            Thank you, <strong style="color:var(--gold)"><?= $successName ?></strong>! Your guest order has been received.
        <?php endif; ?>
    </p>
    <?php if (!$isLoggedIn): ?>
    <div style="margin:12px 0;background:rgba(201,168,76,0.06);border:1px solid var(--gold-dim);border-radius:3px;padding:12px 16px;font-size:13px;color:var(--muted);line-height:1.6;max-width:400px;margin:14px auto">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2" style="vertical-align:-2px;margin-right:6px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <a href="register.php" style="color:var(--gold);text-decoration:none;font-weight:500">Create an account</a> to track your orders and get faster checkout next time!
    </div>
    <?php endif; ?>

    <div class="order-badge">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        <?= htmlspecialchars($successReceiptCode ?: ('Order #' . str_pad($successOrderId, 5, '0', STR_PAD_LEFT))) ?>
    </div>

    <div class="success-items" id="successItemsList"></div>
    <p class="success-hint">Tap any item to see its ingredients &amp; add-ons</p>

    <div class="success-actions">
        <button type="button" class="btn-primary" onclick="openReceipt()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/></svg>
            View Receipt
        </button>
        <a href="orders.php" class="btn-ghost">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            View My Orders
        </a>
        <a href="index.php" class="btn-ghost">Browse Menu</a>
    </div>
</div>

<!-- ══ RECEIPT MODAL ══ -->
<div id="receiptModal" onclick="if(event.target===this)closeReceipt()">
    <div class="receipt-card">
        <button class="receipt-close" onclick="closeReceipt()">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div class="receipt-head">
            <div class="receipt-brand">AyosCoffeeNegosyo</div>
            <div class="receipt-sub">Official Receipt · <?= htmlspecialchars($successReceiptCode ?: ('#' . str_pad($successOrderId, 5, '0', STR_PAD_LEFT))) ?></div>
            <div class="receipt-sub" style="margin-top:2px"><?= ucfirst($branch) ?> Branch · <?= date('M j, Y g:i A') ?></div>
        </div>
        <div class="receipt-body" id="receiptBody"></div>
        <div class="receipt-total">
            <span>Total</span><span>₱<?= number_format($total, 2) ?></span>
        </div>
        <div class="receipt-foot">Thank you for choosing us ☕<br>Paid via <?= ucfirst($paymentUsed) ?></div>
    </div>
</div>

<!-- ══ PRODUCT DETAIL MODAL (shared by success list + receipt) ══ -->
<div id="itemDetailModal" onclick="if(event.target===this)closeItemDetail()">
    <div class="detail-card">
        <button class="detail-close" onclick="closeItemDetail()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div class="detail-media" id="detailMedia"></div>
        <div class="detail-body">
            <div class="detail-name" id="detailName"></div>
            <div class="detail-qtyprice" id="detailQtyPrice"></div>

            <div class="detail-block-label">Ingredients</div>
            <div class="detail-ingredients" id="detailIngredients"></div>

            <div class="detail-block-label">Add-ons</div>
            <div id="detailAddons"></div>

            <div class="detail-subtotal">
                <span class="lbl">Subtotal</span>
                <span class="amt" id="detailSubtotal"></span>
            </div>
        </div>
    </div>
</div>

<script>
// One entry per order line — powers both the success list, the receipt, and
// the shared product-detail modal (image · ingredients · add-ons · subtotal).
const RECEIPT_ITEMS = <?= json_encode($detailQueue) ?>;

function fmtPeso(v){ return '₱' + Number(v).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2}); }

// Second-layer safety net: strips the same kind of stray leading/trailing
// quote-and-bracket junk (e.g. a literal  '">  saved into a product name)
// that cleanDisplayText() already handles server-side in PHP. Having it here
// too means the receipt/detail views stay clean even if a stale cached page,
// an old row, or a future code path ever skips the PHP-side cleanup.
function cleanName(s){
    if (s === null || s === undefined) return s;
    s = String(s);
    s = s.replace(/^[\s'"<>\u2018\u2019\u201C\u201D]+/, '');
    s = s.replace(/[\s'"<>\u2018\u2019\u201C\u201D]+$/, '');
    return s;
}

function fallbackThumbSvg(){
    return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/></svg>';
}

function renderRowThumb(item){
    if (item.image) {
        // NOTE: no inline onerror="..." attribute here on purpose — see
        // wireThumbFallbacks() below for why and how the fallback is attached.
        return '<img class="row-thumb" data-thumb-fallback src="' + item.image.replace(/"/g,'&quot;') + '" alt="">';
    }
    return '<div class="row-thumb-fallback">' + fallbackThumbSvg() + '</div>';
}

// Attaches broken-image fallback handling via a real JS event listener
// instead of an inline onerror="..." HTML attribute.
//
// WHY: the fallback SVG markup itself contains double-quoted attributes
// (width="16", height="16", d="M18 8h1a4...", etc). Trying to inline that
// whole SVG string inside an onerror="..." attribute — which is ALSO
// delimited by double quotes — meant the very first quote inside the SVG
// prematurely closed the onerror attribute. The browser's parser then choked
// on the leftover fragment, and the tail end of that fragment ('">) leaked
// out as a stray, literal text node right next to the image. That's exactly
// the '"> artifact that was showing up on every product card, regardless of
// what was actually stored in the database — this was a markup-construction
// bug, not corrupted data.
//
// Attaching the handler as a real DOM event listener sidesteps the whole
// nested-quoting problem: no HTML string ever needs to contain the SVG.
function wireThumbFallbacks(container){
    container.querySelectorAll('img[data-thumb-fallback]').forEach(function(img){
        img.addEventListener('error', function(){
            const fallback = document.createElement('div');
            fallback.className = 'row-thumb-fallback';
            fallback.innerHTML = fallbackThumbSvg();
            img.replaceWith(fallback);
        }, { once: true });
    });
}

function buildSuccessList(){
    const wrap = document.getElementById('successItemsList');
    if (!wrap) return;
    let html = '';
    RECEIPT_ITEMS.forEach(function(item, idx){
        const addonNote = item.addons.length ? item.addons.map(a => a.name).join(', ') : '';
        html += '<div class="success-item-row" onclick="openItemDetail(' + idx + ')">'
              +   renderRowThumb(item)
              +   '<div class="row-text"><div class="sname">' + escapeHtml(cleanName(item.name)) + '</div>'
              +     (addonNote ? '<div class="saddons">+ ' + escapeHtml(addonNote) + '</div>' : '')
              +   '</div>'
              +   '<div class="row-qty">×' + item.qty + '</div>'
              +   '<div class="row-price">' + fmtPeso(item.subtotal) + '</div>'
              + '</div>';
    });
    const grandTotal = RECEIPT_ITEMS.reduce((s, i) => s + i.subtotal, 0);
    html += '<div class="success-item-row grand-total"><div class="row-text"><div class="sname">Total</div></div><div class="row-price">' + fmtPeso(grandTotal) + '</div></div>';
    wrap.innerHTML = html;
    wireThumbFallbacks(wrap);
}

function buildReceiptBody(){
    const wrap = document.getElementById('receiptBody');
    if (!wrap) return;
    let html = '';
    RECEIPT_ITEMS.forEach(function(item, idx){
        const addonNote = item.addons.length ? item.addons.map(a => a.name).join(', ') : '';
        html += '<div class="receipt-line-wrap" onclick="openItemDetail(' + idx + ')">'
              +   '<div class="receipt-line"><span>' + escapeHtml(cleanName(item.name)) + ' ×' + item.qty + '</span><span>' + fmtPeso(item.subtotal) + '</span></div>'
              +   (addonNote ? '<div class="receipt-line-addon">+ ' + escapeHtml(addonNote) + '</div>' : '')
              +   '<div class="receipt-tap-note">Tap for details</div>'
              + '</div>';
    });
    wrap.innerHTML = html;
}

function escapeHtml(s){
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function openItemDetail(idx){
    const item = RECEIPT_ITEMS[idx];
    if (!item) return;

    const media = document.getElementById('detailMedia');
    if (item.image) {
        media.innerHTML = '<img data-thumb-fallback src="' + item.image.replace(/"/g,'&quot;') + '" alt="">';
        const detailImg = media.querySelector('img[data-thumb-fallback]');
        detailImg.addEventListener('error', function(){
            const fallback = document.createElement('div');
            fallback.className = 'detail-media-fallback';
            fallback.innerHTML = fallbackThumbSvg().replace('width="16" height="16"', 'width="34" height="34"');
            media.innerHTML = '';
            media.appendChild(fallback);
        }, { once: true });
    } else {
        media.innerHTML = '<div class="detail-media-fallback">' + fallbackThumbSvg().replace('width="16" height="16"','width="34" height="34"') + '</div>';
    }

    document.getElementById('detailName').textContent = cleanName(item.name);
    document.getElementById('detailQtyPrice').textContent = '×' + item.qty + ' · ' + fmtPeso(item.unit_price) + ' each';
    document.getElementById('detailIngredients').textContent = cleanName(item.ingredients) || 'Details for this item aren\'t available yet.';

    const addonsWrap = document.getElementById('detailAddons');
    if (item.addons.length) {
        addonsWrap.innerHTML = item.addons.map(a =>
            '<div class="detail-addon-row"><span>' + escapeHtml(a.name) + '</span><span>+' + fmtPeso(a.price) + '</span></div>'
        ).join('');
    } else {
        addonsWrap.innerHTML = '<div class="detail-addon-empty">No add-ons selected for this item.</div>';
    }

    document.getElementById('detailSubtotal').textContent = fmtPeso(item.subtotal);
    document.getElementById('itemDetailModal').classList.add('show');
}
function closeItemDetail(){ document.getElementById('itemDetailModal').classList.remove('show'); }

function openReceipt(){ buildReceiptBody(); document.getElementById('receiptModal').classList.add('show'); }
function closeReceipt(){ document.getElementById('receiptModal').classList.remove('show'); }

document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') { closeReceipt(); closeItemDetail(); }
});
document.addEventListener('DOMContentLoaded', buildSuccessList);
</script>

<?php else: ?>
<!-- ══ CHECKOUT FORM ══ -->
<div class="page-hero">
    <div class="hero-eyebrow">Almost there</div>
    <h1>Complete your <em>Order</em></h1>
</div>

<?php if ($error): ?>
<div style="max-width:1100px;margin:0 auto 24px;padding:0 32px">
    <div class="error-banner">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($error) ?>
    </div>
</div>
<?php endif; ?>

<form method="POST" action="checkout.php" id="checkoutForm">
<input type="hidden" name="branch" value="<?= htmlspecialchars($branch) ?>">
<!-- Always present regardless of how the form ends up being submitted (a
     programmatic form.submit() call does NOT send a submit button's own
     name/value — that's the bug that silently dropped every order before). -->
<input type="hidden" name="place_order" value="1">
<div class="checkout-layout">
    <div class="form-card">

        <div class="section-label">Customer Details</div>

        <?php if ($isLoggedIn): ?>
            <!-- Naka-login user -->
            <div class="field-row">
                <label>Name</label>
                <input type="text" value="<?= $userName ?>" readonly style="color:var(--green-lt)">
            </div>
            <div style="background:rgba(74,122,58,0.06);border:1px solid rgba(74,122,58,0.2);border-radius:3px;padding:10px 14px;font-size:12.5px;color:var(--green-lt);margin-bottom:18px;display:flex;align-items:center;gap:8px">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                Ordering as <strong style="margin-left:4px"><?= $userName ?></strong>
            </div>

        <?php else: ?>
            <!-- Guest -->
            <div style="background:rgba(201,168,76,0.06);border:1px solid var(--gold-dim);border-radius:3px;padding:10px 14px;font-size:12.5px;color:var(--muted);margin-bottom:18px;line-height:1.6">
                Ordering as guest. <a href="log-in.php" style="color:var(--gold);text-decoration:none;font-weight:500">Login</a> to track your orders.
            </div>
            <div class="field-row">
                <label>Your Name <span style="color:var(--red);margin-left:2px">*</span></label>
                <input type="text" name="guest_name" required maxlength="100" placeholder="Enter your full name" value="<?= htmlspecialchars($_POST['guest_name'] ?? '') ?>">
            </div>
            <div class="field-row">
                <label>Phone Number <span style="color:var(--muted);font-size:10px;text-transform:none;letter-spacing:0">(optional)</span></label>
                <input type="tel" name="guest_phone" placeholder="e.g. 09XX-XXX-XXXX" maxlength="20" value="<?= htmlspecialchars($_POST['guest_phone'] ?? '') ?>">
            </div>
        <?php endif; ?>

        <div class="section-label" style="margin-top:28px">Payment Method</div>
        <div class="pay-methods">
            <label class="pay-method">
                <input type="radio" name="payment" value="cash" checked>
                <div class="pay-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
                Cash on Pickup
            </label>
            <label class="pay-method">
                <input type="radio" name="payment" value="gcash">
                <div class="pay-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10H12V2z"/><path d="M12 2a10 10 0 0 1 10 10"/></svg></div>
                GCash
            </label>
            <label class="pay-method">
                <input type="radio" name="payment" value="card">
                <div class="pay-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
                Credit / Debit
            </label>
        </div>

        <div class="field-row" style="margin-top:24px">
            <label>Order Notes <span style="color:var(--muted);text-transform:none;letter-spacing:0">(optional)</span></label>
            <textarea name="notes" placeholder="Any special requests? E.g. less ice, extra sugar..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
        </div>
    </div>

    <!-- ORDER SUMMARY -->
    <div class="summary-card">
        <div class="summary-title">Order Summary</div>

        <?php foreach ($cartRows as $item): ?>
        <div class="order-item-row">
            <span class="name"><?= htmlspecialchars($item['name']) ?></span>
            <span class="qty">×<?= $item['qty'] ?></span>
            <span class="price">₱<?= number_format($item['subtotal'], 2) ?></span>
        </div>
        <?php if (!empty($item['addon_names'])): ?>
        <div class="order-item-addons">+ <?= htmlspecialchars(implode(', ', array_column($item['addon_names'], 'name'))) ?></div>
        <?php endif; ?>
        <?php endforeach; ?>

        <div class="divider"></div>
        <div class="summary-row">
            <span>Subtotal</span>
            <span class="val">₱<?= number_format($total, 2) ?></span>
        </div>
        <div class="summary-row">
            <span>Delivery fee</span>
            <span class="val" style="color:var(--green-lt);font-size:14px;">Free</span>
        </div>
        <div class="summary-row total">
            <span>Total</span>
            <span class="val">₱<?= number_format($total, 2) ?></span>
        </div>

        <button type="submit" name="place_order" value="1" class="place-btn" id="placeOrderBtn" onclick="return startBrewThenSubmit(event)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Place Order
        </button>
        <a href="cart.php" class="back-link">← Edit cart</a>
        <p class="summary-note">🔒 Secure checkout &nbsp;·&nbsp; Orders prepared fresh upon confirmation.</p>
    </div>
</div>
</form>

<!-- ══ 3D BREWING OVERLAY ══ -->
<div id="checkoutBrewOverlay">
    <div class="brew-stage">
        <div class="brew-ring-wrap">
            <div class="brew-ring"></div>
            <div class="brew-ring r2"></div>
            <svg id="brewSvg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.6"></svg>
        </div>
        <div id="brewItemName">Preparing your order…</div>
        <div id="brewStepText"></div>
        <div id="brewDots"></div>
    </div>
</div>

<script src="brew-icons.js"></script>
<script>
// One entry per cart item: { name, category } — drives the sequential brew animation
const CHECKOUT_ITEMS = <?= json_encode($brewQueue) ?>;
let brewSubmitting = false;

// Returning false from the button's onclick would block the native form
// submit entirely, so instead we intercept once, run the animation, then
// programmatically submit the real form — the button stays type="submit"
// so the flow still works even if this script fails to load.
function startBrewThenSubmit(evt){
    if (brewSubmitting) return true;      // second click after animation: let it through
    if (!CHECKOUT_ITEMS.length || typeof BREW_ICON_PATHS === 'undefined') return true;

    evt.preventDefault();
    brewSubmitting = true;

    const form = document.getElementById('checkoutForm');
    const btn  = document.getElementById('placeOrderBtn');
    btn.disabled = true;

    const overlay = document.getElementById('checkoutBrewOverlay');
    overlay.classList.add('show');

    const svg    = document.getElementById('brewSvg');
    const nameEl = document.getElementById('brewItemName');
    const stepEl = document.getElementById('brewStepText');
    const dotsWrap = document.getElementById('brewDots');
    dotsWrap.innerHTML = CHECKOUT_ITEMS.map(function(){ return '<span class="brew-dot"></span>'; }).join('');
    const dots = [].slice.call(dotsWrap.children);

    // Local safety-net cleanup (mirrors cleanName() on the success page) —
    // this script block runs on the checkout FORM page, a separate <script>
    // scope from the success page, so it needs its own copy.
    function cleanBrewName(s){
        if (s === null || s === undefined) return s;
        s = String(s);
        s = s.replace(/^[\s'"<>\u2018\u2019\u201C\u201D]+/, '');
        s = s.replace(/[\s'"<>\u2018\u2019\u201C\u201D]+$/, '');
        return s;
    }

    function renderStep(idx){
        const item = CHECKOUT_ITEMS[idx];
        const icon = iconFor(item.category);
        svg.innerHTML = BREW_ICON_PATHS[icon] || '';
        nameEl.textContent = cleanBrewName(item.name);
        stepEl.textContent = BREW_LABELS[icon] || 'Preparing…';
        dots.forEach(function(d, di){ d.classList.toggle('active', di <= idx); });
    }
    renderStep(0);

    const perItemMs = Math.max(700, Math.min(1200, 3200 / CHECKOUT_ITEMS.length));
    let i = 0;
    const iv = setInterval(function(){
        i++;
        if (i >= CHECKOUT_ITEMS.length) { clearInterval(iv); return; }
        renderStep(i);
    }, perItemMs);

    const totalMs = perItemMs * CHECKOUT_ITEMS.length + 500;
    setTimeout(function(){
        clearInterval(iv);
        form.submit();
    }, totalMs);

    return false;
}
</script>
<?php endif; ?>

<footer><p>© 2026 <span>My AyosCoffeeNegosyo</span> — All rights reserved.</p></footer>
</body>
</html>