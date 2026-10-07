<?php
/**
 * order-track.php — customer order tracking (works for guests too, via the private link).
 *   order-track.php?t=TOKEN          page
 *   order-track.php?t=TOKEN&json=1   status snapshot (the page polls this every 8s)
 */
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';
include_once 'delivery-lib.php';
dlvEnsureSchema($conn);

$token = preg_replace('/[^a-f0-9]/', '', strtolower((string)($_GET['t'] ?? '')));
$o = null;
if (strlen($token) === 16) {
    $st = $conn->prepare("SELECT * FROM orders WHERE track_token = ? LIMIT 1");
    $st->bind_param('s', $token);
    $st->execute();
    $o = $st->get_result()->fetch_assoc();
    $st->close();
}
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo json_encode($o ? dlvPayload($o) : ['error' => 'Order not found.']);
    exit();
}

$items = [];
if ($o) {
    $st = $conn->prepare("SELECT oi.quantity, oi.price, oi.addons, oi.addons_total, oi.spec, oi.item_note, COALESCE(p.name, CONCAT('Product #', oi.product_id)) AS name
                          FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
    $st->bind_param('i', $o['id']);
    $st->execute();
    $items = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
}
$svcLabels = custOrderTypesSafe();
function custOrderTypesSafe(): array { return ['dine-in' => 'Dine-in', 'take-out' => 'Take-out', 'pick-up' => 'Pick-up', 'delivery' => 'Delivery']; }
$svc  = $o['service_type'] ?? '';
$data = $o ? dlvPayload($o) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Track Order — SIPPERÉ Café</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;--gold:#c9a84c;--gold-dim:#8a6f2e;--green:#4a7a3a;--green-lt:#6aaa52;--red-lt:#c0392b;--cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8}
body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
header{border-bottom:1px solid var(--border);padding:0 24px;height:64px;display:flex;align-items:center;justify-content:space-between}
.brand{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream);text-decoration:none}.brand span{color:var(--gold)}
header a.l{font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);text-decoration:none}header a.l:hover{color:var(--gold)}
.wrap{max-width:560px;margin:0 auto;padding:36px 20px 80px}
.eyebrow{text-align:center;font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:var(--gold)}
h1{font-family:'Cormorant Garamond',serif;font-size:38px;color:var(--cream);text-align:center;margin:8px 0 4px}
.code{text-align:center;font-size:13px;color:var(--gold-dim);letter-spacing:.08em;margin-bottom:24px}
.card{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:22px 22px;margin-bottom:16px}
.msg{font-size:15px;line-height:1.65;color:var(--cream);text-align:center}
.eta{display:none;text-align:center;margin-top:14px;padding-top:14px;border-top:1px dashed var(--border)}
.eta b{display:block;font-family:'Cormorant Garamond',serif;font-size:42px;color:var(--gold);line-height:1}
.eta small{font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted)}
.steps{list-style:none;display:flex;margin:6px 0 0}
.steps li{position:relative;flex:1;text-align:center;font-size:10px;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);padding-top:20px}
.steps li::before{content:'';position:absolute;top:0;left:50%;width:11px;height:11px;margin-left:-5.5px;border-radius:50%;border:1px solid var(--border);background:var(--surface);z-index:1}
.steps li::after{content:'';position:absolute;top:5px;left:-50%;width:100%;height:1px;background:var(--border)}
.steps li:first-child::after{display:none}
.steps li.done{color:var(--green-lt)}.steps li.done::before,.steps li.done::after{background:var(--green-lt);border-color:var(--green-lt)}
.steps li.now{color:var(--gold)}.steps li.now::before{background:var(--gold);border-color:var(--gold);box-shadow:0 0 0 4px rgba(201,168,76,.15)}.steps li.now::after{background:var(--green-lt)}
.cancelled .steps{display:none}.cancelled .msg{color:#e07b7b}
.row{display:flex;justify-content:space-between;gap:12px;font-size:13px;padding:5px 0}
.row .k{color:var(--muted)}.row .v{color:var(--cream);text-align:right}
.item{padding:8px 0;border-bottom:1px solid rgba(44,44,36,.5)}.item:last-child{border:none}
.item .n{display:flex;justify-content:space-between;font-size:13.5px;color:var(--cream)}
.item .s{font-size:11.5px;color:var(--gold-dim);margin-top:2px}
.total{display:flex;justify-content:space-between;font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--gold);font-weight:700;margin-top:10px}
.none{text-align:center;padding:60px 20px;color:var(--muted)}
.rider{display:none}
</style>
</head>
<body>
<header><a class="brand" href="index.php">SIPPERÉ <span>Café</span></a><a class="l" href="menu.php">Menu</a></header>
<div class="wrap">
<?php if (!$o): ?>
    <div class="none"><h1>Order not found</h1><p style="margin-top:10px">Please open the tracking link from your order confirmation.</p></div>
