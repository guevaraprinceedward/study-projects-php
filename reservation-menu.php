<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';
include_once 'reservation-init.php';

// ── SESSION CHECK (optional - guest allowed) ──────────────────────────────
$isLoggedIn = resValidUserId($conn) > 0;

// ── BRANCH SELECTION (shared with menu.php) ───────────────────────────────
$branch = $_SESSION['branch'] ?? 'laguna';
if (isset($_GET['branch']) && in_array($_GET['branch'], ['laguna', 'manila'])) {
    $branch = $_GET['branch'];
    $_SESSION['branch'] = $branch;
}

// ── FETCH PRODUCTS BY BRANCH ──────────────────────────────────────────────
$st = $conn->prepare("SELECT * FROM products WHERE branch = ? ORDER BY id ASC");
$st->bind_param('s', $branch);
$st->execute();
$result = $st->get_result();
$products = [];
while ($row = $result->fetch_assoc()) { $products[] = $row; }
$st->close();

// Categories per branch — 'packages' is the new Packages Combo category
$categories = $branch === 'manila'
    ? ['all' => 'All Items', 'packages' => 'Packages Combo', 'coffee' => 'Coffee', 'meals' => 'Meals', 'champagnes' => 'Champagnes']
    : ['all' => 'All Items', 'packages' => 'Packages Combo', 'mains' => 'Mains', 'sides' => 'Sides', 'drinks' => 'Drinks', 'desserts' => 'Desserts'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reserve — AyosCoffeeNegosyo</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;
    --gold:#c9a84c;--gold-dim:#8a6f2e;--green:#4a7a3a;--green-lt:#6aaa52;
    --cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8;--shadow:rgba(0,0,0,0.6);
    --sidebar-w:270px;--red:#c0392b;--amber:#d4820a;
}
html{scroll-behavior:smooth}
body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;position:relative;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 10% 0%,rgba(201,168,76,0.06) 0%,transparent 55%),radial-gradient(ellipse 50% 70% at 90% 100%,rgba(74,122,58,0.07) 0%,transparent 55%);pointer-events:none;z-index:0}
:focus-visible{outline:2px solid var(--gold);outline-offset:3px}

/* SIDEBAR OVERLAY */
#sidebarOverlay{position:fixed;inset:0;background:rgba(0,0,0,0);z-index:199;pointer-events:none;transition:background 0.4s ease}
#sidebarOverlay.active{background:rgba(0,0,0,0.55);pointer-events:all}

