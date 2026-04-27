<?php
include 'admin-config.php';
requireAdmin();

// Ensure tables
$conn->query("CREATE TABLE IF NOT EXISTS attendance (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT DEFAULT NULL, admin_id INT DEFAULT NULL, type ENUM('user','admin') NOT NULL DEFAULT 'user', clock_in DATETIME DEFAULT NULL, clock_out DATETIME DEFAULT NULL, date DATE NOT NULL, photo_in TEXT DEFAULT NULL, photo_out TEXT DEFAULT NULL, status VARCHAR(20) DEFAULT 'present', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
$conn->query("CREATE TABLE IF NOT EXISTS face_descriptors (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT DEFAULT NULL, admin_id INT DEFAULT NULL, type ENUM('user','admin') NOT NULL DEFAULT 'user', descriptor TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");

// ── FILTERS ────────────────────────────────────────────────────────────────
$filterDate = $_GET['date'] ?? date('Y-m-d');
$filterUser = (int)($_GET['user_id'] ?? 0);

// ── FETCH USERS ────────────────────────────────────────────────────────────
$users = $conn->query("SELECT id, username FROM users ORDER BY username ASC")->fetch_all(MYSQLI_ASSOC);

// ── FETCH ATTENDANCE ───────────────────────────────────────────────────────
$where = "WHERE a.date = '$filterDate'";
if ($filterUser > 0) $where .= " AND a.user_id = $filterUser";

$records = $conn->query("
    SELECT a.*, u.username
    FROM attendance a
    LEFT JOIN users u ON u.id = a.user_id
    $where
    ORDER BY a.clock_in DESC
")->fetch_all(MYSQLI_ASSOC);

// ── SUMMARY STATS ──────────────────────────────────────────────────────────
$totalToday    = count($records);
$completeToday = count(array_filter($records, fn($r) => $r['clock_in'] && $r['clock_out']));
$incompleteToday = count(array_filter($records, fn($r) => $r['clock_in'] && !$r['clock_out']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance Records — Admin</title>
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
.topbar-user{font-size:12.5px;color:var(--muted)}
.topbar-user span{color:var(--gold);font-weight:500}
.page-body{padding:28px 32px 60px;display:flex;flex-direction:column;gap:24px;max-width:1200px}
.stat-row{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:20px 22px;position:relative;overflow:hidden;transition:border-color 0.2s}
.stat-card:hover{border-color:var(--gold-dim)}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--gold-dim),transparent)}
.stat-label{font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted);margin-bottom:6px}
.stat-value{font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:700;color:var(--cream)}
.stat-value.green{color:var(--green-lt)}
.stat-value.amber{color:var(--amber)}
.filter-bar{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:20px 22px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.filter-label{font-size:11px;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted)}
.filter-input{background:var(--surface);border:1px solid var(--border);border-radius:3px;color:var(--cream);font-family:'Jost',sans-serif;font-size:13px;padding:8px 12px;outline:none;transition:border-color 0.2s}
.filter-input:focus{border-color:var(--gold-dim)}
.filter-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:#fff;cursor:pointer;transition:background 0.2s}
.filter-btn:hover{background:var(--green-lt)}
.section-hd{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.section-hd-line{flex:1;height:1px;background:linear-gradient(90deg,var(--border),transparent)}
.section-title{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--cream)}
.table-card{background:var(--card);border:1px solid var(--border);border-radius:6px;overflow:hidden}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:var(--muted);padding:10px 16px;text-align:left;border-bottom:1px solid var(--border);font-weight:500}
tbody td{padding:12px 16px;font-size:13px;border-bottom:1px solid rgba(44,44,36,0.5);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:rgba(255,255,255,0.02)}
.empty-row td{text-align:center;color:var(--muted);padding:32px 16px;font-size:13px}
.att-badge{display:inline-flex;padding:3px 8px;border-radius:3px;font-size:10px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase}
.att-badge.complete{background:rgba(74,122,58,0.1);color:var(--green-lt)}
.att-badge.incomplete{background:var(--amber-pale);color:var(--amber)}
.user-avatar{width:30px;height:30px;border-radius:50%;border:1px solid var(--gold-dim);background:linear-gradient(135deg,#2a1f08,#1a1a16);display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-size:12px;font-weight:700;color:var(--gold)}
.user-info-wrap{display:flex;align-items:center;gap:8px}
@media(max-width:768px){.page-body{padding:20px 16px 48px}.stat-row{grid-template-columns:1fr 1fr}}
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
        <a href="admin-users.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
            User Management
        </a>
        <a href="admin-attendance.php" class="nav-item active">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
            Attendance Records
        </a>
        <div class="sb-nav-label">Site</div>
        <a href="index.php" class="nav-item" target="_blank">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
            View Menu
        </a>
    </nav>
    <div class="sb-footer">
        <a href="admin-logout.php" class="nav-item logout">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
            Logout
        </a>
    </div>
</aside>

<div id="mainContent">
    <div class="topbar">
        <div><div class="topbar-sub">Admin Panel</div><div class="topbar-title">Attendance Records</div></div>
        <div class="topbar-user">Logged in as <span><?= htmlspecialchars($_SESSION['admin']['username']) ?></span></div>
    </div>

    <div class="page-body">

        <!-- STATS -->
        <div class="stat-row">
            <div class="stat-card">
                <div class="stat-label">Total Records</div>
                <div class="stat-value"><?= $totalToday ?></div>
                <div style="font-size:11px;color:var(--muted);margin-top:4px"><?= date('M j, Y', strtotime($filterDate)) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Complete</div>
                <div class="stat-value green"><?= $completeToday ?></div>
                <div style="font-size:11px;color:var(--muted);margin-top:4px">Clocked in & out</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Still In</div>
                <div class="stat-value amber"><?= $incompleteToday ?></div>
                <div style="font-size:11px;color:var(--muted);margin-top:4px">No clock out yet</div>
            </div>
        </div>

        <!-- FILTERS -->
        <form method="GET" action="admin-attendance.php">
            <div class="filter-bar">
                <div class="filter-label">Date</div>
                <input type="date" name="date" class="filter-input" value="<?= htmlspecialchars($filterDate) ?>">
                <div class="filter-label">User</div>
                <select name="user_id" class="filter-input">
                    <option value="0">All Users</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $filterUser === (int)$u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['username']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="filter-btn">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Filter
                </button>
            </div>
        </form>

        <!-- TABLE -->
        <div>
            <div class="section-hd">
                <div class="section-title">Attendance Log</div>
                <div class="section-hd-line"></div>
            </div>
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Date</th>
                            <th>Clock In</th>
                            <th>Clock Out</th>
                            <th>Hours</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($records)): ?>
                        <tr class="empty-row"><td colspan="6">No attendance records for this date.</td></tr>
                    <?php else: ?>
                        <?php foreach ($records as $r):
                            $hoursStr = '—';
                            $badge = 'incomplete';
                            if ($r['clock_in'] && $r['clock_out']) {
                                $in  = new DateTime($r['clock_in']);
                                $out = new DateTime($r['clock_out']);
                                $d   = $in->diff($out);
                                $hoursStr = ($d->h + $d->days * 24) . 'h ' . $d->i . 'm';
                                $badge = 'complete';
                            }
                            $uname = $r['username'] ?? 'Unknown';
                        ?>
                        <tr>
                            <td>
                                <div class="user-info-wrap">
                                    <div class="user-avatar"><?= strtoupper(substr($uname, 0, 2)) ?></div>
                                    <span style="font-weight:500;color:var(--cream)"><?= htmlspecialchars($uname) ?></span>
                                </div>
                            </td>
                            <td style="color:var(--muted)"><?= date('M j, Y', strtotime($r['date'])) ?></td>
                            <td style="color:var(--green-lt)"><?= $r['clock_in'] ? date('h:i A', strtotime($r['clock_in'])) : '—' ?></td>
                            <td style="color:var(--amber)"><?= $r['clock_out'] ? date('h:i A', strtotime($r['clock_out'])) : '—' ?></td>
                            <td style="font-family:'Cormorant Garamond',serif;font-size:15px;color:var(--gold)"><?= $hoursStr ?></td>
                            <td><span class="att-badge <?= $badge ?>"><?= $badge === 'complete' ? 'Complete' : 'Incomplete' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
</body>
</html>
