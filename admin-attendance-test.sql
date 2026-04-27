<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION["user"])) { header("Location: log-in.php"); exit(); }
include 'config.php';

// Session validation
$uid   = (int)($_SESSION["user"]["id"] ?? 0);
$uname = $conn->real_escape_string($_SESSION["user"]["username"] ?? '');
$chk   = $conn->query("SELECT id, role FROM users WHERE id = $uid AND username = '$uname' LIMIT 1");
if (!$chk || $chk->num_rows === 0) {
    session_unset(); session_destroy();
    header("Location: log-in.php"); exit();
}
$userRow = $chk->fetch_assoc();
$isAdmin = (($userRow['role'] ?? '') === 'admin');

// Ensure tables exist
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");
$conn->query("ALTER TABLE attendance ADD COLUMN IF NOT EXISTS punctuality VARCHAR(20) DEFAULT NULL");
$conn->query("ALTER TABLE attendance ADD COLUMN IF NOT EXISTS minutes_late INT DEFAULT 0");
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

// Constants
define('SHIFT_START_HOUR', 8);
define('SHIFT_START_MIN', 0);

function getPunctuality($clockInTime) {
    $clockIn = new DateTime($clockInTime);
    $shiftStart = new DateTime($clockIn->format('Y-m-d') . ' 08:00:00');
    if ($clockIn <= $shiftStart) {
        return ['status' => 'on_time', 'minutes_late' => 0];
    }
    $diff = $shiftStart->diff($clockIn);
    $minsLate = ($diff->h * 60) + $diff->i;
    return ['status' => 'late', 'minutes_late' => $minsLate];
}

// ── HANDLE AJAX ACTIONS ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'register_face') {
        $descriptor = trim($_POST['descriptor'] ?? '');
        if (empty($descriptor)) { echo json_encode(['success'=>false,'message'=>'No face descriptor provided.']); exit(); }
        $existing = $conn->query("SELECT id FROM face_descriptors WHERE user_id = $uid AND type = 'user' LIMIT 1");
        if ($existing && $existing->num_rows > 0) {
            $conn->query("UPDATE face_descriptors SET descriptor = '$descriptor', updated_at = NOW() WHERE user_id = $uid AND type = 'user'");
        } else {
            $conn->query("INSERT INTO face_descriptors (user_id, type, descriptor) VALUES ($uid, 'user', '$descriptor')");
        }
        echo json_encode(['success'=>true,'message'=>'Face registered successfully!']);
        exit();
    }

    if ($action === 'clock_in') {
        $descriptor = trim($_POST['descriptor'] ?? '');
        $photo      = $_POST['photo'] ?? '';
        $today      = date('Y-m-d');
        $existing = $conn->query("SELECT id, clock_in FROM attendance WHERE user_id = $uid AND date = '$today' AND type = 'user' LIMIT 1");
        if ($existing && $existing->num_rows > 0) {
            $row = $existing->fetch_assoc();
            if ($row['clock_in']) { echo json_encode(['success'=>false,'message'=>'Already clocked in today at '.date('h:i A',strtotime($row['clock_in']))]); exit(); }
        }
        $faceRec = $conn->query("SELECT descriptor FROM face_descriptors WHERE user_id = $uid AND type = 'user' LIMIT 1");
        if (!$faceRec || $faceRec->num_rows === 0) { echo json_encode(['success'=>false,'message'=>'No registered face found. Please register your face first.']); exit(); }

        $now        = date('Y-m-d H:i:s');
        $punct      = getPunctuality($now);
        $pStatus    = $punct['status'];
        $minsLate   = $punct['minutes_late'];
        $photoEsc   = $conn->real_escape_string(substr($photo, 0, 65535));

        $conn->query("INSERT INTO attendance (user_id, type, clock_in, date, photo_in, status, punctuality, minutes_late)
                      VALUES ($uid, 'user', '$now', '$today', '$photoEsc', 'present', '$pStatus', $minsLate)");

        $msg = $pStatus === 'late'
            ? 'Clocked In at '.date('h:i A').' — You are '.$minsLate.' minute(s) late.'
            : 'Clocked In at '.date('h:i A').' — On Time! Great job!';

        echo json_encode(['success'=>true,'message'=>$msg,'time'=>date('h:i A'),'punctuality'=>$pStatus,'minutes_late'=>$minsLate]);
        exit();
    }

    if ($action === 'clock_out') {
        $photo  = $_POST['photo'] ?? '';
        $today  = date('Y-m-d');
        $existing = $conn->query("SELECT id, clock_in FROM attendance WHERE user_id = $uid AND date = '$today' AND type = 'user' AND clock_out IS NULL LIMIT 1");
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

    echo json_encode(['success'=>false,'message'=>'Unknown action.']);
    exit();
}

// ── TODAY STATUS ──────────────────────────────────────────────────────────
$today       = date('Y-m-d');
$todayRecord = null;
$res = $conn->query("SELECT * FROM attendance WHERE user_id = $uid AND date = '$today' AND type = 'user' LIMIT 1");
if ($res && $res->num_rows > 0) $todayRecord = $res->fetch_assoc();

// ── FACE REGISTERED ───────────────────────────────────────────────────────
$faceRegistered = false;
$storedDescriptor = null;
$faceRes = $conn->query("SELECT descriptor FROM face_descriptors WHERE user_id = $uid AND type = 'user' LIMIT 1");
if ($faceRes && $faceRes->num_rows > 0) {
    $faceRegistered   = true;
    $storedDescriptor = $faceRes->fetch_assoc()['descriptor'];
}

// ── ATTENDANCE HISTORY (user) ─────────────────────────────────────────────
$history = $conn->query("
    SELECT * FROM attendance
    WHERE user_id = $uid AND type = 'user'
    ORDER BY date DESC, clock_in DESC
    LIMIT 30
")->fetch_all(MYSQLI_ASSOC);

// ── ADMIN: ALL STAFF ATTENDANCE ───────────────────────────────────────────
$adminStats = [];
$adminHistory = [];
if ($isAdmin) {
    // Today's summary
    $aSummary = $conn->query("
        SELECT a.*, u.username, u.email
        FROM attendance a
        LEFT JOIN users u ON u.id = a.user_id
        WHERE a.date = '$today' AND a.type = 'user'
        ORDER BY a.clock_in ASC
    ");
    if ($aSummary) $adminStats = $aSummary->fetch_all(MYSQLI_ASSOC);

    // Full history (last 7 days)
    $aHistory = $conn->query("
        SELECT a.*, u.username
        FROM attendance a
        LEFT JOIN users u ON u.id = a.user_id
        WHERE a.type = 'user' AND a.date >= DATE_SUB('$today', INTERVAL 7 DAY)
        ORDER BY a.date DESC, a.clock_in ASC
        LIMIT 100
    ");
    if ($aHistory) $adminHistory = $aHistory->fetch_all(MYSQLI_ASSOC);

    // Count totals for today
    $totalStaff   = count($adminStats);
    $onTimeCount  = count(array_filter($adminStats, fn($r) => $r['punctuality'] === 'on_time'));
    $lateCount    = count(array_filter($adminStats, fn($r) => $r['punctuality'] === 'late'));
    $absentCount  = 0; // can be computed from users table if needed
}

// ── NOTIFICATION HINT ─────────────────────────────────────────────────────
$now = new DateTime();
$shiftStart = new DateTime(date('Y-m-d').' 08:00:00');
$minutesToShift = (int)(($shiftStart->getTimestamp() - $now->getTimestamp()) / 60);
$showHint = !$todayRecord && $minutesToShift <= 30 && $minutesToShift > 0;
$isLateNow = !$todayRecord && $now > $shiftStart;

$username = htmlspecialchars($_SESSION['user']['username'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance — AyosCoffeeNegosyo</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;
    --gold:#c9a84c;--gold-dim:#8a6f2e;--green:#4a7a3a;--green-lt:#6aaa52;
    --cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8;
    --red:#c0392b;--red-pale:rgba(192,57,43,0.1);--amber:#d4820a;
}
html{scroll-behavior:smooth}
body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 10% 0%,rgba(201,168,76,0.06) 0%,transparent 55%),radial-gradient(ellipse 50% 70% at 90% 100%,rgba(74,122,58,0.07) 0%,transparent 55%);pointer-events:none;z-index:0}

header{position:sticky;top:0;z-index:100;background:rgba(11,11,9,0.88);backdrop-filter:blur(18px);border-bottom:1px solid var(--border)}
.header-inner{max-width:1100px;margin:0 auto;padding:0 32px;height:68px;display:flex;align-items:center;justify-content:space-between}
.brand{display:flex;align-items:center;gap:12px;text-decoration:none}
.brand-icon{width:36px;height:36px;border:1px solid var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center}
.brand-name{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream);letter-spacing:0.04em}
.brand-name span{color:var(--gold)}
.header-right{display:flex;align-items:center;gap:10px}
.back-btn{display:flex;align-items:center;gap:8px;padding:8px 16px;border:1px solid var(--border);border-radius:3px;font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted);text-decoration:none;transition:all 0.2s}
.back-btn:hover{border-color:var(--gold-dim);color:var(--gold)}

/* NOTIFICATION BANNER */
.notif-banner{position:relative;z-index:10;max-width:1100px;margin:20px auto 0;padding:0 32px}
.notif-card{border-radius:6px;padding:14px 18px;display:flex;align-items:center;gap:14px;font-size:13.5px;animation:slideIn 0.4s ease}
.notif-card.warn{background:rgba(212,130,10,0.1);border:1px solid rgba(212,130,10,0.35);color:var(--amber)}
.notif-card.danger{background:rgba(192,57,43,0.1);border:1px solid rgba(192,57,43,0.35);color:#e05a5a}
.notif-card.success{background:rgba(74,122,58,0.1);border:1px solid rgba(74,122,58,0.3);color:var(--green-lt)}
.notif-pulse{width:10px;height:10px;border-radius:50%;flex-shrink:0;animation:pulse 1.5s ease infinite}
.notif-card.warn .notif-pulse{background:var(--amber);box-shadow:0 0 0 0 rgba(212,130,10,0.5)}
.notif-card.danger .notif-pulse{background:#e05a5a;box-shadow:0 0 0 0 rgba(192,57,43,0.5)}
.notif-card.success .notif-pulse{background:var(--green-lt);box-shadow:0 0 0 0 rgba(106,170,82,0.5)}
.notif-text strong{font-weight:600}
@keyframes pulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.3);opacity:0.7}}
@keyframes slideIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}

