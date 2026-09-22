<?php
include_once 'hotel-config.php';
$activePage = '';
requireHotelLogin('dashboard.php');

$userId = $_SESSION['user']['id'];
$today  = date('Y-m-d');
$notice = $_SESSION['dash_notice'] ?? null;
$error  = $_SESSION['dash_error'] ?? null;
unset($_SESSION['dash_notice'], $_SESSION['dash_error']);

$tier = currentTier();

// ── Guest profile (name, tier, loyalty points) straight from the DB ─────
$stmt = $conn->prepare("SELECT full_name, email, tier, loyalty_points FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc() ?: ['full_name' => $_SESSION['user']['name'] ?? 'Guest', 'email' => '', 'tier' => $tier, 'loyalty_points' => 0];

$initials = '';
foreach (preg_split('/\s+/', trim($profile['full_name'])) as $part) {
    if ($part !== '') $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    if (mb_strlen($initials) >= 2) break;
}

// ── VIP threshold, for the progress bar ──────────────────────────────
$vipRow = $conn->query("SELECT min_lifetime_spend FROM membership_tiers WHERE tier = 'vip' LIMIT 1")->fetch_assoc();
$vipThreshold = $vipRow ? (float)$vipRow['min_lifetime_spend'] : 150000.00;

// ── Fetch this guest's bookings, with suite + time slot info ────────────
$stmt = $conn->prepare("SELECT b.*, s.name AS suite_name, s.slug, s.cover_image, ts.label AS slot_label
                         FROM bookings b
                         JOIN suites s ON s.id = b.suite_id
                         JOIN time_slots ts ON ts.id = b.time_slot_id
                         WHERE b.user_id = ?
                         ORDER BY b.checkin_date DESC, b.id DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Lifetime stats (non-cancelled bookings only) ─────────────────────
$lifetimeSpend = 0.00;
$totalNights   = 0;
$completedStays = 0;
foreach ($bookings as $b) {
    if ($b['status'] === 'cancelled') continue;
    $lifetimeSpend += (float)$b['total_price'];
    $totalNights   += (int)$b['nights'];
    if ($b['status'] === 'checked_out') $completedStays++;
}
$vipProgressPct = $tier === 'vip' ? 100 : min(100, round(($lifetimeSpend / max($vipThreshold, 1)) * 100));

// ── Attach room numbers + gallery to each booking ────────────────────
foreach ($bookings as &$bk) {
    $bk['room_numbers'] = [];
    $rr = $conn->query("SELECT r.room_number FROM booking_rooms br
                         JOIN rooms r ON r.id = br.room_id
                         WHERE br.booking_id = " . (int)$bk['id'] . " ORDER BY r.room_number ASC");
    if ($rr) { while ($row = $rr->fetch_assoc()) $bk['room_numbers'][] = $row['room_number']; }
    $bk['is_active']  = in_array($bk['status'], ['pending','confirmed','checked_in']) && $bk['checkout_date'] >= $today;
    $bk['is_past']    = !$bk['is_active'];

    // Every dining / drinks / champagne order placed for this stay — fetched
    // for ALL bookings (not just active ones) since past-stay receipts need it too
    $bk['dining_orders'] = [];
    $dr = $conn->query("SELECT so.scheduled_date, so.quantity, so.total_price, s.name, s.type
                         FROM service_orders so JOIN services s ON s.id = so.service_id
                         WHERE so.booking_id = " . (int)$bk['id'] . "
                         ORDER BY so.scheduled_date ASC, s.type ASC");
    if ($dr) { while ($row = $dr->fetch_assoc()) $bk['dining_orders'][] = $row; }
}
unset($bk);

$active = array_filter($bookings, fn($b) => $b['is_active']);
$past   = array_filter($bookings, fn($b) => $b['is_past']);

// ── Pre-built receipt data for every checked-out stay, embedded below as
//    JSON so "View Receipt" reads it instantly — no fetch, no get-receipt.php,
//    no possible network/parse errors. ──────────────────────────────────
$pastReceipts = [];
foreach ($past as $bk) {
    if ($bk['status'] !== 'checked_out') continue;
    $diningTotal = 0.00;
    foreach ($bk['dining_orders'] as $do) $diningTotal += (float)$do['total_price'];
    $roomTotal = (float)$bk['total_price'];
    $pastReceipts[$bk['id']] = [
        'booking_ref'     => $bk['booking_ref'],
        'guest_name'      => $bk['guest_name'],
        'guest_tier'      => $bk['tier_applied'],
        'suite_name'      => $bk['suite_name'],
        'room_numbers'    => $bk['room_numbers'],
        'checkin_date'    => $bk['checkin_date'],
        'checkout_date'   => $bk['checkout_date'],
        'nights'          => (int)$bk['nights'],
        'nightly_rate'    => (float)$bk['nightly_rate'],
        'quantity'        => (int)$bk['quantity'],
        'subtotal'        => (float)$bk['subtotal'],
        'discount_amount' => (float)$bk['discount_amount'],
        'room_total'      => $roomTotal,
        'dining_items'    => $bk['dining_orders'],
        'dining_total'    => round($diningTotal, 2),
        'grand_total'     => round($roomTotal + $diningTotal, 2),
    ];
}
$pastReceiptsJson = json_encode($pastReceipts);

// ── Dining menu (only needed if there's at least one active stay) ───────
$diningMenu = ['breakfast'=>[], 'lunch'=>[], 'dinner'=>[], 'drink'=>[], 'champagne'=>[]];
if (!empty($active)) {
    $mr = $conn->query("SELECT * FROM services WHERE category = 'dining' AND active = 1 ORDER BY type ASC, id ASC");
    if ($mr) {
        while ($row = $mr->fetch_assoc()) {
            $row['price'] = $tier === 'vip' ? (float)$row['vip_price'] : (float)$row['base_price'];
            if (isset($diningMenu[$row['type']])) $diningMenu[$row['type']][] = $row;
        }
    }
}
$diningMenuJson = json_encode($diningMenu);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Bookings — Nocturne Manila Bay</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="hotel-theme.css">
<style>
/* PAGE-SPECIFIC: dashboard */
.dash-wrap{position:relative;z-index:1;max-width:980px;margin:0 auto;padding:50px 24px 120px}
.dash-notice{padding:13px 16px;border-radius:4px;font-size:12.5px;margin-bottom:24px}
.dash-notice.ok{background:rgba(127,168,118,0.1);border:1px solid rgba(127,168,118,0.35);color:#c7e0c2}
.dash-notice.err{background:rgba(192,87,74,0.1);border:1px solid rgba(192,87,74,0.35);color:#f0c9c2}
.dash-section-label{font-size:11px;letter-spacing:0.2em;text-transform:uppercase;color:var(--h-gold);margin:34px 0 16px}
.dash-empty{color:var(--h-muted);font-size:13px;border:1px dashed var(--h-line);border-radius:4px;padding:26px;text-align:center}

/* ── Fade-in animation used across the page ── */
@keyframes riseIn{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.rise{animation:riseIn 0.55s cubic-bezier(.2,.7,.3,1) both}

/* ── Smooth hover animations (buttons, cards, tabs) ── */
.btn-gold, .btn-ghost{transition:transform 0.25s cubic-bezier(.2,.7,.3,1), box-shadow 0.25s ease, background-color 0.25s ease, color 0.25s ease, opacity 0.25s ease}
.btn-gold:hover, .btn-ghost:hover{transform:translateY(-2px)}
.btn-gold:hover{box-shadow:0 10px 22px rgba(207,167,107,0.25)}
.btn-ghost:hover{box-shadow:0 8px 18px rgba(255,255,255,0.06)}
.btn-gold:active, .btn-ghost:active{transform:translateY(0)}
.btn-gold:disabled, .btn-ghost:disabled{opacity:0.6;cursor:default;transform:none}

.booking-card{transition:box-shadow 0.35s ease, transform 0.35s ease}
.booking-card:hover{box-shadow:0 16px 36px rgba(0,0,0,0.35);transform:translateY(-3px)}

.dining-tab-btn{transition:color 0.22s ease, border-color 0.22s ease, background-color 0.22s ease}
.dining-tab-btn:hover{color:var(--h-champagne);border-color:var(--h-gold-dim)}

.qty-control button{transition:background-color 0.2s ease, color 0.2s ease}
.qty-control button:hover{background-color:rgba(207,167,107,0.14)}

.bc-room-pill{transition:border-color 0.2s ease, color 0.2s ease}
.bc-room-pill:hover{border-color:var(--h-gold-dim);color:var(--h-champagne)}

.dining-toggle{transition:color 0.22s ease}
.dining-toggle:hover{color:var(--h-champagne)}

.dining-pager button{transition:background-color 0.2s ease, transform 0.2s ease, color 0.2s ease}
.dining-pager button:hover:not(:disabled){background-color:rgba(207,167,107,0.12);transform:translateY(-1px)}

/* ── Profile / overview panel ── */
.profile-panel{background:linear-gradient(160deg,rgba(207,167,107,0.08),var(--h-card) 55%);border:1px solid var(--h-line);border-radius:6px;padding:28px 30px;display:flex;flex-wrap:wrap;gap:26px;align-items:center;justify-content:space-between}
.profile-id{display:flex;align-items:center;gap:16px;min-width:230px}
.profile-avatar{width:58px;height:58px;border-radius:50%;background:rgba(207,167,107,0.14);border:1px solid var(--h-gold-dim);display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-size:22px;color:var(--h-gold);flex-shrink:0}
.profile-name{font-family:'Cormorant Garamond',serif;font-size:22px;color:var(--h-champagne)}
.profile-tier{font-size:10.5px;letter-spacing:0.12em;text-transform:uppercase;margin-top:3px;display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:100px}
.profile-tier.vip{background:rgba(201,165,74,0.14);color:var(--h-vip);border:1px solid rgba(201,165,74,0.35)}
.profile-tier.regular{background:rgba(255,255,255,0.05);color:var(--h-muted);border:1px solid var(--h-line)}

.stat-row{display:grid;grid-template-columns:repeat(3,minmax(96px,1fr));gap:22px}
.stat-item{text-align:center}
.stat-item .num{font-family:'Cormorant Garamond',serif;font-size:26px;color:var(--h-gold);font-weight:700;line-height:1}
.stat-item .lbl{font-size:9.5px;letter-spacing:0.1em;text-transform:uppercase;color:var(--h-muted);margin-top:6px}

.vip-track{width:100%;margin-top:20px;padding-top:18px;border-top:1px solid var(--h-line)}
.vip-track .vt-top{display:flex;justify-content:space-between;font-size:11px;color:var(--h-muted);margin-bottom:8px}
.vip-track .vt-top b{color:var(--h-gold)}
.vip-bar{height:6px;border-radius:100px;background:rgba(255,255,255,0.06);overflow:hidden}
.vip-bar-fill{height:100%;border-radius:100px;background:linear-gradient(90deg,var(--h-gold-dim),var(--h-gold));width:0%;transition:width 1.1s cubic-bezier(.2,.7,.3,1)}

/* ── Booking card ── */
.booking-card{background:var(--h-card);border:1px solid var(--h-line);border-radius:4px;overflow:hidden;margin-bottom:20px}
.bc-photo{width:100%;height:190px;position:relative;overflow:hidden}
.bc-photo img{width:100%;height:100%;object-fit:cover;transform:scale(1.02);transition:transform 6s ease}
.booking-card:hover .bc-photo img{transform:scale(1.1)}
.bc-photo::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(4,4,6,0) 40%,rgba(4,4,6,0.85) 100%)}
.bc-photo-label{position:absolute;left:18px;bottom:12px;z-index:1;font-family:'Cormorant Garamond',serif;font-size:19px;color:var(--h-champagne)}

.bc-top{display:flex;justify-content:space-between;align-items:center;padding:18px 22px;border-bottom:1px solid var(--h-line);flex-wrap:wrap;gap:10px}
.bc-code-label{font-size:9.5px;letter-spacing:0.14em;text-transform:uppercase;color:var(--h-gold-dim)}
.bc-code{font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--h-gold);letter-spacing:0.05em}
.bc-status{font-size:10px;letter-spacing:0.1em;text-transform:uppercase;padding:5px 12px;border-radius:100px}
.bc-status.confirmed,.bc-status.pending{background:rgba(127,168,118,0.12);color:#7fa876;border:1px solid rgba(127,168,118,0.3)}
.bc-status.checked_in{background:rgba(201,165,74,0.12);color:var(--h-vip);border:1px solid rgba(201,165,74,0.3)}
.bc-status.checked_out{background:rgba(255,255,255,0.05);color:var(--h-muted);border:1px solid var(--h-line)}
.bc-status.cancelled{background:rgba(192,87,74,0.1);color:var(--h-bad);border:1px solid rgba(192,87,74,0.3)}
.bc-body{padding:20px 22px;display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:16px}
.bc-field .label{font-size:9.5px;letter-spacing:0.12em;text-transform:uppercase;color:var(--h-muted);margin-bottom:5px}
.bc-field .val{font-size:14px;color:var(--h-champagne);font-family:'Cormorant Garamond',serif}
.bc-rooms{display:flex;gap:6px;flex-wrap:wrap}
.bc-room-pill{background:var(--h-surface);border:1px solid var(--h-line);border-radius:100px;padding:3px 11px;font-size:12px;color:var(--h-text)}
.bc-actions{padding:16px 22px;border-top:1px solid var(--h-line);display:flex;gap:10px;flex-wrap:wrap;align-items:center}

.extend-box{display:none;padding:18px 22px;border-top:1px solid var(--h-line);background:var(--h-surface)}
.extend-box.open{display:block}
.extend-row{display:flex;align-items:flex-end;gap:18px;flex-wrap:wrap}
.extend-field{display:flex;flex-direction:column;gap:7px;min-width:150px}
.extend-label{font-size:9.5px;letter-spacing:0.12em;text-transform:uppercase;color:var(--h-muted)}
.extend-cost{font-size:12.5px;color:var(--h-muted)}
.extend-cost b{color:var(--h-gold);font-family:'Cormorant Garamond',serif;font-size:17px}

/* ── Dining panel ── */
.dining-panel{border-top:1px solid var(--h-line);background:var(--h-surface)}
.dining-toggle{width:100%;text-align:left;background:none;border:none;color:var(--h-gold);font-size:12px;letter-spacing:0.08em;text-transform:uppercase;padding:14px 22px;cursor:pointer;display:flex;justify-content:space-between;align-items:center}
.dining-toggle .chev{transition:transform 0.3s cubic-bezier(.2,.7,.3,1)}
.dining-toggle.open .chev{transform:rotate(180deg)}
/* Smooth expand/collapse: outer element only clips + animates max-height;
   the inner wrapper carries the padding so it doesn't create a gap when closed. */
.dining-body{max-height:0;overflow:hidden;transition:max-height 0.45s cubic-bezier(.4,0,.2,1)}
.dining-body-inner{padding:4px 22px 22px}
.dining-existing{margin-bottom:16px;display:flex;flex-direction:column;gap:6px}
.dining-existing-row{font-size:12px;color:var(--h-muted);display:flex;justify-content:space-between;border-bottom:1px dashed var(--h-line);padding-bottom:5px}
.dining-existing-row b{color:var(--h-champagne);font-weight:500}
.dining-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px}
.dining-tab-btn{background:var(--h-card);border:1px solid var(--h-line);border-radius:100px;padding:6px 14px;font-size:11px;letter-spacing:0.05em;text-transform:uppercase;color:var(--h-muted);cursor:pointer}
.dining-tab-btn.active{border-color:var(--h-gold);color:var(--h-gold);background:rgba(207,167,107,0.08)}
.dining-tab-panel{display:none}
.dining-tab-panel.active{display:block}
.dining-item-list{min-height:40px}
.dining-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid var(--h-line)}
.dining-item:last-child{border-bottom:none}
.dining-item-name{font-size:13px;color:var(--h-text)}
.dining-item-desc{font-size:11px;color:var(--h-muted);margin-top:2px}
.dining-item-price{font-size:11px;color:var(--h-gold);margin-top:2px}
.qty-control{display:flex;align-items:center;border:1px solid var(--h-line);border-radius:3px;overflow:hidden;flex-shrink:0}
.qty-control button{width:26px;height:26px;background:var(--h-card);border:none;color:var(--h-gold);cursor:pointer;font-size:14px}
.qty-control input{width:32px;text-align:center;background:none;border:none;color:var(--h-champagne);font-size:12.5px}
.dining-pager{display:flex;align-items:center;justify-content:center;gap:14px;margin-top:12px;padding-top:12px;border-top:1px solid var(--h-line)}
.dining-pager button{background:var(--h-card);border:1px solid var(--h-line);border-radius:100px;padding:5px 14px;font-size:11px;letter-spacing:0.05em;text-transform:uppercase;color:var(--h-gold);cursor:pointer}
.dining-pager button:disabled{opacity:0.35;cursor:not-allowed}
.dining-pager .pager-info{font-size:11px;color:var(--h-muted)}
.dining-footer{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:16px}
.dining-date{font-size:11.5px;color:var(--h-muted);display:flex;align-items:center;gap:8px}
.dining-date input{background:var(--h-card);border:1px solid var(--h-line);border-radius:3px;color:var(--h-text);padding:5px 8px;font-size:12px}
.dining-total{font-size:12.5px;color:var(--h-muted)}
.dining-total b{color:var(--h-gold);font-family:'Cormorant Garamond',serif;font-size:16px}
.dining-msg{font-size:11.5px;margin-top:10px;display:none;padding:8px 10px;border-radius:3px}
.dining-msg.ok{display:block;background:rgba(127,168,118,0.1);border:1px solid rgba(127,168,118,0.3);color:#c7e0c2}
.dining-msg.error{display:block;background:rgba(192,87,74,0.1);border:1px solid rgba(192,87,74,0.3);color:#f0c9c2}

/* ── Receipt modal (Art Deco card) ── */
.receipt-overlay{position:fixed;inset:0;background:rgba(4,4,6,0.8);backdrop-filter:blur(3px);display:flex;align-items:center;justify-content:center;padding:24px;z-index:999;opacity:0;pointer-events:none;transition:opacity 0.3s ease}
.receipt-overlay.open{opacity:1;pointer-events:auto}
.receipt-box{position:relative;background:linear-gradient(165deg,rgba(207,167,107,0.06),var(--h-card) 60%);border:1px solid var(--h-gold-dim);border-radius:2px;max-width:480px;width:100%;max-height:85vh;overflow-y:auto;padding:44px 38px 36px;transform:scale(0.94) translateY(10px);opacity:0;transition:transform 0.35s cubic-bezier(.2,.7,.3,1), opacity 0.35s ease}
.receipt-overlay.open .receipt-box{transform:scale(1) translateY(0);opacity:1}
.receipt-corner{position:absolute;width:20px;height:20px;pointer-events:none}
.receipt-corner.tl{top:9px;left:9px;border-top:1.5px solid var(--h-gold);border-left:1.5px solid var(--h-gold)}
.receipt-corner.tr{top:9px;right:9px;border-top:1.5px solid var(--h-gold);border-right:1.5px solid var(--h-gold)}
.receipt-corner.bl{bottom:9px;left:9px;border-bottom:1.5px solid var(--h-gold);border-left:1.5px solid var(--h-gold)}
.receipt-corner.br{bottom:9px;right:9px;border-bottom:1.5px solid var(--h-gold);border-right:1.5px solid var(--h-gold)}
.receipt-close-x{position:absolute;top:14px;right:16px;background:none;border:none;color:var(--h-muted);font-size:20px;line-height:1;cursor:pointer;transition:color 0.2s ease;z-index:2}
.receipt-close-x:hover{color:var(--h-gold)}

.receipt-head{text-align:center;margin-bottom:6px}
.receipt-head .eyebrow{font-size:9.5px;letter-spacing:0.35em;text-transform:uppercase;color:var(--h-gold-dim)}
.receipt-head h2{font-family:'Cormorant Garamond',serif;color:var(--h-champagne);font-size:26px;letter-spacing:0.06em;margin:6px 0 4px}
.receipt-head .sub{font-size:10.5px;letter-spacing:0.18em;text-transform:uppercase;color:var(--h-gold)}

.receipt-divider{display:flex;align-items:center;gap:10px;margin:18px 0}
.receipt-divider::before,.receipt-divider::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,transparent,var(--h-gold-dim),var(--h-gold),var(--h-gold-dim),transparent)}
.receipt-diamond{width:7px;height:7px;background:var(--h-gold);transform:rotate(45deg);flex-shrink:0}

.receipt-row{display:flex;justify-content:space-between;align-items:baseline;gap:14px;padding:5px 0}
.receipt-row .lbl{font-size:9.5px;letter-spacing:0.1em;text-transform:uppercase;color:var(--h-muted);white-space:nowrap}
.receipt-row .val{font-family:'Cormorant Garamond',serif;font-size:15.5px;color:var(--h-champagne);text-align:right}
.receipt-tier-badge{font-size:9px;letter-spacing:0.1em;text-transform:uppercase;padding:2px 9px;border-radius:100px;margin-left:8px;display:inline-block;vertical-align:middle}
.receipt-tier-badge.vip{background:rgba(201,165,74,0.14);color:var(--h-vip);border:1px solid rgba(201,165,74,0.35)}
.receipt-tier-badge.regular{background:rgba(255,255,255,0.05);color:var(--h-muted);border:1px solid var(--h-line)}

.receipt-section-title{text-align:center;font-size:10px;letter-spacing:0.22em;text-transform:uppercase;color:var(--h-gold-dim);margin:4px 0 6px}
.receipt-dining-row{display:flex;justify-content:space-between;gap:10px;font-size:12.5px;color:var(--h-text);padding:6px 0;border-bottom:1px dotted var(--h-line)}
.receipt-dining-row:last-child{border-bottom:none}
.receipt-dining-row .rd-name{color:var(--h-champagne)}
.receipt-dining-row .rd-sub{font-size:10.5px;color:var(--h-muted);display:block}
.receipt-empty-note{font-size:11.5px;color:var(--h-muted);font-style:italic;text-align:center;padding:6px 0}

.receipt-totals .rt-row{display:flex;justify-content:space-between;font-size:11.5px;color:var(--h-muted);padding:3px 0;letter-spacing:0.04em}
.receipt-totals .rt-row.grand{margin-top:10px;padding-top:12px;border-top:1px double var(--h-gold-dim);font-size:20px;color:var(--h-gold);font-family:'Cormorant Garamond',serif;letter-spacing:0.03em}

.receipt-actions{display:flex;gap:10px;justify-content:center;margin-top:26px}
@media print{
    body *{visibility:hidden}
    .receipt-box, .receipt-box *{visibility:visible}
    .receipt-box{position:fixed;inset:0;max-height:none;max-width:none;box-shadow:none;border:none}
    .receipt-actions, .receipt-close-x{display:none}
}
</style>
</head>
<body>
<?php include_once 'sidebar.php'; ?>
<?php include_once 'topheader.php'; ?>

<section class="page-hero">
    <div class="sec-eyebrow">Your Account</div>
    <h1>My <em>Bookings</em></h1>
    <p>Your room access code, room number, and check-in details — extend your stay, order in-room dining, or check out from here.</p>
</section>

<div class="dash-wrap">
    <?php if ($notice): ?><div class="dash-notice ok"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="dash-notice err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="profile-panel rise">
        <div class="profile-id">
            <div class="profile-avatar"><?= htmlspecialchars($initials ?: 'G') ?></div>
            <div>
                <div class="profile-name">Welcome, <?= htmlspecialchars($profile['full_name']) ?></div>
                <span class="profile-tier <?= $tier ?>"><?= $tier === 'vip' ? 'Nocturne Noir · VIP' : 'Nocturne Guest' ?></span>
            </div>
        </div>
        <div class="stat-row">
            <div class="stat-item"><div class="num" data-count="<?= (int)round($lifetimeSpend) ?>" data-prefix="₱">0</div><div class="lbl">Lifetime Spend</div></div>
            <div class="stat-item"><div class="num" data-count="<?= (int)$completedStays ?>">0</div><div class="lbl">Completed Stays</div></div>
            <div class="stat-item"><div class="num" data-count="<?= (int)($profile['loyalty_points']) ?>">0</div><div class="lbl">Loyalty Points</div></div>
        </div>
        <?php if ($tier !== 'vip'): ?>
        <div class="vip-track">
            <div class="vt-top"><span>Progress to Nocturne Noir (VIP)</span><b>₱<?= number_format($lifetimeSpend) ?> / ₱<?= number_format($vipThreshold) ?></b></div>
            <div class="vip-bar"><div class="vip-bar-fill" data-vip-fill="<?= $vipProgressPct ?>"></div></div>
        </div>
        <?php else: ?>
        <div class="vip-track">
            <div class="vt-top"><span>You're enjoying Nocturne Noir rates, priority check-in, and complimentary transfers.</span></div>
        </div>
        <?php endif; ?>
    </div>

    <div class="dash-section-label" style="display:flex;justify-content:space-between;align-items:center">
        <span>Current &amp; Upcoming Stays</span>
        <?php if (!empty($active)): ?><a href="my-room.php" style="font-size:11px;letter-spacing:0.08em;text-transform:none;color:var(--h-gold)">View My Room &amp; Timer →</a><?php endif; ?>
    </div>
    <?php if (empty($active)): ?>
        <div class="dash-empty">No active reservations yet. <a href="hotel-reserve.php" style="color:var(--h-gold)">Reserve a residence →</a></div>
    <?php else: foreach ($active as $bk): ?>
    <div class="booking-card rise">
        <div class="bc-photo">
            <img src="<?= htmlspecialchars($bk['cover_image']) ?>" alt="<?= htmlspecialchars($bk['suite_name']) ?>" loading="lazy">
            <div class="bc-photo-label"><?= htmlspecialchars($bk['suite_name']) ?></div>
        </div>
        <div class="bc-top">
            <div>
                <div class="bc-code-label">Room Access Code</div>
                <div class="bc-code"><?= htmlspecialchars($bk['booking_ref']) ?></div>
            </div>
            <span class="bc-status <?= $bk['status'] ?>"><?= ucwords(str_replace('_',' ',$bk['status'])) ?></span>
        </div>
        <div class="bc-body">
            <div class="bc-field"><div class="label">Residence</div><div class="val"><?= htmlspecialchars($bk['suite_name']) ?></div></div>
            <div class="bc-field">
                <div class="label">Room<?= count($bk['room_numbers'])>1?'s':'' ?></div>
                <div class="bc-rooms">
                    <?php if ($bk['room_numbers']): foreach ($bk['room_numbers'] as $rn): ?>
                        <span class="bc-room-pill"><?= htmlspecialchars($rn) ?></span>
                    <?php endforeach; else: ?>
                        <span class="bc-room-pill"><?= (int)$bk['quantity'] ?> room<?= $bk['quantity']>1?'s':'' ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="bc-field"><div class="label">Checked In</div><div class="val"><?= date('M j, Y', strtotime($bk['checkin_date'])) ?></div></div>
            <div class="bc-field"><div class="label">Check-out</div><div class="val"><?= date('M j, Y', strtotime($bk['checkout_date'])) ?></div></div>
            <div class="bc-field"><div class="label">Nightly Rate</div><div class="val">₱<?= number_format($bk['nightly_rate'],2) ?></div></div>
            <div class="bc-field"><div class="label">Total Paid</div><div class="val">₱<?= number_format($bk['total_price'],2) ?></div></div>
        </div>
        <div class="bc-actions">
            <button type="button" class="btn-ghost btn-toggle-extend" data-target="ext-<?= $bk['id'] ?>">Extend Stay</button>
            <form method="POST" action="process-checkout.php" class="checkout-form" data-booking-id="<?= $bk['id'] ?>" style="margin:0">
                <input type="hidden" name="booking_id" value="<?= $bk['id'] ?>">
                <button type="submit" class="btn-gold">Check Out</button>
            </form>
        </div>
        <div class="extend-box" id="ext-<?= $bk['id'] ?>">
            <form method="POST" action="process-extend-booking.php" class="extend-row">
                <input type="hidden" name="booking_id" value="<?= $bk['id'] ?>">
                <div class="extend-field">
                    <label class="extend-label">Additional Nights</label>
                    <div class="qty-control">
                        <button type="button" class="qty-minus">−</button>
                        <input type="number" name="extra_nights" class="extend-nights" value="1" min="1" max="14" readonly data-rate="<?= $bk['nightly_rate'] ?>" data-qty="<?= $bk['quantity'] ?>">
                        <button type="button" class="qty-plus">+</button>
                    </div>
                </div>
                <div class="extend-cost">Additional payment: <b class="extend-total">₱<?= number_format($bk['nightly_rate'] * $bk['quantity'],2) ?></b></div>
                <button type="submit" class="btn-gold">Confirm &amp; Pay Extension</button>
            </form>
        </div>

        <!-- ── Dining & Amenities ── -->
        <div class="dining-panel">
            <button type="button" class="dining-toggle" data-dining-target="din-<?= $bk['id'] ?>">
                <span>🍽 Room Dining &amp; Champagne</span>
                <span class="chev">▾</span>
            </button>
            <div class="dining-body" id="din-<?= $bk['id'] ?>">
            <div class="dining-body-inner">

                <?php if (!empty($bk['dining_orders'])): ?>
                <div class="dining-existing">
                    <?php foreach ($bk['dining_orders'] as $do): ?>
                    <div class="dining-existing-row">
                        <span><?= date('M j', strtotime($do['scheduled_date'])) ?> · <b><?= htmlspecialchars($do['name']) ?></b> ×<?= (int)$do['quantity'] ?></span>
                        <span><?= $do['total_price'] > 0 ? '₱'.number_format($do['total_price'],2) : 'Included' ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form class="dining-order-form" data-booking-id="<?= $bk['id'] ?>" data-checkin="<?= $bk['checkin_date'] ?>" data-checkout="<?= $bk['checkout_date'] ?>">
                    <div class="dining-tabs">
                        <button type="button" class="dining-tab-btn active" data-tab="breakfast">Breakfast</button>
                        <button type="button" class="dining-tab-btn" data-tab="lunch">Lunch</button>
                        <button type="button" class="dining-tab-btn" data-tab="dinner">Dinner</button>
                        <button type="button" class="dining-tab-btn" data-tab="drink">Drinks</button>
                        <button type="button" class="dining-tab-btn" data-tab="champagne">Champagne</button>
                    </div>
                    <div class="dining-panes"></div>
                    <div class="dining-footer">
                        <div class="dining-date">
                            <label>Serve on</label>
                            <input type="date" class="din-date" value="<?= max($bk['checkin_date'], $today) ?>" min="<?= $bk['checkin_date'] ?>" max="<?= $bk['checkout_date'] ?>">
                        </div>
                        <div class="dining-total">Champagne charge: <b class="din-charge">₱0</b></div>
                        <button type="submit" class="btn-gold">Send Order to Room</button>
                    </div>
                    <div class="dining-msg"></div>
                </form>
            </div>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>

    <div class="dash-section-label">Past Stays</div>
    <?php if (empty($past)): ?>
        <div class="dash-empty">No past stays yet.</div>
    <?php else: foreach ($past as $bk): ?>
    <div class="booking-card rise">
        <div class="bc-top">
            <div>
                <div class="bc-code-label">Room Access Code</div>
                <div class="bc-code" style="color:var(--h-muted)"><?= htmlspecialchars($bk['booking_ref']) ?></div>
            </div>
            <span class="bc-status <?= $bk['status'] ?>"><?= ucwords(str_replace('_',' ',$bk['status'])) ?></span>
        </div>
        <div class="bc-body">
            <div class="bc-field"><div class="label">Residence</div><div class="val"><?= htmlspecialchars($bk['suite_name']) ?></div></div>
            <div class="bc-field"><div class="label">Checked In</div><div class="val"><?= date('M j, Y', strtotime($bk['checkin_date'])) ?></div></div>
            <div class="bc-field"><div class="label">Checked Out</div><div class="val"><?= date('M j, Y', strtotime($bk['checkout_date'])) ?></div></div>
            <div class="bc-field"><div class="label">Total Paid</div><div class="val">₱<?= number_format($bk['total_price'],2) ?></div></div>
        </div>
        <?php if ($bk['status'] === 'checked_out'): ?>
        <div class="bc-actions">
            <button type="button" class="btn-ghost btn-view-receipt" data-booking-id="<?= $bk['id'] ?>">View Receipt</button>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; endif; ?>
</div>

<!-- ── Receipt Modal (checkout proof + viewable anytime from Past Stays) ── -->
<div class="receipt-overlay" id="receipt-overlay">
    <div class="receipt-box">
        <span class="receipt-corner tl"></span>
        <span class="receipt-corner tr"></span>
        <span class="receipt-corner bl"></span>
        <span class="receipt-corner br"></span>
        <button type="button" class="receipt-close-x" id="receipt-close-x">&times;</button>
        <div id="receipt-content"><!-- filled by JS --></div>
        <div class="receipt-actions">
            <button type="button" class="btn-ghost" id="receipt-print">Print</button>
            <button type="button" class="btn-gold" id="receipt-close-btn">Close</button>
        </div>
    </div>
</div>

<footer><p>© 2026 <span>Nocturne Manila Bay</span> — A Property of AyosCoffeeNegosyo Hospitality.</p></footer>

<script>
const DINING_MENU = <?= $diningMenuJson ?>;
const PAST_RECEIPTS = <?= $pastReceiptsJson ?>; // keyed by booking id — no fetch needed for "View Receipt"
const TAB_LABELS = { breakfast:'Breakfast', lunch:'Lunch', dinner:'Dinner', drink:'Drinks', champagne:'Champagne' };
const PESO = n => '₱' + Math.round(n).toLocaleString('en-PH');

// ── Count-up animation for the stat numbers ──────────────────────────
document.querySelectorAll('.stat-item .num').forEach(el => {
    const target = parseInt(el.dataset.count, 10) || 0;
    const prefix = el.dataset.prefix || '';
    const duration = 900;
    const start = performance.now();
    function tick(now){
        const p = Math.min(1, (now - start) / duration);
        const eased = 1 - Math.pow(1 - p, 3);
        el.textContent = prefix + Math.round(target * eased).toLocaleString('en-PH');
        if (p < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
});
document.querySelectorAll('[data-vip-fill]').forEach(el => {
    requestAnimationFrame(() => { el.style.width = el.dataset.vipFill + '%'; });
});

// ── Extend Stay toggle (unchanged) ───────────────────────────────────
document.querySelectorAll('.btn-toggle-extend').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById(btn.dataset.target).classList.toggle('open');
    });
});
document.querySelectorAll('.extend-row').forEach(row => {
    const input = row.querySelector('.extend-nights');
    const totalEl = row.querySelector('.extend-total');
    const rate = parseFloat(input.dataset.rate);
    const qty = parseInt(input.dataset.qty);
    function recalc(){ totalEl.textContent = '₱' + (rate * qty * parseInt(input.value)).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2}); }
    row.querySelector('.qty-minus').addEventListener('click', () => { input.value = Math.max(1, parseInt(input.value)-1); recalc(); });
    row.querySelector('.qty-plus').addEventListener('click', () => { input.value = Math.min(14, parseInt(input.value)+1); recalc(); });
});

