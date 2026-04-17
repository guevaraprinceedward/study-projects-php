<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION["user"])) { header("Location: log-in.php"); exit(); }

include 'config.php';

// ── CURRENT MONTH/YEAR ──
$month = (int)date('n');
$year  = (int)date('Y');
$monthName = date('F');

// ── TOTAL SALES THIS MONTH ──
$stmtSales = $conn->prepare("
    SELECT COALESCE(SUM(oi.quantity * oi.price), 0) AS total
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE MONTH(o.created_at) = ? AND YEAR(o.created_at) = ?
    AND o.status != 'cancelled'
");
$stmtSales->bind_param("ii", $month, $year);
$stmtSales->execute();
$totalSales = $stmtSales->get_result()->fetch_assoc()['total'] ?? 0;

// ── TOTAL ORDERS THIS MONTH ──
$stmtOrders = $conn->prepare("
    SELECT COUNT(*) AS cnt FROM orders
    WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?
    AND status != 'cancelled'
");
$stmtOrders->bind_param("ii", $month, $year);
$stmtOrders->execute();
$totalOrders = $stmtOrders->get_result()->fetch_assoc()['cnt'] ?? 0;

// ── TOTAL CUSTOMERS (safe — computed after column check below) ──
// Will be set after orderCols check

// ── AVG ORDER VALUE ──
$avgOrder = ($totalOrders > 0) ? ($totalSales / $totalOrders) : 0;

// ── CHECK WHICH COLUMNS EXIST IN products ──
$colCheck = $conn->query("SHOW COLUMNS FROM products");
$existingCols = [];
while ($col = $colCheck->fetch_assoc()) $existingCols[] = $col['Field'];

$hasCategory     = in_array('category',      $existingCols);
$hasSku          = in_array('sku',           $existingCols);
$hasStock        = in_array('stock',         $existingCols);
$hasReorderLevel = in_array('reorder_level', $existingCols);
$hasStatus       = in_array('status',        $existingCols);

// Auto-add missing columns safely
if (!$hasStock)        { $conn->query("ALTER TABLE products ADD COLUMN stock INT NOT NULL DEFAULT 100"); $hasStock = true; }
if (!$hasReorderLevel) { $conn->query("ALTER TABLE products ADD COLUMN reorder_level INT NOT NULL DEFAULT 10"); $hasReorderLevel = true; }
if (!$hasSku)          { $conn->query("ALTER TABLE products ADD COLUMN sku VARCHAR(100) DEFAULT NULL"); $hasSku = true; }
if (!$hasCategory)     { $conn->query("ALTER TABLE products ADD COLUMN category VARCHAR(100) DEFAULT 'general'"); $hasCategory = true; }

// ── TOP 6 SOLD PRODUCTS (all time, by revenue) ──
$topProducts = $conn->query("
    SELECT p.name, p.category, SUM(oi.quantity) AS units, SUM(oi.quantity * oi.price) AS revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled'
    GROUP BY oi.product_id, p.name, p.category
    ORDER BY revenue DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

// ── SALES BY CATEGORY (this month) ──
$stmtCat = $conn->prepare("
    SELECT p.category, SUM(oi.quantity * oi.price) AS revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE MONTH(o.created_at) = ? AND YEAR(o.created_at) = ?
    AND o.status != 'cancelled'
    GROUP BY p.category
    ORDER BY revenue DESC
");
$stmtCat->bind_param("ii", $month, $year);
$stmtCat->execute();
$salesByCategory = $stmtCat->get_result()->fetch_all(MYSQLI_ASSOC);

// ── TOP 5 PRODUCTS BY UNITS (this month) ──
$stmtTop5 = $conn->prepare("
    SELECT p.name, SUM(oi.quantity) AS units
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE MONTH(o.created_at) = ? AND YEAR(o.created_at) = ?
    AND o.status != 'cancelled'
    GROUP BY oi.product_id
    ORDER BY units DESC
    LIMIT 5
");
$stmtTop5->bind_param("ii", $month, $year);
$stmtTop5->execute();
$top5Products = $stmtTop5->get_result()->fetch_all(MYSQLI_ASSOC);

// ── DAILY TRANSACTIONS (this month) ──
$stmtDaily = $conn->prepare("
    SELECT DAY(created_at) AS day, COUNT(*) AS txn
    FROM orders
    WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?
    AND status != 'cancelled'
    GROUP BY DAY(created_at)
    ORDER BY day ASC
");
$stmtDaily->bind_param("ii", $month, $year);
$stmtDaily->execute();
$dailyRaw = $stmtDaily->get_result()->fetch_all(MYSQLI_ASSOC);
$dailyMap = [];
foreach ($dailyRaw as $d) $dailyMap[$d['day']] = $d['txn'];
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$dailyLabels = $dailyValues = [];
for ($d = 1; $d <= $daysInMonth; $d++) {
    $dailyLabels[] = $d;
    $dailyValues[] = $dailyMap[$d] ?? 0;
}

// ── LOW STOCK ALERT ──
$lowStockSelect = "id, name, stock, reorder_level";
if ($hasSku)    $lowStockSelect .= ", sku";
if ($hasStatus) $lowStockSelect .= ", status";
$lowStock = $conn->query("
    SELECT $lowStockSelect FROM products
    WHERE stock <= reorder_level
    ORDER BY stock ASC
")->fetch_all(MYSQLI_ASSOC);

// ── CHECK users & orders columns ──
$userCols  = [];
$r = $conn->query("SHOW COLUMNS FROM users");
while ($c = $r->fetch_assoc()) $userCols[] = $c['Field'];

$orderCols = [];
$r = $conn->query("SHOW COLUMNS FROM orders");
while ($c = $r->fetch_assoc()) $orderCols[] = $c['Field'];

// Pick best name column from users
$nameCol = 'u.id';
foreach (['name','full_name','username','email'] as $try) {
    if (in_array($try, $userCols)) { $nameCol = "u.$try"; break; }
}

// Pick best total column from orders
$totalCol = in_array('total', $orderCols) ? 'o.total' :
            (in_array('amount', $orderCols) ? 'o.amount' : '0');

// Check if orders has user_id
$hasUserId = in_array('user_id', $orderCols);

// ── TOTAL CUSTOMERS ──
$totalCustomers = $hasUserId
    ? ($conn->query("SELECT COUNT(DISTINCT user_id) AS cnt FROM orders")->fetch_assoc()['cnt'] ?? 0)
    : 0;

if ($hasUserId) {
    $recentOrders = $conn->query("
        SELECT o.id, o.created_at, o.status, $totalCol AS total,
               $nameCol AS customer
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 8
    ")->fetch_all(MYSQLI_ASSOC);
} else {
    $recentOrders = $conn->query("
        SELECT o.id, o.created_at, o.status, $totalCol AS total,
               'Guest' AS customer
        FROM orders o
        ORDER BY o.created_at DESC
        LIMIT 8
    ")->fetch_all(MYSQLI_ASSOC);
}

// JSON for JS
$jsCategory = json_encode($salesByCategory);
$jsTop5     = json_encode($top5Products);
$jsDaily    = json_encode(['labels' => $dailyLabels, 'values' => $dailyValues]);
$jsTopProd  = json_encode($topProducts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — AyosCoffeeNegosyo</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --bg:          #0b0b09;
    --surface:     #131310;
    --card:        #1a1a16;
    --card-hover:  #1f1f1a;
    --border:      #2c2c24;
    --border-lt:   #3a3a2e;
    --gold:        #c9a84c;
    --gold-dim:    #8a6f2e;
    --gold-pale:   rgba(201,168,76,0.08);
    --green:       #4a7a3a;
    --green-lt:    #6aaa52;
    --cream:       #f0ead8;
    --muted:       #6b6b58;
    --text:        #e8e4d8;
    --red:         #c0392b;
    --red-pale:    rgba(192,57,43,0.1);
    --amber:       #d4820a;
    --amber-pale:  rgba(212,130,10,0.1);
    --sidebar-w:   240px;
    --sidebar-col: 60px;
}

html { scroll-behavior: smooth; }

body {
    font-family: 'Jost', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    overflow-x: hidden;
}

body::before {
    content: '';
    position: fixed; inset: 0;
    background:
        radial-gradient(ellipse 60% 40% at 15% 0%, rgba(201,168,76,0.05) 0%, transparent 60%),
        radial-gradient(ellipse 50% 60% at 85% 100%, rgba(74,122,58,0.06) 0%, transparent 60%);
    pointer-events: none; z-index: 0;
}

/* ── SIDEBAR ── */
#sidebar {
    position: fixed; top: 0; left: 0;
    height: 100vh; width: var(--sidebar-w);
    background: var(--surface);
    border-right: 1px solid var(--border);
    display: flex; flex-direction: column;
    z-index: 200;
    transition: width 0.28s cubic-bezier(0.4,0,0.2,1);
    overflow: hidden;
}
#sidebar.collapsed { width: var(--sidebar-col); }

.sidebar-brand {
    display: flex; align-items: center; gap: 12px;
    padding: 20px 16px 18px;
    border-bottom: 1px solid var(--border);
    min-height: 72px; flex-shrink: 0;
}
.brand-icon {
    width: 36px; height: 36px;
    border: 1px solid var(--gold-dim); border-radius: 50%;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.brand-text { overflow: hidden; white-space: nowrap; opacity: 1; transition: opacity 0.2s; }
#sidebar.collapsed .brand-text { opacity: 0; pointer-events: none; }
.brand-main { font-family: 'Cormorant Garamond', serif; font-size: 17px; font-weight: 600; color: var(--cream); }
.brand-main span { color: var(--gold); }
.brand-sub { font-size: 10px; letter-spacing: 0.14em; text-transform: uppercase; color: var(--muted); margin-top: 2px; }

.sidebar-nav { flex: 1; padding: 14px 10px; display: flex; flex-direction: column; gap: 2px; overflow-y: auto; overflow-x: hidden; }
.sidebar-nav::-webkit-scrollbar { width: 3px; }
.sidebar-nav::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

.nav-section-label {
    font-size: 9px; letter-spacing: 0.2em; text-transform: uppercase;
    color: var(--muted); padding: 10px 10px 4px; white-space: nowrap;
    opacity: 1; transition: opacity 0.2s;
}
#sidebar.collapsed .nav-section-label { opacity: 0; }

.nav-item {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 12px; border-radius: 6px; cursor: pointer;
    text-decoration: none; color: var(--muted);
    font-size: 13.5px; font-weight: 400; white-space: nowrap;
    position: relative; transition: background 0.18s, color 0.18s;
}
.nav-item:hover { background: rgba(255,255,255,0.04); color: var(--text); }
.nav-item.active { background: rgba(201,168,76,0.1); color: var(--gold); }
.nav-icon { width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.nav-label-text { flex: 1; overflow: hidden; opacity: 1; transition: opacity 0.18s; }
#sidebar.collapsed .nav-label-text { opacity: 0; }
.nav-badge {
    background: var(--gold); color: #000; font-size: 10px; font-weight: 700;
    min-width: 18px; height: 18px; border-radius: 9px;
    display: inline-flex; align-items: center; justify-content: center; padding: 0 4px;
    flex-shrink: 0; transition: opacity 0.18s;
}
#sidebar.collapsed .nav-badge { position: absolute; top: 5px; right: 5px; min-width: 14px; height: 14px; font-size: 8px; }

.sidebar-toggle {
    position: absolute; bottom: 86px; right: -14px;
    width: 28px; height: 28px;
    background: var(--surface); border: 1px solid var(--border); border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; z-index: 10; color: var(--muted);
    transition: background 0.18s, transform 0.28s;
}
.sidebar-toggle:hover { background: var(--card); color: var(--text); }
#sidebar.collapsed .sidebar-toggle { transform: rotate(180deg); }

.sidebar-footer { padding: 10px; border-top: 1px solid var(--border); flex-shrink: 0; }
.nav-item.logout { color: #e05a5a; }
.nav-item.logout:hover { background: rgba(224,90,90,0.08); color: #e86e6e; }

/* ── MAIN ── */
#mainContent {
    margin-left: var(--sidebar-w);
    flex: 1; min-width: 0;
    transition: margin-left 0.28s cubic-bezier(0.4,0,0.2,1);
    position: relative; z-index: 1;
    display: flex; flex-direction: column;
}
body.sidebar-collapsed #mainContent { margin-left: var(--sidebar-col); }

/* ── TOPBAR ── */
.topbar {
    position: sticky; top: 0; z-index: 100;
    background: rgba(11,11,9,0.9);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border);
    padding: 0 32px; height: 64px;
    display: flex; align-items: center; justify-content: space-between;
    flex-shrink: 0;
}
.topbar-left { display: flex; align-items: center; gap: 14px; }
.topbar-eyebrow { font-size: 10px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--gold); }
.topbar-title { font-family: 'Cormorant Garamond', serif; font-size: 22px; font-weight: 600; color: var(--cream); }
.topbar-right { display: flex; align-items: center; gap: 16px; }
.topbar-period {
    font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase;
    color: var(--muted); padding: 5px 12px;
    border: 1px solid var(--border); border-radius: 20px;
}
.topbar-user { font-size: 12.5px; color: var(--muted); }
.topbar-user span { color: var(--gold); font-weight: 500; }

/* ── PAGE BODY ── */
.dash-body { padding: 28px 32px 60px; display: flex; flex-direction: column; gap: 24px; max-width: 1300px; }

/* ── STAT CARDS ── */
.stat-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }

.stat-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 20px 22px 18px;
    display: flex; flex-direction: column; gap: 6px;
    position: relative; overflow: hidden;
    transition: border-color 0.2s, transform 0.2s;
}
.stat-card:hover { border-color: var(--gold-dim); transform: translateY(-2px); }
.stat-card::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 2px;
    background: linear-gradient(90deg, var(--gold-dim), transparent);
}
.stat-icon {
    width: 38px; height: 38px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    background: var(--gold-pale); margin-bottom: 4px; flex-shrink: 0;
}
.stat-icon.green-bg { background: rgba(74,122,58,0.12); }
.stat-icon.blue-bg  { background: rgba(59,130,246,0.1); }
.stat-icon.red-bg   { background: var(--red-pale); }
.stat-label { font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--muted); }
.stat-value { font-family: 'Cormorant Garamond', serif; font-size: 32px; font-weight: 700; color: var(--cream); line-height: 1; }
.stat-value.gold { color: var(--gold); }
.stat-sub { font-size: 11px; color: var(--muted); margin-top: 2px; }

/* ── SECTION HEADER ── */
.section-hd {
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 16px;
}
.section-hd-line {
    flex: 1; height: 1px; background: linear-gradient(90deg, var(--border), transparent);
}
.section-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 18px; font-weight: 600; color: var(--cream);
}
.section-sub { font-size: 11px; color: var(--muted); letter-spacing: 0.06em; }

/* ── TOP 6 PRODUCTS BAR ── */
.top6-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }

.top6-card {
    background: var(--card); border: 1px solid var(--border); border-radius: 6px;
    padding: 14px 16px; display: flex; align-items: center; gap: 12px;
    transition: border-color 0.2s;
}
.top6-card:hover { border-color: var(--border-lt); }
.top6-rank {
    font-family: 'Cormorant Garamond', serif;
    font-size: 28px; font-weight: 700; color: var(--border-lt);
    min-width: 28px; text-align: center; line-height: 1;
}
.top6-rank.gold-rank { color: var(--gold-dim); }
.top6-info { flex: 1; min-width: 0; }
.top6-name { font-size: 13px; font-weight: 500; color: var(--cream); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.top6-cat  { font-size: 10px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--muted); margin-top: 2px; }
.top6-meta { text-align: right; flex-shrink: 0; }
.top6-rev  { font-family: 'Cormorant Garamond', serif; font-size: 16px; font-weight: 600; color: var(--gold); }
.top6-units { font-size: 10px; color: var(--muted); margin-top: 1px; }

/* ── CHART GRID ── */
.chart-row { display: grid; grid-template-columns: 1fr 1fr 1.2fr; gap: 16px; }

.chart-card {
    background: var(--card); border: 1px solid var(--border); border-radius: 6px;
    padding: 20px 20px 16px;
}
.chart-card-title { font-size: 13px; font-weight: 500; color: var(--cream); margin-bottom: 3px; }
.chart-card-sub { font-size: 11px; color: var(--muted); margin-bottom: 16px; }
.chart-wrap { position: relative; }

/* ── LOW STOCK TABLE ── */
.table-card {
    background: var(--card); border: 1px solid var(--border); border-radius: 6px;
    overflow: hidden;
}
.table-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px;
}
.table-header-title { font-size: 13.5px; font-weight: 500; color: var(--cream); }
.alert-badge {
    background: var(--red-pale); color: #e05a5a;
    font-size: 10px; font-weight: 600; letter-spacing: 0.08em;
    padding: 3px 8px; border-radius: 3px; text-transform: uppercase;
}
.alert-badge.amber { background: var(--amber-pale); color: var(--amber); }

table { width: 100%; border-collapse: collapse; }
thead th {
    font-size: 10px; letter-spacing: 0.14em; text-transform: uppercase;
    color: var(--muted); padding: 10px 16px; text-align: left;
    border-bottom: 1px solid var(--border); font-weight: 500;
}
tbody td { padding: 12px 16px; font-size: 13px; border-bottom: 1px solid rgba(44,44,36,0.5); }
tbody tr:last-child td { border-bottom: none; }
tbody tr { transition: background 0.15s; }
tbody tr:hover { background: rgba(255,255,255,0.02); }

.stock-bar-wrap { display: flex; align-items: center; gap: 8px; }
.stock-bar-bg { flex: 1; height: 4px; background: var(--border); border-radius: 2px; min-width: 60px; }
.stock-bar-fill { height: 100%; border-radius: 2px; background: var(--green); transition: width 0.3s; }
.stock-bar-fill.warn { background: var(--amber); }
.stock-bar-fill.danger { background: var(--red); }

.status-pill {
    display: inline-flex; align-items: center;
    padding: 3px 8px; border-radius: 3px; font-size: 10px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase;
}
.status-pill.critical { background: var(--red-pale); color: #e05a5a; }
.status-pill.low      { background: var(--amber-pale); color: var(--amber); }
.status-pill.ok       { background: rgba(74,122,58,0.1); color: var(--green-lt); }

.empty-row td { text-align: center; color: var(--muted); padding: 32px 16px; font-size: 13px; }

/* pagination */
.table-footer {
    padding: 12px 16px; border-top: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
}
.tbl-pg-btn {
    padding: 6px 14px; border-radius: 3px; border: 1px solid var(--border);
    background: transparent; color: var(--muted); font-size: 12px;
    font-family: 'Jost', sans-serif; cursor: pointer; transition: all 0.18s;
}
.tbl-pg-btn:hover:not(:disabled) { border-color: var(--gold-dim); color: var(--gold); }
.tbl-pg-btn:disabled { opacity: 0.3; cursor: not-allowed; }
.tbl-pg-info { font-size: 11px; color: var(--muted); letter-spacing: 0.06em; }

/* ── RECENT ORDERS TABLE ── */
.orders-two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

.order-status {
    display: inline-flex; align-items: center;
    padding: 3px 8px; border-radius: 3px; font-size: 10px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase;
}
.order-status.pending   { background: var(--amber-pale); color: var(--amber); }
.order-status.completed { background: rgba(74,122,58,0.1); color: var(--green-lt); }
.order-status.cancelled { background: var(--red-pale); color: #e05a5a; }
.order-status.processing{ background: rgba(59,130,246,0.1); color: #60a5fa; }

/* ── MOBILE ── */
#sidebarOverlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,0.55); z-index: 190;
    backdrop-filter: blur(2px);
    opacity: 0; pointer-events: none; transition: opacity 0.28s;
}
#sidebarOverlay.visible { opacity: 1; pointer-events: all; }

@media (max-width: 1100px) {
    .chart-row { grid-template-columns: 1fr 1fr; }
    .chart-row .chart-card:last-child { grid-column: span 2; }
    .stat-row { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    :root { --sidebar-w: 240px; }
    #sidebar { transform: translateX(-100%); width: var(--sidebar-w) !important; transition: transform 0.28s cubic-bezier(0.4,0,0.2,1); }
    #sidebar.mobile-open { transform: translateX(0); }
    #sidebarOverlay { display: block; }
    #mainContent { margin-left: 0 !important; }
    body.sidebar-collapsed #mainContent { margin-left: 0 !important; }
    .sidebar-toggle { display: none; }
    .topbar { padding: 0 16px; }
    .dash-body { padding: 20px 16px 48px; }
    .stat-row { grid-template-columns: 1fr 1fr; }
    .top6-grid { grid-template-columns: 1fr; }
    .chart-row { grid-template-columns: 1fr; }
    .chart-row .chart-card:last-child { grid-column: span 1; }
    .orders-two-col { grid-template-columns: 1fr; }
}
@media (max-width: 480px) {
    .stat-row { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- ══════════ SIDEBAR ══════════ -->
<aside id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
        </div>
        <div class="brand-text">
            <div class="brand-main">Ayos<span>Coffee</span></div>
            <div class="brand-sub">Negosyo</div>
        </div>
    </div>

    <div class="sidebar-toggle" onclick="toggleSidebar()" title="Toggle sidebar">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <a href="index.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
            <span class="nav-label-text">Menu</span>
        </a>
        <div class="nav-section-label">Account</div>
        <a href="profile.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
            <span class="nav-label-text">Profile</span>
        </a>
        <a href="dashboard.php" class="nav-item active">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></span>
            <span class="nav-label-text">Dashboard</span>
        </a>
        <a href="orders.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span>
            <span class="nav-label-text">My Orders</span>
        </a>
        <a href="cart.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg></span>
            <span class="nav-label-text">Cart</span>
            <span class="nav-badge" id="cartCount">0</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="log-out.php" class="nav-item logout">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
            <span class="nav-label-text">Logout</span>
        </a>
    </div>
</aside>

<div id="sidebarOverlay" onclick="closeMobileSidebar()"></div>

<!-- ══════════ MAIN ══════════ -->
<div id="mainContent">

    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <div>
                <div class="topbar-eyebrow">Overview</div>
                <div class="topbar-title">Dashboard</div>
            </div>
        </div>
        <div class="topbar-right">
            <button onclick="openMobileSidebar()" id="mobileMenuBtn"
                style="display:none;background:transparent;border:1px solid var(--border);border-radius:4px;padding:7px 10px;cursor:pointer;color:var(--text);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="topbar-period"><?= $monthName . ' ' . $year ?></div>
            <div class="topbar-user">Welcome, <span><?= htmlspecialchars(is_array($_SESSION['user']) ? ($_SESSION['user']['name'] ?? $_SESSION['user']['username'] ?? 'Admin') : (string)$_SESSION['user']) ?></span></div>
        </div>
    </div>

    <div class="dash-body">

        <!-- ── STAT CARDS ── -->
        <div class="stat-row">
            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div class="stat-label">Total Sales (Month)</div>
                <div class="stat-value gold">₱<?= number_format($totalSales, 2) ?></div>
                <div class="stat-sub"><?= $monthName ?> <?= $year ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green-bg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6aaa52" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </div>
                <div class="stat-label">Orders This Month</div>
                <div class="stat-value"><?= number_format($totalOrders) ?></div>
                <div class="stat-sub">Completed orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue-bg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="stat-label">Total Customers</div>
                <div class="stat-value"><?= number_format($totalCustomers) ?></div>
                <div class="stat-sub">Unique buyers</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red-bg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e05a5a" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <div class="stat-label">Avg. Order Value</div>
                <div class="stat-value">₱<?= number_format($avgOrder, 2) ?></div>
                <div class="stat-sub">Per transaction</div>
            </div>
        </div>

        <!-- ── TOP 6 PRODUCTS ── -->
        <div>
            <div class="section-hd">
                <div class="section-title">Top 6 Sold Products</div>
                <div class="section-sub">By total revenue</div>
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
                            <div class="top6-cat"><?= htmlspecialchars($p['category']) ?></div>
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

        <!-- ── CHARTS ── -->
        <div>
            <div class="section-hd">
                <div class="section-title">Sales Report</div>
                <div class="section-sub"><?= strtoupper($monthName . ' ' . $year) ?></div>
                <div class="section-hd-line"></div>
            </div>
            <div class="chart-row">

                <div class="chart-card">
                    <div class="chart-card-title">Sales by Category</div>
                    <div class="chart-card-sub">Category share of total sales</div>
                    <div class="chart-wrap" style="height:200px">
                        <canvas id="chartCategory"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-card-title">Top 5 Products (Units)</div>
                    <div class="chart-card-sub">Highest unit sales this month</div>
                    <div class="chart-wrap" style="height:200px">
                        <canvas id="chartTop5"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-card-title">Daily Transactions</div>
                    <div class="chart-card-sub">Transaction count trend this month</div>
                    <div class="chart-wrap" style="height:200px">
                        <canvas id="chartDaily"></canvas>
                    </div>
                </div>

            </div>
        </div>

        <!-- ── LOW STOCK + RECENT ORDERS ── -->
        <div class="orders-two-col">

            <!-- Low Stock -->
            <div>
                <div class="section-hd">
                    <div class="section-title">Low Stock Alert</div>
                    <div class="section-hd-line"></div>
                </div>
                <div class="table-card">
                    <div class="table-header">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#e05a5a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <div class="table-header-title">Inventory Alerts</div>
                        <?php if (!empty($lowStock)): ?>
                            <div class="alert-badge"><?= count($lowStock) ?> items</div>
                        <?php endif; ?>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>On Hand</th>
                                <th>Reorder Lvl</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="lowStockBody">
                        <?php if (empty($lowStock)): ?>
                            <tr class="empty-row"><td colspan="5">No low stock products.</td></tr>
                        <?php else: ?>
                            <?php foreach ($lowStock as $s):
                                $pct    = min(100, ($s['stock'] / max(1,$s['reorder_level'])) * 100);
                                $cls    = $s['stock'] == 0 ? 'danger' : ($s['stock'] <= 5 ? 'danger' : 'warn');
                                $status = $s['stock'] == 0 ? 'Out of Stock' : ($s['stock'] <= 5 ? 'Critical' : 'Low');
                                $pill   = $s['stock'] == 0 ? 'critical' : ($s['stock'] <= 5 ? 'critical' : 'low');
                            ?>
                            <tr>
                                <td style="color:var(--muted)">#<?= $s['id'] ?></td>
                                <td style="font-weight:500"><?= htmlspecialchars($s['name']) ?></td>
                                <td>
                                    <div class="stock-bar-wrap">
                                        <span><?= $s['stock'] ?></span>
                                        <div class="stock-bar-bg"><div class="stock-bar-fill <?= $cls ?>" style="width:<?= $pct ?>%"></div></div>
                                    </div>
                                </td>
                                <td style="color:var(--muted)"><?= $s['reorder_level'] ?></td>
                                <td><span class="status-pill <?= $pill ?>"><?= $status ?></span></td>
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
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <div class="table-header-title">Latest Transactions</div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recentOrders)): ?>
                            <tr class="empty-row"><td colspan="5">No orders yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $o): ?>
                            <tr>
                                <td style="color:var(--muted)">#<?= $o['id'] ?></td>
                                <td style="font-weight:500"><?= htmlspecialchars($o['customer']) ?></td>
                                <td style="color:var(--gold);font-family:'Cormorant Garamond',serif;font-size:15px">₱<?= number_format($o['total'],2) ?></td>
                                <td><span class="order-status <?= htmlspecialchars($o['status']) ?>"><?= ucfirst($o['status']) ?></span></td>
                                <td style="color:var(--muted);font-size:11px"><?= date('M d', strtotime($o['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="table-footer">
                        <span></span>
                        <a href="orders.php" style="font-size:12px;color:var(--gold);text-decoration:none;letter-spacing:0.06em;">View all orders →</a>
                    </div>
                </div>
            </div>

        </div>

    </div><!-- end dash-body -->
</div><!-- end mainContent -->

<script>
// ── Sidebar ──
const sidebar = document.getElementById('sidebar');
const overlay  = document.getElementById('sidebarOverlay');
const isMobile = () => window.innerWidth <= 768;

function toggleSidebar() {
    if (isMobile()) return;
    const c = sidebar.classList.toggle('collapsed');
    document.body.classList.toggle('sidebar-collapsed', c);
    localStorage.setItem('sidebarCollapsed', c ? '1' : '0');
}
function openMobileSidebar()  { sidebar.classList.add('mobile-open'); overlay.classList.add('visible'); }
function closeMobileSidebar() { sidebar.classList.remove('mobile-open'); overlay.classList.remove('visible'); }

if (!isMobile() && localStorage.getItem('sidebarCollapsed') === '1') {
    sidebar.classList.add('collapsed');
    document.body.classList.add('sidebar-collapsed');
}

const mobileMenuBtn = document.getElementById('mobileMenuBtn');
function handleResize() {
    mobileMenuBtn.style.display = isMobile() ? 'flex' : 'none';
    if (!isMobile()) closeMobileSidebar();
}
handleResize();
window.addEventListener('resize', handleResize);

// ── Cart badge ──
fetch('cart_handler.php?count=1').then(r=>r.json()).then(d=>{ document.getElementById('cartCount').textContent = d.count||0; }).catch(()=>{});

// ── Chart.js defaults ──
Chart.defaults.color = '#6b6b58';
Chart.defaults.font.family = "'Jost', sans-serif";
Chart.defaults.font.size = 11;

const GOLD   = '#c9a84c';
const GREEN  = '#4a7a3a';
const GREENS = ['#4a7a3a','#6aaa52','#8aba72','#aacb92','#c9a84c','#8a6f2e'];
const AMBERS = ['#c9a84c','#d4820a','#4a7a3a','#6aaa52','#8a6f2e'];

// ── Category Doughnut ──
const catData = <?= $jsCategory ?>;
if (catData.length) {
    new Chart(document.getElementById('chartCategory'), {
        type: 'doughnut',
        data: {
            labels: catData.map(d => d.category),
            datasets: [{ data: catData.map(d => d.revenue), backgroundColor: GREENS, borderColor: '#1a1a16', borderWidth: 3 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 10, padding: 12, color: '#6b6b58' } },
                tooltip: { callbacks: { label: ctx => ' ₱' + Number(ctx.parsed).toLocaleString() } }
            }
        }
    });
} else {
    document.getElementById('chartCategory').parentElement.innerHTML =
        '<div style="display:flex;align-items:center;justify-content:center;height:200px;color:var(--muted);font-size:13px;">No data for this month</div>';
}

// ── Top 5 Horizontal Bar ──
const top5Data = <?= $jsTop5 ?>;
if (top5Data.length) {
    new Chart(document.getElementById('chartTop5'), {
        type: 'bar',
        data: {
            labels: top5Data.map(d => d.name.length > 16 ? d.name.slice(0,14)+'…' : d.name),
            datasets: [{ data: top5Data.map(d => d.units), backgroundColor: AMBERS, borderRadius: 3, borderSkipped: false }]
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.x + ' units' } } },
            scales: {
                x: { grid: { color: 'rgba(44,44,36,0.5)' }, ticks: { stepSize: 1 } },
                y: { grid: { display: false } }
            }
        }
    });
} else {
    document.getElementById('chartTop5').parentElement.innerHTML =
        '<div style="display:flex;align-items:center;justify-content:center;height:200px;color:var(--muted);font-size:13px;">No data for this month</div>';
}

// ── Daily Transactions Line ──
const dailyData = <?= $jsDaily ?>;
new Chart(document.getElementById('chartDaily'), {
    type: 'line',
    data: {
        labels: dailyData.labels,
        datasets: [{
            data: dailyData.values,
            borderColor: GOLD,
            backgroundColor: 'rgba(201,168,76,0.07)',
            borderWidth: 2,
            pointRadius: 3,
            pointBackgroundColor: GOLD,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.y + ' orders' } } },
        scales: {
            x: { grid: { color: 'rgba(44,44,36,0.5)' }, ticks: { maxTicksLimit: 10 } },
            y: { grid: { color: 'rgba(44,44,36,0.5)' }, beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// ── Low Stock Pagination ──
const lowRows   = document.querySelectorAll('#lowStockBody tr:not(.empty-row)');
const PER_PAGE  = 5;
let lowCurrentPage = 1;
const totalLowPages = Math.ceil(lowRows.length / PER_PAGE);

function renderLowTable() {
    const start = (lowCurrentPage - 1) * PER_PAGE;
    const end   = start + PER_PAGE;
    lowRows.forEach((r, i) => { r.style.display = (i >= start && i < end) ? '' : 'none'; });
    document.getElementById('lowInfo').textContent =
        lowRows.length ? `Page ${lowCurrentPage} of ${totalLowPages}` : '';
    document.getElementById('lowPrev').disabled = lowCurrentPage <= 1;
    document.getElementById('lowNext').disabled = lowCurrentPage >= totalLowPages || !lowRows.length;
}

function lowPage(dir) { lowCurrentPage += dir; renderLowTable(); }
renderLowTable();
</script>
</body>
</html>