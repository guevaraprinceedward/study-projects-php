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

// ── BRANCH (new — needed for the Branch Selection section + signature picks) ──
$branch = $_SESSION['branch'] ?? 'laguna';
if (isset($_GET['branch']) && in_array($_GET['branch'], ['laguna', 'manila'])) {
    $branch = $_GET['branch'];
    $_SESSION['branch'] = $branch;
}
$branchEsc = $conn->real_escape_string($branch);

// ── SIGNATURE: real best-sellers (by revenue) for the current branch ──────
// Falls back to the 3 newest in-stock items if there's no order history yet,
// so the section is never empty on a fresh install.
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
// Chunk into pages of 3 for the paginated testimonial grid.
$testiPages = array_chunk($teaser, 3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AyosCoffeeNegosyo — The Pursuit of Exceptional Coffee</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<noscript><style>.loader{display:none!important}.reveal-el,.cup-stage,[data-reveal],[data-reveal]>*,.bean{opacity:1!important;transform:none!important}</style></noscript>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    /* ── SAME theme tokens as the live site — untouched ── */
    --bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;
    --gold:#c9a84c;--gold-dim:#8a6f2e;--gold-bright:#e6c777;--gold-pale:rgba(201,168,76,.08);
    --green:#4a7a3a;--green-lt:#6aaa52;--cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8;
    --mono:'IBM Plex Mono',ui-monospace,monospace;
    --ease:cubic-bezier(.2,.8,.2,1);
}
html{scroll-behavior:smooth}
@media (prefers-reduced-motion: reduce){
    *{animation-duration:.01ms !important;animation-iteration-count:1 !important;transition-duration:.01ms !important;scroll-behavior:auto !important}
    .loader{display:none !important}
    .reveal-el,.cup-stage,[data-reveal],[data-reveal] > *,.bean,#cursorDot,#cursorRing,.coffee-particle{opacity:1 !important;transform:none !important;display:revert !important}
    #cursorDot,#cursorRing,.coffee-particle{display:none !important}
}
/* NOTE: perspective used to live here on body. Any `perspective`/`transform`/`filter`
   on body creates a new containing block for position:fixed descendants — which broke
   the loader's centering AND made the custom cursor drift from the real cursor while
   scrolling. Moved to .cup-stage below, which is the only thing that actually needs it. */
body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden;cursor:none}
@media(hover:none),(pointer:coarse){body{cursor:auto}}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 8% 0%,rgba(201,168,76,.07) 0%,transparent 55%),radial-gradient(ellipse 55% 70% at 92% 100%,rgba(74,122,58,.08) 0%,transparent 55%);pointer-events:none;z-index:0}
a{color:inherit}
:focus-visible{outline:2px solid var(--gold);outline-offset:3px}

.grain{position:fixed;inset:0;z-index:4;pointer-events:none;opacity:.035;mix-blend-mode:overlay;background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='140' height='140'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/></filter><rect width='100%25' height='100%25' filter='url(%23n)'/></svg>")}

/* ── Cursor-following warm spotlight (premium, subtle) ── */
#cursorGlow{position:fixed;top:0;left:0;width:520px;height:520px;border-radius:50%;pointer-events:none;z-index:2;
    background:radial-gradient(circle,rgba(201,168,76,.06),transparent 70%);transform:translate(-50%,-50%);will-change:transform}
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
.loader-glow{position:absolute;inset:0;background:radial-gradient(ellipse 55% 55% at 50% 45%,rgba(201,168,76,.1),transparent 65%)}
.loader-stage{position:relative}
.loader-word{position:absolute;bottom:-58px;left:0;right:0;text-align:center;font-family:'Cormorant Garamond',serif;font-size:13px;letter-spacing:.34em;text-transform:uppercase;color:var(--muted);opacity:0;animation:loaderWord .8s ease .6s forwards}
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
/* padding-bottom + matching negative margin gives descenders/italic swashes room
   inside the overflow:hidden mask without shifting layout below it */
.reveal-mask{overflow:hidden;padding-bottom:.14em;margin-bottom:-.14em}
.reveal-mask span{display:inline-block;transform:translateY(112%);transition:transform 1s var(--ease)}
body.loaded .reveal-mask span{transform:translateY(0)}