// ── Dining panel: smooth expand/collapse (max-height animation) ──────
document.querySelectorAll('.dining-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const target = document.getElementById(btn.dataset.diningTarget);
        const inner = target.querySelector('.dining-body-inner');
        const opening = !btn.classList.contains('open');
        btn.classList.toggle('open');
        target.classList.toggle('open');
        target.style.maxHeight = opening ? inner.scrollHeight + 'px' : '0px';
    });
});

// ── Dining panel: build tab panes (paginated, 6/page), quantity steppers, submit ─────
document.querySelectorAll('.dining-order-form').forEach(form => {
    const panes = form.querySelector('.dining-panes');
    const chargeEl = form.querySelector('.din-charge');
    const msgBox = form.querySelector('.dining-msg');
    const dateInput = form.querySelector('.din-date');

    const PAGE_SIZE = 6;
    const orderQty = {};    // itemId -> quantity, remembered across pages & tabs
    const menuLookup = {};  // itemId -> { price, name }

    // If the dining panel is currently open, recompute its max-height so the
    // expand animation never clips content after switching tabs / pages.
    function refreshBodyHeight() {
        const body = form.closest('.dining-body');
        if (body && body.classList.contains('open')) {
            const inner = body.querySelector('.dining-body-inner');
            body.style.maxHeight = inner.scrollHeight + 'px';
        }
    }

    Object.keys(TAB_LABELS).forEach(type => {
        const items = DINING_MENU[type] || [];
        items.forEach(it => { menuLookup[it.id] = { price: it.price, name: it.name }; });

        const pane = document.createElement('div');
        pane.className = 'dining-tab-panel' + (type === 'breakfast' ? ' active' : '');
        pane.dataset.pane = type;

        const listEl = document.createElement('div');
        listEl.className = 'dining-item-list';
        pane.appendChild(listEl);

        let pagerEl = null;
        if (items.length > PAGE_SIZE) {
            pagerEl = document.createElement('div');
            pagerEl.className = 'dining-pager';
            pagerEl.innerHTML = `<button type="button" class="pager-prev">‹ Prev</button><span class="pager-info"></span><button type="button" class="pager-next">Next ›</button>`;
            pane.appendChild(pagerEl);
        }

        let currentPage = 0;
        const totalPages = Math.max(1, Math.ceil(items.length / PAGE_SIZE));

        function renderPage() {
            listEl.innerHTML = '';
            if (!items.length) {
                listEl.innerHTML = '<div class="dash-empty" style="padding:14px">Nothing on the menu right now.</div>';
                return;
            }
            const start = currentPage * PAGE_SIZE;
            items.slice(start, start + PAGE_SIZE).forEach(it => {
                const row = document.createElement('div');
                row.className = 'dining-item';
                const priceLabel = it.price > 0 ? PESO(it.price) + ' per bottle' : 'Included with your stay';
                const qty = orderQty[it.id] || 0;
                row.innerHTML = `
                    <div>
                        <div class="dining-item-name">${it.name}</div>
                        <div class="dining-item-desc">${it.description || ''}</div>
                        <div class="dining-item-price">${priceLabel}</div>
                    </div>
                    <div class="qty-control">
                        <button type="button" class="qty-minus">−</button>
                        <input type="number" class="din-qty" value="${qty}" min="0" max="10" readonly data-id="${it.id}">
                        <button type="button" class="qty-plus">+</button>
                    </div>`;
                const input = row.querySelector('.din-qty');
                row.querySelector('.qty-minus').addEventListener('click', () => {
                    const v = Math.max(0, parseInt(input.value) - 1);
                    input.value = v;
                    orderQty[it.id] = v;
                    recalcCharge();
                });
                row.querySelector('.qty-plus').addEventListener('click', () => {
                    const v = Math.min(10, parseInt(input.value) + 1);
                    input.value = v;
                    orderQty[it.id] = v;
                    recalcCharge();
                });
                listEl.appendChild(row);
            });
            if (pagerEl) {
                pagerEl.querySelector('.pager-info').textContent = `Page ${currentPage + 1} of ${totalPages}`;
                pagerEl.querySelector('.pager-prev').disabled = currentPage === 0;
                pagerEl.querySelector('.pager-next').disabled = currentPage >= totalPages - 1;
            }
            refreshBodyHeight();
        }

        if (pagerEl) {
            pagerEl.querySelector('.pager-prev').addEventListener('click', () => {
                if (currentPage > 0) { currentPage--; renderPage(); }
            });
            pagerEl.querySelector('.pager-next').addEventListener('click', () => {
                if (currentPage < totalPages - 1) { currentPage++; renderPage(); }
            });
        }

        renderPage();
        panes.appendChild(pane);
    });

    function recalcCharge() {
        let total = 0;
        Object.keys(orderQty).forEach(id => {
            const qty = orderQty[id] || 0;
            const item = menuLookup[id];
            if (qty > 0 && item) total += item.price * qty;
        });
        chargeEl.textContent = PESO(total);
    }

    form.querySelectorAll('.dining-tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            form.querySelectorAll('.dining-tab-btn').forEach(b => b.classList.remove('active'));
            panes.querySelectorAll('.dining-tab-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            panes.querySelector(`[data-pane="${btn.dataset.tab}"]`).classList.add('active');
            refreshBodyHeight();
        });
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        msgBox.style.display = 'none';
        const items = [];
        Object.keys(orderQty).forEach(id => {
            const qty = orderQty[id] || 0;
            if (qty > 0) items.push({ id: parseInt(id), qty });
        });
        if (!items.length) {
            msgBox.className = 'dining-msg error';
            msgBox.textContent = 'Please choose at least one item first.';
            msgBox.style.display = 'block';
            refreshBodyHeight();
            return;
        }
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';
        try {
            const r = await fetch('process-meal-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ booking_id: parseInt(form.dataset.bookingId), for_date: dateInput.value, items })
            });
            const raw = await r.text();
            let data;
            try { data = JSON.parse(raw); }
            catch (parseErr) {
                throw new Error(`Server returned an unexpected response (HTTP ${r.status}). ${raw.slice(0, 200)}`);
            }
            if (data.ok) {
                msgBox.className = 'dining-msg ok';
                msgBox.textContent = 'Order sent to your room' + (data.extra_charge > 0 ? ` — ${PESO(data.extra_charge)} added to your bill.` : ' — complimentary, no extra charge.');
                msgBox.style.display = 'block';
                Object.keys(orderQty).forEach(id => orderQty[id] = 0);
                panes.querySelectorAll('.din-qty').forEach(inp => inp.value = 0);
                recalcCharge();
            } else {
                msgBox.className = 'dining-msg error';
                msgBox.textContent = data.error || 'Something went wrong. Please try again.';
                msgBox.style.display = 'block';
            }
        } catch (err) {
            msgBox.className = 'dining-msg error';
            msgBox.textContent = err.message || 'Network error — please check your connection and try again.';
            msgBox.style.display = 'block';
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Send Order to Room';
            refreshBodyHeight();
        }
    });
});

