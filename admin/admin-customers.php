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

$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'pending'");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS branch VARCHAR(20) DEFAULT 'laguna'");

// ── CUSTOMERS BASE LIST ──────────────────────────────────────────────────
$customers = $conn->query("
    SELECT u.id, u.username,
           COUNT(DISTINCT o.id) AS total_orders,
           COALESCE(SUM(oi.quantity * oi.price), 0) AS total_spent,
           MAX(o.created_at) AS last_order,
           MIN(o.created_at) AS first_order
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id AND o.status != 'cancelled'
    LEFT JOIN order_items oi ON oi.order_id = o.id
    GROUP BY u.id
    ORDER BY total_spent DESC
")->fetch_all(MYSQLI_ASSOC);

// ── FAVORITE PRODUCT PER CUSTOMER ────────────────────────────────────────
$prodAgg = $conn->query("
    SELECT o.user_id, p.name, SUM(oi.quantity) AS qty
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    JOIN products p ON p.id = oi.product_id
    WHERE o.status != 'cancelled' AND o.user_id IS NOT NULL
    GROUP BY o.user_id, p.id, p.name
    ORDER BY qty DESC
")->fetch_all(MYSQLI_ASSOC);
$favoriteByUser = [];
foreach ($prodAgg as $row) {
    if (!isset($favoriteByUser[$row['user_id']])) {
        $favoriteByUser[$row['user_id']] = $row['name']; // first hit per user = highest qty (already sorted)
    }
}

// ── SUMMARY STATS ─────────────────────────────────────────────────────────
$totalCustomers = count($customers);
$activeCustomers = count(array_filter($customers, fn($c) => $c['total_orders'] > 0));
$totalRevenue = array_sum(array_column($customers, 'total_spent'));
$avgSpend = $activeCustomers > 0 ? $totalRevenue / $activeCustomers : 0;
$monthStart = date('Y-m-01');
$newThisMonth = $conn->query("
    SELECT COUNT(*) AS c FROM users u
    WHERE EXISTS (SELECT 1 FROM orders o WHERE o.user_id = u.id AND o.created_at >= '$monthStart')
    AND NOT EXISTS (SELECT 1 FROM orders o2 WHERE o2.user_id = u.id AND o2.created_at < '$monthStart')
")->fetch_assoc()['c'] ?? 0;

$topSpender = $customers[0]['username'] ?? '—';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Management — SIPPERÉ Café</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;--gold:#c9a84c;--gold-dim:#8a6f2e;--gold-pale:rgba(201,168,76,0.08);--green:#4a7a3a;--green-lt:#6aaa52;--cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8;--red:#c0392b;--red-pale:rgba(192,57,43,0.1);--amber:#d4820a;--amber-pale:rgba(212,130,10,0.1);--blue:#60a5fa;--blue-pale:rgba(59,130,246,0.1);--sidebar-w:240px}
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
.topbar-user{font-size:12.5px;color:var(--muted)}
.topbar-user span{color:var(--gold);font-weight:500}

.page-body{padding:28px 32px 60px;display:flex;flex-direction:column;gap:24px;max-width:1300px}

.stat-row{display:grid;grid-template-columns:repeat(5,1fr);gap:16px}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:18px 20px;position:relative;overflow:hidden}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--gold-dim),transparent)}
.stat-label{font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted)}
.stat-value{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--cream);margin-top:4px}
.stat-value.gold{color:var(--gold)}

.section-hd{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.section-hd-line{flex:1;height:1px;background:linear-gradient(90deg,var(--border),transparent)}
.section-title{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--cream)}

.search-wrap{position:relative;max-width:280px}
.search-wrap input{width:100%;background:var(--card);border:1px solid var(--border);border-radius:5px;padding:9px 14px 9px 36px;font-family:'Jost',sans-serif;font-size:13px;color:var(--text);outline:none}
.search-wrap .s-icon{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--muted)}

.table-card{background:var(--card);border:1px solid var(--border);border-radius:6px;overflow:hidden}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:var(--muted);padding:10px 16px;text-align:left;border-bottom:1px solid var(--border);font-weight:500;white-space:nowrap}
tbody td{padding:12px 16px;font-size:13px;border-bottom:1px solid rgba(44,44,36,0.5);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:rgba(255,255,255,0.02)}
.empty-row td{text-align:center;color:var(--muted);padding:32px 16px;font-size:13px}
.cust-avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#2a1f08,#1a1a16);border:1px solid var(--gold-dim);display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-size:13px;font-weight:700;color:var(--gold);flex-shrink:0}
.cust-info{display:flex;align-items:center;gap:10px}
.fav-pill{display:inline-flex;padding:3px 9px;border-radius:3px;font-size:11px;background:rgba(201,168,76,0.08);color:var(--gold)}
.view-btn{padding:6px 13px;border-radius:3px;border:1px solid var(--border);background:transparent;color:var(--muted);font-family:'Jost',sans-serif;font-size:11.5px;cursor:pointer;transition:all 0.18s}
.view-btn:hover{border-color:var(--gold-dim);color:var(--gold)}

