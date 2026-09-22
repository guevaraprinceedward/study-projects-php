<?php
include 'admin-config.php';
requireAdmin();

$adminId   = (int)($_SESSION['admin']['id'] ?? 0);
$adminName = $_SESSION['admin']['username'] ?? 'Admin';
$adminRole = $_SESSION['admin']['role'] ?? 'staff';
$isOwner   = $adminRole === 'owner';

// Only Admin and Staff clock a "customers served" count — Owner and Super Admin don't serve customers directly.
$canServeCustomers = in_array($adminRole, ['admin', 'staff'], true);

$roleLabels = ['owner'=>'Owner','super_admin'=>'Super Admin','admin'=>'Admin','manager'=>'Manager','staff'=>'Staff'];

// ── Ensure tables / columns exist ───────────────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        admin_id INT DEFAULT NULL,
        type ENUM('user','admin') NOT NULL DEFAULT 'user',
        clock_in DATETIME DEFAULT NULL,
        clock_out DATETIME DEFAULT NULL,
        date DATE NOT NULL,
        photo_in TEXT DEFAULT NULL,
        photo_out TEXT DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'present',
        punctuality VARCHAR(20) DEFAULT NULL,
        minutes_late INT DEFAULT 0,
        upload_mode TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");
$conn->query("ALTER TABLE attendance ADD COLUMN IF NOT EXISTS punctuality VARCHAR(20) DEFAULT NULL");
$conn->query("ALTER TABLE attendance ADD COLUMN IF NOT EXISTS minutes_late INT DEFAULT 0");
$conn->query("ALTER TABLE attendance ADD COLUMN IF NOT EXISTS upload_mode TINYINT(1) DEFAULT 0");
$conn->query("ALTER TABLE attendance ADD COLUMN IF NOT EXISTS actor_role VARCHAR(20) DEFAULT NULL");
$conn->query("ALTER TABLE attendance ADD COLUMN IF NOT EXISTS shift_notes TEXT DEFAULT NULL");
$conn->query("ALTER TABLE attendance ADD COLUMN IF NOT EXISTS customers_served INT DEFAULT 0");
$conn->query("
    CREATE TABLE IF NOT EXISTS face_descriptors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        admin_id INT DEFAULT NULL,
        type ENUM('user','admin') NOT NULL DEFAULT 'user',
        descriptor TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

define('SHIFT_START_HOUR', 8);
define('SHIFT_START_MIN', 0);

function getPunctuality($clockInTime) {
    $clockIn = new DateTime($clockInTime);
    $shiftStart = new DateTime($clockIn->format('Y-m-d') . ' 08:00:00');
    if ($clockIn <= $shiftStart) return ['status' => 'on_time', 'minutes_late' => 0];
    $diff = $shiftStart->diff($clockIn);
    return ['status' => 'late', 'minutes_late' => ($diff->h * 60) + $diff->i];
}

// ── AJAX ACTIONS ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'register_face') {
        $descriptor = trim($_POST['descriptor'] ?? '');
        if (empty($descriptor)) { echo json_encode(['success'=>false,'message'=>'No face descriptor provided.']); exit(); }
        $existing = $conn->query("SELECT id FROM face_descriptors WHERE admin_id = $adminId AND type = 'admin' LIMIT 1");
        if ($existing && $existing->num_rows > 0) {
            $conn->query("UPDATE face_descriptors SET descriptor = '$descriptor', updated_at = NOW() WHERE admin_id = $adminId AND type = 'admin'");
        } else {
            $conn->query("INSERT INTO face_descriptors (admin_id, type, descriptor) VALUES ($adminId, 'admin', '$descriptor')");
        }
        echo json_encode(['success'=>true,'message'=>'Face registered successfully!']);
        exit();
    }

    if ($action === 'clock_in') {
        $descriptor  = trim($_POST['descriptor'] ?? '');
        $photo       = $_POST['photo'] ?? '';
        $uploadMode  = isset($_POST['upload_mode']) ? 1 : 0;
        $today       = date('Y-m-d');
        $roleEsc     = $conn->real_escape_string($adminRole);

        $existing = $conn->query("SELECT id, clock_in FROM attendance WHERE admin_id = $adminId AND date = '$today' AND type = 'admin' LIMIT 1");
        if ($existing && $existing->num_rows > 0) {
            $row = $existing->fetch_assoc();
            if ($row['clock_in']) { echo json_encode(['success'=>false,'message'=>'Already clocked in today at '.date('h:i A',strtotime($row['clock_in']))]); exit(); }
        }

        if (!$uploadMode) {
            $faceRec = $conn->query("SELECT descriptor FROM face_descriptors WHERE admin_id = $adminId AND type = 'admin' LIMIT 1");
            if (!$faceRec || $faceRec->num_rows === 0) { echo json_encode(['success'=>false,'message'=>'No registered face found. Please register your face first.']); exit(); }
        }

        $now      = date('Y-m-d H:i:s');
        $punct    = getPunctuality($now);
        $pStatus  = $punct['status'];
        $minsLate = $punct['minutes_late'];
        // Owner owns the system/shift — never flagged as late.
        if ($adminRole === 'owner') { $pStatus = 'on_time'; $minsLate = 0; }
        $photoEsc = $conn->real_escape_string(substr($photo, 0, 65535));

        $conn->query("INSERT INTO attendance (admin_id, type, clock_in, date, photo_in, status, punctuality, minutes_late, upload_mode, actor_role, customers_served)
                      VALUES ($adminId, 'admin', '$now', '$today', '$photoEsc', 'present', '$pStatus', $minsLate, $uploadMode, '$roleEsc', 0)");

        $modeLabel = $uploadMode ? ' (Photo Upload)' : '';
        $msg = $pStatus === 'late'
            ? 'Clocked In at '.date('h:i A').' — You are '.$minsLate.' minute(s) late.'.$modeLabel
            : 'Clocked In at '.date('h:i A').' — On Time! Great job!'.$modeLabel;

        echo json_encode(['success'=>true,'message'=>$msg,'time'=>date('h:i A'),'punctuality'=>$pStatus,'minutes_late'=>$minsLate,'upload_mode'=>$uploadMode]);
        exit();
    }

    if ($action === 'clock_out') {
        $photo      = $_POST['photo'] ?? '';
        $uploadMode = isset($_POST['upload_mode']) ? 1 : 0;
        $today      = date('Y-m-d');

        $existing = $conn->query("SELECT id, clock_in FROM attendance WHERE admin_id = $adminId AND date = '$today' AND type = 'admin' AND clock_out IS NULL LIMIT 1");
        if (!$existing || $existing->num_rows === 0) { echo json_encode(['success'=>false,'message'=>'No active clock-in found for today.']); exit(); }
        $row      = $existing->fetch_assoc();
        $attId    = (int)$row['id'];
        $now      = date('Y-m-d H:i:s');
        $photoEsc = $conn->real_escape_string(substr($photo, 0, 65535));
        $clockIn  = new DateTime($row['clock_in']);
        $clockOut = new DateTime($now);
        $diff     = $clockIn->diff($clockOut);
        $hours    = $diff->h + ($diff->days * 24);
        $mins     = $diff->i;
        $conn->query("UPDATE attendance SET clock_out = '$now', photo_out = '$photoEsc' WHERE id = $attId");
        echo json_encode(['success'=>true,'message'=>'Clock Out recorded at '.date('h:i A'),'time'=>date('h:i A'),'duration'=>$hours.'h '.$mins.'m']);
        exit();
    }

    // Customer-service counter — increments today's attendance row so it feeds directly
    // into payroll's total_customers_served (see payroll_handler.php generate_payroll).
    if ($action === 'mark_served') {
        if (!in_array($adminRole, ['admin', 'staff'], true)) {
            echo json_encode(['success'=>false,'message'=>'Customer-served tracking is only for Admin and Staff accounts.']); exit();
        }
        $today = date('Y-m-d');
        $delta = isset($_POST['undo']) ? -1 : 1;

        $existing = $conn->query("SELECT id, customers_served FROM attendance WHERE admin_id = $adminId AND date = '$today' AND type = 'admin' AND clock_in IS NOT NULL LIMIT 1");
        if (!$existing || $existing->num_rows === 0) { echo json_encode(['success'=>false,'message'=>'Clock in first before marking customers served.']); exit(); }
        $row = $existing->fetch_assoc();
        $newCount = max(0, (int)$row['customers_served'] + $delta);
        $conn->query("UPDATE attendance SET customers_served = $newCount WHERE id = " . (int)$row['id']);
        echo json_encode(['success'=>true,'customers_served'=>$newCount]);
        exit();
    }

    echo json_encode(['success'=>false,'message'=>'Unknown action.']);
    exit();
}

