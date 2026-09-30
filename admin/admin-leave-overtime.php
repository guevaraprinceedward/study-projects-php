<?php
include 'admin-config.php';
requireAdmin();

$conn->query("
    CREATE TABLE IF NOT EXISTS leave_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        leave_type ENUM('sick','vacation','emergency','other') NOT NULL DEFAULT 'sick',
        date_from DATE NOT NULL,
        date_to DATE NOT NULL,
        reason TEXT DEFAULT NULL,
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        reviewed_by INT DEFAULT NULL,
        reviewed_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");
$conn->query("
    CREATE TABLE IF NOT EXISTS overtime_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        date DATE NOT NULL,
        hours DECIMAL(4,2) NOT NULL,
        reason TEXT DEFAULT NULL,
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        reviewed_by INT DEFAULT NULL,
        reviewed_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$myAdminId = (int)($_SESSION['admin']['id'] ?? 0);
$myRole    = $_SESSION['admin']['role'] ?? 'staff';
$isOwner   = $myRole === 'owner';
$roleLabels = ['owner'=>'Owner','super_admin'=>'Super Admin','admin'=>'Admin','manager'=>'Manager','staff'=>'Staff'];
$leaveTypeLabels = ['sick'=>'Sick Leave','vacation'=>'Vacation Leave','emergency'=>'Emergency Leave','other'=>'Other'];

/* ── Dropdown roles ── */
$currentPage = basename($_SERVER['PHP_SELF']);
$invOpen = in_array($currentPage, ['admin-inventory.php', 'admin-products.php']);
$attOpen = in_array($currentPage, ['admin-attendance.php', 'admin-leave-overtime.php']);

// ── MY REQUESTS ───────────────────────────────────────────────────────────
$myLeaves = $conn->query("SELECT * FROM leave_requests WHERE admin_id = $myAdminId ORDER BY created_at DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);
$myOvertime = $conn->query("SELECT * FROM overtime_requests WHERE admin_id = $myAdminId ORDER BY created_at DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);

// ── OWNER: PENDING QUEUE + ALL HISTORY ───────────────────────────────────
$pendingLeaves = $pendingOvertime = $allLeaves = $allOvertime = [];
if ($isOwner) {
    $pendingLeaves = $conn->query("
        SELECT l.*, a.username, a.role FROM leave_requests l LEFT JOIN admins a ON a.id = l.admin_id
        WHERE l.status = 'pending' ORDER BY l.created_at ASC
    ")->fetch_all(MYSQLI_ASSOC);
    $pendingOvertime = $conn->query("
        SELECT o.*, a.username, a.role FROM overtime_requests o LEFT JOIN admins a ON a.id = o.admin_id
        WHERE o.status = 'pending' ORDER BY o.created_at ASC
    ")->fetch_all(MYSQLI_ASSOC);
    $allLeaves = $conn->query("
        SELECT l.*, a.username, a.role, r.username AS reviewer FROM leave_requests l
        LEFT JOIN admins a ON a.id = l.admin_id LEFT JOIN admins r ON r.id = l.reviewed_by
        WHERE l.status != 'pending' ORDER BY l.reviewed_at DESC LIMIT 30
    ")->fetch_all(MYSQLI_ASSOC);
    $allOvertime = $conn->query("
        SELECT o.*, a.username, a.role, r.username AS reviewer FROM overtime_requests o
        LEFT JOIN admins a ON a.id = o.admin_id LEFT JOIN admins r ON r.id = o.reviewed_by
        WHERE o.status != 'pending' ORDER BY o.reviewed_at DESC LIMIT 30
    ")->fetch_all(MYSQLI_ASSOC);
}

$pendingCount = count($pendingLeaves) + count($pendingOvertime);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Leave & Overtime — AyosCoffeeNegosyo</title>
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

.two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.panel{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:22px}
.panel-title{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--cream);margin-bottom:16px;display:flex;align-items:center;gap:8px}
.form-group{margin-bottom:12px;display:flex;flex-direction:column;gap:6px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.form-label{font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted)}
.form-input,.form-select{background:var(--surface);border:1px solid var(--border);border-radius:4px;padding:9px 12px;font-family:'Jost',sans-serif;font-size:13px;color:var(--text);outline:none;width:100%}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:4px;font-family:'Jost',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:0.06em;text-transform:uppercase;cursor:pointer;border:none}
.btn-green{margin-top: 5px;background:var(--green);color:#fff}.btn-green:hover{background:var(--green-lt)}.btn-green:disabled{opacity:0.5;cursor:not-allowed}
.btn-gold{background:rgba(201,168,76,0.1);border:1px solid var(--gold-dim);color:var(--gold)}.btn-gold:hover{background:rgba(201,168,76,0.18)}
.btn-red{background:var(--red-pale);border:1px solid rgba(192,57,43,0.4);color:#e05a5a}.btn-red:hover{background:rgba(192,57,43,0.2)}
.btn-muted{background:transparent;border:1px solid var(--border);color:var(--muted)}.btn-muted:hover{border-color:var(--gold-dim);color:var(--gold)}
.btn-sm{padding:5px 12px;font-size:11px}

.section-hd{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.section-hd-line{flex:1;height:1px;background:linear-gradient(90deg,var(--border),transparent)}
.section-title{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--cream)}

.table-card{background:var(--card);border:1px solid var(--border);border-radius:6px;overflow:hidden}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:var(--muted);padding:10px 16px;text-align:left;border-bottom:1px solid var(--border);font-weight:500;white-space:nowrap}
tbody td{padding:11px 16px;font-size:13px;border-bottom:1px solid rgba(44,44,36,0.5);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:rgba(255,255,255,0.02)}
.empty-row td{text-align:center;color:var(--muted);padding:28px 16px;font-size:13px}

.status-pill{display:inline-flex;padding:3px 9px;border-radius:3px;font-size:10px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase}
.status-pill.pending{background:var(--amber-pale);color:var(--amber)}
.status-pill.approved{background:rgba(74,122,58,0.12);color:var(--green-lt)}
.status-pill.rejected{background:var(--red-pale);color:#e05a5a}
.type-pill{display:inline-flex;padding:2px 8px;border-radius:3px;font-size:10px;background:var(--blue-pale);color:var(--blue)}
.role-pill{display:inline-flex;padding:2px 7px;border-radius:3px;font-size:9px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase}
.role-pill.owner{background:rgba(201,168,76,0.14);color:var(--gold)}
.role-pill.super_admin{background:rgba(192,57,43,0.12);color:#e05a5a}
.role-pill.admin{background:rgba(59,130,246,0.1);color:#60a5fa}
.role-pill.manager{background:rgba(74,122,58,0.12);color:var(--green-lt)}
.role-pill.staff{background:rgba(107,107,88,0.12);color:var(--muted)}



#toast{position:fixed;bottom:28px;right:28px;z-index:999;background:var(--card);border:1px solid var(--green);border-radius:5px;padding:12px 18px;font-size:13.5px;color:var(--cream);transform:translateY(16px);opacity:0;transition:all 0.3s;pointer-events:none;max-width:340px}
#toast.show{transform:translateY(0);opacity:1}
#toast.error{border-color:var(--red)}

@media(max-width:900px){.two-col{grid-template-columns:1fr}.form-row{grid-template-columns:1fr}}
@media(max-width:768px){.page-body{padding:20px 16px 48px}}
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
        <a href="admin-dashboard.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></span>
            Dashboard
        </a>
        <a href="admin-analytics.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
            Analytics
        </a>
        <a href="admin-orders.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span>
            Orders & Sales
        </a>

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

        <a href="admin-customers.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.5-7 8-7s8 3 8 7"/></svg></span>
            Customers
        </a>
        <a href="admin-users.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span>
            User Management
        </a>

        <div class="nav-group <?= $attOpen ? 'open has-active' : '' ?>">
                <button type="button" class="nav-item nav-toggle" aria-expanded="<?= $attOpen ? 'true' : 'false' ?>">
                    <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                    Attendance
                    <?php if ($isOwner && $pendingCount > 0): ?><span class="nav-badge"><?= $pendingCount ?></span><?php endif; ?>
                    <span class="nav-chevron"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></span>
                </button>
            <div class="nav-sub">
                <div class="nav-sub-inner">
                    <a href="admin-attendance.php" class="nav-item <?= $currentPage === 'admin-attendance.php' ? 'active' : '' ?>">Overview</a>
                    <a href="admin-leave-overtime.php" class="nav-item <?= $currentPage === 'admin-leave-overtime.php' ? 'active' : '' ?>">
                        Leave & Overtime
                    </a>
                </div>
            </div>
        </div>
        <?php if ($isOwner): ?>
        <a href="admin-payroll.php" class="nav-item">
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
        <div><div class="topbar-sub">Admin Panel</div><div class="topbar-title">Leave & Overtime</div></div>
        <div class="topbar-user">Logged in as <span><?= htmlspecialchars($_SESSION['admin']['username']) ?></span> <span class="role-pill <?= $myRole ?>"><?= $roleLabels[$myRole] ?? $myRole ?></span></div>
    </div>

    <div class="page-body">

        <!-- SUBMIT FORMS -->
        <div class="two-col">
            <div class="panel">
                <div class="panel-title">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Request Leave
                </div>
                <div class="form-group">
                    <label class="form-label">Leave Type</label>
                    <select class="form-select" id="lv_type">
                        <?php foreach ($leaveTypeLabels as $tv => $tl): ?>
                        <option value="<?= $tv ?>"><?= $tl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">From</label><input type="date" class="form-input" id="lv_from"></div>
                    <div class="form-group"><label class="form-label">To</label><input type="date" class="form-input" id="lv_to"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason</label>
                    <input type="text" class="form-input" id="lv_reason" placeholder="Brief reason (optional)">
                </div>
                <button class="btn btn-green" onclick="submitLeave()">Submit Leave Request</button>
            </div>

            <div class="panel">
                <div class="panel-title">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Request Overtime
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Date</label><input type="date" class="form-input" id="ot_date"></div>
                    <div class="form-group"><label class="form-label">Overtime Hours</label><input type="number" class="form-input" id="ot_hours" min="0.5" max="12" step="0.5" placeholder="e.g. 2"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason</label>
                    <input type="text" class="form-input" id="ot_reason" placeholder="e.g. Extended shift for inventory count">
                </div>
                <button class="btn btn-green" onclick="submitOvertime()">Submit Overtime Request</button>
                <p style="font-size:11px;color:var(--muted);margin-top:10px;line-height:1.5">Approved overtime is automatically included the next time the Owner generates your payroll for a period covering this date.</p>
            </div>
        </div>

        <?php if ($isOwner && ($pendingLeaves || $pendingOvertime)): ?>
        <!-- OWNER: PENDING REVIEW QUEUE -->
        <div>
            <div class="section-hd"><div class="section-title">Pending Review</div><div class="section-hd-line"></div></div>
            <div class="table-card">
                <table>
                    <thead><tr><th>Employee</th><th>Type</th><th>Details</th><th>Reason</th><th>Submitted</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($pendingLeaves as $l): ?>
                        <tr>
                            <td style="font-weight:500;color:var(--cream)"><?= htmlspecialchars($l['username'] ?? '—') ?> <span class="role-pill <?= $l['role'] ?? 'staff' ?>"><?= $roleLabels[$l['role'] ?? 'staff'] ?? '' ?></span></td>
                            <td><span class="type-pill">Leave — <?= $leaveTypeLabels[$l['leave_type']] ?? $l['leave_type'] ?></span></td>
                            <td style="color:var(--muted);font-size:12px"><?= date('M j', strtotime($l['date_from'])) ?> – <?= date('M j, Y', strtotime($l['date_to'])) ?></td>
                            <td style="color:var(--muted);font-size:12px;max-width:200px"><?= $l['reason'] ? htmlspecialchars($l['reason']) : '—' ?></td>
                            <td style="color:var(--muted);font-size:12px"><?= date('M j', strtotime($l['created_at'])) ?></td>
                            <td>
                                <button class="btn btn-gold btn-sm" onclick="review('leave', <?= $l['id'] ?>, 'approved')">Approve</button>
                                <button class="btn btn-red btn-sm" onclick="review('leave', <?= $l['id'] ?>, 'rejected')">Reject</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php foreach ($pendingOvertime as $o): ?>
                        <tr>
                            <td style="font-weight:500;color:var(--cream)"><?= htmlspecialchars($o['username'] ?? '—') ?> <span class="role-pill <?= $o['role'] ?? 'staff' ?>"><?= $roleLabels[$o['role'] ?? 'staff'] ?? '' ?></span></td>
                            <td><span class="type-pill" style="background:rgba(201,168,76,0.1);color:var(--gold)">Overtime — <?= $o['hours'] ?>h</span></td>
                            <td style="color:var(--muted);font-size:12px"><?= date('M j, Y', strtotime($o['date'])) ?></td>
                            <td style="color:var(--muted);font-size:12px;max-width:200px"><?= $o['reason'] ? htmlspecialchars($o['reason']) : '—' ?></td>
                            <td style="color:var(--muted);font-size:12px"><?= date('M j', strtotime($o['created_at'])) ?></td>
                            <td>
                                <button class="btn btn-gold btn-sm" onclick="review('overtime', <?= $o['id'] ?>, 'approved')">Approve</button>
                                <button class="btn btn-red btn-sm" onclick="review('overtime', <?= $o['id'] ?>, 'rejected')">Reject</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- MY REQUESTS -->
        <div>
            <div class="section-hd"><div class="section-title">My Leave Requests</div><div class="section-hd-line"></div></div>
            <div class="table-card">
                <table>
                    <thead><tr><th>Type</th><th>Dates</th><th>Reason</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
                    <tbody>
                    <?php if (empty($myLeaves)): ?>
                        <tr class="empty-row"><td colspan="6">No leave requests yet.</td></tr>
                    <?php else: foreach ($myLeaves as $l): ?>
                        <tr>
                            <td><?= $leaveTypeLabels[$l['leave_type']] ?? $l['leave_type'] ?></td>
                            <td style="color:var(--muted);font-size:12px"><?= date('M j', strtotime($l['date_from'])) ?> – <?= date('M j, Y', strtotime($l['date_to'])) ?></td>
                            <td style="color:var(--muted);font-size:12px"><?= $l['reason'] ? htmlspecialchars($l['reason']) : '—' ?></td>
                            <td><span class="status-pill <?= $l['status'] ?>"><?= ucfirst($l['status']) ?></span></td>
                            <td style="color:var(--muted);font-size:12px"><?= date('M j, Y', strtotime($l['created_at'])) ?></td>
                            <td><?php if ($l['status']==='pending'): ?><button class="btn btn-muted btn-sm" onclick="cancelReq('leave', <?= $l['id'] ?>)">Cancel</button><?php endif; ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <div class="section-hd"><div class="section-title">My Overtime Requests</div><div class="section-hd-line"></div></div>
            <div class="table-card">
                <table>
                    <thead><tr><th>Date</th><th>Hours</th><th>Reason</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
                    <tbody>
                    <?php if (empty($myOvertime)): ?>
                        <tr class="empty-row"><td colspan="6">No overtime requests yet.</td></tr>
                    <?php else: foreach ($myOvertime as $o): ?>
                        <tr>
                            <td><?= date('M j, Y', strtotime($o['date'])) ?></td>
                            <td style="font-family:'Cormorant Garamond',serif;font-size:15px;color:var(--gold)"><?= $o['hours'] ?>h</td>
                            <td style="color:var(--muted);font-size:12px"><?= $o['reason'] ? htmlspecialchars($o['reason']) : '—' ?></td>
                            <td><span class="status-pill <?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                            <td style="color:var(--muted);font-size:12px"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                            <td><?php if ($o['status']==='pending'): ?><button class="btn btn-muted btn-sm" onclick="cancelReq('overtime', <?= $o['id'] ?>)">Cancel</button><?php endif; ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($isOwner && ($allLeaves || $allOvertime)): ?>
        <div>
            <div class="section-hd"><div class="section-title">Review History</div><div class="section-hd-line"></div></div>
            <div class="table-card">
                <table>
                    <thead><tr><th>Employee</th><th>Type</th><th>Details</th><th>Status</th><th>Reviewed By</th><th>Reviewed On</th></tr></thead>
                    <tbody>
                    <?php if (empty($allLeaves) && empty($allOvertime)): ?>
                        <tr class="empty-row"><td colspan="6">No reviewed requests yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($allLeaves as $l): ?>
                        <tr>
                            <td style="font-weight:500;color:var(--cream)"><?= htmlspecialchars($l['username'] ?? '—') ?></td>
                            <td><span class="type-pill">Leave — <?= $leaveTypeLabels[$l['leave_type']] ?? $l['leave_type'] ?></span></td>
                            <td style="color:var(--muted);font-size:12px"><?= date('M j', strtotime($l['date_from'])) ?> – <?= date('M j, Y', strtotime($l['date_to'])) ?></td>
                            <td><span class="status-pill <?= $l['status'] ?>"><?= ucfirst($l['status']) ?></span></td>
                            <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($l['reviewer'] ?? '—') ?></td>
                            <td style="color:var(--muted);font-size:12px"><?= $l['reviewed_at'] ? date('M j, Y', strtotime($l['reviewed_at'])) : '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php foreach ($allOvertime as $o): ?>
                        <tr>
                            <td style="font-weight:500;color:var(--cream)"><?= htmlspecialchars($o['username'] ?? '—') ?></td>
                            <td><span class="type-pill" style="background:rgba(201,168,76,0.1);color:var(--gold)">Overtime — <?= $o['hours'] ?>h</span></td>
                            <td style="color:var(--muted);font-size:12px"><?= date('M j, Y', strtotime($o['date'])) ?></td>
                            <td><span class="status-pill <?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                            <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($o['reviewer'] ?? '—') ?></td>
                            <td style="color:var(--muted);font-size:12px"><?= $o['reviewed_at'] ? date('M j, Y', strtotime($o['reviewed_at'])) : '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<div id="toast"></div>

<script>
let toastTimer;
function showToast(msg, isErr) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = isErr ? 'error show' : 'show';
    clearTimeout(toastTimer); toastTimer = setTimeout(() => t.className = '', 3000);
}

function submitLeave() {
    const from = document.getElementById('lv_from').value;
    const to = document.getElementById('lv_to').value;
    if (!from || !to) { showToast('Please select both dates.', true); return; }
    const fd = new FormData();
    fd.append('action', 'submit_leave');
    fd.append('leave_type', document.getElementById('lv_type').value);
    fd.append('date_from', from);
    fd.append('date_to', to);
    fd.append('reason', document.getElementById('lv_reason').value);
    fetch('leave_overtime_handler.php', { method:'POST', body: fd })
        .then(r=>r.json()).then(d=>{
            if (d.success) { showToast(d.message); setTimeout(()=>location.reload(), 900); }
            else showToast(d.message || 'Failed.', true);
        }).catch(()=>showToast('Network error.', true));
}

function submitOvertime() {
    const date = document.getElementById('ot_date').value;
    const hours = document.getElementById('ot_hours').value;
    if (!date || !hours) { showToast('Please fill in date and hours.', true); return; }
    const fd = new FormData();
    fd.append('action', 'submit_overtime');
    fd.append('date', date);
    fd.append('hours', hours);
    fd.append('reason', document.getElementById('ot_reason').value);
    fetch('leave_overtime_handler.php', { method:'POST', body: fd })
        .then(r=>r.json()).then(d=>{
            if (d.success) { showToast(d.message); setTimeout(()=>location.reload(), 900); }
            else showToast(d.message || 'Failed.', true);
        }).catch(()=>showToast('Network error.', true));
}

function review(kind, id, decision) {
    if (!confirm((decision === 'approved' ? 'Approve' : 'Reject') + ' this request?')) return;
    const fd = new FormData();
    fd.append('action', 'review_' + kind);
    fd.append('id', id);
    fd.append('decision', decision);
    fetch('leave_overtime_handler.php', { method:'POST', body: fd })
        .then(r=>r.json()).then(d=>{
            if (d.success) { showToast(d.message); setTimeout(()=>location.reload(), 700); }
            else showToast(d.message || 'Failed.', true);
        }).catch(()=>showToast('Network error.', true));
}

function cancelReq(kind, id) {
    if (!confirm('Cancel this request?')) return;
    const fd = new FormData();
    fd.append('action', 'cancel_' + kind);
    fd.append('id', id);
    fetch('leave_overtime_handler.php', { method:'POST', body: fd })
        .then(r=>r.json()).then(d=>{
            if (d.success) { showToast(d.message); setTimeout(()=>location.reload(), 700); }
            else showToast(d.message || 'Failed.', true);
        }).catch(()=>showToast('Network error.', true));
}

document.querySelectorAll('.nav-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const group = btn.closest('.nav-group');
        const isOpen = group.classList.toggle('open');
        btn.setAttribute('aria-expanded', isOpen);
    });
});
</script>
</body>
</html>