.page-body{max-width:1100px;margin:0 auto;padding:28px 32px 60px;display:flex;flex-direction:column;gap:24px;position:relative;z-index:1}
.page-title{font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:700;color:var(--cream)}
.page-title em{font-style:italic;color:var(--gold)}
.page-sub{font-size:13px;color:var(--muted);margin-top:4px}

/* TAB SWITCHER (admin) */
.tab-bar{display:flex;gap:4px;background:var(--surface);border:1px solid var(--border);border-radius:6px;padding:4px;width:fit-content}
.tab-btn{padding:9px 22px;border-radius:4px;border:none;background:transparent;font-family:'Jost',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted);cursor:pointer;transition:all 0.2s}
.tab-btn.active{background:var(--card);color:var(--gold);border:1px solid var(--border)}

/* STATUS CARDS */
.status-row{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.status-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:20px 22px;display:flex;flex-direction:column;gap:6px;position:relative;overflow:hidden;transition:border-color 0.2s}
.status-card:hover{border-color:var(--gold-dim)}
.status-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--gold-dim),transparent)}
.status-label{font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:var(--muted)}
.status-value{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--cream)}
.status-value.green{color:var(--green-lt)}
.status-value.amber{color:var(--amber)}
.status-value.red{color:var(--red)}
.status-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:3px;font-size:11px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase}
.status-badge.clocked-in{background:rgba(74,122,58,0.12);color:var(--green-lt)}
.status-badge.clocked-out{background:rgba(201,168,76,0.08);color:var(--gold)}
.status-badge.not-in{background:var(--red-pale);color:#e05a5a}

/* ADMIN SUMMARY STATS */
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

/* PUNCTUALITY BADGE */
.punct-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:3px;font-size:10px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase}
.punct-badge.on-time{background:rgba(74,122,58,0.12);color:var(--green-lt)}
.punct-badge.late{background:var(--red-pale);color:#e05a5a}
.punct-badge.none{background:rgba(107,107,88,0.1);color:var(--muted)}

/* CAMERA + CONTROLS */
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
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:3px;font-family:'Jost',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;cursor:pointer;transition:all 0.2s;border:none}
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
.clock-btn{display:flex;align-items:center;justify-content:center;gap:10px;padding:14px;border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;cursor:pointer;border:none;transition:all 0.2s;position:relative;overflow:hidden}
.clock-in-btn{background:var(--green);color:#fff}.clock-in-btn:hover:not(:disabled){background:var(--green-lt)}
.clock-out-btn{background:#8b2e2e;color:#fff}.clock-out-btn:hover:not(:disabled){background:var(--red)}
.clock-btn:disabled{opacity:0.35;cursor:not-allowed}
.clock-btn::after{content:'';position:absolute;top:0;left:-100%;width:60%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.1),transparent);transition:left 0.4s ease}
.clock-btn:hover:not(:disabled)::after{left:160%}