/* SIDEBAR */
#sidebar{position:fixed;top:0;left:0;width:var(--sidebar-w);height:100vh;background:#111109;border-right:1px solid var(--border);z-index:200;display:flex;flex-direction:column;transform:translateX(calc(-1 * var(--sidebar-w)));transition:transform 0.42s cubic-bezier(0.4,0,0.2,1);will-change:transform}
#sidebar.open{transform:translateX(0);box-shadow:6px 0 40px rgba(0,0,0,0.7)}
.sb-header{padding:28px 24px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.sb-brand{display:flex;align-items:center;gap:11px}
.sb-brand-icon{width:34px;height:34px;border:1px solid var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.sb-brand-name{font-family:'Cormorant Garamond',serif;font-size:15px;font-weight:600;color:var(--cream);letter-spacing:0.03em;line-height:1.2}
.sb-brand-name span{display:block;font-size:11px;font-weight:400;font-family:'Jost',sans-serif;color:var(--gold);letter-spacing:0.1em;text-transform:uppercase}
.sb-close{width:30px;height:30px;border:1px solid var(--border);border-radius:50%;background:transparent;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:color 0.2s,border-color 0.2s,background 0.2s;flex-shrink:0}
.sb-close:hover{color:var(--cream);border-color:var(--gold-dim);background:rgba(201,168,76,0.06)}
.sb-nav{flex:1;padding:20px 16px;display:flex;flex-direction:column;gap:4px;overflow-y:auto}
.sb-nav-label{font-size:9.5px;letter-spacing:0.18em;text-transform:uppercase;color:var(--muted);padding:4px 10px 10px;margin-top:6px}
.sb-link{display:flex;align-items:center;gap:13px;padding:11px 14px;border-radius:6px;text-decoration:none;color:var(--muted);font-size:13.5px;font-weight:400;letter-spacing:0.02em;transition:color 0.2s,background 0.2s;position:relative;border:1px solid transparent}
.sb-link:hover{color:var(--cream);background:rgba(255,255,255,0.04)}
.sb-link.active{color:var(--gold);background:rgba(201,168,76,0.08);border-color:rgba(201,168,76,0.12)}
.sb-link svg{flex-shrink:0;opacity:0.7;transition:opacity 0.2s}
.sb-link:hover svg,.sb-link.active svg{opacity:1}
.sb-badge{margin-left:auto;background:var(--gold);color:#1a1400;font-size:10px;font-weight:700;min-width:20px;height:20px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;padding:0 6px}
.sb-divider{height:1px;background:var(--border);margin:8px 0}
.sb-footer{padding:16px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:4px}
.sb-logout{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:6px;text-decoration:none;color:#c0574a;font-size:13.5px;font-weight:400;letter-spacing:0.02em;transition:color 0.2s,background 0.2s;border:1px solid transparent}
.sb-logout:hover{color:#e06b5d;background:rgba(192,87,74,0.08);border-color:rgba(192,87,74,0.14)}
.sb-login{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:6px;text-decoration:none;color:var(--green-lt);font-size:13.5px;font-weight:400;letter-spacing:0.02em;transition:color 0.2s,background 0.2s;border:1px solid transparent}
.sb-login:hover{color:#fff;background:rgba(74,122,58,0.12);border-color:rgba(74,122,58,0.2)}
.sb-register{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:6px;text-decoration:none;color:var(--gold);font-size:13.5px;font-weight:400;letter-spacing:0.02em;transition:color 0.2s,background 0.2s;border:1px solid transparent}
.sb-register:hover{color:#fff;background:rgba(201,168,76,0.1);border-color:rgba(201,168,76,0.2)}

/* BRANCH SWITCHER */
.branch-bar{position:relative;z-index:1;display:flex;justify-content:center;gap:10px;padding:20px 32px 0}
.branch-btn{display:flex;align-items:center;gap:8px;padding:10px 24px;border-radius:100px;border:1px solid var(--border);background:transparent;color:var(--muted);font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;cursor:pointer;text-decoration:none;transition:all 0.25s ease}
.branch-btn:hover{border-color:var(--gold-dim);color:var(--gold);background:rgba(201,168,76,0.05)}
.branch-btn.active{border-color:var(--gold);color:var(--gold);background:rgba(201,168,76,0.1)}
.branch-dot{width:7px;height:7px;border-radius:50%;background:var(--green-lt)}
.branch-btn.active .branch-dot{background:var(--gold)}

/* HEADER */
header{position:sticky;top:0;z-index:100;background:rgba(11,11,9,0.88);backdrop-filter:blur(18px);border-bottom:1px solid var(--border)}
.header-inner{max-width:1200px;margin:0 auto;padding:0 32px;height:68px;display:flex;align-items:center;justify-content:space-between}
.header-left{display:flex;align-items:center;gap:18px}
#sidebarToggle{width:40px;height:40px;border:1px solid var(--border);border-radius:6px;background:transparent;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:color 0.2s,border-color 0.2s,background 0.2s}
#sidebarToggle:hover{color:var(--gold);border-color:var(--gold-dim);background:rgba(201,168,76,0.06)}
.hb{display:flex;flex-direction:column;gap:5px;width:18px}
.hb span{display:block;height:1.5px;background:currentColor;border-radius:2px;transition:transform 0.3s ease,opacity 0.3s ease,width 0.3s ease;transform-origin:center}
.hb span:nth-child(3){width:12px}
#sidebarToggle.open .hb span:nth-child(1){transform:translateY(6.5px) rotate(45deg)}
#sidebarToggle.open .hb span:nth-child(2){opacity:0;transform:scaleX(0)}
#sidebarToggle.open .hb span:nth-child(3){transform:translateY(-6.5px) rotate(-45deg);width:18px}
.brand{display:flex;align-items:center;gap:12px;text-decoration:none}
.brand-icon{width:36px;height:36px;border:1px solid var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center}
.brand-name{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream);letter-spacing:0.04em}
.brand-name span{color:var(--gold)}
.header-right{display:flex;align-items:center;gap:10px}
.header-home-link{display:flex;align-items:center;gap:7px;padding:7px 16px;border:1px solid var(--border);border-radius:100px;color:var(--muted);font-size:12.5px;font-weight:500;letter-spacing:0.06em;text-decoration:none;transition:all 0.2s}
.header-home-link:hover{color:var(--gold);border-color:var(--gold-dim);background:rgba(201,168,76,0.06)}
.header-cart-pill{display:flex;align-items:center;gap:8px;padding:7px 16px;border:1px solid var(--border);border-radius:100px;background:transparent;color:var(--muted);font-family:'Jost',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:0.06em;text-decoration:none;transition:color 0.2s,border-color 0.2s,background 0.2s;cursor:pointer}
.header-cart-pill:hover{color:var(--gold);border-color:var(--gold-dim);background:rgba(201,168,76,0.06)}
.header-cart-count{background:var(--gold);color:#1a1400;font-size:10px;font-weight:700;min-width:18px;height:18px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;padding:0 4px}
@media(max-width:640px){.header-home-link{display:none}}

/* HERO */
.hero{position:relative;z-index:1;text-align:center;padding:60px 32px 30px;max-width:700px;margin:0 auto}
.hero-eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:11px;letter-spacing:0.2em;text-transform:uppercase;color:var(--gold);margin-bottom:22px}
.hero-eyebrow::before,.hero-eyebrow::after{content:'';width:28px;height:1px;background:var(--gold-dim)}
.hero h1{font-family:'Cormorant Garamond',serif;font-size:clamp(44px,6vw,72px);font-weight:700;line-height:1.08;color:var(--cream);letter-spacing:-0.01em;margin-bottom:18px}
.hero h1 em{font-style:italic;color:var(--gold)}
.hero p{font-size:15px;color:var(--muted);line-height:1.7;font-weight:300}
.branch-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(201,168,76,0.08);border:1px solid var(--gold-dim);border-radius:100px;padding:5px 14px;font-size:11px;letter-spacing:0.1em;text-transform:uppercase;color:var(--gold);margin-top:14px}

/* RESERVATION NOTICE */
.mode-note{position:relative;z-index:1;max-width:720px;margin:0 auto 30px;padding:0 32px}
.mode-note-inner{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;background:rgba(201,168,76,0.06);border:1px solid var(--gold-dim);border-radius:4px;padding:12px 18px;font-size:12.5px;color:var(--muted);line-height:1.6}
.mode-note-inner strong{color:var(--gold);font-weight:500}
.mode-note-inner a{color:var(--gold);text-decoration:none;white-space:nowrap}
.mode-note-inner a:hover{text-decoration:underline}

/* FILTER */
.filter-bar{position:relative;z-index:1;display:flex;justify-content:center;gap:8px;padding:0 32px 48px;flex-wrap:wrap}
.filter-btn{font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;padding:8px 20px;border-radius:100px;border:1px solid var(--border);background:transparent;color:var(--muted);cursor:pointer;transition:all 0.25s ease}
.filter-btn:hover,.filter-btn.active{border-color:var(--gold);color:var(--gold);background:rgba(201,168,76,0.07)}

/* MENU GRID */
.menu-section{position:relative;z-index:1;max-width:1200px;margin:0 auto;padding:0 32px 60px}
.menu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:24px;transition:opacity 0.25s ease}
.menu-grid.fading{opacity:0}

/* CARD */
.menu-card{background:var(--card);border:1px solid var(--border);border-radius:4px;overflow:hidden;display:flex;flex-direction:column;opacity:0;transform:translateY(18px) rotateX(-4deg);transform-origin:top center;transition:opacity 0.4s ease,transform 0.4s cubic-bezier(0.2,0.8,0.3,1),border-color 0.3s ease,box-shadow 0.3s ease}
.menu-card.visible{opacity:1;transform:translateY(0) rotateX(0)}
.menu-card:hover{border-color:var(--gold-dim);box-shadow:0 24px 55px rgba(0,0,0,0.55),0 0 0 1px rgba(201,168,76,0.08)}
.menu-card.visible:hover{transform:translateY(-7px) scale(1.012)}
.menu-card.out-of-stock{opacity:0.55}
.menu-card.out-of-stock.visible:hover{transform:none}
.card-img-wrap{position:relative;height:210px;overflow:hidden;background:var(--surface)}
.card-img-wrap img{width:100%;height:100%;object-fit:cover;transition:transform 0.5s ease;display:block}
.menu-card:hover .card-img-wrap img{transform:scale(1.06)}
.card-img-wrap::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 50%,rgba(0,0,0,0.5) 100%);pointer-events:none}
.img-placeholder{width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;color:var(--muted);font-size:13px;letter-spacing:0.05em}
.img-placeholder svg{opacity:0.3}
.oos-badge{position:absolute;top:12px;left:12px;z-index:2;background:rgba(192,57,43,0.9);color:#fff;font-size:10px;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;padding:4px 10px;border-radius:3px}
.card-body{padding:22px 24px 24px;display:flex;flex-direction:column;flex:1;gap:6px}
.card-category{font-size:10px;letter-spacing:0.18em;text-transform:uppercase;color:var(--gold);font-weight:500}
.card-name{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream);line-height:1.2;margin-bottom:4px}
.card-desc{font-size:13px;color:var(--muted);line-height:1.6;font-weight:300;flex:1;margin-bottom:10px}
.stock-wrap{margin-bottom:10px}
.stock-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:5px}
.stock-label{font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted)}
.stock-text{font-size:10px;font-weight:500}
.stock-text.s-green{color:var(--green-lt)}
.stock-text.s-amber{color:var(--amber)}
.stock-text.s-red{color:var(--red)}
.stock-bar-bg{height:3px;background:var(--border);border-radius:2px;overflow:hidden}
.stock-bar-fill{height:100%;border-radius:2px;transition:width 0.4s ease}
.stock-bar-fill.s-green{background:var(--green-lt)}
.stock-bar-fill.s-amber{background:var(--amber)}
.stock-bar-fill.s-red{background:var(--red)}
.card-footer{display:flex;align-items:center;justify-content:space-between;padding-top:16px;border-top:1px solid var(--border);margin-top:auto}
.card-price{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--gold);letter-spacing:-0.01em}
.card-price span{font-size:14px;font-weight:400;font-family:'Jost',sans-serif;color:var(--muted);margin-right:2px}
.add-btn{display:flex;align-items:center;gap:8px;padding:10px 18px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:#fff;cursor:pointer;transition:background 0.2s,transform 0.15s;position:relative;overflow:hidden}
.add-btn:hover{background:var(--green-lt)}
.add-btn:active{transform:scale(0.95)}
.add-btn.added{background:var(--gold);color:#000}
.add-btn:disabled{background:var(--muted);cursor:not-allowed;opacity:0.5}
.add-btn .ripple{position:absolute;border-radius:50%;background:rgba(255,255,255,0.3);transform:scale(0);animation:ripple 0.5s linear;pointer-events:none}
@keyframes ripple{to{transform:scale(4);opacity:0}}

/* PACKAGES COMBO — its own look so a bundle reads differently from a single item */
.menu-card[data-category="packages"]{border-color:rgba(201,168,76,0.35)}
.menu-card[data-category="packages"] .card-img-wrap::before{content:'Package Combo';position:absolute;top:12px;right:12px;z-index:2;background:var(--gold);color:#1a1400;font-size:10px;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;padding:4px 10px;border-radius:3px}
.menu-card[data-category="packages"] .card-desc{color:var(--text);opacity:.8}

/* EMPTY */
.empty-state{text-align:center;padding:80px 20px;color:var(--muted)}
.empty-state svg{opacity:0.2;margin-bottom:20px}
.empty-state h3{font-family:'Cormorant Garamond',serif;font-size:26px;color:var(--text);margin-bottom:8px}

/* PAGINATION */
.pagination-wrap{position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;gap:14px;padding:32px 32px 80px}
.pagination-info{font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted)}
.pagination{display:flex;align-items:center;gap:6px;flex-wrap:wrap;justify-content:center}
.page-btn{font-family:'Jost',sans-serif;font-size:13px;font-weight:500;min-width:38px;height:38px;padding:0 12px;border-radius:3px;border:1px solid var(--border);background:transparent;color:var(--muted);cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:all 0.2s ease}
.page-btn:hover:not(:disabled){border-color:var(--gold-dim);color:var(--gold);background:rgba(201,168,76,0.07)}
.page-btn.active{border-color:var(--gold);color:var(--gold);background:rgba(201,168,76,0.12)}
.page-btn:disabled{opacity:0.3;cursor:not-allowed}
.page-ellipsis{color:var(--muted);padding:0 4px;font-size:14px;line-height:38px}

/* TOAST */
#toast{position:fixed;bottom:30px;right:30px;z-index:999;background:var(--card);border:1px solid var(--gold-dim);border-radius:4px;padding:14px 20px;display:flex;align-items:center;gap:12px;font-size:14px;color:var(--cream);box-shadow:0 8px 32px rgba(0,0,0,0.5);transform:translateY(20px);opacity:0;transition:all 0.3s ease;pointer-events:none;max-width:min(380px,calc(100vw - 40px))}
#toast.show{transform:translateY(0);opacity:1}
#toast .t-icon{color:var(--green-lt);flex-shrink:0}
#toast.error .t-icon{color:var(--red)}

/* BREWING OVERLAY */
#brewOverlay{position:fixed;inset:0;z-index:400;display:flex;align-items:center;justify-content:center;background:rgba(6,6,5,0.86);backdrop-filter:blur(10px);opacity:0;pointer-events:none;transition:opacity 0.3s ease}
#brewOverlay.show{opacity:1;pointer-events:all}
.brew-card{text-align:center;max-width:280px}
.brew-icon-stage{position:relative;width:120px;height:120px;margin:0 auto 26px;display:flex;align-items:center;justify-content:center}
.brew-ring{position:absolute;inset:0;border-radius:50%;border:2px solid rgba(201,168,76,0.15);border-top-color:var(--gold);animation:brewspin 1.1s linear infinite}
@keyframes brewspin{to{transform:rotate(360deg)}}
.brew-icon{color:var(--gold);animation:brewpulse 1.4s ease-in-out infinite}
@keyframes brewpulse{0%,100%{transform:scale(1);opacity:0.85}50%{transform:scale(1.1);opacity:1}}
.brew-title{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;color:var(--cream);margin-bottom:6px}
.brew-sub{font-size:12.5px;color:var(--muted);letter-spacing:0.04em}

footer{position:relative;z-index:1;border-top:1px solid var(--border);padding:30px 32px;text-align:center}
footer p{font-size:12px;color:var(--muted);letter-spacing:0.06em}
footer p span{color:var(--gold-dim)}
footer a{color:var(--gold);text-decoration:none;transition:color 0.2s ease}
footer a:hover{color:var(--cream);text-decoration:underline}

@media(max-width:640px){
    .header-inner{padding:0 16px}
    .brand-name{font-size:18px}
    .hero{padding:50px 20px 24px}
    .mode-note{padding:0 16px}
    .menu-section{padding:0 16px 40px}
    .menu-grid{grid-template-columns:1fr;gap:16px}
    .pagination-wrap{padding:24px 16px 60px}
    .branch-bar{padding:16px 16px 0}
}
@media (prefers-reduced-motion: reduce){*{animation-duration:.01ms !important;transition-duration:.01ms !important}}
</style>
</head>
<body>

<div id="sidebarOverlay"></div>

<aside id="sidebar" role="navigation" aria-label="Main navigation">
    <div class="sb-header">
        <div class="sb-brand">
            <div class="sb-brand-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
            </div>
            <div class="sb-brand-name">AyosCoffeeNegosyo<span>Est. 2026</span></div>
        </div>
        <button class="sb-close" id="sidebarClose" aria-label="Close sidebar">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    <nav class="sb-nav">
        <div class="sb-nav-label">Navigation</div>
        <a href="index.php" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Home
        </a>
        <a href="order-type.php" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
            Change Order Type
        </a>
        <a href="menu.php" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            Serve Menu
        </a>
        <a href="reservation-menu.php" class="sb-link active">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Reservation Menu
        </a>
        <a href="testimonial.php" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Testimonials
        </a>
        <a href="includes/about-us.php" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            About Us
        </a>

        <div class="sb-divider"></div>
        <div class="sb-nav-label">Branch</div>
        <a href="?branch=laguna" class="sb-link <?= $branch === 'laguna' ? 'active' : '' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Laguna Branch
        </a>
        <a href="?branch=manila" class="sb-link <?= $branch === 'manila' ? 'active' : '' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Manila Branch
        </a>

        <div class="sb-divider"></div>
        <div class="sb-nav-label">Reservations</div>
        <a href="reservation-cart.php" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            Reservation Cart
            <span class="sb-badge" id="sidebarCartCount">0</span>
        </a>
        <?php if ($isLoggedIn): ?>
        <a href="my-reservations.php" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            My Reservations
        </a>
        <div class="sb-divider"></div>
        <div class="sb-nav-label">Account</div>
        <a href="profile.php" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profile
        </a>
        <a href="orders.php" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            My Orders
        </a>
        <?php endif; ?>
    </nav>

    <div class="sb-footer">
        <?php if ($isLoggedIn): ?>
        <a href="log-out.php" class="sb-logout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
        </a>
        <?php else: ?>
        <a href="log-in.php" class="sb-login">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            Login
        </a>
        <a href="register.php" class="sb-register">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            Sign Up
        </a>
        <?php endif; ?>
    </div>
</aside>

<header>
    <div class="header-inner">
        <div class="header-left">
            <button id="sidebarToggle" aria-label="Open sidebar" aria-expanded="false">
                <div class="hb"><span></span><span></span><span></span></div>
            </button>
            <a href="index.php" class="brand">
                <div class="brand-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
                </div>
                <span class="brand-name">My <span>AyosCoffeeNegosyo</span></span>
            </a>
        </div>
        <div class="header-right">
            <a href="order-type.php" class="header-home-link">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/></svg>
                Order Type
            </a>
            <a href="reservation-cart.php" class="header-cart-pill" aria-label="View reservation cart">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                <span class="header-cart-count" id="cartCount">0</span>
            </a>
        </div>
    </div>
</header>

<!-- Branch Bar -->
<div class="branch-bar">
    <a href="?branch=laguna" class="branch-btn <?= $branch === 'laguna' ? 'active' : '' ?>"><div class="branch-dot"></div>Laguna</a>
    <a href="?branch=manila" class="branch-btn <?= $branch === 'manila' ? 'active' : '' ?>"><div class="branch-dot"></div>Manila</a>
</div>

<section class="hero">
    <div class="hero-eyebrow">Reservation</div>
    <h1>Reserve Your <em>Order</em></h1>
    <p>Pick your favorites and Packages Combo, then choose the date and time you'll arrive.<br>You can pay now or pay later.</p>
    <div class="branch-badge">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        <?= ucfirst($branch) ?> Branch
    </div>
</section>

<div class="mode-note">
    <div class="mode-note-inner">
        <span><strong>Reservation mode.</strong> One reservation is for one branch. Add items, then set your date and time in the reservation cart.</span>
        <a href="menu.php">Want it served now? Go to the serve menu</a>
    </div>
</div>

<div class="filter-bar">
    <?php foreach ($categories as $key => $label): ?>
    <button class="filter-btn <?= $key === 'all' ? 'active' : '' ?>" data-filter="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></button>
    <?php endforeach; ?>
</div>

<main class="menu-section">
    <?php if (empty($products)): ?>
        <div class="empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
            <h3>No dishes available yet.</h3>
            <p>Check back soon — the kitchen is preparing something special.</p>
        </div>
    <?php else: ?>
        <div class="menu-grid" id="menuGrid">
        <?php foreach ($products as $row):
            $stock   = (int)($row['stock'] ?? 100);
            $reorder = (int)($row['reorder_level'] ?? 10);
            $pct     = max(0, min(100, ($stock / 100) * 100));
            $isOos   = $stock <= 0;
            $isLow   = !$isOos && $stock <= $reorder;
            $cls     = $isOos ? 's-red' : ($isLow ? 's-amber' : 's-green');
            $txt     = $isOos ? 'Out of stock' : ($isLow ? "Low — {$stock} left" : "{$stock} in stock");
            $cat     = $row['category'] ?? 'mains';
            $catLabel = $cat === 'packages' ? 'Packages Combo' : $cat;
        ?>
            <div class="menu-card <?= $isOos ? 'out-of-stock' : '' ?>"
                 data-category="<?= htmlspecialchars($cat) ?>"
                 data-stock="<?= $stock ?>">
                <div class="card-img-wrap">
                    <?php if ($isOos): ?><div class="oos-badge">Out of Stock</div><?php endif; ?>
                    <?php if (!empty($row['image'])): ?>
                        <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'img-placeholder\'><svg width=\'40\' height=\'40\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'1\'><path d=\'M3 11l19-9-9 19-2-8-8-2z\'/></svg><span>No Image</span></div>'">
                    <?php else: ?>
                        <div class="img-placeholder">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
                            <span>No Image</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($row['category'])): ?>
                        <div class="card-category"><?= htmlspecialchars($catLabel) ?></div>
                    <?php endif; ?>
                    <h3 class="card-name"><?= htmlspecialchars($row['name']) ?></h3>
                    <p class="card-desc"><?= !empty($row['description']) ? htmlspecialchars($row['description']) : 'A signature dish from our kitchen, prepared fresh to order.' ?></p>
                    <div class="stock-wrap">
                        <div class="stock-top">
                            <span class="stock-label">Stock</span>
                            <span class="stock-text <?= $cls ?>"><?= $txt ?></span>
                        </div>
                        <div class="stock-bar-bg">
                            <div class="stock-bar-fill <?= $cls ?>" style="width:<?= $pct ?>%"></div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="card-price"><span>₱</span><?= number_format((float)$row['price'], 2) ?></div>
                        <?php if ($isOos): ?>
                            <button class="add-btn" disabled>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                Unavailable
                            </button>
                        <?php else: ?>
                            <button class="add-btn" onclick="addToReservation(<?= (int)$row['id'] ?>, this)">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Reserve
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<div class="pagination-wrap" id="paginationWrap" style="display:none;">
    <div class="pagination-info" id="paginationInfo"></div>
    <div class="pagination" id="pagination"></div>
</div>

<div id="toast">
    <svg class="t-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    <span id="toastMsg">Added to reservation!</span>
</div>

<!-- Brewing / making overlay -->
<div id="brewOverlay">
    <div class="brew-card">
        <div class="brew-icon-stage">
            <div class="brew-ring"></div>
            <svg class="brew-icon" id="brewIcon" width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"></svg>
        </div>
        <div class="brew-title" id="brewTitle">Reserving your order…</div>
        <div class="brew-sub" id="brewSub">Adding to reservation</div>
    </div>
</div>

<footer>
    <p>© 2026 <span>My AyosCoffeeNegosyo</span> — All rights reserved.</p>
    <p>Developed by: <a href="https://www.instagram.com/_theprnx.gvara/" target="_blank" rel="noopener noreferrer">Prince Edward Guevara</a></p>
</footer>

<!-- Shared category -> icon map (also used by menu.php and checkout.php) -->
<script src="brew-icons.js"></script>
<script>
// Sidebar
const sidebar=document.getElementById('sidebar'),sidebarOverlay=document.getElementById('sidebarOverlay'),sidebarToggle=document.getElementById('sidebarToggle'),sidebarClose=document.getElementById('sidebarClose');
function openSidebar(){sidebar.classList.add('open');sidebarOverlay.classList.add('active');sidebarToggle.classList.add('open');sidebarToggle.setAttribute('aria-expanded','true');document.body.style.overflow='hidden'}
function closeSidebar(){sidebar.classList.remove('open');sidebarOverlay.classList.remove('active');sidebarToggle.classList.remove('open');sidebarToggle.setAttribute('aria-expanded','false');document.body.style.overflow=''}
sidebarToggle.addEventListener('click',()=>sidebar.classList.contains('open')?closeSidebar():openSidebar());
sidebarClose.addEventListener('click',closeSidebar);
sidebarOverlay.addEventListener('click',closeSidebar);
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeSidebar()});

