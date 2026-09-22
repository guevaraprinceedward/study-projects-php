<?php
include 'admin-config.php';
requireAdmin();

// Ensure all needed columns
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS stock INT NOT NULL DEFAULT 100");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS reorder_level INT NOT NULL DEFAULT 10");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS branch VARCHAR(20) DEFAULT 'laguna'");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT 'mains'");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS sku VARCHAR(100) DEFAULT NULL");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS type VARCHAR(20) DEFAULT 'food'");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS image TEXT DEFAULT NULL");

// Fetch all products
$products = $conn->query("SELECT * FROM products ORDER BY branch ASC, id ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Product Management — AyosCoffeeNegosyo</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;
    --gold:#c9a84c;--gold-dim:#8a6f2e;--gold-pale:rgba(201,168,76,0.08);
    --green:#4a7a3a;--green-lt:#6aaa52;--cream:#f0ead8;--muted:#6b6b58;
    --text:#e8e4d8;--red:#c0392b;--red-pale:rgba(192,57,43,0.1);
    --amber:#d4820a;--amber-pale:rgba(212,130,10,0.1);--sidebar-w:240px;
    --blue:#3b82f6;--blue-pale:rgba(59,130,246,0.1);
}
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
.sb-footer{padding:10px;border-top:1px solid var(--border)}
.nav-item.logout{color:#e05a5a}
.nav-item.logout:hover{background:rgba(224,90,90,0.08)}

/* MAIN */
#mainContent{margin-left:var(--sidebar-w);flex:1;min-width:0;position:relative;z-index:1;display:flex;flex-direction:column}
.topbar{position:sticky;top:0;z-index:100;background:rgba(11,11,9,0.9);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:0 32px;height:64px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.topbar-sub{font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#e05a5a}
.topbar-title{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream)}
.topbar-right{display:flex;align-items:center;gap:12px}
.topbar-user{font-size:12.5px;color:var(--muted)}
.topbar-user span{color:var(--gold);font-weight:500}

/* PAGE BODY */
.page-body{padding:28px 32px 80px;display:flex;flex-direction:column;gap:20px;max-width:1400px}

/* TOOLBAR */
.toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
.toolbar-left{display:flex;align-items:center;gap:10px}
.search-wrap{position:relative}
.search-wrap input{background:var(--card);border:1px solid var(--border);border-radius:5px;padding:9px 14px 9px 36px;font-family:'Jost',sans-serif;font-size:13px;color:var(--text);width:220px;outline:none;transition:border-color 0.2s}
.search-wrap input:focus{border-color:var(--gold-dim)}
.search-wrap input::placeholder{color:var(--muted)}
.search-wrap .s-icon{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--muted);pointer-events:none}
.filter-select{background:var(--card);border:1px solid var(--border);border-radius:5px;padding:9px 14px;font-family:'Jost',sans-serif;font-size:13px;color:var(--text);outline:none;cursor:pointer;transition:border-color 0.2s}
.filter-select:focus{border-color:var(--gold-dim)}
.filter-select option{background:var(--card)}
.btn-add{display:flex;align-items:center;gap:8px;padding:10px 20px;background:var(--green);border:none;border-radius:5px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:0.06em;color:#fff;cursor:pointer;transition:background 0.2s;white-space:nowrap}
.btn-add:hover{background:var(--green-lt)}
.count-badge{font-size:11px;color:var(--muted);letter-spacing:0.06em}

/* TABLE CARD */
.table-card{background:var(--card);border:1px solid var(--border);border-radius:6px;overflow:hidden}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:var(--muted);padding:12px 16px;text-align:left;border-bottom:1px solid var(--border);font-weight:500;white-space:nowrap}
tbody td{padding:12px 16px;font-size:13px;border-bottom:1px solid rgba(44,44,36,0.5);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:rgba(255,255,255,0.02)}
.prod-thumb{width:48px;height:48px;border-radius:4px;object-fit:cover;background:var(--surface);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0}
.prod-thumb img{width:100%;height:100%;object-fit:cover}
.prod-thumb-placeholder{width:48px;height:48px;border-radius:4px;background:var(--surface);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);flex-shrink:0}
.prod-name-cell{display:flex;align-items:center;gap:12px}
.prod-name{font-weight:500;color:var(--cream)}
.prod-sku{font-size:11px;color:var(--muted);margin-top:2px;letter-spacing:0.04em}
.branch-pill{display:inline-flex;padding:3px 9px;border-radius:3px;font-size:10px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase}
.branch-pill.laguna{background:rgba(74,122,58,0.12);color:var(--green-lt)}
.branch-pill.manila{background:rgba(201,168,76,0.08);color:var(--gold)}
.cat-pill{display:inline-flex;padding:3px 8px;border-radius:3px;font-size:10px;font-weight:500;letter-spacing:0.06em;text-transform:uppercase;background:rgba(59,130,246,0.1);color:#60a5fa}
.type-pill{display:inline-flex;padding:3px 8px;border-radius:3px;font-size:10px;font-weight:500;letter-spacing:0.06em;text-transform:uppercase}
.type-pill.food{background:rgba(201,168,76,0.08);color:var(--gold)}
.type-pill.beverage{background:rgba(74,122,58,0.1);color:var(--green-lt)}
.type-pill.item{background:rgba(59,130,246,0.1);color:#60a5fa}
.price-cell{font-family:'Cormorant Garamond',serif;font-size:16px;color:var(--gold);font-weight:600}
.stock-cell{display:flex;align-items:center;gap:8px}
.stock-num{font-weight:500;min-width:28px}
.stock-bar-bg{width:60px;height:4px;background:var(--border);border-radius:2px}
.stock-bar-fill{height:100%;border-radius:2px}
.stock-bar-fill.ok{background:var(--green-lt)}
.stock-bar-fill.warn{background:var(--amber)}
.stock-bar-fill.danger{background:var(--red)}
.status-dot{width:7px;height:7px;border-radius:50%;display:inline-block;margin-right:6px}
.status-dot.active{background:var(--green-lt)}
.status-dot.inactive{background:var(--muted)}
.action-btns{display:flex;align-items:center;gap:6px}
.act-btn{width:32px;height:32px;border-radius:4px;border:1px solid var(--border);background:transparent;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.18s;color:var(--muted)}
.act-btn:hover{color:var(--cream);border-color:var(--gold-dim)}
.act-btn.delete:hover{color:#e05a5a;border-color:rgba(192,57,43,0.4);background:var(--red-pale)}
.empty-row td{text-align:center;color:var(--muted);padding:48px 16px;font-size:13px}

/* PAGINATION */
.table-footer{padding:12px 16px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.pg-btns{display:flex;gap:4px}
.pg-btn{padding:5px 12px;border-radius:4px;border:1px solid var(--border);background:transparent;color:var(--muted);font-family:'Jost',sans-serif;font-size:12px;cursor:pointer;transition:all 0.18s}
.pg-btn:hover:not(:disabled){border-color:var(--gold-dim);color:var(--gold)}
.pg-btn.active{border-color:var(--gold);color:var(--gold);background:rgba(201,168,76,0.08)}
.pg-btn:disabled{opacity:0.3;cursor:not-allowed}
.pg-info{font-size:11px;color:var(--muted);letter-spacing:0.06em}

/* ═══════════════════════════════════
   MODAL OVERLAY
═══════════════════════════════════ */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.75);backdrop-filter:blur(6px);z-index:500;display:flex;align-items:flex-start;justify-content:center;padding:40px 20px;opacity:0;pointer-events:none;transition:opacity 0.3s ease;overflow-y:auto}
.modal-overlay.show{opacity:1;pointer-events:all}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:8px;width:100%;max-width:720px;transform:translateY(24px);transition:transform 0.35s cubic-bezier(0.4,0,0.2,1);overflow:hidden;margin:auto}
.modal-overlay.show .modal{transform:translateY(0)}

.modal-header{display:flex;align-items:center;justify-content:space-between;padding:24px 28px;border-bottom:1px solid var(--border)}
.modal-title-wrap{}
.modal-eyebrow{font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#e05a5a;margin-bottom:4px}
.modal-title{font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700;color:var(--cream)}
.modal-close{width:34px;height:34px;border:1px solid var(--border);border-radius:50%;background:transparent;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;flex-shrink:0}
.modal-close:hover{border-color:var(--gold-dim);color:var(--cream)}

.modal-body{padding:28px;display:flex;flex-direction:column;gap:22px}

/* FORM ELEMENTS */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-row.triple{grid-template-columns:1fr 1fr 1fr}
.form-row.single{grid-template-columns:1fr}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-label{font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted);font-weight:500}
.form-label span{color:#e05a5a;margin-left:2px}
.form-input,.form-select,.form-textarea{background:var(--card);border:1px solid var(--border);border-radius:5px;padding:10px 14px;font-family:'Jost',sans-serif;font-size:13.5px;color:var(--text);outline:none;transition:border-color 0.2s,box-shadow 0.2s;width:100%}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--gold-dim);box-shadow:0 0 0 3px rgba(201,168,76,0.07)}
.form-input::placeholder,.form-textarea::placeholder{color:var(--muted)}
.form-select{cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b6b58' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center}
.form-select option{background:var(--card)}
.form-textarea{resize:vertical;min-height:80px;line-height:1.6}
.form-hint{font-size:11px;color:var(--muted);margin-top:2px}

/* DIVIDER */
.form-divider{display:flex;align-items:center;gap:12px}
.form-divider-line{flex:1;height:1px;background:var(--border)}
.form-divider-label{font-size:10px;letter-spacing:0.18em;text-transform:uppercase;color:var(--muted);white-space:nowrap}

/* STOCK RANGE */
.stock-range-wrap{display:flex;flex-direction:column;gap:8px}
.stock-range-top{display:flex;align-items:center;justify-content:space-between}
.stock-range-val{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:700;color:var(--gold)}
.stock-range-max{font-size:11px;color:var(--muted)}
input[type=range]{width:100%;accent-color:var(--gold);cursor:pointer;height:4px}

/* IMAGE UPLOAD */
.img-upload-area{border:1.5px dashed var(--border);border-radius:6px;padding:24px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;cursor:pointer;transition:border-color 0.2s,background 0.2s;position:relative;min-height:130px;text-align:center}
.img-upload-area:hover{border-color:var(--gold-dim);background:rgba(201,168,76,0.03)}
.img-upload-area.has-img{padding:10px}
.img-upload-area input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.img-preview{width:100%;max-height:200px;object-fit:contain;border-radius:4px;display:block}
.img-upload-icon{color:var(--muted);opacity:0.5}
.img-upload-text{font-size:13px;color:var(--muted)}
.img-upload-text strong{color:var(--text)}
.img-upload-hint{font-size:11px;color:var(--muted)}
.img-actions{display:flex;gap:8px;margin-top:8px;justify-content:center}
.img-del-btn{padding:5px 14px;border-radius:4px;border:1px solid rgba(192,57,43,0.4);background:var(--red-pale);color:#e05a5a;font-family:'Jost',sans-serif;font-size:12px;cursor:pointer;transition:all 0.18s;display:flex;align-items:center;gap:6px}
.img-del-btn:hover{background:rgba(192,57,43,0.2)}
.img-change-btn{padding:5px 14px;border-radius:4px;border:1px solid var(--border);background:transparent;color:var(--muted);font-family:'Jost',sans-serif;font-size:12px;cursor:pointer;transition:all 0.18s;position:relative;display:flex;align-items:center;gap:6px}
.img-change-btn:hover{border-color:var(--gold-dim);color:var(--gold)}
.img-change-btn input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}

/* MODAL FOOTER */
.modal-footer{padding:20px 28px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:flex-end;gap:10px}
.btn-cancel{padding:10px 22px;border-radius:5px;border:1px solid var(--border);background:transparent;color:var(--muted);font-family:'Jost',sans-serif;font-size:13px;font-weight:500;cursor:pointer;transition:all 0.18s}
.btn-cancel:hover{border-color:var(--gold-dim);color:var(--text)}
.btn-save{padding:10px 28px;border-radius:5px;border:none;background:var(--green);color:#fff;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:0.06em;cursor:pointer;transition:background 0.2s;display:flex;align-items:center;gap:8px}
.btn-save:hover{background:var(--green-lt)}
.btn-save:disabled{opacity:0.6;cursor:not-allowed}

/* CONFIRM MODAL */
.confirm-modal{max-width:420px}
.confirm-body{padding:28px;text-align:center}
.confirm-icon{width:56px;height:56px;border-radius:50%;background:var(--red-pale);border:1px solid rgba(192,57,43,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;color:#e05a5a}
.confirm-title{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:var(--cream);margin-bottom:8px}
.confirm-desc{font-size:13px;color:var(--muted);line-height:1.6}
.confirm-footer{padding:0 28px 28px;display:flex;gap:10px;justify-content:center}
.btn-danger{padding:10px 28px;border-radius:5px;border:none;background:var(--red);color:#fff;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;cursor:pointer;transition:background 0.2s}
.btn-danger:hover{background:#e04030}

/* TOAST */
#toast{position:fixed;bottom:28px;right:28px;z-index:999;background:var(--card);border-radius:5px;padding:12px 18px;display:flex;align-items:center;gap:10px;font-size:13.5px;color:var(--cream);box-shadow:0 8px 32px rgba(0,0,0,0.5);transform:translateY(16px);opacity:0;transition:all 0.3s ease;pointer-events:none;border:1px solid var(--border);max-width:320px}
#toast.show{transform:translateY(0);opacity:1}
#toast.success{border-color:var(--green)}
#toast.error{border-color:rgba(192,57,43,0.5)}
#toast .toast-icon{flex-shrink:0}
#toast.success .toast-icon{color:var(--green-lt)}
#toast.error .toast-icon{color:#e05a5a}

@media(max-width:900px){.form-row{grid-template-columns:1fr}.form-row.triple{grid-template-columns:1fr 1fr}}
@media(max-width:768px){.page-body{padding:20px 16px 60px}.toolbar{flex-direction:column;align-items:stretch}}
@keyframes spin{to{transform:rotate(360deg)}}
.spin{animation:spin 0.7s linear infinite}
</style>
</head>
<body>

<!-- SIDEBAR -->
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
        <a href="admin-products.php" class="nav-item active">
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
            <div class="topbar-sub">Admin Panel</div>
            <div class="topbar-title">Product Management</div>
        </div>
        <div class="topbar-right">
            <div class="topbar-user">Welcome, <span><?= htmlspecialchars($_SESSION['admin']['username']) ?></span></div>
        </div>
    </div>

    <div class="page-body">

        <!-- TOOLBAR -->
        <div class="toolbar">
            <div class="toolbar-left">
                <div class="search-wrap">
                    <svg class="s-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="searchInput" placeholder="Search products…" oninput="filterTable()">
                </div>
                <select class="filter-select" id="branchFilter" onchange="filterTable()">
                    <option value="">All Branches</option>
                    <option value="laguna">Laguna</option>
                    <option value="manila">Manila</option>
                </select>
                <select class="filter-select" id="typeFilter" onchange="filterTable()">
                    <option value="">All Types</option>
                    <option value="food">Food</option>
                    <option value="beverage">Beverage</option>
                    <option value="item">Item</option>
                </select>
            </div>
            <div style="display:flex;align-items:center;gap:12px">
                <span class="count-badge" id="countBadge"><?= count($products) ?> products</span>
                <button class="btn-add" onclick="openAdd()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Product
                </button>
            </div>
        </div>

        <!-- TABLE -->
        <div class="table-card">
            <table id="productTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Branch</th>
                        <th>Category</th>
                        <th>SKU</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                <?php if (empty($products)): ?>
                    <tr class="empty-row"><td colspan="9">No products yet. Click <strong>Add Product</strong> to get started.</td></tr>
                <?php else: ?>
                    <?php foreach ($products as $p):
                        $stock = (int)($p['stock'] ?? 100);
                        $reorder = (int)($p['reorder_level'] ?? 10);
                        $pct = min(100, max(0, ($stock / 100) * 100));
                        $fillCls = $stock <= 0 ? 'danger' : ($stock <= $reorder ? 'warn' : 'ok');
                    ?>
                    <tr data-name="<?= htmlspecialchars(strtolower($p['name'])) ?>"
                        data-branch="<?= htmlspecialchars($p['branch'] ?? 'laguna') ?>"
                        data-type="<?= htmlspecialchars($p['type'] ?? 'food') ?>">
                        <td style="color:var(--muted);font-size:12px">#<?= $p['id'] ?></td>
                        <td>
                            <div class="prod-name-cell">
                                <?php if (!empty($p['image'])): ?>
                                    <div class="prod-thumb"><img src="<?= htmlspecialchars($p['image']) ?>" alt="" onerror="this.parentElement.innerHTML='<svg width=16 height=16 viewBox=\'0 0 24 24\' fill=none stroke=\'currentColor\' stroke-width=1><path d=\'M3 11l19-9-9 19-2-8-8-2z\'/></svg>'"></div>
                                <?php else: ?>
                                    <div class="prod-thumb-placeholder"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg></div>
                                <?php endif; ?>
                                <div>
                                    <div class="prod-name"><?= htmlspecialchars($p['name']) ?></div>
                                    <div class="prod-sku"><?= htmlspecialchars($p['sku'] ?? '—') ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="type-pill <?= htmlspecialchars($p['type'] ?? 'food') ?>"><?= ucfirst($p['type'] ?? 'food') ?></span></td>
                        <td><span class="branch-pill <?= htmlspecialchars($p['branch'] ?? 'laguna') ?>"><?= ucfirst($p['branch'] ?? 'laguna') ?></span></td>
                        <td><span class="cat-pill"><?= htmlspecialchars($p['category'] ?? '—') ?></span></td>
                        <td style="color:var(--muted);font-size:12px;letter-spacing:0.04em"><?= htmlspecialchars($p['sku'] ?? '—') ?></td>
                        <td class="price-cell">₱<?= number_format((float)$p['price'], 2) ?></td>
                        <td>
                            <div class="stock-cell">
                                <span class="stock-num" style="color:<?= $stock<=0?'var(--red)':($stock<=$reorder?'var(--amber)':'var(--green-lt)') ?>"><?= $stock ?></span>
                                <div class="stock-bar-bg"><div class="stock-bar-fill <?= $fillCls ?>" style="width:<?= $pct ?>%"></div></div>
                            </div>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button class="act-btn" title="Edit product" onclick='openEdit(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)'>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button class="act-btn delete" title="Delete product" onclick="confirmDelete(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
            <div class="table-footer">
                <span class="pg-info" id="pgInfo"></span>
                <div class="pg-btns" id="pgBtns"></div>
            </div>
        </div>

    </div>
</div>

<!-- ══════════════════════════════════
     PRODUCT MODAL (Add / Edit)
══════════════════════════════════ -->
<div class="modal-overlay" id="productModal" onclick="if(event.target===this)closeModal()">
<div class="modal" id="modalBox">

    <div class="modal-header">
        <div class="modal-title-wrap">
            <div class="modal-eyebrow" id="modalEyebrow">New Product</div>
            <div class="modal-title" id="modalTitle">Add Product</div>
        </div>
        <button class="modal-close" onclick="closeModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    <div class="modal-body">

        <!-- TYPE -->
        <div class="form-group">
            <label class="form-label">Product Type <span>*</span></label>
            <select class="form-select" id="f_type">
                <option value="food">🍽 Food</option>
                <option value="beverage">☕ Beverage</option>
                <option value="item">📦 Item / Add-on</option>
            </select>
        </div>

        <div class="form-divider"><div class="form-divider-line"></div><div class="form-divider-label">Basic Info</div><div class="form-divider-line"></div></div>

        <!-- NAME + BRANCH -->
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Product Name <span>*</span></label>
                <input class="form-input" id="f_name" type="text" placeholder="e.g. Caramel Macchiato">
            </div>
            <div class="form-group">
                <label class="form-label">Branch <span>*</span></label>
                <select class="form-select" id="f_branch">
                    <option value="laguna">📍 Laguna</option>
                    <option value="manila">📍 Manila</option>
                </select>
            </div>
        </div>

        <!-- DESCRIPTION -->
        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea class="form-textarea" id="f_desc" placeholder="Brief product description…"></textarea>
        </div>

        <!-- CATEGORY + SKU -->
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Category <span>*</span></label>
                <select class="form-select" id="f_category">
                    <option value="mains">Mains</option>
                    <option value="sides">Sides</option>
                    <option value="drinks">Drinks</option>
                    <option value="desserts">Desserts</option>
                    <option value="coffee">Coffee</option>
                    <option value="add-ons">Add-ons</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">SKU / Code</label>
                <input class="form-input" id="f_sku" type="text" placeholder="e.g. CARM-MAC-001">
            </div>
        </div>

        <div class="form-divider"><div class="form-divider-line"></div><div class="form-divider-label">Pricing & Inventory</div><div class="form-divider-line"></div></div>

        <!-- PRICE + REORDER -->
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Price (₱) <span>*</span></label>
                <input class="form-input" id="f_price" type="number" min="0" step="0.01" placeholder="0.00">
            </div>
            <div class="form-group">
                <label class="form-label">Reorder Level</label>
                <input class="form-input" id="f_reorder" type="number" min="1" max="100" value="10">
                <span class="form-hint">Alert fires when stock reaches this number</span>
            </div>
        </div>

        <!-- STOCK SLIDER -->
        <div class="form-group">
            <label class="form-label">Stock Quantity</label>
            <div class="stock-range-wrap">
                <div class="stock-range-top">
                    <span style="font-size:12px;color:var(--muted)">0</span>
                    <div style="display:flex;align-items:baseline;gap:6px">
                        <span class="stock-range-val" id="stockDisplay">100</span>
                        <span class="stock-range-max">/ 100</span>
                    </div>
                    <span style="font-size:12px;color:var(--muted)">100</span>
                </div>
                <input type="range" id="f_stock" min="0" max="100" value="100" oninput="document.getElementById('stockDisplay').textContent=this.value;document.getElementById('f_stock_num').value=this.value">
                <input type="number" id="f_stock_num" class="form-input" min="0" max="100" value="100" style="margin-top:6px" oninput="syncRange(this.value)" placeholder="Or type a value">
                <span class="form-hint">Maximum stock is 100 units</span>
            </div>
        </div>

        <div class="form-divider"><div class="form-divider-line"></div><div class="form-divider-label">Product Image</div><div class="form-divider-line"></div></div>

        <!-- IMAGE -->
        <div class="form-group">
            <label class="form-label">Image</label>
            <div class="img-upload-area" id="imgUploadArea">
                <input type="file" id="f_image_file" accept="image/*" onchange="previewImage(this)">
                <svg class="img-upload-icon" id="imgIcon" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                <div class="img-upload-text" id="imgText"><strong>Click to upload</strong> or drag & drop</div>
                <div class="img-upload-hint" id="imgHint">PNG, JPG, WebP — max 5 MB</div>
            </div>
            <div id="imgActionsWrap" style="display:none">
                <img id="imgPreview" class="img-preview" src="" alt="Preview" style="display:none;margin-top:8px;max-height:160px">
                <div class="img-actions">
                    <button type="button" class="img-change-btn">
                        <input type="file" id="f_image_change" accept="image/*" onchange="previewImage(this)">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Change Image
                    </button>
                    <button type="button" class="img-del-btn" onclick="clearImage()">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                        Remove
                    </button>
                </div>
            </div>
            <input type="hidden" id="f_image_url" value="">
            <input type="hidden" id="f_image_delete" value="0">
        </div>

    </div><!-- /modal-body -->

    <div class="modal-footer">
        <button class="btn-cancel" onclick="closeModal()">Cancel</button>
        <button class="btn-save" id="btnSave" onclick="saveProduct()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Save Product
        </button>
    </div>

</div>
</div>

<!-- CONFIRM DELETE MODAL -->
<div class="modal-overlay" id="confirmModal" onclick="if(event.target===this)closeConfirm()">
<div class="modal confirm-modal" id="confirmBox">
    <div class="confirm-body">
        <div class="confirm-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
        </div>
        <div class="confirm-title">Delete Product?</div>
        <div class="confirm-desc" id="confirmDesc">This action cannot be undone.</div>
    </div>
    <div class="confirm-footer">
        <button class="btn-cancel" onclick="closeConfirm()">Cancel</button>
        <button class="btn-danger" id="btnConfirmDel" onclick="doDelete()">Yes, Delete</button>
    </div>
</div>
</div>

<!-- TOAST -->
<div id="toast">
    <svg class="toast-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    <span id="toastMsg">Done!</span>
</div>

<script>
// ── PAGINATION ──────────────────────────────────────────────────────────
const PER_PAGE = 15;
let currentPage = 1;

function getVisibleRows() {
    return Array.from(document.querySelectorAll('#tableBody tr:not([style*="display: none"])'));
}

function renderPagination() {
    const rows = getVisibleRows();
    const total = rows.length, totalPages = Math.max(1, Math.ceil(total / PER_PAGE));
    if (currentPage > totalPages) currentPage = totalPages;
    const start = (currentPage - 1) * PER_PAGE;
    rows.forEach((r, i) => r.style.display = (i >= start && i < start + PER_PAGE) ? '' : 'none');

    document.getElementById('pgInfo').textContent = total
        ? `Showing ${start + 1}–${Math.min(start + PER_PAGE, total)} of ${total}`
        : '';

    const container = document.getElementById('pgBtns');
    container.innerHTML = '';
    if (totalPages <= 1) return;
    const mkBtn = (label, page, disabled, active) => {
        const b = document.createElement('button');
        b.className = 'pg-btn' + (active ? ' active' : '');
        b.textContent = label; b.disabled = !!disabled;
        b.onclick = () => { currentPage = page; renderPagination(); };
        return b;
    };
    container.appendChild(mkBtn('←', currentPage - 1, currentPage === 1));
    for (let i = 1; i <= totalPages; i++) container.appendChild(mkBtn(i, i, false, i === currentPage));
    container.appendChild(mkBtn('→', currentPage + 1, currentPage === totalPages));
}

// ── SEARCH / FILTER ─────────────────────────────────────────────────────
function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const branch = document.getElementById('branchFilter').value;
    const type = document.getElementById('typeFilter').value;
    const rows = Array.from(document.querySelectorAll('#tableBody tr'));
    let vis = 0;
    rows.forEach(r => {
        if (r.classList.contains('empty-row')) return;
        const nm = r.dataset.name || '';
        const br = r.dataset.branch || '';
        const tp = r.dataset.type || '';
        const show = (!q || nm.includes(q))
            && (!branch || br === branch)
            && (!type || tp === type);
        r.style.display = show ? '' : 'none';
        if (show) vis++;
    });
    document.getElementById('countBadge').textContent = vis + ' products';
    currentPage = 1;
    renderPagination();
}

renderPagination();

// ── IMAGE HANDLING ──────────────────────────────────────────────────────
function previewImage(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const prev = document.getElementById('imgPreview');
        prev.src = e.target.result;
        prev.style.display = 'block';
        document.getElementById('imgUploadArea').classList.add('has-img');
        document.getElementById('imgIcon').style.display = 'none';
        document.getElementById('imgText').style.display = 'none';
        document.getElementById('imgHint').style.display = 'none';
        document.getElementById('imgActionsWrap').style.display = 'block';
        document.getElementById('f_image_delete').value = '0';
        // sync both file inputs
        if (input.id !== 'f_image_change') {
            document.getElementById('f_image_change').files = input.files;
        }
    };
    reader.readAsDataURL(file);
}

function clearImage() {
    document.getElementById('imgPreview').src = '';
    document.getElementById('imgPreview').style.display = 'none';
    document.getElementById('imgActionsWrap').style.display = 'none';
    document.getElementById('imgUploadArea').classList.remove('has-img');
    document.getElementById('imgIcon').style.display = '';
    document.getElementById('imgText').style.display = '';
    document.getElementById('imgHint').style.display = '';
    document.getElementById('f_image_file').value = '';
    document.getElementById('f_image_change').value = '';
    document.getElementById('f_image_url').value = '';
    document.getElementById('f_image_delete').value = '1';
}

function syncRange(val) {
    const v = Math.max(0, Math.min(100, parseInt(val) || 0));
    document.getElementById('f_stock').value = v;
    document.getElementById('stockDisplay').textContent = v;
}

// ── MODAL STATE ─────────────────────────────────────────────────────────
let editingId = null;

function openAdd() {
    editingId = null;
    document.getElementById('modalEyebrow').textContent = 'New Product';
    document.getElementById('modalTitle').textContent = 'Add Product';
    resetForm();
    showModal();
}

function openEdit(p) {
    editingId = p.id;
    document.getElementById('modalEyebrow').textContent = 'Edit Product #' + p.id;
    document.getElementById('modalTitle').textContent = 'Edit Product';
    resetForm();

    document.getElementById('f_type').value = p.type || 'food';
    document.getElementById('f_name').value = p.name || '';
    document.getElementById('f_branch').value = p.branch || 'laguna';
    document.getElementById('f_desc').value = p.description || '';
    document.getElementById('f_category').value = p.category || 'mains';
    document.getElementById('f_sku').value = p.sku || '';
    document.getElementById('f_price').value = p.price || '';
    document.getElementById('f_reorder').value = p.reorder_level || 10;
    const stock = parseInt(p.stock) || 100;
    document.getElementById('f_stock').value = stock;
    document.getElementById('f_stock_num').value = stock;
    document.getElementById('stockDisplay').textContent = stock;

    if (p.image) {
        document.getElementById('f_image_url').value = p.image;
        const prev = document.getElementById('imgPreview');
        prev.src = p.image;
        prev.style.display = 'block';
        document.getElementById('imgActionsWrap').style.display = 'block';
        document.getElementById('imgUploadArea').classList.add('has-img');
        document.getElementById('imgIcon').style.display = 'none';
        document.getElementById('imgText').style.display = 'none';
        document.getElementById('imgHint').style.display = 'none';
    }

    showModal();
}

function resetForm() {
    ['f_type','f_name','f_branch','f_desc','f_category','f_sku','f_price','f_image_url'].forEach(id => {
        const el = document.getElementById(id);
        if (el.tagName === 'SELECT') el.selectedIndex = 0;
        else el.value = '';
    });
    document.getElementById('f_reorder').value = 10;
    document.getElementById('f_stock').value = 100;
    document.getElementById('f_stock_num').value = 100;
    document.getElementById('stockDisplay').textContent = 100;
    document.getElementById('f_image_file').value = '';
    document.getElementById('f_image_change').value = '';
    document.getElementById('f_image_delete').value = '0';
    document.getElementById('imgPreview').src = '';
    document.getElementById('imgPreview').style.display = 'none';
    document.getElementById('imgActionsWrap').style.display = 'none';
    document.getElementById('imgUploadArea').classList.remove('has-img');
    document.getElementById('imgIcon').style.display = '';
    document.getElementById('imgText').style.display = '';
    document.getElementById('imgHint').style.display = '';
}

function showModal() {
    document.getElementById('productModal').classList.add('show');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('f_name').focus(), 350);
}

function closeModal() {
    document.getElementById('productModal').classList.remove('show');
    document.body.style.overflow = '';
}

// ── SAVE PRODUCT ────────────────────────────────────────────────────────
function saveProduct() {
    const name = document.getElementById('f_name').value.trim();
    const price = document.getElementById('f_price').value;
    if (!name) { showToast('Product name is required.', false, true); return; }
    if (!price || isNaN(price)) { showToast('Please enter a valid price.', false, true); return; }

    const btn = document.getElementById('btnSave');
    btn.disabled = true;
    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.54"/></svg> Saving…';

    const fd = new FormData();
    fd.append('action', editingId ? 'edit' : 'add');
    if (editingId) fd.append('id', editingId);
    fd.append('type', document.getElementById('f_type').value);
    fd.append('name', name);
    fd.append('branch', document.getElementById('f_branch').value);
    fd.append('description', document.getElementById('f_desc').value);
    fd.append('category', document.getElementById('f_category').value);
    fd.append('sku', document.getElementById('f_sku').value);
    fd.append('price', price);
    fd.append('stock', document.getElementById('f_stock_num').value);
    fd.append('reorder_level', document.getElementById('f_reorder').value);
    fd.append('image_url', document.getElementById('f_image_url').value);
    fd.append('image_delete', document.getElementById('f_image_delete').value);

    const fileInput = document.getElementById('f_image_change').files.length
        ? document.getElementById('f_image_change')
        : document.getElementById('f_image_file');
    if (fileInput.files[0]) fd.append('image_file', fileInput.files[0]);

    fetch('product_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || 'Product saved!', true);
                closeModal();
                setTimeout(() => location.reload(), 900);
            } else {
                showToast(data.message || 'Failed to save product.', false, true);
                btn.disabled = false;
                btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Save Product';
            }
        })
        .catch(() => {
            showToast('Network error. Please try again.', false, true);
            btn.disabled = false;
            btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Save Product';
        });
}

// ── DELETE ──────────────────────────────────────────────────────────────
let deleteId = null;
function confirmDelete(id, name) {
    deleteId = id;
    document.getElementById('confirmDesc').textContent = 'Delete "' + name + '"? This action cannot be undone.';
    document.getElementById('confirmModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeConfirm() {
    document.getElementById('confirmModal').classList.remove('show');
    document.body.style.overflow = '';
    deleteId = null;
}
function doDelete() {
    if (!deleteId) return;
    const btn = document.getElementById('btnConfirmDel');
    btn.disabled = true; btn.textContent = 'Deleting…';
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', deleteId);
    fetch('product_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('Product deleted.', true);
                closeConfirm();
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(data.message || 'Could not delete.', false, true);
                btn.disabled = false; btn.textContent = 'Yes, Delete';
            }
        })
        .catch(() => { showToast('Network error.', false, true); btn.disabled = false; btn.textContent = 'Yes, Delete'; });
}

// ── TOAST ───────────────────────────────────────────────────────────────
let toastTimer;
function showToast(msg, success = true, isErr = false) {
    const toast = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    toast.className = isErr ? 'error show' : (success ? 'success show' : 'show');
    toast.querySelector('.toast-icon').innerHTML = isErr
        ? '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>'
        : '<polyline points="20 6 9 17 4 12"/>';
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 3000);
}

// Keyboard close
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeModal(); closeConfirm(); }
});
</script>
<style>@keyframes spin{to{transform:rotate(360deg)}}.spin{animation:spin 0.7s linear infinite;display:inline-block}</style>
</body>
</html>