#face-status{margin-top:10px;font-size:12.5px;min-height:32px}
.fs-ok{color:var(--green-lt);display:flex;align-items:center;gap:6px}
.fs-fail{color:#e05a5a;display:flex;align-items:center;gap:6px}
.fs-info{color:var(--gold);display:flex;align-items:center;gap:6px}

/* SECTION HEADER */
.section-hd{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.section-hd-line{flex:1;height:1px;background:linear-gradient(90deg,var(--border),transparent)}
.section-title{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--cream)}

/* TABLE */
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

/* LIVE CLOCK */
.live-clock{font-family:'Cormorant Garamond',serif;font-size:48px;font-weight:700;color:var(--gold);letter-spacing:0.04em;text-align:center;padding:16px 0 8px}
.shift-hint{text-align:center;font-size:12px;color:var(--muted);margin-bottom:4px}
.shift-hint strong{color:var(--cream)}

/* TOAST */
#toast{position:fixed;bottom:28px;right:28px;z-index:999;background:var(--card);border:1px solid var(--gold-dim);border-radius:6px;padding:16px 20px;display:flex;align-items:flex-start;gap:14px;font-size:14px;color:var(--cream);box-shadow:0 8px 32px rgba(0,0,0,0.6);transform:translateY(30px);opacity:0;transition:all 0.35s cubic-bezier(0.4,0,0.2,1);pointer-events:none;max-width:380px}
#toast.show{transform:translateY(0);opacity:1;pointer-events:all}
#toast.success{border-color:var(--green)}
#toast.error{border-color:var(--red)}
#toast.warn{border-color:var(--amber)}
.toast-icon{flex-shrink:0;margin-top:1px}
.toast-body{}
.toast-title{font-weight:600;font-size:13px;margin-bottom:3px}
.toast-msg{font-size:12.5px;color:var(--muted);line-height:1.5}
.toast-close{margin-left:auto;flex-shrink:0;background:transparent;border:none;color:var(--muted);cursor:pointer;padding:0;line-height:1}
.toast-close:hover{color:var(--cream)}

/* FACE MODAL */
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
.modal-close{position:absolute;top:12px;right:12px;width:28px;height:28px;border:1px solid var(--border);border-radius:50%;background:transparent;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s}
.modal-close:hover{border-color:var(--gold-dim);color:var(--cream)}

/* NOTIF PERMISSION STRIP */
.notif-perm-strip{background:rgba(201,168,76,0.06);border:1px solid var(--gold-dim);border-radius:4px;padding:10px 14px;font-size:12px;color:var(--muted);display:flex;align-items:center;gap:10px;margin-bottom:16px}
.notif-perm-strip button{margin-left:auto;background:transparent;border:1px solid var(--gold-dim);color:var(--gold);border-radius:3px;padding:5px 12px;font-size:11px;font-family:'Jost',sans-serif;cursor:pointer;font-weight:500;letter-spacing:0.06em;text-transform:uppercase;white-space:nowrap}
.notif-perm-strip button:hover{background:rgba(201,168,76,0.1)}
#notifPermStrip{display:none}

@media(max-width:768px){
    .page-body{padding:20px 16px 48px}
    .header-inner{padding:0 16px}
    .two-col{grid-template-columns:1fr}
    .status-row,.admin-stat-row{grid-template-columns:1fr 1fr}
    .notif-banner{padding:0 16px}
}
@media(max-width:480px){.status-row,.admin-stat-row{grid-template-columns:1fr}}
</style>
</head>
<body>

<header>
    <div class="header-inner">
        <a href="index.php" class="brand">
            <div class="brand-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.5"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
            </div>
            <span class="brand-name">My <span>AyosCoffeeNegosyo</span></span>
        </a>
        <div class="header-right">
            <a href="index.php" class="back-btn">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back to Menu
            </a>
        </div>
    </div>
</header>

<!-- IN-APP NOTIFICATION BANNER -->
<div class="notif-banner">
<?php if ($isLateNow): ?>
    <div class="notif-card danger">
        <div class="notif-pulse"></div>
        <div class="notif-text">
            <strong>You are late!</strong> Shift started at 8:00 AM. Please clock in immediately.
        </div>
    </div>
<?php elseif ($showHint): ?>
    <div class="notif-card warn">
        <div class="notif-pulse"></div>
        <div class="notif-text">
            <strong>Heads up!</strong> Your shift starts in <strong><?= $minutesToShift ?> minute(s)</strong> at 8:00 AM. Get ready to clock in!
        </div>
    </div>
<?php elseif ($todayRecord && ($todayRecord['punctuality'] ?? '') === 'late'): ?>
    <div class="notif-card danger">
        <div class="notif-pulse"></div>
        <div class="notif-text">
            <strong>Late Clock In Recorded.</strong> You were <?= $todayRecord['minutes_late'] ?> minute(s) late today.
        </div>
    </div>
<?php elseif ($todayRecord && ($todayRecord['punctuality'] ?? '') === 'on_time'): ?>
    <div class="notif-card success">
        <div class="notif-pulse"></div>
        <div class="notif-text">
            <strong>Great job!</strong> You clocked in on time today at <?= date('h:i A', strtotime($todayRecord['clock_in'])) ?>.
        </div>
    </div>
<?php endif; ?>
</div>

<div class="page-body">

    <!-- TITLE + TAB BAR -->
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <div class="page-title">Your <em>Attendance</em></div>
            <div class="page-sub">Welcome, <?= $username ?> — <?= date('l, F j, Y') ?></div>
        </div>
        <?php if ($isAdmin): ?>
        <div class="tab-bar">
            <button class="tab-btn active" id="tabMyAtt" onclick="switchTab('my')">My Attendance</button>
            <button class="tab-btn" id="tabAdminAtt" onclick="switchTab('admin')">
                Staff Overview
                <?php if ($lateCount > 0): ?>
                <span style="background:var(--red);color:#fff;border-radius:10px;padding:1px 6px;font-size:10px;margin-left:4px"><?= $lateCount ?></span>
                <?php endif; ?>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- BROWSER NOTIFICATION PERMISSION STRIP -->
    <div id="notifPermStrip" class="notif-perm-strip">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        Enable browser notifications to get shift reminders even when this tab is in the background.
        <button onclick="requestNotifPermission()">Enable</button>
    </div>

    <!-- ══ MY ATTENDANCE TAB ══════════════════════════════════════════════ -->
    <div id="tabMyContent">

        <!-- STATUS CARDS -->
        <div class="status-row" style="margin-bottom:24px">
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
                <div class="status-value <?= $todayRecord && $todayRecord['clock_in'] ? 'green' : '' ?>">
                    <?= $todayRecord && $todayRecord['clock_in'] ? date('h:i A', strtotime($todayRecord['clock_in'])) : '—' ?>
                </div>
                <?php if ($todayRecord && isset($todayRecord['punctuality'])): ?>
                <span class="punct-badge <?= $todayRecord['punctuality'] === 'on_time' ? 'on-time' : 'late' ?>" style="margin-top:4px">
                    <?= $todayRecord['punctuality'] === 'on_time' ? '✓ On Time' : '✗ Late +' . $todayRecord['minutes_late'] . 'min' ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="status-card">
                <div class="status-label">Clock Out</div>
                <div class="status-value <?= $todayRecord && $todayRecord['clock_out'] ? 'amber' : '' ?>">
                    <?= $todayRecord && $todayRecord['clock_out'] ? date('h:i A', strtotime($todayRecord['clock_out'])) : '—' ?>
                </div>
            </div>
        </div>

        <!-- CAMERA + CONTROLS -->
        <div class="two-col">
            <div class="panel">
                <div class="panel-header">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    <div class="panel-title">Camera</div>
                </div>
                <div class="panel-body">
                    <div id="video-wrap">
                        <video id="att-video" autoplay playsinline muted></video>
                        <canvas id="face-overlay"></canvas>
                    </div>
                    <img id="att-preview" alt="Captured">
                    <canvas id="att-canvas" style="display:none"></canvas>
                    <div class="cam-btns">
                        <button class="btn btn-gold" id="start-cam-btn">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                            Enable Camera
                        </button>
                        <button class="btn btn-green" id="capture-btn" disabled>
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/></svg>
                            Capture
                        </button>
                        <button class="btn btn-muted" id="retake-btn" disabled>
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.54"/></svg>
                            Retake
                        </button>
                    </div>
                    <div id="face-status"></div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <div class="panel-title">Clock In / Out</div>
                </div>
                <div class="panel-body">

                    <!-- Live clock -->
                    <div class="live-clock" id="liveClock">--:-- --</div>
                    <div class="shift-hint">Shift starts at <strong>8:00 AM</strong> sharp</div>
                    <div style="height:16px"></div>

                    <div class="readiness">
                        <div class="ready-item" id="r-models">
                            <span class="ready-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                            Loading face detection models...
                        </div>
                        <div class="ready-item" id="r-camera">
                            <span class="ready-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></span>
                            Camera not started
                        </div>
                        <div class="ready-item" id="r-face">
                            <span class="ready-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                            <?= $faceRegistered ? 'Face registered ✓' : 'Face not registered' ?>
                        </div>
                    </div>

                    <?php if (!$faceRegistered): ?>
                    <div style="background:rgba(201,168,76,0.06);border:1px solid var(--gold-dim);border-radius:3px;padding:12px 14px;font-size:12.5px;color:var(--muted);margin-bottom:16px;line-height:1.6">
                        You need to register your face before clocking in.
                        <button class="btn btn-gold" onclick="openFaceModal()" style="margin-top:8px;width:100%;justify-content:center">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Register My Face
                        </button>
                    </div>
                    <?php else: ?>
                    <div style="background:rgba(74,122,58,0.06);border:1px solid rgba(74,122,58,0.2);border-radius:3px;padding:10px 14px;font-size:12px;color:var(--green-lt);margin-bottom:16px;display:flex;align-items:center;gap:8px">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        Face registered — ready to clock in/out
                        <button onclick="openFaceModal()" style="margin-left:auto;background:transparent;border:none;color:var(--muted);font-size:11px;cursor:pointer;text-decoration:underline">Update</button>
                    </div>
                    <?php endif; ?>

                    <div class="action-btns">
                        <?php
                        $canClockIn  = !$todayRecord || (!$todayRecord['clock_in']);
                        $canClockOut = $todayRecord && $todayRecord['clock_in'] && !$todayRecord['clock_out'];
                        ?>
                        <button class="clock-btn clock-in-btn" id="clock-in-btn" <?= (!$canClockIn || !$faceRegistered) ? 'disabled' : '' ?>>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 11 21 7 17 3"/><line x1="21" y1="7" x2="9" y2="7"/><polyline points="7 13 3 17 7 21"/><line x1="3" y1="17" x2="15" y2="17"/></svg>
                            <?= !$canClockIn ? 'Already Clocked In' : 'Clock In' ?>
                        </button>
                        <button class="clock-btn clock-out-btn" id="clock-out-btn" <?= !$canClockOut ? 'disabled' : '' ?>>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="7 11 3 7 7 3"/><line x1="3" y1="7" x2="15" y2="7"/><polyline points="17 13 21 17 17 21"/><line x1="21" y1="17" x2="9" y2="17"/></svg>
                            <?= !$canClockOut ? ($todayRecord && $todayRecord['clock_out'] ? 'Already Clocked Out' : 'Clock In First') : 'Clock Out' ?>
                        </button>
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

        <!-- MY HISTORY -->
        <div>
            <div class="section-hd">
                <div class="section-title">My Attendance History</div>
                <div class="section-hd-line"></div>
            </div>
            <div class="table-card">
                <table>
                    <thead><tr><th>Date</th><th>Clock In</th><th>Punctuality</th><th>Clock Out</th><th>Hours</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if (empty($history)): ?>
                        <tr class="empty-row"><td colspan="6">No attendance records yet.</td></tr>
                    <?php else: foreach ($history as $h):
                        $hoursStr='—';$badge='incomplete';
                        if ($h['clock_in']&&$h['clock_out']){
                            $in=new DateTime($h['clock_in']);$out=new DateTime($h['clock_out']);$d=$in->diff($out);
                            $hoursStr=($d->h+$d->days*24).'h '.$d->i.'m';$badge='complete';
                        }
                        $punct = $h['punctuality'] ?? null;
                        $minsLate = (int)($h['minutes_late'] ?? 0);
                    ?><tr>
                        <td style="font-weight:500;color:var(--cream)"><?= date('M j, Y', strtotime($h['date'])) ?></td>
                        <td style="color:var(--green-lt)"><?= $h['clock_in'] ? date('h:i A',strtotime($h['clock_in'])) : '—' ?></td>
                        <td>
                            <?php if ($punct === 'on_time'): ?>
                                <span class="punct-badge on-time">✓ On Time</span>
                            <?php elseif ($punct === 'late'): ?>
                                <span class="punct-badge late">✗ Late +<?= $minsLate ?>min</span>
                            <?php else: ?>
                                <span class="punct-badge none">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--amber)"><?= $h['clock_out'] ? date('h:i A',strtotime($h['clock_out'])) : '—' ?></td>
                        <td style="font-family:'Cormorant Garamond',serif;font-size:15px;color:var(--gold)"><?= $hoursStr ?></td>
                        <td><span class="att-badge <?= $badge ?>"><?= $badge==='complete'?'Complete':'Incomplete' ?></span></td>
                    </tr><?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- end tabMyContent -->

    <!-- ══ ADMIN TAB ═════════════════════════════════════════════════════ -->
    <?php if ($isAdmin): ?>
    <div id="tabAdminContent" style="display:none">

        <!-- ADMIN SUMMARY STATS -->
        <div class="admin-stat-row" style="margin-bottom:24px">
            <div class="admin-stat green-stat">
                <div class="admin-stat-num"><?= $onTimeCount ?></div>
                <div class="admin-stat-label">On Time Today</div>
            </div>
            <div class="admin-stat red-stat">
                <div class="admin-stat-num"><?= $lateCount ?></div>
                <div class="admin-stat-label">Late Today</div>
                <?php if ($lateCount > 0): ?>
                <div style="font-size:11px;color:var(--muted);margin-top:6px">Needs attention</div>
                <?php endif; ?>
            </div>
            <div class="admin-stat gold-stat">
                <div class="admin-stat-num"><?= $totalStaff ?></div>
                <div class="admin-stat-label">Total Clocked In</div>
            </div>
        </div>

        <!-- TODAY'S STAFF LIST -->
        <div>
            <div class="section-hd">
                <div class="section-title">Today's Staff — <?= date('F j, Y') ?></div>
                <div class="section-hd-line"></div>
            </div>
            <div class="table-card" style="margin-bottom:24px">
                <table>
                    <thead><tr><th>Staff</th><th>Clock In</th><th>Punctuality</th><th>Clock Out</th><th>Hours</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if (empty($adminStats)): ?>
                        <tr class="empty-row"><td colspan="6">No staff have clocked in today yet.</td></tr>
                    <?php else: foreach ($adminStats as $s):
                        $hoursStr='—';
                        if ($s['clock_in']&&$s['clock_out']){
                            $in=new DateTime($s['clock_in']);$out=new DateTime($s['clock_out']);$d=$in->diff($out);
                            $hoursStr=($d->h+$d->days*24).'h '.$d->i.'m';
                        }
                        $punct=$s['punctuality']??null;$minsLate=(int)($s['minutes_late']??0);
                        $rowBg = $punct==='late' ? 'background:rgba(192,57,43,0.04)' : '';
                    ?><tr style="<?= $rowBg ?>">
                        <td style="font-weight:500;color:var(--cream)"><?= htmlspecialchars($s['username'] ?? '—') ?></td>
                        <td style="color:var(--green-lt)"><?= $s['clock_in'] ? date('h:i A',strtotime($s['clock_in'])) : '—' ?></td>
                        <td>
                            <?php if ($punct==='on_time'): ?>
                                <span class="punct-badge on-time">✓ On Time</span>
                            <?php elseif ($punct==='late'): ?>
                                <span class="punct-badge late">✗ Late +<?= $minsLate ?>min</span>
                            <?php else: ?><span class="punct-badge none">—</span><?php endif; ?>
                        </td>
                        <td style="color:var(--amber)"><?= $s['clock_out'] ? date('h:i A',strtotime($s['clock_out'])) : '—' ?></td>
                        <td style="font-family:'Cormorant Garamond',serif;font-size:15px;color:var(--gold)"><?= $hoursStr ?></td>
                        <td>
                            <?php if (!$s['clock_out']&&$s['clock_in']): ?>
                                <span class="att-badge" style="background:rgba(74,122,58,0.1);color:var(--green-lt)">Active</span>
                            <?php elseif ($s['clock_out']): ?>
                                <span class="att-badge complete">Done</span>
                            <?php else: ?>
                                <span class="att-badge incomplete">Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr><?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 7-DAY HISTORY -->
        <div>
            <div class="section-hd">
                <div class="section-title">Staff History — Last 7 Days</div>
                <div class="section-hd-line"></div>
            </div>
            <div class="table-card">
                <table>
                    <thead><tr><th>Date</th><th>Staff</th><th>Clock In</th><th>Punctuality</th><th>Clock Out</th><th>Hours</th></tr></thead>
                    <tbody>
                    <?php if (empty($adminHistory)): ?>
                        <tr class="empty-row"><td colspan="6">No records in the last 7 days.</td></tr>
                    <?php else: foreach ($adminHistory as $s):
                        $hoursStr='—';
                        if ($s['clock_in']&&$s['clock_out']){
                            $in=new DateTime($s['clock_in']);$out=new DateTime($s['clock_out']);$d=$in->diff($out);
                            $hoursStr=($d->h+$d->days*24).'h '.$d->i.'m';
                        }
                        $punct=$s['punctuality']??null;$minsLate=(int)($s['minutes_late']??0);
                    ?><tr>
                        <td style="color:var(--cream)"><?= date('M j', strtotime($s['date'])) ?></td>
                        <td style="font-weight:500;color:var(--cream)"><?= htmlspecialchars($s['username']??'—') ?></td>
                        <td style="color:var(--green-lt)"><?= $s['clock_in']?date('h:i A',strtotime($s['clock_in'])):'—' ?></td>
                        <td>
                            <?php if ($punct==='on_time'): ?>
                                <span class="punct-badge on-time">✓ On Time</span>
                            <?php elseif ($punct==='late'): ?>
                                <span class="punct-badge late">✗ +<?= $minsLate ?>min</span>
                            <?php else: ?><span class="punct-badge none">—</span><?php endif; ?>
                        </td>
                        <td style="color:var(--amber)"><?= $s['clock_out']?date('h:i A',strtotime($s['clock_out'])):'—' ?></td>
                        <td style="font-family:'Cormorant Garamond',serif;font-size:15px;color:var(--gold)"><?= $hoursStr ?></td>
                    </tr><?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    <?php endif; ?>

