<?php
include 'admin-config.php';
requireAdmin();

$myAdminId = (int)($_SESSION['admin']['id'] ?? 0);
$myRole    = $_SESSION['admin']['role'] ?? 'staff';
$isOwner   = $myRole === 'owner';

// Owner ang nagbabayad ng sweldo, kaya walang access dito.
if ($isOwner) { header('Location: admin-payroll.php'); exit(); }

$roleLabels = ['owner'=>'Owner','super_admin'=>'Super Admin','admin'=>'Admin','manager'=>'Manager','staff'=>'Staff'];
$payTypeLabels = ['daily'=>'Daily rate','hourly'=>'Hourly rate','monthly'=>'Monthly salary'];

// ── Schema (safe no-ops kapag meron na) ─────────────────────────────────
$conn->query("ALTER TABLE payroll ADD COLUMN IF NOT EXISTS overtime_hours DECIMAL(6,2) DEFAULT 0");
$conn->query("ALTER TABLE payroll ADD COLUMN IF NOT EXISTS overtime_pay DECIMAL(10,2) DEFAULT 0");
$conn->query("ALTER TABLE payroll ADD COLUMN IF NOT EXISTS days_on_leave INT DEFAULT 0");
$conn->query("ALTER TABLE payroll ADD COLUMN IF NOT EXISTS received_at TIMESTAMP NULL DEFAULT NULL");

// ── AJAX: Confirm na natanggap ang sweldo ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_received') {
    header('Content-Type: application/json');
    $pid = (int)($_POST['id'] ?? 0);
    $stmt = $conn->prepare("
        UPDATE payroll p JOIN employees e ON e.id = p.employee_id
        SET p.received_at = NOW()
        WHERE p.id = ? AND e.admin_id = ? AND p.status = 'paid' AND p.received_at IS NULL
    ");
    $stmt->bind_param('ii', $pid, $myAdminId);
    $stmt->execute();
    if ($stmt->affected_rows > 0) echo json_encode(['success'=>true,'message'=>'Salary receipt confirmed. Salamat!']);
    else echo json_encode(['success'=>false,'message'=>'This payment cannot be confirmed (not paid yet, already confirmed, or not yours).']);
    exit();
}

