<?php
include 'admin-config.php';
requireAdmin();

$currentPage = basename($_SERVER['PHP_SELF']);
$isOwner  = ($_SESSION['admin']['role'] ?? '') === 'owner';
$invOpen  = in_array($currentPage, ['admin-inventory.php', 'admin-products.php']);
$attOpen  = in_array($currentPage, ['admin-attendance.php', 'admin-leave-overtime.php']);

$pendingCount = 0;
if ($isOwner) {
    try {
        $pendingCount += (int)$conn->query("SELECT COUNT(*) c FROM leave_requests WHERE status='pending'")->fetch_assoc()['c'];
        $pendingCount += (int)$conn->query("SELECT COUNT(*) c FROM overtime_requests WHERE status='pending'")->fetch_assoc()['c'];
    } catch (Throwable $e) { $pendingCount = 0; }
}

$month     = (int)date('n');
$year      = (int)date('Y');
$monthName = date('F');

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

// ── TOP PRODUCTS ALL BRANCHES ──────────────────────────────────────────────
$topProducts = $conn->query("
    SELECT p.name, p.branch, p.category, SUM(oi.quantity) AS units, SUM(oi.quantity * oi.price) AS revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled'
    GROUP BY oi.product_id, p.name, p.branch, p.category
    ORDER BY revenue DESC
    LIMIT 6
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
$jsDaily = json_encode(['labels' => $dailyLabels, 'values' => $dailyValues]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analytics — AyosCoffeeNegosyo</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;--gold:#c9a84c;--gold-dim:#8a6f2e;--gold-pale:rgba(201,168,76,0.08);--green:#4a7a3a;--green-lt:#6aaa52;--cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8;--red:#c0392b;--red-pale:rgba(192,57,43,0.1);--amber:#d4820a;--amber-pale:rgba(212,130,10,0.1);--sidebar-w:240px}
html{scroll-behavior:smooth}
body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 60% 40% at 15% 0%,rgba(192,57,43,0.04) 0%,transparent 60%),radial-gradient(ellipse 50% 60% at 85% 100%,rgba(201,168,76,0.05) 0%,transparent 60%);pointer-events:none;z-index:0}

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

.sb-nav{overflow-y:auto}
.nav-badge{margin-left:auto;background:var(--red);color:#fff;border-radius:10px;padding:1px 7px;font-size:10px}

/* ── Dropdown groups ── */
.nav-toggle{width:100%;background:none;border:none;font-family:'Jost',sans-serif;cursor:pointer;text-align:left}
.nav-chevron{margin-left:auto;display:flex;transition:transform 0.3s ease}
.nav-group.open > .nav-toggle .nav-chevron{transform:rotate(180deg)}
.nav-group.open > .nav-toggle{color:var(--text)}
.nav-group.has-active > .nav-toggle{color:#e05a5a}

.nav-sub{display:grid;grid-template-rows:0fr;transition:grid-template-rows 0.35s ease}
.nav-group.open > .nav-sub{grid-template-rows:1fr}
.nav-sub-inner{
    overflow:hidden;min-height:0;
    display:flex;flex-direction:column;gap:2px;
    margin-left:22px;border-left:1px solid var(--border);
    visibility:hidden;opacity:0;
    transition:opacity 0.3s ease, visibility 0.35s;
}
.nav-group.open > .nav-sub > .nav-sub-inner{visibility:visible;opacity:1}
.nav-sub .nav-item{padding:9px 12px 9px 18px;font-size:13px}
.nav-sub .nav-icon{width:18px;height:18px}
.nav-sub .nav-icon svg{width:15px;height:15px}
.nav-group.open > .nav-toggle .nav-badge{display:none}

#mainContent{margin-left:var(--sidebar-w);flex:1;min-width:0;position:relative;z-index:1;display:flex;flex-direction:column}
.topbar{position:sticky;top:0;z-index:100;background:rgba(11,11,9,0.9);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:0 32px;height:64px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.topbar-sub{font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#e05a5a}
.topbar-title{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream)}
.topbar-period{font-size:11px;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);padding:5px 12px;border:1px solid var(--border);border-radius:20px}

.dash-body{padding:28px 32px 60px;display:flex;flex-direction:column;gap:24px;max-width:1300px}

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

.section-hd{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.section-hd-line{flex:1;height:1px;background:linear-gradient(90deg,var(--border),transparent)}
.section-title{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--cream)}
.section-sub{font-size:11px;color:var(--muted);letter-spacing:0.06em}

.top6-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.top6-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:14px 16px;display:flex;align-items:center;gap:12px}
.top6-rank{font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:var(--border);min-width:28px;text-align:center;line-height:1}
.top6-rank.gold-rank{color:var(--gold-dim)}
.top6-info{flex:1;min-width:0}
.top6-name{font-size:13px;font-weight:500;color:var(--cream);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.top6-cat{font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);margin-top:2px}
.top6-meta{text-align:right;flex-shrink:0}
.top6-rev{font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:600;color:var(--gold)}
.top6-units{font-size:10px;color:var(--muted);margin-top:1px}

.chart-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:20px 20px 16px}
.chart-card-title{font-size:13px;font-weight:500;color:var(--cream);margin-bottom:3px}
.chart-card-sub{font-size:11px;color:var(--muted);margin-bottom:16px}
.chart-wrap{position:relative}

