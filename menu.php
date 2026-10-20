<?php
/**
 * menu.php (v4 — premium redesign) — serve menu.
 * "+ Add" opens the customization modal first (size, hot/iced, sugar, add-ons, note, qty,
 * dine-in / take-out / pick-up / delivery). Only after "Add to cart" is the item put in the cart.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';
include_once 'customize-lib.php';

// ── SESSION CHECK (optional - guest allowed) ──────────────────────────────
$isLoggedIn = false;
if (isset($_SESSION["user"])) {
    $uid = (int)($_SESSION["user"]["id"] ?? 0);
    $un  = (string)($_SESSION["user"]["username"] ?? '');
    $st  = $conn->prepare("SELECT id FROM users WHERE id = ? AND username = ? LIMIT 1");
    $st->bind_param('is', $uid, $un);
    $st->execute(); $st->store_result();
    if ($st->num_rows > 0) $isLoggedIn = true; else { session_unset(); session_destroy(); session_start(); }
    $st->close();
}

// ── BRANCH ────────────────────────────────────────────────────────────────
$branch = $_SESSION['branch'] ?? 'laguna';
if (isset($_GET['branch']) && in_array($_GET['branch'], ['laguna', 'manila'])) {
    $branch = $_GET['branch'];
    $_SESSION['branch'] = $branch;
}

// ── PRODUCTS + which ones can be customized (have sizes) ──────────────────
$st = $conn->prepare("SELECT p.*, (SELECT COUNT(*) FROM product_sizes s WHERE s.product_id = p.id) AS size_count
                      FROM products p WHERE p.branch = ? ORDER BY p.id ASC");
$st->bind_param('s', $branch);
$st->execute();
$products = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

$categories = $branch === 'manila'
    ? ['all' => 'All Items', 'coffee' => 'Coffee', 'meals' => 'Meals', 'champagnes' => 'Champagnes']
    : ['all' => 'All Items', 'mains' => 'Mains', 'sides' => 'Sides', 'drinks' => 'Drinks', 'desserts' => 'Desserts'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Now — SIPPERÉ Café</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;--gold:#c9a84c;--gold-dim:#8a6f2e;--green:#4a7a3a;--green-lt:#6aaa52;--cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8;--sidebar-w:270px;--red:#c0392b;--amber:#d4820a}
html{scroll-behavior:smooth}
body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;position:relative;overflow-x:hidden;-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 10% 0%,rgba(201,168,76,.07) 0%,transparent 55%),radial-gradient(ellipse 50% 70% at 90% 100%,rgba(74,122,58,.07) 0%,transparent 55%),radial-gradient(ellipse 100% 60% at 50% 50%,transparent 60%,rgba(0,0,0,.35) 100%);pointer-events:none;z-index:0}
:focus-visible{outline:2px solid var(--gold);outline-offset:3px}

/* ══ SIDEBAR ══ */
#sidebarOverlay{position:fixed;inset:0;background:rgba(0,0,0,0);z-index:199;pointer-events:none;transition:background .4s}
#sidebarOverlay.active{background:rgba(0,0,0,.6);pointer-events:all}
#sidebar{position:fixed;top:0;left:0;width:var(--sidebar-w);height:100vh;background:linear-gradient(180deg,#131310,#0e0e0b);border-right:1px solid var(--border);z-index:200;display:flex;flex-direction:column;transform:translateX(calc(-1 * var(--sidebar-w)));transition:transform .42s cubic-bezier(.4,0,.2,1)}
#sidebar.open{transform:translateX(0);box-shadow:6px 0 40px rgba(0,0,0,.7)}
.sb-header{padding:28px 24px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;position:relative}
.sb-header::after{content:'';position:absolute;left:24px;right:24px;bottom:-1px;height:1px;background:linear-gradient(90deg,var(--gold-dim),transparent)}
.sb-brand{display:flex;align-items:center;gap:11px}
.sb-brand-icon{width:34px;height:34px;border:1px solid var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center}
.sb-brand-name{font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:600;color:var(--cream);line-height:1.2}
.sb-brand-name span{display:block;font-size:10px;font-weight:400;font-family:'Jost',sans-serif;color:var(--gold);letter-spacing:.18em;text-transform:uppercase;margin-top:2px}
.sb-close{width:30px;height:30px;border:1px solid var(--border);border-radius:50%;background:transparent;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s}
.sb-close:hover{color:var(--cream);border-color:var(--gold-dim)}
.sb-nav{flex:1;padding:20px 16px;display:flex;flex-direction:column;gap:3px;overflow-y:auto}
.sb-nav-label{font-size:9.5px;letter-spacing:.2em;text-transform:uppercase;color:var(--gold-dim);padding:4px 10px 9px;margin-top:6px}
.sb-link{display:flex;align-items:center;gap:13px;padding:10px 14px;border-radius:5px;text-decoration:none;color:var(--muted);font-size:13.5px;border:1px solid transparent;transition:all .2s}
.sb-link:hover{color:var(--cream);background:rgba(255,255,255,.04);padding-left:18px}
.sb-link.active{color:var(--gold);background:rgba(201,168,76,.08);border-color:rgba(201,168,76,.14)}
.sb-badge{margin-left:auto;background:var(--gold);color:#1a1400;font-size:10px;font-weight:700;min-width:20px;height:20px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;padding:0 6px}
.sb-divider{height:1px;background:var(--border);margin:8px 0}
.sb-footer{padding:16px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:4px}
.sb-logout,.sb-login,.sb-register{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:6px;text-decoration:none;font-size:13.5px;border:1px solid transparent}
.sb-logout{color:#c0574a}.sb-logout:hover{background:rgba(192,87,74,.08)}
.sb-login{color:var(--green-lt)}.sb-login:hover{background:rgba(74,122,58,.12)}
.sb-register{color:var(--gold)}.sb-register:hover{background:rgba(201,168,76,.1)}

/* ══ TOP STRIP + HEADER ══ */
.top-strip{position:relative;z-index:101;background:#0e0e0b;border-bottom:1px solid var(--border);text-align:center;padding:8px 16px;font-size:10.5px;letter-spacing:.24em;text-transform:uppercase;color:var(--gold-dim)}
.top-strip b{font-weight:500;color:var(--gold)}
.top-strip i{display:inline-block;width:3px;height:3px;background:var(--gold-dim);transform:rotate(45deg);margin:0 12px;vertical-align:middle}
header{position:sticky;top:0;z-index:100;background:rgba(11,11,9,.9);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);border-bottom:1px solid var(--border)}
header::after{content:'';position:absolute;left:0;right:0;bottom:-1px;height:1px;background:linear-gradient(90deg,transparent,rgba(201,168,76,.55),transparent)}
.header-inner{max-width:1240px;margin:0 auto;padding:0 32px;height:70px;display:flex;align-items:center;justify-content:space-between}
.header-left{display:flex;align-items:center;gap:18px}
#sidebarToggle{width:40px;height:40px;border:1px solid var(--border);border-radius:6px;background:transparent;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s}
#sidebarToggle:hover{color:var(--gold);border-color:var(--gold-dim)}
.hb{display:flex;flex-direction:column;gap:5px;width:18px}
.hb span{display:block;height:1.5px;background:currentColor;border-radius:2px;transition:transform .3s,opacity .3s,width .3s;transform-origin:center}
.hb span:nth-child(3){width:12px}
#sidebarToggle.open .hb span:nth-child(1){transform:translateY(6.5px) rotate(45deg)}
#sidebarToggle.open .hb span:nth-child(2){opacity:0;transform:scaleX(0)}
#sidebarToggle.open .hb span:nth-child(3){transform:translateY(-6.5px) rotate(-45deg);width:18px}
.brand{display:flex;align-items:center;gap:12px;text-decoration:none}
.brand-icon{width:36px;height:36px;border:1px solid var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center}
.brand-name{font-family:'Cormorant Garamond',serif;font-size:23px;font-weight:600;color:var(--cream);letter-spacing:.06em}
.brand-name span{color:var(--gold);font-style:italic;font-weight:500}
.header-right{display:flex;align-items:center;gap:10px}
.header-home-link,.header-cart-pill{display:flex;align-items:center;gap:8px;padding:7px 16px;border:1px solid var(--border);border-radius:100px;color:var(--muted);font-size:12.5px;font-weight:500;letter-spacing:.06em;text-decoration:none;transition:all .2s}
.header-home-link:hover,.header-cart-pill:hover{color:var(--gold);border-color:var(--gold-dim)}
.header-cart-count{background:var(--gold);color:#1a1400;font-size:10px;font-weight:700;min-width:18px;height:18px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;padding:0 4px}
@media(max-width:640px){.header-home-link{display:none}.top-strip i,.top-strip .ts-extra{display:none}}

/* ══ BRANCH BAR ══ */
.branch-bar{position:relative;z-index:1;display:flex;justify-content:center;gap:10px;padding:30px 32px 0}
.branch-btn{display:flex;align-items:center;gap:8px;padding:10px 24px;border-radius:100px;border:1px solid var(--border);color:var(--muted);font-size:12px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;text-decoration:none;transition:all .25s}
.branch-btn:hover{border-color:var(--gold-dim);color:var(--gold)}
.branch-btn.active{border-color:var(--gold);color:var(--gold);background:rgba(201,168,76,.1)}
.branch-dot{width:7px;height:7px;border-radius:50%;background:var(--green-lt)}.branch-btn.active .branch-dot{background:var(--gold)}

/* ══ HERO ══ */
.hero{position:relative;z-index:1;text-align:center;padding:64px 32px 44px;max-width:780px;margin:0 auto}
.hero-eyebrow{display:inline-flex;align-items:center;gap:14px;font-size:11px;letter-spacing:.32em;text-transform:uppercase;color:var(--gold);margin-bottom:24px;font-weight:500}
.hero-eyebrow::before,.hero-eyebrow::after{content:'';width:44px;height:1px;background:linear-gradient(90deg,transparent,var(--gold-dim))}
.hero-eyebrow::after{transform:scaleX(-1)}
.hero h1{font-family:'Cormorant Garamond',serif;font-size:clamp(46px,6.4vw,82px);font-weight:600;line-height:1.02;color:var(--cream);letter-spacing:-.01em;margin-bottom:6px}
.hero h1 em{font-style:italic;font-weight:500;color:var(--gold)}
.hero-orn{display:flex;align-items:center;justify-content:center;gap:14px;margin:22px auto 22px;max-width:260px}
.hero-orn span{flex:1;height:1px;background:linear-gradient(90deg,transparent,var(--gold-dim))}
.hero-orn span:last-child{transform:scaleX(-1)}
.hero-orn i{width:6px;height:6px;border:1px solid var(--gold);transform:rotate(45deg)}
.hero p{font-size:15px;color:#8a8a76;line-height:1.8;font-weight:300;max-width:520px;margin:0 auto}
.branch-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(201,168,76,.07);border:1px solid var(--gold-dim);border-radius:100px;padding:6px 16px;font-size:10.5px;letter-spacing:.18em;text-transform:uppercase;color:var(--gold);margin-top:24px}
.branch-badge::before{content:'';width:5px;height:5px;border-radius:50%;background:var(--gold)}

/* ══ FILTER ══ */
.filter-bar{position:relative;z-index:1;display:flex;justify-content:center;gap:8px;padding:0 32px 40px;flex-wrap:wrap}
.filter-btn{font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;padding:8px 20px;border-radius:100px;border:1px solid var(--border);background:transparent;color:var(--muted);cursor:pointer;transition:all .25s}
.filter-btn:hover,.filter-btn.active{border-color:var(--gold);color:var(--gold);background:rgba(201,168,76,.07)}

/* ══ SECTION HEAD ══ */
.menu-section{position:relative;z-index:1;max-width:1240px;margin:0 auto;padding:0 32px 60px}
.section-head{display:flex;align-items:center;gap:20px;margin-bottom:30px}
.section-head h2{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:600;font-style:italic;color:var(--cream);white-space:nowrap}
.section-head .sh-line{flex:1;height:1px;background:linear-gradient(90deg,var(--border),transparent)}
.section-head .sh-count{font-size:10.5px;letter-spacing:.2em;text-transform:uppercase;color:var(--gold-dim);white-space:nowrap}

/* ══ GRID + CARDS ══ */
.menu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(296px,1fr));gap:28px;transition:opacity .25s}
.menu-grid.fading{opacity:0}
.menu-card{position:relative;background:linear-gradient(180deg,#1c1c17 0%,#16160f 100%);border:1px solid var(--border);border-radius:6px;overflow:hidden;display:flex;flex-direction:column;opacity:0;transform:translateY(18px);transition:opacity .4s,transform .4s cubic-bezier(.2,.8,.3,1),border-color .3s,box-shadow .3s}
.menu-card::before{content:'';position:absolute;inset:8px;border:1px solid rgba(201,168,76,0);border-radius:3px;pointer-events:none;z-index:3;transition:border-color .45s}
.menu-card.visible{opacity:1;transform:none}
.menu-card:hover{border-color:var(--gold-dim);box-shadow:0 26px 60px rgba(0,0,0,.6),0 0 0 1px rgba(201,168,76,.06)}
.menu-card:hover::before{border-color:rgba(201,168,76,.26)}
.menu-card.visible:hover{transform:translateY(-6px)}
.menu-card.out-of-stock{opacity:.55}
.card-img-wrap{position:relative;aspect-ratio:16/11;overflow:hidden;background:var(--surface)}
.card-img-wrap img{width:100%;height:100%;object-fit:cover;transition:transform .7s cubic-bezier(.2,.8,.3,1);display:block}
.menu-card:hover .card-img-wrap img{transform:scale(1.07)}
.card-img-wrap::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,0) 45%,rgba(22,22,15,.85) 100%);pointer-events:none}
.img-placeholder{width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;color:var(--muted);font-size:12px;letter-spacing:.16em;text-transform:uppercase}
.img-placeholder svg{opacity:.3}
.oos-badge{position:absolute;top:16px;left:16px;z-index:4;background:rgba(192,57,43,.92);color:#fff;font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;padding:5px 11px;border-radius:3px}
.cz-badge{position:absolute;top:16px;right:16px;z-index:4;background:rgba(11,11,9,.78);backdrop-filter:blur(6px);border:1px solid var(--gold-dim);color:var(--gold);font-size:9.5px;font-weight:500;letter-spacing:.14em;text-transform:uppercase;padding:5px 11px;border-radius:3px}
.card-body{padding:20px 24px 24px;display:flex;flex-direction:column;flex:1;gap:4px}
.card-category{display:flex;align-items:center;gap:10px;font-size:10px;letter-spacing:.22em;text-transform:uppercase;color:var(--gold);font-weight:500}
.card-category::before{content:'';width:18px;height:1px;background:var(--gold-dim)}
.card-name{font-family:'Cormorant Garamond',serif;font-size:23px;font-weight:600;color:var(--cream);line-height:1.18;margin:4px 0 2px;letter-spacing:.005em}
.card-desc{font-size:13px;color:#7d7d69;line-height:1.7;font-weight:300;flex:1;margin-bottom:14px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:44px}
.stock-wrap{margin-bottom:16px}
.stock-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px}
.stock-label{font-size:9.5px;letter-spacing:.16em;text-transform:uppercase;color:var(--muted)}
.stock-text{font-size:10px;font-weight:500;letter-spacing:.04em}
.s-green{color:var(--green-lt)}.s-amber{color:var(--amber)}.s-red{color:var(--red)}
.stock-bar-bg{height:2px;background:var(--border);border-radius:2px;overflow:hidden}
.stock-bar-fill{height:100%;border-radius:2px;transition:width .4s}
.stock-bar-fill.s-green{background:var(--green-lt)}.stock-bar-fill.s-amber{background:var(--amber)}.stock-bar-fill.s-red{background:var(--red)}
.card-footer{display:flex;align-items:center;justify-content:space-between;padding-top:18px;border-top:1px solid var(--border);margin-top:auto}
.card-price{font-family:'Cormorant Garamond',serif;font-size:27px;font-weight:700;color:var(--gold);line-height:1;letter-spacing:.01em}
.card-price span{font-size:15px;font-weight:400;font-family:'Jost',sans-serif;color:var(--gold-dim);margin-right:3px}
.card-price small{display:block;font-family:'Jost',sans-serif;font-size:9.5px;font-weight:400;color:var(--muted);letter-spacing:.18em;text-transform:uppercase;margin-bottom:5px}
.add-btn{display:flex;align-items:center;gap:8px;padding:10px 18px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:.08em;text-transform:uppercase;color:#fff;cursor:pointer;transition:background .2s,transform .15s}
.add-btn:hover{background:var(--green-lt)}.add-btn:active{transform:scale(.95)}
.add-btn.added{background:var(--gold);color:#000}
.add-btn:disabled{background:var(--muted);cursor:not-allowed;opacity:.5}
.empty-state{text-align:center;padding:90px 20px;color:var(--muted)}
.empty-state h3{font-family:'Cormorant Garamond',serif;font-size:28px;font-style:italic;color:var(--text);margin-bottom:8px}

/* ══ PAGINATION ══ */
.pagination-wrap{position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;gap:14px;padding:20px 32px 90px}
.pagination-info{font-size:10.5px;letter-spacing:.18em;text-transform:uppercase;color:var(--muted)}
.pagination{display:flex;align-items:center;gap:6px;flex-wrap:wrap;justify-content:center}
.page-btn{font-family:'Jost',sans-serif;font-size:13px;font-weight:500;min-width:38px;height:38px;padding:0 12px;border-radius:3px;border:1px solid var(--border);background:transparent;color:var(--muted);cursor:pointer;transition:all .2s}
.page-btn:hover:not(:disabled){border-color:var(--gold-dim);color:var(--gold)}
.page-btn.active{border-color:var(--gold);color:var(--gold);background:rgba(201,168,76,.12)}
.page-btn:disabled{opacity:.3;cursor:not-allowed}
.page-ellipsis{color:var(--muted);padding:0 4px;line-height:38px}

/* ══ TOAST ══ */
#toast{position:fixed;bottom:30px;right:30px;z-index:999;background:var(--card);border:1px solid var(--gold-dim);border-radius:4px;padding:14px 20px;display:flex;align-items:center;gap:12px;font-size:14px;color:var(--cream);box-shadow:0 8px 32px rgba(0,0,0,.5);transform:translateY(20px);opacity:0;transition:all .3s;pointer-events:none;max-width:min(380px,calc(100vw - 40px))}
#toast.show{transform:none;opacity:1}
#toast .t-icon{color:var(--green-lt);flex-shrink:0}
#toast.error .t-icon{color:var(--red)}

/* ══ FOOTER ══ */
footer{position:relative;z-index:1;border-top:1px solid var(--border);padding:44px 32px 36px;text-align:center;background:linear-gradient(180deg,transparent,rgba(0,0,0,.35))}
footer::before{content:'';position:absolute;top:-1px;left:50%;transform:translateX(-50%);width:200px;height:1px;background:linear-gradient(90deg,transparent,var(--gold),transparent)}
.f-brand{font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;color:var(--cream);letter-spacing:.08em;margin-bottom:4px}
.f-brand span{color:var(--gold);font-style:italic}
.f-tag{font-size:10px;letter-spacing:.28em;text-transform:uppercase;color:var(--gold-dim);margin-bottom:22px}
footer p{font-size:12px;color:var(--muted);line-height:1.9}footer p span{color:var(--gold-dim)}
footer a{color:var(--gold);text-decoration:none}footer a:hover{color:var(--cream);text-decoration:underline}

@media(max-width:640px){.header-inner{padding:0 16px}.brand-name{font-size:19px}.hero{padding:48px 20px 36px}.menu-section{padding:0 16px 40px}.menu-grid{grid-template-columns:1fr;gap:20px}.pagination-wrap{padding:20px 16px 60px}.branch-bar{padding:22px 16px 0}.filter-bar{padding:0 16px 32px}.section-head h2{font-size:22px}.top-strip{letter-spacing:.16em;font-size:10px}}
</style>
</head>
<body>

<div id="sidebarOverlay"></div>
<aside id="sidebar" role="navigation" aria-label="Main navigation">
    <div class="sb-header">
        <div class="sb-brand">
            <div class="sb-brand-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg></div>
            <div class="sb-brand-name">AyosCoffeeNegosyo<span>Est. 2026</span></div>
        </div>
        <button class="sb-close" id="sidebarClose" aria-label="Close sidebar">✕</button>
    </div>
    <nav class="sb-nav">
        <div class="sb-nav-label">Navigation</div>
        <a href="index.php" class="sb-link">Home</a>
        <a href="order-type.php" class="sb-link">Change Order Type</a>
        <a href="menu.php" class="sb-link active">Menu</a>
        <a href="testimonial.php" class="sb-link">Testimonials</a>
        <a href="includes/about-us.php" class="sb-link">About Us</a>
        <div class="sb-divider"></div>
        <div class="sb-nav-label">Branch</div>
        <a href="?branch=laguna" class="sb-link <?= $branch === 'laguna' ? 'active' : '' ?>">Laguna Branch</a>
        <a href="?branch=manila" class="sb-link <?= $branch === 'manila' ? 'active' : '' ?>">Manila Branch</a>
        <div class="sb-divider"></div>
        <div class="sb-nav-label">Industry</div>
        <a href="includes/hotel-industry.php" class="sb-link">Hotel Business</a>
        <div class="sb-divider"></div>
        <div class="sb-nav-label"><?= $isLoggedIn ? 'Account' : 'Orders' ?></div>
        <?php if ($isLoggedIn): ?>
        <a href="profile.php" class="sb-link">Profile</a>
        <a href="dashboard.php" class="sb-link">Dashboard</a>
        <a href="orders.php" class="sb-link">My Orders</a>
        <a href="attendance.php" class="sb-link">Attendance</a>
        <?php endif; ?>
        <a href="cart.php" class="sb-link">Cart <span class="sb-badge" id="sidebarCartCount">0</span></a>
    </nav>
    <div class="sb-footer">
        <?php if ($isLoggedIn): ?><a href="log-out.php" class="sb-logout">Logout</a>
        <?php else: ?><a href="log-in.php" class="sb-login">Login</a><a href="register.php" class="sb-register">Sign Up</a><?php endif; ?>
    </div>
</aside>

<div class="top-strip"><b><?= ucfirst($branch) ?> Branch</b><i></i>Freshly prepared to order<i class="ts-extra"></i><span class="ts-extra">Est. 2026</span></div>

<header>
    <div class="header-inner">
        <div class="header-left">
            <button id="sidebarToggle" aria-label="Open sidebar" aria-expanded="false"><div class="hb"><span></span><span></span><span></span></div></button>
            <a href="index.php" class="brand">
                <div class="brand-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg></div>
                <span class="brand-name">SIPPERÉ <span>Café</span></span>
            </a>
        </div>
        <div class="header-right">
            <a href="order-type.php" class="header-home-link">Order Type</a>
            <a href="cart.php" class="header-cart-pill" aria-label="View cart">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                <span class="header-cart-count" id="cartCount">0</span>
            </a>
        </div>
    </div>
</header>

<div class="branch-bar">
    <a href="?branch=laguna" class="branch-btn <?= $branch === 'laguna' ? 'active' : '' ?>"><div class="branch-dot"></div>Laguna</a>
    <a href="?branch=manila" class="branch-btn <?= $branch === 'manila' ? 'active' : '' ?>"><div class="branch-dot"></div>Manila</a>
</div>

<section class="hero">
    <div class="hero-eyebrow">Order Now</div>
    <h1>Our <em>Menu</em></h1>
    <div class="hero-orn"><span></span><i></i><span></span></div>
    <p>Carefully crafted dishes made with the finest ingredients.<br>Pick your size and how you like it, then add it to your cart.</p>
    <div class="branch-badge"><?= ucfirst($branch) ?> Branch</div>
</section>

<div class="filter-bar">
    <?php foreach ($categories as $key => $label): ?>
    <button class="filter-btn <?= $key === 'all' ? 'active' : '' ?>" data-filter="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></button>
    <?php endforeach; ?>
</div>

<main class="menu-section">
<?php if (empty($products)): ?>
    <div class="empty-state"><h3>No dishes available yet.</h3><p>Check back soon — the kitchen is preparing something special.</p></div>
<?php else: ?>
    <div class="section-head"><h2>The Selection</h2><span class="sh-line"></span><span class="sh-count" id="shCount"><?= count($products) ?> items</span></div>
    <div class="menu-grid" id="menuGrid">
    <?php foreach ($products as $row):
        $stock = (int)($row['stock'] ?? 100); $reorder = (int)($row['reorder_level'] ?? 10);
        $pct = max(0, min(100, $stock));
        $isOos = $stock <= 0; $isLow = !$isOos && $stock <= $reorder;
        $cls = $isOos ? 's-red' : ($isLow ? 's-amber' : 's-green');
        $txt = $isOos ? 'Out of stock' : ($isLow ? "Low — {$stock} left" : "{$stock} in stock");
        $cat = $row['category'] ?? 'mains'; $hasSizes = (int)$row['size_count'] > 0;
    ?>
        <div class="menu-card <?= $isOos ? 'out-of-stock' : '' ?>" data-category="<?= htmlspecialchars($cat) ?>" data-stock="<?= $stock ?>">
            <div class="card-img-wrap">
                <?php if ($isOos): ?><div class="oos-badge">Out of Stock</div><?php elseif ($hasSizes): ?><div class="cz-badge">Choose size</div><?php endif; ?>
                <?php if (!empty($row['image'])): ?>
                    <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'img-placeholder\'><span>No Image</span></div>'">
                <?php else: ?><div class="img-placeholder"><span>No Image</span></div><?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!empty($row['category'])): ?><div class="card-category"><?= htmlspecialchars($row['category']) ?></div><?php endif; ?>
                <h3 class="card-name"><?= htmlspecialchars($row['name']) ?></h3>
                <p class="card-desc"><?= !empty($row['description']) ? htmlspecialchars($row['description']) : 'A signature dish from our kitchen, prepared fresh to order.' ?></p>
                <div class="stock-wrap">
                    <div class="stock-top"><span class="stock-label">Availability</span><span class="stock-text <?= $cls ?>"><?= $txt ?></span></div>
                    <div class="stock-bar-bg"><div class="stock-bar-fill <?= $cls ?>" style="width:<?= $pct ?>%"></div></div>
                </div>
                <div class="card-footer">
                    <div class="card-price"><?php if ($hasSizes): ?><small>from</small><?php endif; ?><span>₱</span><?= number_format((float)$row['price'], 2) ?></div>
                    <?php if ($isOos): ?>
                        <button class="add-btn" disabled>Unavailable</button>
                    <?php else: ?>
                        <button type="button" class="add-btn" onclick="addToCart(<?= (int)$row['id'] ?>, this)">+ Add</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
</main>

<div class="pagination-wrap" id="paginationWrap" style="display:none"><div class="pagination-info" id="paginationInfo"></div><div class="pagination" id="pagination"></div></div>

<div id="toast"><svg class="t-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span id="toastMsg">Added to cart!</span></div>

<footer>
    <div class="f-brand">SIPPERÉ <span>Café</span></div>
    <div class="f-tag">Crafted with intention</div>
    <p>© 2026 <span>SIPPERÉ Café</span> — All rights reserved.</p>
    <p>Developed by: <a href="https://www.instagram.com/_theprnx.gvara/" target="_blank" rel="noopener noreferrer">Prince Edward Guevara</a></p>
</footer>

<!-- Customization modal (size / hot-iced / sugar / add-ons / note / qty / order type) -->
<?php $czMode = 'serve'; include 'customize-modal.inc.php'; ?>

<script>
// Sidebar
var sidebar=document.getElementById('sidebar'),sidebarOverlay=document.getElementById('sidebarOverlay'),sidebarToggle=document.getElementById('sidebarToggle'),sidebarClose=document.getElementById('sidebarClose');
function openSidebar(){sidebar.classList.add('open');sidebarOverlay.classList.add('active');sidebarToggle.classList.add('open');sidebarToggle.setAttribute('aria-expanded','true');document.body.style.overflow='hidden'}
function closeSidebar(){sidebar.classList.remove('open');sidebarOverlay.classList.remove('active');sidebarToggle.classList.remove('open');sidebarToggle.setAttribute('aria-expanded','false');document.body.style.overflow=''}
sidebarToggle.addEventListener('click',function(){sidebar.classList.contains('open')?closeSidebar():openSidebar()});
sidebarClose.addEventListener('click',closeSidebar);sidebarOverlay.addEventListener('click',closeSidebar);
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeSidebar()});