// ── TODAY STATUS (own) ──────────────────────────────────────────────────
$today = date('Y-m-d');
$todayRecord = null;
$res = $conn->query("SELECT * FROM attendance WHERE admin_id = $adminId AND date = '$today' AND type = 'admin' LIMIT 1");
if ($res && $res->num_rows > 0) $todayRecord = $res->fetch_assoc();
$customersServedToday = (int)($todayRecord['customers_served'] ?? 0);

// ── FACE REGISTERED (own) ───────────────────────────────────────────────
$faceRegistered = false;
$storedDescriptor = null;
$faceRes = $conn->query("SELECT descriptor FROM face_descriptors WHERE admin_id = $adminId AND type = 'admin' LIMIT 1");
if ($faceRes && $faceRes->num_rows > 0) {
    $faceRegistered   = true;
    $storedDescriptor = $faceRes->fetch_assoc()['descriptor'];
}

// ── MY HISTORY ───────────────────────────────────────────────────────────
$history = $conn->query("
    SELECT * FROM attendance
    WHERE admin_id = $adminId AND type = 'admin'
    ORDER BY date DESC, clock_in DESC
    LIMIT 30
")->fetch_all(MYSQLI_ASSOC);

// ── MY PAYSLIPS (if linked to an employee record) ───────────────────────
$myEmployee = $conn->query("SELECT id FROM employees WHERE admin_id = $adminId LIMIT 1")->fetch_assoc();
$myPayroll = [];
if ($myEmployee) {
    $myPayroll = $conn->query("
        SELECT * FROM payroll WHERE employee_id = " . (int)$myEmployee['id'] . "
        ORDER BY created_at DESC LIMIT 12
    ")->fetch_all(MYSQLI_ASSOC);
}

// ── OWNER: TEAM OVERVIEW ────────────────────────────────────────────────
$activeTab   = ($_GET['tab'] ?? 'my') === 'team' && $isOwner ? 'team' : 'my';
$filterDate  = $_GET['date'] ?? $today;
$filterAdmin = (int)($_GET['admin_id'] ?? 0);
$teamStats = [];
$teamToday = [];
$teamHistory = [];
$adminList = [];
if ($isOwner) {
    $adminList = $conn->query("SELECT id, username, role FROM admins ORDER BY username ASC")->fetch_all(MYSQLI_ASSOC);

    $where = "a.date = '" . $conn->real_escape_string($filterDate) . "' AND a.type = 'admin'";
    if ($filterAdmin > 0) $where .= " AND a.admin_id = $filterAdmin";
    $teamToday = $conn->query("
        SELECT a.*, ad.username, ad.role
        FROM attendance a
        LEFT JOIN admins ad ON ad.id = a.admin_id
        WHERE $where
        ORDER BY a.clock_in ASC
    ")->fetch_all(MYSQLI_ASSOC);

    $onTimeCount = count(array_filter($teamToday, fn($r) => $r['punctuality'] === 'on_time'));
    $lateCount   = count(array_filter($teamToday, fn($r) => $r['punctuality'] === 'late'));
    $servedTotal = array_sum(array_map(
        fn($r) => in_array($r['role'] ?? 'staff', ['admin','staff'], true) ? (int)($r['customers_served'] ?? 0) : 0,
        $teamToday
    ));

    $teamHistory = $conn->query("
        SELECT a.*, ad.username, ad.role
        FROM attendance a
        LEFT JOIN admins ad ON ad.id = a.admin_id
        WHERE a.type = 'admin' AND a.date >= DATE_SUB('$today', INTERVAL 7 DAY)
        ORDER BY a.date DESC, a.clock_in ASC
        LIMIT 150
    ")->fetch_all(MYSQLI_ASSOC);
}

// ── NOTIFICATION HINT ─────────────────────────────────────────────────────
$now = new DateTime();
$shiftStart = new DateTime(date('Y-m-d').' 08:00:00');
$minutesToShift = (int)(($shiftStart->getTimestamp() - $now->getTimestamp()) / 60);
$showHint  = !$todayRecord && $minutesToShift <= 30 && $minutesToShift > 0;
$isLateNow = !$isOwner && !$todayRecord && $now > $shiftStart;

$usernameEsc = htmlspecialchars($adminName);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;--gold:#c9a84c;--gold-dim:#8a6f2e;--green:#4a7a3a;--green-lt:#6aaa52;--cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8;--red:#c0392b;--red-pale:rgba(192,57,43,0.1);--amber:#d4820a;--amber-pale:rgba(212,130,10,0.1);--sidebar-w:240px}
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

.notif-banner{position:relative;z-index:10;padding:20px 32px 0}
.notif-card{border-radius:6px;padding:14px 18px;display:flex;align-items:center;gap:14px;font-size:13.5px}
.notif-card.warn{background:rgba(212,130,10,0.1);border:1px solid rgba(212,130,10,0.35);color:var(--amber)}
.notif-card.danger{background:rgba(192,57,43,0.1);border:1px solid rgba(192,57,43,0.35);color:#e05a5a}
.notif-card.success{background:rgba(74,122,58,0.1);border:1px solid rgba(74,122,58,0.3);color:var(--green-lt)}

.page-body{padding:24px 32px 60px;display:flex;flex-direction:column;gap:24px;max-width:1300px}
.page-title{font-family:'Cormorant Garamond',serif;font-size:30px;font-weight:700;color:var(--cream)}
.page-sub{font-size:13px;color:var(--muted);margin-top:4px}
.role-pill{display:inline-flex;padding:3px 9px;border-radius:3px;font-size:10px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;margin-left:8px}
.role-pill.owner{background:rgba(201,168,76,0.14);color:var(--gold)}
.role-pill.super_admin{background:rgba(192,57,43,0.12);color:#e05a5a}
.role-pill.admin{background:rgba(59,130,246,0.1);color:#60a5fa}
.role-pill.manager{background:rgba(74,122,58,0.12);color:var(--green-lt)}
.role-pill.staff{background:rgba(107,107,88,0.12);color:var(--muted)}

.tab-bar{display:flex;gap:4px;background:var(--surface);border:1px solid var(--border);border-radius:6px;padding:4px;width:fit-content}
.tab-btn{padding:9px 22px;border-radius:4px;border:none;background:transparent;font-family:'Jost',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted);cursor:pointer;text-decoration:none;display:inline-block}
.tab-btn.active{background:var(--card);color:var(--gold);border:1px solid var(--border)}

.status-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
.status-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:20px 22px;display:flex;flex-direction:column;gap:6px;position:relative;overflow:hidden}
.status-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--gold-dim),transparent)}
.status-label{font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:var(--muted)}
.status-value{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--cream)}
.status-value.green{color:var(--green-lt)}
.status-value.amber{color:var(--amber)}
.status-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:3px;font-size:11px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase}
.status-badge.clocked-in{background:rgba(74,122,58,0.12);color:var(--green-lt)}
.status-badge.clocked-out{background:rgba(201,168,76,0.08);color:var(--gold)}
.status-badge.not-in{background:var(--red-pale);color:#e05a5a}

.punct-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:3px;font-size:10px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase}
.punct-badge.on-time{background:rgba(74,122,58,0.12);color:var(--green-lt)}
.punct-badge.late{background:var(--red-pale);color:#e05a5a}
.punct-badge.none{background:rgba(107,107,88,0.1);color:var(--muted)}

.cam-mode-tabs{display:flex;gap:0;background:var(--surface);border:1px solid var(--border);border-radius:3px;margin-bottom:14px;overflow:hidden}
.cam-mode-tab{flex:1;padding:9px 10px;border:none;background:transparent;font-family:'Jost',sans-serif;font-size:11.5px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px}
.cam-mode-tab.active{background:var(--card);color:var(--gold);box-shadow:inset 0 -2px 0 var(--gold)}

.upload-zone{border:2px dashed var(--border);border-radius:4px;padding:32px 20px;text-align:center;cursor:pointer;background:var(--surface);position:relative;aspect-ratio:4/3;display:flex;align-items:center;justify-content:center}
.upload-zone.has-image{border:2px solid var(--green);padding:0;overflow:hidden;cursor:default}
.upload-zone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;z-index:2}
.upload-zone.has-image input[type=file]{display:none}
.upload-zone-inner{display:flex;flex-direction:column;align-items:center;gap:10px;pointer-events:none}
.upload-zone-icon{width:48px;height:48px;border:1px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--muted)}
.upload-zone-text{font-size:13px;color:var(--muted);line-height:1.6;text-align:center}
.upload-zone-text strong{color:var(--cream);display:block;font-size:14px;margin-bottom:2px}
#upload-preview{width:100%;height:100%;object-fit:cover;display:block;border-radius:2px}
.upload-mode-note{background:rgba(212,130,10,0.06);border:1px solid rgba(212,130,10,0.2);border-radius:3px;padding:10px 14px;font-size:11.5px;color:var(--muted);margin-top:10px}
.upload-mode-note strong{color:var(--amber)}
.upload-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:3px;font-size:10px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;background:rgba(212,130,10,0.1);color:var(--amber);margin-left:6px}

