<?php
include 'admin-config.php';
requireAdmin();

$conn->query("ALTER TABLE admins ADD COLUMN IF NOT EXISTS profile_photo TEXT DEFAULT NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'pending'");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS branch VARCHAR(20) DEFAULT 'laguna'");
$conn->query("ALTER TABLE order_items ADD COLUMN IF NOT EXISTS price decimal(10,2) DEFAULT NULL");

$adminId   = (int)($_SESSION['admin']['id'] ?? 0);
$adminRow  = $conn->query("SELECT username, role, profile_photo FROM admins WHERE id = $adminId LIMIT 1")->fetch_assoc();
$adminName = $adminRow['username'] ?? ($_SESSION['admin']['username'] ?? 'Admin');
$adminRole = $adminRow['role'] ?? ($_SESSION['admin']['role'] ?? 'staff');
$adminPhoto = $adminRow['profile_photo'] ?? null;
$roleLabels = ['owner'=>'Owner','super_admin'=>'Super Admin','admin'=>'Admin','manager'=>'Manager','staff'=>'Staff'];

// Pull a bit of employee context (position, branch) if linked
$myEmployee = $conn->query("SELECT position, branch, pay_type, rate FROM employees WHERE admin_id = $adminId LIMIT 1")->fetch_assoc();

// Today's own attendance snapshot for the little status line
$today = date('Y-m-d');
$todayAtt = $conn->query("SELECT clock_in, clock_out FROM attendance WHERE admin_id = $adminId AND type = 'admin' AND date = '$today' LIMIT 1")->fetch_assoc();

$month = (int)date('n');
$year  = (int)date('Y');