/* ── HEADER ── */
header{position:sticky;top:0;z-index:100;background:rgba(11,11,9,.72);backdrop-filter:blur(14px);border-bottom:1px solid transparent;transition:background .4s var(--ease),border-color .4s var(--ease),backdrop-filter .4s var(--ease)}
header.scrolled{background:rgba(11,11,9,.9);backdrop-filter:blur(20px);border-bottom-color:var(--border)}
.header-inner{max-width:1220px;margin:0 auto;padding:0 32px;height:70px;display:flex;align-items:center;justify-content:space-between}
.brand{display:flex;align-items:center;gap:12px;text-decoration:none}
.brand-icon{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:linear-gradient(155deg,var(--gold-bright),var(--gold-dim));box-shadow:0 4px 16px -4px rgba(201,168,76,.6);transition:transform .4s var(--ease)}
.brand:hover .brand-icon{transform:rotate(-14deg)}
.brand-name{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream);letter-spacing:.04em}
.brand-name span{color:var(--gold)}
nav{display:flex;align-items:center;gap:4px}
nav a{position:relative;font-size:12.5px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);text-decoration:none;padding:8px 14px;border-radius:3px;transition:color .2s}
nav a:not(.nav-cta)::after{content:'';position:absolute;left:14px;right:14px;bottom:5px;height:1px;background:var(--gold);transform:scaleX(0);transform-origin:left;transition:transform .35s var(--ease)}
nav a:not(.nav-cta):hover{color:var(--cream)}
nav a:not(.nav-cta):hover::after,nav a.active::after{transform:scaleX(1)}
nav a.active{color:var(--gold)}
.nav-cta{margin-left:10px;padding:10px 22px !important;background:var(--green) !important;color:#fff !important;border-radius:3px;transition:background .25s !important,transform .25s !important;font-weight:600 !important}
.nav-cta:hover{background:var(--green-lt) !important;transform:translateY(-2px)}
@media(max-width:820px){nav a:not(.nav-cta){display:none}}

/* ── HERO ── */
.hero{position:relative;z-index:1;max-width:1220px;margin:0 auto;padding:78px 32px 64px;display:grid;grid-template-columns:1.05fr .95fr;gap:44px;align-items:center;transition:opacity .3s linear}
.hero-eyebrow{display:inline-flex;align-items:center;gap:10px;font-family:var(--mono);font-size:10.5px;letter-spacing:.24em;text-transform:uppercase;color:var(--gold);margin-bottom:22px}
.hero-eyebrow::before{content:'';width:26px;height:1px;background:var(--gold-dim)}
/* line-height raised from 1.06 to 1.18 — 1.06 was tight enough to clip the italic
   Cormorant Garamond's descenders/swash tails at this font-size */
.hero h1{font-family:'Cormorant Garamond',serif;font-size:clamp(40px,5.4vw,64px);font-weight:700;line-height:1.18;color:var(--cream);letter-spacing:-.01em;margin-bottom:22px}
.hero h1 em{font-style:italic;color:var(--gold-bright)}
.hero p.lede{font-size:15.5px;color:var(--muted);line-height:1.8;font-weight:300;max-width:470px;margin-bottom:36px}
.hero-ctas{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:8px}
.btn-primary{position:relative;overflow:hidden;display:inline-flex;align-items:center;gap:10px;padding:15px 30px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#fff;text-decoration:none;transition:background .2s,transform .3s var(--ease);box-shadow:0 14px 32px -10px rgba(74,122,58,.55)}
.btn-primary::after{content:'';position:absolute;top:0;left:-60%;width:35%;height:100%;background:linear-gradient(115deg,transparent,rgba(255,255,255,.28),transparent);transform:skewX(-18deg);transition:left .65s ease}
.btn-primary:hover{background:var(--green-lt);transform:translateY(-3px)}
.btn-primary:hover::after{left:130%}
.btn-ghost{display:inline-flex;align-items:center;gap:10px;padding:15px 28px;border:1px solid var(--border);border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);text-decoration:none;transition:all .3s var(--ease)}
.btn-ghost:hover{border-color:var(--gold-dim);color:var(--gold);transform:translateY(-3px)}
.hero-stats{display:flex;gap:34px;margin-top:46px;padding-top:26px;border-top:1px solid var(--border)}
.hstat-num{font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:var(--gold-bright)}
.hstat-label{font-size:10px;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-top:3px;max-width:12ch}

/* ── 3D CUP RIG ── */
/* perspective now lives here — the only element that actually needs a 3D viewing
   cone — instead of on body, where it hijacked position:fixed for the whole page */
.cup-stage{position:relative;height:440px;display:flex;align-items:center;justify-content:center;transform-style:preserve-3d;perspective:1400px;opacity:0;transform:scale(.85) rotateY(-8deg);transition:opacity 1.1s var(--ease) .3s, transform 1.1s var(--ease) .3s}
body.loaded .cup-stage{opacity:1;transform:scale(1) rotateY(0)}
.hero-badge{position:absolute;top:2px;right:0;width:112px;height:112px;z-index:3;opacity:0;transform:scale(.7) rotate(-14deg);transition:opacity .9s var(--ease) .95s, transform .9s var(--ease) .95s}
body.loaded .hero-badge{opacity:1;transform:scale(1) rotate(0)}
.badge-ring-svg{width:100%;height:100%;display:block;animation:spin 17s linear infinite;animation-play-state:paused}
body.loaded .badge-ring-svg{animation-play-state:running}
.badge-ring-svg text{fill:var(--gold)}
.hero-badge-center{position:absolute;inset:0;margin:auto;width:38px;height:38px;border-radius:50%;background:var(--bg);border:1px solid var(--gold-dim);display:flex;align-items:center;justify-content:center;box-shadow:0 8px 20px -8px rgba(0,0,0,.7)}
@media(max-width:960px){.hero-badge{display:none}}
.cup-ring{position:absolute;width:340px;height:340px;border:1px solid rgba(201,168,76,.14);border-radius:50%;animation:spin 26s linear infinite;animation-play-state:paused}
.cup-ring.r2{width:400px;height:400px;border-color:rgba(106,170,82,.12);animation-duration:34s;animation-direction:reverse}
body.loaded .cup-ring{animation-play-state:running}
@keyframes spin{to{transform:rotate(360deg)}}

/* Beans — orbit trick: outer wrapper rotates in 3D, bean sits at translateZ radius */
.bean-field{position:absolute;inset:-70px;transform-style:preserve-3d;pointer-events:none}
.bean-orbit{position:absolute;top:50%;left:50%;transform-style:preserve-3d;animation:orbitSpin linear infinite}
.bean-orbit .bean-inner{position:absolute;width:15px;height:20px;margin:-10px 0 0 -7px;border-radius:52% 48% 50% 50%/62% 62% 38% 38%;
    background:linear-gradient(140deg,var(--gold-bright),var(--gold-dim) 75%);box-shadow:0 8px 16px rgba(0,0,0,.5)}
.bean-orbit .bean-inner::after{content:'';position:absolute;top:2px;left:6px;width:2px;height:16px;border-radius:2px;background:rgba(11,11,9,.6)}
@keyframes orbitSpin{from{transform:rotateY(0deg)}to{transform:rotateY(360deg)}}
.bo1{animation-duration:9s}.bo2{animation-duration:12s;animation-direction:reverse}
.bo3{animation-duration:15s}.bo4{animation-duration:18s;animation-direction:reverse}
.bo5{animation-duration:21s}

.bean{position:absolute;width:14px;height:20px;background:linear-gradient(135deg,var(--gold),var(--gold-dim));border-radius:50% 50% 50% 50%/60% 60% 40% 40%;box-shadow:0 6px 14px rgba(0,0,0,.5);opacity:0;transform:translateY(-18px);transition:opacity .6s ease, transform .6s cubic-bezier(.3,1.3,.4,1)}
body.loaded .bean{opacity:1;transform:translateY(0)}
.bean::after{content:'';position:absolute;top:2px;left:6px;width:2px;height:16px;background:rgba(11,11,9,.55);border-radius:2px}
.bean.b1{top:6%;left:48%;transition-delay:.55s}
.bean.b2{top:46%;left:0;transition-delay:.68s}
.bean.b3{top:80%;left:52%;transition-delay:.81s}
.bean.b4{top:40%;left:88%;transition-delay:.94s}

/* Espresso cup — now an SVG illustration (crema swirl, ceramic shading, sugar
   cubes, garnish beans) instead of stacked CSS boxes. Same tilt animation, just
   applied to the SVG's wrapper. Height grew 190→200px to fit the saucer/garnish. */
.cup3d{position:relative;width:170px;height:200px;transform-style:preserve-3d;animation:tilt 7s ease-in-out infinite;animation-play-state:paused}
body.loaded .cup3d{animation-play-state:running}
@keyframes tilt{0%,100%{transform:rotateY(-8deg) rotateX(4deg)}50%{transform:rotateY(8deg) rotateX(-2deg)}}
.cup-svg{display:block;overflow:visible;filter:drop-shadow(0 30px 38px rgba(0,0,0,.55))}
.steam{position:absolute;top:-36px;left:69px;width:7px;height:60px;opacity:0;filter:blur(1.5px)}
.steam span{position:absolute;bottom:0;left:0;width:100%;height:100%;background:linear-gradient(180deg,transparent,rgba(240,234,216,.5),transparent);border-radius:40%;animation:rise 3.2s ease-in infinite;animation-play-state:paused}
body.loaded .steam span{animation-play-state:running}
.steam.s2{left:85px}.steam.s2 span{animation-delay:.8s}
.steam.s3{left:101px}.steam.s3 span{animation-delay:1.6s}
@keyframes rise{0%{transform:translateY(0) scaleX(1);opacity:0}20%{opacity:.7}100%{transform:translateY(-72px) scaleX(1.9);opacity:0}}
@media(max-width:960px){.hero{grid-template-columns:1fr;text-align:center}.hero p.lede{margin-inline:auto}.hero-stats{justify-content:center}.cup-stage{height:320px}.hero-ctas{justify-content:center}}

/* ── SECTIONS ── */
.section{position:relative;z-index:1;max-width:1220px;margin:0 auto;padding:90px 32px}
.section-hd{text-align:center;max-width:600px;margin:0 auto 48px}
.section-eyebrow{display:inline-flex;align-items:center;gap:10px;font-family:var(--mono);font-size:10.5px;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:14px}
.section-eyebrow::before,.section-eyebrow::after{content:'';width:22px;height:1px;background:var(--gold-dim)}
.section-hd h2{font-family:'Cormorant Garamond',serif;font-size:clamp(28px,4vw,42px);font-weight:700;color:var(--cream)}
.section-hd h2 em{font-style:italic;color:var(--gold-bright)}
.section-hd p{font-size:13.5px;color:var(--muted);margin-top:12px;line-height:1.75}

/* ── SIGNATURE COFFEE (premium floating cards) ── */
.sig-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:26px}
.sig-card{position:relative;background:var(--card);border:1px solid var(--border);border-radius:10px;overflow:hidden;transform-style:preserve-3d;will-change:transform;
    transition:transform .12s ease-out,border-color .3s,box-shadow .4s}