// Grid
const ITEMS_PER_PAGE=6;
let currentPage=1,currentFilter='all';
const allCards=Array.from(document.querySelectorAll('.menu-card'));
const grid=document.getElementById('menuGrid');

function renderGrid(animate=true){
    if(!grid) return;
    const filtered=currentFilter==='all'?allCards:allCards.filter(c=>c.dataset.category===currentFilter);
    const totalItems=filtered.length,totalPages=Math.max(1,Math.ceil(totalItems/ITEMS_PER_PAGE));
    if(currentPage>totalPages)currentPage=totalPages;
    const start=(currentPage-1)*ITEMS_PER_PAGE,end=start+ITEMS_PER_PAGE,pageCards=filtered.slice(start,end);
    if(animate){
        grid.classList.add('fading');
        setTimeout(()=>{applyVis(allCards,pageCards);grid.classList.remove('fading');pageCards.forEach((c,i)=>{c.classList.remove('visible');setTimeout(()=>c.classList.add('visible'),i*60)})},250);
    }else{applyVis(allCards,pageCards);pageCards.forEach((c,i)=>setTimeout(()=>c.classList.add('visible'),i*60))}
    renderPagUI(totalItems,totalPages,start,end);
}
function applyVis(all,pageCards){all.forEach(c=>{c.classList.remove('visible');c.style.display='none'});pageCards.forEach(c=>c.style.display='')}
function renderPagUI(totalItems,totalPages,start,end){
    const wrap=document.getElementById('paginationWrap'),infoEl=document.getElementById('paginationInfo'),paginEl=document.getElementById('pagination');
    wrap.style.display=totalPages<=1?'none':'flex';
    infoEl.textContent=totalItems?('Showing '+(start+1)+'–'+Math.min(end,totalItems)+' of '+totalItems+' items'):'No items in this category yet';
    paginEl.innerHTML='';
    const prev=mkBtn('←',currentPage===1);
    prev.addEventListener('click',()=>{currentPage--;renderGrid();scrollUp()});paginEl.appendChild(prev);
    for(let i=1;i<=totalPages;i++){
        if(totalPages>7&&i>2&&i<totalPages-1&&Math.abs(i-currentPage)>1){if(i===3||i===totalPages-2){const el=document.createElement('span');el.className='page-ellipsis';el.textContent='…';paginEl.appendChild(el)}continue}
        const btn=mkBtn(i,false,i===currentPage);btn.addEventListener('click',()=>{currentPage=i;renderGrid();scrollUp()});paginEl.appendChild(btn);
    }
    const next=mkBtn('→',currentPage===totalPages);next.addEventListener('click',()=>{currentPage++;renderGrid();scrollUp()});paginEl.appendChild(next);
}
function mkBtn(label,disabled,active){const btn=document.createElement('button');btn.className='page-btn'+(active?' active':'');btn.textContent=label;btn.disabled=!!disabled;return btn}
function scrollUp(){document.querySelector('.filter-bar').scrollIntoView({behavior:'smooth',block:'start'})}

