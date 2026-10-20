<?php
/**
 * admin-delivery.php — staff queue for DELIVERY orders (Foodpanda-style).
 *   pending           -> staff APPROVES and sets the preparation minutes (or rejects)
 *   preparing         -> staff marks READY when the order is done
 *   ready             -> staff DISPATCHES with a rider (name + phone)
 *   out_for_delivery  -> staff marks DELIVERED
 * The customer sees every step live on order-track.php (it polls every 8s).
 */
include 'admin-config.php';
requireAdmin();
include_once __DIR__ . '/../delivery-lib.php';
dlvEnsureSchema($conn);

// ── Sidebar/topbar context (same pattern as admin-dashboard.php) ──────────
$currentPage  = basename($_SERVER['PHP_SELF']);
$isOwner      = ($_SESSION['admin']['role'] ?? '') === 'owner';
$invOpen      = in_array($currentPage, ['admin-inventory.php', 'admin-products.php']);
$attOpen      = in_array($currentPage, ['admin-attendance.php', 'admin-leave-overtime.php']);
$ordOpen      = in_array($currentPage, ['admin-orders.php', 'admin-delivery.php']);

$pendingCount = 0;
if ($isOwner) {
    try {
        $pendingCount += (int)$conn->query("SELECT COUNT(*) c FROM leave_requests WHERE status='pending'")->fetch_assoc()['c'];
        $pendingCount += (int)$conn->query("SELECT COUNT(*) c FROM overtime_requests WHERE status='pending'")->fetch_assoc()['c'];
    } catch (Throwable $e) { $pendingCount = 0; }
}