.sig-card:hover{border-color:var(--gold-dim);box-shadow:0 40px 80px -26px rgba(0,0,0,.75)}
.sig-media{position:relative;aspect-ratio:4/3;overflow:hidden;background:linear-gradient(160deg,#241d10,#171410)}
.sig-media img{width:100%;height:100%;object-fit:cover;transition:transform .6s var(--ease)}
.sig-card:hover .sig-media img{transform:scale(1.08)}
.sig-media::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 55%,rgba(11,11,9,.65))}
.sig-rank{position:absolute;top:14px;left:14px;z-index:2;font-family:var(--mono);font-size:.62rem;letter-spacing:.14em;text-transform:uppercase;background:rgba(11,11,9,.72);border:1px solid var(--gold-dim);color:var(--gold-bright);padding:5px 11px;border-radius:999px;backdrop-filter:blur(4px)}
.sig-cat{position:absolute;bottom:14px;left:14px;z-index:2;font-size:.68rem;letter-spacing:.08em;text-transform:uppercase;color:var(--gold-bright)}
.sig-body{padding:22px 22px 24px;transform:translateZ(24px)}
.sig-name{font-family:'Cormorant Garamond',serif;font-size:1.35rem;font-weight:700;color:var(--cream);margin-bottom:6px}
.sig-desc{font-size:.85rem;color:var(--muted);line-height:1.65;margin-bottom:18px;min-height:2.6em}
.sig-foot{display:flex;align-items:center;justify-content:space-between;padding-top:16px;border-top:1px solid var(--border)}
.sig-price{font-family:var(--mono);font-weight:600;color:var(--gold-bright);font-size:1.05rem}
.sig-price small{font-size:.6rem;color:var(--muted);font-weight:400}
.sig-add{font-family:'Jost',sans-serif;font-weight:600;font-size:.76rem;letter-spacing:.04em;padding:10px 18px;border-radius:999px;border:none;cursor:pointer;
    background:var(--green);color:#fff;transition:background .2s ease,transform .25s var(--ease)}
.sig-add:hover{background:var(--green-lt);transform:translateY(-2px)}
.sig-add.added{background:var(--gold);color:var(--bg)}
.sig-add:disabled{background:#3a3025;color:#7a6c58;cursor:not-allowed}
.sig-empty{grid-column:1/-1;text-align:center;padding:50px;color:var(--muted);border:1px dashed var(--border);border-radius:10px}
@media(max-width:960px){.sig-grid{grid-template-columns:1fr}}

/* ── BRANCH SELECTION (premium) ── */
.branch-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}
.branch-card{position:relative;background:var(--card);border:1px solid var(--border);border-radius:10px;padding:0;overflow:hidden;transform-style:preserve-3d;will-change:transform;
    transition:transform .35s var(--ease),border-color .3s,box-shadow .4s}
.branch-card:hover{border-color:var(--gold-dim);box-shadow:0 34px 70px -22px rgba(0,0,0,.7)}
.branch-media{position:relative;aspect-ratio:16/9;overflow:hidden}
.branch-media img{width:100%;height:100%;object-fit:cover;transition:transform .7s var(--ease)}
.branch-card:hover .branch-media img{transform:scale(1.06)}
.branch-media::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 40%,rgba(11,11,9,.85))}
.branch-tag{position:absolute;bottom:16px;left:20px;z-index:2;display:inline-flex;align-items:center;gap:6px;font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:var(--green-lt)}
.branch-tag .dot{width:6px;height:6px;border-radius:50%;background:var(--green-lt)}
.branch-card.active-branch{border-color:var(--gold-bright);box-shadow:0 0 0 1px var(--gold-bright)}
.branch-card.manila .branch-tag{color:var(--gold-bright)}
.branch-card.manila .branch-tag .dot{background:var(--gold-bright)}
.branch-body{padding:26px 28px 28px}
.branch-body h3{font-family:'Cormorant Garamond',serif;font-size:30px;font-weight:700;color:var(--cream);margin-bottom:8px}
.branch-body p{font-size:13.5px;color:var(--muted);line-height:1.75;margin-bottom:18px}
.branch-meta{display:flex;justify-content:space-between;align-items:center}
.branch-count{font-family:var(--mono);font-size:.72rem;color:var(--gold-bright);letter-spacing:.06em}
.branch-link{display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--gold);text-decoration:none}
.branch-link svg{transition:transform .25s var(--ease)}
.branch-card:hover .branch-link svg{transform:translateX(5px)}