document.querySelectorAll('.filter-btn').forEach(btn=>{
    btn.addEventListener('click',()=>{
        document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');currentFilter=btn.dataset.filter;currentPage=1;renderGrid(true);
    });
});

// Reservation cart badge
function setCartCount(c){
    document.getElementById('cartCount').textContent=c;
    const sb=document.getElementById('sidebarCartCount');
    if(sb)sb.textContent=c;
}
function updateCartBadge(){
    fetch('reservation_handler.php?action=count').then(r=>r.json()).then(data=>setCartCount(data.count||0)).catch(()=>{});
}
updateCartBadge();

// Brewing overlay (icon/label per category — packages have their own "combo" icon)
function showBrewOverlay(category){
    const key = iconFor(category);
    document.getElementById('brewIcon').innerHTML = BREW_ICON_PATHS[key];
    document.getElementById('brewSub').textContent = BREW_LABELS[key];
    document.getElementById('brewOverlay').classList.add('show');
}
function hideBrewOverlay(){document.getElementById('brewOverlay').classList.remove('show')}

// Add to the RESERVATION cart
function addToReservation(id,btn){
    const ripple=document.createElement('span');ripple.className='ripple';
    const rect=btn.getBoundingClientRect(),size=Math.max(rect.width,rect.height);
    ripple.style.cssText='width:'+size+'px;height:'+size+'px;left:'+((rect.width-size)/2)+'px;top:'+((rect.height-size)/2)+'px';
    btn.appendChild(ripple);setTimeout(()=>ripple.remove(),600);
    btn.disabled=true;

    const card=btn.closest('.menu-card');
    const category=card.dataset.category||'mains';
    showBrewOverlay(category);
    const brewStart=Date.now();
    const MIN_BREW_MS=900;

    fetch('reservation_handler.php?action=add&id='+id).then(r=>r.json()).then(data=>{
        const wait=Math.max(0,MIN_BREW_MS-(Date.now()-brewStart));
        setTimeout(()=>{
            hideBrewOverlay();
            if(data.success){
                setCartCount(data.count||0);
                // server tells us how many are still available after what's in the reservation cart
                const stock=(typeof data.stock==='number')?data.stock:Math.max(0,parseInt(card.dataset.stock||'100')-1);
                card.dataset.stock=stock;
                updateStockUI(card,stock);
                const orig=btn.innerHTML;
                btn.classList.add('added');
                btn.innerHTML='<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Added';
                setTimeout(()=>{
                    btn.classList.remove('added');
                    if(stock<=0){
                        btn.innerHTML='<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg> Unavailable';
                        btn.disabled=true;
                    }else{btn.innerHTML=orig;btn.disabled=false}
                },1400);
                showToast('Added to your reservation!',false);
            }else{btn.disabled=false;showToast(data.message||'Error — please try again.',true)}
        },wait);
    }).catch(()=>{hideBrewOverlay();btn.disabled=false;showToast('Error — please try again.',true)});
}