</div><!-- end page-body -->

<!-- TOAST -->
<div id="toast">
    <span class="toast-icon" id="toast-icon"></span>
    <div class="toast-body">
        <div class="toast-title" id="toast-title"></div>
        <div class="toast-msg" id="toast-msg"></div>
    </div>
    <button class="toast-close" onclick="hideToast()">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
</div>

<!-- FACE MODAL -->
<div id="faceModal">
    <div class="faceModal-card">
        <button class="modal-close" onclick="closeFaceModal()">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div class="modal-title">Register Your Face</div>
        <div class="modal-sub">Position your face clearly in the frame and tap Capture. This is used for clock in/out verification.</div>
        <div id="reg-video-wrap">
            <video id="reg-video" autoplay playsinline muted></video>
            <canvas id="reg-overlay"></canvas>
        </div>
        <img id="reg-preview" alt="Face preview">
        <canvas id="reg-canvas" style="display:none"></canvas>
        <div id="reg-status" style="font-size:12.5px;color:var(--muted);margin-bottom:12px;min-height:20px"></div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button class="btn btn-green" id="reg-capture-btn" disabled>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/></svg>
                Capture Face
            </button>
            <button class="btn btn-gold" id="reg-save-btn" disabled>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                Save & Register
            </button>
            <button class="btn btn-muted" id="reg-retake-btn" disabled>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.54"/></svg>
                Retake
            </button>
        </div>
    </div>