/* ── STORY / BEAN-TO-EXPERIENCE ── */
.story-section{position:relative;overflow:hidden}
.story-grid{display:grid;grid-template-columns:.9fr 1.1fr;gap:60px;align-items:center}
.story-media{position:relative;aspect-ratio:4/5;border-radius:14px;overflow:hidden;border:1px solid var(--border)}
.story-media img{width:100%;height:100%;object-fit:cover}
.story-media::before{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 50%,rgba(11,11,9,.6))}
.story-copy h2{font-family:'Cormorant Garamond',serif;font-size:clamp(2rem,4vw,3rem);font-weight:700;line-height:1.14;margin-bottom:22px;color:var(--cream)}
.story-copy h2 em{font-style:italic;color:var(--gold-bright)}
.story-copy p{color:var(--muted);line-height:1.9;font-weight:300;margin-bottom:18px;max-width:52ch;font-size:14.5px}
.floating-bean{position:absolute;width:13px;height:18px;border-radius:52% 48% 50% 50%/62% 62% 38% 38%;
    background:linear-gradient(140deg,var(--gold-bright),var(--gold-dim));opacity:.3;animation:beanFloat 10s ease-in-out infinite;pointer-events:none}
@keyframes beanFloat{0%,100%{transform:translateY(0) rotate(0deg)}50%{transform:translateY(-24px) rotate(28deg)}}
@media(max-width:900px){.story-grid{grid-template-columns:1fr}}

