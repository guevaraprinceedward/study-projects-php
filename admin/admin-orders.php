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
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(20) DEFAULT 'cash'");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS guest_name VARCHAR(150) DEFAULT NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS inventory_deducted TINYINT(1) DEFAULT 0");
$conn->query("ALTER TABLE order_items ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) DEFAULT NULL");

$statusFlow = ['pending' => 'Pending', 'preparing' => 'Preparing', 'ready' => 'Ready', 'completed' => 'Completed'];
$allStatuses = $statusFlow + ['cancelled' => 'Cancelled'];

// ── FILTERS ────────────────────────────────────────────────────────────────
$fBranch = $_GET['branch'] ?? '';
$fStatus = $_GET['status'] ?? '';
$fDate   = $_GET['date'] ?? '';

$where = ["1=1"];
if ($fBranch && in_array($fBranch, ['laguna','manila'], true)) $where[] = "o.branch = '" . $conn->real_escape_string($fBranch) . "'";
if ($fStatus && isset($allStatuses[$fStatus])) $where[] = "o.status = '" . $conn->real_escape_string($fStatus) . "'";
if ($fDate) $where[] = "DATE(o.created_at) = '" . $conn->real_escape_string($fDate) . "'";
$whereSql = implode(' AND ', $where);

// ── STATS (today, all branches) ──────────────────────────────────────────
$today = date('Y-m-d');
$statCounts = [];
foreach (array_keys($allStatuses) as $st) {
    $statCounts[$st] = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status = '$st' AND DATE(created_at) = '$today'")->fetch_assoc()['c'] ?? 0;
}
$salesToday = $conn->query("
    SELECT COALESCE(SUM(oi.quantity * oi.price), 0) AS total
    FROM orders o JOIN order_items oi ON o.id = oi.order_id
    WHERE DATE(o.created_at) = '$today' AND o.status != 'cancelled'
")->fetch_assoc()['total'] ?? 0;

// ── ORDERS LIST ───────────────────────────────────────────────────────────
$orders = $conn->query("
    SELECT o.*,
           CASE WHEN u.username IS NOT NULL THEN u.username
                WHEN o.guest_name IS NOT NULL AND o.guest_name != '' THEN CONCAT(o.guest_name, ' (Guest)')
                ELSE 'Guest' END AS customer,
           (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count,
           (SELECT COALESCE(SUM(oi.quantity * oi.price),0) FROM order_items oi WHERE oi.order_id = o.id) AS computed_total
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE $whereSql
    ORDER BY o.created_at DESC
    LIMIT 150
")->fetch_all(MYSQLI_ASSOC);

// Pre-fetch items for all listed orders in one go (for the detail modal, embedded as JSON)
$orderIds = array_column($orders, 'id');
$itemsByOrder = [];
if (!empty($orderIds)) {
    $idsCsv = implode(',', array_map('intval', $orderIds));
    $itemRows = $conn->query("
        SELECT oi.order_id, p.name, oi.quantity, oi.price
        FROM order_items oi
        LEFT JOIN products p ON p.id = oi.product_id
        WHERE oi.order_id IN ($idsCsv)
    ")->fetch_all(MYSQLI_ASSOC);
    foreach ($itemRows as $ir) {
        $itemsByOrder[$ir['order_id']][] = ['name' => $ir['name'] ?? 'Unknown item', 'quantity' => (int)$ir['quantity'], 'price' => (float)$ir['price']];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Orders & Sales — SIPPERÉ Café</title>
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

.page-body{padding:28px 32px 60px;display:flex;flex-direction:column;gap:24px;max-width:1400px}

.stat-row{display:grid;grid-template-columns:repeat(6,1fr);gap:14px}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:16px 18px;position:relative;overflow:hidden}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--gold-dim),transparent)}
.stat-label{font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted)}
.stat-value{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--cream);margin-top:4px}
.stat-value.gold{color:var(--gold)}
.stat-value.amber{color:var(--amber)}
.stat-value.blue{color:var(--blue)}
.stat-value.green{color:var(--green-lt)}
.stat-value.red{color:#e05a5a}

.section-hd{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.section-hd-line{flex:1;height:1px;background:linear-gradient(90deg,var(--border),transparent)}
.section-title{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--cream)}

.filter-bar{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:16px 20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.filter-label{font-size:11px;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted)}
.filter-input{background:var(--surface);border:1px solid var(--border);border-radius:3px;color:var(--cream);font-family:'Jost',sans-serif;font-size:13px;padding:8px 12px;outline:none}
.filter-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:#fff;cursor:pointer}
.filter-clear{font-size:11.5px;color:var(--muted);text-decoration:underline}

.table-card{background:var(--card);border:1px solid var(--border);border-radius:6px;overflow:hidden}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:var(--muted);padding:10px 16px;text-align:left;border-bottom:1px solid var(--border);font-weight:500;white-space:nowrap}
tbody td{padding:11px 16px;font-size:13px;border-bottom:1px solid rgba(44,44,36,0.5);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:rgba(255,255,255,0.02)}
.empty-row td{text-align:center;color:var(--muted);padding:32px 16px;font-size:13px}
.branch-pill{display:inline-flex;padding:2px 7px;border-radius:3px;font-size:9px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase}
.branch-pill.laguna{background:rgba(74,122,58,0.1);color:var(--green-lt)}
.branch-pill.manila{background:rgba(201,168,76,0.08);color:var(--gold)}