</div>

<script src="https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/dist/face-api.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/face-api.js/dist/face-api.min.js"></script>
<script>
const FACE_THRESHOLD = 0.5;
const MODEL_URL = 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights';
const storedDescriptor = <?= $storedDescriptor ? $storedDescriptor : 'null' ?>;
const faceRegistered = <?= $faceRegistered ? 'true' : 'false' ?>;
const isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;

// ── LIVE CLOCK ──────────────────────────────────────────────────────────────
function updateClock() {
    const now = new Date();
    let h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    document.getElementById('liveClock').textContent =
        String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0') + ' ' + ampm;

    // Dynamic shift hint
    const shiftEl = document.querySelector('.shift-hint');
    if (shiftEl) {
        const shift = new Date(); shift.setHours(8,0,0,0);
        const diffMs = shift - now;
        const diffMin = Math.floor(diffMs / 60000);
        if (diffMin > 0 && diffMin <= 30) {
            shiftEl.innerHTML = `⚠️ Shift starts in <strong>${diffMin} min</strong> — get ready!`;
            shiftEl.style.color = 'var(--amber)';
        } else if (diffMs < 0 && diffMs > -3600000) {
            const lateMin = Math.abs(diffMin);
            shiftEl.innerHTML = `🔴 You are <strong>${lateMin} min late</strong> — clock in now!`;
            shiftEl.style.color = '#e05a5a';
        } else {
            shiftEl.innerHTML = 'Shift starts at <strong>8:00 AM</strong> sharp';
            shiftEl.style.color = 'var(--muted)';
        }
    }
}
setInterval(updateClock, 1000);
updateClock();

