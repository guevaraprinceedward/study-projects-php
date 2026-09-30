<?php
include 'admin-config.php';
requireAdmin();

$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS stock INT NOT NULL DEFAULT 100");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS reorder_level INT NOT NULL DEFAULT 10");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS branch VARCHAR(20) DEFAULT 'laguna'");
$conn->query("
    CREATE TABLE IF NOT EXISTS inventory_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        type ENUM('stock_in','stock_out','damaged','restock','adjustment') NOT NULL,
        quantity INT NOT NULL,
        previous_stock INT NOT NULL,
        new_stock INT NOT NULL,
        reason VARCHAR(255) DEFAULT NULL,
        admin_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$typeLabels = [
    'stock_in'   => 'Stock In',
    'stock_out'  => 'Stock Out',
    'damaged'    => 'Damaged',
    'restock'    => 'Restocked',
    'adjustment' => 'Adjustment',
];

$currentPage = basename($_SERVER['PHP_SELF']);
$isOwner  = ($_SESSION['admin']['role'] ?? '') === 'owner';
$invOpen  = in_array($currentPage, ['admin-inventory.php', 'admin-products.php']);
$attOpen  = in_array($currentPage, ['admin-attendance.php', 'admin-leave-overtime.php']);

// Pending count para sa badge (Owner lang). Naka-try/catch kasi baka wala pa ang tables.
$pendingCount = 0;
if ($isOwner) {
    try {
        $pendingCount += (int)$conn->query("SELECT COUNT(*) c FROM leave_requests WHERE status='pending'")->fetch_assoc()['c'];
        $pendingCount += (int)$conn->query("SELECT COUNT(*) c FROM overtime_requests WHERE status='pending'")->fetch_assoc()['c'];
    } catch (Throwable $e) { $pendingCount = 0; }
}

// ── PRODUCTS (current stock) ────────────────────────────────────────────
$products = $conn->query("SELECT id, name, branch, category, stock, reorder_level FROM products ORDER BY branch ASC, name ASC")->fetch_all(MYSQLI_ASSOC);

$totalProducts  = count($products);
$totalUnits     = array_sum(array_column($products, 'stock'));
$lowStockCount  = count(array_filter($products, fn($p) => (int)$p['stock'] > 0 && (int)$p['stock'] <= (int)$p['reorder_level']));
$outOfStockCount= count(array_filter($products, fn($p) => (int)$p['stock'] <= 0));

$today = date('Y-m-d');
$movementsToday = $conn->query("SELECT COUNT(*) AS c FROM inventory_movements WHERE DATE(created_at) = '$today'")->fetch_assoc()['c'] ?? 0;

// ── HISTORY FILTERS ──────────────────────────────────────────────────────
$fProduct = (int)($_GET['product_id'] ?? 0);
$fType    = $_GET['type'] ?? '';
$fFrom    = $_GET['date_from'] ?? '';
$fTo      = $_GET['date_to'] ?? '';

$where = ["1=1"];
if ($fProduct > 0) $where[] = "m.product_id = $fProduct";
if ($fType && isset($typeLabels[$fType])) $where[] = "m.type = '" . $conn->real_escape_string($fType) . "'";
if ($fFrom) $where[] = "DATE(m.created_at) >= '" . $conn->real_escape_string($fFrom) . "'";
if ($fTo)   $where[] = "DATE(m.created_at) <= '" . $conn->real_escape_string($fTo) . "'";
$whereSql = implode(' AND ', $where);

$history = $conn->query("
    SELECT m.*, p.name AS product_name, p.branch AS product_branch, a.username AS admin_username
    FROM inventory_movements m
    LEFT JOIN products p ON p.id = m.product_id
    LEFT JOIN admins a ON a.id = m.admin_id
    WHERE $whereSql
    ORDER BY m.created_at DESC
    LIMIT 200
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory Management — AyosCoffeeNegosyo</title>
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

.sb-footer{padding:10px;border-top:1px solid var(--border)}
.nav-item.logout{color:#e05a5a}
.nav-item.logout:hover{background:rgba(224,90,90,0.08)}

#mainContent{margin-left:var(--sidebar-w);flex:1;min-width:0;position:relative;z-index:1;display:flex;flex-direction:column}
.topbar{position:sticky;top:0;z-index:100;background:rgba(11,11,9,0.9);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:0 32px;height:64px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.topbar-sub{font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#e05a5a}
.topbar-title{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream)}
.topbar-user{font-size:12.5px;color:var(--muted)}
.topbar-user span{color:var(--gold);font-weight:500}

.page-body{padding:28px 32px 60px;display:flex;flex-direction:column;gap:24px;max-width:1400px}

.stat-row{display:grid;grid-template-columns:repeat(5,1fr);gap:16px}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:18px 20px;position:relative;overflow:hidden}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--gold-dim),transparent)}
.stat-label{font-size:10px;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted)}
.stat-value{font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:var(--cream);margin-top:4px}
.stat-value.red{color:#e05a5a}
.stat-value.amber{color:var(--amber)}
.stat-value.gold{color:var(--gold)}

.section-hd{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.section-hd-line{flex:1;height:1px;background:linear-gradient(90deg,var(--border),transparent)}
.section-title{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--cream)}

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
.stock-bar-wrap{display:flex;align-items:center;gap:8px}
.stock-bar-bg{flex:1;height:4px;background:var(--border);border-radius:2px;min-width:50px}
.stock-bar-fill{height:100%;border-radius:2px}
.stock-bar-fill.ok{background:var(--green-lt)}
.stock-bar-fill.warn{background:var(--amber)}
.stock-bar-fill.danger{background:var(--red)}
.record-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border-radius:3px;border:1px solid var(--gold-dim);background:rgba(201,168,76,0.08);color:var(--gold);font-family:'Jost',sans-serif;font-size:11.5px;font-weight:500;letter-spacing:0.05em;cursor:pointer;transition:all 0.18s}
.record-btn:hover{background:rgba(201,168,76,0.18)}

