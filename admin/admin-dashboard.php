<?php
include 'admin-config.php';
requireAdmin();

$month     = (int)date('n');
$year      = (int)date('Y');
$monthName = date('F');

// Ensure columns
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'pending'");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS branch VARCHAR(20) DEFAULT 'laguna'");
$conn->query("ALTER TABLE order_items ADD COLUMN IF NOT EXISTS price decimal(10,2) DEFAULT NULL");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS branch VARCHAR(20) DEFAULT 'laguna'");

// ── STATS PER BRANCH ──────────────────────────────────────────────────────
function getBranchStats($conn, $branch, $month, $year) {
    $b = $conn->real_escape_string($branch);

    $sales = $conn->query("
        SELECT COALESCE(SUM(oi.quantity * oi.price), 0) AS total
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.branch = '$b'
        AND MONTH(o.created_at) = $month AND YEAR(o.created_at) = $year
        AND o.status != 'cancelled'
    ")->fetch_assoc()['total'] ?? 0;

    $orders = $conn->query("
        SELECT COUNT(*) AS cnt FROM orders
        WHERE branch = '$b'
        AND MONTH(created_at) = $month AND YEAR(created_at) = $year
        AND status != 'cancelled'
    ")->fetch_assoc()['cnt'] ?? 0;

    $customers = $conn->query("
        SELECT COUNT(DISTINCT user_id) AS cnt FROM orders
        WHERE branch = '$b' AND status != 'cancelled'
    ")->fetch_assoc()['cnt'] ?? 0;

    return ['sales' => $sales, 'orders' => $orders, 'customers' => $customers];
}

$laguna = getBranchStats($conn, 'laguna', $month, $year);
$manila = getBranchStats($conn, 'manila', $month, $year);

$totalSales   = $laguna['sales'] + $manila['sales'];
$totalOrders  = $laguna['orders'] + $manila['orders'];
$totalCustomers = $conn->query("SELECT COUNT(DISTINCT user_id) AS cnt FROM orders WHERE status != 'cancelled'")->fetch_assoc()['cnt'] ?? 0;
$avgOrder = $totalOrders > 0 ? $totalSales / $totalOrders : 0;

// ── TOP PRODUCTS ALL BRANCHES ──────────────────────────────────────────────
$topProducts = $conn->query("
    SELECT p.name, p.branch, SUM(oi.quantity) AS units, SUM(oi.quantity * oi.price) AS revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled'
    GROUP BY oi.product_id, p.name, p.branch
    ORDER BY revenue DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

// ── RECENT ORDERS (all branches) ──────────────────────────────────────────
$userCols = [];
$r = $conn->query("SHOW COLUMNS FROM users");
while ($c = $r->fetch_assoc()) $userCols[] = $c['Field'];
$nameCol = 'u.id';
foreach (['name','full_name','username','email'] as $try) {
    if (in_array($try, $userCols)) { $nameCol = "u.$try"; break; }
}

$orderCols = [];
$r = $conn->query("SHOW COLUMNS FROM orders");
while ($c = $r->fetch_assoc()) $orderCols[] = $c['Field'];
$totalCol = in_array('total', $orderCols) ? 'o.total' : '0';
$hasBranch = in_array('branch', $orderCols);

$recentOrders = $conn->query("
    SELECT o.id, o.created_at, o.status, $totalCol AS total,
           $nameCol AS customer,
           " . ($hasBranch ? "o.branch" : "'laguna'") . " AS branch
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// ── LOW STOCK (all branches) ───────────────────────────────────────────────
$lowStock = $conn->query("
    SELECT id, name, stock, reorder_level, branch
    FROM products
    WHERE stock <= reorder_level
    ORDER BY stock ASC
")->fetch_all(MYSQLI_ASSOC);

// ── DAILY TRANSACTIONS ─────────────────────────────────────────────────────
$dailyRaw = $conn->query("
    SELECT DAY(created_at) AS day, COUNT(*) AS txn
    FROM orders
    WHERE MONTH(created_at) = $month AND YEAR(created_at) = $year
    AND status != 'cancelled'
    GROUP BY DAY(created_at)
    ORDER BY day ASC
")->fetch_all(MYSQLI_ASSOC);
$dailyMap = [];
foreach ($dailyRaw as $d) $dailyMap[$d['day']] = $d['txn'];
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$dailyLabels = $dailyValues = [];
for ($d = 1; $d <= $daysInMonth; $d++) {
    $dailyLabels[] = $d;
    $dailyValues[] = $dailyMap[$d] ?? 0;
}

$jsDaily   = json_encode(['labels' => $dailyLabels, 'values' => $dailyValues]);
$jsTopProd = json_encode($topProducts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — AyosCoffeeNegosyo</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;--gold:#c9a84c;--gold-dim:#8a6f2e;--gold-pale:rgba(201,168,76,0.08);--green:#4a7a3a;--green-lt:#6aaa52;--cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8;--red:#c0392b;--red-pale:rgba(192,57,43,0.1);--amber:#d4820a;--amber-pale:rgba(212,130,10,0.1);--sidebar-w:240px}
html{scroll-behavior:smooth}
body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 60% 40% at 15% 0%,rgba(192,57,43,0.04) 0%,transparent 60%),radial-gradient(ellipse 50% 60% at 85% 100%,rgba(201,168,76,0.05) 0%,transparent 60%);pointer-events:none;z-index:0}

/* SIDEBAR */
#sidebar{position:fixed;top:0;left:0;height:100vh;width:var(--sidebar-w);background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;z-index:100;overflow:hidden}
.sb-brand{display:flex;align-items:center;gap:12px;padding:20px 16px 18px;border-bottom:1px solid var(--border);min-height:72px}
.sb-icon{width:36px;height:36px;border:1px solid rgba(192,57,43,0.4);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.sb-title{font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:600;color:var(--cream)}
.sb-title span{color:#e05a5a}
.sb-sub{font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:var(--muted);margin-top:2px}
.sb-nav{flex:1;padding:14px 10px;display:flex;flex-direction:column;gap:2px;overflow-y:auto}
.sb-nav-label{font-size:9px;letter-spacing:0.2em;text-transform:uppercase;color:var(--muted);padding:10px 10px 4px}
.nav-item{display:flex;align-items:center;gap:12px;padding:11px 12px;border-radius:6px;text-decoration:none;color:var(--muted);font-size:13.5px;font-weight:400;transition:background 0.18s,color 0.18s;white-space:nowrap}
.nav-item:hover{background:rgba(255,255,255,0.04);color:var(--text)}
.nav-item.active{background:rgba(192,57,43,0.1);color:#e05a5a}
.nav-icon{width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.sb-footer{padding:10px;border-top:1px solid var(--border)}
.nav-item.logout{color:#e05a5a}
.nav-item.logout:hover{background:rgba(224,90,90,0.08)}

/* MAIN */
#mainContent{margin-left:var(--sidebar-w);flex:1;min-width:0;position:relative;z-index:1;display:flex;flex-direction:column}
.topbar{position:sticky;top:0;z-index:100;background:rgba(11,11,9,0.9);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:0 32px;height:64px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.topbar-left{}
.topbar-sub{font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#e05a5a}
.topbar-title{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream)}
.topbar-right{display:flex;align-items:center;gap:16px}
.topbar-period{font-size:11px;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);padding:5px 12px;border:1px solid var(--border);border-radius:20px}
.topbar-user{font-size:12.5px;color:var(--muted)}
.topbar-user span{color:var(--gold);font-weight:500}

.dash-body{padding:28px 32px 60px;display:flex;flex-direction:column;gap:24px;max-width:1300px}

/* STAT CARDS */
.stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:20px 22px 18px;display:flex;flex-direction:column;gap:6px;position:relative;overflow:hidden;transition:border-color 0.2s,transform 0.2s}
.stat-card:hover{border-color:var(--gold-dim);transform:translateY(-2px)}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--gold-dim),transparent)}
.stat-card.red-accent::before{background:linear-gradient(90deg,rgba(192,57,43,0.6),transparent)}
.stat-icon{width:38px;height:38px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:var(--gold-pale);margin-bottom:4px;flex-shrink:0}
.stat-icon.green-bg{background:rgba(74,122,58,0.12)}
.stat-icon.blue-bg{background:rgba(59,130,246,0.1)}
.stat-icon.red-bg{background:var(--red-pale)}
.stat-label{font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted)}
.stat-value{font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:700;color:var(--cream);line-height:1}
.stat-value.gold{color:var(--gold)}
.stat-sub{font-size:11px;color:var(--muted);margin-top:2px}

/* BRANCH COMPARISON */
.branch-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.branch-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:22px}
.branch-card-title{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--cream);margin-bottom:4px;display:flex;align-items:center;gap:8px}
.branch-tag{font-size:10px;letter-spacing:0.1em;text-transform:uppercase;padding:3px 8px;border-radius:3px;font-family:'Jost',sans-serif;font-weight:600}
.branch-tag.laguna{background:rgba(74,122,58,0.12);color:var(--green-lt)}
.branch-tag.manila{background:rgba(201,168,76,0.08);color:var(--gold)}
.branch-stats{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px}
.branch-stat-item{background:var(--surface);border-radius:4px;padding:12px 14px}
.branch-stat-label{font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);margin-bottom:4px}
.branch-stat-val{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:var(--cream)}
.branch-stat-val.gold{color:var(--gold)}

/* SECTION HEADER */
.section-hd{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.section-hd-line{flex:1;height:1px;background:linear-gradient(90deg,var(--border),transparent)}
.section-title{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--cream)}
.section-sub{font-size:11px;color:var(--muted);letter-spacing:0.06em}

/* TOP PRODUCTS */
.top6-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.top6-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:14px 16px;display:flex;align-items:center;gap:12px;transition:border-color 0.2s}
.top6-rank{font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:var(--border);min-width:28px;text-align:center;line-height:1}
.top6-rank.gold-rank{color:var(--gold-dim)}
.top6-info{flex:1;min-width:0}
.top6-name{font-size:13px;font-weight:500;color:var(--cream);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.top6-cat{font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);margin-top:2px}
.top6-meta{text-align:right;flex-shrink:0}
.top6-rev{font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:600;color:var(--gold)}
.top6-units{font-size:10px;color:var(--muted);margin-top:1px}

/* CHART */
.chart-row{display:grid;grid-template-columns:1fr 1.4fr;gap:16px}
.chart-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:20px 20px 16px}
.chart-card-title{font-size:13px;font-weight:500;color:var(--cream);margin-bottom:3px}
.chart-card-sub{font-size:11px;color:var(--muted);margin-bottom:16px}
.chart-wrap{position:relative}

/* TABLES */
.orders-two-col{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.table-card{background:var(--card);border:1px solid var(--border);border-radius:6px;overflow:hidden}
.table-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
.table-header-title{font-size:13.5px;font-weight:500;color:var(--cream)}
.alert-badge{background:var(--red-pale);color:#e05a5a;font-size:10px;font-weight:600;letter-spacing:0.08em;padding:3px 8px;border-radius:3px;text-transform:uppercase}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:var(--muted);padding:10px 16px;text-align:left;border-bottom:1px solid var(--border);font-weight:500}
tbody td{padding:11px 16px;font-size:13px;border-bottom:1px solid rgba(44,44,36,0.5);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:rgba(255,255,255,0.02)}
.order-status{display:inline-flex;align-items:center;padding:3px 8px;border-radius:3px;font-size:10px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase}
.order-status.pending{background:var(--amber-pale);color:var(--amber)}
.order-status.completed{background:rgba(74,122,58,0.1);color:var(--green-lt)}
.order-status.cancelled{background:var(--red-pale);color:#e05a5a}
.order-status.processing{background:rgba(59,130,246,0.1);color:#60a5fa}
.branch-pill{display:inline-flex;padding:2px 7px;border-radius:3px;font-size:9px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase}
.branch-pill.laguna{background:rgba(74,122,58,0.1);color:var(--green-lt)}
.branch-pill.manila{background:rgba(201,168,76,0.08);color:var(--gold)}
.stock-bar-wrap{display:flex;align-items:center;gap:8px}
.stock-bar-bg{flex:1;height:4px;background:var(--border);border-radius:2px;min-width:40px}
.stock-bar-fill{height:100%;border-radius:2px;background:var(--green);transition:width 0.4s,background 0.4s}
.stock-bar-fill.warn{background:var(--amber)}
.stock-bar-fill.danger{background:var(--red)}
.status-pill{display:inline-flex;align-items:center;padding:3px 8px;border-radius:3px;font-size:10px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase}
.status-pill.critical{background:var(--red-pale);color:#e05a5a}
.status-pill.low{background:var(--amber-pale);color:var(--amber)}
.restock-btn{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;border-radius:3px;border:1px solid var(--green);background:rgba(74,122,58,0.08);color:var(--green-lt);font-family:'Jost',sans-serif;font-size:11px;font-weight:500;letter-spacing:0.06em;cursor:pointer;transition:all 0.18s;white-space:nowrap}
.restock-btn:hover:not(:disabled){background:rgba(74,122,58,0.2);border-color:var(--green-lt);color:#fff}
.restock-btn:disabled{opacity:0.5;cursor:not-allowed}
.restock-btn.done{border-color:var(--gold);background:rgba(201,168,76,0.08);color:var(--gold)}
.table-footer{padding:12px 16px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.tbl-pg-btn{padding:6px 14px;border-radius:3px;border:1px solid var(--border);background:transparent;color:var(--muted);font-size:12px;font-family:'Jost',sans-serif;cursor:pointer;transition:all 0.18s}
.tbl-pg-btn:hover:not(:disabled){border-color:var(--gold-dim);color:var(--gold)}
.tbl-pg-btn:disabled{opacity:0.3;cursor:not-allowed}
.tbl-pg-info{font-size:11px;color:var(--muted);letter-spacing:0.06em}
.empty-row td{text-align:center;color:var(--muted);padding:32px 16px;font-size:13px}

/* TOAST */
#restockToast{position:fixed;bottom:28px;right:28px;z-index:999;background:var(--card);border:1px solid var(--green);border-radius:5px;padding:12px 18px;display:flex;align-items:center;gap:10px;font-size:13.5px;color:var(--cream);box-shadow:0 8px 32px rgba(0,0,0,0.5);transform:translateY(16px);opacity:0;transition:all 0.3s ease;pointer-events:none}
#restockToast.show{transform:translateY(0);opacity:1}
#restockToast .toast-icon{color:var(--green-lt);flex-shrink:0}

@media(max-width:1100px){.stat-row{grid-template-columns:repeat(2,1fr)}.chart-row{grid-template-columns:1fr}.orders-two-col{grid-template-columns:1fr}.top6-grid{grid-template-columns:1fr 1fr}.branch-row{grid-template-columns:1fr}}
@media(max-width:768px){.dash-body{padding:20px 16px 48px}.stat-row{grid-template-columns:1fr 1fr}.top6-grid{grid-template-columns:1fr}}
</style>
</head>
<body>

<aside id="sidebar">
    <div class="sb-brand">
        <div class="sb-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e05a5a" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div>
            <div class="sb-title">Ayos<span>Coffee</span></div>
            <div class="sb-sub">Admin Panel</div>
        </div>
    </div>
    <nav class="sb-nav">
        <div class="sb-nav-label">Admin</div>
        <a href="admin-dashboard.php" class="nav-item active">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></span>
            Dashboard
        </a>
        <a href="admin-users.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
            User Management
        </a>
        <div class="sb-nav-label">Site</div>
        <a href="admin-index.php" class="nav-item" target="_blank">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
            View Menu
        </a>
    </nav>
    <div class="sb-footer">
        <a href="admin-logout.php" class="nav-item logout">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
            Logout
        </a>
    </div>
</aside>

<div id="mainContent">
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-sub">Admin Overview</div>
            <div class="topbar-title">Dashboard</div>
        </div>
        <div class="topbar-right">
            <div class="topbar-period"><?= $monthName . ' ' . $year ?></div>
            <div class="topbar-user">Welcome, <span><?= htmlspecialchars($_SESSION['admin']['username']) ?></span></div>
        </div>
    </div>

    <div class="dash-body">

        <!-- STAT CARDS -->
        <div class="stat-row">
            <div class="stat-card">
                <div class="stat-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.8"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                <div class="stat-label">Total Sales (Month)</div>
                <div class="stat-value gold">₱<?= number_format($totalSales, 2) ?></div>
                <div class="stat-sub">All branches — <?= $monthName ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green-bg"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6aaa52" stroke-width="1.8"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></div>
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?= number_format($totalOrders) ?></div>
                <div class="stat-sub">This month, all branches</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue-bg"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
                <div class="stat-label">Total Customers</div>
                <div class="stat-value"><?= number_format($totalCustomers) ?></div>
                <div class="stat-sub">Unique buyers</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red-bg"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e05a5a" stroke-width="1.8"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
                <div class="stat-label">Avg. Order Value</div>
                <div class="stat-value">₱<?= number_format($avgOrder, 2) ?></div>
                <div class="stat-sub">Per transaction</div>
            </div>
        </div>

        <!-- BRANCH COMPARISON -->
        <div>
            <div class="section-hd">
                <div class="section-title">Branch Performance</div>
                <div class="section-sub"><?= strtoupper($monthName . ' ' . $year) ?></div>
                <div class="section-hd-line"></div>
            </div>
            <div class="branch-row">
                <div class="branch-card">
                    <div class="branch-card-title">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--green-lt)" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        Laguna Branch
                        <span class="branch-tag laguna">Laguna</span>
                    </div>
                    <div class="branch-stats">
                        <div class="branch-stat-item">
                            <div class="branch-stat-label">Sales</div>
                            <div class="branch-stat-val gold">₱<?= number_format($laguna['sales'], 0) ?></div>
                        </div>
                        <div class="branch-stat-item">
                            <div class="branch-stat-label">Orders</div>
                            <div class="branch-stat-val"><?= number_format($laguna['orders']) ?></div>
                        </div>
                        <div class="branch-stat-item">
                            <div class="branch-stat-label">Customers</div>
                            <div class="branch-stat-val"><?= number_format($laguna['customers']) ?></div>
                        </div>
                        <div class="branch-stat-item">
                            <div class="branch-stat-label">Avg Order</div>
                            <div class="branch-stat-val gold">₱<?= $laguna['orders'] > 0 ? number_format($laguna['sales'] / $laguna['orders'], 0) : '0' ?></div>
                        </div>
                    </div>
                </div>
                <div class="branch-card">
                    <div class="branch-card-title">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        Manila Branch
                        <span class="branch-tag manila">Manila</span>
                    </div>
                    <div class="branch-stats">
                        <div class="branch-stat-item">
                            <div class="branch-stat-label">Sales</div>
                            <div class="branch-stat-val gold">₱<?= number_format($manila['sales'], 0) ?></div>
                        </div>
                        <div class="branch-stat-item">
                            <div class="branch-stat-label">Orders</div>
                            <div class="branch-stat-val"><?= number_format($manila['orders']) ?></div>
                        </div>
                        <div class="branch-stat-item">
                            <div class="branch-stat-label">Customers</div>
                            <div class="branch-stat-val"><?= number_format($manila['customers']) ?></div>
                        </div>
                        <div class="branch-stat-item">
                            <div class="branch-stat-label">Avg Order</div>
                            <div class="branch-stat-val gold">₱<?= $manila['orders'] > 0 ? number_format($manila['sales'] / $manila['orders'], 0) : '0' ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TOP PRODUCTS -->
        <div>
            <div class="section-hd">
                <div class="section-title">Top 6 Products</div>
                <div class="section-sub">By revenue — all branches</div>
                <div class="section-hd-line"></div>
            </div>
            <div class="top6-grid">
                <?php if (empty($topProducts)): ?>
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                    <div class="top6-card" style="opacity:0.35">
                        <div class="top6-rank"><?= $i ?></div>
                        <div class="top6-info"><div class="top6-name" style="color:var(--muted)">No data yet</div></div>
                    </div>
                    <?php endfor; ?>
                <?php else: ?>
                    <?php foreach ($topProducts as $i => $p): ?>
                    <div class="top6-card">
                        <div class="top6-rank <?= $i === 0 ? 'gold-rank' : '' ?>"><?= $i+1 ?></div>
                        <div class="top6-info">
                            <div class="top6-name"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="top6-cat">
                                <?= htmlspecialchars($p['category']) ?> &middot;
                                <span style="color:<?= $p['branch'] === 'manila' ? 'var(--gold)' : 'var(--green-lt)' ?>"><?= ucfirst($p['branch']) ?></span>
                            </div>
                        </div>
                        <div class="top6-meta">
                            <div class="top6-rev">₱<?= number_format($p['revenue'], 0) ?></div>
                            <div class="top6-units"><?= number_format($p['units']) ?> units</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- CHARTS -->
        <div>
            <div class="section-hd">
                <div class="section-title">Daily Transactions</div>
                <div class="section-sub"><?= strtoupper($monthName . ' ' . $year) ?></div>
                <div class="section-hd-line"></div>
            </div>
            <div class="chart-card">
                <div class="chart-card-title">Transaction Count per Day</div>
                <div class="chart-card-sub">All branches combined</div>
                <div class="chart-wrap" style="height:200px"><canvas id="chartDaily"></canvas></div>
            </div>
        </div>

        <!-- LOW STOCK + RECENT ORDERS -->
        <div class="orders-two-col">

            <!-- Low Stock -->
            <div>
                <div class="section-hd">
                    <div class="section-title">Low Stock Alert</div>
                    <div class="section-hd-line"></div>
                </div>
                <div class="table-card">
                    <div class="table-header">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#e05a5a" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <div class="table-header-title">Inventory Alerts</div>
                        <?php if (!empty($lowStock)): ?>
                        <div class="alert-badge"><?= count($lowStock) ?> items</div>
                        <?php endif; ?>
                    </div>
                    <table>
                        <thead><tr><th>Product</th><th>Branch</th><th>Stock</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody id="lowStockBody">
                        <?php if (empty($lowStock)): ?>
                            <tr class="empty-row"><td colspan="5">✓ All products well-stocked.</td></tr>
                        <?php else: ?>
                            <?php foreach ($lowStock as $s):
                                $pct  = min(100, ($s['stock'] / max(1, $s['reorder_level'])) * 100);
                                $cls  = $s['stock'] == 0 ? 'danger' : ($s['stock'] <= 5 ? 'danger' : 'warn');
                                $stat = $s['stock'] == 0 ? 'Out of Stock' : ($s['stock'] <= 5 ? 'Critical' : 'Low');
                                $pill = $s['stock'] == 0 ? 'critical' : ($s['stock'] <= 5 ? 'critical' : 'low');
                            ?>
                            <tr>
                                <td style="font-weight:500"><?= htmlspecialchars($s['name']) ?></td>
                                <td><span class="branch-pill <?= $s['branch'] ?? 'laguna' ?>"><?= ucfirst($s['branch'] ?? 'laguna') ?></span></td>
                                <td>
                                    <div class="stock-bar-wrap">
                                        <span class="stock-num"><?= $s['stock'] ?></span>
                                        <div class="stock-bar-bg"><div class="stock-bar-fill <?= $cls ?>" style="width:<?= $pct ?>%"></div></div>
                                    </div>
                                </td>
                                <td><span class="status-pill <?= $pill ?>"><?= $stat ?></span></td>
                                <td>
                                    <button class="restock-btn" onclick="restockItem(<?= $s['id'] ?>, this)">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.54"/></svg>
                                        Restock
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="table-footer">
                        <button class="tbl-pg-btn" id="lowPrev" onclick="lowPage(-1)" disabled>Previous</button>
                        <span class="tbl-pg-info" id="lowInfo"></span>
                        <button class="tbl-pg-btn" id="lowNext" onclick="lowPage(1)">Next</button>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div>
                <div class="section-hd">
                    <div class="section-title">Recent Orders</div>
                    <div class="section-hd-line"></div>
                </div>
                <div class="table-card">
                    <div class="table-header">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <div class="table-header-title">Latest Transactions</div>
                    </div>
                    <table>
                        <thead><tr><th>Order #</th><th>Customer</th><th>Branch</th><th>Total</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php if (empty($recentOrders)): ?>
                            <tr class="empty-row"><td colspan="5">No orders yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $o): ?>
                            <tr>
                                <td style="color:var(--muted)">#<?= $o['id'] ?></td>
                                <td style="font-weight:500"><?= htmlspecialchars($o['customer'] ?? 'Guest') ?></td>
                                <td><span class="branch-pill <?= $o['branch'] ?? 'laguna' ?>"><?= ucfirst($o['branch'] ?? 'laguna') ?></span></td>
                                <td style="color:var(--gold);font-family:'Cormorant Garamond',serif;font-size:15px">₱<?= number_format($o['total'], 2) ?></td>
                                <td><span class="order-status <?= htmlspecialchars($o['status']) ?>"><?= ucfirst($o['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="table-footer">
                        <span></span>
                        <a href="admin-users.php" style="font-size:12px;color:var(--gold);text-decoration:none;letter-spacing:0.06em;">Manage Users →</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div id="restockToast">
    <svg class="toast-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    <span id="restockToastMsg">Restocked!</span>
</div>

<script>
Chart.defaults.color = '#6b6b58';
Chart.defaults.font.family = "'Jost', sans-serif";
Chart.defaults.font.size = 11;

const GOLD = '#c9a84c';
const dailyData = <?= $jsDaily ?>;
new Chart(document.getElementById('chartDaily'), {
    type: 'line',
    data: {
        labels: dailyData.labels,
        datasets: [{
            data: dailyData.values,
            borderColor: GOLD,
            backgroundColor: 'rgba(201,168,76,0.07)',
            borderWidth: 2, pointRadius: 3, pointBackgroundColor: GOLD,
            fill: true, tension: 0.4
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: 'rgba(44,44,36,0.5)' }, ticks: { maxTicksLimit: 10 } },
            y: { grid: { color: 'rgba(44,44,36,0.5)' }, beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// Low Stock Pagination
let lowCurrentPage = 1;
const PER_PAGE = 5;
function getLowRows() { return Array.from(document.querySelectorAll('#lowStockBody tr:not(.empty-row)')); }
function renderLowTable() {
    const rows = getLowRows(), total = rows.length;
    const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));
    if (lowCurrentPage > totalPages) lowCurrentPage = totalPages;
    const start = (lowCurrentPage - 1) * PER_PAGE, end = start + PER_PAGE;
    rows.forEach((r, i) => { r.style.display = (i >= start && i < end) ? '' : 'none'; });
    document.getElementById('lowInfo').textContent = total ? `Page ${lowCurrentPage} of ${totalPages}` : '';
    document.getElementById('lowPrev').disabled = lowCurrentPage <= 1;
    document.getElementById('lowNext').disabled = lowCurrentPage >= totalPages || !total;
}
function lowPage(dir) { lowCurrentPage += dir; renderLowTable(); }
renderLowTable();

// Toast
let toastTimer;
function showRestockToast(msg) {
    const toast = document.getElementById('restockToast');
    document.getElementById('restockToastMsg').textContent = msg;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 2800);
}

// Restock
function restockItem(id, btn) {
    btn.disabled = true;
    btn.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 0.7s linear infinite"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.54"/></svg> Working…`;
    fetch('restock_handler.php?id=' + id)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const row = btn.closest('tr');
                const numEl = row.querySelector('.stock-num');
                if (numEl) numEl.textContent = data.stock;
                const fill = row.querySelector('.stock-bar-fill');
                if (fill) { fill.style.width = '100%'; fill.className = 'stock-bar-fill'; }
                const pill = row.querySelector('.status-pill');
                if (pill) { pill.textContent = 'OK'; pill.className = 'status-pill ok'; }
                btn.classList.add('done');
                btn.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Done`;
                showRestockToast((data.name || 'Product') + ' restocked to ' + data.stock + ' units.');
                setTimeout(() => {
                    row.style.transition = 'opacity 0.5s ease';
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        renderLowTable();
                        if (getLowRows().length === 0) {
                            document.getElementById('lowStockBody').innerHTML = '<tr class="empty-row"><td colspan="5">✓ All products well-stocked.</td></tr>';
                        }
                    }, 500);
                }, 1400);
            } else {
                btn.disabled = false;
                btn.innerHTML = `Restock`;
                alert('Error: ' + (data.message || 'Could not restock.'));
            }
        })
        .catch(() => { btn.disabled = false; btn.innerHTML = 'Restock'; alert('Network error.'); });
}
</script>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>
</body>
</html>