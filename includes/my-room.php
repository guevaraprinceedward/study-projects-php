<?php
include_once 'hotel-config.php';
$activePage = '';
requireHotelLogin('my-room.php');

$userId = $_SESSION['user']['id'];
$today  = date('Y-m-d');

// ── Active stays only — this page is a live "room pass", not a full history ──
$stmt = $conn->prepare("SELECT b.*, s.name AS suite_name, s.slug, s.cover_image, s.view_desc,
                                ts.label AS slot_label, ts.checkin_time, ts.checkout_time
                         FROM bookings b
                         JOIN suites s ON s.id = b.suite_id
                         JOIN time_slots ts ON ts.id = b.time_slot_id
                         WHERE b.user_id = ?
                           AND b.status IN ('pending','confirmed','checked_in')
                           AND b.checkout_date >= ?
                         ORDER BY b.checkin_date ASC, b.id ASC");
$stmt->bind_param("is", $userId, $today);
$stmt->execute();
$stays = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($stays as &$bk) {
    $bk['room_numbers'] = [];
    $rr = $conn->query("SELECT r.room_number FROM booking_rooms br
                         JOIN rooms r ON r.id = br.room_id
                         WHERE br.booking_id = " . (int)$bk['id'] . " ORDER BY r.room_number ASC");
    if ($rr) { while ($row = $rr->fetch_assoc()) $bk['room_numbers'][] = $row['room_number']; }

    // Combine the booking's actual dates with the time slot's clock times to
    // get the real check-in / check-out instants (JS timestamps, in ms).
    $checkinAt  = strtotime($bk['checkin_date']  . ' ' . $bk['checkin_time']);
    $checkoutAt = strtotime($bk['checkout_date'] . ' ' . $bk['checkout_time']);
    $bk['checkin_ts']  = $checkinAt  * 1000;
    $bk['checkout_ts'] = $checkoutAt * 1000;
}
unset($bk);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Room — Nocturne Manila Bay</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="hotel-theme.css">
<style>
/* PAGE-SPECIFIC: my-room */
.room-wrap{position:relative;z-index:1;max-width:820px;margin:0 auto;padding:50px 24px 120px}
.dash-empty{color:var(--h-muted);font-size:13px;border:1px dashed var(--h-line);border-radius:4px;padding:26px;text-align:center}

@keyframes riseIn{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.rise{animation:riseIn 0.55s cubic-bezier(.2,.7,.3,1) both}

.pass-card{background:var(--h-card);border:1px solid var(--h-line);border-radius:6px;overflow:hidden;margin-bottom:28px}
.pass-photo{width:100%;height:220px;position:relative;overflow:hidden}
.pass-photo img{width:100%;height:100%;object-fit:cover}
.pass-photo::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(4,4,6,0) 35%,rgba(4,4,6,0.9) 100%)}
.pass-photo-info{position:absolute;left:22px;bottom:16px;z-index:1}
.pass-photo-info .suite{font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--h-champagne)}
.pass-photo-info .view{font-size:11.5px;color:var(--h-muted);margin-top:2px}
.pass-rooms{position:absolute;right:22px;bottom:16px;z-index:1;display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}
.pass-room-pill{background:rgba(4,4,6,0.55);backdrop-filter:blur(4px);border:1px solid var(--h-gold-dim);border-radius:100px;padding:4px 13px;font-size:12.5px;color:var(--h-gold)}