/* MODAL */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.75);backdrop-filter:blur(6px);z-index:400;display:flex;align-items:flex-start;justify-content:center;padding:40px 20px;opacity:0;pointer-events:none;transition:opacity 0.25s;overflow-y:auto}
.modal-overlay.show{opacity:1;pointer-events:all}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:8px;width:100%;max-width:560px;padding:26px;margin:auto}
.modal-close{float:right;width:28px;height:28px;border:1px solid var(--border);border-radius:50%;background:transparent;color:var(--muted);cursor:pointer}
.modal-close:hover{border-color:var(--gold-dim);color:var(--cream)}
.profile-hd{display:flex;align-items:center;gap:14px;margin-bottom:18px}
.profile-avatar{width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#2a1f08,#1a1a16);border:1px solid var(--gold-dim);display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:700;color:var(--gold)}
.profile-name{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:var(--cream)}
.profile-sub{font-size:12px;color:var(--muted)}

.detail-row{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:18px}
.detail-block{background:var(--card);border:1px solid var(--border);border-radius:5px;padding:10px 12px}
.detail-label{font-size:9.5px;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);margin-bottom:3px}
.detail-val{font-size:13px;color:var(--cream);font-weight:500}

.history-list{border:1px solid var(--border);border-radius:5px;overflow:hidden;max-height:260px;overflow-y:auto}
.hist-row{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;font-size:12.5px;border-bottom:1px solid rgba(44,44,36,0.5)}
.hist-row:last-child{border-bottom:none}
.hist-left{color:var(--muted)}
.hist-left strong{color:var(--text);font-weight:500}
.order-status{display:inline-flex;align-items:center;padding:2px 8px;border-radius:3px;font-size:9.5px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase}
.order-status.pending{background:var(--amber-pale);color:var(--amber)}
.order-status.preparing{background:var(--blue-pale);color:var(--blue)}
.order-status.ready{background:rgba(201,168,76,0.1);color:var(--gold)}
.order-status.completed{background:rgba(74,122,58,0.1);color:var(--green-lt)}
.order-status.cancelled{background:var(--red-pale);color:#e05a5a}
.hist-total{color:var(--gold);font-family:'Cormorant Garamond',serif;font-size:14px}
.empty-hist{padding:24px;text-align:center;color:var(--muted);font-size:12.5px}

@media(max-width:1100px){.stat-row{grid-template-columns:repeat(3,1fr)}}
@media(max-width:768px){.page-body{padding:20px 16px 48px}.stat-row{grid-template-columns:1fr 1fr}.detail-row{grid-template-columns:1fr 1fr}}
</style>
</head>
<body>

<aside id="sidebar">
    <div class="sb-brand">
        <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e05a5a" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div><div class="sb-title">SIPPERÉ <span>Café</span></div><div class="sb-sub">Admin Panel</div></div>
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
        <div><div class="topbar-sub">Admin Panel</div><div class="topbar-title">Customer Management</div></div>
        <div class="topbar-user">Logged in as <span><?= htmlspecialchars($_SESSION['admin']['username']) ?></span></div>
    </div>

    <div class="page-body">

        <!-- STATS -->
        <div class="stat-row">
            <div class="stat-card"><div class="stat-label">Total Customers</div><div class="stat-value"><?= $totalCustomers ?></div></div>
            <div class="stat-card"><div class="stat-label">Active Buyers</div><div class="stat-value"><?= $activeCustomers ?></div></div>
            <div class="stat-card"><div class="stat-label">New This Month</div><div class="stat-value"><?= $newThisMonth ?></div></div>
            <div class="stat-card"><div class="stat-label">Avg Spend / Customer</div><div class="stat-value gold">₱<?= number_format($avgSpend, 0) ?></div></div>
            <div class="stat-card"><div class="stat-label">Top Spender</div><div class="stat-value gold" style="font-size:18px"><?= htmlspecialchars($topSpender) ?></div></div>
        </div>

        <!-- SEARCH -->
        <div class="search-wrap">
            <svg class="s-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="searchInput" placeholder="Search customers…" oninput="filterTable()">
        </div>

        <!-- CUSTOMERS TABLE -->
        <div>
            <div class="section-hd"><div class="section-title">Customer Profiles</div><div class="section-hd-line"></div></div>
            <div class="table-card">
                <table id="custTable">
                    <thead><tr><th>Customer</th><th>Total Orders</th><th>Total Spent</th><th>Last Order</th><th>Favorite Product</th><th></th></tr></thead>
                    <tbody>
                    <?php if (empty($customers)): ?>
                        <tr class="empty-row"><td colspan="6">No customers yet.</td></tr>
                    <?php else: foreach ($customers as $c):
                        $fav = $favoriteByUser[$c['id']] ?? null;
                    ?>
                        <tr data-name="<?= htmlspecialchars(strtolower($c['username'])) ?>">
                            <td>
                                <div class="cust-info">
                                    <div class="cust-avatar"><?= strtoupper(substr($c['username'], 0, 2)) ?></div>
                                    <div>
                                        <div style="font-weight:500;color:var(--cream)"><?= htmlspecialchars($c['username']) ?></div>
                                        <div style="font-size:11px;color:var(--muted)">ID #<?= $c['id'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= number_format($c['total_orders']) ?> orders</td>
                            <td style="color:var(--gold);font-family:'Cormorant Garamond',serif;font-size:15px">₱<?= number_format($c['total_spent'], 2) ?></td>
                            <td style="color:var(--muted);font-size:12px"><?= $c['last_order'] ? date('M d, Y', strtotime($c['last_order'])) : 'No orders yet' ?></td>
                            <td><?= $fav ? '<span class="fav-pill">' . htmlspecialchars($fav) . '</span>' : '<span style="color:var(--muted)">—</span>' ?></td>
                            <td><button class="view-btn" onclick="openCustomer(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['username'])) ?>', <?= (int)$c['total_orders'] ?>, <?= (float)$c['total_spent'] ?>, '<?= $fav ? htmlspecialchars(addslashes($fav)) : '—' ?>')">View Profile</button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- CUSTOMER PROFILE MODAL -->
<div class="modal-overlay" id="custModal" onclick="if(event.target===this)closeCustomer()">
    <div class="modal">
        <button class="modal-close" onclick="closeCustomer()">✕</button>
        <div class="profile-hd">
            <div class="profile-avatar" id="pAvatar">—</div>
            <div>
                <div class="profile-name" id="pName">—</div>
                <div class="profile-sub" id="pSub">—</div>
            </div>
        </div>
        <div class="detail-row">
            <div class="detail-block"><div class="detail-label">Total Orders</div><div class="detail-val" id="pOrders">—</div></div>
            <div class="detail-block"><div class="detail-label">Total Spent</div><div class="detail-val" id="pSpent">—</div></div>
            <div class="detail-block"><div class="detail-label">Favorite Product</div><div class="detail-val" id="pFav">—</div></div>
        </div>
        <div class="section-hd" style="margin-bottom:10px"><div class="section-title" style="font-size:15px">Order History</div><div class="section-hd-line"></div></div>
        <div class="history-list" id="histList"><div class="empty-hist">Loading…</div></div>
    </div>
</div>

<script>

document.querySelectorAll('.nav-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const group = btn.closest('.nav-group');
        const isOpen = group.classList.toggle('open');
        btn.setAttribute('aria-expanded', isOpen);
    });
});

