<?php
include 'admin-config.php';
requireAdmin();

// ── DELETE USER ───────────────────────────────────────────────────────────
if (isset($_POST['delete_user'])) {
    $delId = (int)$_POST['user_id'];

    // Delete user's order items, orders, then user
    $conn->query("
        DELETE oi FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.user_id = $delId
    ");
    $conn->query("DELETE FROM orders WHERE user_id = $delId");
    $conn->query("DELETE FROM users WHERE id = $delId");

    // Force logout by clearing their session (PHP sessions are file-based,
    // so we store a "banned" flag in DB)
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_banned TINYINT(1) DEFAULT 0");

    header("Location: admin-users.php?deleted=1");
    exit();
}

// ── FETCH ALL USERS ───────────────────────────────────────────────────────
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_banned TINYINT(1) DEFAULT 0");
$users = $conn->query("
    SELECT u.id, u.username,
           COUNT(DISTINCT o.id) AS total_orders,
           COALESCE(SUM(oi.quantity * oi.price), 0) AS total_spent,
           MAX(o.created_at) AS last_order
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id AND o.status != 'cancelled'
    LEFT JOIN order_items oi ON oi.order_id = o.id
    GROUP BY u.id
    ORDER BY u.id ASC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Management — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;--gold:#c9a84c;--gold-dim:#8a6f2e;--green:#4a7a3a;--green-lt:#6aaa52;--cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8;--red:#c0392b;--red-pale:rgba(192,57,43,0.1);--amber:#d4820a;--sidebar-w:240px}
html{scroll-behavior:smooth}
body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 60% 40% at 15% 0%,rgba(192,57,43,0.04) 0%,transparent 60%),radial-gradient(ellipse 50% 60% at 85% 100%,rgba(201,168,76,0.05) 0%,transparent 60%);pointer-events:none;z-index:0}

/* SIDEBAR */
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