/* ── VALUE PROPS ── */
.props-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.prop-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:30px 26px;text-align:center;will-change:transform;transition:transform .35s var(--ease),border-color .3s,box-shadow .4s}
.prop-card:hover{border-color:var(--gold-dim);box-shadow:0 24px 50px -20px rgba(0,0,0,.55)}
.prop-icon{width:46px;height:46px;margin:0 auto 16px;border:1px solid var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--gold);transition:transform .35s var(--ease),border-color .3s}
.prop-card:hover .prop-icon{transform:rotate(-10deg) scale(1.08);border-color:var(--gold)}
.prop-card h4{font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:600;color:var(--cream);margin-bottom:8px}
.prop-card p{font-size:12.5px;color:var(--muted);line-height:1.65}
@media(max-width:900px){.props-grid{grid-template-columns:1fr}}

/* ── TESTIMONIALS (3-per-page, paginated) ── */
.testi-page{display:none;grid-template-columns:repeat(3,1fr);gap:20px}
.testi-page.active{display:grid}
.testi-card{background:var(--card);border:1px solid var(--border);border-radius:8px;padding:26px;will-change:transform;transition:transform .35s var(--ease),border-color .3s,box-shadow .4s}
.testi-card:hover{border-color:var(--gold-dim);box-shadow:0 24px 50px -20px rgba(0,0,0,.55)}
.testi-stars{display:flex;gap:2px;margin-bottom:12px;color:var(--gold-bright)}
.testi-stars svg{width:12px;height:12px}
.testi-msg{font-family:'Cormorant Garamond',serif;font-style:italic;font-size:16.5px;color:var(--cream);line-height:1.65;margin-bottom:18px;min-height:5.6em}
.testi-who{display:flex;align-items:center;gap:10px}
.testi-avatar{width:34px;height:34px;border-radius:50%;object-fit:cover;border:1px solid var(--gold-dim);background:var(--surface)}
.testi-avatar-fallback{width:34px;height:34px;border-radius:50%;border:1px solid var(--gold-dim);background:var(--surface);display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-size:12.5px;font-weight:700;color:var(--gold)}
.testi-name{font-size:12.5px;font-weight:500;color:var(--cream)}
.testi-branch{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em}
.testi-pager{display:flex;justify-content:center;gap:10px;margin-top:38px}
.testi-dot{width:8px;height:8px;border-radius:50%;background:var(--border);border:none;cursor:pointer;transition:background .25s ease,transform .25s ease}
.testi-dot.active{background:var(--gold-bright);transform:scale(1.3)}
@media(max-width:900px){.testi-page{grid-template-columns:1fr}}

/* ── CTA BAND ── */
.cta-band{position:relative;z-index:1;max-width:1220px;margin:0 auto 90px;padding:64px 48px;background:var(--card);border:1px solid var(--border);border-radius:10px;text-align:center;overflow:hidden}
.cta-band::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 70% 60% at 50% 0%,rgba(201,168,76,.07) 0%,transparent 70%);animation:glowShift 9s ease-in-out infinite alternate}
@keyframes glowShift{0%{opacity:.65;transform:translateY(0)}100%{opacity:1;transform:translateY(12px)}}
.cta-steam{position:absolute;top:16%;left:50%;transform:translateX(-50%);width:8px;height:100px;opacity:.35;filter:blur(1.5px)}
.cta-band h2{position:relative;font-family:'Cormorant Garamond',serif;font-size:clamp(28px,4.5vw,42px);font-weight:700;color:var(--cream);margin-bottom:14px}
.cta-band h2 em{font-style:italic;color:var(--gold-bright)}
.cta-band p{position:relative;font-size:13.5px;color:var(--muted);margin-bottom:28px;max-width:46ch;margin-inline:auto}

/* ── FOOTER (minimal, elegant) ── */
footer{position:relative;z-index:1;border-top:1px solid var(--border);padding:44px 32px 28px}
.footer-inner{max-width:1220px;margin:0 auto;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:18px}
.footer-brand{display:flex;align-items:center;gap:10px;font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:600;color:var(--cream)}
.footer-brand .dot{width:26px;height:26px;border-radius:50%;background:linear-gradient(155deg,var(--gold-bright),var(--gold-dim));display:flex;align-items:center;justify-content:center}
.footer-links{display:flex;gap:22px;flex-wrap:wrap}
.footer-links a{font-size:12px;color:var(--muted);letter-spacing:.04em;transition:color .2s ease}
.footer-links a:hover{color:var(--gold)}
.footer-bottom{max-width:1220px;margin:22px auto 0;padding-top:18px;border-top:1px solid var(--border);display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;font-size:11.5px;color:var(--muted)}
footer a.credit{color:var(--gold);text-decoration:none}
footer a.credit:hover{color:var(--cream);text-decoration:underline}

@media(max-width:900px){.branch-grid,.props-grid{grid-template-columns:1fr}}
</style>
</head>
<body>