.table-card{background:var(--card);border:1px solid var(--border);border-radius:6px;overflow:hidden}
.table-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
.table-header-title{font-size:13.5px;font-weight:500;color:var(--cream)}
.alert-badge{background:var(--red-pale);color:#e05a5a;font-size:10px;font-weight:600;letter-spacing:0.08em;padding:3px 8px;border-radius:3px;text-transform:uppercase}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:var(--muted);padding:10px 16px;text-align:left;border-bottom:1px solid var(--border);font-weight:500}
tbody td{padding:11px 16px;font-size:13px;border-bottom:1px solid rgba(44,44,36,0.5);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:rgba(255,255,255,0.02)}
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

#restockToast{position:fixed;bottom:28px;right:28px;z-index:999;background:var(--card);border:1px solid var(--green);border-radius:5px;padding:12px 18px;display:flex;align-items:center;gap:10px;font-size:13.5px;color:var(--cream);box-shadow:0 8px 32px rgba(0,0,0,0.5);transform:translateY(16px);opacity:0;transition:all 0.3s ease;pointer-events:none}
#restockToast.show{transform:translateY(0);opacity:1}
#restockToast .toast-icon{color:var(--green-lt);flex-shrink:0}

@media(max-width:1100px){.top6-grid{grid-template-columns:1fr 1fr}.branch-row{grid-template-columns:1fr}}
@media(max-width:768px){.dash-body{padding:20px 16px 48px}.top6-grid{grid-template-columns:1fr}}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
</head>
<body>

<aside id="sidebar">
    <div class="sb-brand">
        <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e05a5a" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div><div class="sb-title">AyosCoffee<span>Negosyo</span></div><div class="sb-sub">Admin Panel</div></div>
    </div>
<nav class="sb-nav">
    <div class="sb-nav-label">Admin</div>

    <a href="admin-dashboard.php" class="nav-item <?= $currentPage === 'admin-dashboard.php' ? 'active' : '' ?>">
        <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></span>
        Dashboard
    </a>
    <a href="admin-analytics.php" class="nav-item <?= $currentPage === 'admin-analytics.php' ? 'active' : '' ?>">
        <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
        Analytics
    </a>
    <a href="admin-orders.php" class="nav-item <?= $currentPage === 'admin-orders.php' ? 'active' : '' ?>">
        <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span>
        Orders & Sales
    </a>

    <!-- INVENTORY DROPDOWN -->
    <div class="nav-group <?= $invOpen ? 'open has-active' : '' ?>">
        <button type="button" class="nav-item nav-toggle" aria-expanded="<?= $invOpen ? 'true' : 'false' ?>">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.5 7.28a1 1 0 0 0-.5-.86L12.5 2.4a1 1 0 0 0-1 0L4 6.42a1 1 0 0 0-.5.86v9.44a1 1 0 0 0 .5.86l7.5 4.02a1 1 0 0 0 1 0l7.5-4.02a1 1 0 0 0 .5-.86z"/><polyline points="3.5 7.5 12 12.5 20.5 7.5"/><line x1="12" y1="22" x2="12" y2="12.5"/></svg></span>
            Inventory
            <span class="nav-chevron"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></span>
        </button>
        <div class="nav-sub">
            <div class="nav-sub-inner">
                <a href="admin-inventory.php" class="nav-item <?= $currentPage === 'admin-inventory.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg></span>
                    Overview
                </a>
                <a href="admin-products.php" class="nav-item <?= $currentPage === 'admin-products.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></span>
                    Products
                </a>
            </div>
        </div>
    </div>

    <a href="admin-customers.php" class="nav-item <?= $currentPage === 'admin-customers.php' ? 'active' : '' ?>">
        <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.5-7 8-7s8 3 8 7"/></svg></span>
        Customers
    </a>
    <a href="admin-users.php" class="nav-item <?= $currentPage === 'admin-users.php' ? 'active' : '' ?>">
        <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span>
        User Management
    </a>

    <!-- ATTENDANCE DROPDOWN -->
    <div class="nav-group <?= $attOpen ? 'open has-active' : '' ?>">
        <button type="button" class="nav-item nav-toggle" aria-expanded="<?= $attOpen ? 'true' : 'false' ?>">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
            Attendance
            <?php if ($isOwner && $pendingCount > 0): ?><span class="nav-badge"><?= $pendingCount ?></span><?php endif; ?>
            <span class="nav-chevron"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></span>
        </button>
        <div class="nav-sub">
            <div class="nav-sub-inner">
                <a href="admin-attendance.php" class="nav-item <?= $currentPage === 'admin-attendance.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><polyline points="9 14 11 16 15 12"/></svg></span>
                    Overview
                </a>
                <a href="admin-leave-overtime.php" class="nav-item <?= $currentPage === 'admin-leave-overtime.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                    Leave & Overtime
                    <?php if ($isOwner && $pendingCount > 0): ?><span class="nav-badge"><?= $pendingCount ?></span><?php endif; ?>
                </a>
            </div>
        </div>
    </div>

    <?php if ($isOwner): ?>
    <a href="admin-payroll.php" class="nav-item <?= $currentPage === 'admin-payroll.php' ? 'active' : '' ?>">
        <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></span>
        Payroll & Employees
    </a>
    <?php endif; ?>

    <div class="sb-nav-label">Site</div>
    <a href="../order-type.php" class="nav-item" target="_blank">
        <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></span>
        View Menu
    </a>
    </nav>
    <div class="sb-footer">
        <a href="admin-logout.php" class="nav-item logout">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/></svg></span>
            Logout
        </a>
    </div>
</aside>

<div id="mainContent">
    <div class="topbar">
        <div><div class="topbar-sub">Admin Panel</div><div class="topbar-title">Analytics</div></div>
        <div class="topbar-period"><?= $monthName . ' ' . $year ?></div>
    </div>

    <div class="dash-body">

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
                        <div class="branch-stat-item"><div class="branch-stat-label">Sales</div><div class="branch-stat-val gold">₱<?= number_format($laguna['sales'], 0) ?></div></div>
                        <div class="branch-stat-item"><div class="branch-stat-label">Orders</div><div class="branch-stat-val"><?= number_format($laguna['orders']) ?></div></div>
                        <div class="branch-stat-item"><div class="branch-stat-label">Customers</div><div class="branch-stat-val"><?= number_format($laguna['customers']) ?></div></div>
                        <div class="branch-stat-item"><div class="branch-stat-label">Avg Order</div><div class="branch-stat-val gold">₱<?= $laguna['orders'] > 0 ? number_format($laguna['sales'] / $laguna['orders'], 0) : '0' ?></div></div>
                    </div>
                </div>
                <div class="branch-card">
                    <div class="branch-card-title">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        Manila Branch
                        <span class="branch-tag manila">Manila</span>
                    </div>
                    <div class="branch-stats">
                        <div class="branch-stat-item"><div class="branch-stat-label">Sales</div><div class="branch-stat-val gold">₱<?= number_format($manila['sales'], 0) ?></div></div>
                        <div class="branch-stat-item"><div class="branch-stat-label">Orders</div><div class="branch-stat-val"><?= number_format($manila['orders']) ?></div></div>
                        <div class="branch-stat-item"><div class="branch-stat-label">Customers</div><div class="branch-stat-val"><?= number_format($manila['customers']) ?></div></div>
                        <div class="branch-stat-item"><div class="branch-stat-label">Avg Order</div><div class="branch-stat-val gold">₱<?= $manila['orders'] > 0 ? number_format($manila['sales'] / $manila['orders'], 0) : '0' ?></div></div>
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
                    <div class="top6-card" style="opacity:0.35"><div class="top6-rank"><?= $i ?></div><div class="top6-info"><div class="top6-name" style="color:var(--muted)">No data yet</div></div></div>
                    <?php endfor; ?>
                <?php else: ?>
                    <?php foreach ($topProducts as $i => $p): ?>
                    <div class="top6-card">
                        <div class="top6-rank <?= $i === 0 ? 'gold-rank' : '' ?>"><?= $i+1 ?></div>
                        <div class="top6-info">
                            <div class="top6-name"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="top6-cat"><?= htmlspecialchars($p['category'] ?? '') ?> &middot; <span style="color:<?= $p['branch'] === 'manila' ? 'var(--gold)' : 'var(--green-lt)' ?>"><?= ucfirst($p['branch']) ?></span></div>
                        </div>
                        <div class="top6-meta"><div class="top6-rev">₱<?= number_format($p['revenue'], 0) ?></div><div class="top6-units"><?= number_format($p['units']) ?> units</div></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- CHART -->
        <div>
            <div class="section-hd">
                <div class="section-title">Daily Transactions</div>
                <div class="section-sub"><?= strtoupper($monthName . ' ' . $year) ?></div>
                <div class="section-hd-line"></div>
            </div>
            <div class="chart-card">
                <div class="chart-card-title">Transaction Count per Day</div>
                <div class="chart-card-sub">All branches combined</div>
                <div class="chart-wrap" style="height:220px"><canvas id="chartDaily"></canvas></div>
            </div>
        </div>

        <!-- LOW STOCK -->
        <div>
            <div class="section-hd">
                <div class="section-title">Low Stock Alert</div>
                <div class="section-hd-line"></div>
            </div>
            <div class="table-card">
                <div class="table-header">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#e05a5a" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <div class="table-header-title">Inventory Alerts</div>
                    <?php if (!empty($lowStock)): ?><div class="alert-badge"><?= count($lowStock) ?> items</div><?php endif; ?>
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

    </div>
</div>

<div id="restockToast">
    <svg class="toast-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    <span id="restockToastMsg">Restocked!</span>
</div>

<script>

    document.querySelectorAll('.nav-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const group = btn.closest('.nav-group');
        const isOpen = group.classList.toggle('open');
        btn.setAttribute('aria-expanded', isOpen);
    });
});

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
            borderColor: GOLD, backgroundColor: 'rgba(201,168,76,0.07)',
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

let toastTimer;
function showRestockToast(msg) {
    const toast = document.getElementById('restockToast');
    document.getElementById('restockToastMsg').textContent = msg;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 2800);
}

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
</body>
</html>