.two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start}
.panel{background:var(--card);border:1px solid var(--border);border-radius:6px;overflow:hidden}
.panel-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
.panel-title{font-size:14px;font-weight:500;color:var(--cream)}
.panel-body{padding:20px}

#video-wrap{position:relative;border-radius:4px;overflow:hidden;background:#000;aspect-ratio:4/3}
#att-video{width:100%;height:100%;object-fit:cover;display:block}
#face-overlay{position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none}
#att-preview{width:100%;border-radius:4px;display:none}

.cam-btns{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:3px;font-family:'Jost',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;cursor:pointer;border:none}
.btn-green{background:var(--green);color:#fff}.btn-green:hover{background:var(--green-lt)}.btn-green:disabled{opacity:0.4;cursor:not-allowed}
.btn-gold{background:rgba(201,168,76,0.1);border:1px solid var(--gold-dim);color:var(--gold)}.btn-gold:hover{background:rgba(201,168,76,0.18)}.btn-gold:disabled{opacity:0.4;cursor:not-allowed}
.btn-red{background:var(--red-pale);border:1px solid rgba(192,57,43,0.4);color:#e05a5a}.btn-red:hover{background:rgba(192,57,43,0.2)}.btn-red:disabled{opacity:0.4;cursor:not-allowed}
.btn-muted{background:transparent;border:1px solid var(--border);color:var(--muted)}.btn-muted:hover{border-color:var(--gold-dim);color:var(--gold)}.btn-muted:disabled{opacity:0.4;cursor:not-allowed}

.readiness{display:flex;flex-direction:column;gap:8px;margin-bottom:16px}
.ready-item{display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--muted);padding:8px 12px;background:var(--surface);border-radius:3px}
.ready-icon{flex-shrink:0;width:16px;text-align:center}
.ready-item.ok{color:var(--green-lt)}
.ready-item.fail{color:#e05a5a}

.action-btns{display:flex;flex-direction:column;gap:10px;margin-top:16px}
.clock-btn{display:flex;align-items:center;justify-content:center;gap:10px;padding:14px;border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;cursor:pointer;border:none}
.clock-in-btn{background:var(--green);color:#fff}.clock-in-btn:hover:not(:disabled){background:var(--green-lt)}
.clock-out-btn{background:#8b2e2e;color:#fff}.clock-out-btn:hover:not(:disabled){background:var(--red)}
.clock-btn:disabled{opacity:0.35;cursor:not-allowed}

#face-status{margin-top:10px;font-size:12.5px;min-height:32px}
.fs-ok{color:var(--green-lt);display:flex;align-items:center;gap:6px}
.fs-fail{color:#e05a5a;display:flex;align-items:center;gap:6px}
.fs-info{color:var(--gold);display:flex;align-items:center;gap:6px}

/* CUSTOMER SERVED CARD */
.served-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:22px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}
.served-count-wrap{display:flex;align-items:baseline;gap:10px}
.served-num{font-family:'Cormorant Garamond',serif;font-size:44px;font-weight:700;color:var(--gold)}
.served-label{font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted)}
.served-btns{display:flex;gap:8px}
.served-btn{width:44px;height:44px;border-radius:50%;border:none;font-size:20px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center}
.served-btn.plus{background:var(--green);color:#fff}.served-btn.plus:hover:not(:disabled){background:var(--green-lt)}
.served-btn.minus{background:var(--surface);border:1px solid var(--border);color:var(--muted)}.served-btn.minus:hover:not(:disabled){border-color:var(--gold-dim);color:var(--gold)}
.served-btn:disabled{opacity:0.35;cursor:not-allowed}
.served-hint{font-size:11.5px;color:var(--muted)}

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
.att-badge.incomplete{background:rgba(212,130,10,0.1);color:var(--amber)}
.status-pill{display:inline-flex;padding:3px 9px;border-radius:3px;font-size:10px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase}
.status-pill.pending{background:var(--amber-pale);color:var(--amber)}
.status-pill.approved{background:rgba(59,130,246,0.1);color:#60a5fa}
.status-pill.paid{background:rgba(74,122,58,0.12);color:var(--green-lt)}
.payslip-link{color:var(--gold);font-size:12px;text-decoration:none;letter-spacing:0.04em}
.payslip-link:hover{text-decoration:underline}

.live-clock{font-family:'Cormorant Garamond',serif;font-size:44px;font-weight:700;color:var(--gold);letter-spacing:0.04em;text-align:center;padding:14px 0 6px}
.shift-hint{text-align:center;font-size:12px;color:var(--muted);margin-bottom:4px}
.shift-hint strong{color:var(--cream)}

.filter-bar{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:16px 20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.filter-label{font-size:11px;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted)}
.filter-input{background:var(--surface);border:1px solid var(--border);border-radius:3px;color:var(--cream);font-family:'Jost',sans-serif;font-size:13px;padding:8px 12px;outline:none}
.filter-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:#fff;cursor:pointer}

.admin-stat-row{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.admin-stat{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:20px 22px;position:relative;overflow:hidden}
.admin-stat::before{content:'';position:absolute;top:0;left:0;right:0;height:2px}
.admin-stat.green-stat::before{background:linear-gradient(90deg,var(--green-lt),transparent)}
.admin-stat.red-stat::before{background:linear-gradient(90deg,var(--red),transparent)}
.admin-stat.gold-stat::before{background:linear-gradient(90deg,var(--gold),transparent)}
.admin-stat-num{font-family:'Cormorant Garamond',serif;font-size:38px;font-weight:700;line-height:1}
.admin-stat.green-stat .admin-stat-num{color:var(--green-lt)}
.admin-stat.red-stat .admin-stat-num{color:#e05a5a}
.admin-stat.gold-stat .admin-stat-num{color:var(--gold)}
.admin-stat-label{font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:var(--muted);margin-top:6px}

#toast{position:fixed;bottom:28px;right:28px;z-index:999;background:var(--card);border:1px solid var(--gold-dim);border-radius:6px;padding:16px 20px;display:flex;align-items:flex-start;gap:14px;font-size:14px;color:var(--cream);box-shadow:0 8px 32px rgba(0,0,0,0.6);transform:translateY(30px);opacity:0;transition:all 0.35s cubic-bezier(0.4,0,0.2,1);pointer-events:none;max-width:380px}
#toast.show{transform:translateY(0);opacity:1;pointer-events:all}
#toast.success{border-color:var(--green)}
#toast.error{border-color:var(--red)}
#toast.warn{border-color:var(--amber)}
.toast-title{font-weight:600;font-size:13px;margin-bottom:3px}
.toast-msg{font-size:12.5px;color:var(--muted);line-height:1.5}
.toast-close{margin-left:auto;flex-shrink:0;background:transparent;border:none;color:var(--muted);cursor:pointer}

#faceModal{position:fixed;inset:0;z-index:300;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.75);backdrop-filter:blur(6px);opacity:0;pointer-events:none;transition:opacity 0.3s}
#faceModal.show{opacity:1;pointer-events:all}
.faceModal-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:28px;max-width:480px;width:92%;transform:translateY(20px);transition:transform 0.3s;position:relative}
#faceModal.show .faceModal-card{transform:translateY(0)}
.modal-title{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream);margin-bottom:6px}
.modal-sub{font-size:13px;color:var(--muted);margin-bottom:18px;line-height:1.6}
#reg-video-wrap{position:relative;border-radius:4px;overflow:hidden;background:#000;aspect-ratio:4/3;margin-bottom:12px}
#reg-video{width:100%;height:100%;object-fit:cover;display:block}
#reg-overlay{position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none}
#reg-preview{width:100%;border-radius:4px;display:none;margin-bottom:12px}
.modal-close{position:absolute;top:12px;right:12px;width:28px;height:28px;border:1px solid var(--border);border-radius:50%;background:transparent;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center}
.modal-close:hover{border-color:var(--gold-dim);color:var(--cream)}

@media(max-width:768px){.page-body{padding:20px 16px 48px}.two-col{grid-template-columns:1fr}.status-row,.admin-stat-row{grid-template-columns:1fr 1fr}}
@media(max-width:480px){.status-row,.admin-stat-row{grid-template-columns:1fr}}
@keyframes spin{to{transform:rotate(360deg)}}
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
        <a href="admin-attendance.php" class="nav-item active">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
            Attendance Records
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
        <div><div class="topbar-sub">Admin Panel</div><div class="topbar-title">Attendance</div></div>
        <div class="topbar-user">Logged in as <span><?= $usernameEsc ?></span> <span class="role-pill <?= $adminRole ?>"><?= $roleLabels[$adminRole] ?? $adminRole ?></span></div>
    </div>

    <div class="notif-banner">
    <?php if ($isLateNow): ?>
        <div class="notif-card danger">You are late! Shift started at 8:00 AM — please clock in immediately.</div>
    <?php elseif ($showHint): ?>
        <div class="notif-card warn">Your shift starts in <strong><?= $minutesToShift ?> minute(s)</strong> at 8:00 AM.</div>
    <?php elseif ($todayRecord && ($todayRecord['punctuality'] ?? '') === 'late'): ?>
        <div class="notif-card danger">Late Clock In Recorded. You were <?= $todayRecord['minutes_late'] ?> minute(s) late today.</div>
    <?php elseif ($todayRecord && ($todayRecord['punctuality'] ?? '') === 'on_time'): ?>
        <div class="notif-card success">Great job! You clocked in on time today at <?= date('h:i A', strtotime($todayRecord['clock_in'])) ?>.</div>
    <?php endif; ?>
    </div>

    <div class="page-body">

        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px">
            <div>
                <div class="page-title">Attendance</div>
                <div class="page-sub"><?= $usernameEsc ?> — <?= date('l, F j, Y') ?></div>
            </div>
            <?php if ($isOwner): ?>
            <div class="tab-bar">
                <a href="?tab=my" class="tab-btn <?= $activeTab==='my'?'active':'' ?>">My Attendance</a>
                <a href="?tab=team" class="tab-btn <?= $activeTab==='team'?'active':'' ?>">Team Overview</a>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($activeTab === 'my'): ?>
        <!-- ══ MY ATTENDANCE ══════════════════════════════════════════ -->

        <div class="status-row" style="grid-template-columns:repeat(<?= $canServeCustomers ? 4 : 3 ?>,1fr)">
            <div class="status-card">
                <div class="status-label">Today's Status</div>
                <?php if (!$todayRecord): ?>
                    <span class="status-badge not-in">Not Clocked In</span>
                <?php elseif ($todayRecord['clock_in'] && !$todayRecord['clock_out']): ?>
                    <span class="status-badge clocked-in">● Clocked In</span>
                    <div style="font-size:12px;color:var(--muted);margin-top:4px">Since <?= date('h:i A', strtotime($todayRecord['clock_in'])) ?></div>
                <?php else: ?>
                    <span class="status-badge clocked-out">✓ Completed</span>
                <?php endif; ?>
            </div>
            <div class="status-card">
                <div class="status-label">Clock In</div>
                <div class="status-value <?= $todayRecord && $todayRecord['clock_in'] ? 'green' : '' ?>"><?= $todayRecord && $todayRecord['clock_in'] ? date('h:i A', strtotime($todayRecord['clock_in'])) : '—' ?></div>
                <?php if ($todayRecord && isset($todayRecord['punctuality'])): ?>
                <span class="punct-badge <?= $todayRecord['punctuality'] === 'on_time' ? 'on-time' : 'late' ?>" style="margin-top:4px"><?= $todayRecord['punctuality'] === 'on_time' ? '✓ On Time' : '✗ Late +' . $todayRecord['minutes_late'] . 'min' ?></span>
                <?php endif; ?>
            </div>
            <div class="status-card">
                <div class="status-label">Clock Out</div>
                <div class="status-value <?= $todayRecord && $todayRecord['clock_out'] ? 'amber' : '' ?>"><?= $todayRecord && $todayRecord['clock_out'] ? date('h:i A', strtotime($todayRecord['clock_out'])) : '—' ?></div>
            </div>
            <?php if ($canServeCustomers): ?>
            <div class="status-card">
                <div class="status-label">Customers Served Today</div>
                <div class="status-value green"><?= $customersServedToday ?></div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($canServeCustomers): ?>
        <!-- CUSTOMER SERVED (Admin & Staff only) -->
        <div class="served-card" id="served">
            <div>
                <div class="served-count-wrap"><span class="served-num" id="servedNum"><?= $customersServedToday ?></span><span class="served-label">Customers Served Today</span></div>
                <div class="served-hint" style="margin-top:4px">Tap + every time you finish serving a customer. This feeds straight into your payroll's "Customers Served" total.</div>
            </div>
            <div class="served-btns">
                <button class="served-btn minus" id="servedMinusBtn" onclick="markServed(true)" <?= (!$todayRecord || !$todayRecord['clock_in']) ? 'disabled' : '' ?>>−</button>
                <button class="served-btn plus" id="servedPlusBtn" onclick="markServed(false)" <?= (!$todayRecord || !$todayRecord['clock_in']) ? 'disabled' : '' ?>>+</button>
            </div>
        </div>
        <?php if (!$todayRecord || !$todayRecord['clock_in']): ?>
        <div style="font-size:12px;color:var(--muted);margin-top:-14px">Clock in below to start counting customers served.</div>
        <?php endif; ?>
        <?php endif; ?>

        <div class="two-col">
            <div class="panel">
                <div class="panel-header">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    <div class="panel-title">Camera / Photo</div>
                </div>
                <div class="panel-body">
                    <div class="cam-mode-tabs">
                        <button class="cam-mode-tab active" id="mode-cam-btn" onclick="switchCamMode('camera')">Camera</button>
                        <button class="cam-mode-tab" id="mode-upload-btn" onclick="switchCamMode('upload')">Upload Image</button>
                    </div>
                    <div id="camera-mode-section">
                        <div id="video-wrap">
                            <video id="att-video" autoplay playsinline muted></video>
                            <canvas id="face-overlay"></canvas>
                        </div>
                        <img id="att-preview" alt="Captured">
                        <canvas id="att-canvas" style="display:none"></canvas>
                        <div class="cam-btns">
                            <button class="btn btn-gold" id="start-cam-btn">Enable Camera</button>
                            <button class="btn btn-green" id="capture-btn" disabled>Capture</button>
                            <button class="btn btn-muted" id="retake-btn" disabled>Retake</button>
                        </div>
                        <div id="face-status"></div>
                    </div>
                    <div id="upload-mode-section" style="display:none">
                        <div class="upload-zone" id="upload-zone">
                            <input type="file" id="att-upload-input" accept="image/*" onchange="handleImageUpload(this)">
                            <div class="upload-zone-inner" id="upload-zone-inner">
                                <div class="upload-zone-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></div>
                                <div class="upload-zone-text"><strong>Tap to upload your photo</strong>JPG, PNG, WEBP — max 5MB</div>
                            </div>
                            <img id="upload-preview" alt="Upload preview">
                        </div>
                        <div class="upload-mode-note"><strong>⚠ Manual Reference Mode</strong> — no face recognition here; photo is reviewed by the owner.</div>
                        <div class="cam-btns"><button class="btn btn-muted" id="upload-retake-btn" style="display:none" onclick="clearUpload()">Change the photo</button></div>
                        <div id="upload-face-status" style="margin-top:8px;font-size:12.5px;min-height:24px"></div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <div class="panel-title">Clock In / Out</div>
                </div>
                <div class="panel-body">
                    <div class="live-clock" id="liveClock">--:-- --</div>
                    <div class="shift-hint">Shift starts at <strong>8:00 AM</strong> sharp</div>
                    <div style="height:16px"></div>
                    <div class="readiness">
                        <div class="ready-item" id="r-models">Loading face detection models...</div>
                        <div class="ready-item" id="r-camera">Camera not started</div>
                        <div class="ready-item" id="r-face"><?= $faceRegistered ? 'Face registered ✓' : 'Face not registered' ?></div>
                    </div>
                    <?php if (!$faceRegistered): ?>
                    <div style="background:rgba(201,168,76,0.06);border:1px solid var(--gold-dim);border-radius:3px;padding:12px 14px;font-size:12.5px;color:var(--muted);margin-bottom:16px">
                        Register your face to use Camera mode.
                        <button class="btn btn-gold" onclick="openFaceModal()" style="margin-top:8px;width:100%;justify-content:center">Register My Face</button>
                    </div>
                    <?php else: ?>
                    <div style="background:rgba(74,122,58,0.06);border:1px solid rgba(74,122,58,0.2);border-radius:3px;padding:10px 14px;font-size:12px;color:var(--green-lt);margin-bottom:16px;display:flex;align-items:center;gap:8px">
                        Face registered — ready to clock in/out
                        <button onclick="openFaceModal()" style="margin-left:auto;background:transparent;border:none;color:var(--muted);font-size:11px;cursor:pointer;text-decoration:underline">Update</button>
                    </div>
                    <?php endif; ?>
                    <div class="action-btns">
                        <?php $canClockIn = !$todayRecord || (!$todayRecord['clock_in']); $canClockOut = $todayRecord && $todayRecord['clock_in'] && !$todayRecord['clock_out']; ?>
                        <button class="clock-btn clock-in-btn" id="clock-in-btn" <?= !$canClockIn ? 'disabled' : '' ?>><?= !$canClockIn ? 'Already Clocked In' : 'Clock In' ?></button>
                        <button class="clock-btn clock-out-btn" id="clock-out-btn" <?= !$canClockOut ? 'disabled' : '' ?>><?= !$canClockOut ? ($todayRecord && $todayRecord['clock_out'] ? 'Already Clocked Out' : 'Clock In First') : 'Clock Out' ?></button>
                    </div>
                    <?php if ($todayRecord && $todayRecord['clock_in'] && $todayRecord['clock_out']): ?>
                    <?php $cIn=new DateTime($todayRecord['clock_in']);$cOut=new DateTime($todayRecord['clock_out']);$diff=$cIn->diff($cOut); ?>
                    <div style="margin-top:16px;padding:12px 14px;background:var(--surface);border-radius:3px;font-size:13px">
                        <div style="color:var(--muted);font-size:10px;letter-spacing:0.12em;text-transform:uppercase;margin-bottom:6px">Total Hours Today</div>
                        <div style="font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:var(--gold)"><?= $diff->h + ($diff->days * 24) ?>h <?= $diff->i ?>m</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div>
            <div class="section-hd"><div class="section-title">My Attendance History</div><div class="section-hd-line"></div></div>
            <div class="table-card">
                <table>
                    <thead><tr><th>Date</th><th>Clock In</th><th>Punctuality</th><th>Clock Out</th><th>Hours</th><?php if ($canServeCustomers): ?><th>Served</th><?php endif; ?><th>Status</th></tr></thead>
                    <tbody>
                    <?php if (empty($history)): ?>
                        <tr class="empty-row"><td colspan="<?= $canServeCustomers ? 7 : 6 ?>">No attendance records yet.</td></tr>
                    <?php else: foreach ($history as $h):
                        $hoursStr='—';$badge='incomplete';
                        if ($h['clock_in']&&$h['clock_out']){ $in=new DateTime($h['clock_in']);$out=new DateTime($h['clock_out']);$d=$in->diff($out); $hoursStr=($d->h+$d->days*24).'h '.$d->i.'m'; $badge='complete'; }
                        $punct=$h['punctuality']??null; $minsLate=(int)($h['minutes_late']??0);
                    ?><tr>
                        <td style="font-weight:500;color:var(--cream)"><?= date('M j, Y', strtotime($h['date'])) ?></td>
                        <td style="color:var(--green-lt)"><?= $h['clock_in'] ? date('h:i A',strtotime($h['clock_in'])) : '—' ?></td>
                        <td><?php if ($punct==='on_time'): ?><span class="punct-badge on-time">✓ On Time</span><?php elseif ($punct==='late'): ?><span class="punct-badge late">✗ Late +<?= $minsLate ?>min</span><?php else: ?><span class="punct-badge none">—</span><?php endif; ?></td>
                        <td style="color:var(--amber)"><?= $h['clock_out'] ? date('h:i A',strtotime($h['clock_out'])) : '—' ?></td>
                        <td style="font-family:'Cormorant Garamond',serif;font-size:15px;color:var(--gold)"><?= $hoursStr ?></td>
                        <?php if ($canServeCustomers): ?><td><?= (int)($h['customers_served'] ?? 0) ?></td><?php endif; ?>
                        <td><span class="att-badge <?= $badge ?>"><?= $badge==='complete'?'Complete':'Incomplete' ?></span></td>
                    </tr><?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($myEmployee): ?>
        <div>
            <div class="section-hd"><div class="section-title">My Payslips</div><div class="section-hd-line"></div></div>
            <div class="table-card">
                <table>
                    <thead><tr><th>Period</th><th>Days Present</th><th>Late</th><th>Hours</th><th>Customers Served</th><th>Net Pay</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php if (empty($myPayroll)): ?>
                        <tr class="empty-row"><td colspan="8">No payslips yet. Once the owner generates payroll for you, it will show up here.</td></tr>
                    <?php else: foreach ($myPayroll as $p): ?>
                        <tr>
                            <td style="color:var(--muted);font-size:12px"><?= date('M j', strtotime($p['period_start'])) ?> – <?= date('M j, Y', strtotime($p['period_end'])) ?></td>
                            <td><?= $p['days_present'] ?></td>
                            <td style="color:<?= $p['days_late']>0?'#e05a5a':'var(--muted)' ?>"><?= $p['days_late'] ?></td>
                            <td><?= $p['total_hours'] ?>h</td>
                            <td><?= (int)$p['total_customers_served'] ?></td>
                            <td style="color:var(--gold);font-family:'Cormorant Garamond',serif;font-size:15px">₱<?= number_format($p['net_pay'],2) ?></td>
                            <td><span class="status-pill <?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
                            <td><a class="payslip-link" href="payslip.php?id=<?= $p['id'] ?>">View Payslip →</a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php else: /* ══ TEAM OVERVIEW (owner) ═════════════════════════ */ ?>

        <div class="admin-stat-row">
            <div class="admin-stat green-stat"><div class="admin-stat-num"><?= $onTimeCount ?></div><div class="admin-stat-label">On Time</div></div>
            <div class="admin-stat red-stat"><div class="admin-stat-num"><?= $lateCount ?></div><div class="admin-stat-label">Late</div></div>
            <div class="admin-stat gold-stat"><div class="admin-stat-num"><?= $servedTotal ?></div><div class="admin-stat-label">Customers Served (Date Shown)</div></div>
        </div>

        <form method="GET" action="admin-attendance.php">
            <input type="hidden" name="tab" value="team">
            <div class="filter-bar">
                <div class="filter-label">Date</div>
                <input type="date" name="date" class="filter-input" value="<?= htmlspecialchars($filterDate) ?>">
                <div class="filter-label">Employee</div>
                <select name="admin_id" class="filter-input">
                    <option value="0">All</option>
                    <?php foreach ($adminList as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= $filterAdmin===(int)$a['id']?'selected':'' ?>><?= htmlspecialchars($a['username']) ?> (<?= $roleLabels[$a['role']] ?? $a['role'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="filter-btn">Filter</button>
            </div>
        </form>

        <div>
            <div class="section-hd"><div class="section-title">Team Attendance — <?= date('F j, Y', strtotime($filterDate)) ?></div><div class="section-hd-line"></div></div>
            <div class="table-card">
                <table>
                    <thead><tr><th>Name</th><th>Role</th><th>Clock In</th><th>Punctuality</th><th>Clock Out</th><th>Hours</th><th>Served</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if (empty($teamToday)): ?>
                        <tr class="empty-row"><td colspan="8">No records for this date.</td></tr>
                    <?php else: foreach ($teamToday as $s):
                        $hoursStr='—';
                        if ($s['clock_in']&&$s['clock_out']){ $in=new DateTime($s['clock_in']);$out=new DateTime($s['clock_out']);$d=$in->diff($out); $hoursStr=($d->h+$d->days*24).'h '.$d->i.'m'; }
                        $punct=$s['punctuality']??null; $minsLate=(int)($s['minutes_late']??0);
                    ?><tr>
                        <td style="font-weight:500;color:var(--cream)"><?= htmlspecialchars($s['username'] ?? '—') ?></td>
                        <td><span class="role-pill <?= $s['role'] ?? 'staff' ?>"><?= $roleLabels[$s['role'] ?? 'staff'] ?? $s['role'] ?></span></td>
                        <td style="color:var(--green-lt)"><?= $s['clock_in'] ? date('h:i A',strtotime($s['clock_in'])) : '—' ?></td>
                        <td><?php if ($punct==='on_time'): ?><span class="punct-badge on-time">✓ On Time</span><?php elseif ($punct==='late'): ?><span class="punct-badge late">✗ Late +<?= $minsLate ?>min</span><?php else: ?><span class="punct-badge none">—</span><?php endif; ?></td>
                        <td style="color:var(--amber)"><?= $s['clock_out'] ? date('h:i A',strtotime($s['clock_out'])) : '—' ?></td>
                        <td style="font-family:'Cormorant Garamond',serif;font-size:15px;color:var(--gold)"><?= $hoursStr ?></td>
                        <td><?= in_array($s['role'] ?? 'staff', ['admin','staff'], true) ? (int)($s['customers_served'] ?? 0) : '—' ?></td>
                        <td>
                            <?php if (!$s['clock_out']&&$s['clock_in']): ?><span class="att-badge" style="background:rgba(74,122,58,0.1);color:var(--green-lt)">Active</span>
                            <?php elseif ($s['clock_out']): ?><span class="att-badge complete">Done</span>
                            <?php else: ?><span class="att-badge incomplete">Pending</span><?php endif; ?>
                        </td>
                    </tr><?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <div class="section-hd"><div class="section-title">Team History — Last 7 Days</div><div class="section-hd-line"></div></div>
            <div class="table-card">
                <table>
                    <thead><tr><th>Date</th><th>Name</th><th>Role</th><th>Clock In</th><th>Punctuality</th><th>Clock Out</th><th>Hours</th><th>Served</th></tr></thead>
                    <tbody>
                    <?php if (empty($teamHistory)): ?>
                        <tr class="empty-row"><td colspan="8">No records in the last 7 days.</td></tr>
                    <?php else: foreach ($teamHistory as $s):
                        $hoursStr='—';
                        if ($s['clock_in']&&$s['clock_out']){ $in=new DateTime($s['clock_in']);$out=new DateTime($s['clock_out']);$d=$in->diff($out); $hoursStr=($d->h+$d->days*24).'h '.$d->i.'m'; }
                        $punct=$s['punctuality']??null; $minsLate=(int)($s['minutes_late']??0);
                    ?><tr>
                        <td style="color:var(--cream)"><?= date('M j', strtotime($s['date'])) ?></td>
                        <td style="font-weight:500;color:var(--cream)"><?= htmlspecialchars($s['username']??'—') ?></td>
                        <td><span class="role-pill <?= $s['role'] ?? 'staff' ?>"><?= $roleLabels[$s['role'] ?? 'staff'] ?? $s['role'] ?></span></td>
                        <td style="color:var(--green-lt)"><?= $s['clock_in']?date('h:i A',strtotime($s['clock_in'])):'—' ?></td>
                        <td><?php if ($punct==='on_time'): ?><span class="punct-badge on-time">✓ On Time</span><?php elseif ($punct==='late'): ?><span class="punct-badge late">✗ +<?= $minsLate ?>min</span><?php else: ?><span class="punct-badge none">—</span><?php endif; ?></td>
                        <td style="color:var(--amber)"><?= $s['clock_out']?date('h:i A',strtotime($s['clock_out'])):'—' ?></td>
                        <td style="font-family:'Cormorant Garamond',serif;font-size:15px;color:var(--gold)"><?= $hoursStr ?></td>
                        <td><?= in_array($s['role'] ?? 'staff', ['admin','staff'], true) ? (int)($s['customers_served'] ?? 0) : '—' ?></td>
                    </tr><?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php endif; ?>

    </div>
</div>

<div id="toast">
    <span class="toast-icon" id="toast-icon"></span>
    <div class="toast-body"><div class="toast-title" id="toast-title"></div><div class="toast-msg" id="toast-msg"></div></div>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div id="faceModal">
    <div class="faceModal-card">
        <button class="modal-close" onclick="closeFaceModal()">✕</button>
        <div class="modal-title">Register Your Face</div>
        <div class="modal-sub">Scan your face with the camera. Used for clock in/out verification.</div>
        <div id="reg-video-wrap">
            <video id="reg-video" autoplay playsinline muted></video>
            <canvas id="reg-overlay"></canvas>
        </div>
        <img id="reg-preview" alt="Face preview">
        <canvas id="reg-canvas" style="display:none"></canvas>
        <div id="reg-status" style="font-size:12.5px;color:var(--muted);margin-bottom:12px;min-height:20px"></div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button class="btn btn-green" id="reg-capture-btn" disabled>Capture Face</button>
            <button class="btn btn-gold" id="reg-save-btn" disabled>Save & Register</button>
            <button class="btn btn-muted" id="reg-retake-btn" disabled>Retake</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/face-api.js/dist/face-api.min.js"></script>
<script>
const FACE_THRESHOLD = 0.5;
const MODEL_URL = 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights';
const storedDescriptor = <?= $storedDescriptor ? $storedDescriptor : 'null' ?>;
const faceRegistered = <?= $faceRegistered ? 'true' : 'false' ?>;

function updateClock() {
    const now = new Date();
    let h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
    const ampm = h >= 12 ? 'PM' : 'AM'; h = h % 12 || 12;
    const el = document.getElementById('liveClock');
    if (el) el.textContent = String(h).padStart(2,'0')+':'+String(m).padStart(2,'0')+':'+String(s).padStart(2,'0')+' '+ampm;
    const shiftEl = document.querySelector('.shift-hint');
    if (shiftEl) {
        const shift = new Date(); shift.setHours(8,0,0,0);
        const diffMs = shift - now; const diffMin = Math.floor(diffMs / 60000);
        if (diffMin > 0 && diffMin <= 30) { shiftEl.innerHTML = `⚠️ Shift starts in <strong>${diffMin} min</strong>`; shiftEl.style.color='var(--amber)'; }
        else if (diffMs < 0 && diffMs > -3600000) { shiftEl.innerHTML = `🔴 You are <strong>${Math.abs(diffMin)} min late</strong>`; shiftEl.style.color='#e05a5a'; }
        else { shiftEl.innerHTML = 'Shift starts at <strong>8:00 AM</strong> sharp'; shiftEl.style.color='var(--muted)'; }
    }
}
setInterval(updateClock, 1000); updateClock();

let uploadState = { photo: null, ready: false };
function switchCamMode(mode) {
    const isCamera = mode === 'camera';
    document.getElementById('camera-mode-section').style.display = isCamera ? '' : 'none';
    document.getElementById('upload-mode-section').style.display = isCamera ? 'none' : '';
    document.getElementById('mode-cam-btn').classList.toggle('active', isCamera);
    document.getElementById('mode-upload-btn').classList.toggle('active', !isCamera);
    if (!isCamera) {
        if (state.stream) { state.stream.getTracks().forEach(t => t.stop()); state.stream = null; }
        setReady('r-camera', false, 'Upload mode active');
        setReady('r-face', uploadState.ready, uploadState.ready ? 'Photo uploaded — ready' : 'No photo uploaded yet');
    } else {
        clearUpload();
        setReady('r-camera', false, 'Camera not started');
        setReady('r-face', faceRegistered, faceRegistered ? 'Face registered ✓' : 'Face not registered');
        document.getElementById('att-preview').style.display = 'none';
        document.getElementById('video-wrap').style.display = 'block';
        document.getElementById('start-cam-btn').style.display = '';
        document.getElementById('start-cam-btn').disabled = false;
        document.getElementById('capture-btn').disabled = true;
        document.getElementById('retake-btn').disabled = true;
        document.getElementById('face-status').innerHTML = '';
        state.captured = false; state.faceMatched = false; state.capturedDescriptor = null; state.capturedPhoto = null;
    }
}
function handleImageUpload(input) {
    const file = input.files[0]; if (!file) return;
    if (file.size > 5*1024*1024) { showToast('File Too Large','Max 5MB.', 'error'); input.value=''; return; }
    const reader = new FileReader();
    reader.onload = function(e) {
        uploadState.photo = e.target.result; uploadState.ready = true;
        const preview = document.getElementById('upload-preview');
        preview.src = e.target.result; preview.style.display = 'block';
        document.getElementById('upload-zone-inner').style.display = 'none';
        document.getElementById('upload-zone').classList.add('has-image');
        document.getElementById('upload-retake-btn').style.display = '';
        document.getElementById('upload-face-status').innerHTML = '<div class="fs-ok">Photo ready — proceed to clock in/out below</div>';
        setReady('r-face', true, 'Photo uploaded — manual reference mode');
    };
    reader.readAsDataURL(file);
}
function clearUpload() {
    uploadState = { photo: null, ready: false };
    const preview = document.getElementById('upload-preview'); if (preview) { preview.style.display='none'; preview.src=''; }
    const inner = document.getElementById('upload-zone-inner'); if (inner) inner.style.display='';
    const zone = document.getElementById('upload-zone'); if (zone) zone.classList.remove('has-image');
    const retakeBtn = document.getElementById('upload-retake-btn'); if (retakeBtn) retakeBtn.style.display='none';
    const statusEl = document.getElementById('upload-face-status'); if (statusEl) statusEl.innerHTML='';
    const inputEl = document.getElementById('att-upload-input'); if (inputEl) inputEl.value='';
}
function isUploadMode() { const el = document.getElementById('upload-mode-section'); return el && el.style.display !== 'none'; }
function getPhotoForSubmit() { return isUploadMode() ? uploadState.photo : (state.capturedPhoto || null); }
function isReadyForClock() { if (isUploadMode()) return uploadState.ready; return state.captured && state.faceMatched; }

function setReady(id, ok, msg) {
    const el = document.getElementById(id); if (!el) return;
    el.className = 'ready-item ' + (ok ? 'ok' : 'fail');
    el.textContent = (ok ? '✓ ' : '✗ ') + msg;
}

let toastTimer;
function showToast(title, msg, type='success') {
    const toast = document.getElementById('toast');
    document.getElementById('toast-title').textContent = title;
    document.getElementById('toast-msg').textContent = msg;
    toast.className = 'show ' + type;
    clearTimeout(toastTimer); toastTimer = setTimeout(hideToast, 4000);
}
function hideToast() { document.getElementById('toast').className = ''; }

let state = { modelsLoaded:false, cameraReady:false, faceDetected:false, captured:false, capturedDescriptor:null, stream:null, detecting:false };
let regState = { stream:null, cameraReady:false, faceDetected:false, captured:false, capturedDescriptor:null };

async function loadModels() {
    try {
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
        ]);
        state.modelsLoaded = true;
        setReady('r-models', true, 'Face recognition models loaded');
        if (state.cameraReady) startDetection();
    } catch(e) { setReady('r-models', false, 'Failed to load models: ' + e.message); }
}
async function startCamera(videoEl) {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video:{ facingMode:'user', width:{ideal:640}, height:{ideal:480} }, audio:false });
        videoEl.srcObject = stream; videoEl.play(); return stream;
    } catch(e) { return null; }
}
const startCamBtn = document.getElementById('start-cam-btn');
if (startCamBtn) startCamBtn.addEventListener('click', async function() {
    this.disabled = true;
    const video = document.getElementById('att-video');
    state.stream = await startCamera(video);
    if (!state.stream) { setReady('r-camera', false, 'Camera access denied'); this.disabled = false; return; }
    this.style.display = 'none'; state.cameraReady = true;
    setReady('r-camera', true, 'Camera active');
    if (state.modelsLoaded) startDetection();
});
async function startDetection() { document.getElementById('capture-btn').disabled = false; detectLoop(); }
async function detectLoop() {
    if (state.captured || !state.cameraReady) return;
    if (state.detecting) return;
    const video = document.getElementById('att-video');
    if (!video.videoWidth) { setTimeout(detectLoop, 150); return; }
    state.detecting = true;
    try {
        const det = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize:416, scoreThreshold:0.35 })).withFaceLandmarks();
        drawOverlay('face-overlay', det, video);
        state.faceDetected = !!det;
        if (det) setReady('r-face', true, faceRegistered ? 'Face detected — tap Capture' : 'Face detected — register first');
        else setReady('r-face', false, 'No face detected');
    } catch(e) {}
    state.detecting = false;
    if (!state.captured) setTimeout(detectLoop, 120);
}
function drawOverlay(canvasId, detection, videoEl) {
    const canvas = document.getElementById(canvasId); if (!canvas) return;
    const ctx = canvas.getContext('2d');
    canvas.width = videoEl.videoWidth || videoEl.clientWidth;
    canvas.height = videoEl.videoHeight || videoEl.clientHeight;
    ctx.clearRect(0,0,canvas.width,canvas.height);
    if (!detection) return;
    const box = detection.detection.box;
    const sx = canvas.width/(videoEl.videoWidth||1), sy = canvas.height/(videoEl.videoHeight||1);
    ctx.strokeStyle = '#6aaa52'; ctx.lineWidth = 2;
    ctx.strokeRect(box.x*sx, box.y*sy, box.width*sx, box.height*sy);
}
function euclidDist(a,b){ let sum=0; for(let i=0;i<a.length;i++){const d=a[i]-b[i];sum+=d*d;} return Math.sqrt(sum); }

