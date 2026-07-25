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

    // Already-placed dining orders for this stay (grouped for display)
    $bk['dining_orders'] = [];
    if ($bk['is_active']) {
        $dr = $conn->query("SELECT so.scheduled_date, so.quantity, so.total_price, s.name, s.type
                             FROM service_orders so JOIN services s ON s.id = so.service_id
                             WHERE so.booking_id = " . (int)$bk['id'] . "
                             ORDER BY so.scheduled_date ASC, s.type ASC");
        if ($dr) { while ($row = $dr->fetch_assoc()) $bk['dining_orders'][] = $row; }
    }
}
unset($bk);

$active = array_filter($bookings, fn($b) => $b['is_active']);
$past   = array_filter($bookings, fn($b) => $b['is_past']);

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

.extend-box{display:none;padding:16px 22px;border-top:1px solid var(--h-line);background:var(--h-surface)}
.extend-box.open{display:block}
.extend-row{display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap}
.extend-cost{font-size:12.5px;color:var(--h-muted)}
.extend-cost b{color:var(--h-gold);font-family:'Cormorant Garamond',serif;font-size:17px}

/* ── Dining panel ── */
.dining-panel{border-top:1px solid var(--h-line);background:var(--h-surface)}
.dining-toggle{width:100%;text-align:left;background:none;border:none;color:var(--h-gold);font-size:12px;letter-spacing:0.08em;text-transform:uppercase;padding:14px 22px;cursor:pointer;display:flex;justify-content:space-between;align-items:center}
.dining-toggle .chev{transition:transform 0.2s}
.dining-toggle.open .chev{transform:rotate(180deg)}
.dining-body{display:none;padding:4px 22px 22px}
.dining-body.open{display:block}
.dining-existing{margin-bottom:16px;display:flex;flex-direction:column;gap:6px}
.dining-existing-row{font-size:12px;color:var(--h-muted);display:flex;justify-content:space-between;border-bottom:1px dashed var(--h-line);padding-bottom:5px}
.dining-existing-row b{color:var(--h-champagne);font-weight:500}
.dining-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px}
.dining-tab-btn{background:var(--h-card);border:1px solid var(--h-line);border-radius:100px;padding:6px 14px;font-size:11px;letter-spacing:0.05em;text-transform:uppercase;color:var(--h-muted);cursor:pointer}
.dining-tab-btn.active{border-color:var(--h-gold);color:var(--h-gold);background:rgba(207,167,107,0.08)}
.dining-tab-panel{display:none}
.dining-tab-panel.active{display:block}
.dining-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid var(--h-line)}
.dining-item:last-child{border-bottom:none}
.dining-item-name{font-size:13px;color:var(--h-text)}
.dining-item-desc{font-size:11px;color:var(--h-muted);margin-top:2px}
.dining-item-price{font-size:11px;color:var(--h-gold);margin-top:2px}
.qty-control{display:flex;align-items:center;border:1px solid var(--h-line);border-radius:3px;overflow:hidden;flex-shrink:0}
.qty-control button{width:26px;height:26px;background:var(--h-card);border:none;color:var(--h-gold);cursor:pointer;font-size:14px}
.qty-control input{width:32px;text-align:center;background:none;border:none;color:var(--h-champagne);font-size:12.5px}
.dining-footer{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:16px}
.dining-date{font-size:11.5px;color:var(--h-muted);display:flex;align-items:center;gap:8px}
.dining-date input{background:var(--h-card);border:1px solid var(--h-line);border-radius:3px;color:var(--h-text);padding:5px 8px;font-size:12px}
.dining-total{font-size:12.5px;color:var(--h-muted)}
.dining-total b{color:var(--h-gold);font-family:'Cormorant Garamond',serif;font-size:16px}
.dining-msg{font-size:11.5px;margin-top:10px;display:none;padding:8px 10px;border-radius:3px}
.dining-msg.ok{display:block;background:rgba(127,168,118,0.1);border:1px solid rgba(127,168,118,0.3);color:#c7e0c2}
.dining-msg.error{display:block;background:rgba(192,87,74,0.1);border:1px solid rgba(192,87,74,0.3);color:#f0c9c2}
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
            <form method="POST" action="process-checkout.php" onsubmit="return confirm('Check out now? This will end the reservation.');" style="margin:0">
                <input type="hidden" name="booking_id" value="<?= $bk['id'] ?>">
                <button type="submit" class="btn-gold">Check Out</button>
            </form>
        </div>
        <div class="extend-box" id="ext-<?= $bk['id'] ?>">
            <form method="POST" action="process-extend-booking.php" class="extend-row">
                <input type="hidden" name="booking_id" value="<?= $bk['id'] ?>">
                <div class="field" style="min-width:140px">
                    <label>Additional Nights</label>
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
    </div>
    <?php endforeach; endif; ?>
</div>

<footer><p>© 2026 <span>Nocturne Manila Bay</span> — A Property of AyosCoffeeNegosyo Hospitality.</p></footer>

<script>
const DINING_MENU = <?= $diningMenuJson ?>;
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

// ── Dining panel: expand/collapse ────────────────────────────────────
document.querySelectorAll('.dining-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        btn.classList.toggle('open');
        document.getElementById(btn.dataset.diningTarget).classList.toggle('open');
    });
});