// Bilang ng sweldong Paid na hindi pa na-confirm (para sa badge, hindi para sa Owner)
$toConfirm = 0;
if (!$isOwner) {
    try {
        $conn->query("ALTER TABLE payroll ADD COLUMN IF NOT EXISTS received_at TIMESTAMP NULL DEFAULT NULL");
        $myEmpAdminId = (int)($_SESSION['admin']['id'] ?? 0);
        $toConfirm = (int)$conn->query("
            SELECT COUNT(*) c FROM payroll p
            JOIN employees e ON e.id = p.employee_id
            WHERE e.admin_id = $myEmpAdminId AND p.status = 'paid' AND p.received_at IS NULL
        ")->fetch_assoc()['c'];
    } catch (Throwable $e) { $toConfirm = 0; }
}

$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $do = (string)($_POST['do'] ?? '');

    if ($do === 'approve') {
        $min = max(1, min(240, (int)($_POST['minutes'] ?? 0)));
        $st = $conn->prepare("UPDATE orders SET status='preparing', eta_minutes=?, approved_at=NOW(),
                              eta_at = DATE_ADD(NOW(), INTERVAL ? MINUTE)
                              WHERE id=? AND service_type='delivery' AND status='pending'");
        $st->bind_param('iii', $min, $min, $id);
        $st->execute(); $flash = $st->affected_rows ? "Order approved. Customer was told {$min} minutes." : 'Order was already handled.';
    } elseif ($do === 'ready') {
        $st = $conn->prepare("UPDATE orders SET status='ready', ready_at=NOW() WHERE id=? AND service_type='delivery' AND status='preparing'");
        $st->bind_param('i', $id); $st->execute(); $flash = $st->affected_rows ? 'Marked as ready for the rider.' : 'Nothing changed.';
    } elseif ($do === 'dispatch') {
        $rn = mb_substr(trim((string)($_POST['rider_name'] ?? '')), 0, 100);
        $rp = mb_substr(trim((string)($_POST['rider_phone'] ?? '')), 0, 20);
        if ($rn === '') { $flash = 'Please enter the rider name.'; }
        else {
            $st = $conn->prepare("UPDATE orders SET status='out_for_delivery', dispatched_at=NOW(), rider_name=?, rider_phone=?
                                  WHERE id=? AND service_type='delivery' AND status='ready'");
            $st->bind_param('ssi', $rn, $rp, $id); $st->execute();
            $flash = $st->affected_rows ? 'Out for delivery with ' . $rn . '.' : 'Nothing changed.';
        }
    } elseif ($do === 'deliver') {
        $st = $conn->prepare("UPDATE orders SET status='completed', delivered_at=NOW() WHERE id=? AND service_type='delivery' AND status='out_for_delivery'");
        $st->bind_param('i', $id); $st->execute(); $flash = $st->affected_rows ? 'Order delivered.' : 'Nothing changed.';
    } elseif ($do === 'reject') {
        $why = mb_substr(trim((string)($_POST['reason'] ?? '')), 0, 255) ?: 'The branch cannot accept this order right now.';
        $conn->begin_transaction();
        try {
            $st = $conn->prepare("UPDATE orders SET status='cancelled', reject_reason=? WHERE id=? AND service_type='delivery' AND status IN ('pending','preparing','ready')");
            $st->bind_param('si', $why, $id); $st->execute();
            if ($st->affected_rows) {                       // put the stock back (assumes checkout deducted it)
                $q = $conn->prepare("UPDATE products p JOIN order_items oi ON oi.product_id = p.id SET p.stock = p.stock + oi.quantity WHERE oi.order_id = ?");
                $q->bind_param('i', $id); $q->execute();
                $flash = 'Order rejected. Stock returned.';
            } else $flash = 'Nothing changed.';
            $conn->commit();
        } catch (Throwable $e) { $conn->rollback(); $flash = 'Could not reject the order.'; }
    }
    $_SESSION['dlv_flash'] = $flash;
    header('Location: admin-delivery.php' . (isset($_GET['view']) ? '?view=' . urlencode($_GET['view']) : '')); exit();
}
if (!empty($_SESSION['dlv_flash'])) { $flash = $_SESSION['dlv_flash']; unset($_SESSION['dlv_flash']); }

$history = ($_GET['view'] ?? '') === 'done';
$where = $history ? "status IN ('completed','cancelled')" : "status NOT IN ('completed','cancelled')";
$order = $history ? 'o.id DESC LIMIT 50' : "FIELD(o.status,'pending','preparing','ready','out_for_delivery'), o.scheduled_for ASC, o.id ASC";
$rows = $conn->query("SELECT o.* FROM orders o WHERE o.service_type='delivery' AND $where ORDER BY $order")->fetch_all(MYSQLI_ASSOC);

function itemsOf(mysqli $conn, int $oid): array {
    $st = $conn->prepare("SELECT oi.quantity, oi.spec, oi.item_note, oi.addons, COALESCE(p.name, CONCAT('Product #', oi.product_id)) AS name
                          FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
    $st->bind_param('i', $oid); $st->execute();
    $r = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close(); return $r;
}
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
$counts = $conn->query("SELECT status, COUNT(*) c FROM orders WHERE service_type='delivery' AND status IN ('pending','preparing','ready','out_for_delivery') GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$cnt = array_column($counts, 'c', 'status');
$cntPending  = (int)($cnt['pending'] ?? 0);
$cntPrep     = (int)($cnt['preparing'] ?? 0);
$cntReady    = (int)($cnt['ready'] ?? 0);
$cntOut      = (int)($cnt['out_for_delivery'] ?? 0);
$cntActive   = $cntPending + $cntPrep + $cntReady + $cntOut;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delivery Orders — SIPPERÉ Café</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;--gold:#c9a84c;--gold-dim:#8a6f2e;--gold-pale:rgba(201,168,76,0.08);--green:#4a7a3a;--green-lt:#6aaa52;--cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8;--red:#c0392b;--red-pale:rgba(192,57,43,0.1);--amber:#d4820a;--amber-pale:rgba(212,130,10,0.1);--blue:#5b8fd4;--blue-pale:rgba(91,143,212,0.1);--purple:#9b7be0;--purple-pale:rgba(155,123,224,0.1);--sidebar-w:240px}
html{scroll-behavior:smooth}
body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 60% 40% at 15% 0%,rgba(192,57,43,0.04) 0%,transparent 60%),radial-gradient(ellipse 50% 60% at 85% 100%,rgba(201,168,76,0.05) 0%,transparent 60%);pointer-events:none;z-index:0}

/* ══ SIDEBAR (same shell as admin-dashboard.php) ══ */
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
.nav-group.open > .nav-toggle .nav-badge{display:none}

/* ══ MAIN SHELL ══ */
#mainContent{margin-left:var(--sidebar-w);flex:1;min-width:0;position:relative;z-index:1;display:flex;flex-direction:column}
.topbar{position:sticky;top:0;z-index:100;background:rgba(11,11,9,0.9);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:0 32px;height:64px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.topbar-sub{font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#e05a5a}
.topbar-title{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream)}
.topbar-right{display:flex;align-items:center;gap:10px}
.topbar-period{display:inline-flex;align-items:center;gap:7px;font-size:11px;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);padding:5px 12px;border:1px solid var(--border);border-radius:20px}
.live-dot{width:6px;height:6px;border-radius:50%;background:var(--green-lt);flex-shrink:0;animation:pulse 1.6s ease-in-out infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.35}}

.dash-body{padding:28px 32px 60px;display:flex;flex-direction:column;gap:24px;max-width:1300px}

/* ══ STAT CARDS (same language as admin-dashboard.php) ══ */
.stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:20px 22px 18px;display:flex;flex-direction:column;gap:6px;position:relative;overflow:hidden}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--gold-dim),transparent)}
.stat-icon{width:38px;height:38px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:var(--gold-pale);margin-bottom:4px;flex-shrink:0}
.stat-icon.amber-bg{background:var(--amber-pale)}
.stat-icon.blue-bg{background:var(--blue-pale)}
.stat-icon.purple-bg{background:var(--purple-pale)}
.stat-label{font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted)}
.stat-value{font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:700;color:var(--cream);line-height:1}
.stat-value.gold{color:var(--gold)}
.stat-sub{font-size:11px;color:var(--muted);margin-top:2px}

