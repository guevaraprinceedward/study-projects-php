<?php
/**
 * my-reservations.php
 * -------------------------------------------------------------------------
 * Everything the customer has reserved, newest first.
 *   - Logged in : reservations tied to the account (reservations.user_id)
 *   - Guest     : the reservations made in this browser session
 *
 * Per reservation: view receipt, "Pick up now" (-> reservation-checkout.php),
 * cancel (unpaid only; the held stock goes back on the shelf via
 * reservation_handler.php?action=cancel), and a link to the paid order.
 * Unpaid reservations simply stay at the counter until they are picked up.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';
include_once 'reservation-init.php';

$uid        = resValidUserId($conn);
$isLoggedIn = $uid > 0;
$reservations = resListForVisitor($conn, $uid);

$counts   = ['all' => 0, 'waiting' => 0, 'paid' => 0, 'done' => 0, 'cancelled' => 0];
$receipts = [];
foreach ($reservations as $r) {
    $counts['all']++;
    $counts[resGroupKey($r)]++;
    $receipts['r' . (int)$r['id']] = resReceiptData($r);
}
$chipLabels = ['waiting' => 'At the counter', 'paid' => 'Paid', 'done' => 'Picked up', 'cancelled' => 'Cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations — AyosCoffeeNegosyo</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="reservation-pages.css">
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
            <a href="my-reservations.php" class="current">Reservations</a>
            <a href="reservation-orders.php">Orders</a>
            <?php if ($isLoggedIn): ?>
                <a href="log-out.php">Logout</a>
            <?php else: ?>
                <a href="log-in.php">Login</a>
            <?php endif; ?>
            <a href="reservation-cart.php" class="nav-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                Reservation Cart
            </a>
        </nav>
    </div>
</header>

<div class="page-hero">
    <div class="hero-eyebrow">Held at the counter</div>
    <h1>My <em>Reservations</em></h1>
    <p class="hero-sub">Your reserved orders stay at the counter until you pick them up. Pay when you pick up, or pay ahead.</p>
</div>

<main class="wrap">
<?php if (!$isLoggedIn && $counts['all'] > 0): ?>
    <div class="notice">You reserved as a guest, so these are saved in this browser only. <a href="register.php">Create an account</a> to keep them in one place.</div>
<?php endif; ?>

<?php if ($counts['all'] === 0): ?>
    <div class="empty">
        <div class="empty-icon">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <h3>No reservations yet</h3>
        <p>Reserve your favorites and pick them up at the branch when you're ready.</p>
        <a href="reservation-menu.php" class="btn primary">Browse reservation menu</a>
    </div>
<?php else: ?>

    <div class="chips" role="tablist" aria-label="Filter reservations">
        <button type="button" class="chip active" data-filter="all">All<span class="n"><?= $counts['all'] ?></span></button>
        <?php foreach ($chipLabels as $key => $label): if ($counts[$key] < 1) continue; ?>
        <button type="button" class="chip" data-filter="<?= $key ?>"><?= $label ?><span class="n"><?= $counts[$key] ?></span></button>
        <?php endforeach; ?>
    </div>

    <div class="res-list" id="resList">
    <?php foreach ($reservations as $r):
        [$label, $color] = resStatusMeta($r['status'], $r['payment_status']);
        $key    = resGroupKey($r);
        $id     = (int)$r['id'];
        $code   = $r['reservation_code'] ?: ('RSV-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT));
        $shown  = array_slice($r['items'], 0, 3);
        $more   = count($r['items']) - count($shown);
        $qtyAll = array_sum(array_map(fn($i) => (int)$i['quantity'], $r['items']));
    ?>
        <article class="res-card" id="res-<?= $id ?>" data-group="<?= $key ?>">
            <div class="res-top">
                <div>
                    <div class="res-code"><?= htmlspecialchars($code) ?></div>
                    <div class="res-created">Reserved <?= htmlspecialchars(date('M j, Y · g:i A', strtotime($r['created_at']))) ?></div>
                </div>
                <span class="pill" style="color:<?= htmlspecialchars($color) ?>"><i></i><?= htmlspecialchars($label) ?></span>
            </div>

            <div class="res-meta">
                <div><span class="k">Branch</span><span class="v"><?= ucfirst(htmlspecialchars($r['branch'])) ?></span></div>
                <div><span class="k">Pick-up</span><span class="v"><?= htmlspecialchars(resFormatDate($r['reservation_date'])) ?><br><?= htmlspecialchars(resFormatTime($r['reservation_time'])) ?></span></div>
                <div><span class="k">Party</span><span class="v"><?= (int)$r['party_size'] ?> · <?= $qtyAll ?> item<?= $qtyAll !== 1 ? 's' : '' ?></span></div>
                <div><span class="k">Total</span><span class="v gold">₱<?= number_format((float)$r['total'], 2) ?></span></div>
            </div>

            <div class="res-items">
                <?php foreach ($shown as $it): ?>
                <div class="res-item">
                    <span><?= htmlspecialchars($it['product_name']) ?><span class="q">×<?= (int)$it['quantity'] ?></span></span>
                    <span class="p">₱<?= number_format($it['line_total'], 2) ?></span>
                </div>
                <?php if (!empty($it['addon_list'])): ?>
                <div class="res-addon">+ <?= htmlspecialchars(implode(', ', array_column($it['addon_list'], 'name'))) ?></div>
                <?php endif; ?>
                <?php endforeach; ?>
                <?php if ($more > 0): ?><div class="res-more">+ <?= $more ?> more item<?= $more !== 1 ? 's' : '' ?></div><?php endif; ?>
            </div>

            <p class="res-note">
                <?php if ($key === 'waiting'): ?>
                    <strong>Waiting at the counter.</strong> Pick it up and pay when you arrive, or pay ahead now.
                <?php elseif ($key === 'paid'): ?>
                    <strong>Paid</strong> · <?= htmlspecialchars(resPaymentSummary($r)) ?>. Show your receipt at the counter to pick it up.
                <?php elseif ($key === 'done'): ?>
                    <strong>Picked up</strong><?= !empty($r['picked_up_at']) ? ' on ' . htmlspecialchars(date('M j, Y · g:i A', strtotime($r['picked_up_at']))) : '' ?>.
                <?php else: ?>
                    <strong>Cancelled</strong><?= !empty($r['cancelled_at']) ? ' on ' . htmlspecialchars(date('M j, Y', strtotime($r['cancelled_at']))) : '' ?>. The items went back on the shelf.
                <?php endif; ?>
            </p>

            <div class="res-actions">
                <button type="button" class="btn ghost" onclick="openReservationReceipt(RECEIPTS['r<?= $id ?>'])">View receipt</button>
                <?php if ($key === 'waiting'): ?>
                    <a class="btn primary" href="<?= htmlspecialchars(resPayUrl($id)) ?>">Pick up now</a>
                    <button type="button" class="btn danger" onclick="askCancel(<?= $id ?>, '<?= htmlspecialchars($code, ENT_QUOTES) ?>')">Cancel</button>
                <?php elseif ($key === 'paid' || $key === 'done'): ?>
                    <a class="btn ghost" href="reservation-orders.php#order-<?= $id ?>">View order</a>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
    <p class="no-match" id="noMatch">Nothing in this group.</p>
<?php endif; ?>
</main>

<!-- cancel confirmation -->
<div id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="cmTitle">
    <div class="cm-card">
        <h3 id="cmTitle">Cancel this reservation?</h3>
        <p><strong id="cmCode"></strong><br>Your held items go back on the shelf. This can't be undone.</p>
        <div class="cm-btns">
            <button type="button" class="btn ghost" onclick="closeCancel()">Keep reservation</button>
            <button type="button" class="btn danger" id="cmYes" onclick="doCancel()">Cancel reservation</button>
        </div>
    </div>
</div>
<div id="toast" role="status"></div>

<footer><p>© 2026 <span>My AyosCoffeeNegosyo</span> — All rights reserved.</p></footer>

<?php include 'reservation-receipt.inc.php'; ?>

<script>
const RECEIPTS = <?= resJson($receipts ?: new stdClass()) ?>;

// ── filter chips ──
document.querySelectorAll('.chip').forEach(function(chip){
    chip.addEventListener('click', function(){
        document.querySelectorAll('.chip').forEach(function(c){ c.classList.remove('active'); });
        chip.classList.add('active');
        var f = chip.dataset.filter, shown = 0;
        document.querySelectorAll('.res-card').forEach(function(card){
            var ok = (f === 'all' || card.dataset.group === f);
            card.style.display = ok ? '' : 'none';
            if (ok) shown++;
        });
        var nm = document.getElementById('noMatch');
        if (nm) nm.style.display = shown ? 'none' : 'block';
    });
});

// ── toast ──
var toastT;
function toast(msg, isErr){
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.className = isErr ? 'error show' : 'show';
    clearTimeout(toastT);
    toastT = setTimeout(function(){ t.classList.remove('show'); }, 2800);
}

// ── cancel (unpaid only) ──
var cancelId = 0;
function askCancel(id, code){
    cancelId = id;
    document.getElementById('cmCode').textContent = code;
    document.getElementById('confirmModal').classList.add('show');
}
function closeCancel(){ document.getElementById('confirmModal').classList.remove('show'); cancelId = 0; }
function doCancel(){
    if (!cancelId) return;
    var btn = document.getElementById('cmYes');
    btn.disabled = true;
    fetch('reservation_handler.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=cancel&id=' + encodeURIComponent(cancelId)
    }).then(function(r){ return r.json(); }).then(function(d){
        btn.disabled = false;
        closeCancel();
        if (d.success) { toast('Reservation cancelled.', false); setTimeout(function(){ location.reload(); }, 700); }
        else { toast(d.message || 'Could not cancel this reservation.', true); }
    }).catch(function(){
        btn.disabled = false; closeCancel();
        toast('Network error. Please try again.', true);
    });
}
document.getElementById('confirmModal').addEventListener('click', function(e){ if (e.target === this) closeCancel(); });
document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeCancel(); });
</script>
</body>
</html>