function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#custTable tbody tr').forEach(r => {
        if (!r.dataset.name) return;
        r.style.display = r.dataset.name.includes(q) ? '' : 'none';
    });
}

const STATUS_LABELS = { pending: 'Pending', preparing: 'Preparing', ready: 'Ready', completed: 'Completed', cancelled: 'Cancelled' };

function openCustomer(id, name, orders, spent, fav) {
    document.getElementById('pAvatar').textContent = name.substring(0, 2).toUpperCase();
    document.getElementById('pName').textContent = name;
    document.getElementById('pSub').textContent = 'Customer ID #' + id;
    document.getElementById('pOrders').textContent = orders + ' orders';
    document.getElementById('pSpent').textContent = '₱' + spent.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
    document.getElementById('pFav').textContent = fav;
    document.getElementById('histList').innerHTML = '<div class="empty-hist">Loading…</div>';
    document.getElementById('custModal').classList.add('show');

    fetch('customer_handler.php?action=get_orders&user_id=' + id)
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('histList');
            if (!data.success || !data.orders || !data.orders.length) {
                list.innerHTML = '<div class="empty-hist">No order history yet.</div>';
                return;
            }
            let html = '';
            data.orders.forEach(o => {
                const date = new Date(o.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                html += `<div class="hist-row">
                    <div class="hist-left">#ORD-${String(o.id).padStart(4,'0')} <strong>· ${date}</strong> · ${(o.branch||'laguna').charAt(0).toUpperCase()+(o.branch||'laguna').slice(1)}</div>
                    <div style="display:flex;align-items:center;gap:10px">
                        <span class="order-status ${o.status}">${STATUS_LABELS[o.status] || o.status}</span>
                        <span class="hist-total">₱${parseFloat(o.total).toFixed(2)}</span>
                    </div>
                </div>`;
            });
            list.innerHTML = html;
        })
        .catch(() => { document.getElementById('histList').innerHTML = '<div class="empty-hist">Failed to load history.</div>'; });
}
function closeCustomer() { document.getElementById('custModal').classList.remove('show'); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCustomer(); });
</script>
</body>
</html>
