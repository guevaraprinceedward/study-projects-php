<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user"])) {
    header("Location: log-in.php");
    exit();
}

include 'config.php';

$user   = $_SESSION['user'];
$userId = (int)($user['id'] ?? 0);

// ── FETCH ORDERS ──────────────────────────────────────────────────────────────
$orders = [];

$res = $conn->query("
    SELECT o.id, o.total, o.status, o.notes, o.created_at
    FROM orders o
    WHERE o.user_id = $userId
    ORDER BY o.created_at DESC
");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Fetch items for each order
foreach ($orders as &$order) {
    $oid  = (int)$order['id'];
    $items = [];

    $iRes = $conn->query("
        SELECT oi.quantity, oi.price AS unit_price,
               COALESCE(p.name, CONCAT('Product #', oi.product_id)) AS name
        FROM order_items oi
        LEFT JOIN products p ON p.id = oi.product_id
        WHERE oi.order_id = $oid
    ");

    if ($iRes) {
        while ($iRow = $iRes->fetch_assoc()) {
            $items[] = $iRow;
        }
    }

    $order['items'] = $items;
}
unset($order);

// ── STATUS HELPERS ────────────────────────────────────────────────────────────
function statusLabel($s) {
    return match(strtolower($s)) {
        'pending'   => 'Pending',
        'confirmed' => 'Confirmed',
        'preparing' => 'Preparing',
        'ready'     => 'Ready',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        default     => ucfirst($s),
    };
}

function statusColor($s) {
    return match(strtolower($s)) {
        'pending'   => '#c9a84c',   // gold
        'confirmed' => '#5b8fd4',   // blue
        'preparing' => '#c9a84c',   // gold
        'ready'     => '#6aaa52',   // green
        'completed' => '#4a7a3a',   // dark green
        'cancelled' => '#c0392b',   // red
        default     => '#6b6b58',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders — My Restaurant</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #0b0b09;
            --surface:  #131310;
            --card:     #1a1a16;
            --border:   #2c2c24;
            --gold:     #c9a84c;
            --gold-dim: #8a6f2e;
            --green:    #4a7a3a;
            --green-lt: #6aaa52;
            --red:      #8b2e2e;
            --cream:    #f0ead8;
            --muted:    #6b6b58;
            --text:     #e8e4d8;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Jost', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed; inset: 0;
            background:
                radial-gradient(ellipse 70% 50% at 10% 0%, rgba(201,168,76,0.06) 0%, transparent 55%),
                radial-gradient(ellipse 50% 70% at 90% 100%, rgba(74,122,58,0.07) 0%, transparent 55%);
            pointer-events: none; z-index: 0;
        }

        /* ── HEADER ── */
        header {
            position: sticky; top: 0; z-index: 100;
            background: rgba(11,11,9,0.88);
            backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--border);
        }
        .header-inner {
            max-width: 1100px; margin: 0 auto;
            padding: 0 32px; height: 68px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .brand-icon {
            width: 36px; height: 36px;
            border: 1px solid var(--gold-dim); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }
        .brand-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px; font-weight: 600;
            color: var(--cream); letter-spacing: 0.04em;
        }
        .brand-name span { color: var(--gold); }
        nav { display: flex; align-items: center; gap: 6px; }
        nav a {
            font-size: 12.5px; font-weight: 500;
            letter-spacing: 0.1em; text-transform: uppercase;
            color: var(--muted); text-decoration: none;
            padding: 8px 14px; border-radius: 3px;
            transition: color 0.2s, background 0.2s;
        }
        nav a:hover { color: var(--cream); background: rgba(255,255,255,0.04); }
        nav a.active { color: var(--gold); }
        .nav-back {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 18px !important;
            border: 1px solid var(--border) !important;
            border-radius: 3px; color: var(--muted) !important;
            transition: all 0.2s !important;
        }
        .nav-back:hover {
            border-color: var(--gold-dim) !important;
            color: var(--gold) !important;
            background: transparent !important;
        }

        /* ── PAGE HERO ── */
        .page-hero {
            position: relative; z-index: 1;
            text-align: center;
            padding: 60px 32px 48px;
        }
        .hero-eyebrow {
            display: inline-flex; align-items: center; gap: 10px;
            font-size: 11px; letter-spacing: 0.2em;
            text-transform: uppercase; color: var(--gold);
            margin-bottom: 16px;
        }
        .hero-eyebrow::before, .hero-eyebrow::after {
            content: ''; width: 28px; height: 1px; background: var(--gold-dim);
        }
        .page-hero h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(36px, 5vw, 56px);
            font-weight: 700; color: var(--cream);
        }
        .page-hero h1 em { font-style: italic; color: var(--gold); }
        .order-count {
            margin-top: 10px; font-size: 13px;
            color: var(--muted); letter-spacing: 0.05em;
        }

        /* ── MAIN CONTENT ── */
        .main {
            position: relative; z-index: 1;
            max-width: 820px; margin: 0 auto;
            padding: 0 32px 80px;
        }

        /* ── ORDER CARD ── */
        .order-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 4px;
            margin-bottom: 18px;
            overflow: hidden;
            animation: slideIn 0.35s ease both;
            transition: border-color 0.25s;
        }
        .order-card:hover { border-color: var(--gold-dim); }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Card header */
        .order-header {
            display: flex; align-items: center;
            justify-content: space-between; flex-wrap: wrap; gap: 12px;
            padding: 18px 24px;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            user-select: none;
        }
        .order-header:hover { background: rgba(255,255,255,0.015); }

        .order-meta { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }

        .order-id {
            font-family: 'Cormorant Garamond', serif;
            font-size: 18px; font-weight: 700;
            color: var(--cream); letter-spacing: 0.04em;
        }

        .order-date { font-size: 12px; color: var(--muted); }

        .status-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px; font-weight: 600;
            letter-spacing: 0.1em; text-transform: uppercase;
        }
        .status-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
        }

        .order-right {
            display: flex; align-items: center; gap: 16px;
        }
        .order-total {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px; font-weight: 700;
            color: var(--gold);
        }
        .order-total small {
            font-family: 'Jost', sans-serif;
            font-size: 11px; color: var(--muted); font-weight: 400;
        }

        .toggle-icon {
            color: var(--muted); transition: transform 0.25s;
        }
        .order-card.open .toggle-icon { transform: rotate(180deg); }

        /* Card body */
        .order-body {
            display: none;
            padding: 20px 24px;
        }
        .order-card.open .order-body { display: block; }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
            margin-bottom: 16px;
        }
        .items-table th {
            text-align: left;
            font-size: 10.5px;
            font-weight: 500;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--muted);
            padding: 0 0 10px;
            border-bottom: 1px solid var(--border);
        }
        .items-table th:last-child { text-align: right; }
        .items-table td {
            padding: 10px 0;
            border-bottom: 1px solid rgba(44,44,36,0.5);
            color: var(--text);
        }
        .items-table td:last-child { text-align: right; color: var(--gold); }
        .items-table td .item-qty { color: var(--muted); font-size: 12px; }

        .order-footer-row {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 12px;
        }

        .order-notes {
            font-size: 12.5px; color: var(--muted);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 3px;
            padding: 8px 12px;
            font-style: italic;
            max-width: 400px;
        }

        .order-total-line {
            text-align: right;
        }
        .order-total-line .label {
            font-size: 12px; color: var(--muted);
            margin-bottom: 2px; letter-spacing: 0.06em;
        }
        .order-total-line .amount {
            font-family: 'Cormorant Garamond', serif;
            font-size: 26px; font-weight: 700;
            color: var(--gold);
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            position: relative; z-index: 1;
            text-align: center;
            padding: 80px 20px;
            max-width: 420px; margin: 0 auto;
        }
        .empty-icon {
            width: 80px; height: 80px; margin: 0 auto 24px;
            border: 1px solid var(--border); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: var(--muted); opacity: 0.5;
        }
        .empty-state h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px; color: var(--cream); margin-bottom: 10px;
        }
        .empty-state p {
            font-size: 14px; color: var(--muted);
            line-height: 1.7; margin-bottom: 28px;
        }
        .browse-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 28px;
            background: var(--green); border-radius: 3px;
            font-family: 'Jost', sans-serif;
            font-size: 13px; font-weight: 500;
            letter-spacing: 0.08em; text-transform: uppercase;
            color: #fff; text-decoration: none;
            transition: background 0.2s;
        }
        .browse-btn:hover { background: var(--green-lt); }

        /* footer */
        footer {
            position: relative; z-index: 1;
            border-top: 1px solid var(--border);
            padding: 28px 32px; text-align: center;
        }
        footer p { font-size: 12px; color: var(--muted); letter-spacing: 0.06em; }
        footer p span { color: var(--gold-dim); }

        /* responsive */
        @media (max-width: 640px) {
            .main { padding: 0 16px 60px; }
            .header-inner { padding: 0 16px; }
            nav a:not(.nav-back) { display: none; }
            .order-header { flex-direction: column; align-items: flex-start; }
            .order-right { width: 100%; justify-content: space-between; }
        }
    </style>
