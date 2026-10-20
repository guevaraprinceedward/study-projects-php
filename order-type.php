<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';

// ── SESSION CHECK (guest allowed) ─────────────────────────────────────────
$isLoggedIn = false;
if (isset($_SESSION["user"])) {
    $uid   = (int)($_SESSION["user"]["id"] ?? 0);
    $uname = $conn->real_escape_string($_SESSION["user"]["username"] ?? '');
    $chk   = $conn->query("SELECT id FROM users WHERE id = $uid AND username = '$uname' LIMIT 1");
    if ($chk && $chk->num_rows > 0) {
        $isLoggedIn = true;
    } else {
        session_unset(); session_destroy();
    }
}

// ── BRANCH (shared with menu.php / reservation-menu.php) ──────────────────
$branch = $_SESSION['branch'] ?? 'laguna';
if (isset($_GET['branch']) && in_array($_GET['branch'], ['laguna', 'manila'])) {
    $branch = $_GET['branch'];
    $_SESSION['branch'] = $branch;
}
$branchEsc = $conn->real_escape_string($branch);

// Number of Packages Combo available in the chosen branch (nice teaser on the cards)
$pkgCount = 0;
$pk = $conn->query("SELECT COUNT(*) AS c FROM products WHERE category = 'packages' AND branch = '$branchEsc'");
if ($pk) $pkgCount = (int)($pk->fetch_assoc()['c'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Choose Order Type — SIPPERÉ Café</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<noscript><style>.intro{display:none!important}.rv{opacity:1!important;transform:none!important}body.intro-lock{overflow:auto!important}</style></noscript>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    /* ── DARK IMPERIAL THEME: black marble · premium gold (no crimson) ── */
    --bg:#0a0907;--surface:#12100b;--card:#17140d;--border:#2e2616;
    --gold:#c9a84c;--gold-bright:#e6c777;--gold-dim:#8a6f2e;--gold-deep:#5d4a1d;
    --cream:#f0ead8;--text:#e6dfcc;--muted:#8d8368;
    --serif:'Cinzel','Cormorant Garamond',serif;--disp:'Cormorant Garamond',serif;
    --ease:cubic-bezier(.2,.8,.2,1);--ease-gate:cubic-bezier(.77,0,.18,1);
}
html{scroll-behavior:smooth}
body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden;-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility}
body.intro-lock{overflow:hidden}
/* black marble with faint gold veining */
body::before{content:'';position:fixed;inset:0;pointer-events:none;z-index:0;
    background:
      radial-gradient(ellipse 70% 50% at 8% 0%,rgba(201,168,76,.09) 0%,transparent 55%),
      radial-gradient(ellipse 55% 70% at 92% 100%,rgba(201,168,76,.05) 0%,transparent 55%),
      url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='900' height='900'><filter id='m' x='0' y='0' width='100%25' height='100%25'><feTurbulence type='turbulence' baseFrequency='0.0035 0.011' numOctaves='5' seed='7' stitchTiles='stitch'/><feColorMatrix values='0 0 0 0 .79  0 0 0 0 .66  0 0 0 0 .3  -7 0 0 0 .95'/></filter><rect width='100%25' height='100%25' filter='url(%23m)'/></svg>");
    background-size:auto,auto,900px 900px}
