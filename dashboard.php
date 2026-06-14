<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION["user"])) { header("Location: log-in.php"); exit(); }

include 'config.php';

// ── CURRENT MONTH/YEAR ──
$month     = (int)date('n');
$year      = (int)date('Y');
$monthName = date('F');

$userId = (int)($_SESSION['user']['id'] ?? 0);

// ── THIS USER'S ORDERS THIS MONTH ──
$stmtOrders = $conn->prepare("
    SELECT COUNT(*) AS cnt FROM orders
    WHERE user_id = ? AND MONTH(created_at) = ? AND YEAR(created_at) = ?
    AND status != 'cancelled'
");
$stmtOrders->bind_param("iii", $userId, $month, $year);
$stmtOrders->execute();
$myOrders = $stmtOrders->get_result()->fetch_assoc()['cnt'] ?? 0;

// ── THIS USER'S TOTAL SPEND THIS MONTH ──
$stmtSpend = $conn->prepare("
    SELECT COALESCE(SUM(oi.quantity * oi.price), 0) AS total
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ? AND MONTH(o.created_at) = ? AND YEAR(o.created_at) = ?
    AND o.status != 'cancelled'
");
$stmtSpend->bind_param("iii", $userId, $month, $year);
$stmtSpend->execute();
$mySpend = $stmtSpend->get_result()->fetch_assoc()['total'] ?? 0;

// ── THIS USER'S AVERAGE ORDER VALUE ──
$myAvg = $myOrders > 0 ? $mySpend / $myOrders : 0;

// ── THIS USER'S ALL-TIME ORDERS ──
$stmtAll = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE user_id = ? AND status != 'cancelled'");
$stmtAll->bind_param("i", $userId);
$stmtAll->execute();
$myTotalOrders = $stmtAll->get_result()->fetch_assoc()['cnt'] ?? 0;

// ── TOP 5 PRODUCTS THIS USER ORDERED (all time) ──
$stmtTop5 = $conn->prepare("
    SELECT p.name, SUM(oi.quantity) AS units, SUM(oi.quantity * oi.price) AS spent
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.user_id = ? AND o.status != 'cancelled'
    GROUP BY oi.product_id, p.name
    ORDER BY units DESC
    LIMIT 5
");
$stmtTop5->bind_param("i", $userId);
$stmtTop5->execute();
$top5Products = $stmtTop5->get_result()->fetch_all(MYSQLI_ASSOC);

// ── DAILY SPEND THIS MONTH ──
$stmtDaily = $conn->prepare("
    SELECT DAY(o.created_at) AS day, SUM(oi.quantity * oi.price) AS amount
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ? AND MONTH(o.created_at) = ? AND YEAR(o.created_at) = ?
    AND o.status != 'cancelled'
    GROUP BY DAY(o.created_at)
    ORDER BY day ASC
");
$stmtDaily->bind_param("iii", $userId, $month, $year);
$stmtDaily->execute();
$dailyRaw = $stmtDaily->get_result()->fetch_all(MYSQLI_ASSOC);
$dailyMap = [];
foreach ($dailyRaw as $d) $dailyMap[$d['day']] = $d['amount'];
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$dailyLabels = $dailyValues = [];
for ($d = 1; $d <= $daysInMonth; $d++) {
    $dailyLabels[] = $d;
    $dailyValues[] = round((float)($dailyMap[$d] ?? 0), 2);
}

// ── RECENT ORDERS (this user) ──
$stmtRecent = $conn->prepare("
    SELECT o.id, o.created_at, o.status,
           COALESCE(o.total, 0) AS total,
           COALESCE(o.branch, 'laguna') AS branch
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
    LIMIT 8
");
$stmtRecent->bind_param("i", $userId);
$stmtRecent->execute();
$recentOrders = $stmtRecent->get_result()->fetch_all(MYSQLI_ASSOC);

$jsDaily = json_encode(['labels' => $dailyLabels, 'values' => $dailyValues]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Dashboard — AyosCoffeeNegosyo</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --bg:          #0b0b09;
    --surface:     #131310;
    --card:        #1a1a16;
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
    flex-shrink: 0;
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
.topbar-eyebrow { font-size: 10px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--gold); }
.topbar-title { font-family: 'Cormorant Garamond', serif; font-size: 22px; font-weight: 600; color: var(--cream); }
.topbar-right { display: flex; align-items: center; gap: 16px; }
.topbar-period { font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--muted); padding: 5px 12px; border: 1px solid var(--border); border-radius: 20px; }
.topbar-user { font-size: 12.5px; color: var(--muted); }
.topbar-user span { color: var(--gold); font-weight: 500; }

/* ── PAGE BODY ── */
.dash-body { padding: 28px 32px 60px; display: flex; flex-direction: column; gap: 24px; max-width: 1200px; }

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
    background: var(--gold-pale); margin-bottom: 4px;
}
.stat-icon.green-bg { background: rgba(74,122,58,0.12); }
.stat-icon.blue-bg  { background: rgba(59,130,246,0.1); }
.stat-icon.red-bg   { background: var(--red-pale); }
.stat-label { font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--muted); }
.stat-value { font-family: 'Cormorant Garamond', serif; font-size: 32px; font-weight: 700; color: var(--cream); line-height: 1; }
.stat-value.gold { color: var(--gold); }
.stat-sub { font-size: 11px; color: var(--muted); margin-top: 2px; }

/* ── SECTION HEADER ── */
.section-hd { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
.section-hd-line { flex: 1; height: 1px; background: linear-gradient(90deg, var(--border), transparent); }
.section-title { font-family: 'Cormorant Garamond', serif; font-size: 18px; font-weight: 600; color: var(--cream); }
.section-sub { font-size: 11px; color: var(--muted); letter-spacing: 0.06em; }

/* ── TOP 5 PRODUCTS ── */
.top5-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; }
.top5-card {
    background: var(--card); border: 1px solid var(--border); border-radius: 6px;
    padding: 14px 16px; display: flex; align-items: center; gap: 12px;
    transition: border-color 0.2s;
}
.top5-card:hover { border-color: var(--border-lt); }
.top5-rank { font-family: 'Cormorant Garamond', serif; font-size: 28px; font-weight: 700; color: var(--border-lt); min-width: 28px; text-align: center; line-height: 1; }
.top5-rank.gold-rank { color: var(--gold-dim); }
.top5-info { flex: 1; min-width: 0; }
.top5-name { font-size: 13px; font-weight: 500; color: var(--cream); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.top5-meta { text-align: right; flex-shrink: 0; }
.top5-spent { font-family: 'Cormorant Garamond', serif; font-size: 15px; font-weight: 600; color: var(--gold); }
.top5-units { font-size: 10px; color: var(--muted); margin-top: 1px; }

/* ── CHART ── */
.chart-card { background: var(--card); border: 1px solid var(--border); border-radius: 6px; padding: 20px 20px 16px; }
.chart-card-title { font-size: 13px; font-weight: 500; color: var(--cream); margin-bottom: 3px; }
.chart-card-sub { font-size: 11px; color: var(--muted); margin-bottom: 16px; }

/* ── RECENT ORDERS TABLE ── */
.table-card { background: var(--card); border: 1px solid var(--border); border-radius: 6px; overflow: hidden; }
.table-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; }
.table-header-title { font-size: 13.5px; font-weight: 500; color: var(--cream); }
table { width: 100%; border-collapse: collapse; }
thead th { font-size: 10px; letter-spacing: 0.14em; text-transform: uppercase; color: var(--muted); padding: 10px 16px; text-align: left; border-bottom: 1px solid var(--border); font-weight: 500; }
tbody td { padding: 11px 16px; font-size: 13px; border-bottom: 1px solid rgba(44,44,36,0.5); vertical-align: middle; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover { background: rgba(255,255,255,0.02); }
.order-status { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 3px; font-size: 10px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; }
.order-status.pending    { background: var(--amber-pale); color: var(--amber); }
.order-status.completed  { background: rgba(74,122,58,0.1); color: var(--green-lt); }
.order-status.cancelled  { background: var(--red-pale); color: #e05a5a; }
.order-status.processing { background: rgba(59,130,246,0.1); color: #60a5fa; }
.branch-pill { display: inline-flex; padding: 2px 7px; border-radius: 3px; font-size: 9px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; }
.branch-pill.laguna { background: rgba(74,122,58,0.1); color: var(--green-lt); }
.branch-pill.manila { background: rgba(201,168,76,0.08); color: var(--gold); }
.empty-row td { text-align: center; color: var(--muted); padding: 32px 16px; font-size: 13px; }
.table-footer { padding: 12px 16px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; }

/* ── MOBILE SIDEBAR ── */
#sidebarOverlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 190; backdrop-filter: blur(2px); opacity: 0; pointer-events: none; transition: opacity 0.28s; }
#sidebarOverlay.visible { opacity: 1; pointer-events: all; }

@media (max-width: 768px) {
    #sidebar { transform: translateX(-100%); width: var(--sidebar-w) !important; transition: transform 0.28s; }
    #sidebar.mobile-open { transform: translateX(0); }
    #sidebarOverlay { display: block; }
    #mainContent { margin-left: 0 !important; }
    .sidebar-toggle { display: none; }
    .topbar { padding: 0 16px; }
    .dash-body { padding: 20px 16px 48px; }
    .stat-row { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 480px) {
    .stat-row { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- Sidebar -->
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
        <a href="testimonial.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
            <span class="nav-label-text">Testimonials</span>
        </a>

        <div class="nav-section-label">My Account</div>
        <a href="profile.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
            <span class="nav-label-text">Profile</span>
        </a>
        <a href="dashboard.php" class="nav-item active">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></span>
            <span class="nav-label-text">My Dashboard</span>
        </a>
        <a href="orders.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span>
            <span class="nav-label-text">My Orders</span>
        </a>
        <a href="attendance.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
            <span class="nav-label-text">Attendance</span>
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

<!-- Main Content -->
<div id="mainContent">
    <div class="topbar">
        <div class="topbar-left">
            <div>
                <div class="topbar-eyebrow">My Overview</div>
                <div class="topbar-title">My Dashboard</div>
            </div>
        </div>
        <div class="topbar-right">
            <button onclick="openMobileSidebar()" id="mobileMenuBtn"
                style="display:none;background:transparent;border:1px solid var(--border);border-radius:4px;padding:7px 10px;cursor:pointer;color:var(--text);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="topbar-period"><?= $monthName . ' ' . $year ?></div>
            <div class="topbar-user">Welcome, <span><?= htmlspecialchars($_SESSION['user']['name'] ?? $_SESSION['user']['username'] ?? 'User') ?></span></div>
        </div>
    </div>

    <div class="dash-body">

        <!-- ── MY STAT CARDS ── -->
        <div class="stat-row">
            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div class="stat-label">My Spend (Month)</div>
                <div class="stat-value gold">₱<?= number_format($mySpend, 2) ?></div>
                <div class="stat-sub"><?= $monthName ?> <?= $year ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green-bg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6aaa52" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </div>
                <div class="stat-label">My Orders (Month)</div>
                <div class="stat-value"><?= number_format($myOrders) ?></div>
                <div class="stat-sub">This month</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue-bg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <div class="stat-label">All-Time Orders</div>
                <div class="stat-value"><?= number_format($myTotalOrders) ?></div>
                <div class="stat-sub">Total since joining</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red-bg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e05a5a" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <div class="stat-label">Avg. Order (Month)</div>
                <div class="stat-value">₱<?= number_format($myAvg, 2) ?></div>
                <div class="stat-sub">Per transaction</div>
            </div>
        </div>

        <!-- ── MY FAVOURITE ITEMS ── -->
        <?php if (!empty($top5Products)): ?>
        <div>
            <div class="section-hd">
                <div class="section-title">My Favourite Items</div>
                <div class="section-sub">Most ordered, all time</div>
                <div class="section-hd-line"></div>
            </div>
            <div class="top5-grid">
                <?php foreach ($top5Products as $i => $p): ?>
                <div class="top5-card">
                    <div class="top5-rank <?= $i === 0 ? 'gold-rank' : '' ?>"><?= $i + 1 ?></div>
                    <div class="top5-info">
                        <div class="top5-name"><?= htmlspecialchars($p['name']) ?></div>
                        <div style="font-size:10px;color:var(--muted);margin-top:2px"><?= $p['units'] ?> orders total</div>
                    </div>
                    <div class="top5-meta">
                        <div class="top5-spent">₱<?= number_format($p['spent'], 0) ?></div>
                        <div class="top5-units">spent</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── MY SPENDING CHART ── -->
        <div>
            <div class="section-hd">
                <div class="section-title">My Spending This Month</div>
                <div class="section-sub"><?= strtoupper($monthName . ' ' . $year) ?></div>
                <div class="section-hd-line"></div>
            </div>
            <div class="chart-card">
                <div class="chart-card-title">Daily Spend</div>
                <div class="chart-card-sub">Your order spend per day</div>
                <div style="position:relative;height:200px"><canvas id="chartSpend"></canvas></div>
            </div>
        </div>

        <!-- ── MY RECENT ORDERS ── -->
        <div>
            <div class="section-hd">
                <div class="section-title">My Recent Orders</div>
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
                            <th>Branch</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr class="empty-row"><td colspan="5">You haven't placed any orders yet. <a href="index.php" style="color:var(--gold)">Browse the menu →</a></td></tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $o): ?>
                        <tr>
                            <td style="color:var(--muted)">#<?= (int)$o['id'] ?></td>
                            <td><span class="branch-pill <?= htmlspecialchars($o['branch']) ?>"><?= ucfirst($o['branch']) ?></span></td>
                            <td style="color:var(--gold);font-family:'Cormorant Garamond',serif;font-size:15px">₱<?= number_format((float)$o['total'], 2) ?></td>
                            <td><span class="order-status <?= htmlspecialchars($o['status']) ?>"><?= ucfirst($o['status']) ?></span></td>
                            <td style="color:var(--muted);font-size:11px"><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
                <div class="table-footer">
                    <a href="orders.php" style="font-size:12px;color:var(--gold);text-decoration:none;letter-spacing:0.06em;">View all my orders →</a>
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
function openMobileSidebar()  { sidebar.classList.add('mobile-open');    overlay.classList.add('visible'); }
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
fetch('cart_handler.php?count=1').then(r => r.json()).then(d => {
    document.getElementById('cartCount').textContent = d.count || 0;
}).catch(() => {});

// ── Spending Chart ──
Chart.defaults.color = '#6b6b58';
Chart.defaults.font.family = "'Jost', sans-serif";
Chart.defaults.font.size   = 11;

const GOLD      = '#c9a84c';
const dailyData = <?= $jsDaily ?>;

if (dailyData.values.some(v => v > 0)) {
    new Chart(document.getElementById('chartSpend'), {
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
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ' ₱' + Number(ctx.parsed.y).toLocaleString() } }
            },
            scales: {
                x: { grid: { color: 'rgba(44,44,36,0.5)' }, ticks: { maxTicksLimit: 10 } },
                y: { grid: { color: 'rgba(44,44,36,0.5)' }, beginAtZero: true,
                     ticks: { callback: v => '₱' + v } }
            }
        }
    });
} else {
    document.getElementById('chartSpend').parentElement.innerHTML =
        '<div style="display:flex;align-items:center;justify-content:center;height:200px;color:var(--muted);font-size:13px;">No spending data for this month yet.</div>';
}
</script>
</body>
</html>