function updateStockUI(card,stock){
    const reorder=10,pct=Math.max(0,Math.min(100,(stock/100)*100));
    const isOos=stock<=0,isLow=!isOos&&stock<=reorder;
    const cls=isOos?'s-red':(isLow?'s-amber':'s-green');
    const txt=isOos?'Out of stock':(isLow?'Low — '+stock+' left':stock+' in stock');
    const fill=card.querySelector('.stock-bar-fill'),stxt=card.querySelector('.stock-text');
    if(fill){fill.style.width=pct+'%';fill.className='stock-bar-fill '+cls}
    if(stxt){stxt.textContent=txt;stxt.className='stock-text '+cls}
    if(isOos){
        card.classList.add('out-of-stock');
        const iw=card.querySelector('.card-img-wrap');
        if(iw&&!iw.querySelector('.oos-badge')){const b=document.createElement('div');b.className='oos-badge';b.textContent='Out of Stock';iw.appendChild(b)}
    }
}

let toastTimer;
function showToast(msg,isError=false){
    const toast=document.getElementById('toast');
    document.getElementById('toastMsg').textContent=msg;
    toast.className=isError?'error':'';toast.classList.add('show');
    clearTimeout(toastTimer);toastTimer=setTimeout(()=>toast.classList.remove('show'),2800);
}

renderGrid(false);
</script>
</body>
</html>