</head>
<body>

<!-- ══ HEADER ══ -->
<header>
    <div class="header-inner">
        <a href="index.php" class="brand">
            <div class="brand-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                     stroke="#c9a84c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 11l19-9-9 19-2-8-8-2z"/>
                </svg>
            </div>
            <span class="brand-name">My <span>Restaurant</span></span>
        </a>
        <nav>
            <a href="index.php">Menu</a>
            <a href="cart.php">Cart</a>
            <a href="profile.php">Profile</a>
            <a href="log-out.php">Logout</a>
            <a href="index.php" class="nav-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
                </svg>
                Back to Menu
            </a>
        </nav>
    </div>
</header>

<!-- ══ PAGE HERO ══ -->
<div class="page-hero">
    <div class="hero-eyebrow">Order history</div>
    <h1>My <em>Orders</em></h1>
    <?php if (!empty($orders)): ?>
        <p class="order-count"><?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?> placed</p>
    <?php endif; ?>
</div>

<?php if (empty($orders)): ?>
<!-- ══ EMPTY STATE ══ -->
<div class="empty-state">
    <div class="empty-icon">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 11l3 3L22 4"/>
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
        </svg>
    </div>
    <h3>No orders yet</h3>
    <p>You haven't placed any orders.<br>Browse the menu and treat yourself!</p>
    <a href="index.php" class="browse-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
        </svg>
        Browse Menu
    </a>
