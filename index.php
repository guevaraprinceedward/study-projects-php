<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';

// ── SESSION VALIDATION ────────────────────────────────────────────────────
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

// ── TESTIMONIAL TEASER (3 latest) ─────────────────────────────────────────
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
$tRes = $conn->query("SELECT name, branch, rating, avatar_url, message FROM testimonials WHERE is_approved = 1 ORDER BY created_at DESC LIMIT 3");
if ($tRes) { while ($row = $tRes->fetch_assoc()) $teaser[] = $row; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AyosCoffeeNegosyo — Two Branches, One Craft</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<noscript><style>.loader{display:none!important}.reveal-el,.cup-stage,[data-reveal],[data-reveal]>*,.bean{opacity:1!important;transform:none!important}</style></noscript>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;
    --gold:#c9a84c;--gold-dim:#8a6f2e;--gold-pale:rgba(201,168,76,.08);
    --green:#4a7a3a;--green-lt:#6aaa52;--cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8;
}
html{scroll-behavior:smooth}
@media (prefers-reduced-motion: reduce){
    *{animation-duration:.01ms !important;animation-iteration-count:1 !important;transition-duration:.01ms !important;scroll-behavior:auto !important}
    .loader{display:none !important}
    .reveal-el,.cup-stage,[data-reveal],[data-reveal] > *,.bean{opacity:1 !important;transform:none !important}
}
body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden;perspective:1400px}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 8% 0%,rgba(201,168,76,.07) 0%,transparent 55%),radial-gradient(ellipse 55% 70% at 92% 100%,rgba(74,122,58,.08) 0%,transparent 55%);pointer-events:none;z-index:0}
a{color:inherit}
:focus-visible{outline:2px solid var(--gold);outline-offset:3px}