<div class="loader" id="loader" aria-hidden="true">
    <div class="loader-glow"></div>
    <div class="loader-stage">
        <div class="bean-field">
            <div class="bean-orbit bo1"><div class="bean-inner" style="transform:translateZ(150px)"></div></div>
            <div class="bean-orbit bo2"><div class="bean-inner" style="transform:translateZ(172px) rotateX(18deg)"></div></div>
            <div class="bean-orbit bo3"><div class="bean-inner" style="transform:translateZ(136px) rotateX(-14deg)"></div></div>
            <div class="bean-orbit bo4"><div class="bean-inner" style="transform:translateZ(188px)"></div></div>
        </div>
        <div class="cup3d" style="animation-play-state:running">
            <svg class="cup-svg" viewBox="0 0 170 200" width="170" height="200" aria-hidden="true">
                <defs>
                    <linearGradient id="cupBodyGrad2" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#2e2513"/><stop offset="55%" stop-color="#201a10"/><stop offset="100%" stop-color="#14110c"/>
                    </linearGradient>
                    <radialGradient id="cremaGrad2" cx="32%" cy="28%" r="80%">
                        <stop offset="0%" stop-color="#e6c777"/><stop offset="42%" stop-color="#a97a3e"/><stop offset="100%" stop-color="#3d2612"/>
                    </radialGradient>
                    <linearGradient id="saucerGrad2" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#241d10"/><stop offset="100%" stop-color="#100e09"/>
                    </linearGradient>
                    <linearGradient id="sugarGrad2" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#f5f0e0"/><stop offset="100%" stop-color="#c9a84c"/>
                    </linearGradient>
                    <linearGradient id="beanGrad2" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#e6c777"/><stop offset="100%" stop-color="#8a6f2e"/>
                    </linearGradient>
                    <filter id="softShadow2" x="-60%" y="-60%" width="220%" height="220%"><feGaussianBlur stdDeviation="5"/></filter>
                </defs>
                <ellipse cx="85" cy="186" rx="88" ry="10" fill="#000" opacity=".45" filter="url(#softShadow2)"/>
                <ellipse cx="85" cy="182" rx="92" ry="11" fill="url(#saucerGrad2)" stroke="#2c2c24" stroke-width="1"/>
                <ellipse cx="85" cy="180.5" rx="80" ry="8.5" fill="none" stroke="#8a6f2e" stroke-width=".6" opacity=".5"/>
                <g opacity=".95">
                    <rect x="8" y="163" width="20" height="20" rx="2.5" fill="url(#sugarGrad2)" transform="rotate(-10 18 173)"/>
                    <rect x="22" y="148" width="18" height="18" rx="2.5" fill="url(#sugarGrad2)" transform="rotate(8 31 157)"/>
                    <circle cx="15" cy="168" r="1" fill="#fff" opacity=".8"/>
                    <circle cx="34" cy="153" r="1" fill="#fff" opacity=".8"/>
                </g>
                <g>
                    <g transform="translate(148,168) rotate(18)"><ellipse rx="7" ry="10" fill="url(#beanGrad2)"/><path d="M0,-9 Q3,0 0,9" stroke="#0b0b09" stroke-width="1.3" fill="none" opacity=".65"/></g>
                    <g transform="translate(133,178) rotate(-24)"><ellipse rx="6" ry="8.5" fill="url(#beanGrad2)"/><path d="M0,-7.5 Q2.5,0 0,7.5" stroke="#0b0b09" stroke-width="1.1" fill="none" opacity=".65"/></g>
                    <g transform="translate(158,180) rotate(6)"><ellipse rx="5.5" ry="7.5" fill="url(#beanGrad2)"/><path d="M0,-6.5 Q2,0 0,6.5" stroke="#0b0b09" stroke-width="1" fill="none" opacity=".65"/></g>
                </g>
                <path d="M124,52 C152,52 152,98 124,98" fill="none" stroke="#8a6f2e" stroke-width="8" stroke-linecap="round"/>
                <path d="M124,52 C152,52 152,98 124,98" fill="none" stroke="#c9a84c" stroke-width="2" stroke-linecap="round" opacity=".5"/>
                <path d="M34,20 L136,20 L124,128 Q122,141 85,141 Q48,141 46,128 Z" fill="url(#cupBodyGrad2)" stroke="#8a6f2e" stroke-width="1"/>
                <path d="M42,26 L44,118" stroke="#e6c777" stroke-width="1.6" stroke-linecap="round" opacity=".45"/>
                <path d="M120,30 Q126,60 120,95" stroke="#fff" stroke-width="6" stroke-linecap="round" opacity=".05"/>
                <ellipse cx="85" cy="22" rx="49" ry="8" fill="url(#cremaGrad2)"/>
                <path d="M60,20 Q85,26 110,20" stroke="#e6c777" stroke-width="1" fill="none" opacity=".4"/>
                <path d="M66,23 Q85,18 104,23" stroke="#3d2612" stroke-width="1" fill="none" opacity=".35"/>
                <ellipse cx="85" cy="20" rx="51" ry="9" fill="none" stroke="#e6c777" stroke-width="1.4"/>
            </svg>
            <div class="steam s1" style="opacity:1"><span></span></div>
            <div class="steam s2" style="opacity:1"><span></span></div>
            <div class="steam s3" style="opacity:1"><span></span></div>
        </div>
        <div class="loader-word">AyosCoffeeNegosyo</div>
        <div class="loader-bar"><span></span></div>
    </div>
</div>

<div id="cursorGlow"></div>
<div id="cursorRing"></div>
<div id="cursorDot"></div>
<div class="grain"></div>
<div class="scroll-progress" id="scrollProgress"></div>