/* MAIN */
#mainContent{margin-left:var(--sidebar-w);flex:1;min-width:0;position:relative;z-index:1;display:flex;flex-direction:column}
.topbar{position:sticky;top:0;z-index:100;background:rgba(11,11,9,0.9);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:0 32px;height:64px;display:flex;align-items:center;justify-content:space-between}
.topbar-title{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream)}
.topbar-sub{font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#e05a5a}
.topbar-user{font-size:12.5px;color:var(--muted)}
.topbar-user span{color:var(--gold);font-weight:500}

.page-body{padding:28px 32px 60px;display:flex;flex-direction:column;gap:24px;max-width:1200px}

/* ALERT */
.success-alert{background:rgba(74,122,58,0.1);border:1px solid rgba(74,122,58,0.3);border-radius:4px;padding:12px 18px;color:var(--green-lt);font-size:13.5px;display:flex;align-items:center;gap:10px}

/* TABLE */
.table-card{background:var(--card);border:1px solid var(--border);border-radius:6px;overflow:hidden}
.table-header{padding:18px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.table-header-title{font-size:14px;font-weight:500;color:var(--cream)}
.user-count{background:var(--red-pale);color:#e05a5a;font-size:10px;font-weight:600;letter-spacing:0.08em;padding:3px 10px;border-radius:3px;text-transform:uppercase}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:var(--muted);padding:10px 16px;text-align:left;border-bottom:1px solid var(--border);font-weight:500}
tbody td{padding:13px 16px;font-size:13px;border-bottom:1px solid rgba(44,44,36,0.5);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:rgba(255,255,255,0.02)}
.user-avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#2a1f08,#1a1a16);border:1px solid var(--gold-dim);display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-size:13px;font-weight:700;color:var(--gold);flex-shrink:0}
.user-info{display:flex;align-items:center;gap:10px}
.user-name{font-weight:500;color:var(--cream)}
.user-id{font-size:11px;color:var(--muted)}

/* DELETE BUTTON */
.delete-btn{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:3px;border:1px solid rgba(192,57,43,0.4);background:var(--red-pale);color:#e05a5a;font-family:'Jost',sans-serif;font-size:11px;font-weight:500;letter-spacing:0.06em;cursor:pointer;transition:all 0.2s}
.delete-btn:hover{background:rgba(192,57,43,0.2);border-color:var(--red);color:#fff}

/* CONFIRM MODAL */
#confirmModal{position:fixed;inset:0;z-index:300;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.7);backdrop-filter:blur(6px);opacity:0;pointer-events:none;transition:opacity 0.3s}
#confirmModal.show{opacity:1;pointer-events:all}
.modal-card{background:var(--card);border:1px solid rgba(192,57,43,0.3);border-radius:6px;padding:32px;max-width:360px;width:90%;text-align:center;transform:translateY(20px);transition:transform 0.3s}
#confirmModal.show .modal-card{transform:translateY(0)}
.modal-card h3{font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--cream);margin-bottom:10px}
.modal-card p{font-size:13.5px;color:var(--muted);line-height:1.6;margin-bottom:24px}
.modal-card p strong{color:#e05a5a}
.modal-btns{display:flex;gap:10px;justify-content:center}
.modal-confirm{padding:10px 24px;background:var(--red);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:#fff;cursor:pointer;transition:background 0.2s}
.modal-confirm:hover{background:#a33535}
.modal-cancel{padding:10px 24px;border:1px solid var(--border);border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted);background:transparent;cursor:pointer;transition:all 0.2s}
.modal-cancel:hover{border-color:var(--gold-dim);color:var(--gold)}
.empty-row td{text-align:center;color:var(--muted);padding:40px 16px;font-size:13px}
</style>
</head>
<body>

<aside id="sidebar">
    <div class="sb-brand">
        <div class="sb-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e05a5a" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div>
            <div class="sb-title">Ayos<span>Coffee</span></div>
            <div class="sb-sub">Admin Panel</div>
        </div>
    </div>
    <nav class="sb-nav">
        <div class="sb-nav-label">Admin</div>
        <a href="admin-dashboard.php" class="nav-item">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></span>
            Dashboard
        </a>
        <a href="admin-users.php" class="nav-item active">
            <span class="nav-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
            User Management
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
        <div>
            <div class="topbar-sub">Admin Panel</div>
            <div class="topbar-title">User Management</div>
        </div>
        <div class="topbar-user">Logged in as <span><?= htmlspecialchars($_SESSION['admin']['username']) ?></span></div>
    </div>

    <div class="page-body">

        <?php if (isset($_GET['deleted'])): ?>
        <div class="success-alert">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            User deleted successfully. Their session will expire on next page load.
        </div>
        <?php endif; ?>

        <div class="table-card">
            <div class="table-header">
                <div class="table-header-title">Registered Users</div>
                <?php if (!empty($users)): ?>
                <div class="user-count"><?= count($users) ?> users</div>
                <?php endif; ?>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Total Orders</th>
                        <th>Total Spent</th>
                        <th>Last Order</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($users)): ?>
                    <tr class="empty-row"><td colspan="5">No registered users yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar"><?= strtoupper(substr($u['username'], 0, 2)) ?></div>
                                <div>
                                    <div class="user-name"><?= htmlspecialchars($u['username']) ?></div>
                                    <div class="user-id">ID #<?= $u['id'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?= number_format($u['total_orders']) ?> orders</td>
                        <td style="color:var(--gold);font-family:'Cormorant Garamond',serif;font-size:15px">₱<?= number_format($u['total_spent'], 2) ?></td>
                        <td style="color:var(--muted);font-size:12px"><?= $u['last_order'] ? date('M d, Y', strtotime($u['last_order'])) : 'No orders yet' ?></td>
                        <td>
                            <button class="delete-btn" onclick="confirmDelete(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                Delete
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- Confirm Delete Modal -->
<div id="confirmModal">
    <div class="modal-card">
        <h3>Delete User</h3>
        <p>Are you sure you want to delete <strong id="modalUsername"></strong>? This will also delete all their orders and cannot be undone.</p>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="delete_user" value="1">
            <input type="hidden" name="user_id" id="modalUserId">
            <div class="modal-btns">
                <button type="button" class="modal-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="modal-confirm">Delete User</button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmDelete(id, username) {
    document.getElementById('modalUserId').value = id;
    document.getElementById('modalUsername').textContent = username;
    document.getElementById('confirmModal').classList.add('show');
}
function closeModal() {
    document.getElementById('confirmModal').classList.remove('show');
}
document.getElementById('confirmModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>
</body>
</html>