// ── TAB SWITCHER ────────────────────────────────────────────────────────────
function switchTab(tab) {
    document.getElementById('tabMyContent').style.display = tab === 'my' ? '' : 'none';
    if (isAdmin) document.getElementById('tabAdminContent').style.display = tab === 'admin' ? '' : 'none';
    document.getElementById('tabMyAtt').classList.toggle('active', tab === 'my');
    document.getElementById('tabAdminAtt')?.classList.toggle('active', tab === 'admin');
}

// ── BROWSER NOTIFICATIONS ───────────────────────────────────────────────────
const SHIFT_HOUR = 8, SHIFT_MIN = 0;

function checkNotifPermission() {
    if (!('Notification' in window)) return;
    if (Notification.permission === 'default') {
        document.getElementById('notifPermStrip').style.display = 'flex';
    }
}

function requestNotifPermission() {
    if (!('Notification' in window)) return;
    Notification.requestPermission().then(perm => {
        document.getElementById('notifPermStrip').style.display = 'none';
        if (perm === 'granted') {
            showToast('Notifications Enabled', 'You will be reminded before your shift starts.', 'success');
            scheduleShiftReminders();
        }
    });
}

function scheduleShiftReminders() {
    if (!('Notification' in window) || Notification.permission !== 'granted') return;
    const now = new Date();
    const shift = new Date(); shift.setHours(SHIFT_HOUR, SHIFT_MIN, 0, 0);

    // 30-min reminder
    const ms30 = shift - now - 30 * 60 * 1000;
    if (ms30 > 0) setTimeout(() => {
        new Notification('☕ AyosCoffeeNegosyo', {
            body: 'Your shift starts in 30 minutes (8:00 AM). Prepare to clock in!',
            icon: '/favicon.ico'
        });
    }, ms30);

    // 10-min reminder
    const ms10 = shift - now - 10 * 60 * 1000;
    if (ms10 > 0) setTimeout(() => {
        new Notification('⏰ Almost Time!', {
            body: 'Your shift starts in 10 minutes. Head to the attendance page now!',
            icon: '/favicon.ico'
        });
    }, ms10);

    // Exactly at 8:00 AM
    const msShift = shift - now;
    if (msShift > 0) setTimeout(() => {
        new Notification('🔔 Shift Started!', {
            body: 'It is now 8:00 AM. Clock in now to be marked On Time!',
            icon: '/favicon.ico'
        });
    }, msShift);

    // 5 min after (late warning)
    const msLate = shift - now + 5 * 60 * 1000;
    if (msLate > 0) setTimeout(() => {
        new Notification('🔴 You May Be Late!', {
            body: 'It is 8:05 AM and you have not clocked in. Clock in now before it gets worse!',
            icon: '/favicon.ico'
        });
    }, msLate);
}