</div>

<?php else: ?>
<!-- ══ ORDER LIST ══ -->
<div class="main">
    <?php foreach ($orders as $i => $order):
        $statusColor = statusColor($order['status']);
        $statusLabel = statusLabel($order['status']);
        $date = date('M j, Y · g:i A', strtotime($order['created_at']));
    ?>
    <div class="order-card" id="order-<?= $order['id'] ?>" style="animation-delay: <?= $i * 0.07 ?>s"
         onclick="toggleOrder(<?= $order['id'] ?>)">

        <!-- Header (always visible) -->
        <div class="order-header">
            <div class="order-meta">
                <div class="order-id">#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></div>
                <div class="order-date"><?= $date ?></div>
                <div class="status-badge"
                     style="background:<?= $statusColor ?>22; color:<?= $statusColor ?>; border: 1px solid <?= $statusColor ?>44;">
                    <div class="status-dot" style="background:<?= $statusColor ?>;"></div>
                    <?= $statusLabel ?>
                </div>
            </div>
            <div class="order-right">
                <div class="order-total">
                    <small>₱</small><?= number_format($order['total'], 2) ?>
                </div>
                <div class="toggle-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Body (toggle) -->
        <div class="order-body">
            <?php if (!empty($order['items'])): ?>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order['items'] as $item):
                        $unit  = (float)($item['unit_price'] ?? 0);
                        $qty   = (int)$item['quantity'];
                        $sub   = $unit * $qty;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><span class="item-qty">×<?= $qty ?></span></td>
                        <td>₱<?= number_format($unit, 2) ?></td>
                        <td>₱<?= number_format($sub, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p style="font-size:13px; color:var(--muted); margin-bottom:16px;">No item details available.</p>
            <?php endif; ?>

            <div class="order-footer-row">
                <?php if (!empty($order['notes'])): ?>
                <div class="order-notes">
                    📝 <?= htmlspecialchars($order['notes']) ?>
                </div>
                <?php else: ?>
                <div></div>
                <?php endif; ?>

                <div class="order-total-line">
                    <div class="label">ORDER TOTAL</div>
                    <div class="amount">₱<?= number_format($order['total'], 2) ?></div>
                </div>
            </div>
        </div>

    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ══ FOOTER ══ -->
<footer>
    <p>© 2026 <span>My AyosCoffeeNegosyo</span> — All rights reserved.</p>
</footer>

<script>
function toggleOrder(id) {
    const card = document.getElementById('order-' + id);
    card.classList.toggle('open');
}

// Auto-open the most recent order on load
<?php if (!empty($orders)): ?>
document.addEventListener('DOMContentLoaded', () => {
    toggleOrder(<?= $orders[0]['id'] ?>);
});
<?php endif; ?>
</script>

</body>
</html>