const captureBtn = document.getElementById('capture-btn');
if (captureBtn) captureBtn.addEventListener('click', async function() {
    if (!state.faceDetected) { showToast('No Face Detected','Please face the camera clearly.','error'); return; }
    this.disabled = true;
    const video = document.getElementById('att-video'), canvas = document.getElementById('att-canvas');
    try {
        const full = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize:416, scoreThreshold:0.35 })).withFaceLandmarks().withFaceDescriptor();
        if (!full || !full.descriptor) { this.disabled = false; showToast('Scan Failed','Could not read face.','error'); return; }
        canvas.width = video.videoWidth; canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
        document.getElementById('att-preview').src = dataUrl;
        document.getElementById('att-preview').style.display = 'block';
        document.getElementById('video-wrap').style.display = 'none';
        state.captured = true; state.capturedDescriptor = Array.from(full.descriptor); state.capturedPhoto = dataUrl;
        const faceStatus = document.getElementById('face-status');
        if (faceRegistered && storedDescriptor) {
            const dist = euclidDist(state.capturedDescriptor, storedDescriptor);
            if (dist < FACE_THRESHOLD) { state.faceMatched = true; faceStatus.innerHTML = '<div class="fs-ok">Face matched — ready to clock in/out</div>'; }
            else { state.faceMatched = false; faceStatus.innerHTML = '<div class="fs-fail">Face not recognized. Retake or use Upload Image.</div>'; }
        } else if (!faceRegistered) {
            faceStatus.innerHTML = '<div class="fs-info">Register your face first, or use the Upload Image tab.</div>';
        }
        document.getElementById('retake-btn').disabled = false;
        if (state.stream) state.stream.getTracks().forEach(t => t.stop());
    } catch(e) { this.disabled = false; showToast('Error','Face scan failed.','error'); }
});
const retakeBtn = document.getElementById('retake-btn');
if (retakeBtn) retakeBtn.addEventListener('click', function() {
    document.getElementById('att-preview').style.display = 'none';
    document.getElementById('video-wrap').style.display = 'block';
    document.getElementById('face-status').innerHTML = '';
    state.captured = false; state.faceMatched = false; state.capturedDescriptor = null; state.capturedPhoto = null;
    this.disabled = true; document.getElementById('capture-btn').disabled = true;
    document.getElementById('start-cam-btn').style.display = ''; document.getElementById('start-cam-btn').disabled = false;
    setReady('r-camera', false, 'Camera not started');
});