// Grid + pagination
var ITEMS_PER_PAGE=6,currentPage=1,currentFilter='all';
var allCards=Array.prototype.slice.call(document.querySelectorAll('.menu-card')),grid=document.getElementById('menuGrid');
function renderGrid(animate){
    if(!grid)return;
    var filtered=currentFilter==='all'?allCards:allCards.filter(function(c){return c.dataset.category===currentFilter});
    var totalItems=filtered.length,totalPages=Math.max(1,Math.ceil(totalItems/ITEMS_PER_PAGE));
    if(currentPage>totalPages)currentPage=totalPages;
    var start=(currentPage-1)*ITEMS_PER_PAGE,end=start+ITEMS_PER_PAGE,pageCards=filtered.slice(start,end);
    function show(){applyVis(allCards,pageCards);grid.classList.remove('fading');pageCards.forEach(function(c,i){setTimeout(function(){c.classList.add('visible')},i*60)})}
    if(animate){grid.classList.add('fading');setTimeout(show,250)}else show();
    var sc=document.getElementById('shCount');if(sc)sc.textContent=totalItems+(totalItems===1?' item':' items');
    renderPagUI(totalItems,totalPages,start,end);
}
function applyVis(all,pageCards){all.forEach(function(c){c.classList.remove('visible');c.style.display='none'});pageCards.forEach(function(c){c.style.display=''})}
function mkBtn(label,disabled,active){var b=document.createElement('button');b.className='page-btn'+(active?' active':'');b.textContent=label;b.disabled=!!disabled;return b}
function scrollUp(){document.querySelector('.filter-bar').scrollIntoView({behavior:'smooth',block:'start'})}
function renderPagUI(totalItems,totalPages,start,end){
    var wrap=document.getElementById('paginationWrap'),info=document.getElementById('paginationInfo'),pg=document.getElementById('pagination');
    wrap.style.display=totalPages<=1?'none':'flex';
    info.textContent=totalItems?('Showing '+(start+1)+'–'+Math.min(end,totalItems)+' of '+totalItems+' items'):'No items in this category yet';
    pg.innerHTML='';
    var prev=mkBtn('←',currentPage===1);prev.addEventListener('click',function(){currentPage--;renderGrid(true);scrollUp()});pg.appendChild(prev);
    for(var i=1;i<=totalPages;i++){
        if(totalPages>7&&i>2&&i<totalPages-1&&Math.abs(i-currentPage)>1){if(i===3||i===totalPages-2){var el=document.createElement('span');el.className='page-ellipsis';el.textContent='…';pg.appendChild(el)}continue}
        (function(n){var b=mkBtn(n,false,n===currentPage);b.addEventListener('click',function(){currentPage=n;renderGrid(true);scrollUp()});pg.appendChild(b)})(i);
    }
    var next=mkBtn('→',currentPage===totalPages);next.addEventListener('click',function(){currentPage++;renderGrid(true);scrollUp()});pg.appendChild(next);
}
document.querySelectorAll('.filter-btn').forEach(function(btn){btn.addEventListener('click',function(){
    document.querySelectorAll('.filter-btn').forEach(function(b){b.classList.remove('active')});
    btn.classList.add('active');currentFilter=btn.dataset.filter;currentPage=1;renderGrid(true);
})});