/* FILM GRAIN — quiet texture so dark surfaces feel material, not flat */
.grain{position:fixed;inset:0;z-index:4;pointer-events:none;opacity:.035;mix-blend-mode:overlay;background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='140' height='140'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/></filter><rect width='100%25' height='100%25' filter='url(%23n)'/></svg>")}

/* ── ENTRANCE LOADER — full curtain that opens on the brand mark ─────── */
.loader{position:fixed;inset:0;z-index:9999;background:var(--bg);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:20px;transition:opacity .7s ease, visibility 0s linear .7s}
.loader.done{opacity:0;visibility:hidden}
.loader-mark{width:56px;height:56px;border:1px solid var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center;opacity:0;transform:scale(.7);animation:loaderMark 1s cubic-bezier(.2,.8,.2,1) .1s forwards}
.loader-mark svg{width:22px;height:22px}
@keyframes loaderMark{to{opacity:1;transform:scale(1)}}
.loader-word{font-family:'Cormorant Garamond',serif;font-size:12.5px;letter-spacing:.34em;text-transform:uppercase;color:var(--muted);opacity:0;animation:loaderWord .8s ease .55s forwards}
@keyframes loaderWord{to{opacity:1}}
.loader-bar{width:170px;height:1px;background:var(--border);position:relative;overflow:hidden}
.loader-bar span{position:absolute;inset:0;background:linear-gradient(90deg,var(--gold-dim),var(--gold));transform:scaleX(0);transform-origin:left;animation:loaderBar 1.15s cubic-bezier(.4,0,.2,1) .35s forwards}
@keyframes loaderBar{to{transform:scaleX(1)}}

/* ── SCROLL PROGRESS ──────────────────────────────────────────────────── */
.scroll-progress{position:fixed;top:67px;left:0;height:1px;width:0%;background:linear-gradient(90deg,var(--gold-dim),var(--gold));z-index:99;pointer-events:none}

/* ── REVEAL PRIMITIVES ────────────────────────────────────────────────── */
.reveal-el{opacity:0;transform:translateY(24px) rotateX(6deg);transition:opacity .9s cubic-bezier(.2,.8,.2,1), transform .9s cubic-bezier(.2,.8,.2,1)}
body.loaded .reveal-el{opacity:1;transform:translateY(0) rotateX(0)}
.reveal-el.d1{transition-delay:.05s}.reveal-el.d2{transition-delay:.2s}.reveal-el.d3{transition-delay:.35s}.reveal-el.d4{transition-delay:.5s}.reveal-el.d5{transition-delay:.65s}

[data-reveal]{opacity:0;transform:translateY(34px);transition:opacity .8s cubic-bezier(.2,.8,.2,1), transform .8s cubic-bezier(.2,.8,.2,1)}
[data-reveal].in-view{opacity:1;transform:translateY(0)}
[data-reveal="stagger"]{opacity:1;transform:none;transition:none}
[data-reveal="stagger"] > *{opacity:0;transform:translateY(28px) rotateX(4deg);transition:opacity .7s cubic-bezier(.2,.8,.2,1), transform .7s cubic-bezier(.2,.8,.2,1)}
[data-reveal="stagger"].in-view > *{opacity:1;transform:translateY(0) rotateX(0)}
[data-reveal="stagger"].in-view > *:nth-child(1){transition-delay:.04s}
[data-reveal="stagger"].in-view > *:nth-child(2){transition-delay:.16s}
[data-reveal="stagger"].in-view > *:nth-child(3){transition-delay:.28s}

/* HEADER */
header{position:sticky;top:0;z-index:100;background:rgba(11,11,9,.88);backdrop-filter:blur(18px);border-bottom:1px solid var(--border)}
.header-inner{max-width:1200px;margin:0 auto;padding:0 32px;height:68px;display:flex;align-items:center;justify-content:space-between}
.brand{display:flex;align-items:center;gap:12px;text-decoration:none}
.brand-icon{width:36px;height:36px;border:1px solid var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center;transition:border-color .3s,transform .4s}
.brand:hover .brand-icon{border-color:var(--gold);transform:rotate(-14deg)}
.brand-name{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream);letter-spacing:.04em}
.brand-name span{color:var(--gold)}
nav{display:flex;align-items:center;gap:4px}
nav a{position:relative;font-size:12.5px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);text-decoration:none;padding:8px 14px;border-radius:3px;transition:color .2s}
nav a:not(.nav-cta)::after{content:'';position:absolute;left:14px;right:14px;bottom:5px;height:1px;background:var(--gold);transform:scaleX(0);transform-origin:left;transition:transform .35s cubic-bezier(.2,.8,.2,1)}
nav a:not(.nav-cta):hover{color:var(--cream)}
nav a:not(.nav-cta):hover::after,nav a.active::after{transform:scaleX(1)}
nav a.active{color:var(--gold)}
.nav-cta{margin-left:10px;padding:8px 18px !important;background:var(--green) !important;color:#fff !important;border-radius:3px;transition:background .25s !important,transform .25s !important}
.nav-cta:hover{background:var(--green-lt) !important;transform:translateY(-2px)}
@media(max-width:820px){nav a:not(.nav-cta){display:none}}

/* HERO */
.hero{position:relative;z-index:1;max-width:1200px;margin:0 auto;padding:88px 32px 60px;display:grid;grid-template-columns:1.05fr .95fr;gap:40px;align-items:center;transition:opacity .3s linear}
.hero-eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:11px;letter-spacing:.22em;text-transform:uppercase;color:var(--gold);margin-bottom:22px}
.hero-eyebrow::before{content:'';width:26px;height:1px;background:var(--gold-dim)}
.hero h1{font-family:'Cormorant Garamond',serif;font-size:clamp(42px,5.5vw,68px);font-weight:700;line-height:1.06;color:var(--cream);letter-spacing:-.01em;margin-bottom:20px}
.hero h1 em{font-style:italic;color:var(--gold)}
.hero p{font-size:15.5px;color:var(--muted);line-height:1.75;font-weight:300;max-width:460px;margin-bottom:34px}
.hero-ctas{display:flex;gap:14px;flex-wrap:wrap}
.btn-primary{position:relative;overflow:hidden;display:inline-flex;align-items:center;gap:10px;padding:14px 30px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;color:#fff;text-decoration:none;transition:background .2s,transform .25s;box-shadow:0 12px 30px -10px rgba(74,122,58,.5)}
.btn-primary::after{content:'';position:absolute;top:0;left:-60%;width:35%;height:100%;background:linear-gradient(115deg,transparent,rgba(255,255,255,.28),transparent);transform:skewX(-18deg);transition:left .65s ease}
.btn-primary:hover{background:var(--green-lt);transform:translateY(-3px)}
.btn-primary:hover::after{left:130%}
.btn-ghost{display:inline-flex;align-items:center;gap:10px;padding:14px 28px;border:1px solid var(--border);border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);text-decoration:none;transition:all .2s}
.btn-ghost:hover{border-color:var(--gold-dim);color:var(--gold);transform:translateY(-3px)}
.hero-stats{display:flex;gap:28px;margin-top:44px;padding-top:24px;border-top:1px solid var(--border)}
.hstat-num{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--gold)}
.hstat-label{font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-top:2px}