<?php else:
    $sub = 0.0; ?>
    <div class="eyebrow"><?= htmlspecialchars($svcLabels[$svc] ?? 'Order') ?> order</div>
    <h1>Track your order</h1>
    <div class="code"><?= htmlspecialchars($o['receipt_code'] ?: ('#' . str_pad((string)$o['id'], 5, '0', STR_PAD_LEFT))) ?></div>

    <div class="card" id="statusCard">
        <ol class="steps" id="steps"></ol>
        <div class="msg" id="msg" style="margin-top:18px"></div>
        <div class="eta" id="eta"><small>Estimated ready in</small><b id="etaClock">--:--</b></div>
        <div class="rider card" id="rider" style="margin:16px 0 0;padding:12px;text-align:center;font-size:13px"></div>
    </div>

    <div class="card">
        <?php if ($svc === 'delivery'): ?>
        <div class="row"><span class="k">Deliver to</span><span class="v"><?= htmlspecialchars((string)$o['delivery_address']) ?></span></div>
        <?php if (!empty($o['delivery_phone'])): ?><div class="row"><span class="k">Contact</span><span class="v"><?= htmlspecialchars($o['delivery_phone']) ?></span></div><?php endif; ?>
        <?php if (!empty($o['scheduled_for'])): ?><div class="row"><span class="k">Scheduled for</span><span class="v"><?= htmlspecialchars(date('D, M j · g:i A', strtotime($o['scheduled_for']))) ?></span></div><?php endif; ?>
        <?php endif; ?>
        <div class="row"><span class="k">Placed</span><span class="v"><?= htmlspecialchars(date('M j, Y · g:i A', strtotime($o['created_at']))) ?></span></div>
        <div class="row"><span class="k">Payment</span><span class="v"><?= htmlspecialchars(ucfirst((string)($o['payment_method'] ?? 'cash'))) ?></span></div>
    </div>

    <div class="card">
        <?php foreach ($items as $it):
            $line = ((float)$it['price'] + (float)$it['addons_total']) * (int)$it['quantity']; $sub += $line;
            $ad = json_decode((string)$it['addons'], true); $ad = is_array($ad) ? $ad : [];
            $sp = trim((string)$it['spec']) . ($ad ? ' · + ' . implode(', ', array_map(fn($a) => is_array($a) ? ($a['name'] ?? '') : $a, $ad)) : ''); ?>
        <div class="item">
            <div class="n"><span><?= htmlspecialchars($it['name']) ?> ×<?= (int)$it['quantity'] ?></span><span>₱<?= number_format($line, 2) ?></span></div>
            <?php if (trim($sp, ' ·') !== ''): ?><div class="s"><?= htmlspecialchars(trim($sp, ' ·')) ?></div><?php endif; ?>
            <?php if (!empty($it['item_note'])): ?><div class="s">“<?= htmlspecialchars($it['item_note']) ?>”</div><?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php if ((float)$o['delivery_fee'] > 0): ?><div class="row"><span class="k">Delivery fee</span><span class="v">₱<?= number_format((float)$o['delivery_fee'], 2) ?></span></div><?php endif; ?>
        <div class="total"><span>Total</span><span>₱<?= number_format((float)$o['total'], 2) ?></span></div>
    </div>
    <p style="text-align:center;font-size:12px;color:var(--muted)">This page updates by itself. Keep it open.</p>

<script>
var TOKEN = <?= json_encode($token) ?>, DATA = <?= json_encode($data) ?>, skew = 0, tickT = null, pollT = null;
function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
function render(d){
    var idx = d.flow.map(function(f){return f.key}).indexOf(d.status);
    document.getElementById('statusCard').classList.toggle('cancelled', d.status === 'cancelled');
    document.getElementById('steps').innerHTML = d.flow.map(function(f,i){
        return '<li class="'+(d.status==='completed'||i<idx?'done':(i===idx?'now':''))+'">'+esc(f.label)+'</li>';
    }).join('');
    document.getElementById('msg').textContent = d.message;
    var r = document.getElementById('rider');
    if (d.status === 'out_for_delivery' && d.rider_name) { r.style.display='block'; r.textContent = 'Rider: ' + d.rider_name + (d.rider_phone ? ' · ' + d.rider_phone : ''); } else r.style.display='none';
    skew = d.now - Math.floor(Date.now()/1000);
    clearInterval(tickT);
    var eta = document.getElementById('eta');
    if (d.eta_at) { eta.style.display='block'; tick(d.eta_at); tickT = setInterval(function(){tick(d.eta_at)}, 1000); } else eta.style.display='none';
    if (d.done) clearInterval(pollT);
}
function tick(at){
    var left = at - (Math.floor(Date.now()/1000) + skew), el = document.getElementById('etaClock');
    if (left <= 0) { el.textContent = 'Any moment now'; el.style.fontSize = '24px'; return; }
    el.style.fontSize = ''; el.textContent = String(Math.floor(left/60)).padStart(2,'0') + ':' + String(left%60).padStart(2,'0');
}
function poll(){
    fetch('order-track.php?t='+TOKEN+'&json=1',{credentials:'same-origin'}).then(function(r){return r.json()}).then(function(d){ if(!d.error) render(d); }).catch(function(){});
}
render(DATA);
if (!DATA.done) pollT = setInterval(poll, 8000);
</script>
<?php endif; ?>
</div>
</body>
</html>