const clockInBtn = document.getElementById('clock-in-btn');
if (clockInBtn) clockInBtn.addEventListener('click', async function() {
    if (!isReadyForClock()) { showToast('Verification Required', isUploadMode() ? 'Upload a photo first.' : 'Capture and verify your face first.', 'error'); return; }
    this.disabled = true; this.textContent = 'Processing...';
    const fd = new FormData();
    fd.append('action','clock_in'); fd.append('photo', getPhotoForSubmit());
    if (!isUploadMode()) fd.append('descriptor', JSON.stringify(state.capturedDescriptor));
    if (isUploadMode()) fd.append('upload_mode','1');
    try {
        const res = await fetch('admin-attendance.php', { method:'POST', body:fd });
        const data = await res.json();
        if (data.success) {
            showToast(data.punctuality==='late' ? '⚠️ Clocked In — Late' : '✅ Clocked In — On Time!', data.message, data.punctuality==='late'?'warn':'success');
            setTimeout(() => location.reload(), 1800);
        } else { showToast('Clock In Failed', data.message, 'error'); this.disabled = false; this.textContent = 'Clock In'; }
    } catch(e) { showToast('Network Error','Please try again.','error'); this.disabled = false; }
});
const clockOutBtn = document.getElementById('clock-out-btn');
if (clockOutBtn) clockOutBtn.addEventListener('click', async function() {
    if (!isReadyForClock()) { showToast('Verification Required', isUploadMode() ? 'Upload a photo first.' : 'Capture and verify your face first.', 'error'); return; }
    this.disabled = true; this.textContent = 'Processing...';
    const fd = new FormData();
    fd.append('action','clock_out'); fd.append('photo', getPhotoForSubmit());
    if (isUploadMode()) fd.append('upload_mode','1');
    try {
        const res = await fetch('admin-attendance.php', { method:'POST', body:fd });
        const data = await res.json();
        if (data.success) { showToast('✅ Clocked Out', data.message + ' — ' + data.duration, 'success'); setTimeout(() => location.reload(), 1800); }
        else { showToast('Clock Out Failed', data.message, 'error'); this.disabled = false; this.textContent = 'Clock Out'; }
    } catch(e) { showToast('Network Error','Please try again.','error'); this.disabled = false; }
});