/* 3D CUP SIGNATURE */
.cup-stage{position:relative;height:440px;display:flex;align-items:center;justify-content:center;transform-style:preserve-3d;opacity:0;transform:scale(.85) rotateY(-8deg);transition:opacity 1.1s cubic-bezier(.2,.8,.2,1) .3s, transform 1.1s cubic-bezier(.2,.8,.2,1) .3s}
body.loaded .cup-stage{opacity:1;transform:scale(1) rotateY(0)}

/* ROTATING SEAL BADGE — spinning circular text, static center mark */
.hero-badge{position:absolute;top:2px;right:0;width:112px;height:112px;z-index:3;opacity:0;transform:scale(.7) rotate(-14deg);transition:opacity .9s cubic-bezier(.2,.8,.2,1) .95s, transform .9s cubic-bezier(.2,.8,.2,1) .95s}
body.loaded .hero-badge{opacity:1;transform:scale(1) rotate(0)}
.badge-ring-svg{width:100%;height:100%;display:block;animation:spin 17s linear infinite;animation-play-state:paused}
body.loaded .badge-ring-svg{animation-play-state:running}
.badge-ring-svg text{fill:var(--gold)}
.hero-badge-center{position:absolute;inset:0;margin:auto;width:38px;height:38px;border-radius:50%;background:var(--bg);border:1px solid var(--gold-dim);display:flex;align-items:center;justify-content:center;box-shadow:0 8px 20px -8px rgba(0,0,0,.7)}
@media(max-width:960px){.hero-badge{display:none}}
.cup-ring{position:absolute;width:340px;height:340px;border:1px solid rgba(201,168,76,.16);border-radius:50%;animation:spin 26s linear infinite;animation-play-state:paused}
.cup-ring.r2{width:400px;height:400px;border-color:rgba(106,170,82,.14);animation-duration:34s;animation-direction:reverse}
body.loaded .cup-ring{animation-play-state:running}
@keyframes spin{to{transform:rotate(360deg)}}
.bean{position:absolute;width:14px;height:20px;background:linear-gradient(135deg,var(--gold),var(--gold-dim));border-radius:50% 50% 50% 50%/60% 60% 40% 40%;box-shadow:0 6px 14px rgba(0,0,0,.5);opacity:0;transform:translateY(-18px);transition:opacity .6s ease, transform .6s cubic-bezier(.3,1.3,.4,1)}
body.loaded .bean{opacity:1;transform:translateY(0)}
.bean::after{content:'';position:absolute;top:2px;left:6px;width:2px;height:16px;background:rgba(11,11,9,.55);border-radius:2px}
.bean.b1{top:6%;left:48%;transition-delay:.55s}
.bean.b2{top:46%;left:0;transition-delay:.68s}
.bean.b3{top:80%;left:52%;transition-delay:.81s}
.bean.b4{top:40%;left:88%;transition-delay:.94s}
.cup3d{position:relative;width:170px;height:190px;transform-style:preserve-3d;animation:tilt 7s ease-in-out infinite;animation-play-state:paused}
body.loaded .cup3d{animation-play-state:running}
@keyframes tilt{0%,100%{transform:rotateY(-8deg) rotateX(4deg)}50%{transform:rotateY(8deg) rotateX(-2deg)}}
.cup-body{position:absolute;bottom:0;left:20px;width:110px;height:120px;background:linear-gradient(160deg,#241d10,#171410);border:1px solid var(--gold-dim);border-radius:8px 8px 28px 28px;box-shadow:0 30px 60px -20px rgba(0,0,0,.7),inset 0 -12px 24px rgba(0,0,0,.4)}
.cup-body::before{content:'';position:absolute;top:6px;left:10px;width:2px;height:78%;background:linear-gradient(180deg,rgba(201,168,76,.5),transparent);border-radius:2px}
.cup-rim{position:absolute;top:-3px;left:20px;width:110px;height:10px;border-radius:50%;border:1px solid var(--gold);background:linear-gradient(180deg,rgba(201,168,76,.35),transparent)}
.cup-liquid{position:absolute;top:8px;left:8px;right:8px;height:22px;background:radial-gradient(ellipse at 30% 30%,#9a6a34,#3d2612);border-radius:50%;box-shadow:inset 0 4px 8px rgba(0,0,0,.4)}
.cup-handle{position:absolute;bottom:38px;right:-24px;width:34px;height:44px;border:9px solid var(--gold-dim);border-left:none;border-radius:0 22px 22px 0}
.cup-saucer{position:absolute;bottom:-10px;left:-4px;width:158px;height:16px;background:linear-gradient(160deg,#1e1a12,#141210);border:1px solid var(--border);border-radius:50%;box-shadow:0 18px 34px -14px rgba(0,0,0,.7)}
.steam{position:absolute;top:-58px;left:56px;width:6px;height:60px;opacity:0}
.steam span{position:absolute;bottom:0;left:0;width:100%;height:100%;background:linear-gradient(180deg,transparent,rgba(240,234,216,.5),transparent);border-radius:40%;animation:rise 3.2s ease-in infinite;animation-play-state:paused}
body.loaded .steam span{animation-play-state:running}
.steam.s2{left:88px}.steam.s2 span{animation-delay:.8s}
.steam.s3{left:72px}.steam.s3 span{animation-delay:1.6s}
@keyframes rise{0%{transform:translateY(0) scaleX(1);opacity:0}20%{opacity:.7}100%{transform:translateY(-70px) scaleX(1.8);opacity:0}}
@media(max-width:960px){.hero{grid-template-columns:1fr;text-align:center}.hero p{margin-inline:auto}.hero-stats{justify-content:center}.cup-stage{height:320px}}

/* BRANCH SHOWCASE */
.section{position:relative;z-index:1;max-width:1200px;margin:0 auto;padding:70px 32px}
.section-hd{text-align:center;max-width:560px;margin:0 auto 44px}
.section-eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:10.5px;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:12px}
.section-eyebrow::before,.section-eyebrow::after{content:'';width:22px;height:1px;background:var(--gold-dim)}
.section-hd h2{font-family:'Cormorant Garamond',serif;font-size:clamp(28px,4vw,40px);font-weight:700;color:var(--cream)}
.section-hd h2 em{font-style:italic;color:var(--gold)}
.section-hd p{font-size:13.5px;color:var(--muted);margin-top:10px;line-height:1.7}

.branch-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px}
.branch-card{position:relative;background:var(--card);border:1px solid var(--border);border-radius:6px;padding:34px 30px;overflow:hidden;transform-style:preserve-3d;will-change:transform;transition:transform .35s cubic-bezier(.2,.8,.3,1),border-color .3s,box-shadow .4s}
.branch-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--gold-dim),transparent)}
.branch-card:hover{border-color:var(--gold-dim);box-shadow:0 30px 60px -20px rgba(0,0,0,.6)}
.branch-tag{display:inline-flex;align-items:center;gap:6px;font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:var(--green-lt);margin-bottom:14px}
.branch-tag .dot{width:6px;height:6px;border-radius:50%;background:var(--green-lt)}
.branch-card.manila .branch-tag{color:var(--gold)}
.branch-card.manila .branch-tag .dot{background:var(--gold)}
.branch-card h3{font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:var(--cream);margin-bottom:10px}
.branch-card p{font-size:13.5px;color:var(--muted);line-height:1.7;margin-bottom:22px}
.branch-link{display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:500;letter-spacing:.08em;text-transform:uppercase;color:var(--gold);text-decoration:none}
.branch-link svg{transition:transform .25s}
.branch-card:hover .branch-link svg{transform:translateX(4px)}

/* VALUE PROPS */
.props-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.prop-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:28px 24px;text-align:center;will-change:transform;transition:transform .35s cubic-bezier(.2,.8,.3,1),border-color .3s,box-shadow .4s}
.prop-card:hover{border-color:var(--gold-dim);box-shadow:0 24px 50px -20px rgba(0,0,0,.55)}
.prop-icon{width:46px;height:46px;margin:0 auto 16px;border:1px solid var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--gold);transition:transform .35s cubic-bezier(.2,.8,.3,1),border-color .3s}
.prop-card:hover .prop-icon{transform:rotate(-10deg) scale(1.08);border-color:var(--gold)}
.prop-card h4{font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:600;color:var(--cream);margin-bottom:8px}
.prop-card p{font-size:12.5px;color:var(--muted);line-height:1.6}