$totalSales = $conn->query("
    SELECT COALESCE(SUM(oi.quantity * oi.price), 0) AS total
    FROM orders o JOIN order_items oi ON o.id = oi.order_id
    WHERE MONTH(o.created_at) = $month AND YEAR(o.created_at) = $year AND o.status != 'cancelled'
")->fetch_assoc()['total'] ?? 0;

$totalOrders = $conn->query("
    SELECT COUNT(*) AS cnt FROM orders
    WHERE MONTH(created_at) = $month AND YEAR(created_at) = $year AND status != 'cancelled'
")->fetch_assoc()['cnt'] ?? 0;

$totalCustomers = $conn->query("SELECT COUNT(DISTINCT user_id) AS cnt FROM orders WHERE status != 'cancelled'")->fetch_assoc()['cnt'] ?? 0;
$avgOrder = $totalOrders > 0 ? $totalSales / $totalOrders : 0;

$lowStockCount = $conn->query("SELECT COUNT(*) AS c FROM products WHERE stock <= reorder_level")->fetch_assoc()['c'] ?? 0;

$recentOrders = $conn->query("
    SELECT o.id, o.created_at, o.status, COALESCE(o.total, 0) AS total,
           CASE
               WHEN u.username IS NOT NULL THEN u.username
               WHEN o.guest_name IS NOT NULL AND o.guest_name != '' THEN CONCAT(o.guest_name, ' (Guest)')
               ELSE 'Guest'
           END AS customer,
           COALESCE(o.branch, 'laguna') AS branch
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

$initials = strtoupper(substr($adminName, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — AyosCoffeeNegosyo</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
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

#mainContent{margin-left:var(--sidebar-w);flex:1;min-width:0;position:relative;z-index:1;display:flex;flex-direction:column}
.topbar{position:sticky;top:0;z-index:100;background:rgba(11,11,9,0.9);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:0 32px;height:64px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.topbar-sub{font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#e05a5a}
.topbar-title{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream)}
.topbar-right{display:flex;align-items:center;gap:16px}
.topbar-period{font-size:11px;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);padding:5px 12px;border:1px solid var(--border);border-radius:20px}

.dash-body{padding:28px 32px 60px;display:flex;flex-direction:column;gap:24px;max-width:1300px}

/* PROFILE CARD */
.profile-card{background:var(--card);border:1px solid var(--border);border-radius:8px;padding:26px 28px;display:flex;align-items:center;gap:22px;flex-wrap:wrap;position:relative;overflow:hidden}
.profile-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--gold),transparent)}
.avatar-wrap{position:relative;width:88px;height:88px;flex-shrink:0}
.avatar-img{width:88px;height:88px;border-radius:50%;object-fit:cover;border:2px solid var(--gold-dim);background:var(--surface)}
.avatar-placeholder{width:88px;height:88px;border-radius:50%;border:2px solid var(--gold-dim);background:linear-gradient(135deg,#2a1f08,#1a1a16);display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:700;color:var(--gold)}
.avatar-edit-btn{position:absolute;bottom:-2px;right:-2px;width:30px;height:30px;border-radius:50%;background:var(--gold);border:2px solid var(--card);display:flex;align-items:center;justify-content:center;cursor:pointer;color:#1a1a16;transition:background 0.2s}
.avatar-edit-btn:hover{background:#e0c377}
.avatar-edit-btn input{position:absolute;inset:0;opacity:0;cursor:pointer}
.profile-info{flex:1;min-width:200px}
.profile-name{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--cream);display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.role-pill{display:inline-flex;padding:4px 11px;border-radius:3px;font-size:10.5px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase}
.role-pill.owner{background:rgba(201,168,76,0.14);color:var(--gold)}
.role-pill.super_admin{background:rgba(192,57,43,0.12);color:#e05a5a}
.role-pill.admin{background:rgba(59,130,246,0.1);color:#60a5fa}
.role-pill.manager{background:rgba(74,122,58,0.12);color:var(--green-lt)}
.role-pill.staff{background:rgba(107,107,88,0.12);color:var(--muted)}
.profile-meta{font-size:12.5px;color:var(--muted);margin-top:6px;display:flex;gap:16px;flex-wrap:wrap}
.profile-meta span strong{color:var(--text);font-weight:500}
.profile-shift-status{margin-top:10px}
.shift-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:11.5px;font-weight:500}
.shift-badge.in{background:rgba(74,122,58,0.12);color:var(--green-lt)}
.shift-badge.out{background:rgba(201,168,76,0.08);color:var(--gold)}
.shift-badge.none{background:var(--red-pale);color:#e05a5a}
.shift-badge a{color:inherit;text-decoration:underline;margin-left:6px}
.remove-photo-link{font-size:11px;color:var(--muted);text-decoration:underline;cursor:pointer;margin-top:4px;display:inline-block}
.remove-photo-link:hover{color:#e05a5a}

/* STAT CARDS */
.stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:20px 22px 18px;display:flex;flex-direction:column;gap:6px;position:relative;overflow:hidden}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--gold-dim),transparent)}
.stat-icon{width:38px;height:38px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:var(--gold-pale);margin-bottom:4px;flex-shrink:0}
.stat-icon.green-bg{background:rgba(74,122,58,0.12)}
.stat-icon.blue-bg{background:rgba(59,130,246,0.1)}
.stat-icon.red-bg{background:var(--red-pale)}
.stat-label{font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted)}
.stat-value{font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:700;color:var(--cream);line-height:1}
.stat-value.gold{color:var(--gold)}
.stat-sub{font-size:11px;color:var(--muted);margin-top:2px}
.stat-card.link{text-decoration:none;transition:border-color 0.2s}
.stat-card.link:hover{border-color:var(--gold-dim)}

.section-hd{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.section-hd-line{flex:1;height:1px;background:linear-gradient(90deg,var(--border),transparent)}
.section-title{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--cream)}
.section-link{font-size:12px;color:var(--gold);text-decoration:none;letter-spacing:0.06em}
.section-link:hover{text-decoration:underline}

.table-card{background:var(--card);border:1px solid var(--border);border-radius:6px;overflow:hidden}
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
.empty-row td{text-align:center;color:var(--muted);padding:32px 16px;font-size:13px}

#toast{position:fixed;bottom:28px;right:28px;z-index:999;background:var(--card);border:1px solid var(--green);border-radius:5px;padding:12px 18px;display:flex;align-items:center;gap:10px;font-size:13.5px;color:var(--cream);box-shadow:0 8px 32px rgba(0,0,0,0.5);transform:translateY(16px);opacity:0;transition:all 0.3s ease;pointer-events:none}
#toast.show{transform:translateY(0);opacity:1}
#toast.error{border-color:var(--red)}

@media(max-width:1100px){.stat-row{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){.dash-body{padding:20px 16px 48px}.stat-row{grid-template-columns:1fr 1fr}.profile-card{padding:22px}}
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
        <a href="admin-dashboard.php" class="nav-item active    ">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></span>
            Dashboard
        </a>
        <a href="admin-analytics.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
            Analytics
        </a>
        <a href="admin-products.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/></svg></span>
            Products
        </a>
        <a href="admin-inventory.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.5 7.28a1 1 0 0 0-.5-.86L12.5 2.4a1 1 0 0 0-1 0L4 6.42a1 1 0 0 0-.5.86v9.44a1 1 0 0 0 .5.86l7.5 4.02a1 1 0 0 0 1 0l7.5-4.02a1 1 0 0 0 .5-.86z"/><polyline points="3.5 7.5 12 12.5 20.5 7.5"/><line x1="12" y1="22" x2="12" y2="12.5"/></svg></span>
            Inventory
        </a>
        <a href="admin-users.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span>
            User Management
        </a>
        <a href="admin-attendance.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
            Attendance
        </a>
        <?php if (($_SESSION['admin']['role'] ?? '') === 'owner'): ?>
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
        <div>
            <div class="topbar-sub">Admin Overview</div>
            <div class="topbar-title">Dashboard</div>
        </div>
        <div class="topbar-right">
            <div class="topbar-period"><?= date('F Y') ?></div>
        </div>
    </div>

    <div class="dash-body">

        <!-- PROFILE CARD -->
        <div class="profile-card">
            <div class="avatar-wrap">
                <?php if ($adminPhoto): ?>
                    <img src="<?= htmlspecialchars($adminPhoto) ?>" class="avatar-img" id="avatarImg" alt="Profile photo">
                <?php else: ?>
                    <div class="avatar-placeholder" id="avatarPlaceholder"><?= htmlspecialchars($initials) ?></div>
                    <img src="" class="avatar-img" id="avatarImg" alt="Profile photo" style="display:none">
                <?php endif; ?>
                <label class="avatar-edit-btn" title="Change profile photo">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    <input type="file" id="photoInput" accept="image/jpeg,image/png,image/webp" onchange="uploadPhoto(this)">
                </label>
            </div>
            <div class="profile-info">
                <div class="profile-name">
                    <?= htmlspecialchars($adminName) ?>
                    <span class="role-pill <?= $adminRole ?>"><?= $roleLabels[$adminRole] ?? $adminRole ?></span>
                </div>
                <div class="profile-meta">
                    <?php if (!empty($myEmployee['position'])): ?><span><strong><?= htmlspecialchars($myEmployee['position']) ?></strong></span><?php endif; ?>
                    <?php if (!empty($myEmployee['branch'])): ?><span><?= ucfirst($myEmployee['branch']) ?> Branch</span><?php endif; ?>
                    <span>Logged in as <strong><?= htmlspecialchars($adminName) ?></strong></span>
                </div>
                <div class="profile-shift-status">
                    <?php if (!$todayAtt): ?>
                        <span class="shift-badge none">● Not clocked in today <a href="admin-attendance.php">Clock in →</a></span>
                    <?php elseif ($todayAtt['clock_in'] && !$todayAtt['clock_out']): ?>
                        <span class="shift-badge in">● Clocked in since <?= date('h:i A', strtotime($todayAtt['clock_in'])) ?> <a href="admin-attendance.php">View →</a></span>
                    <?php else: ?>
                        <span class="shift-badge out">✓ Shift completed today <a href="admin-attendance.php">View →</a></span>
                    <?php endif; ?>
                </div>
                <?php if ($adminPhoto): ?>
                <span class="remove-photo-link" onclick="removePhoto()">Remove photo</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- QUICK STATS -->
        <div class="stat-row">
            <div class="stat-card">
                <div class="stat-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.8"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                <div class="stat-label">Total Sales (Month)</div>
                <div class="stat-value gold">₱<?= number_format($totalSales, 2) ?></div>
                <div class="stat-sub">All branches — <?= date('F') ?></div>
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
            <a href="admin-analytics.php" class="stat-card link">
                <div class="stat-icon red-bg"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e05a5a" stroke-width="1.8"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
                <div class="stat-label">Low Stock Alerts</div>
                <div class="stat-value" style="color:<?= $lowStockCount > 0 ? '#e05a5a' : 'var(--cream)' ?>"><?= $lowStockCount ?></div>
                <div class="stat-sub">View full Analytics →</div>
            </a>
        </div>

        <!-- RECENT ORDERS -->
        <div>
            <div class="section-hd">
                <div class="section-title">Recent Orders</div>
                <div class="section-hd-line"></div>
                <a href="admin-analytics.php" class="section-link">Full analytics →</a>
            </div>
            <div class="table-card">
                <table>
                    <thead><tr><th>Order #</th><th>Customer</th><th>Branch</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr class="empty-row"><td colspan="5">No orders yet.</td></tr>
                    <?php else: foreach ($recentOrders as $o): ?>
                        <tr>
                            <td style="color:var(--muted)">#<?= $o['id'] ?></td>
                            <td style="font-weight:500"><?= htmlspecialchars($o['customer'] ?? 'Guest') ?></td>
                            <td><span class="branch-pill <?= $o['branch'] ?? 'laguna' ?>"><?= ucfirst($o['branch'] ?? 'laguna') ?></span></td>
                            <td style="color:var(--gold);font-family:'Cormorant Garamond',serif;font-size:15px">₱<?= number_format($o['total'], 2) ?></td>
                            <td><span class="order-status <?= htmlspecialchars($o['status']) ?>"><?= ucfirst($o['status']) ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<div id="toast"><span id="toastMsg"></span></div>

<script>
let toastTimer;
function showToast(msg, isErr) {
    const t = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    t.className = isErr ? 'error show' : 'show';
    clearTimeout(toastTimer); toastTimer = setTimeout(() => t.className = '', 2800);
}

function uploadPhoto(input) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) { showToast('Image must be under 5MB.', true); input.value = ''; return; }

    // instant local preview
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById('avatarImg');
        img.src = e.target.result;
        img.style.display = 'block';
        const ph = document.getElementById('avatarPlaceholder');
        if (ph) ph.style.display = 'none';
    };
    reader.readAsDataURL(file);

    const fd = new FormData();
    fd.append('action', 'upload_photo');
    fd.append('photo', file);
    fetch('admin-profile_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) { showToast('Profile photo updated!'); setTimeout(() => location.reload(), 900); }
            else { showToast(data.message || 'Upload failed.', true); }
        })
        .catch(() => showToast('Network error.', true));
}

function removePhoto() {
    if (!confirm('Remove your profile photo?')) return;
    const fd = new FormData();
    fd.append('action', 'remove_photo');
    fetch('admin-profile_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) { showToast('Photo removed.'); setTimeout(() => location.reload(), 700); }
            else showToast(data.message || 'Failed.', true);
        })
        .catch(() => showToast('Network error.', true));
}
</script>
</body>
</html>