// ── Receipt modal: shared render + open/close logic ──────────────────
const receiptOverlay = document.getElementById('receipt-overlay');
const receiptContent = document.getElementById('receipt-content');
let receiptShouldReloadOnClose = false;

function openReceiptModal(receipt, reloadOnClose) {
    receiptShouldReloadOnClose = !!reloadOnClose;

    const fmtDate = (d) => new Date(d + 'T00:00:00').toLocaleDateString('en-PH', {month:'short', day:'numeric', year:'numeric'});
    const roomsLabel = (receipt.room_numbers && receipt.room_numbers.length) ? receipt.room_numbers.join(', ') : `${receipt.quantity} room(s)`;

    let diningRows;
    if (receipt.dining_items && receipt.dining_items.length) {
        diningRows = receipt.dining_items.map(d => {
            const dDate = new Date(d.scheduled_date + 'T00:00:00').toLocaleDateString('en-PH', {month:'short', day:'numeric'});
            const priceLabel = d.total_price > 0 ? PESO(d.total_price) : 'Included';
            const typeLabel = TAB_LABELS[d.type] || d.type;
            return `<div class="receipt-dining-row">
                        <span><span class="rd-name">${d.name}</span> ×${d.quantity}<span class="rd-sub">${dDate} · ${typeLabel}</span></span>
                        <span>${priceLabel}</span>
                    </div>`;
        }).join('');
    } else {
        diningRows = '<div class="receipt-empty-note">No dining or champagne ordered during this stay.</div>';
    }

    receiptContent.innerHTML = `
        <div class="receipt-head">
            <div class="eyebrow">Nocturne Manila Bay</div>
            <h2>Guest Receipt</h2>
            <div class="sub">${receipt.booking_ref}</div>
        </div>

        <div class="receipt-divider"><span class="receipt-diamond"></span></div>

        <div class="receipt-row"><span class="lbl">Guest</span><span class="val">${receipt.guest_name}<span class="receipt-tier-badge ${receipt.guest_tier}">${receipt.guest_tier === 'vip' ? 'Nocturne Noir · VIP' : 'Regular'}</span></span></div>
        <div class="receipt-row"><span class="lbl">Room Access Code</span><span class="val">${receipt.booking_ref}</span></div>
        <div class="receipt-row"><span class="lbl">Residence</span><span class="val">${receipt.suite_name}</span></div>
        <div class="receipt-row"><span class="lbl">Room(s)</span><span class="val">${roomsLabel}</span></div>
        <div class="receipt-row"><span class="lbl">Checked In</span><span class="val">${fmtDate(receipt.checkin_date)}</span></div>
        <div class="receipt-row"><span class="lbl">Checked Out</span><span class="val">${fmtDate(receipt.checkout_date)}</span></div>

        <div class="receipt-divider"><span class="receipt-diamond"></span></div>

        <div class="receipt-section-title">Dining &amp; Champagne</div>
        ${diningRows}

        <div class="receipt-totals">
            <div class="rt-row"><span>Room Total</span><span>${PESO(receipt.room_total)}</span></div>
            <div class="rt-row"><span>Dining &amp; Champagne Total</span><span>${PESO(receipt.dining_total)}</span></div>
            <div class="rt-row grand"><span>Total Paid</span><span>${PESO(receipt.grand_total)}</span></div>
        </div>`;

    receiptOverlay.classList.add('open');
}