/* TESTIMONIAL TEASER */
.teaser-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.teaser-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:22px;will-change:transform;transition:transform .35s cubic-bezier(.2,.8,.3,1),border-color .3s,box-shadow .4s}
.teaser-card:hover{border-color:var(--gold-dim);box-shadow:0 24px 50px -20px rgba(0,0,0,.55)}
.teaser-stars{display:flex;gap:2px;margin-bottom:10px}
.teaser-stars svg{width:12px;height:12px;color:var(--gold)}
.teaser-msg{font-family:'Cormorant Garamond',serif;font-style:italic;font-size:15.5px;color:var(--cream);line-height:1.6;margin-bottom:16px}
.teaser-who{display:flex;align-items:center;gap:10px}
.teaser-avatar{width:32px;height:32px;border-radius:50%;object-fit:cover;border:1px solid var(--gold-dim);background:var(--surface)}
.teaser-avatar-fallback{width:32px;height:32px;border-radius:50%;border:1px solid var(--gold-dim);background:var(--surface);display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-size:12px;font-weight:700;color:var(--gold)}
.teaser-name{font-size:12.5px;font-weight:500;color:var(--cream)}
.teaser-branch{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em}

/* CTA BAND */
.cta-band{position:relative;z-index:1;max-width:1200px;margin:0 auto 90px;padding:56px 48px;background:var(--card);border:1px solid var(--border);border-radius:8px;text-align:center;overflow:hidden}
.cta-band::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 70% 60% at 50% 0%,rgba(201,168,76,.06) 0%,transparent 70%);animation:glowShift 9s ease-in-out infinite alternate}
@keyframes glowShift{0%{opacity:.65;transform:translateY(0)}100%{opacity:1;transform:translateY(12px)}}
.cta-band h2{font-family:'Cormorant Garamond',serif;font-size:clamp(26px,4vw,38px);font-weight:700;color:var(--cream);margin-bottom:14px}
.cta-band h2 em{font-style:italic;color:var(--gold)}
.cta-band p{font-size:13.5px;color:var(--muted);margin-bottom:26px}