// Cart badge
function setCartCount(c){document.getElementById('cartCount').textContent=c;var sb=document.getElementById('sidebarCartCount');if(sb)sb.textContent=c}
function updateCartBadge(){fetch('cart_handler.php?count=1',{credentials:'same-origin'}).then(function(r){return r.json()}).then(function(d){setCartCount(d.count||0)}).catch(function(){})}
updateCartBadge();

// "+ Add" -> customization modal FIRST, cart only after "Add to cart"
function addToCart(id,btn){
    var card=btn.closest('.menu-card');
    openCustomize(id,{onAdded:function(d){
        setCartCount(d.count||0);
        var stock=Math.max(0,d.stock);
        card.dataset.stock=stock;updateStockUI(card,stock);
        if(stock<=0){btn.disabled=true;btn.textContent='Unavailable'}
        else{btn.classList.add('added');btn.textContent='Added ✓';setTimeout(function(){btn.classList.remove('added');btn.textContent='+ Add'},1400)}
        showToast('Added to cart!',false);
    }});
}
function updateStockUI(card,stock){
    var reorder=10,pct=Math.max(0,Math.min(100,stock)),isOos=stock<=0,isLow=!isOos&&stock<=reorder;
    var cls=isOos?'s-red':(isLow?'s-amber':'s-green'),txt=isOos?'Out of stock':(isLow?'Low — '+stock+' left':stock+' in stock');
    var fill=card.querySelector('.stock-bar-fill'),stxt=card.querySelector('.stock-text');
    if(fill){fill.style.width=pct+'%';fill.className='stock-bar-fill '+cls}
    if(stxt){stxt.textContent=txt;stxt.className='stock-text '+cls}
    if(isOos){card.classList.add('out-of-stock');var iw=card.querySelector('.card-img-wrap');if(iw&&!iw.querySelector('.oos-badge')){var b=document.createElement('div');b.className='oos-badge';b.textContent='Out of Stock';iw.appendChild(b)}}
}
var toastTimer;
function showToast(msg,isError){var t=document.getElementById('toast');document.getElementById('toastMsg').textContent=msg;t.className=isError?'error':'';t.classList.add('show');clearTimeout(toastTimer);toastTimer=setTimeout(function(){t.classList.remove('show')},2500)}

renderGrid(false);
</script>
</body>
</html>