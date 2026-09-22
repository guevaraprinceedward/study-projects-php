<?php
include 'admin-config.php';
requireAdmin();

$myAdminId = (int)($_SESSION['admin']['id'] ?? 0);
$myRole    = $_SESSION['admin']['role'] ?? 'staff';
$isOwner   = $myRole === 'owner';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { die('<div style="font-family:sans-serif;padding:40px;text-align:center">Invalid payslip.</div>'); }

$slip = $conn->query("
    SELECT p.*, e.full_name, e.role AS emp_role, e.position, e.branch, e.pay_type, e.rate, e.admin_id
    FROM payroll p
    JOIN employees e ON e.id = p.employee_id
    WHERE p.id = $id
    LIMIT 1
")->fetch_assoc();

if (!$slip) { die('<div style="font-family:sans-serif;padding:40px;text-align:center">Payslip not found.</div>'); }

// Access control: owner sees all; anyone else only their own linked payslip
if (!$isOwner && (int)$slip['admin_id'] !== $myAdminId) {
    http_response_code(403);
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;color:#888">
            <h2 style="color:#c0392b">Access denied</h2>
            <p>This payslip does not belong to your account.</p>
            <p><a href="admin-attendance.php">&larr; Back</a></p>
         </div>');
}

$roleLabels = ['owner'=>'Owner','super_admin'=>'Super Admin','admin'=>'Admin','manager'=>'Manager','staff'=>'Staff'];
$payTypeLabels = ['daily'=>'Daily Rate','hourly'=>'Hourly Rate','monthly'=>'Monthly Salary'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payslip #<?= $slip['id'] ?> — AyosCoffeeNegosyo</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0b09;--card:#161710;--border:#2c2c24;--gold:#c9a84c;--green:#4a7a3a;--green-lt:#6aaa52;--cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8;--red:#c0392b;--amber:#d4820a}
body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:flex-start;justify-content:center;padding:40px 20px}
.slip{background:var(--card);border:1px solid var(--border);border-radius:8px;max-width:640px;width:100%;padding:40px 44px;position:relative}
.slip::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--gold),transparent)}
.slip-header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid var(--border);padding-bottom:20px;margin-bottom:24px}
.brand{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:var(--cream)}
.brand span{color:var(--gold)}
.brand-sub{font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted);margin-top:4px}
.slip-id{text-align:right}
.slip-id-num{font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--gold)}
.slip-id-status{margin-top:6px}
.status-pill{display:inline-flex;padding:4px 12px;border-radius:3px;font-size:10px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase}
.status-pill.pending{background:rgba(212,130,10,0.12);color:var(--amber)}
.status-pill.approved{background:rgba(59,130,246,0.1);color:#60a5fa}
.status-pill.paid{background:rgba(74,122,58,0.12);color:var(--green-lt)}
.info-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px}
.info-block{background:#0e0f0b;border:1px solid var(--border);border-radius:5px;padding:14px 16px}
.info-label{font-size:10px;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted);margin-bottom:4px}
.info-val{font-size:14px;color:var(--cream);font-weight:500}
.section-title{font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:600;color:var(--cream);margin:24px 0 12px}
table{width:100%;border-collapse:collapse;margin-bottom:8px}
td{padding:9px 0;font-size:13.5px;border-bottom:1px solid rgba(44,44,36,0.6)}
td.right{text-align:right;color:var(--cream)}
td.muted{color:var(--muted)}
.total-row td{border-bottom:none;border-top:2px solid var(--gold-dim,var(--border));padding-top:16px;font-size:17px}
.total-row td.right{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--gold)}
.neg{color:#e05a5a}
.pos{color:var(--green-lt)}
.footer-note{margin-top:28px;font-size:11px;color:var(--muted);line-height:1.6;text-align:center}
.print-bar{max-width:640px;width:100%;display:flex;justify-content:space-between;margin-bottom:12px}
.btn{padding:9px 18px;border-radius:4px;border:1px solid var(--border);background:transparent;color:var(--muted);font-family:'Jost',sans-serif;font-size:12.5px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn:hover{border-color:var(--gold);color:var(--gold)}
.btn.primary{background:var(--green);border-color:var(--green);color:#fff}
.btn.primary:hover{background:var(--green-lt);color:#fff}
@media print{
  body{background:#fff;color:#111;padding:0}
  .print-bar{display:none}
  .slip{border:1px solid #ccc;box-shadow:none;color:#111}
  .brand,.info-val,.section-title,.total-row td.right{color:#111}
  .info-block{background:#f7f7f7;border-color:#ddd}
  td{color:#222;border-color:#ddd}
}
</style>
</head>
<body>
<div style="display:flex;flex-direction:column;align-items:center;width:100%">
<div class="print-bar">
    <a href="admin-attendance.php" class="btn">← Back</a>
    <button class="btn primary" onclick="window.print()">🖨 Print / Save PDF</button>
</div>
<div class="slip">
    <div class="slip-header">
        <div>
            <div class="brand">Ayos<span>Coffee</span>Negosyo</div>
            <div class="brand-sub">Official Payslip</div>
        </div>
        <div class="slip-id">
            <div class="slip-id-num">#<?= str_pad($slip['id'], 5, '0', STR_PAD_LEFT) ?></div>
            <div class="slip-id-status"><span class="status-pill <?= $slip['status'] ?>"><?= ucfirst($slip['status']) ?></span></div>
        </div>
    </div>

    <div class="info-row">
        <div class="info-block">
            <div class="info-label">Employee</div>
            <div class="info-val"><?= htmlspecialchars($slip['full_name']) ?></div>
        </div>
        <div class="info-block">
            <div class="info-label">Role / Position</div>
            <div class="info-val"><?= $roleLabels[$slip['emp_role']] ?? $slip['emp_role'] ?><?= $slip['position'] ? ' — '.htmlspecialchars($slip['position']) : '' ?></div>
        </div>
        <div class="info-block">
            <div class="info-label">Pay Period</div>
            <div class="info-val"><?= date('M j', strtotime($slip['period_start'])) ?> – <?= date('M j, Y', strtotime($slip['period_end'])) ?></div>
        </div>
        <div class="info-block">
            <div class="info-label">Pay Type / Rate</div>
            <div class="info-val"><?= $payTypeLabels[$slip['pay_type']] ?? $slip['pay_type'] ?> — ₱<?= number_format((float)$slip['rate'], 2) ?></div>
        </div>
    </div>

    <div class="section-title">Attendance Summary (from Attendance Tracker)</div>
    <table>
        <tr><td class="muted">Days Present</td><td class="right"><?= (int)$slip['days_present'] ?></td></tr>
        <tr><td class="muted">Days Late</td><td class="right <?= $slip['days_late']>0?'neg':'' ?>"><?= (int)$slip['days_late'] ?></td></tr>
        <tr><td class="muted">Total Hours Worked</td><td class="right"><?= $slip['total_hours'] ?> hrs</td></tr>
        <tr><td class="muted">Customers Served</td><td class="right pos"><?= (int)$slip['total_customers_served'] ?></td></tr>
    </table>

    <div class="section-title">Pay Breakdown</div>
    <table>
        <tr><td class="muted">Base Pay</td><td class="right">₱<?= number_format((float)$slip['base_pay'], 2) ?></td></tr>
        <tr><td class="muted">Bonuses</td><td class="right pos">+ ₱<?= number_format((float)$slip['bonuses'], 2) ?></td></tr>
        <tr><td class="muted">Deductions</td><td class="right neg">− ₱<?= number_format((float)$slip['deductions'], 2) ?></td></tr>
        <tr class="total-row"><td>Net Pay</td><td class="right">₱<?= number_format((float)$slip['net_pay'], 2) ?></td></tr>
    </table>

    <div class="footer-note">
        Generated on <?= date('M j, Y g:i A', strtotime($slip['created_at'])) ?> by the Owner.<br>
        This payslip is computed automatically from clock-in/out records and customer-served counts logged on the Attendance page.
    </div>
</div>
</div>
</body>
</html>