footer{position:relative;z-index:1;border-top:1px solid var(--border);padding:30px 32px;text-align:center}
footer p{font-size:12px;color:var(--muted);letter-spacing:.06em}
footer p span{color:var(--gold-dim)}
footer a{color:var(--gold);text-decoration:none}
footer a:hover{color:var(--cream);text-decoration:underline}

@media(max-width:900px){.branch-grid,.props-grid,.teaser-grid{grid-template-columns:1fr}}
</style>
</head>
<body>

<div class="loader" id="loader" aria-hidden="true">
    <div class="loader-mark">
        <svg viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.5"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
    </div>
    <div class="loader-word">AyosCoffeeNegosyo</div>
    <div class="loader-bar"><span></span></div>
</div>
<div class="grain"></div>
<div class="scroll-progress" id="scrollProgress"></div>

<header>
    <div class="header-inner">
        <a href="index.php" class="brand">
            <div class="brand-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.5"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg></div>
            <span class="brand-name">My <span>AyosCoffeeNegosyo</span></span>
        </a>
        <nav>
            <a href="index.php" class="active">Home</a>
            <a href="menu.php">Menu</a>
            <a href="testimonial.php">Testimonials</a>
            <a href="includes/about-us.php">About Us</a>
            <?php if ($isLoggedIn): ?>
                <a href="profile.php">Profile</a>
                <a href="menu.php" class="nav-cta">Order Now</a>
            <?php else: ?>
                <a href="log-in.php">Login</a>
                <a href="menu.php" class="nav-cta">Order Now</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<!-- HERO -->
