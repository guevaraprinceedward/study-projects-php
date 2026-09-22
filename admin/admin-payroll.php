<?php
include 'admin-config.php';
requireAdmin();
requireRole(['owner']); // Only Owner can see payroll & assign roles

// Ensure schema pieces exist (safe no-ops if already migrated)
$conn->query("ALTER TABLE admins ADD COLUMN IF NOT EXISTS role VARCHAR(20) NOT NULL DEFAULT 'admin'");
$conn->query("
    CREATE TABLE IF NOT EXISTS employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT DEFAULT NULL,
        user_id INT DEFAULT NULL,
        full_name VARCHAR(150) NOT NULL,
        role ENUM('owner','super_admin','admin','manager','staff') NOT NULL DEFAULT 'staff',
        position VARCHAR(100) DEFAULT NULL,
        branch VARCHAR(20) DEFAULT 'laguna',
        pay_type ENUM('daily','hourly','monthly') NOT NULL DEFAULT 'daily',
        rate DECIMAL(10,2) NOT NULL DEFAULT 0,
        date_hired DATE DEFAULT NULL,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");
$conn->query("
    CREATE TABLE IF NOT EXISTS payroll (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        period_start DATE NOT NULL,
        period_end DATE NOT NULL,
        days_present INT DEFAULT 0,
        days_late INT DEFAULT 0,
        total_hours DECIMAL(7,2) DEFAULT 0,
        total_customers_served INT DEFAULT 0,
        base_pay DECIMAL(10,2) DEFAULT 0,
        deductions DECIMAL(10,2) DEFAULT 0,
        bonuses DECIMAL(10,2) DEFAULT 0,
        net_pay DECIMAL(10,2) DEFAULT 0,
        status ENUM('pending','approved','paid') NOT NULL DEFAULT 'pending',
        generated_by INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");
$conn->query("ALTER TABLE attendance ADD COLUMN IF NOT EXISTS actor_role VARCHAR(20) DEFAULT NULL");
$conn->query("ALTER TABLE attendance ADD COLUMN IF NOT EXISTS shift_notes TEXT DEFAULT NULL");
$conn->query("ALTER TABLE attendance ADD COLUMN IF NOT EXISTS customers_served INT DEFAULT NULL");

// Seed employees from existing admins if empty
$conn->query("
    INSERT INTO employees (admin_id, full_name, role, pay_type, rate, status)
    SELECT a.id, a.username, a.role, 'daily', 0, 'active'
    FROM admins a
    WHERE NOT EXISTS (SELECT 1 FROM employees e WHERE e.admin_id = a.id)
");

// ── Fetch employees with linked attendance username (for user-linked staff) ─
$employees = $conn->query("
    SELECT e.*, u.username AS linked_user
    FROM employees e
    LEFT JOIN users u ON u.id = e.user_id
    ORDER BY FIELD(e.role,'owner','super_admin','admin','manager','staff'), e.full_name ASC
")->fetch_all(MYSQLI_ASSOC);

// All `users` rows not yet linked to an employee (candidates to add as staff)
$unlinkedUsers = $conn->query("
    SELECT id, username FROM users u
    WHERE NOT EXISTS (SELECT 1 FROM employees e WHERE e.user_id = u.id)
    ORDER BY username ASC
")->fetch_all(MYSQLI_ASSOC);

// Recent payroll runs
$payrollRuns = $conn->query("
    SELECT p.*, e.full_name, e.role AS emp_role
    FROM payroll p
    JOIN employees e ON e.id = p.employee_id
    ORDER BY p.created_at DESC
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

$roleLabels = ['owner'=>'Owner','super_admin'=>'Super Admin','admin'=>'Admin','manager'=>'Manager','staff'=>'Staff'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payroll & Employees — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;--gold:#c9a84c;--gold-dim:#8a6f2e;--green:#4a7a3a;--green-lt:#6aaa52;--cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8;--red:#c0392b;--red-pale:rgba(192,57,43,0.1);--amber:#d4820a;--amber-pale:rgba(212,130,10,0.1);--sidebar-w:240px}
html{scroll-behavior:smooth}
body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 60% 40% at 15% 0%,rgba(192,57,43,0.04) 0%,transparent 60%),radial-gradient(ellipse 50% 60% at 85% 100%,rgba(201,168,76,0.05) 0%,transparent 60%);pointer-events:none;z-index:0}
#sidebar{position:fixed;top:0;left:0;height:100vh;width:var(--sidebar-w);background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;z-index:100}
.sb-brand{display:flex;align-items:center;gap:12px;padding:20px 16px 18px;border-bottom:1px solid var(--border);min-height:72px}
.sb-icon{width:36px;height:36px;border:1px solid rgba(192,57,43,0.4);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.sb-title{font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:600;color:var(--cream)}
.sb-title span{color:#e05a5a}
.sb-sub{font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:var(--muted);margin-top:2px}
.sb-nav{flex:1;padding:14px 10px;display:flex;flex-direction:column;gap:2px}
.sb-nav-label{font-size:9px;letter-spacing:0.2em;text-transform:uppercase;color:var(--muted);padding:10px 10px 4px}
.nav-item{display:flex;align-items:center;gap:12px;padding:11px 12px;border-radius:6px;text-decoration:none;color:var(--muted);font-size:13.5px;font-weight:400;transition:background 0.18s,color 0.18s}
.nav-item:hover{background:rgba(255,255,255,0.04);color:var(--text)}
.nav-item.active{background:rgba(192,57,43,0.1);color:#e05a5a}
.nav-icon{width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.sb-footer{padding:10px;border-top:1px solid var(--border)}
.nav-item.logout{color:#e05a5a}
.nav-item.logout:hover{background:rgba(224,90,90,0.08)}
#mainContent{margin-left:var(--sidebar-w);flex:1;min-width:0;position:relative;z-index:1;display:flex;flex-direction:column}
.topbar{position:sticky;top:0;z-index:100;background:rgba(11,11,9,0.9);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:0 32px;height:64px;display:flex;align-items:center;justify-content:space-between}
.topbar-sub{font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#e05a5a}
.topbar-title{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream)}
.topbar-user{font-size:12.5px;color:var(--muted)}
.topbar-user span{color:var(--gold);font-weight:500}
.page-body{padding:28px 32px 60px;display:flex;flex-direction:column;gap:24px;max-width:1300px}
.section-hd{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.section-hd-line{flex:1;height:1px;background:linear-gradient(90deg,var(--border),transparent)}
.section-title{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--cream)}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:4px;font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:0.06em;text-transform:uppercase;cursor:pointer;transition:all 0.2s;border:none}
.btn-green{background:var(--green);color:#fff}.btn-green:hover{background:var(--green-lt)}
.btn-gold{background:rgba(201,168,76,0.1);border:1px solid var(--gold-dim);color:var(--gold)}.btn-gold:hover{background:rgba(201,168,76,0.18)}
.btn-muted{background:transparent;border:1px solid var(--border);color:var(--muted)}.btn-muted:hover{border-color:var(--gold-dim);color:var(--gold)}
.table-card{background:var(--card);border:1px solid var(--border);border-radius:6px;overflow:hidden}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:var(--muted);padding:10px 16px;text-align:left;border-bottom:1px solid var(--border);font-weight:500}
tbody td{padding:12px 16px;font-size:13px;border-bottom:1px solid rgba(44,44,36,0.5);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:rgba(255,255,255,0.02)}
.empty-row td{text-align:center;color:var(--muted);padding:32px 16px;font-size:13px}
.role-pill{display:inline-flex;padding:3px 9px;border-radius:3px;font-size:10px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase}
.role-pill.owner{background:rgba(201,168,76,0.14);color:var(--gold)}
.role-pill.super_admin{background:rgba(192,57,43,0.12);color:#e05a5a}
.role-pill.admin{background:rgba(59,130,246,0.1);color:#60a5fa}
.role-pill.manager{background:rgba(74,122,58,0.12);color:var(--green-lt)}
.role-pill.staff{background:rgba(107,107,88,0.12);color:var(--muted)}
.role-select,.pay-select,.rate-input{background:var(--surface);border:1px solid var(--border);border-radius:3px;color:var(--text);font-family:'Jost',sans-serif;font-size:12px;padding:6px 8px;outline:none}
.status-pill{display:inline-flex;padding:3px 9px;border-radius:3px;font-size:10px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase}
.status-pill.pending{background:var(--amber-pale);color:var(--amber)}
.status-pill.approved{background:rgba(59,130,246,0.1);color:#60a5fa}
.status-pill.paid{background:rgba(74,122,58,0.12);color:var(--green-lt)}
.act-btn{padding:5px 10px;border-radius:3px;border:1px solid var(--border);background:transparent;color:var(--muted);font-size:11px;cursor:pointer;transition:all 0.18s}
.act-btn:hover{border-color:var(--gold-dim);color:var(--gold)}
.act-btn.danger:hover{border-color:rgba(192,57,43,0.4);color:#e05a5a}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.75);backdrop-filter:blur(6px);z-index:400;display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;pointer-events:none;transition:opacity 0.25s}
.modal-overlay.show{opacity:1;pointer-events:all}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:8px;width:100%;max-width:520px;padding:24px;transform:translateY(16px);transition:transform 0.3s}
.modal-overlay.show .modal{transform:translateY(0)}
.modal h3{font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--cream);margin-bottom:16px}
.form-group{margin-bottom:12px;display:flex;flex-direction:column;gap:5px}
.form-label{font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted)}
.form-input,.form-select{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:9px 12px;font-family:'Jost',sans-serif;font-size:13px;color:var(--text);outline:none}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.modal-footer{display:flex;justify-content:flex-end;gap:10px;margin-top:18px}
#toast{position:fixed;bottom:28px;right:28px;z-index:999;background:var(--card);border:1px solid var(--gold-dim);border-radius:6px;padding:12px 18px;font-size:13px;color:var(--cream);transform:translateY(16px);opacity:0;transition:all 0.3s;pointer-events:none}
#toast.show{transform:translateY(0);opacity:1}
@media(max-width:900px){.form-row{grid-template-columns:1fr}}
</style>
</head>
<body>

<aside id="sidebar">
    <div class="sb-brand">
        <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e05a5a" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div><div class="sb-title">Ayos<span>Coffee</span></div><div class="sb-sub">Admin Panel</div></div>
    </div>
    <nav class="sb-nav">
        <div class="sb-nav-label">Admin</div>
        <a href="admin-dashboard.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></span>
            Dashboard
        </a>
        <a href="admin-products.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/></svg></span>
            Products
        </a>
        <a href="admin-users.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span>
            User Management
        </a>
        <a href="admin-attendance.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
            Attendance Records
        </a>
        <?php if (($_SESSION['admin']['role'] ?? '') === 'owner'): ?>
        <a href="admin-payroll.php" class="nav-item active">
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
        <div><div class="topbar-sub">Owner Only</div><div class="topbar-title">Payroll & Employees</div></div>
        <div class="topbar-user">Logged in as <span><?= htmlspecialchars($_SESSION['admin']['username']) ?></span> (Owner)</div>
    </div>

    <div class="page-body">

        <!-- EMPLOYEES -->
        <div>
            <div class="section-hd">
                <div class="section-title">Employees & Roles</div>
                <div class="section-hd-line"></div>
                <button class="btn btn-green" onclick="openAddEmployee()">+ Add Employee</button>
            </div>
            <div class="table-card">
                <table>
                    <thead><tr><th>Name</th><th>Role</th><th>Position</th><th>Pay Type</th><th>Rate</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php if (empty($employees)): ?>
                        <tr class="empty-row"><td colspan="7">No employees yet. Click "Add Employee" to start.</td></tr>
                    <?php else: foreach ($employees as $e): ?>
                        <tr>
                            <td style="font-weight:500;color:var(--cream)"><?= htmlspecialchars($e['full_name']) ?>
                                <?php if ($e['linked_user']): ?><div style="font-size:11px;color:var(--muted)">kiosk login: <?= htmlspecialchars($e['linked_user']) ?></div><?php endif; ?>
                            </td>
                            <td>
                                <select class="role-select" onchange="updateEmployee(<?= $e['id'] ?>,'role',this.value)">
                                    <?php foreach ($roleLabels as $rv=>$rl): ?>
                                    <option value="<?= $rv ?>" <?= $e['role']===$rv?'selected':'' ?>><?= $rl ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><?= htmlspecialchars($e['position'] ?? '—') ?></td>
                            <td>
                                <select class="pay-select" onchange="updateEmployee(<?= $e['id'] ?>,'pay_type',this.value)">
                                    <option value="daily" <?= $e['pay_type']==='daily'?'selected':'' ?>>Daily</option>
                                    <option value="hourly" <?= $e['pay_type']==='hourly'?'selected':'' ?>>Hourly</option>
                                    <option value="monthly" <?= $e['pay_type']==='monthly'?'selected':'' ?>>Monthly</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" class="rate-input" style="width:80px" value="<?= $e['rate'] ?>" step="0.01" min="0"
                                       onchange="updateEmployee(<?= $e['id'] ?>,'rate',this.value)">
                            </td>
                            <td><span class="status-pill <?= $e['status']==='active'?'paid':'pending' ?>"><?= ucfirst($e['status']) ?></span></td>
                            <td>
                                <button class="act-btn" onclick="openGenerate(<?= $e['id'] ?>, '<?= htmlspecialchars(addslashes($e['full_name'])) ?>')">Generate Payroll</button>
                                <button class="act-btn danger" onclick="deleteEmployee(<?= $e['id'] ?>)">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PAYROLL RUNS -->
        <div>
            <div class="section-hd">
                <div class="section-title">Payroll History</div>
                <div class="section-hd-line"></div>
            </div>
            <div class="table-card">
                <table>
                    <thead><tr><th>Employee</th><th>Period</th><th>Days Present</th><th>Late</th><th>Hours</th><th>Customers Served</th><th>Net Pay</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if (empty($payrollRuns)): ?>
                        <tr class="empty-row"><td colspan="9">No payroll generated yet.</td></tr>
                    <?php else: foreach ($payrollRuns as $p): ?>
                        <tr>
                            <td style="font-weight:500;color:var(--cream)"><?= htmlspecialchars($p['full_name']) ?> <span class="role-pill <?= $p['emp_role'] ?>"><?= $roleLabels[$p['emp_role']] ?? $p['emp_role'] ?></span></td>
                            <td style="color:var(--muted);font-size:12px"><?= date('M j', strtotime($p['period_start'])) ?> – <?= date('M j, Y', strtotime($p['period_end'])) ?></td>
                            <td><?= $p['days_present'] ?></td>
                            <td style="color:<?= $p['days_late']>0?'#e05a5a':'var(--muted)' ?>"><?= $p['days_late'] ?></td>
                            <td><?= $p['total_hours'] ?>h</td>
                            <td><?= (int)$p['total_customers_served'] ?></td>
                            <td style="color:var(--gold);font-family:'Cormorant Garamond',serif;font-size:15px">₱<?= number_format($p['net_pay'],2) ?></td>
                            <td><span class="status-pill <?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
                            <td>
                                <?php if ($p['status'] !== 'paid'): ?>
                                <button class="act-btn" onclick="advancePayroll(<?= $p['id'] ?>)">Mark <?= $p['status']==='pending'?'Approved':'Paid' ?></button>
                                <?php else: ?>
                                <span style="font-size:11px;color:var(--muted)">✓ Done</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- ADD EMPLOYEE MODAL -->
<div class="modal-overlay" id="addModal" onclick="if(event.target===this)closeAdd()">
    <div class="modal">
        <h3>Add Employee</h3>
        <div class="form-group">
            <label class="form-label">Full Name</label>
            <input class="form-input" id="ne_name" type="text" placeholder="Juan Dela Cruz">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Role</label>
                <select class="form-select" id="ne_role">
                    <option value="staff">Staff</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                    <option value="super_admin">Super Admin</option>
                    <option value="owner">Owner</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Position</label>
                <input class="form-input" id="ne_position" type="text" placeholder="Barista">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Pay Type</label>
                <select class="form-select" id="ne_pay_type">
                    <option value="daily">Daily</option>
                    <option value="hourly">Hourly</option>
                    <option value="monthly">Monthly</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Rate (₱)</label>
                <input class="form-input" id="ne_rate" type="number" step="0.01" min="0" placeholder="0.00">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Link to existing kiosk/user account (optional)</label>
            <select class="form-select" id="ne_user_id">
                <option value="">— None —</option>
                <?php foreach ($unlinkedUsers as $u): ?>
                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="modal-footer">
            <button class="btn btn-muted" onclick="closeAdd()">Cancel</button>
            <button class="btn btn-green" onclick="saveEmployee()">Save Employee</button>
        </div>
    </div>
</div>

<!-- GENERATE PAYROLL MODAL -->
<div class="modal-overlay" id="genModal" onclick="if(event.target===this)closeGen()">
    <div class="modal">
        <h3>Generate Payroll — <span id="gen_emp_name"></span></h3>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Period Start</label>
                <input class="form-input" id="gen_start" type="date">
            </div>
            <div class="form-group">
                <label class="form-label">Period End</label>
                <input class="form-input" id="gen_end" type="date">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Bonuses (₱)</label>
                <input class="form-input" id="gen_bonus" type="number" step="0.01" value="0">
            </div>
            <div class="form-group">
                <label class="form-label">Deductions (₱)</label>
                <input class="form-input" id="gen_deduction" type="number" step="0.01" value="0">
            </div>
        </div>
        <p style="font-size:12px;color:var(--muted);line-height:1.5">
            Days present, lates, hours worked and total customers served will be pulled automatically
            from this employee's Attendance records within the period you pick.
        </p>
        <div class="modal-footer">
            <button class="btn btn-muted" onclick="closeGen()">Cancel</button>
            <button class="btn btn-gold" onclick="generatePayroll()">Compute & Save</button>
        </div>
    </div>
</div>

<div id="toast"></div>

<script>
let genEmployeeId = null;

function openAddEmployee(){ document.getElementById('addModal').classList.add('show'); }
function closeAdd(){ document.getElementById('addModal').classList.remove('show'); }
function openGenerate(id, name){
    genEmployeeId = id;
    document.getElementById('gen_emp_name').textContent = name;
    document.getElementById('genModal').classList.add('show');
}
function closeGen(){ document.getElementById('genModal').classList.remove('show'); genEmployeeId=null; }

function showToast(msg){
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(()=>t.classList.remove('show'), 2800);
}

function saveEmployee(){
    const fd = new FormData();
    fd.append('action','add_employee');
    fd.append('full_name', document.getElementById('ne_name').value.trim());
    fd.append('role', document.getElementById('ne_role').value);
    fd.append('position', document.getElementById('ne_position').value.trim());
    fd.append('pay_type', document.getElementById('ne_pay_type').value);
    fd.append('rate', document.getElementById('ne_rate').value || 0);
    fd.append('user_id', document.getElementById('ne_user_id').value);
    if (!fd.get('full_name')) { showToast('Pakilagay ang pangalan.'); return; }
    fetch('payroll_handler.php', { method:'POST', body: fd })
        .then(r=>r.json()).then(d=>{
            if (d.success) { showToast('Employee added.'); setTimeout(()=>location.reload(), 700); }
            else showToast(d.message || 'Failed to add employee.');
        }).catch(()=>showToast('Network error.'));
}

function updateEmployee(id, field, value){
    const fd = new FormData();
    fd.append('action','update_employee');
    fd.append('id', id);
    fd.append('field', field);
    fd.append('value', value);
    fetch('payroll_handler.php', { method:'POST', body: fd })
        .then(r=>r.json()).then(d=>{
            if (d.success) showToast('Saved.');
            else showToast(d.message || 'Update failed.');
        }).catch(()=>showToast('Network error.'));
}

function deleteEmployee(id){
    if (!confirm('Remove this employee record? Payroll history stays but the employee is delisted.')) return;
    const fd = new FormData();
    fd.append('action','delete_employee');
    fd.append('id', id);
    fetch('payroll_handler.php', { method:'POST', body: fd })
        .then(r=>r.json()).then(d=>{
            if (d.success) { showToast('Removed.'); setTimeout(()=>location.reload(), 600); }
            else showToast(d.message || 'Failed.');
        }).catch(()=>showToast('Network error.'));
}

function generatePayroll(){
    const start = document.getElementById('gen_start').value;
    const end = document.getElementById('gen_end').value;
    if (!start || !end) { showToast('Pumili ng date range.'); return; }
    const fd = new FormData();
    fd.append('action','generate_payroll');
    fd.append('employee_id', genEmployeeId);
    fd.append('period_start', start);
    fd.append('period_end', end);
    fd.append('bonuses', document.getElementById('gen_bonus').value || 0);
    fd.append('deductions', document.getElementById('gen_deduction').value || 0);
    fetch('payroll_handler.php', { method:'POST', body: fd })
        .then(r=>r.json()).then(d=>{
            if (d.success) { showToast('Payroll generated: ₱' + d.net_pay); setTimeout(()=>location.reload(), 900); }
            else showToast(d.message || 'Failed to generate payroll.');
        }).catch(()=>showToast('Network error.'));
}

function advancePayroll(id){
    const fd = new FormData();
    fd.append('action','advance_payroll');
    fd.append('id', id);
    fetch('payroll_handler.php', { method:'POST', body: fd })
        .then(r=>r.json()).then(d=>{
            if (d.success) { showToast('Status updated.'); setTimeout(()=>location.reload(), 600); }
            else showToast(d.message || 'Failed.');
        }).catch(()=>showToast('Network error.'));
}
</script>
</body>
</html>