checkNotifPermission();
scheduleShiftReminders();

// ── READINESS ───────────────────────────────────────────────────────────────
function setReady(id, ok, msg) {
    const el = document.getElementById(id);
    if (!el) return;
    el.className = 'ready-item ' + (ok ? 'ok' : 'fail');
    el.querySelector('.ready-icon').innerHTML = ok
        ? '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>'
        : '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
    el.childNodes[el.childNodes.length - 1].textContent = ' ' + msg;
}

// ── TOAST ───────────────────────────────────────────────────────────────────
let toastTimer;
function showToast(title, msg, type = 'success') {
    const toast = document.getElementById('toast');
    document.getElementById('toast-title').textContent = title;
    document.getElementById('toast-msg').textContent = msg;
    const icons = {
        success: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--green-lt)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>`,
        error:   `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e05a5a" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
        warn:    `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--amber)" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`
    };
    document.getElementById('toast-icon').innerHTML = icons[type] || icons.success;
    toast.className = 'show ' + type;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(hideToast, 4000);
}
function hideToast() {
    document.getElementById('toast').className = '';
}

// ── LOAD MODELS ─────────────────────────────────────────────────────────────
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
    } catch(e) {
        setReady('r-models', false, 'Failed to load models: ' + e.message);
    }
}

async function startCamera(videoEl) {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video:{ facingMode:'user', width:{ideal:640}, height:{ideal:480} }, audio:false });
        videoEl.srcObject = stream;
        videoEl.play();
        return stream;
    } catch(e) { return null; }
}

document.getElementById('start-cam-btn').addEventListener('click', async function() {
    this.disabled = true;
    const video = document.getElementById('att-video');
    state.stream = await startCamera(video);
    if (!state.stream) { setReady('r-camera', false, 'Camera access denied'); this.disabled = false; return; }
    this.style.display = 'none';
    state.cameraReady = true;
    setReady('r-camera', true, 'Camera active');
    if (state.modelsLoaded) startDetection();
});

async function startDetection() {
    document.getElementById('capture-btn').disabled = false;
    detectLoop();
}

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
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    canvas.width = videoEl.videoWidth || videoEl.clientWidth;
    canvas.height = videoEl.videoHeight || videoEl.clientHeight;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    if (!detection) return;
    const box = detection.detection.box;
    const sx = canvas.width / (videoEl.videoWidth || 1);
    const sy = canvas.height / (videoEl.videoHeight || 1);
    ctx.strokeStyle = '#6aaa52'; ctx.lineWidth = 2;
    ctx.strokeRect(box.x*sx, box.y*sy, box.width*sx, box.height*sy);
}

function euclidDist(a, b) {
    let sum = 0;
    for (let i = 0; i < a.length; i++) { const d = a[i] - b[i]; sum += d * d; }
    return Math.sqrt(sum);
}

document.getElementById('capture-btn').addEventListener('click', async function() {
    if (!state.faceDetected) { showToast('No Face Detected', 'Please face the camera clearly.', 'error'); return; }
    this.disabled = true;
    const video = document.getElementById('att-video');
    const canvas = document.getElementById('att-canvas');
    try {
        const full = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize:416, scoreThreshold:0.35 })).withFaceLandmarks().withFaceDescriptor();
        if (!full || !full.descriptor) { this.disabled = false; showToast('Scan Failed', 'Could not read face. Try again.', 'error'); return; }
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
            if (dist < FACE_THRESHOLD) {
                state.faceMatched = true;
                faceStatus.innerHTML = '<div class="fs-ok"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Face matched — ready to clock in/out</div>';
            } else {
                state.faceMatched = false;
                faceStatus.innerHTML = '<div class="fs-fail"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> Face not recognized. Please retake.</div>';
            }
        } else if (!faceRegistered) {
            faceStatus.innerHTML = '<div class="fs-info">Register your face first.</div>';
        }
        document.getElementById('retake-btn').disabled = false;
        if (state.stream) state.stream.getTracks().forEach(t => t.stop());
    } catch(e) { this.disabled = false; showToast('Error', 'Face scan failed. Try again.', 'error'); }
});

document.getElementById('retake-btn').addEventListener('click', function() {
    document.getElementById('att-preview').style.display = 'none';
    document.getElementById('video-wrap').style.display = 'block';
    document.getElementById('face-status').innerHTML = '';
    state.captured = false; state.faceMatched = false; state.capturedDescriptor = null; state.capturedPhoto = null;
    document.getElementById('retake-btn').disabled = true;
    document.getElementById('capture-btn').disabled = true;
    document.getElementById('start-cam-btn').style.display = '';
    document.getElementById('start-cam-btn').disabled = false;
    setReady('r-camera', false, 'Camera not started');
});