.order-status{display:inline-flex;align-items:center;padding:3px 10px;border-radius:3px;font-size:10px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase}
.order-status.pending{background:var(--amber-pale);color:var(--amber)}
.order-status.preparing{background:var(--blue-pale);color:var(--blue)}
.order-status.ready{background:rgba(201,168,76,0.1);color:var(--gold)}
.order-status.completed{background:rgba(74,122,58,0.1);color:var(--green-lt)}
.order-status.cancelled{background:var(--red-pale);color:#e05a5a}

.view-btn{padding:6px 13px;border-radius:3px;border:1px solid var(--border);background:transparent;color:var(--muted);font-family:'Jost',sans-serif;font-size:11.5px;cursor:pointer;transition:all 0.18s}
.view-btn:hover{border-color:var(--gold-dim);color:var(--gold)}

/* MODAL */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.75);backdrop-filter:blur(6px);z-index:400;display:flex;align-items:flex-start;justify-content:center;padding:40px 20px;opacity:0;pointer-events:none;transition:opacity 0.25s;overflow-y:auto}
.modal-overlay.show{opacity:1;pointer-events:all}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:8px;width:100%;max-width:560px;padding:26px;margin:auto}
.modal h3{font-family:'Cormorant Garamond',serif;font-size:22px;color:var(--cream);margin-bottom:4px}
.modal-sub{font-size:12px;color:var(--muted);margin-bottom:18px}

.stepper{display:flex;align-items:center;gap:4px;margin-bottom:20px}
.step{flex:1;text-align:center;padding:8px 4px;border-radius:4px;font-size:10.5px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;background:var(--card);color:var(--muted);border:1px solid var(--border)}
.step.done{background:rgba(74,122,58,0.15);color:var(--green-lt);border-color:rgba(74,122,58,0.4)}
.step.current{background:rgba(201,168,76,0.15);color:var(--gold);border-color:var(--gold)}

.detail-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px}
.detail-block{background:var(--card);border:1px solid var(--border);border-radius:5px;padding:12px 14px}
.detail-label{font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);margin-bottom:4px}
.detail-val{font-size:13.5px;color:var(--cream);font-weight:500}

.item-list{border:1px solid var(--border);border-radius:5px;overflow:hidden;margin-bottom:16px}
.item-row{display:flex;justify-content:space-between;padding:10px 14px;font-size:13px;border-bottom:1px solid rgba(44,44,36,0.5)}
.item-row:last-child{border-bottom:none}
.item-row.total{background:var(--card);font-weight:600}
.item-row .name{color:var(--text)}
.item-row .qty{color:var(--muted);margin-left:6px}
.item-row .price{color:var(--gold);font-family:'Cormorant Garamond',serif;font-size:15px}