<header id="acnHeader">
    <div class="header-inner">
        <a href="index.php" class="brand">
            <div class="brand-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0b0b09" stroke-width="2"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg></div>
            <span class="brand-name">My <span>AyosCoffeeNegosyo</span></span>
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
        <div class="hero-eyebrow reveal-el d1">Est. 2026 &middot; Laguna &amp; Manila</div>
        <h1 class="reveal-mask"><span>The Pursuit of</span></h1>
        <h1 class="reveal-mask" style="margin-top:-14px;margin-bottom:22px"><span><em>Exceptional Coffee.</em></span></h1>
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
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.5"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
            </div>
        </div>
        <div class="cup-ring"></div>
        <div class="cup-ring r2"></div>
        <div class="bean-field">
            <div class="bean-orbit bo1"><div class="bean-inner" style="transform:translateZ(150px)"></div></div>
            <div class="bean-orbit bo2"><div class="bean-inner" style="transform:translateZ(175px) rotateX(18deg)"></div></div>
            <div class="bean-orbit bo3"><div class="bean-inner" style="transform:translateZ(135px) rotateX(-14deg)"></div></div>
            <div class="bean-orbit bo4"><div class="bean-inner" style="transform:translateZ(190px)"></div></div>
            <div class="bean-orbit bo5"><div class="bean-inner" style="transform:translateZ(160px) rotateX(10deg)"></div></div>
        </div>
        <div class="bean b1"></div><div class="bean b2"></div><div class="bean b3"></div><div class="bean b4"></div>
        <div class="cup3d">
            <svg class="cup-svg" viewBox="0 0 170 200" width="170" height="200" aria-hidden="true">
                <defs>
                    <linearGradient id="cupBodyGrad" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#2e2513"/><stop offset="55%" stop-color="#201a10"/><stop offset="100%" stop-color="#14110c"/>
                    </linearGradient>
                    <radialGradient id="cremaGrad" cx="32%" cy="28%" r="80%">
                        <stop offset="0%" stop-color="#e6c777"/><stop offset="42%" stop-color="#a97a3e"/><stop offset="100%" stop-color="#3d2612"/>
                    </radialGradient>
                    <linearGradient id="saucerGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#241d10"/><stop offset="100%" stop-color="#100e09"/>
                    </linearGradient>
                    <linearGradient id="sugarGrad" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#f5f0e0"/><stop offset="100%" stop-color="#c9a84c"/>
                    </linearGradient>
                    <linearGradient id="beanGrad" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#e6c777"/><stop offset="100%" stop-color="#8a6f2e"/>
                    </linearGradient>
                    <filter id="softShadow" x="-60%" y="-60%" width="220%" height="220%"><feGaussianBlur stdDeviation="5"/></filter>
                </defs>
                <!-- contact shadow -->
                <ellipse cx="85" cy="186" rx="88" ry="10" fill="#000" opacity=".45" filter="url(#softShadow)"/>
                <!-- saucer -->
                <ellipse cx="85" cy="182" rx="92" ry="11" fill="url(#saucerGrad)" stroke="#2c2c24" stroke-width="1"/>
                <ellipse cx="85" cy="180.5" rx="80" ry="8.5" fill="none" stroke="#8a6f2e" stroke-width=".6" opacity=".5"/>
                <!-- sugar cubes -->
                <g opacity=".95">
                    <rect x="8" y="163" width="20" height="20" rx="2.5" fill="url(#sugarGrad)" transform="rotate(-10 18 173)"/>
                    <rect x="22" y="148" width="18" height="18" rx="2.5" fill="url(#sugarGrad)" transform="rotate(8 31 157)"/>
                    <circle cx="15" cy="168" r="1" fill="#fff" opacity=".8"/>
                    <circle cx="34" cy="153" r="1" fill="#fff" opacity=".8"/>
                </g>
                <!-- garnish beans -->
                <g>
                    <g transform="translate(148,168) rotate(18)"><ellipse rx="7" ry="10" fill="url(#beanGrad)"/><path d="M0,-9 Q3,0 0,9" stroke="#0b0b09" stroke-width="1.3" fill="none" opacity=".65"/></g>
                    <g transform="translate(133,178) rotate(-24)"><ellipse rx="6" ry="8.5" fill="url(#beanGrad)"/><path d="M0,-7.5 Q2.5,0 0,7.5" stroke="#0b0b09" stroke-width="1.1" fill="none" opacity=".65"/></g>
                    <g transform="translate(158,180) rotate(6)"><ellipse rx="5.5" ry="7.5" fill="url(#beanGrad)"/><path d="M0,-6.5 Q2,0 0,6.5" stroke="#0b0b09" stroke-width="1" fill="none" opacity=".65"/></g>
                </g>
                <!-- handle -->
                <path d="M124,52 C152,52 152,98 124,98" fill="none" stroke="#8a6f2e" stroke-width="8" stroke-linecap="round"/>
                <path d="M124,52 C152,52 152,98 124,98" fill="none" stroke="#c9a84c" stroke-width="2" stroke-linecap="round" opacity=".5"/>
                <!-- cup body -->
                <path d="M34,20 L136,20 L124,128 Q122,141 85,141 Q48,141 46,128 Z" fill="url(#cupBodyGrad)" stroke="#8a6f2e" stroke-width="1"/>
                <path d="M42,26 L44,118" stroke="#e6c777" stroke-width="1.6" stroke-linecap="round" opacity=".45"/>
                <path d="M120,30 Q126,60 120,95" stroke="#fff" stroke-width="6" stroke-linecap="round" opacity=".05"/>
                <!-- espresso crema -->
                <ellipse cx="85" cy="22" rx="49" ry="8" fill="url(#cremaGrad)"/>
                <path d="M60,20 Q85,26 110,20" stroke="#e6c777" stroke-width="1" fill="none" opacity=".4"/>
                <path d="M66,23 Q85,18 104,23" stroke="#3d2612" stroke-width="1" fill="none" opacity=".35"/>
                <!-- rim -->
                <ellipse cx="85" cy="20" rx="51" ry="9" fill="none" stroke="#e6c777" stroke-width="1.4"/>
            </svg>
            <div class="steam"><span></span></div>
            <div class="steam s2"><span></span></div>
            <div class="steam s3"><span></span></div>
        </div>
    </div>