<section class="hero">
    <div>
        <div class="hero-eyebrow reveal-el d1">Est. 2026 &middot; Laguna &amp; Manila</div>
        <h1 class="reveal-el d2">Coffee, <em>crafted</em><br>with intention.</h1>
        <p class="reveal-el d3">Two branches, one standard. Every cup is pulled to order, every pastry baked fresh — a menu built for people who notice the difference.</p>
        <div class="hero-ctas reveal-el d4">
            <a href="menu.php" class="btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                View Full Menu
            </a>
            <a href="#branches" class="btn-ghost">Our Branches</a>
        </div>
        <div class="hero-stats reveal-el d5">
            <div><div class="hstat-num">2</div><div class="hstat-label">Branches</div></div>
            <div><div class="hstat-num">40+</div><div class="hstat-label">Menu Items</div></div>
            <div><div class="hstat-num">5.0</div><div class="hstat-label">Avg. Rating</div></div>
        </div>
    </div>

    <div class="cup-stage" aria-hidden="true">
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
        <div class="bean b1"></div><div class="bean b2"></div><div class="bean b3"></div><div class="bean b4"></div>
        <div class="steam"><span></span></div>
        <div class="steam s2"><span></span></div>
        <div class="steam s3"><span></span></div>
        <div class="cup3d">
            <div class="cup-saucer"></div>
            <div class="cup-body"><div class="cup-liquid"></div></div>
            <div class="cup-rim"></div>
            <div class="cup-handle"></div>
        </div>
    </div>
</section>

<!-- BRANCHES -->
<section class="section" id="branches">
    <div class="section-hd" data-reveal>
        <div class="section-eyebrow">Find Us</div>
        <h2>Two Branches, <em>One Craft</em></h2>
        <p>Same standard, same recipes, different neighborhoods. Pick the one closer to you.</p>
    </div>
    <div class="branch-grid" data-reveal="stagger">
        <div class="branch-card">
            <div class="branch-tag"><span class="dot"></span>Laguna Branch</div>
            <h3>Laguna</h3>
            <p>Our original branch — mains, sides, drinks, and desserts made fresh daily in a warm, unhurried space.</p>
            <a href="menu.php?branch=laguna" class="branch-link">Order from Laguna <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        <div class="branch-card manila">
            <div class="branch-tag"><span class="dot"></span>Manila Branch</div>
            <h3>Manila</h3>
            <p>Our city branch — coffee, meals, and a curated champagne list for a slightly different occasion.</p>
            <a href="menu.php?branch=manila" class="branch-link">Order from Manila <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
    </div>
</section>

<!-- VALUE PROPS -->
<section class="section">
    <div class="section-hd" data-reveal>
        <div class="section-eyebrow">Why AyosCoffeeNegosyo</div>
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

<!-- TESTIMONIAL TEASER -->
<?php if (!empty($teaser)): ?>
<section class="section">
    <div class="section-hd" data-reveal>
        <div class="section-eyebrow">Word of Mouth</div>
        <h2>What Guests Are <em>Saying</em></h2>
    </div>
    <div class="teaser-grid" data-reveal="stagger">
        <?php foreach ($teaser as $t):
            $rating = (int)$t['rating'];
            $initials = strtoupper(substr(trim($t['name']), 0, 1));
        ?>
        <div class="teaser-card">
            <div class="teaser-stars">
                <?php for ($s=1;$s<=5;$s++): ?>
                <svg viewBox="0 0 24 24" fill="<?= $s<=$rating?'currentColor':'none' ?>" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <?php endfor; ?>
            </div>
            <div class="teaser-msg">&ldquo;<?= htmlspecialchars(mb_strimwidth($t['message'], 0, 130, '…')) ?>&rdquo;</div>
            <div class="teaser-who">
                <?php if (!empty($t['avatar_url'])): ?>
                    <img class="teaser-avatar" src="<?= htmlspecialchars($t['avatar_url']) ?>" alt="" onerror="this.outerHTML='<div class=&quot;teaser-avatar-fallback&quot;><?= $initials ?></div>'">
                <?php else: ?>
                    <div class="teaser-avatar-fallback"><?= $initials ?></div>
                <?php endif; ?>
                <div>
                    <div class="teaser-name"><?= htmlspecialchars($t['name']) ?></div>
                    <div class="teaser-branch"><?= ucfirst($t['branch']) ?> Branch</div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- CTA BAND -->