.pass-status-row{display:flex;justify-content:space-between;align-items:center;padding:18px 24px;border-bottom:1px solid var(--h-line);flex-wrap:wrap;gap:10px}
.pass-status-label{font-size:9.5px;letter-spacing:0.14em;text-transform:uppercase;color:var(--h-muted)}
.pass-status-badge{font-size:12px;letter-spacing:0.08em;text-transform:uppercase;padding:6px 14px;border-radius:100px;font-weight:600}
.pass-status-badge.upcoming{background:rgba(127,168,118,0.12);color:#7fa876;border:1px solid rgba(127,168,118,0.3)}
.pass-status-badge.staying{background:rgba(201,165,74,0.14);color:var(--h-vip);border:1px solid rgba(201,165,74,0.35)}
.pass-status-badge.overdue{background:rgba(192,87,74,0.12);color:var(--h-bad);border:1px solid rgba(192,87,74,0.3)}

.timer-zone{padding:26px 24px 22px;text-align:center}
.timer-caption{font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:var(--h-muted);margin-bottom:14px}
.timer-digits{display:flex;justify-content:center;gap:10px;flex-wrap:wrap}
.timer-unit{min-width:70px}
.timer-unit .val{font-family:'Cormorant Garamond',serif;font-size:38px;color:var(--h-gold);line-height:1;font-variant-numeric:tabular-nums}
.timer-unit .lbl{font-size:9.5px;letter-spacing:0.1em;text-transform:uppercase;color:var(--h-muted);margin-top:4px}
.timer-sep{font-family:'Cormorant Garamond',serif;font-size:34px;color:var(--h-line);align-self:flex-start;padding-top:2px}

.progress-zone{padding:0 24px 24px}
.progress-labels{display:flex;justify-content:space-between;font-size:11px;color:var(--h-muted);margin-bottom:8px}
.stay-bar{height:6px;border-radius:100px;background:rgba(255,255,255,0.06);overflow:hidden}
.stay-bar-fill{height:100%;border-radius:100px;background:linear-gradient(90deg,var(--h-gold-dim),var(--h-gold));width:0%;transition:width 0.6s ease}

.pass-meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:14px;padding:0 24px 24px}
.pass-meta .m-label{font-size:9.5px;letter-spacing:0.12em;text-transform:uppercase;color:var(--h-muted);margin-bottom:5px}
.pass-meta .m-val{font-size:14px;color:var(--h-champagne);font-family:'Cormorant Garamond',serif}

.pass-manage{display:flex;justify-content:flex-end;padding:0 24px 22px}
</style>
</head>
<body>
<?php include_once 'sidebar.php'; ?>
<?php include_once 'topheader.php'; ?>

<section class="page-hero">
    <div class="sec-eyebrow">Live Status</div>
    <h1>My <em>Room</em></h1>
    <p>Your room number, current status, and a running timer for your stay.</p>
</section>