.modal-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:6px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:4px;font-family:'Jost',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:0.06em;text-transform:uppercase;cursor:pointer;border:none}
.btn-green{background:var(--green);color:#fff}.btn-green:hover{background:var(--green-lt)}
.btn-muted{background:transparent;border:1px solid var(--border);color:var(--muted)}.btn-muted:hover{border-color:var(--gold-dim);color:var(--gold)}
.btn-red{background:var(--red-pale);border:1px solid rgba(192,57,43,0.4);color:#e05a5a}.btn-red:hover{background:rgba(192,57,43,0.2)}
.modal-footer{display:flex;justify-content:space-between;align-items:center;margin-top:20px}

#toast{position:fixed;bottom:28px;right:28px;z-index:999;background:var(--card);border:1px solid var(--green);border-radius:5px;padding:12px 18px;font-size:13.5px;color:var(--cream);transform:translateY(16px);opacity:0;transition:all 0.3s;pointer-events:none;max-width:340px}
#toast.show{transform:translateY(0);opacity:1}
#toast.error{border-color:var(--red)}

@media(max-width:1100px){.stat-row{grid-template-columns:repeat(3,1fr)}}
@media(max-width:768px){.page-body{padding:20px 16px 48px}.stat-row{grid-template-columns:1fr 1fr}.detail-row{grid-template-columns:1fr}}
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
        <div><div class="topbar-sub">Admin Panel</div><div class="topbar-title">Orders & Sales</div></div>
        <div class="topbar-user">Logged in as <span><?= htmlspecialchars($_SESSION['admin']['username']) ?></span></div>
    </div>

    <div class="page-body">

        <!-- STATS -->
        <div class="stat-row">
            <div class="stat-card"><div class="stat-label">Pending</div><div class="stat-value amber"><?= $statCounts['pending'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Preparing</div><div class="stat-value blue"><?= $statCounts['preparing'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Ready</div><div class="stat-value gold"><?= $statCounts['ready'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Completed (Today)</div><div class="stat-value green"><?= $statCounts['completed'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Cancelled (Today)</div><div class="stat-value red"><?= $statCounts['cancelled'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Sales Today</div><div class="stat-value gold">₱<?= number_format($salesToday, 0) ?></div></div>
        </div>

        <!-- FILTERS -->
        <form method="GET" action="admin-orders.php">
            <div class="filter-bar">
                <div class="filter-label">Branch</div>
                <select name="branch" class="filter-input">
                    <option value="">All</option>
                    <option value="laguna" <?= $fBranch==='laguna'?'selected':'' ?>>Laguna</option>
                    <option value="manila" <?= $fBranch==='manila'?'selected':'' ?>>Manila</option>
                </select>
                <div class="filter-label">Status</div>
                <select name="status" class="filter-input">
                    <option value="">All</option>
                    <?php foreach ($allStatuses as $sv => $sl): ?>
                    <option value="<?= $sv ?>" <?= $fStatus===$sv?'selected':'' ?>><?= $sl ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="filter-label">Date</div>
                <input type="date" name="date" class="filter-input" value="<?= htmlspecialchars($fDate) ?>">
                <button type="submit" class="filter-btn">Filter</button>
                <?php if ($fBranch || $fStatus || $fDate): ?><a href="admin-orders.php" class="filter-clear">Clear filters</a><?php endif; ?>
            </div>
        </form>

        <!-- ORDERS TABLE -->
        <div>
            <div class="section-hd"><div class="section-title">Orders</div><div class="section-hd-line"></div></div>
            <div class="table-card">
                <table>
                    <thead><tr><th>Order #</th><th>Customer</th><th>Branch</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Placed</th><th></th></tr></thead>
                    <tbody>
                    <?php if (empty($orders)): ?>
                        <tr class="empty-row"><td colspan="9">No orders found for these filters.</td></tr>
                    <?php else: foreach ($orders as $o):
                        $total = $o['computed_total'] > 0 ? $o['computed_total'] : ($o['total'] ?? 0);
                    ?>
                        <tr>
                            <td style="color:var(--muted)">#ORD-<?= str_pad($o['id'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td style="font-weight:500;color:var(--cream)"><?= htmlspecialchars($o['customer'] ?? 'Guest') ?></td>
                            <td><span class="branch-pill <?= $o['branch'] ?? 'laguna' ?>"><?= ucfirst($o['branch'] ?? 'laguna') ?></span></td>
                            <td style="color:var(--muted)"><?= (int)$o['item_count'] ?> item(s)</td>
                            <td style="color:var(--gold);font-family:'Cormorant Garamond',serif;font-size:15px">₱<?= number_format($total, 2) ?></td>
                            <td style="color:var(--muted);font-size:12px"><?= ucfirst($o['payment_method'] ?? 'cash') ?></td>
                            <td><span class="order-status <?= htmlspecialchars($o['status']) ?>"><?= $allStatuses[$o['status']] ?? ucfirst($o['status']) ?></span></td>
                            <td style="color:var(--muted);font-size:12px;white-space:nowrap"><?= date('M j, h:i A', strtotime($o['created_at'])) ?></td>
                            <td><button class="view-btn" onclick='openOrder(<?= (int)$o["id"] ?>, <?= htmlspecialchars(json_encode($o), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($itemsByOrder[$o["id"]] ?? []), ENT_QUOTES) ?>)'>View</button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- ORDER DETAIL MODAL -->
<div class="modal-overlay" id="orderModal" onclick="if(event.target===this)closeOrder()">
    <div class="modal">
        <h3 id="modalOrderTitle">Order #—</h3>
        <div class="modal-sub" id="modalOrderSub">—</div>

        <div class="stepper" id="stepper"></div>

        <div class="detail-row">
            <div class="detail-block"><div class="detail-label">Customer</div><div class="detail-val" id="detCustomer">—</div></div>
            <div class="detail-block"><div class="detail-label">Branch</div><div class="detail-val" id="detBranch">—</div></div>
            <div class="detail-block"><div class="detail-label">Payment Method</div><div class="detail-val" id="detPayment">—</div></div>
            <div class="detail-block"><div class="detail-label">Placed On</div><div class="detail-val" id="detDate">—</div></div>
        </div>

        <div class="item-list" id="itemList"></div>

        <div class="modal-footer">
            <div class="modal-actions">
                <button class="btn btn-red" id="cancelBtn" onclick="advanceStatus('cancelled')">Cancel Order</button>
            </div>
            <div class="modal-actions">
                <button class="btn btn-muted" onclick="closeOrder()">Close</button>
                <button class="btn btn-green" id="advanceBtn" onclick="advanceNext()">Advance Status</button>
            </div>
        </div>
    </div>
</div>

<div id="toast"></div>

<script>

    document.querySelectorAll('.nav-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const group = btn.closest('.nav-group');
        const isOpen = group.classList.toggle('open');
        btn.setAttribute('aria-expanded', isOpen);
    });
});

const FLOW = ['pending', 'preparing', 'ready', 'completed'];
const FLOW_LABELS = { pending: 'Pending', preparing: 'Preparing', ready: 'Ready', completed: 'Completed' };
let currentOrder = { id: null, status: null };

function openOrder(id, order, items) {
    currentOrder = { id: id, status: order.status };
    document.getElementById('modalOrderTitle').textContent = 'Order #ORD-' + String(id).padStart(4, '0');
    document.getElementById('modalOrderSub').textContent = 'Placed ' + order.created_at;
    document.getElementById('detCustomer').textContent = order.customer || 'Guest';
    document.getElementById('detBranch').textContent = (order.branch || 'laguna').charAt(0).toUpperCase() + (order.branch || 'laguna').slice(1);
    document.getElementById('detPayment').textContent = (order.payment_method || 'cash').charAt(0).toUpperCase() + (order.payment_method || 'cash').slice(1);
    document.getElementById('detDate').textContent = order.created_at;

    const list = document.getElementById('itemList');
    let html = '';
    let total = 0;
    (items || []).forEach(it => {
        const lineTotal = it.quantity * it.price;
        total += lineTotal;
        html += `<div class="item-row"><span class="name">${it.name}<span class="qty">× ${it.quantity}</span></span><span class="price">₱${lineTotal.toFixed(2)}</span></div>`;
    });
    html += `<div class="item-row total"><span class="name">Total</span><span class="price">₱${total.toFixed(2)}</span></div>`;
    list.innerHTML = html || '<div class="item-row"><span class="name">No items recorded.</span></div>';

    renderStepper(order.status);
    document.getElementById('orderModal').classList.add('show');
}
function closeOrder() { document.getElementById('orderModal').classList.remove('show'); }

function renderStepper(status) {
    const stepper = document.getElementById('stepper');
    stepper.innerHTML = '';
    const idx = FLOW.indexOf(status);
    if (status === 'cancelled') {
        stepper.innerHTML = '<div class="step" style="background:rgba(192,57,43,0.15);color:#e05a5a;border-color:rgba(192,57,43,0.4)">Cancelled</div>';
        document.getElementById('advanceBtn').style.display = 'none';
        document.getElementById('cancelBtn').style.display = 'none';
        return;
    }
    FLOW.forEach((s, i) => {
        const cls = i < idx ? 'done' : (i === idx ? 'current' : '');
        stepper.innerHTML += `<div class="step ${cls}">${FLOW_LABELS[s]}</div>`;
    });
    const advanceBtn = document.getElementById('advanceBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    if (status === 'completed') {
        advanceBtn.style.display = 'none';
        cancelBtn.style.display = 'none';
    } else {
        advanceBtn.style.display = '';
        cancelBtn.style.display = '';
        const nextIdx = idx + 1;
        advanceBtn.textContent = 'Mark as ' + FLOW_LABELS[FLOW[nextIdx]];
    }
}

function advanceNext() {
    const idx = FLOW.indexOf(currentOrder.status);
    const next = FLOW[idx + 1];
    if (!next) return;
    updateStatus(next);
}
function advanceStatus(status) {
    if (status === 'cancelled' && !confirm('Cancel this order? This cannot be undone.')) return;
    updateStatus(status);
}

function updateStatus(status) {
    const fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('order_id', currentOrder.id);
    fd.append('status', status);
    fetch('order_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast(data.message);
                currentOrder.status = status;
                renderStepper(status);
                setTimeout(() => location.reload(), 900);
            } else {
                showToast(data.message || 'Failed to update order.', true);
            }
        })
        .catch(() => showToast('Network error.', true));
}

let toastTimer;
function showToast(msg, isErr) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = isErr ? 'error show' : 'show';
    clearTimeout(toastTimer); toastTimer = setTimeout(() => t.className = '', 3000);
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeOrder(); });
</script>
</body>
</html>