body::after{content:'';position:fixed;inset:0;pointer-events:none;z-index:0;background:rgba(10,9,7,.88)}
a{color:inherit}
:focus-visible{outline:2px solid var(--gold);outline-offset:3px}
.grain{position:fixed;inset:0;z-index:4;pointer-events:none;opacity:.035;mix-blend-mode:overlay;background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='140' height='140'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/></filter><rect width='100%25' height='100%25' filter='url(%23n)'/></svg>")}

/* ── ARCHITECTURAL ORNAMENT PRIMITIVES ── */
.meander{height:16px;background:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16'><path d='M0 13H16M13 13V3H3V10H10V6' fill='none' stroke='%23c9a84c' stroke-width='1.4'/></svg>") repeat-x center;opacity:.8}
.dentil{height:9px;background:repeating-linear-gradient(90deg,var(--gold-dim) 0 7px,transparent 7px 14px);border-top:1px solid var(--gold-dim)}
.pilaster{position:relative;width:34px;flex:none;
    background:repeating-linear-gradient(90deg,rgba(0,0,0,.6) 0 2px,transparent 2px 6px),linear-gradient(90deg,#17120a 0%,#6f5a26 34%,#d9b95a 50%,#7a622a 66%,#17120a 100%);
    border-left:1px solid rgba(0,0,0,.6);border-right:1px solid rgba(0,0,0,.6);transition:filter .4s var(--ease)}
.pilaster::before,.pilaster::after{content:'';position:absolute;left:-6px;right:-6px;height:13px;border-radius:2px;background:linear-gradient(180deg,#f0d78e,#8a6f2e);box-shadow:0 4px 10px rgba(0,0,0,.5)}
.pilaster::before{top:0}
.pilaster::after{bottom:0}
.steps i{display:block;height:9px;background:linear-gradient(180deg,#8a6f2e,#2e2410);border-top:1px solid #e6c777;box-shadow:0 5px 12px rgba(0,0,0,.5)}
.steps i:nth-child(2){margin:0 -12px}
.steps i:nth-child(3){margin:0 -24px}
.frieze{position:relative;z-index:1}
.frieze .meander{margin:6px 0}

/* ── INTRO: temple gates part to reveal the page ── */
.intro{position:fixed;inset:0;z-index:9999;pointer-events:all;visibility:visible}
.intro.gone{visibility:hidden;pointer-events:none}
.gate{position:absolute;top:0;bottom:0;width:50.2%;background:
      repeating-linear-gradient(90deg,rgba(255,255,255,.012) 0 2px,transparent 2px 9px),
      linear-gradient(180deg,#0d0b07,#060504);
    transition:transform 1.15s var(--ease-gate);will-change:transform;z-index:1}
.gate.gl{left:0;border-right:1px solid var(--gold-dim)}
.gate.gr{right:0;border-left:1px solid var(--gold-dim)}
.gate::after{content:'';position:absolute;top:0;bottom:0;width:18px;background:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16'><path d='M0 13H16M13 13V3H3V10H10V6' fill='none' stroke='%23c9a84c' stroke-width='1.2'/></svg>") repeat-y center;opacity:.6}
.gate.gl::after{right:6px}.gate.gr::after{left:6px}
.intro.open .gate.gl{transform:translateX(-101%)}
.intro.open .gate.gr{transform:translateX(101%)}
.intro-emblem{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);z-index:3;text-align:center;opacity:0;transition:opacity .5s ease}
.intro.show .intro-emblem{opacity:1}
.intro.open .intro-emblem{opacity:0;transition:opacity .35s ease}
.intro-wreath{width:116px;height:116px;display:block;margin:0 auto 18px;transform:scale(.8) rotate(-12deg);transition:transform 1.3s var(--ease)}
.intro.show .intro-wreath{transform:scale(1) rotate(0)}
.intro-wreath g.leaf{opacity:0}
.intro-word{font-family:var(--serif);font-size:13px;font-weight:500;letter-spacing:.42em;text-transform:uppercase;color:var(--gold);padding-left:.42em}
.intro-line{position:relative;width:170px;height:1px;background:var(--border);margin:18px auto 0;overflow:hidden}
.intro-line span{position:absolute;inset:0;background:linear-gradient(90deg,var(--gold-dim),var(--gold-bright));transform:scaleX(0);transform-origin:left}
.intro.show .intro-line span{animation:introBar 1.25s var(--ease) .1s forwards}
@keyframes introBar{to{transform:scaleX(1)}}

/* leaf-by-leaf wreath bloom */
@keyframes leafIn{from{opacity:0;transform:scale(.3)}to{opacity:1;transform:scale(1)}}
.intro.show .intro-wreath g.leaf{animation:leafIn .5s var(--ease) forwards;transform-box:fill-box;transform-origin:center}

/* ── PAGE EXIT VEIL ── */
.veil{position:fixed;inset:0;z-index:9998;background:#060504;opacity:0;pointer-events:none;transition:opacity .38s ease}
body.leaving .veil{opacity:1;pointer-events:all}

/* ── STAGGERED REVEAL (starts when gates open) ── */
.rv{opacity:0;transform:translateY(26px);transition:opacity .9s var(--ease),transform .9s var(--ease);transition-delay:calc(var(--d,0) * 1s)}
body.ready .rv{opacity:1;transform:none}
.rv-mask{overflow:hidden;padding-bottom:.14em;margin-bottom:-.14em}
.rv-mask span{display:inline-block;transform:translateY(112%);transition:transform 1.05s var(--ease);transition-delay:calc(var(--d,0) * 1s)}
body.ready .rv-mask span{transform:none}

/* ── TOP STRIP ── */
.top-strip{position:relative;z-index:101;background:#050403;color:var(--gold);text-align:center;padding:9px 16px 7px;font-family:var(--serif);font-size:10.5px;font-weight:500;letter-spacing:.36em;text-transform:uppercase;border-bottom:1px solid var(--gold-dim)}
.top-strip i{display:inline-block;width:4px;height:4px;background:var(--gold);transform:rotate(45deg);margin:0 14px;vertical-align:middle;opacity:.7}
@media(max-width:640px){.top-strip{letter-spacing:.2em;font-size:9.5px}.top-strip .ts-x{display:none}}

/* ── HEADER ── */
header{position:sticky;top:0;z-index:100;background:rgba(10,9,7,.88);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);border-bottom:1px solid var(--border)}
header::after{content:'';position:absolute;left:0;right:0;bottom:-1px;height:1px;background:linear-gradient(90deg,transparent,rgba(201,168,76,.55),transparent)}
.header-inner{max-width:1180px;margin:0 auto;padding:0 32px;height:76px;display:flex;align-items:center;justify-content:space-between}
.brand{display:flex;align-items:center;gap:13px;text-decoration:none}
.brand-icon{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:linear-gradient(155deg,var(--gold-bright),var(--gold-dim));box-shadow:0 4px 16px -4px rgba(201,168,76,.6),inset 0 0 0 3px var(--bg),inset 0 0 0 4px rgba(201,168,76,.55);transition:transform .4s var(--ease)}
.brand:hover .brand-icon{transform:rotate(-14deg)}
.brand-name{font-family:var(--serif);font-size:20px;font-weight:600;color:var(--cream);letter-spacing:.1em}
.brand-name span{color:var(--gold);font-family:var(--disp);font-style:italic;font-weight:600;letter-spacing:.02em;font-size:23px}
nav{display:flex;align-items:center;gap:6px}
nav a{position:relative;font-size:12px;font-weight:500;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);text-decoration:none;padding:8px 14px;border-radius:2px;transition:color .2s}
nav a::after{content:'';position:absolute;left:14px;right:14px;bottom:4px;height:1px;background:var(--gold);transform:scaleX(0);transform-origin:left;transition:transform .35s var(--ease)}
nav a:hover{color:var(--cream)}
nav a:hover::after{transform:scaleX(1)}

/* ── PAGE HERO ── */
.page-hero{position:relative;z-index:1;text-align:center;padding:70px 32px 26px;max-width:780px;margin:0 auto}
.page-eyebrow{display:inline-flex;align-items:center;gap:12px;font-family:var(--serif);font-size:11px;font-weight:500;letter-spacing:.32em;text-transform:uppercase;color:var(--gold);margin-bottom:22px}
.page-eyebrow::before,.page-eyebrow::after{content:'';width:34px;height:1px;background:var(--gold-dim)}
.page-hero h1{font-family:var(--serif);font-size:clamp(30px,4.4vw,50px);font-weight:600;color:var(--cream);line-height:1.22;letter-spacing:.015em;margin-bottom:6px}
.page-hero h1 em{font-family:var(--disp);font-style:italic;font-weight:600;font-size:1.12em;letter-spacing:0;background:linear-gradient(180deg,#f5dc92 0%,#c9a84c 55%,#9a7a2e 100%);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent;padding-right:.08em}
.orn{display:flex;align-items:center;justify-content:center;gap:12px;margin:22px 0 20px}
.orn span{width:80px;height:1px;background:linear-gradient(90deg,transparent,var(--gold-dim))}
.orn span:last-child{transform:scaleX(-1)}
.orn svg{width:38px;height:38px;flex:none}
.page-hero p{font-size:15px;color:var(--muted);line-height:1.85;font-weight:300;max-width:560px;margin:0 auto}

/* ── BRANCH BAR (pill buttons unchanged in form) ── */
.branch-bar{position:relative;z-index:1;display:flex;justify-content:center;align-items:center;gap:10px;padding:14px 32px 54px;flex-wrap:wrap}
.branch-bar .lbl{font-family:var(--serif);font-size:11px;font-weight:500;letter-spacing:.24em;text-transform:uppercase;color:var(--gold-dim);margin-right:6px}
.branch-btn{display:flex;align-items:center;gap:8px;padding:9px 22px;border-radius:100px;border:1px solid var(--border);color:var(--muted);font-size:12px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;text-decoration:none;transition:all .25s ease}
.branch-btn:hover{border-color:var(--gold-dim);color:var(--gold)}
.branch-btn.active{border-color:var(--gold);color:var(--gold);background:rgba(201,168,76,.1)}
.branch-dot{width:7px;height:7px;border-radius:50%;background:var(--gold-dim)}
.branch-btn.active .branch-dot{background:var(--gold-bright);box-shadow:0 0 8px rgba(230,199,119,.7)}

/* ── CHOICES: two temple fronts ── */
.choices{position:relative;z-index:1;max-width:1020px;margin:0 auto;padding:0 40px 100px;display:grid;grid-template-columns:1fr 1fr;gap:44px}
.choice{position:relative;display:flex;flex-direction:column;text-decoration:none;color:inherit;transition:transform .45s var(--ease),filter .45s var(--ease)}
.choice:hover{transform:translateY(-8px);filter:drop-shadow(0 30px 40px rgba(0,0,0,.7))}
.choice:hover .pilaster{filter:brightness(1.25)}

/* pediment */
.cn-pediment{position:relative;height:104px;margin:0 -10px}
.cn-pediment svg.tri{position:absolute;inset:0;width:100%;height:100%;overflow:visible}
.cn-emblem{position:absolute;left:50%;bottom:6px;width:90px;height:90px;transform:translateX(-50%);display:flex;align-items:center;justify-content:center}
.cn-emblem svg.wr{position:absolute;inset:0;width:100%;height:100%}
.cn-emblem .ico{position:relative;width:28px;height:28px;color:var(--gold-bright);transition:transform .5s var(--ease)}
.choice:hover .cn-emblem .ico{transform:scale(1.18) rotate(-6deg)}
/* entablature */
.cn-entab{background:linear-gradient(180deg,#a8883a,#5d4a1d);border-top:1px solid #e6c777;margin:0 -10px}
.cn-entab .dentil{background:repeating-linear-gradient(90deg,#1a150b 0 7px,transparent 7px 14px);border-top:none}
.cn-entab .meander{height:22px;background-color:#0d0a05;opacity:1}
/* body between pilasters */
.cn-body{display:flex;align-items:stretch;padding:0 4px}
.cn-body > .pilaster{margin:0 6px}
.cn-inner{flex:1;display:flex;flex-direction:column;padding:34px 26px 30px;text-align:center;background:linear-gradient(180deg,#17130c,#0f0c07);border-bottom:1px solid #000;position:relative}
.cn-inner::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--gold-dim),transparent)}
.cn-num{font-family:var(--serif);font-size:11px;font-weight:500;letter-spacing:.34em;color:var(--gold-dim);margin-bottom:12px;padding-left:.34em}
.choice h2{font-family:var(--serif);font-size:27px;font-weight:600;color:var(--cream);margin-bottom:12px;letter-spacing:.06em}
.choice p{font-size:14px;color:var(--muted);line-height:1.8;margin-bottom:22px;font-weight:300;flex:1}
.choice ul{list-style:none;margin-bottom:28px;display:flex;flex-direction:column;gap:10px;text-align:left;padding:18px 4px 0;border-top:1px solid var(--border)}
.choice li{font-size:12.5px;color:var(--text);display:flex;gap:10px;align-items:flex-start;line-height:1.55;font-weight:300}
.choice li svg{flex-shrink:0;margin-top:3px;color:var(--gold)}
.choice-cta{display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:14px 24px;background:linear-gradient(180deg,#e6c777,#b8903a);border:1px solid #f0d78e;border-radius:2px;font-size:13px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#1a1400;position:relative;overflow:hidden;transition:filter .2s;box-shadow:0 16px 30px -14px rgba(201,168,76,.45)}
.choice-cta::after{content:'';position:absolute;top:0;left:-60%;width:35%;height:100%;background:linear-gradient(115deg,transparent,rgba(255,255,255,.4),transparent);transform:skewX(-18deg);transition:left .65s ease}
.choice:hover .choice-cta{filter:brightness(1.07)}
.choice:hover .choice-cta::after{left:130%}
.choice-cta svg{transition:transform .3s var(--ease)}
.choice:hover .choice-cta svg{transform:translateX(5px)}
.choice.alt .choice-cta{background:transparent;border:1px solid var(--gold-dim);color:var(--gold);box-shadow:none}
.choice.alt:hover .choice-cta{background:rgba(201,168,76,.1);border-color:var(--gold-bright);color:var(--gold-bright);filter:none}
.choice .steps{margin-top:0}

/* building-up animation for each temple (runs when gates open) */
.choice .cn-pediment{opacity:0;transform:translateY(-22px);transition:opacity .8s var(--ease),transform .9s var(--ease);transition-delay:calc(var(--d,0) * 1s + .35s)}
.choice .cn-entab{opacity:0;transform:scaleX(.82);transition:opacity .7s var(--ease),transform .8s var(--ease);transition-delay:calc(var(--d,0) * 1s + .22s)}
.choice .pilaster{transform:scaleY(0);transform-origin:bottom;transition:transform 1s var(--ease),filter .4s var(--ease);transition-delay:calc(var(--d,0) * 1s + .1s)}
.choice .cn-inner{opacity:0;transition:opacity .9s var(--ease);transition-delay:calc(var(--d,0) * 1s + .5s)}
.choice .steps i{opacity:0;transform:scaleX(.6);transition:opacity .7s var(--ease),transform .8s var(--ease);transition-delay:calc(var(--d,0) * 1s)}
body.ready .choice .cn-pediment{opacity:1;transform:none}
body.ready .choice .cn-entab{opacity:1;transform:none}
body.ready .choice .pilaster{transform:none}
body.ready .choice .cn-inner{opacity:1}
body.ready .choice .steps i{opacity:1;transform:none}
/* choice wrapper itself must not be hidden by .rv — it animates its own parts */
.choice.no-rv{opacity:1;transform:none}

/* ── FOOTER ── */
footer{position:relative;z-index:1;background:#050403;border-top:1px solid var(--gold-dim);text-align:center}
footer .in{padding:26px 32px 28px}
footer p{font-size:12px;color:rgba(240,234,216,.5);letter-spacing:.08em}
footer p span{color:var(--gold)}
footer p+p{margin-top:4px}
footer a.credit{color:var(--gold);text-decoration:none}
footer a.credit:hover{color:var(--cream);text-decoration:underline}

@media(max-width:820px){
    .header-inner{padding:0 16px}
    nav a:not(:last-child){display:none}
    .choices{grid-template-columns:1fr;padding:0 22px 70px;gap:54px}
    .page-hero{padding:52px 20px 20px}
    .cn-inner{padding:30px 16px 26px}
    .pilaster{width:26px}
    .steps i:nth-child(2){margin:0 -8px}
    .steps i:nth-child(3){margin:0 -16px}
    .cn-pediment,.cn-entab{margin:0 -8px}
}
@media (prefers-reduced-motion: reduce){
    *{transition-duration:.01ms !important;animation-duration:.01ms !important;transition-delay:0s !important}
    .intro{display:none !important}
    .rv,.rv-mask span,.choice .cn-pediment,.choice .cn-entab,.choice .pilaster,.choice .cn-inner,.choice .steps i{opacity:1 !important;transform:none !important}
}
</style>
</head>
<body class="intro-lock">

<!-- Shared SVG sprite: gold materials -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
<defs>
    <linearGradient id="tGold" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f0d78e"/><stop offset=".5" stop-color="#c9a84c"/><stop offset="1" stop-color="#7a5f22"/></linearGradient>
    <linearGradient id="tGoldDim" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#b8943f"/><stop offset="1" stop-color="#4a3a17"/></linearGradient>
</defs>
</svg>

<!-- Intro: temple gates part -->
<div class="intro" id="intro" aria-hidden="true">
    <div class="gate gl"></div>
    <div class="gate gr"></div>
    <div class="intro-emblem">
        <svg class="intro-wreath" viewBox="-40 -40 80 80"><g data-wreath="30" data-pairs="9" data-leaf="1"></g><path d="M0,-7 l6,7 l-6,7 l-6,-7 Z" fill="url(#tGold)"/></svg>
        <div class="intro-word">SIPPERÉ Café</div>
        <div class="intro-line"><span></span></div>
    </div>
</div>
<div class="veil" id="veil"></div>
<div class="grain"></div>

<div class="top-strip">Laguna<i></i>Manila<i class="ts-x"></i><span class="ts-x">Anno MMXXVI</span></div>

<header>
    <div class="header-inner">
        <a href="index.php" class="brand">
            <div class="brand-icon">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#0b0b09" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
            </div>
            <span class="brand-name">SIPPERÉ <span>Café</span></span>
        </a>
        <nav>
            <a href="index.php">Home</a>
            <?php if ($isLoggedIn): ?>
                <a href="my-reservations.php">Reservations</a>
                <a href="orders.php">Orders</a>
            <?php else: ?>
                <a href="log-in.php">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<section class="page-hero">
    <div class="page-eyebrow rv" style="--d:.05">Choose Your Path</div>
    <h1 class="rv-mask" style="--d:.15"><span>How would you like to <em>order?</em></span></h1>
    <div class="orn rv" style="--d:.3"><span></span><svg viewBox="-17 -17 34 34"><g data-wreath="12" data-pairs="6"></g></svg><span></span></div>
    <p class="rv" style="--d:.4">Get your coffee, meals and packages served fresh right now, or reserve them ahead for a date and time that works for you.</p>
</section>

<div class="branch-bar rv" style="--d:.5">
    <span class="lbl">Ordering from</span>
    <a href="?branch=laguna" class="branch-btn <?= $branch === 'laguna' ? 'active' : '' ?>"><span class="branch-dot"></span>Laguna</a>
    <a href="?branch=manila" class="branch-btn <?= $branch === 'manila' ? 'active' : '' ?>"><span class="branch-dot"></span>Manila</a>
</div>

<main class="choices">
    <a href="menu.php" class="choice no-rv" style="--d:.55">
        <div class="cn-pediment">
            <svg class="tri" viewBox="0 0 1000 104" preserveAspectRatio="none"><polygon points="0,104 500,6 1000,104" fill="#120f08" stroke="#c9a84c" stroke-width="1.5" vector-effect="non-scaling-stroke"/><polygon points="60,100 500,22 940,100" fill="none" stroke="#8a6f2e" stroke-width="1" stroke-dasharray="3 4" vector-effect="non-scaling-stroke"/></svg>
            <div class="cn-emblem">
                <svg class="wr" viewBox="-40 -40 80 80"><g data-wreath="32" data-pairs="9"></g></svg>
                <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
            </div>
        </div>
        <div class="cn-entab"><div class="dentil"></div><div class="meander"></div></div>
        <div class="cn-body">
            <div class="pilaster"></div>
            <div class="cn-inner">
                <div class="cn-num">I</div>
                <h2>Serve Now</h2>
                <p>Order right now and we prepare it fresh the moment you check out.</p>
                <ul>
                    <li><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Made to order, no waiting for a set time</li>
                    <li><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Checkout right after you pick your items</li>
                </ul>
                <span class="choice-cta">Order Now <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
            </div>
            <div class="pilaster"></div>
        </div>
        <div class="steps"><i></i><i></i><i></i></div>
    </a>

    <a href="reservation-menu.php" class="choice alt no-rv" style="--d:.8">
        <div class="cn-pediment">
            <svg class="tri" viewBox="0 0 1000 104" preserveAspectRatio="none"><polygon points="0,104 500,6 1000,104" fill="#120f08" stroke="#c9a84c" stroke-width="1.5" vector-effect="non-scaling-stroke"/><polygon points="60,100 500,22 940,100" fill="none" stroke="#8a6f2e" stroke-width="1" stroke-dasharray="3 4" vector-effect="non-scaling-stroke"/></svg>
            <div class="cn-emblem">
                <svg class="wr" viewBox="-40 -40 80 80"><g data-wreath="32" data-pairs="9"></g></svg>
                <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
        </div>
        <div class="cn-entab"><div class="dentil"></div><div class="meander"></div></div>
        <div class="cn-body">
            <div class="pilaster"></div>
            <div class="cn-inner">
                <div class="cn-num">II</div>
                <h2>Reservation</h2>
                <p>Reserve your items for a date and time you choose, then pay now or pay later.</p>
                <ul>
                    <li><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Your items are held for you until you arrive</li>
                    <li><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Includes <?= $pkgCount ?> Packages Combo at <?= ucfirst($branch) ?></li>
                </ul>
                <span class="choice-cta">Make a Reservation <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
            </div>
            <div class="pilaster"></div>
        </div>
        <div class="steps"><i></i><i></i><i></i></div>
    </a>
</main>

<footer>
    <div class="frieze"><div class="dentil"></div><div class="meander"></div></div>
    <div class="in">
        <p>© 2026 <span>SIPPERÉ Café</span> — All rights reserved.</p>
        <p>Developed by: <a class="credit" href="https://www.instagram.com/_theprnx.gvara/" target="_blank" rel="noopener noreferrer">Prince Edward Guevara</a></p>
    </div>
</footer>

<script>
/* Laurel wreath generator — two mirrored branches of leaf pairs into every [data-wreath] group */
(function(){
    var NS='http://www.w3.org/2000/svg';
    function el(n,a){var e=document.createElementNS(NS,n);for(var k in a)e.setAttribute(k,a[k]);return e}
    function wreath(g,R,pairs,leafMode){
        var rx=R*.23, ry=R*.095, n=0;
        [-1,1].forEach(function(s){
            var d='';
            for(var i=0;i<=24;i++){
                var th=(16+(152-16)*i/24)*Math.PI/180;
                d+=(i?'L':'M')+(s*R*Math.sin(th)).toFixed(2)+' '+(R*Math.cos(th)).toFixed(2);
            }
            g.appendChild(el('path',{d:d,fill:'none',stroke:'#8a6f2e','stroke-width':Math.max(.5,R*.045),'stroke-linecap':'round'}));
            for(var i=0;i<pairs;i++){
                var th=(22+(150-22)*i/(pairs-1))*Math.PI/180;
                var px=s*R*Math.sin(th), py=R*Math.cos(th);
                var tan=Math.atan2(-Math.sin(th), s*Math.cos(th))*180/Math.PI;
                [1,-1].forEach(function(o){
                    var cx=px+s*Math.sin(th)*o*R*.1, cy=py+Math.cos(th)*o*R*.1;
                    /* The rotation lives on a wrapper <g>, so the bloom animation (a CSS transform on the
                       leaf itself) can never override a leaf's angle in any browser. */
                    var holder=el('g',{transform:'translate('+cx.toFixed(2)+' '+cy.toFixed(2)+') rotate('+(tan+o*s*34).toFixed(1)+')'});
                    var leaf=el('ellipse',{cx:0,cy:0,rx:rx.toFixed(2),ry:ry.toFixed(2),fill:'url(#tGold)'});
                    if(leafMode){ leaf.setAttribute('class','leaf'); leaf.style.animationDelay=(.15+ (n++)*0.035).toFixed(3)+'s'; }
                    holder.appendChild(leaf);
                    g.appendChild(holder);
                });
            }
            g.appendChild(el('circle',{cx:(s*R*Math.sin(.2)).toFixed(2),cy:(R*Math.cos(.2)+R*.1).toFixed(2),r:(R*.07).toFixed(2),fill:'#e6c777'}));
        });
    }
    document.querySelectorAll('[data-wreath]').forEach(function(g){
        wreath(g, parseFloat(g.getAttribute('data-wreath')), parseInt(g.getAttribute('data-pairs'),10)||7, g.hasAttribute('data-leaf'));
    });
})();

/* Intro timeline: emblem blooms → gates part → page builds in */
(function(){
    var body = document.body, intro = document.getElementById('intro');
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var seen = false;
    try { seen = sessionStorage.getItem('acnIntroSeen') === '1'; } catch(e){}
    function reveal(){ body.classList.remove('intro-lock'); body.classList.add('ready'); }
    function finish(){ if (intro){ intro.classList.add('gone'); setTimeout(function(){ if (intro.parentNode) intro.parentNode.removeChild(intro); }, 50); } }

    if (reduced || !intro){ reveal(); finish(); return; }

    if (seen){
        /* returning within this session: quick, gentle gate opening */
        intro.classList.add('open');
        setTimeout(reveal, 150);
        setTimeout(finish, 1250);
        return;
    }
    try { sessionStorage.setItem('acnIntroSeen','1'); } catch(e){}

    function start(){
        intro.classList.add('show');
        setTimeout(function(){ intro.classList.add('open'); }, 1700);   // gates part
        setTimeout(reveal, 2150);                                        // content builds while gates open
        setTimeout(finish, 3500);                                        // remove overlay
    }
    if (document.readyState === 'complete') start();
    else { window.addEventListener('load', start); setTimeout(function(){ if(!intro.classList.contains('show')) start(); }, 2500); }
})();

/* Smooth page exit: fade to black before navigating to another page */
(function(){
    var body = document.body;
    window.addEventListener('pageshow', function(e){ if (e.persisted) body.classList.remove('leaving'); });
    document.addEventListener('click', function(e){
        var a = e.target.closest ? e.target.closest('a[href]') : null;
        if (!a) return;
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        if (a.target && a.target !== '_self') return;
        var href = a.getAttribute('href');
        if (!href || href.charAt(0) === '#' || /^(mailto|tel|javascript):/i.test(href)) return;
        var url; try { url = new URL(a.href, location.href); } catch(err){ return; }
        if (url.origin !== location.origin) return;
        if (url.pathname === location.pathname && url.search === location.search) return;
        e.preventDefault();
        body.classList.add('leaving');
        setTimeout(function(){ location.href = url.href; }, 380);
    });
})();
</script>

</body>
</html>