</section>

<!-- SIGNATURE COFFEE -->
<section class="section">
    <div class="section-hd" data-reveal>
        <div class="section-eyebrow" style="justify-content:center">Best Selling</div>
        <h2>Our <em>Signature</em> Coffee</h2>
        <p>The three most-ordered pieces from the <?= ucfirst($branch) ?> branch — ranked by what our regulars actually order, not by guesswork.</p>
    </div>
    <div class="sig-grid" data-reveal="stagger">
        <?php if (empty($signature)): ?>
            <div class="sig-empty">No signature items yet — check back soon.</div>
        <?php else: foreach ($signature as $i => $p): ?>
        <article class="sig-card">
            <div class="sig-media">
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
<section class="section" id="branches">
    <div class="section-hd" data-reveal>
        <div class="section-eyebrow" style="justify-content:center">Find Us</div>
        <h2>Two Branches, <em>One Craft</em></h2>
        <p>Same standard, same recipes, different neighborhoods. Choose the one you're ordering from.</p>
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
<section class="section story-section">
    <div class="floating-bean" style="top:8%;left:2%;animation-delay:0s"></div>
    <div class="floating-bean" style="top:72%;left:8%;animation-delay:1.4s"></div>
    <div class="floating-bean" style="top:20%;right:4%;animation-delay:2.6s"></div>
    <div class="story-grid">
        <div class="story-media" data-reveal>
            <img src="https://images.unsplash.com/photo-1447933601403-0c6688de566e?q=80&w=900&auto=format&fit=crop" alt="Coffee beans" loading="lazy">
        </div>
        <div class="story-copy" data-reveal>
            <div class="section-eyebrow">Our Philosophy</div>
            <h2>From Bean to <em>Experience.</em></h2>
            <p>It begins long before the espresso hits the cup — with beans sourced for character, roasted in small batches, and rested until they're ready to be pulled at exactly the right pressure and temperature.</p>
            <p>Every barista at AyosCoffeeNegosyo is trained to read a shot the way a sommelier reads a glass of wine — adjusting grind, dose, and time until the cup in front of you tastes the way it was meant to.</p>
            <p>That discipline is the same across both branches, on every order, every single day. It's not a slogan. It's the standard we hold ourselves to.</p>
            <a href="includes/about-us.php" class="btn-ghost magnetic" style="margin-top:6px">Read Our Story</a>
        </div>
    </div>
</section>

<!-- VALUE PROPS -->
<section class="section">
    <div class="section-hd" data-reveal>
        <div class="section-eyebrow" style="justify-content:center">Why AyosCoffeeNegosyo</div>
        <h2>Built on the <em>Details</em></h2>
    </div>
    <div class="props-grid" data-reveal="stagger">
        <div class="prop-card">
            <div class="prop-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></div>
            <h4>Made to Order</h4>
            <p>Every drink is pulled fresh the moment you order — nothing sits waiting on a shelf.</p>
        </div>
        <div class="prop-card">
            <div class="prop-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
            <h4>Two Locations</h4>
            <p>Laguna and Manila branches, each with a menu tuned to its own neighborhood.</p>
        </div>
        <div class="prop-card">
            <div class="prop-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
            <h4>Consistent Quality</h4>
            <p>The same recipe, the same standard, every single visit — no surprises, just good coffee.</p>
        </div>
    </div>
</section>

<!-- TESTIMONIALS (3-per-page) -->
<section class="section">
    <div class="section-hd" data-reveal>
        <div class="section-eyebrow" style="justify-content:center">Word of Mouth</div>
        <h2>What Guests Are <em>Saying</em></h2>
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

<!-- CTA BAND -->
<div class="cta-band" data-reveal>
    <div class="cta-steam"><span style="display:block;width:100%;height:100%;background:linear-gradient(180deg,transparent,rgba(240,234,216,.5),transparent);animation:rise 4s ease-in infinite"></span></div>
    <h2>Your Perfect Cup <em>Awaits.</em></h2>
    <p>Browse the full menu across both branches and order in a few taps — pulled fresh the moment you check out.</p>
    <a href="menu.php" class="btn-primary magnetic">
        Explore the Menu
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
    </a>
</div>

<footer>
    <div class="footer-inner">
        <div class="footer-brand"><div class="dot"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#0b0b09" stroke-width="2"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg></div>My AyosCoffeeNegosyo</div>
        <div class="footer-links">
            <a href="index.php">Home</a>
            <a href="menu.php">Menu</a>
            <a href="testimonial.php">Testimonials</a>
            <a href="includes/about-us.php">About Us</a>
        </div>
    </div>
    <div class="footer-bottom">
        <span>&copy; 2026 My AyosCoffeeNegosyo. All rights reserved.</span>
        <span>Developed by <a class="credit" href="https://www.instagram.com/_theprnx.gvara/" target="_blank" rel="noopener noreferrer">Prince Edward Guevara</a></span>
    </div>
</footer>

<script>
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

        /* Hero cup parallax */
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