// ── CUSTOMER SERVED ──────────────────────────────────────────────────────
async function markServed(undo) {
    const plusBtn = document.getElementById('servedPlusBtn'), minusBtn = document.getElementById('servedMinusBtn');
    plusBtn.disabled = true; minusBtn.disabled = true;
    const fd = new FormData();
    fd.append('action','mark_served');
    if (undo) fd.append('undo','1');
    try {
        const res = await fetch('admin-attendance.php', { method:'POST', body:fd });
        const data = await res.json();
        if (data.success) { document.getElementById('servedNum').textContent = data.customers_served; }
        else { showToast('Cannot Update', data.message, 'error'); }
    } catch(e) { showToast('Network Error','Please try again.','error'); }
    plusBtn.disabled = false; minusBtn.disabled = false;
}

// ── FACE REGISTRATION MODAL ──────────────────────────────────────────────
function openFaceModal() { document.getElementById('faceModal').classList.add('show'); startRegCamera(); }
function closeFaceModal() {
    document.getElementById('faceModal').classList.remove('show');
    if (regState.stream) { regState.stream.getTracks().forEach(t => t.stop()); regState.stream = null; }
    regState.cameraReady = false; regState.captured = false; regState.capturedDescriptor = null;
    document.getElementById('reg-preview').style.display = 'none';
    document.getElementById('reg-video-wrap').style.display = 'block';
    document.getElementById('reg-capture-btn').disabled = true;
    document.getElementById('reg-save-btn').disabled = true;
    document.getElementById('reg-retake-btn').disabled = true;
    document.getElementById('reg-status').textContent = '';
}
document.getElementById('faceModal').addEventListener('click', function(e){ if (e.target===this) closeFaceModal(); });
async function startRegCamera() {
    const regVideo = document.getElementById('reg-video');
    document.getElementById('reg-preview').style.display = 'none';
    document.getElementById('reg-video-wrap').style.display = 'block';
    regState.captured = false; regState.capturedDescriptor = null;
    document.getElementById('reg-capture-btn').disabled = true;
    document.getElementById('reg-save-btn').disabled = true;
    document.getElementById('reg-retake-btn').disabled = true;
    document.getElementById('reg-status').textContent = 'Starting camera...';
    regState.stream = await startCamera(regVideo);
    if (!regState.stream) { document.getElementById('reg-status').textContent = 'Camera access denied.'; document.getElementById('reg-status').style.color = '#e05a5a'; return; }
    regState.cameraReady = true;
    document.getElementById('reg-status').textContent = '';
    if (state.modelsLoaded) startRegDetection();
}
async function startRegDetection() { document.getElementById('reg-capture-btn').disabled = false; regDetectLoop(); }
async function regDetectLoop() {
    if (regState.captured || !regState.cameraReady) return;
    const video = document.getElementById('reg-video');
    if (!video.videoWidth) { setTimeout(regDetectLoop, 150); return; }
    try {
        const det = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize:416, scoreThreshold:0.35 })).withFaceLandmarks();
        drawOverlay('reg-overlay', det, video);
        regState.faceDetected = !!det;
        document.getElementById('reg-status').textContent = det ? 'Face detected — ready to capture' : 'No face detected';
        document.getElementById('reg-status').style.color = det ? 'var(--green-lt)' : 'var(--muted)';
    } catch(e) {}
    if (!regState.captured) setTimeout(regDetectLoop, 120);
}
document.getElementById('reg-capture-btn').addEventListener('click', async function() {
    if (!regState.faceDetected) { showToast('No Face','No face detected.','error'); return; }
    this.disabled = true;
    const video = document.getElementById('reg-video'), canvas = document.getElementById('reg-canvas');
    try {
        const full = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize:416, scoreThreshold:0.35 })).withFaceLandmarks().withFaceDescriptor();
        if (!full || !full.descriptor) { this.disabled = false; showToast('Scan Failed','Try again.','error'); return; }
        canvas.width = video.videoWidth; canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        document.getElementById('reg-preview').src = canvas.toDataURL('image/jpeg', 0.8);
        document.getElementById('reg-preview').style.display = 'block';
        document.getElementById('reg-video-wrap').style.display = 'none';
        regState.captured = true; regState.capturedDescriptor = Array.from(full.descriptor);
        document.getElementById('reg-status').textContent = 'Face captured — tap Save to register';
        document.getElementById('reg-status').style.color = 'var(--green-lt)';
        document.getElementById('reg-save-btn').disabled = false;
        document.getElementById('reg-retake-btn').disabled = false;
        if (regState.stream) regState.stream.getTracks().forEach(t => t.stop());
    } catch(e) { this.disabled = false; showToast('Error','Face scan failed.','error'); }
});
document.getElementById('reg-retake-btn').addEventListener('click', async function() {
    document.getElementById('reg-preview').style.display = 'none';
    document.getElementById('reg-video-wrap').style.display = 'block';
    regState.captured = false; regState.capturedDescriptor = null;
    document.getElementById('reg-save-btn').disabled = true;
    document.getElementById('reg-retake-btn').disabled = true;
    document.getElementById('reg-capture-btn').disabled = true;
    document.getElementById('reg-status').textContent = '';
    await startRegCamera();
});
document.getElementById('reg-save-btn').addEventListener('click', async function() {
    if (!regState.capturedDescriptor) { showToast('No Face','No face captured.','error'); return; }
    const origHTML = this.innerHTML; this.disabled = true; this.textContent = 'Saving...';
    const fd = new FormData();
    fd.append('action','register_face'); fd.append('descriptor', JSON.stringify(regState.capturedDescriptor));
    try {
        const res = await fetch('admin-attendance.php', { method:'POST', body:fd });
        const data = await res.json();
        if (data.success) { showToast('Face Registered!','You can now clock in/out with face recognition.','success'); closeFaceModal(); setTimeout(() => location.reload(), 1200); }
        else { showToast('Failed', data.message, 'error'); this.disabled = false; this.innerHTML = origHTML; }
    } catch(e) { showToast('Network Error','Please try again.','error'); this.disabled = false; this.innerHTML = origHTML; }
});

loadModels();
</script>
</body>
</html>