// ── Dining panel: build tab panes, quantity steppers, submit ─────────
document.querySelectorAll('.dining-order-form').forEach(form => {
    const panes = form.querySelector('.dining-panes');
    const chargeEl = form.querySelector('.din-charge');
    const msgBox = form.querySelector('.dining-msg');
    const dateInput = form.querySelector('.din-date');

    Object.keys(TAB_LABELS).forEach(type => {
        const pane = document.createElement('div');
        pane.className = 'dining-tab-panel' + (type === 'breakfast' ? ' active' : '');
        pane.dataset.pane = type;
        const items = DINING_MENU[type] || [];
        if (!items.length) {
            pane.innerHTML = '<div class="dash-empty" style="padding:14px">Nothing on the menu right now.</div>';
        } else {
            items.forEach(it => {
                const row = document.createElement('div');
                row.className = 'dining-item';
                const priceLabel = it.price > 0 ? PESO(it.price) + ' per bottle' : 'Included with your stay';
                row.innerHTML = `
                    <div>
                        <div class="dining-item-name">${it.name}</div>
                        <div class="dining-item-desc">${it.description || ''}</div>
                        <div class="dining-item-price">${priceLabel}</div>
                    </div>
                    <div class="qty-control">
                        <button type="button" class="qty-minus">−</button>
                        <input type="number" class="din-qty" value="0" min="0" max="10" readonly data-id="${it.id}" data-price="${it.price}" data-name="${it.name}">
                        <button type="button" class="qty-plus">+</button>
                    </div>`;
                pane.appendChild(row);
            });
        }
        panes.appendChild(pane);
    });

    function recalcCharge(){
        let total = 0;
        form.querySelectorAll('.din-qty').forEach(inp => { total += parseFloat(inp.dataset.price) * parseInt(inp.value || 0); });
        chargeEl.textContent = PESO(total);
    }

    panes.querySelectorAll('.qty-control').forEach(qc => {
        const input = qc.querySelector('.din-qty');
        qc.querySelector('.qty-minus').addEventListener('click', () => { input.value = Math.max(0, parseInt(input.value)-1); recalcCharge(); });
        qc.querySelector('.qty-plus').addEventListener('click', () => { input.value = Math.min(10, parseInt(input.value)+1); recalcCharge(); });
    });

    form.querySelectorAll('.dining-tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            form.querySelectorAll('.dining-tab-btn').forEach(b => b.classList.remove('active'));
            panes.querySelectorAll('.dining-tab-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            panes.querySelector(`[data-pane="${btn.dataset.tab}"]`).classList.add('active');
        });
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        msgBox.style.display = 'none';
        const items = [];
        form.querySelectorAll('.din-qty').forEach(inp => {
            const qty = parseInt(inp.value || 0);
            if (qty > 0) items.push({ id: parseInt(inp.dataset.id), qty });
        });
        if (!items.length) {
            msgBox.className = 'dining-msg error';
            msgBox.textContent = 'Please choose at least one item first.';
            msgBox.style.display = 'block';
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
                // The server didn't return valid JSON (e.g. a PHP error page, a
                // 404, or a path issue) — show the real response so it's easy
                // to diagnose instead of a generic message.
                throw new Error(`Server returned an unexpected response (HTTP ${r.status}). ${raw.slice(0, 200)}`);
            }
            if (data.ok) {
                msgBox.className = 'dining-msg ok';
                msgBox.textContent = 'Order sent to your room' + (data.extra_charge > 0 ? ` — ${PESO(data.extra_charge)} added to your bill.` : ' — complimentary, no extra charge.');
                msgBox.style.display = 'block';
                form.querySelectorAll('.din-qty').forEach(inp => inp.value = 0);
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
        }
    });
});
</script>
</body>
</html>