<div class="cta-band" data-reveal>
    <h2>Ready for your <em>next cup?</em></h2>
    <p>Browse the full menu across both branches and order in a few taps.</p>
    <a href="menu.php" class="btn-primary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        View Full Menu
    </a>
</div>

<footer>
    <p>© 2026 <span>My AyosCoffeeNegosyo</span> — All rights reserved.</p>
    <p>Developed by: <a href="https://www.instagram.com/_theprnx.gvara/" target="_blank" rel="noopener noreferrer">Prince Edward Guevara</a></p>
</footer>

<script>
(function(){
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var loader  = document.getElementById('loader');

    function finishLoad(){
        if (document.body.classList.contains('loaded')) return;
        document.body.classList.add('loaded');
        if (loader) {
            loader.classList.add('done');
            setTimeout(function(){ if (loader.parentNode) loader.parentNode.removeChild(loader); }, 750);
        }
    }

    if (reduced) {
        finishLoad();
    } else {
        window.addEventListener('load', function(){ setTimeout(finishLoad, 850); });
        setTimeout(finishLoad, 2600); // safety fallback if 'load' is slow/blocked
    }

    // Scroll-triggered reveals
    var revealTargets = document.querySelectorAll('[data-reveal]');
    if (reduced || !('IntersectionObserver' in window)) {
        revealTargets.forEach(function(el){ el.classList.add('in-view'); });
    } else {
        var io = new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: .15, rootMargin: '0px 0px -60px 0px' });
        revealTargets.forEach(function(el){ io.observe(el); });
    }

    // Scroll progress line
    var progress = document.getElementById('scrollProgress');
    function onScroll(){
        var max = document.body.scrollHeight - window.innerHeight;
        var pct = max > 0 ? (window.scrollY / max) * 100 : 0;
        if (progress) progress.style.width = pct + '%';
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    if (!reduced) {
        var hoverFine = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

        // Cursor-driven 3D tilt on cards
        if (hoverFine) {
            document.querySelectorAll('.branch-card, .prop-card, .teaser-card').forEach(function(el){
                el.addEventListener('mousemove', function(e){
                    var r = el.getBoundingClientRect();
                    var px = (e.clientX - r.left) / r.width - .5;
                    var py = (e.clientY - r.top) / r.height - .5;
                    el.style.transform = 'perspective(900px) rotateX(' + (-py * 7).toFixed(2) + 'deg) rotateY(' + (px * 7).toFixed(2) + 'deg) translateY(-4px)';
                });
                el.addEventListener('mouseleave', function(){ el.style.transform = ''; });
            });

            // Cup parallax follows the cursor within the hero
            var cupStage = document.querySelector('.cup-stage');
            var heroSec  = document.querySelector('.hero');
            if (cupStage && heroSec) {
                heroSec.addEventListener('mousemove', function(e){
                    var r = heroSec.getBoundingClientRect();
                    var px = (e.clientX - r.left) / r.width - .5;
                    var py = (e.clientY - r.top) / r.height - .5;
                    cupStage.style.transform = 'translate(' + (px * 16).toFixed(1) + 'px, ' + (py * 12).toFixed(1) + 'px)';
                });
                heroSec.addEventListener('mouseleave', function(){ cupStage.style.transform = ''; });
            }
        }

        // Hero recedes gently as the page scrolls past it
        var hero = document.querySelector('.hero');
        window.addEventListener('scroll', function(){
            if (!hero) return;
            var y = window.scrollY;
            if (y < 900) {
                hero.style.opacity   = String(Math.max(0, 1 - y / 900));
                hero.style.transform = 'translateY(' + (y * 0.1).toFixed(1) + 'px)';
            }
        }, { passive: true });
    }
})();
</script>

</body>
</html>