// ── Data ────────────────────────────────────────────────────────────────
$emp = $conn->query("SELECT * FROM employees WHERE admin_id = $myAdminId LIMIT 1")->fetch_assoc();
$slips = [];
if ($emp) {
    $slips = $conn->query("
        SELECT * FROM payroll WHERE employee_id = " . (int)$emp['id'] . " ORDER BY period_end DESC, id DESC LIMIT 60
    ")->fetch_all(MYSQLI_ASSOC);
}

$totalReceived = 0; $toConfirm = 0; $inProcess = 0; $lastPaid = null;
foreach ($slips as $s) {
    if ($s['status'] === 'paid') {
        $totalReceived += (float)$s['net_pay'];
        if (!$lastPaid) $lastPaid = $s;
        if (!$s['received_at']) $toConfirm++;
    } else { $inProcess++; }
}

// Sidebar state
$currentPage = basename($_SERVER['PHP_SELF']);
$invOpen = in_array($currentPage, ['admin-inventory.php', 'admin-products.php']);
$attOpen = in_array($currentPage, ['admin-attendance.php', 'admin-leave-overtime.php']);

// JSON para sa receipt modal
$jsSlips = [];
foreach ($slips as $s) {
    $jsSlips[$s['id']] = [
        'id' => (int)$s['id'],
        'status' => $s['status'],
        'period' => date('M j', strtotime($s['period_start'])) . ' – ' . date('M j, Y', strtotime($s['period_end'])),
        'generated' => date('M j, Y g:i A', strtotime($s['created_at'])),
        'received_at' => $s['received_at'] ? date('M j, Y g:i A', strtotime($s['received_at'])) : null,
        'days_present' => (int)$s['days_present'],
        'days_late' => (int)$s['days_late'],
        'days_on_leave' => (int)($s['days_on_leave'] ?? 0),
        'hours' => (float)$s['total_hours'],
        'ot_hours' => (float)($s['overtime_hours'] ?? 0),
        'customers' => (int)$s['total_customers_served'],
        'base' => (float)$s['base_pay'],
        'ot_pay' => (float)($s['overtime_pay'] ?? 0),
        'bonus' => (float)$s['bonuses'],
        'deduct' => (float)$s['deductions'],
        'net' => (float)$s['net_pay'],
    ];
}
$jsEmp = [
    'name' => $emp['full_name'] ?? ($_SESSION['admin']['username'] ?? ''),
    'role' => $roleLabels[$emp['role'] ?? $myRole] ?? $myRole,
    'position' => $emp['position'] ?? '',
    'branch' => ucfirst($emp['branch'] ?? ''),
    'pay_type' => $payTypeLabels[$emp['pay_type'] ?? 'daily'] ?? '',
    'rate' => (float)($emp['rate'] ?? 0),
];
$flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receive Payment Salary — SIPPERÉ Café</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;--gold:#c9a84c;--gold-dim:#8a6f2e;--gold-pale:rgba(201,168,76,0.08);--green:#4a7a3a;--green-lt:#6aaa52;--cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8;--red:#c0392b;--red-pale:rgba(192,57,43,0.1);--amber:#d4820a;--amber-pale:rgba(212,130,10,0.1);--blue:#60a5fa;--sidebar-w:240px}
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
.nav-badge{margin-left:auto;background:var(--red);color:#fff;border-radius:10px;padding:1px 7px;font-size:10px}
.nav-toggle{width:100%;background:none;border:none;font-family:'Jost',sans-serif;cursor:pointer;text-align:left}
.nav-chevron{margin-left:auto;display:flex;transition:transform 0.3s ease}
.nav-group.open > .nav-toggle .nav-chevron{transform:rotate(180deg)}
.nav-group.open > .nav-toggle{color:var(--text)}
.nav-group.has-active > .nav-toggle{color:#e05a5a}
.nav-sub{display:grid;grid-template-rows:0fr;transition:grid-template-rows 0.35s ease}
.nav-group.open > .nav-sub{grid-template-rows:1fr}
.nav-sub-inner{overflow:hidden;min-height:0;display:flex;flex-direction:column;gap:2px;margin-left:22px;border-left:1px solid var(--border);visibility:hidden;opacity:0;transition:opacity 0.3s ease, visibility 0.35s}
.nav-group.open > .nav-sub > .nav-sub-inner{visibility:visible;opacity:1}
.nav-sub .nav-item{padding:9px 12px 9px 18px;font-size:13px}
.nav-sub .nav-icon{width:18px;height:18px}
.nav-sub .nav-icon svg{width:15px;height:15px}
.sb-footer{padding:10px;border-top:1px solid var(--border)}
.nav-item.logout{color:#e05a5a}
.nav-item.logout:hover{background:rgba(224,90,90,0.08)}

/* MAIN */
#mainContent{margin-left:var(--sidebar-w);flex:1;min-width:0;position:relative;z-index:1;display:flex;flex-direction:column}
.topbar{position:sticky;top:0;z-index:100;background:rgba(11,11,9,0.9);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:0 32px;height:64px;display:flex;align-items:center;justify-content:space-between}
.topbar-sub{font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#e05a5a}
.topbar-title{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream)}
.topbar-user{font-size:12.5px;color:var(--muted)}
.topbar-user span{color:var(--gold);font-weight:500}
.role-pill{display:inline-flex;padding:3px 9px;border-radius:3px;font-size:10px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;margin-left:6px}
.role-pill.super_admin{background:rgba(192,57,43,0.12);color:#e05a5a}
.role-pill.admin{background:rgba(59,130,246,0.1);color:#60a5fa}
.role-pill.manager{background:rgba(74,122,58,0.12);color:var(--green-lt)}
.role-pill.staff{background:rgba(107,107,88,0.12);color:var(--muted)}
.page-body{padding:28px 32px 60px;display:flex;flex-direction:column;gap:26px;max-width:1100px}

/* SUMMARY */
.summary{display:grid;grid-template-columns:1.4fr 1fr 1fr;gap:16px}
.sum-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:22px 24px;position:relative;overflow:hidden}
.sum-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--gold-dim),transparent)}
.sum-label{font-size:12px;color:var(--muted);letter-spacing:0.04em}
.sum-value{font-family:'Cormorant Garamond',serif;font-size:34px;font-weight:700;color:var(--cream);margin-top:6px;line-height:1.1}
.sum-value.gold{color:var(--gold);font-size:44px}
.sum-value.amber{color:var(--amber)}
.sum-note{font-size:12px;color:var(--muted);margin-top:6px}

.section-hd{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.section-hd-line{flex:1;height:1px;background:linear-gradient(90deg,var(--border),transparent)}
.section-title{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;color:var(--cream)}

.notice{background:var(--amber-pale);border:1px solid rgba(212,130,10,0.35);color:var(--amber);border-radius:6px;padding:13px 18px;font-size:13.5px}

/* RECEIPT LIST */
.slip-list{display:flex;flex-direction:column;gap:12px}
.slip-row{background:var(--card);border:1px solid var(--border);border-left:3px solid var(--border);border-radius:6px;padding:18px 22px;display:grid;grid-template-columns:1.3fr 1fr 1fr auto;align-items:center;gap:18px}
.slip-row.paid{border-left-color:var(--green-lt)}
.slip-row.approved{border-left-color:var(--blue)}
.slip-row.pending{border-left-color:var(--amber)}
.slip-period{font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:600;color:var(--cream)}
.slip-ref{font-size:12px;color:var(--muted);margin-top:3px}
.slip-amt-label{font-size:11px;color:var(--muted)}
.slip-amt{font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700;color:var(--gold)}
.state{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:3px;font-size:11.5px;font-weight:500}
.state.pending{background:var(--amber-pale);color:var(--amber)}
.state.approved{background:rgba(59,130,246,0.1);color:var(--blue)}
.state.paid{background:rgba(74,122,58,0.12);color:var(--green-lt)}
.state.received{background:rgba(201,168,76,0.12);color:var(--gold)}
.state-note{font-size:11.5px;color:var(--muted);margin-top:5px}
.row-actions{display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap}

.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:4px;font-family:'Jost',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:0.03em;cursor:pointer;border:none;transition:all 0.2s;text-decoration:none}
.btn:focus-visible{outline:2px solid var(--gold);outline-offset:2px}
.btn-green{background:var(--green);color:#fff}.btn-green:hover:not(:disabled){background:var(--green-lt)}.btn-green:disabled{opacity:0.5;cursor:not-allowed}
.btn-gold{background:var(--gold-pale);border:1px solid var(--gold-dim);color:var(--gold)}.btn-gold:hover{background:rgba(201,168,76,0.18)}
.btn-muted{background:transparent;border:1px solid var(--border);color:var(--muted)}.btn-muted:hover{border-color:var(--gold-dim);color:var(--gold)}

.empty{background:var(--card);border:1px dashed var(--border);border-radius:6px;padding:48px 24px;text-align:center;color:var(--muted);font-size:14px;line-height:1.7}
.empty strong{display:block;font-family:'Cormorant Garamond',serif;font-size:22px;color:var(--cream);margin-bottom:6px;font-weight:600}

/* RECEIPT MODAL */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.78);backdrop-filter:blur(6px);z-index:400;display:flex;align-items:flex-start;justify-content:center;padding:32px 16px;opacity:0;pointer-events:none;transition:opacity 0.25s;overflow-y:auto}
.modal-overlay.show{opacity:1;pointer-events:all}
.modal-wrap{width:100%;max-width:520px;margin:auto;transform:translateY(14px);transition:transform 0.3s}
.modal-overlay.show .modal-wrap{transform:translateY(0)}
.modal-bar{display:flex;justify-content:space-between;gap:8px;margin-bottom:12px}

.receipt{background:#f6f0df;color:#23200f;border-radius:4px;padding:34px 34px 30px;position:relative;box-shadow:0 24px 60px rgba(0,0,0,0.55)}
.receipt::before,.receipt::after{content:'';position:absolute;left:0;right:0;height:10px;background:radial-gradient(circle at 8px 0,transparent 6px,#f6f0df 6.5px) 0 0/16px 10px repeat-x}
.receipt::before{top:-9px;transform:rotate(180deg)}
.receipt::after{bottom:-9px}
.r-head{text-align:center;padding-bottom:18px;border-bottom:1px dashed #b9ad84}
.r-brand{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;letter-spacing:0.02em}
.r-brand span{color:#8a6f2e}
.r-title{font-size:13px;color:#6f6640;margin-top:4px}
.r-no{font-family:'Cormorant Garamond',serif;font-size:17px;font-weight:600;margin-top:10px;color:#8a6f2e}
.r-block{padding:16px 0;border-bottom:1px dashed #b9ad84}
.r-line{display:flex;justify-content:space-between;gap:16px;font-size:13.5px;padding:4px 0}
.r-line .k{color:#6f6640}
.r-line .v{font-weight:500;text-align:right}
.r-h{font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:700;margin-bottom:6px}
.r-line.neg .v{color:#a63a2b}
.r-line.pos .v{color:#3d6a2e}
.r-total{display:flex;justify-content:space-between;align-items:baseline;padding:18px 0 6px}
.r-total .k{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:700}
.r-total .v{font-family:'Cormorant Garamond',serif;font-size:34px;font-weight:700;color:#23200f}
.r-status{margin-top:12px;text-align:center;font-size:12.5px;color:#6f6640;line-height:1.6}
.stamp{display:inline-block;border:2px solid;border-radius:4px;padding:3px 14px;font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:700;letter-spacing:0.1em;transform:rotate(-4deg);margin-bottom:6px}
.stamp.paid{color:#3d6a2e;border-color:#3d6a2e}
.stamp.received{color:#8a6f2e;border-color:#8a6f2e}
.stamp.wait{color:#a5720c;border-color:#a5720c}
.r-foot{margin-top:16px;text-align:center;font-size:11px;color:#8a8160;line-height:1.6}

#toast{position:fixed;bottom:28px;right:28px;z-index:999;background:var(--card);border:1px solid var(--green);border-radius:5px;padding:12px 18px;font-size:13.5px;color:var(--cream);transform:translateY(16px);opacity:0;transition:all 0.3s;pointer-events:none;max-width:340px}
#toast.show{transform:translateY(0);opacity:1}
#toast.error{border-color:var(--red)}

@media(max-width:1000px){.summary{grid-template-columns:1fr}.slip-row{grid-template-columns:1fr 1fr}.row-actions{grid-column:1/-1;justify-content:flex-start}}
@media(max-width:768px){.page-body{padding:20px 16px 48px}}
@media(prefers-reduced-motion:reduce){*{transition:none!important}}

@media print{
  body{background:#fff;display:block}
  body::before,#sidebar,#mainContent,.modal-bar,#toast{display:none!important}
  .modal-overlay{position:static;background:none;opacity:1;padding:0;display:block;backdrop-filter:none}
  .modal-wrap{max-width:none;transform:none}
  .receipt{box-shadow:none;border:1px solid #ccc;max-width:520px;margin:0 auto}
  .receipt::before,.receipt::after{display:none}
}
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
            <div class="nav-sub"><div class="nav-sub-inner">
                <a href="admin-inventory.php" class="nav-item">
                    <span class="nav-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg></span>
                    Overview
                </a>
                <a href="admin-products.php" class="nav-item">
                    <span class="nav-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></span>
                    Products
                </a>
            </div></div>
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
                <span class="nav-chevron"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></span>
            </button>
            <div class="nav-sub"><div class="nav-sub-inner">
                <a href="admin-attendance.php" class="nav-item">
                    <span class="nav-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><polyline points="9 14 11 16 15 12"/></svg></span>
                    Overview
                </a>
                <a href="admin-leave-overtime.php" class="nav-item">
                    <span class="nav-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                    Leave & Overtime
                </a>41  
            </div></div>
        </div>

        <!-- RECEIVE PAYMENT SALARY (hindi para sa Owner) -->
        <a href="receipt-payment-salary.php" class="nav-item active">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 2v20l3-2 2.5 2L12 20l2.5 2L17 20l3 2V2l-3 2-2.5-2L12 4 9.5 2 7 4z"/><line x1="8" y1="9" x2="16" y2="9"/><line x1="8" y1="13" x2="14" y2="13"/></svg></span>
            Receive Payment Salary
            <?php if ($toConfirm > 0): ?><span class="nav-badge"><?= $toConfirm ?></span><?php endif; ?>
        </a>

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
        <div><div class="topbar-sub">My Salary</div><div class="topbar-title">Receive Payment Salary</div></div>
        <div class="topbar-user">Logged in as <span><?= htmlspecialchars($_SESSION['admin']['username']) ?></span><span class="role-pill <?= htmlspecialchars($myRole) ?>"><?= $roleLabels[$myRole] ?? $myRole ?></span></div>
    </div>

    <div class="page-body">

        <?php if (!$emp): ?>
        <div class="notice">Your account isn't linked to an employee record yet. Ask the Owner to add you in Payroll & Employees so your salary receipts can appear here.</div>
        <?php endif; ?>

        <!-- SUMMARY -->
        <div class="summary">
            <div class="sum-card">
                <div class="sum-label">Total salary received</div>
                <div class="sum-value gold">₱<?= number_format($totalReceived, 2) ?></div>
                <div class="sum-note"><?= $lastPaid ? 'Last payment: ₱' . number_format((float)$lastPaid['net_pay'], 2) . ' for ' . date('M j', strtotime($lastPaid['period_start'])) . ' – ' . date('M j, Y', strtotime($lastPaid['period_end'])) : 'No payment yet.' ?></div>
            </div>
            <div class="sum-card">
                <div class="sum-label">Waiting for your confirmation</div>
                <div class="sum-value <?= $toConfirm ? 'amber' : '' ?>"><?= $toConfirm ?></div>
                <div class="sum-note">Paid by the Owner, not yet confirmed</div>
            </div>
            <div class="sum-card">
                <div class="sum-label">Still being processed</div>
                <div class="sum-value"><?= $inProcess ?></div>
                <div class="sum-note">Pending or approved payroll</div>
            </div>
        </div>

        <!-- RECEIPTS -->
        <div>
            <div class="section-hd"><div class="section-title">My salary receipts</div><div class="section-hd-line"></div></div>

            <?php if (empty($slips)): ?>
            <div class="empty">
                <strong>No receipts yet</strong>
                When the Owner generates your payroll, your receipt will show up here.
            </div>
            <?php else: ?>
            <div class="slip-list">
            <?php foreach ($slips as $s):
                $isReceived = $s['status'] === 'paid' && $s['received_at'];
                $stateKey = $isReceived ? 'received' : $s['status'];
                $stateLabel = ['pending'=>'Processing','approved'=>'Approved','paid'=>'Paid','received'=>'Received'][$stateKey];
                $stateNote = $isReceived ? 'Confirmed ' . date('M j, Y', strtotime($s['received_at']))
                    : ($s['status'] === 'paid' ? 'Please confirm you got it' : ($s['status'] === 'approved' ? 'Waiting for the Owner to pay' : 'Waiting for Owner approval'));
            ?>
                <div class="slip-row <?= $s['status'] ?>" id="row-<?= (int)$s['id'] ?>">
                    <div>
                        <div class="slip-period"><?= date('M j', strtotime($s['period_start'])) ?> – <?= date('M j, Y', strtotime($s['period_end'])) ?></div>
                        <div class="slip-ref">Receipt RCPT-<?= str_pad($s['id'], 5, '0', STR_PAD_LEFT) ?></div>
                    </div>
                    <div>
                        <div class="slip-amt-label">Net pay</div>
                        <div class="slip-amt">₱<?= number_format((float)$s['net_pay'], 2) ?></div>
                    </div>
                    <div>
                        <span class="state <?= $stateKey ?>"><?= $stateLabel ?></span>
                        <div class="state-note"><?= $stateNote ?></div>
                    </div>
                    <div class="row-actions">
                        <button class="btn btn-gold" onclick="openReceipt(<?= (int)$s['id'] ?>)">View receipt</button>
                        <?php if ($s['status'] === 'paid' && !$s['received_at']): ?>
                        <button class="btn btn-green" onclick="confirmReceived(<?= (int)$s['id'] ?>, this)">Confirm received</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- RECEIPT MODAL -->
<div class="modal-overlay" id="receiptModal" onclick="if(event.target===this)closeReceipt()">
    <div class="modal-wrap">
        <div class="modal-bar">
            <button class="btn btn-muted" onclick="closeReceipt()">Close</button>
            <div style="display:flex;gap:8px">
                <button class="btn btn-green" id="modalConfirmBtn" style="display:none" onclick="confirmReceived(currentId, this)">Confirm received</button>
                <button class="btn btn-gold" onclick="window.print()">Print / Save PDF</button>
            </div>
        </div>
        <div class="receipt" id="receiptBody"></div>
    </div>
</div>

<div id="toast"></div>

<script>
const SLIPS = <?= json_encode($jsSlips, $flags) ?>;
const EMP   = <?= json_encode($jsEmp, $flags) ?>;
let currentId = null;

document.querySelectorAll('.nav-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const group = btn.closest('.nav-group');
        const isOpen = group.classList.toggle('open');
        btn.setAttribute('aria-expanded', isOpen);
    });
});

    const peso = n => '₱' + Number(n).toLocaleString('en-PH', {
        
        minimumFractionDigits:2, maximumFractionDigits:2

    }

);

const esc  = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

const line = (k, v, cls='') => `<div class="r-line ${cls}"><span class="k">${k}</span><span class="v">${v}</span></div>`;

function openReceipt(id) {

    const s = SLIPS[id]; if (!s) return;

        currentId = id;

    const received = s.status === 'paid' && s.received_at;

        let stamp = '', note = '';

    if (received)                { 
        
        stamp = '<span class="stamp received">RECEIVED</span>'; note = 'You confirmed this payment on ' + esc(s.received_at) + '.'; 
    
    }

    else if (s.status === 'paid') {
        
        stamp = '<span class="stamp paid">PAID</span>'; 
        note = 'Paid by the Owner. Please confirm once you have received it.'; 
    
    }

    else if (s.status === 'approved') {
        
        stamp = '<span class="stamp wait">APPROVED</span>'; note = 'Approved. Waiting for the Owner to release payment.'; 
    
    }

    else {
        
    stamp = '<span class="stamp wait">PROCESSING</span>'; note = 'Waiting for the Owner to approve this payroll.'; 

    }

    const who = EMP.role + (EMP.position ? ' — ' + EMP.position : '');

    let html = `

      <div class="r-head">

            <div class="r-brand">SIPPERÉ <span>Café</span></div>
            <div class="r-title">Salary payment receipt</div>
            <div class="r-no">RCPT-${String(s.id).padStart(5,'0')}</div>
      </div>


      <div class="r-block">
      
        ${line('Employee', esc(EMP.name))}
        ${line('Role', esc(who))}
        ${EMP.branch ? line('Branch', esc(EMP.branch)) : ''}
        ${line('Pay period', esc(s.period))}
        ${line('Pay type', esc(EMP.pay_type) + ' · ' + peso(EMP.rate))}
      </div>

      <div class="r-block">
        <div class="r-h">Work summary</div>
        ${line('Days present', s.days_present)}
        ${line('Days late', s.days_late, s.days_late > 0 ? 'neg' : '')}
        ${line('Days on approved leave', s.days_on_leave)}
        ${line('Hours worked', s.hours + ' hrs')}
        ${s.ot_hours > 0 ? line('Approved overtime', s.ot_hours + ' hrs') : ''}
        ${line('Customers served', s.customers)}
      </div>

      <div class="r-block">

        <div class="r-h">Pay breakdown</div>
        ${line('Base pay', peso(s.base))}
        ${s.ot_pay > 0 ? line('Overtime pay', '+ ' + peso(s.ot_pay), 'pos') : ''}
        ${line('Bonuses', '+ ' + peso(s.bonus), s.bonus > 0 ? 'pos' : '')}
        ${line('Deductions', '− ' + peso(s.deduct), s.deduct > 0 ? 'neg' : '')}
      </div>
      <div class="r-total"><span class="k">Net pay</span><span class="v">${peso(s.net)}</span></div>
      <div class="r-status">${stamp}<br>${note}</div>
      <div class="r-foot">Issued ${esc(s.generated)} by the Owner.<br>Computed from your clock-in/out records, approved leave and overtime.</div>`;
    document.getElementById('receiptBody').innerHTML = html;

    const cb = document.getElementById('modalConfirmBtn');
    cb.style.display = (s.status === 'paid' && !s.received_at) ? '' : 'none';
    cb.disabled = false;
    document.getElementById('receiptModal').classList.add('show');
}
function closeReceipt() { document.getElementById('receiptModal').classList.remove('show'); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeReceipt(); });

function confirmReceived(id, btn) {
    if (!confirm('Confirm that you received this salary payment?')) return;
    btn.disabled = true;
    const fd = new FormData();
    fd.append('action', 'confirm_received');
    fd.append('id', id);
    fetch('receipt-payment-salary.php', { method:'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) { showToast(d.message); setTimeout(() => location.reload(), 900); }
            else { showToast(d.message || 'Failed.', true); btn.disabled = false; }
        })
        .catch(() => { showToast('Network error.', true); btn.disabled = false; });
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