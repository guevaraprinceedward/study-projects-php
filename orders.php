<?php
/**
 * orders.php (v3) — the customer's order history.
 * Shows order type, live status, every item with size / hot-iced / sugar / add-ons / note,
 * delivery details, a receipt for each order, and the tracking link.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION["user"])) { header("Location: log-in.php"); exit(); }

include 'config.php';
include_once 'order-lib.php';
ordEnsureSchema($conn);

$userId = ordValidUserId($conn);
if ($userId <= 0) { header("Location: log-in.php"); exit(); }
$userName = (string)($_SESSION['user']['username'] ?? '');

$st = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC, id DESC");
$st->bind_param('i', $userId);
$st->execute();
$orders = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

$receipts = [];
foreach ($orders as &$o) {
    $o['_r'] = ordReceiptData($conn, $o, $userName);
    $receipts['o' . (int)$o['id']] = $o['_r'];
}
unset($o);
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders — SIPPERÉ Café</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;--gold:#c9a84c;--gold-dim:#8a6f2e;--green:#4a7a3a;--green-lt:#6aaa52;--red:#8b2e2e;--cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8}
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
.order-count{margin-top:10px;font-size:13px;color:var(--muted);letter-spacing:.05em}
.main{position:relative;z-index:1;max-width:820px;margin:0 auto;padding:0 32px 80px}
.order-card{background:var(--card);border:1px solid var(--border);border-radius:4px;margin-bottom:18px;overflow:hidden;transition:border-color .25s}
.order-card:hover{border-color:var(--gold-dim)}
.order-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;padding:18px 24px;border-bottom:1px solid var(--border);cursor:pointer;user-select:none}
.order-meta{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.order-id{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:700;color:var(--cream);letter-spacing:.04em}
.order-date{font-size:12px;color:var(--muted)}
.svc-tag{font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;padding:3px 9px;border-radius:3px;background:var(--gold);color:#1a1400}
.status-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600;letter-spacing:.1em;text-transform:uppercase}
.status-dot{width:6px;height:6px;border-radius:50%}
.order-right{display:flex;align-items:center;gap:16px}
.order-total{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:var(--gold)}
.order-total small{font-family:'Jost',sans-serif;font-size:11px;color:var(--muted);font-weight:400}
.toggle-icon{color:var(--muted);transition:transform .25s}
.order-card.open .toggle-icon{transform:rotate(180deg)}
.order-body{display:none;padding:20px 24px}
.order-card.open .order-body{display:block}
.dlv-box{background:var(--surface);border:1px solid var(--border);border-radius:3px;padding:12px 14px;margin-bottom:16px;font-size:12.5px;color:var(--muted);line-height:1.7}
.dlv-box b{color:var(--cream);font-weight:500}
.items-table{width:100%;border-collapse:collapse;font-size:13.5px;margin-bottom:14px}
.items-table th{text-align:left;font-size:10.5px;font-weight:500;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);padding:0 0 10px;border-bottom:1px solid var(--border)}
.items-table th:last-child,.items-table td:last-child{text-align:right}
.items-table td{padding:10px 0;border-bottom:1px solid rgba(44,44,36,.5);color:var(--text);vertical-align:top}
.items-table td:last-child{color:var(--gold)}
.items-table .item-qty{color:var(--muted);font-size:12px}
.sp{font-size:11.5px;color:var(--gold-dim);font-style:italic;margin-top:2px}
.fee-row{display:flex;justify-content:space-between;font-size:12.5px;color:var(--muted);margin-bottom:6px}
.order-footer-row{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}
.order-notes{font-size:12.5px;color:var(--muted);background:var(--surface);border:1px solid var(--border);border-radius:3px;padding:8px 12px;font-style:italic;max-width:400px}
.order-total-line{text-align:right}
.order-total-line .label{font-size:12px;color:var(--muted);margin-bottom:2px;letter-spacing:.06em}
.order-total-line .amount{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--gold)}
.order-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;padding-top:16px;border-top:1px dashed var(--border)}
.btn{display:inline-flex;align-items:center;padding:10px 20px;border-radius:3px;font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:.08em;text-transform:uppercase;text-decoration:none;cursor:pointer}
.btn.primary{background:var(--green);border:1px solid var(--green);color:#fff}.btn.primary:hover{background:var(--green-lt)}
.btn.ghost{background:transparent;border:1px solid var(--border);color:var(--muted)}.btn.ghost:hover{border-color:var(--gold-dim);color:var(--gold)}
.empty-state{position:relative;z-index:1;text-align:center;padding:80px 20px;max-width:420px;margin:0 auto}
.empty-icon{width:80px;height:80px;margin:0 auto 24px;border:1px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--muted);opacity:.5}
.empty-state h3{font-family:'Cormorant Garamond',serif;font-size:28px;color:var(--cream);margin-bottom:10px}
.empty-state p{font-size:14px;color:var(--muted);line-height:1.7;margin-bottom:28px}
footer{position:relative;z-index:1;border-top:1px solid var(--border);padding:28px 32px;text-align:center}
footer p{font-size:12px;color:var(--muted)}footer p span{color:var(--gold-dim)}
@media(max-width:640px){.main{padding:0 16px 60px}.header-inner{padding:0 16px}nav a:not(.nav-back){display:none}.order-header{flex-direction:column;align-items:flex-start}.order-right{width:100%;justify-content:space-between}}
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
            <a href="menu.php">Menu</a><a href="cart.php">Cart</a><a href="profile.php">Profile</a><a href="log-out.php">Logout</a>
            <a href="menu.php" class="nav-back">← Back to Menu</a>
        </nav>
    </div>
</header>

<div class="page-hero">
    <div class="hero-eyebrow">Order history</div>
    <h1>My <em>Orders</em></h1>
    <?php if ($orders): ?><p class="order-count"><?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?> placed</p><?php endif; ?>
</div>

<?php if (!$orders): ?>
<div class="empty-state">
    <div class="empty-icon"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
    <h3>No orders yet</h3>
    <p>You haven't placed any orders.<br>Browse the menu and treat yourself!</p>
    <a href="menu.php" class="btn primary" style="padding:12px 28px">Browse Menu</a>
</div>
<?php else: ?>
<div class="main">
<?php foreach ($orders as $i => $o): $r = $o['_r']; $id = (int)$o['id']; ?>
    <div class="order-card" id="order-<?= $id ?>">
        <div class="order-header" onclick="toggleOrder(<?= $id ?>)">
            <div class="order-meta">
                <div class="order-id"><?= $h($r['code']) ?></div>
                <?php if ($r['service_label']): ?><span class="svc-tag"><?= $h($r['service_label']) ?></span><?php endif; ?>
                <div class="status-badge" style="background:<?= $h($r['status_color']) ?>22;color:<?= $h($r['status_color']) ?>;border:1px solid <?= $h($r['status_color']) ?>44">
                    <div class="status-dot" style="background:<?= $h($r['status_color']) ?>"></div><?= $h($r['status_label']) ?>
                </div>
                <div class="order-date"><?= $h($r['created']) ?></div>
            </div>
            <div class="order-right">
                <div class="order-total"><small>₱</small><?= number_format($r['total'], 2) ?></div>
                <div class="toggle-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></div>
            </div>
        </div>
        <div class="order-body">
            <?php if ($r['service'] === 'delivery'): ?>
            <div class="dlv-box">
                <b>Deliver to:</b> <?= $h($r['address']) ?><br>
                <?php if ($o['delivery_phone']): ?><b>Contact:</b> <?= $h($o['delivery_phone']) ?><br><?php endif; ?>
                <?php if ($r['scheduled']): ?><b>Scheduled for:</b> <?= $h($r['scheduled']) ?><br><?php endif; ?>
                <?php if (!empty($o['rider_name']) && $r['status_key'] === 'out_for_delivery'): ?><b>Rider:</b> <?= $h($o['rider_name']) ?> <?= $h($o['rider_phone']) ?><br><?php endif; ?>
                <?php if (!empty($o['reject_reason']) && $r['status_key'] === 'cancelled'): ?><b>Reason:</b> <?= $h($o['reject_reason']) ?><?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($r['items']): ?>
            <table class="items-table">
                <thead><tr><th>Item</th><th>Qty</th><th>Unit</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($r['items'] as $it): $unit = $it['unit'] + $it['addons_total']; ?>
                <tr>
                    <td><?= $h($it['name']) ?>
                        <?php if ($it['spec'] !== ''): ?><div class="sp"><?= $h($it['spec']) ?></div><?php endif; ?>
                        <?php if ($it['addons']): ?><div class="sp">+ <?= $h(implode(', ', $it['addons'])) ?></div><?php endif; ?>
                        <?php if ($it['note'] !== ''): ?><div class="sp">“<?= $h($it['note']) ?>”</div><?php endif; ?>
                    </td>
                    <td><span class="item-qty">×<?= (int)$it['qty'] ?></span></td>
                    <td>₱<?= number_format($unit, 2) ?></td>
                    <td>₱<?= number_format($it['line'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><p style="font-size:13px;color:var(--muted);margin-bottom:16px">No item details available.</p><?php endif; ?>

            <?php if ($r['fee'] > 0): ?><div class="fee-row"><span>Delivery fee</span><span>₱<?= number_format($r['fee'], 2) ?></span></div><?php endif; ?>
            <div class="order-footer-row">
                <?php if ($r['notes'] !== ''): ?><div class="order-notes">📝 <?= $h($r['notes']) ?></div><?php else: ?><div></div><?php endif; ?>
                <div class="order-total-line"><div class="label">ORDER TOTAL</div><div class="amount">₱<?= number_format($r['total'], 2) ?></div></div>
            </div>
            <div class="order-actions">
                <button type="button" class="btn ghost" onclick="openOrderReceipt(RECEIPTS['o<?= $id ?>'])">View receipt</button>
                <?php if ($r['track_url'] && !in_array($r['status_key'], ['completed', 'cancelled'], true)): ?><a class="btn primary" href="<?= $h($r['track_url']) ?>">Track order</a>
                <?php elseif ($r['track_url']): ?><a class="btn ghost" href="<?= $h($r['track_url']) ?>">Order status</a><?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<footer><p>© 2026 <span>SIPPERÉ Café</span> — All rights reserved.</p></footer>

<script>
var ORD_LOGGED_IN = true;
var RECEIPTS = <?= ordJson($receipts ?: new stdClass()) ?>;
function toggleOrder(id){ document.getElementById('order-'+id).classList.toggle('open'); }
<?php if ($orders): ?>
document.addEventListener('DOMContentLoaded', function(){
    var hash = location.hash.match(/^#order-(\d+)$/);
    toggleOrder(hash ? hash[1] : <?= (int)$orders[0]['id'] ?>);
});
<?php endif; ?>
</script>
<?php include 'order-receipt.inc.php'; ?>
</body>
</html>