<?php
/**
 * reservation-orders.php
 * -------------------------------------------------------------------------
 * The customer's RESERVATION ORDERS: every reservation that has been paid
 * (each one is also a normal row in `orders`, linked by reservations.order_id).
 * Unpaid reservations are not orders yet; they live in my-reservations.php.
 *
 * Progress on each card:  Reserved  >  Paid  >  Picked up
 * "Picked up" is set from your admin / counter side:
 *     UPDATE reservations SET status='completed', picked_up_at=NOW() WHERE id=?
 */
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';
include_once 'reservation-init.php';

$uid        = resValidUserId($conn);
$isLoggedIn = $uid > 0;
$orders     = resListForVisitor($conn, $uid, true);   // paid only

$stats    = ['count' => 0, 'waiting' => 0, 'done' => 0, 'spent' => 0.0];
$receipts = [];
foreach ($orders as $o) {
    if (strtolower((string)$o['status']) === 'cancelled') continue;
    $stats['count']++;
    $stats['spent'] += (float)$o['total'];
    if (strtolower((string)$o['status']) === 'completed') $stats['done']++;
    else                                                   $stats['waiting']++;
    $receipts['r' . (int)$o['id']] = resReceiptData($o);
}
// cancelled + paid can't happen (only unpaid can be cancelled), but never list them
$orders = array_values(array_filter($orders, fn($o) => strtolower((string)$o['status']) !== 'cancelled'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Orders — AyosCoffeeNegosyo</title>
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
            <a href="my-reservations.php">Reservations</a>
            <a href="reservation-orders.php" class="current">Orders</a>
            <?php if ($isLoggedIn): ?>
                <a href="log-out.php">Logout</a>
            <?php else: ?>
                <a href="log-in.php">Login</a>
            <?php endif; ?>
            <a href="my-reservations.php" class="nav-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                My Reservations
            </a>
        </nav>
    </div>
</header>

<div class="page-hero">
    <div class="hero-eyebrow">Paid reservations</div>
    <h1>Reservation <em>Orders</em></h1>
    <p class="hero-sub">Every reserved order you've paid for is collected here, from payment to pick up.</p>
</div>

<main class="wrap">
<?php if (!$isLoggedIn && $stats['count'] > 0): ?>
    <div class="notice">You reserved as a guest, so these are saved in this browser only. <a href="register.php">Create an account</a> to keep them in one place.</div>
<?php endif; ?>

<?php if (empty($orders)): ?>
    <div class="empty">
        <div class="empty-icon">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <h3>No paid orders yet</h3>
        <p>A reservation shows up here once you pay for it. Unpaid reservations are waiting for you in My Reservations.</p>
        <a href="my-reservations.php" class="btn primary">Go to my reservations</a>
    </div>
<?php else: ?>

    <div class="stats">
        <div class="stat"><span class="k">Orders</span><span class="v"><?= $stats['count'] ?></span></div>
        <div class="stat"><span class="k">Ready for pick up</span><span class="v"><?= $stats['waiting'] ?></span></div>
        <div class="stat"><span class="k">Picked up</span><span class="v"><?= $stats['done'] ?></span></div>
        <div class="stat"><span class="k">Total paid</span><span class="v gold">₱<?= number_format($stats['spent'], 2) ?></span></div>
    </div>

    <div class="chips" role="tablist" aria-label="Filter orders">
        <button type="button" class="chip active" data-filter="all">All<span class="n"><?= $stats['count'] ?></span></button>
        <?php if ($stats['waiting'] > 0): ?><button type="button" class="chip" data-filter="paid">Ready for pick up<span class="n"><?= $stats['waiting'] ?></span></button><?php endif; ?>
        <?php if ($stats['done'] > 0): ?><button type="button" class="chip" data-filter="done">Picked up<span class="n"><?= $stats['done'] ?></span></button><?php endif; ?>
    </div>

    <div class="res-list">
    <?php foreach ($orders as $o):
        [$label, $color] = resStatusMeta($o['status'], $o['payment_status']);
        $key      = resGroupKey($o);                       // paid | done
        $id       = (int)$o['id'];
        $picked   = ($key === 'done');
        $resCode  = $o['reservation_code'] ?: ('RSV-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT));
        $orderTag = $o['order_code'] !== '' ? $o['order_code'] : ('Order #' . str_pad((string)(int)$o['order_id'], 5, '0', STR_PAD_LEFT));
        $qtyAll   = array_sum(array_map(fn($i) => (int)$i['quantity'], $o['items']));
    ?>
        <article class="res-card" id="order-<?= $id ?>" data-group="<?= $key ?>">
            <div class="res-top">
                <div>
                    <div class="res-code"><?= htmlspecialchars($orderTag) ?></div>
                    <div class="res-created">Reservation <?= htmlspecialchars($resCode) ?><?= !empty($o['paid_at']) ? ' · paid ' . htmlspecialchars(date('M j, Y · g:i A', strtotime($o['paid_at']))) : '' ?></div>
                </div>
                <span class="pill" style="color:<?= htmlspecialchars($color) ?>"><i></i><?= htmlspecialchars($label) ?></span>
            </div>

            <div class="res-meta">
                <div><span class="k">Branch</span><span class="v"><?= ucfirst(htmlspecialchars($o['branch'])) ?></span></div>
                <div><span class="k">Pick-up</span><span class="v"><?= htmlspecialchars(resFormatDate($o['reservation_date'])) ?><br><?= htmlspecialchars(resFormatTime($o['reservation_time'])) ?></span></div>
                <div><span class="k">Items</span><span class="v"><?= $qtyAll ?> item<?= $qtyAll !== 1 ? 's' : '' ?></span></div>
                <div><span class="k">Total paid</span><span class="v gold">₱<?= number_format((float)$o['total'], 2) ?></span></div>
            </div>

            <div class="res-items">
                <?php foreach ($o['items'] as $it): ?>
                <div class="res-item">
                    <span><?= htmlspecialchars($it['product_name']) ?><span class="q">×<?= (int)$it['quantity'] ?></span></span>
                    <span class="p">₱<?= number_format($it['line_total'], 2) ?></span>
                </div>
                <?php if (!empty($it['addon_list'])): ?>
                <div class="res-addon">+ <?= htmlspecialchars(implode(', ', array_column($it['addon_list'], 'name'))) ?></div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <ol class="steps" aria-label="Order progress">
                <li class="done">Reserved</li>
                <li class="done">Paid</li>
                <li class="<?= $picked ? 'done' : 'now' ?>">Picked up</li>
            </ol>

            <div class="pay-line">
                <span>Paid with <strong><?= htmlspecialchars(resPaymentSummary($o)) ?></strong></span>
                <span><?= $picked
                    ? ('Picked up' . (!empty($o['picked_up_at']) ? ' ' . htmlspecialchars(date('M j, Y · g:i A', strtotime($o['picked_up_at']))) : ''))
                    : 'Waiting at the counter' ?></span>
            </div>

            <div class="res-actions">
                <button type="button" class="btn ghost" onclick="openReservationReceipt(RECEIPTS['r<?= $id ?>'])">View receipt</button>
                <a class="btn ghost" href="my-reservations.php#res-<?= $id ?>">Reservation details</a>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
    <p class="no-match" id="noMatch">Nothing in this group.</p>
<?php endif; ?>
</main>

<footer><p>© 2026 <span>My AyosCoffeeNegosyo</span> — All rights reserved.</p></footer>

<?php include 'reservation-receipt.inc.php'; ?>

<script>
const RECEIPTS = <?= resJson($receipts ?: new stdClass()) ?>;

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
</script>
</body>
</html>