.section-hd{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.section-hd-line{flex:1;height:1px;background:linear-gradient(90deg,var(--border),transparent)}
.section-title{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--cream)}
.section-link{font-size:12px;color:var(--gold);text-decoration:none;letter-spacing:0.06em;white-space:nowrap}
.section-link:hover{text-decoration:underline}

/* ══ FLASH ══ */
.flash{display:flex;align-items:center;gap:10px;background:var(--gold-pale);border:1px solid var(--gold-dim);color:var(--gold);padding:12px 16px;border-radius:5px;font-size:13px}

/* ══ ORDER CARDS ══ */
.orders-list{display:flex;flex-direction:column;gap:14px}
.ocard{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:20px 22px;position:relative;overflow:hidden}
.ocard::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--gold-dim),transparent)}
.ocard-top{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:14px}
.ocard-code{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:700;color:var(--cream)}
.ocard-placed{font-size:11.5px;color:var(--muted);margin-top:2px}
.pill{display:inline-flex;align-items:center;padding:4px 11px;border-radius:3px;font-size:10.5px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;height:fit-content;border:1px solid currentColor}
.pill.s-pending{color:var(--amber);background:var(--amber-pale)}
.pill.s-preparing{color:var(--gold);background:var(--gold-pale)}
.pill.s-ready{color:var(--blue);background:var(--blue-pale)}
.pill.s-out_for_delivery{color:var(--purple);background:var(--purple-pale)}
.pill.s-completed{color:var(--green-lt);background:rgba(74,122,58,0.12)}
.pill.s-cancelled{color:#e05a5a;background:var(--red-pale)}
.ocard-meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px 18px;font-size:13px;color:var(--muted);margin-bottom:14px;padding-bottom:14px;border-bottom:1px dashed var(--border)}
.ocard-meta b{display:block;font-size:9.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);font-weight:500;margin-bottom:2px}
.ocard-meta span{color:var(--cream)}
.ocard-items{font-size:13px;margin-bottom:14px;display:flex;flex-direction:column;gap:3px}
.ocard-items .line{color:var(--text)}
.ocard-items .sp{font-size:11.5px;color:var(--gold-dim);padding-left:12px}
.ocard-note{font-size:11.5px;color:var(--muted);margin-top:4px}
.ocard-act{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.ocard-act form{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.ocard-act input[type=text],.ocard-act input[type=number]{background:var(--surface);border:1px solid var(--border);border-radius:3px;color:var(--cream);padding:10px 12px;font-family:'Jost',sans-serif;font-size:13px;outline:none;transition:border-color .2s}
.ocard-act input:focus{border-color:var(--gold-dim)}
.ocard-act input[type=number]{width:90px}
.ocard-act .hint{font-size:11.5px;color:var(--muted)}
.btn{padding:10px 18px;border-radius:3px;border:1px solid var(--border);background:transparent;color:var(--muted);font-family:'Jost',sans-serif;font-size:11.5px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;cursor:pointer;transition:all .2s}
.btn.go{background:var(--green);border-color:var(--green);color:#fff}
.btn.go:hover{background:var(--green-lt)}
.btn.no{color:var(--muted)}
.btn.no:hover{background:var(--red);border-color:var(--red);color:#fff}

/* ══ EMPTY STATE ══ */
.empty-state{text-align:center;padding:70px 20px;color:var(--muted)}
.empty-icon{width:68px;height:68px;margin:0 auto 20px;border:1px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;opacity:.5}
.empty-state h3{font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--text);margin-bottom:6px;font-weight:600}
.empty-state p{font-size:13px;color:var(--muted)}

@media(max-width:1100px){.stat-row{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){.dash-body{padding:20px 16px 48px}.stat-row{grid-template-columns:1fr 1fr}}
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
    <!-- ORDERS & SALES DROPDOWN -->
    <div class="nav-group <?= $ordOpen ? 'open has-active' : '' ?>">
        <button type="button" class="nav-item nav-toggle" aria-expanded="<?= $ordOpen ? 'true' : 'false' ?>">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span>
            Orders & Sales
            <?php if ($cntPending > 0): ?><span class="nav-badge"><?= $cntPending ?></span><?php endif; ?>
            <span class="nav-chevron"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></span>
        </button>
        <div class="nav-sub">
            <div class="nav-sub-inner">
                <a href="admin-orders.php" class="nav-item <?= $currentPage === 'admin-orders.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg></span>
                    Overview
                </a>
                <a href="admin-delivery.php" class="nav-item <?= $currentPage === 'admin-delivery.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></span>
                    Delivery Orders
                    <?php if ($cntPending > 0): ?><span class="nav-badge"><?= $cntPending ?></span><?php endif; ?>
                </a>
            </div>
        </div>
    </div>

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

    <?php if (!$isOwner): ?>
    <a href="receipt-payment-salary.php" class="nav-item <?= $currentPage === 'receipt-payment-salary.php' ? 'active' : '' ?>">
        <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 2v20l3-2 2.5 2L12 20l2.5 2L17 20l3 2V2l-3 2-2.5-2L12 4 9.5 2 7 4z"/><line x1="8" y1="9" x2="16" y2="9"/><line x1="8" y1="13" x2="14" y2="13"/></svg></span>
        Receive Payment Salary
        <?php if ($toConfirm > 0): ?><span class="nav-badge"><?= $toConfirm ?></span><?php endif; ?>
    </a>
    <?php endif; ?>

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
        <div>
            <div class="topbar-sub">Staff Queue</div>
            <div class="topbar-title">Delivery Orders</div>
        </div>
        <div class="topbar-right">
            <div class="topbar-period"><span class="live-dot"></span><?= $history ? 'History' : $cntActive . ' Active' ?></div>
        </div>
    </div>

    <div class="dash-body">

        <!-- QUICK STATS (always the live state, regardless of which tab is open below) -->
        <div class="stat-row">
            <div class="stat-card">
                <div class="stat-icon amber-bg"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d4820a" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <div class="stat-label">Waiting for Approval</div>
                <div class="stat-value" style="color:<?= $cntPending > 0 ? 'var(--amber)' : 'var(--cream)' ?>"><?= $cntPending ?></div>
                <div class="stat-sub">New delivery requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.8"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg></div>
                <div class="stat-label">Preparing</div>
                <div class="stat-value gold"><?= $cntPrep ?></div>
                <div class="stat-sub">Approved, in the kitchen</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue-bg"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#5b8fd4" stroke-width="1.8"><path d="M20 6 9 17l-5-5"/></svg></div>
                <div class="stat-label">Ready</div>
                <div class="stat-value"><?= $cntReady ?></div>
                <div class="stat-sub">Waiting for a rider</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple-bg"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9b7be0" stroke-width="1.8"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div>
                <div class="stat-label">Out for Delivery</div>
                <div class="stat-value"><?= $cntOut ?></div>
                <div class="stat-sub">On the road</div>
            </div>
        </div>

        <?php if ($flash): ?>
        <div class="flash">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 5-6"/></svg>
            <span><?= $h($flash) ?></span>
        </div>
        <?php endif; ?>

        <div>
            <div class="section-hd">
                <div class="section-title"><?= $history ? 'Delivery History' : 'Active Deliveries' ?></div>
                <div class="section-hd-line"></div>
                <a href="<?= $history ? 'admin-delivery.php' : 'admin-delivery.php?view=done' ?>" class="section-link"><?= $history ? '← Back to active' : 'View history →' ?></a>
            </div>

            <?php if (!$rows): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    </div>
                    <h3>No <?= $history ? 'delivery history' : 'delivery orders' ?> yet</h3>
                    <p><?= $history ? 'Completed and cancelled deliveries will show up here.' : 'New delivery orders will appear here as customers place them.' ?></p>
                </div>
            <?php else: ?>
            <div class="orders-list">
            <?php foreach ($rows as $o): $st = strtolower($o['status']); $id = (int)$o['id'];
                  $code = $o['receipt_code'] ?: ('#' . str_pad((string)$id, 5, '0', STR_PAD_LEFT)); ?>
            <div class="ocard">
                <div class="ocard-top">
                    <div>
                        <div class="ocard-code"><?= $h($code) ?></div>
                        <div class="ocard-placed">Placed <?= $h(date('M j · g:i A', strtotime($o['created_at']))) ?></div>
                    </div>
                    <span class="pill s-<?= $h($st) ?>"><?= $h(dlvStatusLabel($st, 'delivery')) ?></span>
                </div>
                <div class="ocard-meta">
                    <div><b>Deliver to</b><span><?= $h($o['delivery_address']) ?></span></div>
                    <div><b>Contact</b><span><?= $h($o['delivery_phone'] ?: $o['guest_phone'] ?? '') ?></span></div>
                    <div><b>Scheduled for</b><span><?= $o['scheduled_for'] ? $h(date('D, M j · g:i A', strtotime($o['scheduled_for']))) : '—' ?></span></div>
                    <div><b>Payment</b><span><?= $h(ucfirst((string)($o['payment_method'] ?? 'cash'))) ?></span></div>
                    <div><b>Total</b><span>₱<?= number_format((float)$o['total'], 2) ?></span></div>
                    <?php if ($st === 'preparing' && $o['eta_at']): ?><div><b>Ready by</b><span><?= $h(date('g:i A', strtotime($o['eta_at']))) ?> (<?= (int)$o['eta_minutes'] ?> min)</span></div><?php endif; ?>
                    <?php if ($o['rider_name']): ?><div><b>Rider</b><span><?= $h($o['rider_name']) ?> <?= $h($o['rider_phone']) ?></span></div><?php endif; ?>
                    <?php if ($o['reject_reason']): ?><div><b>Reason</b><span><?= $h($o['reject_reason']) ?></span></div><?php endif; ?>
                </div>
                <div class="ocard-items">
                <?php foreach (itemsOf($conn, $id) as $it):
                    $ad = json_decode((string)$it['addons'], true); $ad = is_array($ad) ? array_map(fn($a) => is_array($a) ? ($a['name'] ?? '') : $a, $ad) : [];
                    $sp = trim((string)$it['spec']) . ($ad ? ' · + ' . implode(', ', $ad) : ''); ?>
                    <div class="line">×<?= (int)$it['quantity'] ?> <?= $h($it['name']) ?></div>
                    <?php if (trim($sp, ' ·') !== ''): ?><div class="sp"><?= $h(trim($sp, ' ·')) ?></div><?php endif; ?>
                    <?php if ($it['item_note']): ?><div class="sp">"<?= $h($it['item_note']) ?>"</div><?php endif; ?>
                <?php endforeach; ?>
                <?php if (!empty($o['notes'])): ?><div class="ocard-note">Order note: <?= $h($o['notes']) ?></div><?php endif; ?>
                </div>

                <div class="ocard-act">
                <?php if ($st === 'pending'): ?>
                    <form method="POST"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="do" value="approve">
                        <input type="number" name="minutes" min="1" max="240" value="20" required> <span class="hint">minutes to prepare</span>
                        <button class="btn go">Approve</button></form>
                    <form method="POST" onsubmit="return confirm('Reject this order?')"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="do" value="reject">
                        <input type="text" name="reason" placeholder="Reason (optional)"><button class="btn no">Reject</button></form>
                <?php elseif ($st === 'preparing'): ?>
                    <form method="POST"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="do" value="ready"><button class="btn go">Mark ready</button></form>
                <?php elseif ($st === 'ready'): ?>
                    <form method="POST"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="do" value="dispatch">
                        <input type="text" name="rider_name" placeholder="Rider name" required><input type="text" name="rider_phone" placeholder="Rider phone">
                        <button class="btn go">Out for delivery</button></form>
                <?php elseif ($st === 'out_for_delivery'): ?>
                    <form method="POST"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="do" value="deliver"><button class="btn go">Mark delivered</button></form>
                <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
document.querySelectorAll('.nav-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const group = btn.closest('.nav-group');
        const isOpen = group.classList.toggle('open');
        btn.setAttribute('aria-expanded', isOpen);
    });
});
setTimeout(function(){ if(!document.querySelector('input:focus')) location.reload(); }, 20000);
</script>
</body>
</html>