.move-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:3px;font-size:10px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase}
.move-badge.stock_in{background:var(--blue-pale);color:var(--blue)}
.move-badge.restock{background:rgba(74,122,58,0.12);color:var(--green-lt)}
.move-badge.stock_out{background:var(--amber-pale);color:var(--amber)}
.move-badge.damaged{background:var(--red-pale);color:#e05a5a}
.move-badge.adjustment{background:rgba(201,168,76,0.1);color:var(--gold)}
.delta-val{font-family:'Cormorant Garamond',serif;font-size:15px;font-weight:700}
.delta-val.pos{color:var(--green-lt)}
.delta-val.neg{color:#e05a5a}

.filter-bar{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:16px 20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.filter-label{font-size:11px;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted)}
.filter-input{background:var(--surface);border:1px solid var(--border);border-radius:3px;color:var(--cream);font-family:'Jost',sans-serif;font-size:13px;padding:8px 12px;outline:none}
.filter-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:#fff;cursor:pointer;text-decoration:none}
.filter-clear{font-size:11.5px;color:var(--muted);text-decoration:underline}

/* MODAL */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.75);backdrop-filter:blur(6px);z-index:400;display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;pointer-events:none;transition:opacity 0.25s}
.modal-overlay.show{opacity:1;pointer-events:all}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:8px;width:100%;max-width:480px;padding:26px;transform:translateY(16px);transition:transform 0.3s}
.modal-overlay.show .modal{transform:translateY(0)}
.modal h3{font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--cream);margin-bottom:4px}
.modal-sub{font-size:12px;color:var(--muted);margin-bottom:18px}
.form-group{margin-bottom:14px;display:flex;flex-direction:column;gap:6px}
.form-label{font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted)}
.form-input,.form-select{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:10px 12px;font-family:'Jost',sans-serif;font-size:13.5px;color:var(--text);outline:none;width:100%}
.type-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.type-opt{border:1px solid var(--border);border-radius:5px;padding:10px 12px;cursor:pointer;font-size:12.5px;text-align:center;transition:all 0.15s;color:var(--muted)}
.type-opt.selected{border-color:var(--gold);background:rgba(201,168,76,0.1);color:var(--gold)}
.preview-line{font-size:12.5px;color:var(--muted);background:var(--card);border:1px solid var(--border);border-radius:4px;padding:10px 12px;margin-top:4px}
.preview-line strong{color:var(--cream)}
.modal-footer{display:flex;justify-content:flex-end;gap:10px;margin-top:18px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:4px;font-family:'Jost',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:0.06em;text-transform:uppercase;cursor:pointer;border:none}
.btn-green{background:var(--green);color:#fff}.btn-green:hover{background:var(--green-lt)}.btn-green:disabled{opacity:0.5;cursor:not-allowed}
.btn-muted{background:transparent;border:1px solid var(--border);color:var(--muted)}.btn-muted:hover{border-color:var(--gold-dim);color:var(--gold)}

#toast{position:fixed;bottom:28px;right:28px;z-index:999;background:var(--card);border:1px solid var(--green);border-radius:5px;padding:12px 18px;font-size:13.5px;color:var(--cream);transform:translateY(16px);opacity:0;transition:all 0.3s;pointer-events:none;max-width:340px}
#toast.show{transform:translateY(0);opacity:1}
#toast.error{border-color:var(--red)}

@media(max-width:1100px){.stat-row{grid-template-columns:repeat(3,1fr)}}
@media(max-width:768px){.page-body{padding:20px 16px 48px}.stat-row{grid-template-columns:1fr 1fr}}
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
        <div><div class="topbar-sub">Admin Panel</div><div class="topbar-title">Inventory Management</div></div>
        <div class="topbar-user">Logged in as <span><?= htmlspecialchars($_SESSION['admin']['username']) ?></span></div>
    </div>

    <div class="page-body">

        <!-- SUMMARY -->
        <div class="stat-row">
            <div class="stat-card"><div class="stat-label">Total Products</div><div class="stat-value"><?= $totalProducts ?></div></div>
            <div class="stat-card"><div class="stat-label">Total Units in Stock</div><div class="stat-value gold"><?= number_format($totalUnits) ?></div></div>
            <div class="stat-card"><div class="stat-label">Low Stock</div><div class="stat-value amber"><?= $lowStockCount ?></div></div>
            <div class="stat-card"><div class="stat-label">Out of Stock</div><div class="stat-value red"><?= $outOfStockCount ?></div></div>
            <div class="stat-card"><div class="stat-label">Movements Today</div><div class="stat-value"><?= $movementsToday ?></div></div>
        </div>

        <!-- PRODUCT STOCK TABLE -->
        <div>
            <div class="section-hd"><div class="section-title">Current Stock by Product</div><div class="section-hd-line"></div></div>
            <div class="table-card">
                <table>
                    <thead><tr><th>Product</th><th>Branch</th><th>Category</th><th>Stock</th><th>Reorder Level</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if (empty($products)): ?>
                        <tr class="empty-row"><td colspan="6">No products yet.</td></tr>
                    <?php else: foreach ($products as $p):
                        $stock = (int)$p['stock']; $reorder = (int)$p['reorder_level'];
                        $pct = min(100, max(0, ($stock / max(1, $reorder * 3)) * 100));
                        $cls = $stock <= 0 ? 'danger' : ($stock <= $reorder ? 'warn' : 'ok');
                    ?>
                        <tr>
                            <td style="font-weight:500;color:var(--cream)"><?= htmlspecialchars($p['name']) ?></td>
                            <td><span class="branch-pill <?= $p['branch'] ?? 'laguna' ?>"><?= ucfirst($p['branch'] ?? 'laguna') ?></span></td>
                            <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($p['category'] ?? '—') ?></td>
                            <td>
                                <div class="stock-bar-wrap">
                                    <span style="min-width:26px;font-weight:500;color:<?= $stock<=0?'var(--red)':($stock<=$reorder?'var(--amber)':'var(--green-lt)') ?>"><?= $stock ?></span>
                                    <div class="stock-bar-bg"><div class="stock-bar-fill <?= $cls ?>" style="width:<?= $pct ?>%"></div></div>
                                </div>
                            </td>
                            <td style="color:var(--muted)"><?= $reorder ?></td>
                            <td>
                                <button class="record-btn" onclick="openMovement(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>', <?= $stock ?>)">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    Record Movement
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- HISTORY FILTERS -->
        <form method="GET" action="admin-inventory.php">
            <div class="filter-bar">
                <div class="filter-label">Product</div>
                <select name="product_id" class="filter-input">
                    <option value="0">All Products</option>
                    <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $fProduct===(int)$p['id']?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="filter-label">Type</div>
                <select name="type" class="filter-input">
                    <option value="">All Types</option>
                    <?php foreach ($typeLabels as $tv => $tl): ?>
                    <option value="<?= $tv ?>" <?= $fType===$tv?'selected':'' ?>><?= $tl ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="filter-label">From</div>
                <input type="date" name="date_from" class="filter-input" value="<?= htmlspecialchars($fFrom) ?>">
                <div class="filter-label">To</div>
                <input type="date" name="date_to" class="filter-input" value="<?= htmlspecialchars($fTo) ?>">
                <button type="submit" class="filter-btn">Filter</button>
                <?php if ($fProduct || $fType || $fFrom || $fTo): ?>
                <a href="admin-inventory.php" class="filter-clear">Clear filters</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- HISTORY -->
        <div>
            <div class="section-hd"><div class="section-title">Inventory Movement History</div><div class="section-hd-line"></div></div>
            <div class="table-card">
                <table>
                    <thead><tr><th>Date</th><th>Product</th><th>Branch</th><th>Type</th><th>Change</th><th>Previous → New</th><th>Reason</th><th>Recorded By</th></tr></thead>
                    <tbody>
                    <?php if (empty($history)): ?>
                        <tr class="empty-row"><td colspan="8">No movements recorded yet.</td></tr>
                    <?php else: foreach ($history as $h):
                        $qty = (int)$h['quantity'];
                        $deltaCls = $qty > 0 ? 'pos' : ($qty < 0 ? 'neg' : '');
                        $deltaStr = ($qty > 0 ? '+' : '') . $qty;
                    ?>
                        <tr>
                            <td style="color:var(--muted);font-size:12px;white-space:nowrap"><?= date('M j, Y h:i A', strtotime($h['created_at'])) ?></td>
                            <td style="font-weight:500;color:var(--cream)"><?= htmlspecialchars($h['product_name'] ?? '—') ?></td>
                            <td><span class="branch-pill <?= $h['product_branch'] ?? 'laguna' ?>"><?= ucfirst($h['product_branch'] ?? 'laguna') ?></span></td>
                            <td><span class="move-badge <?= $h['type'] ?>"><?= $typeLabels[$h['type']] ?? $h['type'] ?></span></td>
                            <td><span class="delta-val <?= $deltaCls ?>"><?= $deltaStr ?></span></td>
                            <td style="color:var(--muted)"><?= (int)$h['previous_stock'] ?> → <span style="color:var(--cream)"><?= (int)$h['new_stock'] ?></span></td>
                            <td style="color:var(--muted);font-size:12px;max-width:220px"><?= $h['reason'] ? htmlspecialchars($h['reason']) : '—' ?></td>
                            <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($h['admin_username'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- RECORD MOVEMENT MODAL -->
<div class="modal-overlay" id="moveModal" onclick="if(event.target===this)closeMovement()">
    <div class="modal">
        <h3 id="moveTitle">Record Stock Movement</h3>
        <div class="modal-sub" id="moveSub">Current stock: —</div>

        <div class="form-group">
            <label class="form-label">Movement Type</label>
            <div class="type-grid" id="typeGrid">
                <div class="type-opt" data-type="stock_in" onclick="selectType('stock_in')">📥 Stock In</div>
                <div class="type-opt" data-type="restock" onclick="selectType('restock')">🔄 Restocked</div>
                <div class="type-opt" data-type="stock_out" onclick="selectType('stock_out')">📤 Stock Out</div>
                <div class="type-opt" data-type="damaged" onclick="selectType('damaged')">⚠️ Damaged</div>
                <div class="type-opt" data-type="adjustment" onclick="selectType('adjustment')" style="grid-column:1/3">📝 Manual Adjustment (physical count correction)</div>
            </div>
        </div>

        <div class="form-group" id="qtyGroup" style="display:none">
            <label class="form-label">Quantity</label>
            <input type="number" class="form-input" id="moveQty" min="1" placeholder="e.g. 25" oninput="updatePreview()">
        </div>

        <div class="form-group" id="newStockGroup" style="display:none">
            <label class="form-label">Actual Counted Stock</label>
            <input type="number" class="form-input" id="moveNewStock" min="0" placeholder="e.g. 123" oninput="updatePreview()">
        </div>

        <div class="form-group">
            <label class="form-label">Reason / Note <span id="reasonReq" style="display:none;color:#e05a5a">*</span></label>
            <input type="text" class="form-input" id="moveReason" placeholder="e.g. Delivered by supplier, spoiled milk, physical count...">
        </div>

        <div class="preview-line" id="movePreview" style="display:none"></div>

        <div class="modal-footer">
            <button class="btn btn-muted" onclick="closeMovement()">Cancel</button>
            <button class="btn btn-green" id="saveMoveBtn" onclick="saveMovement()" disabled>Save Movement</button>
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

let moveState = { productId: null, currentStock: 0, type: null };

function openMovement(id, name, stock) {
    moveState = { productId: id, currentStock: stock, type: null };
    document.getElementById('moveTitle').textContent = 'Record Movement — ' + name;
    document.getElementById('moveSub').textContent = 'Current stock: ' + stock + ' unit(s)';
    document.querySelectorAll('.type-opt').forEach(el => el.classList.remove('selected'));
    document.getElementById('qtyGroup').style.display = 'none';
    document.getElementById('newStockGroup').style.display = 'none';
    document.getElementById('movePreview').style.display = 'none';
    document.getElementById('moveQty').value = '';
    document.getElementById('moveNewStock').value = '';
    document.getElementById('moveReason').value = '';
    document.getElementById('reasonReq').style.display = 'none';
    document.getElementById('saveMoveBtn').disabled = true;
    document.getElementById('moveModal').classList.add('show');
}
function closeMovement() { document.getElementById('moveModal').classList.remove('show'); }

function selectType(type) {
    moveState.type = type;
    document.querySelectorAll('.type-opt').forEach(el => el.classList.toggle('selected', el.dataset.type === type));
    const isAdjustment = type === 'adjustment';
    document.getElementById('qtyGroup').style.display = isAdjustment ? 'none' : '';
    document.getElementById('newStockGroup').style.display = isAdjustment ? '' : 'none';
    document.getElementById('reasonReq').style.display = isAdjustment ? '' : 'none';
    updatePreview();
}

function updatePreview() {
    const prev = moveState.currentStock;
    const preview = document.getElementById('movePreview');
    const saveBtn = document.getElementById('saveMoveBtn');
    if (!moveState.type) { preview.style.display = 'none'; saveBtn.disabled = true; return; }

    let newStock = null;
    if (moveState.type === 'adjustment') {
        const v = document.getElementById('moveNewStock').value;
        if (v !== '' && !isNaN(v)) newStock = Math.max(0, parseInt(v));
    } else {
        const q = document.getElementById('moveQty').value;
        if (q !== '' && !isNaN(q) && parseInt(q) > 0) {
            const qty = parseInt(q);
            newStock = (moveState.type === 'stock_in' || moveState.type === 'restock') ? prev + qty : Math.max(0, prev - qty);
        }
    }

    if (newStock === null) { preview.style.display = 'none'; saveBtn.disabled = true; return; }

    preview.style.display = '';
    preview.innerHTML = 'Stock will change: <strong>' + prev + '</strong> → <strong>' + newStock + '</strong>';
    saveBtn.disabled = false;
}

function saveMovement() {
    if (!moveState.type) return;
    const reason = document.getElementById('moveReason').value.trim();
    if (moveState.type === 'adjustment' && !reason) { showToast('Reason is required for manual adjustments.', true); return; }

    const btn = document.getElementById('saveMoveBtn');
    btn.disabled = true; btn.textContent = 'Saving...';

    const fd = new FormData();
    fd.append('action', 'record_movement');
    fd.append('product_id', moveState.productId);
    fd.append('type', moveState.type);
    fd.append('reason', reason);
    if (moveState.type === 'adjustment') fd.append('new_stock', document.getElementById('moveNewStock').value);
    else fd.append('quantity', document.getElementById('moveQty').value);

    fetch('inventory_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) { showToast(data.message); closeMovement(); setTimeout(() => location.reload(), 900); }
            else { showToast(data.message || 'Failed to record movement.', true); btn.disabled = false; btn.textContent = 'Save Movement'; }
        })
        .catch(() => { showToast('Network error.', true); btn.disabled = false; btn.textContent = 'Save Movement'; });
}

let toastTimer;
function showToast(msg, isErr) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = isErr ? 'error show' : 'show';
    clearTimeout(toastTimer); toastTimer = setTimeout(() => t.className = '', 3200);
}
</script>
</body>
</html>