<div class="room-wrap">
    <?php if (empty($stays)): ?>
        <div class="dash-empty">No active room right now. <a href="hotel-reserve.php" style="color:var(--h-gold)">Reserve a residence →</a></div>
    <?php else: foreach ($stays as $bk): ?>
    <div class="pass-card rise" data-checkin="<?= $bk['checkin_ts'] ?>" data-checkout="<?= $bk['checkout_ts'] ?>">
        <div class="pass-photo">
            <img src="<?= htmlspecialchars($bk['cover_image']) ?>" alt="<?= htmlspecialchars($bk['suite_name']) ?>">
            <div class="pass-photo-info">
                <div class="suite"><?= htmlspecialchars($bk['suite_name']) ?></div>
                <div class="view"><?= htmlspecialchars($bk['view_desc']) ?></div>
            </div>
            <div class="pass-rooms">
                <?php if ($bk['room_numbers']): foreach ($bk['room_numbers'] as $rn): ?>
                    <span class="pass-room-pill"><?= htmlspecialchars($rn) ?></span>
                <?php endforeach; else: ?>
                    <span class="pass-room-pill"><?= (int)$bk['quantity'] ?> room<?= $bk['quantity']>1?'s':'' ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="pass-status-row">
            <div><span class="pass-status-label">Room Access Code</span><br><span style="font-family:'Cormorant Garamond',serif;font-size:19px;color:var(--h-gold)"><?= htmlspecialchars($bk['booking_ref']) ?></span></div>
            <span class="pass-status-badge js-status-badge">—</span>
        </div>

        <div class="timer-zone">
            <div class="timer-caption js-timer-caption">Calculating…</div>
            <div class="timer-digits">
                <div class="timer-unit"><div class="val js-d">00</div><div class="lbl">Days</div></div>
                <div class="timer-sep">:</div>
                <div class="timer-unit"><div class="val js-h">00</div><div class="lbl">Hours</div></div>
                <div class="timer-sep">:</div>
                <div class="timer-unit"><div class="val js-m">00</div><div class="lbl">Mins</div></div>
                <div class="timer-sep">:</div>
                <div class="timer-unit"><div class="val js-s">00</div><div class="lbl">Secs</div></div>
            </div>
        </div>

        <div class="progress-zone">
            <div class="progress-labels"><span>Check-in</span><span>Check-out</span></div>
            <div class="stay-bar"><div class="stay-bar-fill js-progress-fill"></div></div>
        </div>

        <div class="pass-meta">
            <div><div class="m-label">Check-in</div><div class="m-val"><?= date('M j, Y g:ia', $bk['checkin_ts']/1000) ?></div></div>
            <div><div class="m-label">Check-out</div><div class="m-val"><?= date('M j, Y g:ia', $bk['checkout_ts']/1000) ?></div></div>
            <div><div class="m-label">Window</div><div class="m-val"><?= htmlspecialchars($bk['slot_label']) ?></div></div>
        </div>

        <div class="pass-manage">
            <a href="dashboard.php" class="btn-ghost">Manage This Stay →</a>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<footer><p>© 2026 <span>Nocturne Manila Bay</span> — A Property of AyosCoffeeNegosyo Hospitality.</p></footer>

<script>
function pad(n){ return String(n).padStart(2,'0'); }

function tickCard(card){
    const checkinAt = parseInt(card.dataset.checkin, 10);
    const checkoutAt = parseInt(card.dataset.checkout, 10);
    const now = Date.now();

    const badge = card.querySelector('.js-status-badge');
    const caption = card.querySelector('.js-timer-caption');
    const dEl = card.querySelector('.js-d'), hEl = card.querySelector('.js-h'),
          mEl = card.querySelector('.js-m'), sEl = card.querySelector('.js-s');
    const fill = card.querySelector('.js-progress-fill');

    let targetMs, label, badgeClass;

    if (now < checkinAt) {
        targetMs = checkinAt - now;
        label = 'Time until check-in';
        badgeClass = 'upcoming';
        badge.textContent = 'Upcoming Stay';
        fill.style.width = '0%';
    } else if (now <= checkoutAt) {
        targetMs = checkoutAt - now;
        label = 'Time remaining in your room';
        badgeClass = 'staying';
        badge.textContent = 'Currently Staying';
        const total = checkoutAt - checkinAt;
        const elapsed = now - checkinAt;
        fill.style.width = Math.min(100, Math.max(0, (elapsed / total) * 100)) + '%';
    } else {
        targetMs = now - checkoutAt;
        label = 'Time past your scheduled check-out';
        badgeClass = 'overdue';
        badge.textContent = 'Check-out Time Passed';
        fill.style.width = '100%';
    }

    badge.className = 'pass-status-badge js-status-badge ' + badgeClass;
    caption.textContent = label;

    const totalSeconds = Math.max(0, Math.floor(targetMs / 1000));
    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const mins = Math.floor((totalSeconds % 3600) / 60);
    const secs = totalSeconds % 60;
    dEl.textContent = pad(days);
    hEl.textContent = pad(hours); 
    mEl.textContent = pad(mins);
    sEl.textContent = pad(secs);
}

const cards = document.querySelectorAll('.pass-card');
function tickAll(){ cards.forEach(tickCard); }
tickAll();
setInterval(tickAll, 1000);
</script>
</body>
</html>