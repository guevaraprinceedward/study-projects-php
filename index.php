<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';

// ── SESSION VALIDATION (unchanged from the live site) ─────────────────────
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

// ── BRANCH (needed for the Branch Selection section + signature picks) ────
$branch = $_SESSION['branch'] ?? 'laguna';
if (isset($_GET['branch']) && in_array($_GET['branch'], ['laguna', 'manila'])) {
    $branch = $_GET['branch'];
    $_SESSION['branch'] = $branch;
}
$branchEsc = $conn->real_escape_string($branch);

// ── SIGNATURE: real best-sellers (by revenue) for the current branch ──────
$signature = [];
$bestQ = $conn->query("
    SELECT p.*, COALESCE(SUM(oi.quantity),0) AS units_sold
    FROM products p
    LEFT JOIN order_items oi ON oi.product_id = p.id
    LEFT JOIN orders o ON o.id = oi.order_id AND o.status != 'cancelled'
    WHERE p.branch = '$branchEsc'
    GROUP BY p.id
    ORDER BY units_sold DESC, p.id DESC
    LIMIT 3
");
if ($bestQ) { while ($r = $bestQ->fetch_assoc()) $signature[] = $r; }

// ── BRANCH CARDS: real item counts per branch ─────────────────────────────
$branchCounts = ['laguna' => 0, 'manila' => 0];
$bc = $conn->query("SELECT branch, COUNT(*) AS cnt FROM products GROUP BY branch");
if ($bc) { while ($r = $bc->fetch_assoc()) { if (isset($branchCounts[$r['branch']])) $branchCounts[$r['branch']] = (int)$r['cnt']; } }
$totalMenuItems = $branchCounts['laguna'] + $branchCounts['manila'];

// ── TESTIMONIALS (same table as testimonial.php writes to) ────────────────
$conn->query("CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    branch VARCHAR(20) DEFAULT 'laguna',
    rating TINYINT(1) DEFAULT 5,
    avatar_url VARCHAR(255) DEFAULT NULL,
    message TEXT NOT NULL,
    is_approved TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$teaser = [];
$tRes = $conn->query("SELECT name, branch, rating, avatar_url, message FROM testimonials WHERE is_approved = 1 ORDER BY created_at DESC LIMIT 9");
if ($tRes) { while ($row = $tRes->fetch_assoc()) $teaser[] = $row; }
if (empty($teaser)) {
    $teaser = [
        ['name' => 'Isabelle Tan', 'branch' => 'manila', 'rating' => 5, 'avatar_url' => null, 'message' => 'Every cup feels considered. This is the coffee shop I recommend to everyone who asks where to find real quality in the city.'],
        ['name' => 'Miguel Santos', 'branch' => 'laguna', 'rating' => 5, 'avatar_url' => null, 'message' => 'From the crema to the last sip — consistent, unhurried, exactly what specialty coffee should feel like.'],
        ['name' => 'Patricia Lim', 'branch' => 'manila', 'rating' => 5, 'avatar_url' => null, 'message' => 'It is the kind of coffee shop that feels like it was designed for people who actually care about what they drink.'],
    ];
}
$testiPages = array_chunk($teaser, 3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIPPERÉ Café — The Pursuit of Exceptional Coffee</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600&family=Jost:wght@300;400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<noscript><style>.loader{display:none!important}.reveal-el,.cup-stage,[data-reveal],[data-reveal]>*,.bean,.cb{opacity:1!important;transform:none!important}</style></noscript>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    /* ── DARK IMPERIAL THEME: black marble · premium gold · deep wine ── */
    --bg:#0a0907;--surface:#12100b;--card:#17140d;--border:#2e2616;
    --gold:#c9a84c;--gold-bright:#e6c777;--gold-dim:#8a6f2e;--gold-deep:#5d4a1d;
    --cream:#f0ead8;--text:#e6dfcc;--muted:#8d8368;
    --wine:#5a1522;--wine-dk:#220810;
    --serif:'Cinzel','Cormorant Garamond',serif;--disp:'Cormorant Garamond',serif;
    --mono:'IBM Plex Mono',ui-monospace,monospace;
    --ease:cubic-bezier(.2,.8,.2,1);
}
html{scroll-behavior:smooth}
@media (prefers-reduced-motion: reduce){
    *{animation-duration:.01ms !important;animation-iteration-count:1 !important;transition-duration:.01ms !important;scroll-behavior:auto !important}
    .loader{display:none !important}
    .reveal-el,.cup-stage,[data-reveal],[data-reveal] > *,.bean,.cb,#cursorDot,#cursorRing,.coffee-particle{opacity:1 !important;transform:none !important;display:revert !important}
    #cursorDot,#cursorRing,.coffee-particle{display:none !important}
}
/* perspective lives on .cup-stage only — on body it would break position:fixed descendants */
body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden;cursor:none;-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility}
@media(hover:none),(pointer:coarse){body{cursor:auto}}
/* black marble with faint gold veining + warm ambient light */
body::before{content:'';position:fixed;inset:0;pointer-events:none;z-index:0;
    background:
      radial-gradient(ellipse 70% 50% at 8% 0%,rgba(201,168,76,.08) 0%,transparent 55%),
      radial-gradient(ellipse 55% 70% at 92% 100%,rgba(90,21,34,.14) 0%,transparent 55%),
      url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='900' height='900'><filter id='m' x='0' y='0' width='100%25' height='100%25'><feTurbulence type='turbulence' baseFrequency='0.0035 0.011' numOctaves='5' seed='7' stitchTiles='stitch'/><feColorMatrix values='0 0 0 0 .79  0 0 0 0 .66  0 0 0 0 .3  -7 0 0 0 .95'/></filter><rect width='100%25' height='100%25' filter='url(%23m)'/></svg>");
    background-size:auto,auto,900px 900px;opacity:1}
body::after{content:'';position:fixed;inset:0;pointer-events:none;z-index:0;background:rgba(10,9,7,.88)}
a{color:inherit}
:focus-visible{outline:2px solid var(--gold);outline-offset:3px}

.grain{position:fixed;inset:0;z-index:4;pointer-events:none;opacity:.035;mix-blend-mode:overlay;background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='140' height='140'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/></filter><rect width='100%25' height='100%25' filter='url(%23n)'/></svg>")}

/* ── ARCHITECTURAL ORNAMENT PRIMITIVES ── */
.meander{height:16px;background:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16'><path d='M0 13H16M13 13V3H3V10H10V6' fill='none' stroke='%23c9a84c' stroke-width='1.4'/></svg>") repeat-x center;opacity:.8}
.dentil{height:9px;background:repeating-linear-gradient(90deg,var(--gold-dim) 0 7px,transparent 7px 14px);border-top:1px solid var(--gold-dim)}
.frieze{position:relative;z-index:1}
.frieze .dentil{opacity:.9}
.frieze .meander{margin:6px 0}
.pilaster{position:relative;width:46px;flex:none;
    background:repeating-linear-gradient(90deg,rgba(0,0,0,.6) 0 2px,transparent 2px 6px),linear-gradient(90deg,#17120a 0%,#6f5a26 34%,#d9b95a 50%,#7a622a 66%,#17120a 100%);
    border-left:1px solid rgba(0,0,0,.6);border-right:1px solid rgba(0,0,0,.6)}
.pilaster::before,.pilaster::after{content:'';position:absolute;left:-7px;right:-7px;height:15px;border-radius:2px;background:linear-gradient(180deg,#f0d78e,#8a6f2e);box-shadow:0 4px 10px rgba(0,0,0,.5)}
.pilaster::before{top:0}
.pilaster::after{bottom:0}
.steps{position:relative}
.steps i{display:block;height:10px;background:linear-gradient(180deg,#8a6f2e,#2e2410);border-top:1px solid #e6c777;box-shadow:0 5px 12px rgba(0,0,0,.5)}
.steps i:nth-child(2){margin:0 -14px}
.steps i:nth-child(3){margin:0 -28px}
.colonnade{position:relative}
.cn-pediment{position:relative;height:96px;margin:0 -12px}
.cn-pediment svg.tri{position:absolute;inset:0;width:100%;height:100%}
.cn-wreath{position:absolute;left:50%;bottom:6px;width:76px;height:76px;transform:translateX(-50%)}
.cn-entab{position:relative;margin:0 -12px;background:linear-gradient(180deg,#a8883a,#5d4a1d);border-top:1px solid #e6c777;padding:0}
.cn-entab .dentil{background:repeating-linear-gradient(90deg,#1a150b 0 7px,transparent 7px 14px);border-top:none;height:9px}
.cn-entab .meander{height:22px;background-color:#0d0a05;opacity:1}
.cn-body{position:relative;display:flex;align-items:stretch;gap:0;padding:0 8px}
.cn-body > .pilaster{margin:0 4px}

/* ── Cursor-following warm spotlight ── */
#cursorGlow{position:fixed;top:0;left:0;width:520px;height:520px;border-radius:50%;pointer-events:none;z-index:2;background:radial-gradient(circle,rgba(201,168,76,.07),transparent 70%);transform:translate(-50%,-50%);will-change:transform}
@media(hover:none),(pointer:coarse){#cursorGlow{display:none}}

/* ── Custom cursor ── */
#cursorRing,#cursorDot{position:fixed;top:0;left:0;pointer-events:none;z-index:9998;border-radius:50%;transform:translate(-50%,-50%);will-change:transform}
#cursorDot{width:5px;height:5px;background:var(--gold-bright);box-shadow:0 0 8px 2px rgba(230,199,119,.65)}
#cursorRing{width:32px;height:32px;border:1px solid rgba(201,168,76,.5);transition:width .25s var(--ease),height .25s var(--ease),border-color .25s ease,background .25s ease}
#cursorRing.hover{width:54px;height:54px;background:rgba(201,168,76,.08);border-color:var(--gold-bright)}
.coffee-particle{position:fixed;top:0;left:0;border-radius:50%;background:var(--gold);pointer-events:none;z-index:9997;will-change:transform}
@media(hover:none),(pointer:coarse){#cursorRing,#cursorDot{display:none}}

/* ── ENTRANCE LOADER ── */
.loader{position:fixed;inset:0;z-index:9999;background:var(--bg);display:flex;align-items:center;justify-content:center;transition:opacity .8s ease, visibility 0s linear .8s}
.loader.done{opacity:0;visibility:hidden}
.loader-glow{position:absolute;inset:0;background:radial-gradient(ellipse 55% 55% at 50% 45%,rgba(201,168,76,.12),transparent 65%)}
.loader-stage{position:relative}
.loader-word{position:absolute;bottom:-58px;left:0;right:0;text-align:center;font-family:var(--serif);font-size:13px;font-weight:500;letter-spacing:.4em;text-transform:uppercase;color:var(--gold);opacity:0;animation:loaderWord .8s ease .6s forwards}
@keyframes loaderWord{to{opacity:1}}
.loader-bar{position:absolute;bottom:-86px;left:50%;transform:translateX(-50%);width:170px;height:1px;background:var(--border);overflow:hidden}
.loader-bar span{position:absolute;inset:0;background:linear-gradient(90deg,var(--gold-dim),var(--gold-bright));transform:scaleX(0);transform-origin:left;animation:loaderBar 1.9s var(--ease) .3s forwards}
@keyframes loaderBar{to{transform:scaleX(1)}}

/* ── SCROLL PROGRESS ── */
.scroll-progress{position:fixed;top:0;left:0;height:2px;width:0%;background:linear-gradient(90deg,var(--gold-dim),var(--gold-bright));z-index:150;pointer-events:none}

/* ── REVEAL PRIMITIVES ── */
.reveal-el{opacity:0;transform:translateY(24px) rotateX(6deg);transition:opacity .9s var(--ease), transform .9s var(--ease)}
body.loaded .reveal-el{opacity:1;transform:translateY(0) rotateX(0)}
.reveal-el.d1{transition-delay:.05s}.reveal-el.d2{transition-delay:.2s}.reveal-el.d3{transition-delay:.35s}.reveal-el.d4{transition-delay:.5s}.reveal-el.d5{transition-delay:.65s}
[data-reveal]{opacity:0;transform:translateY(34px);transition:opacity .8s var(--ease), transform .8s var(--ease)}
[data-reveal].in-view{opacity:1;transform:translateY(0)}
[data-reveal="stagger"]{opacity:1;transform:none;transition:none}
[data-reveal="stagger"] > *{opacity:0;transform:translateY(28px) rotateX(4deg);transition:opacity .7s var(--ease), transform .7s var(--ease)}
[data-reveal="stagger"].in-view > *{opacity:1;transform:translateY(0) rotateX(0)}
[data-reveal="stagger"].in-view > *:nth-child(1){transition-delay:.04s}
[data-reveal="stagger"].in-view > *:nth-child(2){transition-delay:.16s}
[data-reveal="stagger"].in-view > *:nth-child(3){transition-delay:.28s}
.reveal-mask{overflow:hidden;padding-bottom:.14em;margin-bottom:-.14em}
.reveal-mask span{display:inline-block;transform:translateY(112%);transition:transform 1s var(--ease)}
body.loaded .reveal-mask span{transform:translateY(0)}
.hd-mask{display:inline-block;overflow:hidden;padding-bottom:.12em;margin-bottom:-.12em}
.hd-mask span{display:inline-block;transform:translateY(102%);transition:transform .9s var(--ease) .08s}
[data-reveal].in-view .hd-mask span{transform:translateY(0)}
.gold-text{background:linear-gradient(180deg,#f5dc92 0%,#c9a84c 55%,#9a7a2e 100%);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent}

/* ── TOP STRIP ── */
.top-strip{position:relative;z-index:101;background:#050403;color:var(--gold);text-align:center;padding:9px 16px 7px;font-family:var(--serif);font-size:10.5px;font-weight:500;letter-spacing:.36em;text-transform:uppercase;border-bottom:1px solid var(--gold-dim)}
.top-strip i{display:inline-block;width:4px;height:4px;background:var(--gold);transform:rotate(45deg);margin:0 14px;vertical-align:middle;opacity:.7}
@media(max-width:640px){.top-strip{letter-spacing:.2em;font-size:9.5px}.top-strip .ts-x{display:none}}

/* ── HEADER ── */
header{position:sticky;top:0;z-index:100;background:rgba(10,9,7,.7);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border-bottom:1px solid transparent;transition:background .4s var(--ease),border-color .4s var(--ease)}
header.scrolled{background:rgba(10,9,7,.94);border-bottom-color:var(--border)}
.header-inner{max-width:1240px;margin:0 auto;padding:0 32px;height:76px;display:flex;align-items:center;justify-content:space-between}
.brand{display:flex;align-items:center;gap:13px;text-decoration:none}
.brand-icon{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:linear-gradient(155deg,var(--gold-bright),var(--gold-dim));box-shadow:0 4px 16px -4px rgba(201,168,76,.6),inset 0 0 0 3px var(--bg),inset 0 0 0 4px rgba(201,168,76,.55);transition:transform .4s var(--ease)}
.brand:hover .brand-icon{transform:rotate(-14deg)}
.brand-name{font-family:var(--serif);font-size:20px;font-weight:600;color:var(--cream);letter-spacing:.1em}
.brand-name span{color:var(--gold);font-family:var(--disp);font-style:italic;font-weight:600;letter-spacing:.02em;font-size:23px}
nav{display:flex;align-items:center;gap:4px}
nav a{position:relative;font-size:12px;font-weight:500;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);text-decoration:none;padding:8px 14px;border-radius:2px;transition:color .2s}
nav a:not(.nav-cta)::after{content:'';position:absolute;left:14px;right:14px;bottom:4px;height:1px;background:var(--gold);transform:scaleX(0);transform-origin:left;transition:transform .35s var(--ease)}
nav a:not(.nav-cta):hover{color:var(--cream)}
nav a:not(.nav-cta):hover::after,nav a.active::after{transform:scaleX(1)}
nav a.active{color:var(--gold)}
.nav-cta{margin-left:12px;padding:11px 24px !important;background:linear-gradient(180deg,#e6c777,#b8903a) !important;color:#1a1400 !important;border-radius:2px;border:1px solid #f0d78e;transition:filter .25s !important,transform .25s !important;font-weight:600 !important}
.nav-cta:hover{filter:brightness(1.08);transform:translateY(-2px)}
@media(max-width:820px){nav a:not(.nav-cta){display:none}}

/* ── HERO ── */
.hero{position:relative;z-index:1;max-width:1240px;margin:0 auto;padding:80px 32px 70px;display:grid;grid-template-columns:1fr 1fr;gap:36px;align-items:center;transition:opacity .3s linear}
.hero-eyebrow{display:inline-flex;align-items:center;gap:12px;font-family:var(--serif);font-size:11px;font-weight:500;letter-spacing:.3em;text-transform:uppercase;color:var(--gold);margin-bottom:24px}
.hero-eyebrow::before{content:'';width:34px;height:1px;background:var(--gold-dim)}
.hero h1{font-family:var(--serif);font-size:clamp(34px,4.6vw,56px);font-weight:600;line-height:1.2;color:var(--cream);letter-spacing:.01em;margin-bottom:22px}
.hero h1 em{font-family:var(--disp);font-style:italic;font-weight:600;letter-spacing:0;font-size:1.12em;background:linear-gradient(180deg,#f5dc92 0%,#c9a84c 55%,#9a7a2e 100%);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent;padding-right:.08em}
.hero p.lede{font-size:15.5px;color:var(--muted);line-height:1.85;font-weight:300;max-width:480px;margin-bottom:38px}
.hero-ctas{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:8px}
.btn-primary{position:relative;overflow:hidden;display:inline-flex;align-items:center;gap:10px;padding:15px 30px;background:linear-gradient(180deg,#e6c777,#b8903a);border:1px solid #f0d78e;border-radius:2px;font-family:'Jost',sans-serif;font-size:13px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#1a1400;text-decoration:none;transition:filter .2s,transform .3s var(--ease);box-shadow:0 16px 32px -12px rgba(201,168,76,.45)}
.btn-primary::after{content:'';position:absolute;top:0;left:-60%;width:35%;height:100%;background:linear-gradient(115deg,transparent,rgba(255,255,255,.4),transparent);transform:skewX(-18deg);transition:left .65s ease}
.btn-primary:hover{filter:brightness(1.07);transform:translateY(-3px)}
.btn-primary:hover::after{left:130%}
.btn-ghost{display:inline-flex;align-items:center;gap:10px;padding:15px 28px;border:1px solid var(--gold-dim);border-radius:2px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:.12em;text-transform:uppercase;color:var(--gold);text-decoration:none;background:transparent;transition:all .3s var(--ease)}
.btn-ghost:hover{border-color:var(--gold-bright);color:var(--gold-bright);background:rgba(201,168,76,.07);transform:translateY(-3px)}
.hero-stats{display:flex;margin-top:50px;border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.hero-stats > div{flex:1;padding:20px 18px;border-right:1px solid var(--border)}
.hero-stats > div:first-child{padding-left:0}
.hero-stats > div:last-child{border-right:none}
.hstat-num{font-family:var(--serif);font-size:30px;font-weight:600;color:var(--gold-bright)}
.hstat-label{font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);margin-top:4px;max-width:14ch;line-height:1.5}

/* ── TRUST STRIP ── */
.trust-strip{position:relative;z-index:1;background:linear-gradient(180deg,var(--wine),var(--wine-dk));border-top:1px solid var(--gold-dim);border-bottom:1px solid var(--gold-dim)}
.trust-inner{max-width:1240px;margin:0 auto;padding:18px 32px;display:flex;justify-content:center;align-items:center;gap:26px;flex-wrap:wrap;font-family:var(--serif);font-size:11px;font-weight:500;letter-spacing:.26em;text-transform:uppercase;color:var(--gold)}
.trust-sep{width:5px;height:5px;background:var(--gold);transform:rotate(45deg);flex-shrink:0;opacity:.8}
@media(max-width:640px){.trust-inner{gap:12px 16px;padding:16px 16px;font-size:10px;letter-spacing:.16em}}

/* ── TEMPLE STAGE (hero) ── */
.cup-stage{position:relative;height:476px;transform-style:preserve-3d;perspective:1400px;opacity:0;transform:scale(.9) rotateY(-8deg);transition:opacity 1.1s var(--ease) .3s, transform 1.1s var(--ease) .3s}
body.loaded .cup-stage{opacity:1;transform:scale(1) rotateY(0)}
.temple-wrap{position:absolute;left:50%;top:0;width:460px;height:470px;margin-left:-230px}
.temple-svg{position:absolute;inset:0;display:block;overflow:visible;filter:drop-shadow(0 30px 40px rgba(0,0,0,.65))}
.hero-badge{position:absolute;top:-6px;right:-30px;width:100px;height:100px;z-index:5;opacity:0;transform:scale(.7) rotate(-14deg);transition:opacity .9s var(--ease) .95s, transform .9s var(--ease) .95s}
body.loaded .hero-badge{opacity:1;transform:scale(1) rotate(0)}
.badge-ring-svg{width:100%;height:100%;display:block;animation:spin 17s linear infinite;animation-play-state:paused}
body.loaded .badge-ring-svg{animation-play-state:running}
.badge-ring-svg text{fill:var(--gold);font-family:'Cinzel',serif;font-weight:600}
.hero-badge-center{position:absolute;inset:0;margin:auto;width:46px;height:46px;border-radius:50%;background:var(--bg);border:1px solid var(--gold-dim);display:flex;align-items:center;justify-content:center;box-shadow:0 8px 20px -8px rgba(0,0,0,.8)}
.hero-badge-center svg{width:32px;height:32px}
@media(max-width:1100px){.hero-badge{display:none}}
@keyframes spin{to{transform:rotate(360deg)}}

/* ── REALISTIC BEANS ── */
.bean-field{position:absolute;inset:0;pointer-events:none;z-index:3}
.loader .bean-field{inset:-70px}
.cb{position:absolute;display:block;overflow:visible;height:auto;aspect-ratio:60/84;filter:drop-shadow(0 10px 10px rgba(0,0,0,.6));animation:cbFloat 7s ease-in-out infinite;animation-play-state:paused}
body.loaded .cb,.loader .cb{animation-play-state:running}
.cb.far{filter:blur(1.4px) drop-shadow(0 6px 8px rgba(0,0,0,.45));opacity:.65}
@keyframes cbFloat{0%,100%{transform:translateY(0) rotate(var(--r))}50%{transform:translateY(-9px) rotate(calc(var(--r) + 7deg))}}
.cb1{top:10%;left:18%;width:38px;--r:-28deg;animation-delay:-1s}
.cb2{top:30%;right:7%;width:28px;--r:38deg;animation-delay:-3s}
.cb3{top:54%;left:5%;width:42px;--r:72deg;animation-delay:-2s}
.cb4{top:70%;right:9%;width:34px;--r:-48deg;animation-delay:-4.5s}
.cb5{bottom:4%;left:30%;width:26px;--r:12deg;animation-delay:-5.5s}
.cb6{top:6%;right:34%;width:24px;--r:55deg;animation-delay:-2.5s}
.temple-wrap .cb1{top:41%;left:25%;width:30px}
.temple-wrap .cb2{top:36%;right:23%;width:22px}
.temple-wrap .cb3{top:84%;left:12%;width:34px}
.temple-wrap .cb4{top:85%;right:13%;width:30px}
.temple-wrap .cb5{top:76%;left:27%;width:20px}
.temple-wrap .cb6{top:58%;right:25%;width:20px}

/* ── ESPRESSO CUP ── */
.cup3d{position:relative;width:240px;height:220px;transform-style:preserve-3d;animation:tilt 8s ease-in-out infinite;animation-play-state:paused}
.temple-wrap .cup3d{position:absolute;left:110px;top:196px;z-index:2}
body.loaded .cup3d{animation-play-state:running}
@keyframes tilt{0%,100%{transform:rotateY(-6deg) rotateX(3deg)}50%{transform:rotateY(6deg) rotateX(-1deg)}}
.cup-svg{display:block;overflow:visible;filter:drop-shadow(0 18px 22px rgba(0,0,0,.55))}
.steam{position:absolute;top:-8px;left:105px;width:7px;height:58px;opacity:0;filter:blur(1.5px)}
.steam span{position:absolute;bottom:0;left:0;width:100%;height:100%;background:linear-gradient(180deg,transparent,rgba(240,234,216,.5),transparent);border-radius:40%;animation:rise 3.2s ease-in infinite;animation-play-state:paused}
body.loaded .steam span{animation-play-state:running}
.steam.s2{left:119px}.steam.s2 span{animation-delay:.8s}
.steam.s3{left:133px}.steam.s3 span{animation-delay:1.6s}
@keyframes rise{0%{transform:translateY(0) scaleX(1);opacity:0}20%{opacity:.7}100%{transform:translateY(-72px) scaleX(1.9);opacity:0}}
@media(max-width:960px){.hero{grid-template-columns:1fr;text-align:center}.hero p.lede{margin-inline:auto}.hero-stats{justify-content:center}.hero-stats > div:first-child{padding-left:18px}.cup-stage{height:390px}.temple-wrap{transform:scale(.82);transform-origin:top center}.hero-ctas{justify-content:center}.hero-eyebrow::before{display:none}}

/* ── SECTIONS ── */
.section{position:relative;z-index:1;max-width:1240px;margin:0 auto;padding:96px 32px}
.section-hd{text-align:center;max-width:640px;margin:0 auto 54px}
.section-eyebrow{display:inline-flex;align-items:center;gap:12px;font-family:var(--serif);font-size:11px;font-weight:500;letter-spacing:.3em;text-transform:uppercase;color:var(--gold);margin-bottom:16px}
.section-eyebrow::before,.section-eyebrow::after{content:'';width:26px;height:1px;background:var(--gold-dim)}
.section-hd h2{font-family:var(--serif);font-size:clamp(26px,3.5vw,38px);font-weight:600;color:var(--cream);letter-spacing:.02em;line-height:1.25}
.section-hd h2 em{font-family:var(--disp);font-style:italic;font-weight:600;color:var(--gold-bright);font-size:1.12em;letter-spacing:0}
.section-hd p{font-size:14px;color:var(--muted);margin-top:16px;line-height:1.8;font-weight:300}
.section-hd .orn{display:flex;align-items:center;justify-content:center;gap:12px;margin-top:22px}
.section-hd .orn span{width:70px;height:1px;background:linear-gradient(90deg,transparent,var(--gold-dim))}
.section-hd .orn span:last-child{transform:scaleX(-1)}
.section-hd .orn svg{width:34px;height:34px;flex:none}

/* ── SIGNATURE COFFEE (arch-topped niches) ── */
.sig-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:28px}
.sig-card{position:relative;background:linear-gradient(180deg,#1b170e,#120f08);border:1px solid var(--border);border-radius:3px;padding:14px 14px 0;overflow:hidden;transform-style:preserve-3d;will-change:transform;box-shadow:0 24px 50px -30px rgba(0,0,0,.9),inset 0 0 0 5px #0f0c07,inset 0 0 0 6px rgba(201,168,76,.18);transition:transform .12s ease-out,border-color .3s,box-shadow .4s}
.sig-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,transparent,var(--gold),transparent);z-index:2}
.sig-card:hover{border-color:var(--gold-dim);box-shadow:0 40px 70px -28px rgba(0,0,0,.95),inset 0 0 0 5px #0f0c07,inset 0 0 0 6px rgba(201,168,76,.4)}
.sig-media{position:relative;aspect-ratio:1/1.02;overflow:hidden;border-radius:999px 999px 3px 3px;background:linear-gradient(160deg,#241d10,#171410);border:1px solid var(--gold-dim);margin:6px}
.sig-keystone{position:absolute;top:10px;left:50%;width:9px;height:9px;margin-left:-4.5px;background:linear-gradient(135deg,#f0d78e,#8a6f2e);transform:rotate(45deg);z-index:3;box-shadow:0 0 8px rgba(230,199,119,.6)}
.sig-media img{width:100%;height:100%;object-fit:cover;transition:transform .6s var(--ease)}
.sig-card:hover .sig-media img{transform:scale(1.08)}
.sig-media::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 55%,rgba(10,9,7,.8))}
.sig-rank{position:absolute;bottom:14px;left:14px;z-index:2;font-family:var(--serif);font-size:.62rem;font-weight:500;letter-spacing:.16em;text-transform:uppercase;background:rgba(10,9,7,.8);border:1px solid var(--gold-dim);color:var(--gold-bright);padding:5px 11px;border-radius:2px;backdrop-filter:blur(4px)}
.sig-cat{position:absolute;bottom:16px;right:16px;z-index:2;font-size:.66rem;letter-spacing:.14em;text-transform:uppercase;color:var(--gold-bright)}
.sig-body{padding:22px 14px 26px;transform:translateZ(24px);text-align:center}
.sig-name{font-family:var(--serif);font-size:1.2rem;font-weight:600;color:var(--cream);margin-bottom:8px;letter-spacing:.03em}
.sig-desc{font-size:.86rem;color:var(--muted);line-height:1.7;margin-bottom:18px;min-height:2.8em;font-weight:300}
.sig-foot{display:flex;align-items:center;justify-content:space-between;padding-top:18px;border-top:1px solid var(--border)}
.sig-price{font-family:var(--serif);font-weight:600;color:var(--gold-bright);font-size:1.15rem}
.sig-price small{font-size:.7rem;color:var(--gold-dim);font-weight:500;margin-right:2px}
.sig-add{font-family:'Jost',sans-serif;font-weight:600;font-size:.74rem;letter-spacing:.1em;text-transform:uppercase;padding:11px 18px;border-radius:2px;border:1px solid #f0d78e;cursor:pointer;background:linear-gradient(180deg,#e6c777,#b8903a);color:#1a1400;transition:filter .2s ease,transform .25s var(--ease)}
.sig-add:hover{filter:brightness(1.08);transform:translateY(-2px)}
.sig-add.added{background:var(--cream);border-color:var(--cream)}
.sig-add:disabled{background:#2a2316;color:#7a6c58;border-color:#2a2316;cursor:not-allowed}
.sig-empty{grid-column:1/-1;text-align:center;padding:50px;color:var(--muted);border:1px dashed var(--border);border-radius:4px}
@media(max-width:960px){.sig-grid{grid-template-columns:1fr;max-width:420px;margin-inline:auto}}

/* ── BRANCH SELECTION ── */
.branch-grid{display:grid;grid-template-columns:1fr 1fr;gap:28px}
.branch-card{position:relative;background:linear-gradient(180deg,#1b170e,#120f08);border:1px solid var(--border);border-radius:3px;padding:12px;overflow:hidden;transform-style:preserve-3d;will-change:transform;box-shadow:0 24px 50px -30px rgba(0,0,0,.9),inset 0 0 0 4px #0f0c07,inset 0 0 0 5px rgba(201,168,76,.18);transition:transform .35s var(--ease),border-color .3s,box-shadow .4s}
.branch-card:hover{border-color:var(--gold-dim);box-shadow:0 34px 70px -28px rgba(0,0,0,.95),inset 0 0 0 4px #0f0c07,inset 0 0 0 5px rgba(201,168,76,.4)}
.branch-media{position:relative;aspect-ratio:16/9;overflow:hidden;border-radius:2px;margin:4px}
.branch-media img{width:100%;height:100%;object-fit:cover;transition:transform .7s var(--ease)}
.branch-card:hover .branch-media img{transform:scale(1.06)}
.branch-media::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 40%,rgba(10,9,7,.88))}
.branch-tag{position:absolute;bottom:16px;left:20px;z-index:2;display:inline-flex;align-items:center;gap:8px;font-family:var(--serif);font-size:11px;font-weight:500;letter-spacing:.22em;text-transform:uppercase;color:var(--gold-bright)}
.branch-tag .dot{width:6px;height:6px;background:var(--gold-bright);transform:rotate(45deg)}
.branch-card.active-branch{border-color:var(--gold-bright);box-shadow:0 0 0 1px var(--gold-bright),0 30px 60px -30px rgba(201,168,76,.35),inset 0 0 0 4px #0f0c07,inset 0 0 0 5px rgba(201,168,76,.5)}
.branch-card.active-branch::before{content:'Selected';position:absolute;top:24px;right:24px;z-index:4;font-family:var(--serif);font-size:9.5px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;background:linear-gradient(180deg,#e6c777,#b8903a);color:#1a1400;padding:5px 11px;border-radius:2px}
.branch-body{padding:24px 18px 18px}
.branch-body h3{font-family:var(--serif);font-size:28px;font-weight:600;color:var(--cream);margin-bottom:10px;letter-spacing:.04em}
.branch-body p{font-size:13.5px;color:var(--muted);line-height:1.8;margin-bottom:20px;font-weight:300}
.branch-meta{display:flex;justify-content:space-between;align-items:center;padding-top:16px;border-top:1px solid var(--border)}
.branch-count{font-family:var(--mono);font-size:.72rem;color:var(--gold-bright);letter-spacing:.06em}
.branch-link{display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:var(--gold);text-decoration:none}
.branch-link svg{transition:transform .25s var(--ease)}
.branch-card:hover .branch-link svg{transform:translateX(5px)}

/* ── STORY (wine-black band) ── */
.band-dark{position:relative;z-index:1;background:radial-gradient(ellipse 70% 80% at 85% 20%,rgba(201,168,76,.12),transparent 60%),linear-gradient(180deg,#1b0910,#0d0508);color:var(--cream);border-top:1px solid var(--gold-dim);border-bottom:1px solid var(--gold-dim)}
.band-dark .frieze{padding:10px 0 4px}
.story-section{position:relative;overflow:hidden}
.story-grid{display:grid;grid-template-columns:.85fr 1.15fr;gap:72px;align-items:center}
.story-fig{position:relative;display:flex;justify-content:center;align-items:stretch;gap:0;padding:0 4px}
.story-fig .pilaster{margin:0 10px}
.story-media{position:relative;flex:1;aspect-ratio:4/5;border-radius:999px 999px 3px 3px;overflow:hidden;border:1px solid var(--gold);box-shadow:0 0 0 6px #0d0508,0 0 0 7px rgba(201,168,76,.5),0 40px 70px -30px rgba(0,0,0,.9)}
.story-media img{width:100%;height:100%;object-fit:cover}
.story-media::before{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 55%,rgba(13,5,8,.7));z-index:1}
.story-copy h2{font-family:var(--serif);font-size:clamp(1.8rem,3.6vw,2.7rem);font-weight:600;line-height:1.25;margin-bottom:24px;color:var(--cream);letter-spacing:.02em}
.story-copy h2 em{font-family:var(--disp);font-style:italic;font-weight:600;color:var(--gold-bright);font-size:1.12em;letter-spacing:0}
.story-copy p{color:rgba(240,234,216,.7);line-height:1.95;font-weight:300;margin-bottom:18px;max-width:54ch;font-size:14.5px}
.floating-bean{position:absolute;width:13px;height:18px;border-radius:52% 48% 50% 50%/62% 62% 38% 38%;background:linear-gradient(140deg,var(--gold-bright),var(--gold-dim));opacity:.3;animation:beanFloat 10s ease-in-out infinite;pointer-events:none}
@keyframes beanFloat{0%,100%{transform:translateY(0) rotate(0deg)}50%{transform:translateY(-24px) rotate(28deg)}}
@media(max-width:900px){.story-grid{grid-template-columns:1fr;gap:48px}.story-fig{max-width:360px;margin-inline:auto}}

/* ── VALUE PROPS (temple colonnade) ── */
.props-grid{flex:1;display:grid;grid-template-columns:repeat(3,1fr);gap:0;background:linear-gradient(180deg,#17130c,#0f0c07);border-top:1px solid #000;border-bottom:1px solid #000}
.prop-card{position:relative;padding:44px 28px 40px;text-align:center;will-change:transform;border-right:1px solid var(--border);transition:background .35s var(--ease)}
.prop-card:last-child{border-right:none}
.prop-card:hover{background:rgba(201,168,76,.05)}
.prop-num{position:absolute;top:16px;left:0;right:0;font-family:var(--serif);font-size:11px;font-weight:500;letter-spacing:.3em;color:var(--gold-dim)}
.prop-icon{width:56px;height:56px;margin:12px auto 20px;border:1px solid var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--gold);box-shadow:inset 0 0 0 4px #14100a,inset 0 0 0 5px rgba(201,168,76,.4);transition:transform .35s var(--ease),border-color .3s}
.prop-card:hover .prop-icon{transform:rotate(-10deg) scale(1.08);border-color:var(--gold)}
.prop-card h4{font-family:var(--serif);font-size:16px;font-weight:600;color:var(--cream);margin-bottom:10px;letter-spacing:.08em;text-transform:uppercase}
.prop-card p{font-size:13px;color:var(--muted);line-height:1.8;font-weight:300}
@media(max-width:900px){.props-grid{grid-template-columns:1fr}.prop-card{border-right:none;border-bottom:1px solid var(--border)}.prop-card:last-child{border-bottom:none}.cn-body .pilaster{width:26px}}

/* ── TESTIMONIALS ── */
.testi-page{display:none;grid-template-columns:repeat(3,1fr);gap:24px}
.testi-page.active{display:grid}
.testi-card{position:relative;background:linear-gradient(180deg,#1b170e,#120f08);border:1px solid var(--border);border-radius:3px;padding:36px 28px 28px;will-change:transform;box-shadow:0 24px 50px -30px rgba(0,0,0,.9),inset 0 0 0 4px #0f0c07,inset 0 0 0 5px rgba(201,168,76,.15);transition:transform .35s var(--ease),border-color .3s,box-shadow .4s}
.testi-card::before{content:'\201C';position:absolute;top:4px;left:24px;font-family:var(--disp);font-size:72px;line-height:1;color:rgba(201,168,76,.3)}
.testi-card:hover{border-color:var(--gold-dim);box-shadow:0 28px 50px -26px rgba(0,0,0,.95),inset 0 0 0 4px #0f0c07,inset 0 0 0 5px rgba(201,168,76,.35)}
.testi-stars{display:flex;gap:3px;margin-bottom:14px;color:var(--gold-bright);justify-content:flex-end}
.testi-stars svg{width:12px;height:12px}
.testi-msg{font-family:var(--disp);font-style:italic;font-size:18px;color:var(--cream);line-height:1.6;margin-bottom:22px;min-height:5.6em}
.testi-who{display:flex;align-items:center;gap:12px;padding-top:18px;border-top:1px solid var(--border)}
.testi-avatar{width:38px;height:38px;border-radius:50%;object-fit:cover;border:1px solid var(--gold-dim);background:var(--surface)}
.testi-avatar-fallback{width:38px;height:38px;border-radius:50%;border:1px solid var(--gold-dim);background:var(--surface);display:flex;align-items:center;justify-content:center;font-family:var(--serif);font-size:13px;font-weight:600;color:var(--gold)}
.testi-name{font-family:var(--serif);font-size:13px;font-weight:600;color:var(--cream);letter-spacing:.06em}
.testi-branch{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.14em;margin-top:2px}
.testi-pager{display:flex;justify-content:center;gap:12px;margin-top:40px}
.testi-dot{width:8px;height:8px;background:var(--border);border:none;cursor:pointer;transform:rotate(45deg);transition:background .25s ease,transform .25s ease}
.testi-dot.active{background:var(--gold-bright);transform:rotate(45deg) scale(1.4)}
@media(max-width:900px){.testi-page{grid-template-columns:1fr}}

/* ── CTA (temple front) ── */
.cta-wrap{position:relative;z-index:1;max-width:1240px;margin:0 auto 96px;padding:0 32px}
.cta-band{flex:1;position:relative;padding:64px 34px 58px;text-align:center;overflow:hidden;background:radial-gradient(ellipse 60% 80% at 50% 0%,rgba(201,168,76,.14),transparent 70%),linear-gradient(180deg,#1b0910,#0d0508)}
.cta-steam{position:absolute;top:12%;left:50%;transform:translateX(-50%);width:8px;height:100px;opacity:.3;filter:blur(1.5px)}
.cta-band h2{position:relative;font-family:var(--serif);font-size:clamp(26px,4.2vw,40px);font-weight:600;color:var(--cream);margin-bottom:16px;letter-spacing:.03em}
.cta-band h2 em{font-family:var(--disp);font-style:italic;font-weight:600;color:var(--gold-bright);font-size:1.12em;letter-spacing:0}
.cta-band p{position:relative;font-size:14px;color:rgba(240,234,216,.7);margin-bottom:32px;max-width:48ch;margin-inline:auto;line-height:1.8;font-weight:300}
.cta-band .btn-primary{position:relative}
@media(max-width:640px){.cta-wrap{padding:0 16px}.cta-band{padding:56px 18px 50px}}

/* ── FOOTER ── */
footer{position:relative;z-index:1;background:#050403;color:rgba(240,234,216,.65);border-top:1px solid var(--gold-dim)}
.footer-main{padding:30px 32px 28px}
.footer-inner{max-width:1240px;margin:0 auto;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:20px}
.footer-brand{display:flex;align-items:center;gap:12px;font-family:var(--serif);font-size:16px;font-weight:600;letter-spacing:.14em;color:var(--cream)}
.footer-brand .dot{width:30px;height:30px;border-radius:50%;background:linear-gradient(155deg,var(--gold-bright),var(--gold-dim));display:flex;align-items:center;justify-content:center}
.footer-links{display:flex;gap:26px;flex-wrap:wrap}
.footer-links a{font-size:11.5px;color:rgba(240,234,216,.6);letter-spacing:.16em;text-transform:uppercase;text-decoration:none;transition:color .2s ease}
.footer-links a:hover{color:var(--gold-bright)}
.footer-bottom{max-width:1240px;margin:26px auto 0;padding-top:20px;border-top:1px solid rgba(201,168,76,.2);display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;font-size:11.5px;color:rgba(240,234,216,.45)}
footer a.credit{color:var(--gold);text-decoration:none}
footer a.credit:hover{color:var(--cream);text-decoration:underline}

@media(max-width:900px){.branch-grid{grid-template-columns:1fr}.section{padding:72px 20px}.header-inner{padding:0 18px}.hero{padding:56px 20px 56px}}
</style>
</head>
<body>

<!-- Shared SVG sprite: gradients, patterns, realistic bean, cup + saucer, fluted column -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
<defs>
    <radialGradient id="rbBody" cx="34%" cy="24%" r="85%">
        <stop offset="0" stop-color="#8b5a33"/><stop offset=".45" stop-color="#5a341a"/><stop offset="1" stop-color="#1e0f06"/>
    </radialGradient>
    <radialGradient id="rbShine" cx="50%" cy="50%" r="50%">
        <stop offset="0" stop-color="#fff" stop-opacity=".55"/><stop offset="1" stop-color="#fff" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="rbCup" x1="0" y1="0" x2="1" y2="0">
        <stop offset="0" stop-color="#3b2f18"/><stop offset=".5" stop-color="#241c0f"/><stop offset="1" stop-color="#120f09"/>
    </linearGradient>
    <radialGradient id="rbCrema" cx="32%" cy="30%" r="80%">
        <stop offset="0" stop-color="#e6c777"/><stop offset=".42" stop-color="#a97a3e"/><stop offset="1" stop-color="#3d2612"/>
    </radialGradient>
    <linearGradient id="rbSaucer" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="#2a2212"/><stop offset="1" stop-color="#120f09"/>
    </linearGradient>
    <filter id="rbBlur" x="-60%" y="-60%" width="220%" height="220%"><feGaussianBlur stdDeviation="5"/></filter>
    <filter id="rbBlurS" x="-60%" y="-60%" width="220%" height="220%"><feGaussianBlur stdDeviation="2.5"/></filter>

    <!-- architecture materials -->
    <linearGradient id="tGold" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f0d78e"/><stop offset=".5" stop-color="#c9a84c"/><stop offset="1" stop-color="#7a5f22"/></linearGradient>
    <linearGradient id="tGoldDim" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#b8943f"/><stop offset="1" stop-color="#4a3a17"/></linearGradient>
    <linearGradient id="tShaft" x1="0" y1="0" x2="1" y2="0">
        <stop offset="0" stop-color="#15100a"/><stop offset=".2" stop-color="#7a6228"/><stop offset=".38" stop-color="#e9cb74"/><stop offset=".55" stop-color="#b6923d"/><stop offset=".8" stop-color="#4b3b18"/><stop offset="1" stop-color="#110d07"/>
    </linearGradient>
    <linearGradient id="tStone" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#241d10"/><stop offset="1" stop-color="#0d0a05"/></linearGradient>
    <radialGradient id="tGlow" cx="50%" cy="58%" r="62%"><stop offset="0" stop-color="#c9a84c" stop-opacity=".38"/><stop offset=".6" stop-color="#5a1522" stop-opacity=".25"/><stop offset="1" stop-color="#000" stop-opacity="0"/></radialGradient>
    <pattern id="tFlute" width="5.6" height="20" patternUnits="userSpaceOnUse">
        <rect x="0" width="2.2" height="20" fill="#000" opacity=".5"/>
        <rect x="2.6" width=".7" height="20" fill="#fff3c8" opacity=".22"/>
    </pattern>
    <pattern id="tMeander" x="0" y="120" width="22" height="22" patternUnits="userSpaceOnUse">
        <path d="M0 18H22M18 18V4H4V14H14V8" fill="none" stroke="#c9a84c" stroke-width="1.6"/>
    </pattern>
    <pattern id="tDentil" x="0" y="112" width="12" height="8" patternUnits="userSpaceOnUse">
        <rect width="6" height="8" fill="#c9a84c" opacity=".9"/>
    </pattern>

    <!-- realistic coffee bean -->
    <symbol id="rbBean" viewBox="0 0 60 84" overflow="visible">
        <path d="M30 3C47 3 57 21 57 44S47 81 30 81 3 65 3 42 13 3 30 3Z" fill="url(#rbBody)"/>
        <path d="M30 3C47 3 57 21 57 44S47 81 30 81 3 65 3 42 13 3 30 3Z" fill="none" stroke="#e6c777" stroke-opacity=".28" stroke-width=".9"/>
        <ellipse cx="21" cy="21" rx="9" ry="14" fill="url(#rbShine)" transform="rotate(-18 21 21)"/>
        <path d="M30 6C19 24 41 33 30 46C20 58 40 66 30 79" fill="none" stroke="#0d0603" stroke-width="4.2" stroke-linecap="round"/>
        <path d="M30 6C19 24 41 33 30 46C20 58 40 66 30 79" fill="none" stroke="#e6c777" stroke-opacity=".3" stroke-width="1.1" stroke-linecap="round" transform="translate(2.6 0)"/>
    </symbol>

    <!-- full cup + saucer artwork -->
    <g id="cupArt">
        <ellipse cx="120" cy="200" rx="104" ry="9" fill="#000" opacity=".5" filter="url(#rbBlur)"/>
        <ellipse cx="120" cy="184" rx="102" ry="13" fill="#0d0b07"/>
        <ellipse cx="120" cy="177" rx="108" ry="16" fill="url(#rbSaucer)" stroke="#3a3324" stroke-width="1"/>
        <ellipse cx="120" cy="175.5" rx="100" ry="14" fill="none" stroke="#e6c777" stroke-width=".6" opacity=".35"/>
        <ellipse cx="120" cy="178" rx="82" ry="10.5" fill="#0f0d08" stroke="#8a6f2e" stroke-width=".7" opacity=".95"/>
        <use href="#rbBean" x="188" y="166" width="13" height="18" transform="rotate(32 194.5 175)"/>
        <use href="#rbBean" x="203" y="170" width="11" height="15" transform="rotate(-20 208.5 177.5)"/>
        <use href="#rbBean" x="20" y="168" width="12" height="16" transform="rotate(15 26 176)"/>
        <ellipse cx="120" cy="176" rx="40" ry="7" fill="#000" opacity=".6" filter="url(#rbBlurS)"/>
        <path d="M176,64 C214,58 216,118 166,126" fill="none" stroke="#8a6f2e" stroke-width="9" stroke-linecap="round"/>
        <path d="M176,64 C214,58 216,118 166,126" fill="none" stroke="#e6c777" stroke-width="2" stroke-linecap="round" opacity=".45"/>
        <path d="M96,164 L144,164 L142,174 Q120,180 98,174 Z" fill="#14110c" stroke="#8a6f2e" stroke-width="1"/>
        <path d="M62,50 C62,118 84,160 100,165 L140,165 C156,160 178,118 178,50 Z" fill="url(#rbCup)" stroke="#8a6f2e" stroke-width="1"/>
        <path d="M72,64 C73,100 82,136 97,154" stroke="#e6c777" stroke-width="2.2" stroke-linecap="round" fill="none" opacity=".4"/>
        <path d="M168,70 C167,100 160,130 150,150" stroke="#fff" stroke-width="5" stroke-linecap="round" fill="none" opacity=".05"/>
        <path d="M62.6,70 Q120,82 177.4,70" stroke="#8a6f2e" stroke-width="1" fill="none" opacity=".7"/>
        <ellipse cx="120" cy="50" rx="58" ry="11" fill="#1a150c"/>
        <ellipse cx="120" cy="52" rx="52" ry="9" fill="url(#rbCrema)"/>
        <path d="M82,52 Q120,62 158,52" stroke="#e6c777" stroke-width=".9" fill="none" opacity=".35"/>
        <path d="M92,55 Q120,48 148,55" stroke="#3d2612" stroke-width=".9" fill="none" opacity=".4"/>
        <ellipse cx="120" cy="50" rx="58" ry="11" fill="none" stroke="#e6c777" stroke-width="1.4"/>
    </g>

    <!-- Ionic fluted column, centered on x=0 (shaft 180→402, capital above, base below) -->
    <g id="tCol">
        <rect x="-30" y="410" width="60" height="14" fill="url(#tGoldDim)" stroke="#e6c777" stroke-width=".5"/>
        <rect x="-25" y="402" width="50" height="9" rx="4" fill="url(#tGold)"/>
        <path d="M-19,180 C-20,260 -21,340 -22,402 L22,402 C21,340 20,260 19,180 Z" fill="url(#tShaft)"/>
        <path d="M-19,180 C-20,260 -21,340 -22,402 L22,402 C21,340 20,260 19,180 Z" fill="url(#tFlute)"/>
        <rect x="-21" y="177" width="42" height="4" fill="url(#tGold)"/>
        <path d="M-24,168 Q-24,178 -19,180 L19,180 Q24,178 24,168 Z" fill="url(#tGoldDim)" stroke="#e6c777" stroke-width=".4"/>
        <rect x="-34" y="160" width="68" height="8" rx="3" fill="url(#tGold)"/>
        <circle cx="-34" cy="164" r="7.5" fill="url(#tGold)"/><circle cx="-34" cy="164" r="3.6" fill="none" stroke="#3a2c0e" stroke-width="1.3"/><circle cx="-34" cy="164" r="1" fill="#3a2c0e"/>
        <circle cx="34" cy="164" r="7.5" fill="url(#tGold)"/><circle cx="34" cy="164" r="3.6" fill="none" stroke="#3a2c0e" stroke-width="1.3"/><circle cx="34" cy="164" r="1" fill="#3a2c0e"/>
        <rect x="-40" y="152" width="80" height="8" fill="url(#tGold)"/>
    </g>
</defs>
</svg>

<div class="loader" id="loader" aria-hidden="true">
    <div class="loader-glow"></div>
    <div class="loader-stage">
        <div class="bean-field">
            <svg class="cb cb1 far" viewBox="0 0 60 84"><use href="#rbBean"/></svg>
            <svg class="cb cb3" viewBox="0 0 60 84"><use href="#rbBean"/></svg>
            <svg class="cb cb4" viewBox="0 0 60 84"><use href="#rbBean"/></svg>
            <svg class="cb cb6 far" viewBox="0 0 60 84"><use href="#rbBean"/></svg>
        </div>
        <div class="cup3d" style="animation-play-state:running">
            <svg class="cup-svg" viewBox="0 0 240 220" width="240" height="220" aria-hidden="true"><use href="#cupArt"/></svg>
            <div class="steam s1" style="opacity:1"><span></span></div>
            <div class="steam s2" style="opacity:1"><span></span></div>
            <div class="steam s3" style="opacity:1"><span></span></div>
        </div>
        <div class="loader-word">SIPPERÉ Café</div>
        <div class="loader-bar"><span></span></div>
    </div>
</div>

<div id="cursorGlow"></div>
<div id="cursorRing"></div>
<div id="cursorDot"></div>
<div class="grain"></div>
<div class="scroll-progress" id="scrollProgress"></div>

<div class="top-strip">Laguna<i></i>Manila<i class="ts-x"></i><span class="ts-x">Anno MMXXVI</span></div>

<header id="acnHeader">
    <div class="header-inner">
        <a href="index.php" class="brand">
            <div class="brand-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#0b0b09" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg></div>
            <span class="brand-name">SIPPERÉ <span> Café</span></span>
        </a>
        <nav>
            <a href="index.php" class="active">Home</a>
            <a href="order-type.php">Menu</a>
            <a href="testimonial.php">Testimonials</a>
            <a href="includes/about-us.php">About Us</a>
            <?php if ($isLoggedIn): ?>
                <a href="profile.php">Profile</a>
                <a href="order-type.php" class="nav-cta magnetic">Order Now</a>
            <?php else: ?>
                <a href="log-in.php">Login</a>
                <a href="order-type.php" class="nav-cta magnetic">Order Now</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<!-- HERO -->
<section class="hero">
    <div>
        <div class="hero-eyebrow reveal-el d1">Est. MMXXVI &middot; Laguna &amp; Manila</div>
        <h1 class="reveal-mask"><span>The Pursuit of</span></h1>
        <h1 class="reveal-mask" style="margin-top:-8px;margin-bottom:22px"><span><em>Exceptional Coffee.</em></span></h1>
        <p class="lede reveal-el d3">A specialty coffee house built on discipline, not shortcuts. Every bean is chosen with intent, every shot pulled to order, every cup held to the same uncompromising standard — across both branches, every single day.</p>
        <div class="hero-ctas reveal-el d4">
            <a href="menu.php" class="btn-primary magnetic">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                View Full Menu
            </a>
            <a href="#branches" class="btn-ghost magnetic">Our Branches</a>
        </div>
        <div class="hero-stats reveal-el d5">
            <div><div class="hstat-num" data-count="<?= $totalMenuItems ?>">0</div><div class="hstat-label">Premium Coffee Selection</div></div>
            <div><div class="hstat-num" data-count="6250">0</div><div class="hstat-label">Satisfied Customers</div></div>
            <div><div class="hstat-num" data-count="7">0</div><div class="hstat-label">Years of Craftsmanship</div></div>
        </div>
    </div>

    <div class="cup-stage" aria-hidden="true" id="heroCupStage">
        <div class="hero-badge">
            <svg class="badge-ring-svg" viewBox="0 0 200 200">
                <defs><path id="badgeCirclePath" d="M100,100 m-82,0 a82,82 0 1,1 164,0 a82,82 0 1,1 -164,0"/></defs>
                <text font-size="11.5" letter-spacing="4">
                    <textPath href="#badgeCirclePath" startOffset="0%">FRESHLY BREWED &#8226; LAGUNA &amp; MANILA &#8226; FRESHLY BREWED &#8226; LAGUNA &amp; MANILA &#8226;</textPath>
                </text>
            </svg>
            <div class="hero-badge-center">
                <svg viewBox="-17 -17 34 34"><g data-wreath="12" data-pairs="6"></g></svg>
            </div>
        </div>

        <div class="temple-wrap">
            <!-- Greek temple front: pediment · entablature · fluted Ionic columns · arched niche · pedestal · stylobate -->
            <svg class="temple-svg" viewBox="0 0 460 470" width="460" height="470" aria-hidden="true">
                <ellipse cx="230" cy="300" rx="215" ry="175" fill="url(#tGlow)"/>
                <!-- steps -->
                <rect x="6" y="446" width="448" height="14" fill="url(#tStone)" stroke="#8a6f2e" stroke-width=".8"/>
                <rect x="6" y="446" width="448" height="2" fill="#e6c777" opacity=".5"/>
                <rect x="18" y="435" width="424" height="11" fill="url(#tStone)" stroke="#8a6f2e" stroke-width=".8"/>
                <rect x="18" y="435" width="424" height="2" fill="#e6c777" opacity=".55"/>
                <rect x="30" y="424" width="400" height="11" fill="url(#tStone)" stroke="#8a6f2e" stroke-width=".8"/>
                <rect x="30" y="424" width="400" height="2" fill="#e6c777" opacity=".65"/>
                <!-- back wall + arched niche -->
                <rect x="30" y="152" width="400" height="272" fill="#0b0805"/>
                <path d="M104,424 V278 A126,126 0 0 1 356,278 V424 Z" fill="#0c0905" stroke="#8a6f2e" stroke-width="1.5"/>
                <path d="M112,424 V278 A118,118 0 0 1 348,278 V424 Z" fill="url(#tGlow)" stroke="#e6c777" stroke-width=".6" opacity=".95"/>
                <path d="M122,424 V280 A108,108 0 0 1 338,280 V424" fill="none" stroke="#8a6f2e" stroke-width=".8" stroke-dasharray="2 4" opacity=".7"/>
                <path d="M230,152 l7,9 l-7,9 l-7,-9 Z" fill="url(#tGold)"/>
                <!-- pedestal for the cup -->
                <rect x="112" y="412" width="236" height="12" fill="url(#tGoldDim)" stroke="#e6c777" stroke-width=".5"/>
                <rect x="124" y="398" width="212" height="14" fill="url(#tStone)" stroke="#8a6f2e" stroke-width=".8"/>
                <rect x="116" y="392" width="228" height="6" fill="url(#tGold)"/>
                <!-- columns -->
                <use href="#tCol" x="60" y="0"/>
                <use href="#tCol" transform="translate(460 0) scale(-1 1)" x="60" y="0"/>
                <!-- entablature -->
                <rect x="14" y="142" width="432" height="10" fill="url(#tGold)"/>
                <rect x="14" y="120" width="432" height="22" fill="#0d0a05"/>
                <rect x="14" y="120" width="432" height="22" fill="url(#tMeander)"/>
                <rect x="14" y="112" width="432" height="8" fill="#14100a"/>
                <rect x="14" y="112" width="432" height="8" fill="url(#tDentil)"/>
                <rect x="8" y="104" width="444" height="8" fill="url(#tGold)"/>
                <!-- pediment -->
                <polygon points="8,104 230,12 452,104" fill="url(#tGoldDim)" stroke="#e6c777" stroke-width=".8"/>
                <polygon points="46,98 230,30 414,98" fill="#0d0a05" stroke="#e6c777" stroke-width=".7"/>
                <polygon points="60,94 230,38 400,94" fill="none" stroke="#8a6f2e" stroke-width=".6" stroke-dasharray="2 3"/>
                <g data-wreath="21" data-pairs="8" transform="translate(230,74)"></g>
                <path d="M230,22 l-9,-10 q9,-9 18,0 z" fill="url(#tGold)"/>
                <path d="M10,104 l-6,-8 q9,-4 14,6z M450,104 l6,-8 q-9,-4 -14,6z" fill="url(#tGold)"/>
            </svg>

            <div class="bean-field">
                <svg class="cb cb1" viewBox="0 0 60 84"><use href="#rbBean"/></svg>
                <svg class="cb cb2 far" viewBox="0 0 60 84"><use href="#rbBean"/></svg>
                <svg class="cb cb3" viewBox="0 0 60 84"><use href="#rbBean"/></svg>
                <svg class="cb cb4" viewBox="0 0 60 84"><use href="#rbBean"/></svg>
                <svg class="cb cb5 far" viewBox="0 0 60 84"><use href="#rbBean"/></svg>
                <svg class="cb cb6 far" viewBox="0 0 60 84"><use href="#rbBean"/></svg>
            </div>
            <div class="cup3d">
                <svg class="cup-svg" viewBox="0 0 240 220" width="240" height="220" aria-hidden="true"><use href="#cupArt"/></svg>
                <div class="steam"><span></span></div>
                <div class="steam s2"><span></span></div>
                <div class="steam s3"><span></span></div>
            </div>
        </div>
    </div>
</section>

<!-- TRUST STRIP -->
<div class="trust-strip" data-reveal>
    <div class="trust-inner">
        <span>Two Branches</span>
        <span class="trust-sep"></span>
        <span>Made to Order</span>
        <span class="trust-sep"></span>
        <span>Est. MMXXVI</span>
        <span class="trust-sep"></span>
        <span>Laguna &middot; Manila</span>
    </div>
</div>

<!-- SIGNATURE COFFEE -->
<section class="section">
    <div class="section-hd" data-reveal>
        <div class="section-eyebrow" style="justify-content:center">I &middot; Best Selling</div>
        <h2 class="hd-mask"><span>Our <em>Signature</em> Coffee</span></h2>
        <p>The three most-ordered pieces from the <?= ucfirst($branch) ?> branch — ranked by what our regulars actually order, not by guesswork.</p>
        <div class="orn"><span></span><svg viewBox="-17 -17 34 34"><g data-wreath="12" data-pairs="6"></g></svg><span></span></div>
    </div>
    <div class="sig-grid" data-reveal="stagger">
        <?php if (empty($signature)): ?>
            <div class="sig-empty">No signature items yet — check back soon.</div>
        <?php else: foreach ($signature as $i => $p): ?>
        <article class="sig-card">
            <div class="sig-media">
                <span class="sig-keystone"></span>
                <span class="sig-rank">No. <?= $i + 1 ?> Best Seller</span>
                <span class="sig-cat"><?= htmlspecialchars(ucfirst($p['category'] ?? 'coffee')) ?></span>
                <?php if (!empty($p['image'])): ?>
                    <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy" onerror="this.style.display='none'">
                <?php endif; ?>
            </div>
            <div class="sig-body">
                <h3 class="sig-name"><?= htmlspecialchars($p['name']) ?></h3>
                <p class="sig-desc"><?= !empty($p['description']) ? htmlspecialchars(mb_strimwidth($p['description'], 0, 90, '…')) : 'A signature preparation, crafted fresh to order every time.' ?></p>
                <div class="sig-foot">
                    <div class="sig-price"><small>₱</small><?= number_format((float)$p['price'], 2) ?></div>
                    <button class="sig-add" onclick="addSignature(<?= (int)$p['id'] ?>, this)">Add to Order</button>
                </div>
            </div>
        </article>
        <?php endforeach; endif; ?>
    </div>
</section>

<!-- BRANCH SELECTION -->
<section class="section" id="branches" style="padding-top:20px">
    <div class="section-hd" data-reveal>
        <div class="section-eyebrow" style="justify-content:center">II &middot; Find Us</div>
        <h2 class="hd-mask"><span>Two Branches, <em>One Craft</em></span></h2>
        <p>Same standard, same recipes, different neighborhoods. Choose the one you're ordering from.</p>
        <div class="orn"><span></span><svg viewBox="-17 -17 34 34"><g data-wreath="12" data-pairs="6"></g></svg><span></span></div>
    </div>
    <div class="branch-grid" data-reveal="stagger">
        <div class="branch-card <?= $branch === 'laguna' ? 'active-branch' : '' ?>">
            <div class="branch-media">
                <img src="https://images.unsplash.com/photo-1554118811-1e0d58224f24?q=80&w=900&auto=format&fit=crop" alt="Laguna Branch interior" loading="lazy">
                <div class="branch-tag"><span class="dot"></span>Laguna Branch</div>
            </div>
            <div class="branch-body">
                <h3>Laguna</h3>
                <p>Our original branch — mains, sides, drinks, and desserts made fresh daily in a warm, unhurried space.</p>
                <div class="branch-meta">
                    <span class="branch-count"><?= $branchCounts['laguna'] ?> menu items</span>
                    <a href="?branch=laguna#branches" class="branch-link">Select Laguna <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
                </div>
            </div>
        </div>
        <div class="branch-card manila <?= $branch === 'manila' ? 'active-branch' : '' ?>">
            <div class="branch-media">
                <img src="https://images.unsplash.com/photo-1445116572660-236099ec97a0?q=80&w=900&auto=format&fit=crop" alt="Manila Branch interior" loading="lazy">
                <div class="branch-tag"><span class="dot"></span>Manila Branch</div>
            </div>
            <div class="branch-body">
                <h3>Manila</h3>
                <p>Our city branch — coffee, meals, and a curated champagne list for a slightly different occasion.</p>
                <div class="branch-meta">
                    <span class="branch-count"><?= $branchCounts['manila'] ?> menu items</span>
                    <a href="?branch=manila#branches" class="branch-link">Select Manila <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FROM BEAN TO EXPERIENCE -->
<div class="band-dark">
    <div class="frieze"><div class="dentil"></div><div class="meander"></div></div>
    <section class="section story-section">
        <div class="floating-bean" style="top:8%;left:2%;animation-delay:0s"></div>
        <div class="floating-bean" style="top:72%;left:8%;animation-delay:1.4s"></div>
        <div class="floating-bean" style="top:20%;right:4%;animation-delay:2.6s"></div>
        <div class="story-grid">
            <div class="story-fig" data-reveal>
                <div class="pilaster"></div>
                <div class="story-media"><img src="https://images.unsplash.com/photo-1447933601403-0c6688de566e?q=80&w=900&auto=format&fit=crop" alt="Coffee beans" loading="lazy"></div>
                <div class="pilaster"></div>
            </div>
            <div class="story-copy" data-reveal>
                <div class="section-eyebrow">III &middot; Our Philosophy</div>
                <h2 class="hd-mask"><span>From Bean to <em>Experience.</em></span></h2>
                <p>It begins long before the espresso hits the cup — with beans sourced for character, roasted in small batches, and rested until they're ready to be pulled at exactly the right pressure and temperature.</p>
                <p>Every barista at SIPPERÉ Café is trained to read a shot the way a sommelier reads a glass of wine — adjusting grind, dose, and time until the cup in front of you tastes the way it was meant to.</p>
                <p>That discipline is the same across both branches, on every order, every single day. It's not a slogan. It's the standard we hold ourselves to.</p>
                <a href="includes/about-us.php" class="btn-ghost magnetic" style="margin-top:6px">Read Our Story</a>
            </div>
        </div>
    </section>
    <div class="frieze"><div class="meander"></div><div class="dentil" style="border-top:none;border-bottom:1px solid var(--gold-dim)"></div></div>
</div>

<!-- VALUE PROPS — temple colonnade -->
<section class="section">
    <div class="section-hd" data-reveal>
        <div class="section-eyebrow" style="justify-content:center">IV &middot; Why SIPPERÉ Café</div>
        <h2 class="hd-mask"><span>Built on the <em>Details</em></span></h2>
        <div class="orn"><span></span><svg viewBox="-17 -17 34 34"><g data-wreath="12" data-pairs="6"></g></svg><span></span></div>
    </div>
    <div class="colonnade">
        <div class="cn-pediment">
            <svg class="tri" viewBox="0 0 1000 96" preserveAspectRatio="none"><polygon points="0,96 500,6 1000,96" fill="#120f08" stroke="#c9a84c" stroke-width="1.5" vector-effect="non-scaling-stroke"/><polygon points="60,92 500,22 940,92" fill="none" stroke="#8a6f2e" stroke-width="1" stroke-dasharray="3 4" vector-effect="non-scaling-stroke"/></svg>
            <svg class="cn-wreath" viewBox="-40 -40 80 80"><g data-wreath="30" data-pairs="9"></g><path d="M0,-6 l5,6 l-5,6 l-5,-6 Z" fill="url(#tGold)"/></svg>
        </div>
        <div class="cn-entab"><div class="dentil"></div><div class="meander"></div></div>
        <div class="cn-body">
            <div class="pilaster"></div>
            <div class="props-grid" data-reveal="stagger">
                <div class="prop-card">
                    <div class="prop-num">I</div>
                    <div class="prop-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></div>
                    <h4>Made to Order</h4>
                    <p>Every drink is pulled fresh the moment you order — nothing sits waiting on a shelf.</p>
                </div>
                <div class="prop-card">
                    <div class="prop-num">II</div>
                    <div class="prop-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
                    <h4>Two Locations</h4>
                    <p>Laguna and Manila branches, each with a menu tuned to its own neighborhood.</p>
                </div>
                <div class="prop-card">
                    <div class="prop-num">III</div>
                    <div class="prop-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                    <h4>Consistent Quality</h4>
                    <p>The same recipe, the same standard, every single visit — no surprises, just good coffee.</p>
                </div>
            </div>
            <div class="pilaster"></div>
        </div>
        <div class="steps"><i></i><i></i><i></i></div>
    </div>
</section>

<!-- TESTIMONIALS (3-per-page) -->
<section class="section" style="padding-top:10px">
    <div class="section-hd" data-reveal>
        <div class="section-eyebrow" style="justify-content:center">V &middot; Word of Mouth</div>
        <h2 class="hd-mask"><span>What Guests Are <em>Saying</em></span></h2>
        <div class="orn"><span></span><svg viewBox="-17 -17 34 34"><g data-wreath="12" data-pairs="6"></g></svg><span></span></div>
    </div>
    <div data-reveal>
        <?php foreach ($testiPages as $pi => $page): ?>
        <div class="testi-page <?= $pi === 0 ? 'active' : '' ?>" data-page="<?= $pi ?>">
            <?php foreach ($page as $t):
                $rating = (int)$t['rating'];
                $initials = strtoupper(substr(trim($t['name']), 0, 1));
            ?>
            <div class="testi-card">
                <div class="testi-stars">
                    <?php for ($s=1;$s<=5;$s++): ?>
                    <svg viewBox="0 0 24 24" fill="<?= $s<=$rating?'currentColor':'none' ?>" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <?php endfor; ?>
                </div>
                <div class="testi-msg">&ldquo;<?= htmlspecialchars(mb_strimwidth($t['message'], 0, 140, '…')) ?>&rdquo;</div>
                <div class="testi-who">
                    <?php if (!empty($t['avatar_url'])): ?>
                        <img class="testi-avatar" src="<?= htmlspecialchars($t['avatar_url']) ?>" alt="" onerror="this.outerHTML='<div class=&quot;testi-avatar-fallback&quot;><?= $initials ?></div>'">
                    <?php else: ?>
                        <div class="testi-avatar-fallback"><?= $initials ?></div>
                    <?php endif; ?>
                    <div>
                        <div class="testi-name"><?= htmlspecialchars($t['name']) ?></div>
                        <div class="testi-branch"><?= ucfirst($t['branch']) ?> Branch</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

        <?php if (count($testiPages) > 1): ?>
        <div class="testi-pager" id="testiPager">
            <?php foreach ($testiPages as $pi => $page): ?>
            <button class="testi-dot <?= $pi === 0 ? 'active' : '' ?>" data-goto="<?= $pi ?>" aria-label="Page <?= $pi+1 ?>"></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA — temple front -->
<div class="cta-wrap">
    <div class="colonnade">
        <div class="cn-pediment" style="height:110px">
            <svg class="tri" viewBox="0 0 1000 110" preserveAspectRatio="none"><polygon points="0,110 500,6 1000,110" fill="#120f08" stroke="#c9a84c" stroke-width="1.5" vector-effect="non-scaling-stroke"/><polygon points="70,106 500,24 930,106" fill="none" stroke="#8a6f2e" stroke-width="1" stroke-dasharray="3 4" vector-effect="non-scaling-stroke"/></svg>
            <svg class="cn-wreath" viewBox="-40 -40 80 80" style="width:88px;height:88px"><g data-wreath="32" data-pairs="10"></g><path d="M0,-7 l6,7 l-6,7 l-6,-7 Z" fill="url(#tGold)"/></svg>
        </div>
        <div class="cn-entab"><div class="dentil"></div><div class="meander"></div></div>
        <div class="cn-body">
            <div class="pilaster"></div>
            <div class="cta-band" data-reveal>
                <div class="cta-steam"><span style="display:block;width:100%;height:100%;background:linear-gradient(180deg,transparent,rgba(240,234,216,.5),transparent);animation:rise 4s ease-in infinite"></span></div>
                <h2 class="hd-mask"><span>Your Perfect Cup <em>Awaits.</em></span></h2>
                <p>Browse the full menu across both branches and order in a few taps — pulled fresh the moment you check out.</p>
                <a href="menu.php" class="btn-primary magnetic">
                    Explore the Menu
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>
            <div class="pilaster"></div>
        </div>
        <div class="steps"><i></i><i></i><i></i></div>
    </div>
</div>

<footer>
    <div class="frieze"><div class="dentil"></div><div class="meander"></div></div>
    <div class="footer-main">
        <div class="footer-inner">
            <div class="footer-brand"><div class="dot"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#0b0b09" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/></svg></div>SIPPERÉ Café</div>
            <div class="footer-links">
                <a href="index.php">Home</a>
                <a href="menu.php">Menu</a>
                <a href="testimonial.php">Testimonials</a>
                <a href="includes/about-us.php">About Us</a>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; 2026 SIPPERÉ Café. All rights reserved.</span>
            <span>Developed by <a class="credit" href="https://www.instagram.com/_theprnx.gvara/" target="_blank" rel="noopener noreferrer">Prince Edward Guevara</a></span>
        </div>
    </div>
</footer>

<script>
/* Laurel wreath generator — builds two mirrored branches of leaf pairs into every [data-wreath] group */
(function(){
    var NS='http://www.w3.org/2000/svg';
    function el(n,a){var e=document.createElementNS(NS,n);for(var k in a)e.setAttribute(k,a[k]);return e}
    function wreath(g,R,pairs){
        var rx=R*.23, ry=R*.095;
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
                    g.appendChild(el('ellipse',{cx:cx.toFixed(2),cy:cy.toFixed(2),rx:rx.toFixed(2),ry:ry.toFixed(2),
                        transform:'rotate('+(tan+o*s*34).toFixed(1)+' '+cx.toFixed(2)+' '+cy.toFixed(2)+')',fill:'url(#tGold)'}));
                });
            }
            // berries at the base
            g.appendChild(el('circle',{cx:(s*R*Math.sin(.2)).toFixed(2),cy:(R*Math.cos(.2)+R*.1).toFixed(2),r:(R*.07).toFixed(2),fill:'#e6c777'}));
        });
    }
    document.querySelectorAll('[data-wreath]').forEach(function(g){
        wreath(g, parseFloat(g.getAttribute('data-wreath')), parseInt(g.getAttribute('data-pairs'),10)||7);
    });
})();

(function(){
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var fine = window.matchMedia('(hover:hover) and (pointer:fine)').matches;
    var loader = document.getElementById('loader');

    function finishLoad(){
        if (document.body.classList.contains('loaded')) return;
        document.body.classList.add('loaded');
        if (loader) { loader.classList.add('done'); setTimeout(function(){ if (loader.parentNode) loader.parentNode.removeChild(loader); }, 850); }
    }
    if (reduced) finishLoad();
    else { window.addEventListener('load', function(){ setTimeout(finishLoad, 1000); }); setTimeout(finishLoad, 3200); }

    /* Scroll reveals */
    var revealTargets = document.querySelectorAll('[data-reveal], .reveal-mask');
    if (reduced || !('IntersectionObserver' in window)) {
        revealTargets.forEach(function(el){ el.classList.add('in-view'); });
    } else {
        var io = new IntersectionObserver(function(entries){
            entries.forEach(function(entry){ if (entry.isIntersecting) { entry.target.classList.add('in-view'); io.unobserve(entry.target); } });
        }, { threshold:.15, rootMargin:'0px 0px -60px 0px' });
        revealTargets.forEach(function(el){ io.observe(el); });
    }

    /* Scroll progress + header blur */
    var progress = document.getElementById('scrollProgress'), header = document.getElementById('acnHeader');
    function onScroll(){
        var max = document.body.scrollHeight - window.innerHeight;
        progress.style.width = (max>0 ? (window.scrollY/max)*100 : 0) + '%';
        header.classList.toggle('scrolled', window.scrollY > 30);
    }
    window.addEventListener('scroll', onScroll, {passive:true}); onScroll();

    /* Animated counters */
    var counters = document.querySelectorAll('.hstat-num');
    var cio = new IntersectionObserver(function(entries){
        entries.forEach(function(entry){
            if (!entry.isIntersecting) return;
            var el = entry.target, target = parseInt(el.dataset.count,10)||0, t0 = performance.now(), dur=1500;
            function step(now){
                var p = Math.min(1,(now-t0)/dur), eased = 1-Math.pow(1-p,3);
                el.textContent = Math.floor(eased*target).toLocaleString() + (p<1 ? '' : (target>=100?'+':''));
                if (p<1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
            cio.unobserve(el);
        });
    }, {threshold:.6});
    counters.forEach(function(el){ cio.observe(el); });

    if (!reduced && fine) {
        /* Cursor: dot + ring + glow + particle trail */
        var dot=document.getElementById('cursorDot'), ring=document.getElementById('cursorRing'), glow=document.getElementById('cursorGlow');
        var mx=0,my=0,rx=0,ry=0,gx=0,gy=0;
        window.addEventListener('mousemove', function(e){
            mx=e.clientX; my=e.clientY;
            dot.style.transform='translate('+mx+'px,'+my+'px) translate(-50%,-50%)';
            spawnParticle(mx,my);
        });
        (function loop(){
            rx += (mx-rx)*.16; ry += (my-ry)*.16;
            gx += (mx-gx)*.08; gy += (my-gy)*.08;
            ring.style.transform='translate('+rx+'px,'+ry+'px) translate(-50%,-50%)';
            glow.style.transform='translate('+gx+'px,'+gy+'px) translate(-50%,-50%)';
            requestAnimationFrame(loop);
        })();
        document.querySelectorAll('a,button,.magnetic,[data-cursor-hover]').forEach(function(el){
            el.addEventListener('mouseenter', function(){ ring.classList.add('hover'); });
            el.addEventListener('mouseleave', function(){ ring.classList.remove('hover'); });
        });
        var lastP = 0;
        function spawnParticle(x,y){
            var now = Date.now(); if (now-lastP < 70) return; lastP = now;
            var size = 3 + Math.random()*3;
            var p = document.createElement('div'); p.className='coffee-particle';
            p.style.width=size+'px'; p.style.height=size+'px'; p.style.opacity='.5';
            p.style.transform='translate('+x+'px,'+y+'px) translate(-50%,-50%)';
            document.body.appendChild(p);
            var start=now;
            (function anim(){
                var t=(Date.now()-start)/700;
                if(t>=1){ p.remove(); return; }
                p.style.opacity=String(.5*(1-t));
                p.style.transform='translate('+x+'px,'+(y - t*26)+'px) translate(-50%,-50%) scale('+(1-t*.5)+')';
                requestAnimationFrame(anim);
            })();
        }

        /* Hero temple parallax */
        var cupStage = document.getElementById('heroCupStage');
        var heroSec = document.querySelector('.hero');
        if (cupStage && heroSec) {
            heroSec.addEventListener('mousemove', function(e){
                var r = heroSec.getBoundingClientRect();
                var px = (e.clientX-r.left)/r.width - .5, py=(e.clientY-r.top)/r.height - .5;
                cupStage.style.transform = 'translate('+(px*18).toFixed(1)+'px,'+(py*14).toFixed(1)+'px) rotateY('+(px*8).toFixed(1)+'deg)';
            });
            heroSec.addEventListener('mouseleave', function(){ cupStage.style.transform=''; });
        }

        /* Card tilt: signature + branch cards */
        document.querySelectorAll('.sig-card, .branch-card').forEach(function(card){
            card.addEventListener('mousemove', function(e){
                var r = card.getBoundingClientRect();
                var px = (e.clientX-r.left)/r.width - .5, py=(e.clientY-r.top)/r.height - .5;
                card.style.transform = 'perspective(1000px) rotateY('+(px*7).toFixed(1)+'deg) rotateX('+(-py*7).toFixed(1)+'deg) translateY(-4px)';
            });
            card.addEventListener('mouseleave', function(){ card.style.transform=''; });
        });

        /* Magnetic buttons */
        document.querySelectorAll('.magnetic').forEach(function(btn){
            btn.addEventListener('mousemove', function(e){
                var r = btn.getBoundingClientRect();
                var mx = e.clientX-r.left-r.width/2, my = e.clientY-r.top-r.height/2;
                btn.style.transform = 'translate('+(mx*0.25).toFixed(1)+'px,'+(my*0.32).toFixed(1)+'px)';
            });
            btn.addEventListener('mouseleave', function(){ btn.style.transform=''; });
        });
    } else {
        document.getElementById('cursorDot')?.remove();
        document.getElementById('cursorRing')?.remove();
        document.getElementById('cursorGlow')?.remove();
    }

    /* Testimonial pagination */
    var pages = Array.from(document.querySelectorAll('.testi-page'));
    var dots = Array.from(document.querySelectorAll('.testi-dot'));
    function gotoPage(i){
        pages.forEach(function(p,pi){ p.classList.toggle('active', pi===i); });
        dots.forEach(function(d,di){ d.classList.toggle('active', di===i); });
    }
    dots.forEach(function(d){ d.addEventListener('click', function(){ gotoPage(parseInt(d.dataset.goto,10)); }); });
})();

/* Add to cart — same JSON contract as app.js / cart_handler.php */
function addSignature(id, btn){
    if (btn.disabled) return;
    var orig = btn.textContent;
    btn.disabled = true; btn.textContent = 'Adding…';
    fetch('cart_handler.php?add=' + encodeURIComponent(id), { credentials:'same-origin' })
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (data.success) {
                btn.textContent = 'Added ✓';
                btn.classList.add('added');
                var badge = document.querySelector('[data-cart-badge]');
                if (badge) badge.textContent = data.count;
                setTimeout(function(){ btn.textContent = orig; btn.classList.remove('added'); btn.disabled = false; }, 1500);
            } else {
                btn.textContent = orig; btn.disabled = false;
                alert(data.message || 'Could not add to cart.');
            }
        })
        .catch(function(){ btn.textContent = orig; btn.disabled = false; alert('Network error — please try again.'); });
}
</script>

</body>
</html>