function closeReceiptModal() {
    receiptOverlay.classList.remove('open');
    if (receiptShouldReloadOnClose) location.reload();
}

document.getElementById('receipt-close-x').addEventListener('click', closeReceiptModal);
document.getElementById('receipt-close-btn').addEventListener('click', closeReceiptModal);
document.getElementById('receipt-print').addEventListener('click', () => window.print());
receiptOverlay.addEventListener('click', (e) => { if (e.target === receiptOverlay) closeReceiptModal(); });
document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && receiptOverlay.classList.contains('open')) closeReceiptModal(); });

// ── Check Out: AJAX submit, then show the receipt as proof of checkout ──
document.querySelectorAll('.checkout-form').forEach(form => {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!confirm('Check out now? This will end the reservation.')) return;

        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Checking out...';

        try {

            const r = await fetch('process-checkout.php', {

                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'booking_id=' + encodeURIComponent(form.dataset.bookingId)

            });

            const raw = await r.text();
            let data;

            try { data = JSON.parse(raw); }

                catch (parseErr) {
                    
                    throw new Error(`Server returned an unexpected response (HTTP ${r.status}). ${raw.slice(0, 200)}`);
            }

            if (data.ok) {

                openReceiptModal(data.receipt, true); // reload the dashboard once the guest closes the receipt
            
            } else {
                
                alert(data.error || 'Something went wrong. Please try again.');
                btn.disabled = false;
                btn.textContent = originalText;
            
            }

        } catch (err) {

            alert(err.message || 'Network error — please check your connection and try again.');
            btn.disabled = false;
            btn.textContent = originalText;

        }

    });
    
});

// ── Past Stays: "View Receipt" reads the pre-built data embedded above ──
document.querySelectorAll('.btn-view-receipt').forEach(btn => {
    btn.addEventListener('click', () => {
        const receipt = PAST_RECEIPTS[btn.dataset.bookingId];
        if (receipt) {
            openReceiptModal(receipt, false);
        } else {
            alert('Receipt data is not available for this stay.');
        }
    });
});
</script>
</body>
</html>