document.getElementById('clock-in-btn').addEventListener('click', async function() {
    if (!state.captured || !state.faceMatched) { showToast('Verification Required', 'Capture and verify your face first.', 'error'); return; }
    this.disabled = true;
    this.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 0.7s linear infinite"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.54"/></svg> Processing...';
    const fd = new FormData();
    fd.append('action','clock_in'); fd.append('descriptor',JSON.stringify(state.capturedDescriptor)); fd.append('photo',state.capturedPhoto);
    try {
        const res = await fetch('attendance.php', { method:'POST', body:fd });
        const data = await res.json();
        if (data.success) {
            const type = data.punctuality === 'late' ? 'warn' : 'success';
            const title = data.punctuality === 'late' ? '⚠️ Clocked In — Late' : '✅ Clocked In — On Time!';
            showToast(title, data.message, type);
            // Browser notification
            if (Notification.permission === 'granted') {
                new Notification(title, { body: data.message, icon: '/favicon.ico' });
            }
            setTimeout(() => location.reload(), 2000);
        } else {
            showToast('Clock In Failed', data.message, 'error');
            this.disabled = false;
            this.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 11 21 7 17 3"/><line x1="21" y1="7" x2="9" y2="7"/><polyline points="7 13 3 17 7 21"/><line x1="3" y1="17" x2="15" y2="17"/></svg> Clock In';
        }
    } catch(e) { showToast('Network Error', 'Please try again.', 'error'); this.disabled = false; }
});

document.getElementById('clock-out-btn').addEventListener('click', async function() {
    if (!state.captured || !state.faceMatched) { showToast('Verification Required', 'Capture and verify your face first.', 'error'); return; }
    this.disabled = true;
    this.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 0.7s linear infinite"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.54"/></svg> Processing...';
    const fd = new FormData();
    fd.append('action','clock_out'); fd.append('photo',state.capturedPhoto);
    try {
        const res = await fetch('attendance.php', { method:'POST', body:fd });
        const data = await res.json();
        if (data.success) {
            showToast('✅ Clocked Out', data.message + ' — ' + data.duration, 'success');
            if (Notification.permission === 'granted') {
                new Notification('Clocked Out', { body: 'You worked ' + data.duration + ' today. See you tomorrow!', icon: '/favicon.ico' });
            }
            setTimeout(() => location.reload(), 2000);
        } else {
            showToast('Clock Out Failed', data.message, 'error');
            this.disabled = false;
            this.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="7 11 3 7 7 3"/><line x1="3" y1="7" x2="15" y2="7"/><polyline points="17 13 21 17 17 21"/><line x1="21" y1="17" x2="9" y2="17"/></svg> Clock Out';
        }
    } catch(e) { showToast('Network Error', 'Please try again.', 'error'); this.disabled = false; }
});

// ── FACE REGISTRATION MODAL ──────────────────────────────────────────────
async function openFaceModal() {
    document.getElementById('faceModal').classList.add('show');
    const regVideo = document.getElementById('reg-video');
    regState.stream = await startCamera(regVideo);
    if (!regState.stream) { showToast('Camera Denied', 'Camera access was denied.', 'error'); return; }
    regState.cameraReady = true;
    if (state.modelsLoaded) startRegDetection();
}
function closeFaceModal() {
    document.getElementById('faceModal').classList.remove('show');
    if (regState.stream) { regState.stream.getTracks().forEach(t => t.stop()); regState.stream = null; }
    document.getElementById('reg-preview').style.display = 'none';
    document.getElementById('reg-video-wrap').style.display = 'block';
    regState.captured = false; regState.capturedDescriptor = null;
    document.getElementById('reg-capture-btn').disabled = true;
    document.getElementById('reg-save-btn').disabled = true;
    document.getElementById('reg-retake-btn').disabled = true;
    document.getElementById('reg-status').textContent = '';
}
document.getElementById('faceModal').addEventListener('click', function(e) { if (e.target === this) closeFaceModal(); });

async function startRegDetection() {
    document.getElementById('reg-capture-btn').disabled = false;
    regDetectLoop();
}
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
    if (!regState.faceDetected) { showToast('No Face', 'No face detected.', 'error'); return; }
    this.disabled = true;
    const video = document.getElementById('reg-video');
    const canvas = document.getElementById('reg-canvas');
    try {
        const full = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize:416, scoreThreshold:0.35 })).withFaceLandmarks().withFaceDescriptor();
        if (!full || !full.descriptor) { this.disabled = false; showToast('Scan Failed', 'Try again.', 'error'); return; }
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
    } catch(e) { this.disabled = false; showToast('Error', 'Face scan failed.', 'error'); }
});
document.getElementById('reg-retake-btn').addEventListener('click', async function() {
    document.getElementById('reg-preview').style.display = 'none';
    document.getElementById('reg-video-wrap').style.display = 'block';
    regState.captured = false; regState.capturedDescriptor = null;
    document.getElementById('reg-save-btn').disabled = true;
    document.getElementById('reg-retake-btn').disabled = true;
    document.getElementById('reg-capture-btn').disabled = true;
    document.getElementById('reg-status').textContent = '';
    regState.stream = await startCamera(document.getElementById('reg-video'));
    if (regState.stream) { regState.cameraReady = true; startRegDetection(); }
});
document.getElementById('reg-save-btn').addEventListener('click', async function() {
    if (!regState.capturedDescriptor) { showToast('No Face', 'No face captured.', 'error'); return; }
    this.disabled = true;
    this.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 0.7s linear infinite"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.54"/></svg> Saving...';
    const fd = new FormData();
    fd.append('action','register_face'); fd.append('descriptor',JSON.stringify(regState.capturedDescriptor));
    try {
        const res = await fetch('attendance.php', { method:'POST', body:fd });
        const data = await res.json();
        if (data.success) { showToast('Face Registered!', 'You can now clock in using face recognition.', 'success'); closeFaceModal(); setTimeout(() => location.reload(), 1500); }
        else { showToast('Failed', data.message, 'error'); this.disabled = false; this.innerHTML = 'Save & Register'; }
    } catch(e) { showToast('Network Error', 'Please try again.', 'error'); this.disabled = false; }
});

loadModels();
</script>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>
</body>
</html>
