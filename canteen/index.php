<?php
include 'config.php';
require_once __DIR__ . '/includes/school-art.php';   // the CMDI school + canteen illustration

// ── SESSION (re-validated against the database) ───────────────────────────
$user       = getLoggedInUser($conn);
$isLoggedIn = $user !== null;
$firstName  = $isLoggedIn ? ($user['first_name'] ?? '') : '';

// ── LIVE NUMBERS FROM THE DATABASE ────────────────────────────────────────
$stats = ['items' => 0, 'categories' => 0, 'students' => 0];

$r = $conn->query("SELECT COUNT(*) AS n, COUNT(DISTINCT category) AS c FROM products WHERE is_available = 1");
if ($r && ($row = $r->fetch_assoc())) { $stats['items'] = (int)$row['n']; $stats['categories'] = (int)$row['c']; }

$r = $conn->query("SELECT COUNT(*) AS n FROM users WHERE role = 'student' AND is_banned = 0");
if ($r && ($row = $r->fetch_assoc())) { $stats['students'] = (int)$row['n']; }

// ── FEATURED ITEMS ("Today's offerings") ──────────────────────────────────
$featured = [];
$r = $conn->query("SELECT id, name, category, description, price, stock, reorder_level, image
                   FROM products
                   WHERE is_featured = 1 AND is_available = 1 AND stock > 0
                   ORDER BY id ASC LIMIT 6");
if ($r) { while ($row = $r->fetch_assoc()) $featured[] = $row; }

// ── CATEGORIES WITH ITEM COUNTS ───────────────────────────────────────────
$categories = [];
$r = $conn->query("SELECT category, COUNT(*) AS n FROM products WHERE is_available = 1 GROUP BY category ORDER BY n DESC, category ASC");
if ($r) { while ($row = $r->fetch_assoc()) $categories[] = $row; }

// Line icons used when a product has no photo yet
function catIcon(string $cat): string {
    $paths = [
        'rice_meals' => '<path d="M3 11h18a9 9 0 0 1-18 0z"/><path d="M8 7c0-1.5 1-1.5 1-3M12 7c0-1.5 1-1.5 1-3M16 7c0-1.5 1-1.5 1-3"/>',
        'snacks'     => '<path d="M4 10a8 5 0 0 1 16 0z"/><path d="M3 14h18"/><path d="M4 18a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-1H4z"/>',
        'drinks'     => '<path d="M6 8h12l-1.5 12h-9z"/><path d="M12 8V3l4-1"/>',
        'desserts'   => '<path d="M8 11l4 11 4-11"/><path d="M7 11a5 5 0 1 1 10 0z"/>',
    ];
    $p = $paths[$cat] ?? '<circle cx="12" cy="12" r="8"/>';
    return '<svg viewBox="0 0 24 24" width="58" height="58" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>COPS — Canteen Ordering Platform System · CARD-MRI Development Institute</title>
<link rel="icon" href="<?= SCHOOL_LOGO ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=EB+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="cops-school.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    /* White Carrara marble · Aegean navy · burnished gold */
    --bg:#faf8f2;--basalt:#f1ece0;--card:#ffffff;--border:#e5dcc6;
    --gold:#b08d3c;--gold-bright:#7a5c19;--gold-dim:#c9b373;--gold-deep:#8a6a1f;
    --navy:#13294b;--navy-2:#1f3d6e;--champagne:#f5ecd2;
    --text:#1c1f2a;--muted:#5b6070;
    --serif:'Cinzel',serif;--body:'EB Garamond',Georgia,serif;
    --ease:cubic-bezier(.2,.8,.2,1);
}
html{scroll-behavior:smooth}
body{background:var(--bg);color:var(--text);font-family:var(--body);font-size:18px;line-height:1.65;min-height:100vh;overflow-x:hidden;-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility}
/* Carrara marble: grey-gold veining under a warm vignette */
body::before{content:'';position:fixed;inset:0;pointer-events:none;z-index:-1;opacity:.55;
    background:
        radial-gradient(ellipse 80% 55% at 12% 0%,rgba(217,187,104,.18),transparent 60%),
        radial-gradient(ellipse 60% 60% at 100% 100%,rgba(44,90,160,.09),transparent 60%),
        url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='1000' height='1000'><filter id='v' x='0' y='0' width='100%25' height='100%25'><feTurbulence type='turbulence' baseFrequency='0.0022 0.0075' numOctaves='6' seed='11'/><feColorMatrix values='0 0 0 0 .5  0 0 0 0 .46  0 0 0 0 .4  -10 0 0 0 1.1'/></filter><rect width='100%25' height='100%25' filter='url(%23v)'/></svg>");
    background-size:auto,auto,1000px 1000px}
a{color:inherit}
:focus-visible{outline:2px solid var(--gold-bright);outline-offset:3px}
.wrap{position:relative;z-index:1}

/* ornament primitives */
.meander{height:16px;background:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16'><path d='M0 13H16M13 13V3H3V10H10V6' fill='none' stroke='%23b08d3c' stroke-width='1.3'/></svg>") repeat-x center;opacity:.85}
.dentil{height:9px;border-top:1px solid var(--gold-dim);background:repeating-linear-gradient(90deg,var(--gold-dim) 0 7px,transparent 7px 14px)}

/* header */
.top-strip{position:relative;z-index:101;background:linear-gradient(180deg,var(--navy-2),var(--navy));border-bottom:2px solid var(--gold);text-align:center;padding:8px 16px;font-family:var(--serif);font-size:11px;letter-spacing:.3em;color:#f0dca0}
header{position:sticky;top:0;z-index:100;background:rgba(255,255,255,.82);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border-bottom:1px solid transparent;transition:background .3s,border-color .3s,box-shadow .3s}
header.scrolled{background:rgba(255,255,255,.96);border-bottom-color:var(--border);box-shadow:0 10px 30px -22px rgba(19,41,75,.4)}
.header-inner{max-width:1200px;margin:0 auto;padding:0 28px;height:78px;display:flex;align-items:center;justify-content:space-between;gap:20px}
.brand{display:flex;align-items:center;gap:13px;text-decoration:none}
.brand-logo{width:50px;height:50px;border-radius:50%;background:#fff;padding:3px;box-shadow:0 0 0 2px var(--gold),0 8px 16px -8px rgba(19,41,75,.55);transition:transform .5s var(--ease)}
.brand:hover .brand-logo{transform:rotate(-8deg) scale(1.05)}
.brand-logo img{display:block;width:100%;height:100%;border-radius:50%;object-fit:cover}
.brand-name{font-family:var(--serif);font-size:22px;font-weight:700;letter-spacing:.2em;color:var(--navy);line-height:1.1}
.brand-name small{display:block;font-family:var(--body);font-style:italic;font-weight:400;font-size:13px;letter-spacing:0;color:var(--gold-bright)}
nav{display:flex;align-items:center;gap:6px}
nav a{font-family:var(--serif);font-size:12px;font-weight:500;letter-spacing:.14em;color:var(--muted);text-decoration:none;padding:9px 13px;position:relative;transition:color .2s}
nav a:not(.cta)::after{content:'';position:absolute;left:13px;right:13px;bottom:4px;height:1px;background:var(--gold);transform:scaleX(0);transform-origin:left;transition:transform .3s var(--ease)}
nav a:not(.cta):hover{color:var(--navy)}
nav a:not(.cta):hover::after{transform:scaleX(1)}
nav a.active{color:var(--gold-bright)}
nav .hello{font-style:italic;font-family:var(--body);font-size:16px;color:var(--gold-bright);padding:0 10px}
nav a.cta{margin-left:8px;padding:11px 22px;color:#1a1400;font-weight:700;background:linear-gradient(180deg,#f0d78e,#c9a24a 55%,#a9822c);border:1px solid #d9b45a;box-shadow:0 10px 20px -12px rgba(138,106,31,.8);transition:filter .2s,transform .2s}
nav a.cta:hover{filter:brightness(1.08);transform:translateY(-2px)}
@media(max-width:860px){nav a:not(.cta):not(.keep),nav .hello{display:none}.brand-name small{display:none}}

/* buttons */
.btn{display:inline-flex;align-items:center;gap:10px;padding:15px 30px;font-family:var(--serif);font-size:13px;font-weight:700;letter-spacing:.16em;text-decoration:none;transition:filter .2s,transform .25s var(--ease),border-color .2s,color .2s,background .2s}
.btn-gold{color:#1a1400;background:linear-gradient(180deg,#f0d78e,#c9a24a 55%,#a9822c);border:1px solid #d9b45a;box-shadow:0 16px 28px -16px rgba(138,106,31,.85)}
.btn-gold:hover{filter:brightness(1.07);transform:translateY(-3px)}
.btn-ghost{color:var(--navy);border:1px solid var(--gold-dim);background:rgba(255,255,255,.6)}
.btn-ghost:hover{color:var(--gold-bright);border-color:var(--gold);background:#fff;transform:translateY(-3px)}

/* hero */
.hero{max-width:1240px;margin:0 auto;padding:64px 28px 40px;display:grid;grid-template-columns:.92fr 1.08fr;gap:52px;align-items:center}
.hero-kicker{font-family:var(--serif);font-size:12px;letter-spacing:.24em;color:var(--gold-bright);margin-bottom:22px;display:flex;align-items:center;gap:14px}
.hero-kicker::before{content:'';width:36px;height:1px;background:var(--gold)}
.hero h1{font-family:var(--serif);font-size:clamp(32px,4.2vw,56px);font-weight:600;line-height:1.16;color:var(--navy);letter-spacing:.01em;margin-bottom:14px}
.hero h1 em{font-style:normal;background:linear-gradient(180deg,#e2c06c,#b8903a 50%,#8a6a1f);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent}
.hero .latin{font-style:italic;font-size:22px;color:var(--gold-bright);margin-bottom:20px}
.hero .lede{max-width:46ch;font-size:19px;color:var(--muted);margin-bottom:32px}
.hero-ctas{display:flex;gap:14px;flex-wrap:wrap}
.hero-stats{display:flex;margin-top:42px;border-top:1px solid var(--gold-dim);border-bottom:1px solid var(--gold-dim);max-width:520px;background:rgba(255,255,255,.55)}
.hero-stats>div{flex:1;padding:18px 18px;border-right:1px solid var(--border)}
.hero-stats>div:first-child{padding-left:4px}
.hero-stats>div:last-child{border-right:none}
.stat-n{font-family:var(--serif);font-size:32px;font-weight:600;color:var(--gold-bright);line-height:1.1}
.stat-l{font-size:15px;color:var(--muted);line-height:1.3;margin-top:4px}

.hero-scene{animation:sceneIn 1.3s var(--ease) .15s both}
.hero-scene .scene-frame{margin-bottom:56px}
@keyframes sceneIn{from{opacity:0;transform:translateY(26px) scale(.97)}to{opacity:1;transform:none}}
.hero-copy>*{animation:fadeUp .9s var(--ease) both}
.hero-copy>*:nth-child(1){animation-delay:.05s}.hero-copy>*:nth-child(2){animation-delay:.15s}.hero-copy>*:nth-child(3){animation-delay:.25s}
.hero-copy>*:nth-child(4){animation-delay:.35s}.hero-copy>*:nth-child(5){animation-delay:.45s}.hero-copy>*:nth-child(6){animation-delay:.55s}
@keyframes fadeUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:none}}
@media(max-width:1000px){.hero{grid-template-columns:1fr;padding-top:44px}.hero-scene{max-width:680px;margin:0 auto;width:100%}}

/* trust strip */
.strip{background:linear-gradient(180deg,#fff,var(--champagne));border-top:1px solid var(--gold-dim);border-bottom:1px solid var(--gold-dim)}
.strip-inner{max-width:1200px;margin:0 auto;padding:16px 28px;display:flex;justify-content:center;flex-wrap:wrap;gap:12px 34px;font-family:var(--serif);font-size:12px;letter-spacing:.2em;color:var(--gold-bright)}
.strip-inner span{display:inline-flex;align-items:center;gap:12px}
.strip-inner span+span::before{content:'';width:5px;height:5px;background:var(--gold);transform:rotate(45deg);opacity:.9}

/* sections */
.section{max-width:1200px;margin:0 auto;padding:92px 28px}
.sec-hd{text-align:center;max-width:620px;margin:0 auto 52px}
.sec-hd h2{font-family:var(--serif);font-size:clamp(26px,3.4vw,38px);font-weight:600;color:var(--navy);letter-spacing:.03em;line-height:1.25}
.sec-hd p{margin-top:14px;color:var(--muted);font-size:18px}
.orn{display:flex;align-items:center;justify-content:center;gap:12px;margin-top:20px}
.orn span{width:70px;height:1px;background:linear-gradient(90deg,transparent,var(--gold))}
.orn span:last-child{transform:scaleX(-1)}
.orn svg{width:32px;height:32px;flex:none}

/* featured: arch-topped niches */
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:26px}
.item{background:linear-gradient(180deg,#fff,#fbf9f3);border:1px solid var(--border);padding:12px 12px 0;box-shadow:0 24px 50px -30px rgba(19,41,75,.45),inset 0 0 0 4px #fff,inset 0 0 0 5px rgba(176,141,60,.3);display:flex;flex-direction:column;transition:border-color .3s,transform .3s var(--ease),box-shadow .3s}
.item:hover{border-color:var(--gold-dim);transform:translateY(-6px);box-shadow:0 34px 56px -30px rgba(19,41,75,.55),inset 0 0 0 4px #fff,inset 0 0 0 5px rgba(176,141,60,.5)}
.item-media{position:relative;aspect-ratio:1/.8;margin:8px;border:1px solid var(--gold-dim);border-radius:999px 999px 2px 2px;overflow:hidden;background:linear-gradient(160deg,#f7f1e1,#efe6cf);display:flex;align-items:center;justify-content:center;color:var(--gold);box-shadow:inset 0 10px 24px -12px rgba(19,41,75,.35)}
.item-media img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.item-media::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 60%,rgba(19,41,75,.24));pointer-events:none}
.item-body{padding:16px 16px 22px;text-align:center;display:flex;flex-direction:column;flex:1}
.item-cat{font-size:15px;font-style:italic;color:var(--gold-bright)}
.item-name{font-family:var(--serif);font-size:19px;font-weight:600;color:var(--navy);margin:4px 0 8px;letter-spacing:.03em}
.item-desc{font-size:16px;color:var(--muted);line-height:1.5;flex:1;margin-bottom:16px}
.item-foot{display:flex;justify-content:space-between;align-items:center;padding-top:14px;border-top:1px solid var(--border)}
.price{font-family:var(--serif);font-size:20px;font-weight:600;color:var(--navy)}
.price small{font-size:13px;color:var(--gold);margin-right:2px}
.stock{font-size:15px;color:#2f7d4f}
.stock.low{color:#b7791f}
.empty{grid-column:1/-1;text-align:center;padding:48px 20px;border:1px dashed var(--gold-dim);color:var(--muted);background:rgba(255,255,255,.6)}
@media(max-width:960px){.grid-3{grid-template-columns:1fr 1fr}}
@media(max-width:620px){.grid-3{grid-template-columns:1fr;max-width:400px;margin-inline:auto}}

/* categories */
.cats{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:0;border:1px solid var(--gold-dim);background:linear-gradient(180deg,#fff,#faf8f2);box-shadow:0 24px 44px -32px rgba(19,41,75,.4)}
.cat{display:block;text-decoration:none;text-align:center;padding:34px 20px;border-right:1px solid var(--border);transition:background .3s}
.cat:last-child{border-right:none}
.cat:hover{background:rgba(176,141,60,.08)}
.cat-ico{color:var(--gold);margin-bottom:10px;display:flex;justify-content:center}
.cat-name{font-family:var(--serif);font-size:17px;font-weight:600;color:var(--navy);letter-spacing:.06em}
.cat-n{font-style:italic;color:var(--muted);font-size:16px}
@media(max-width:700px){.cat{border-right:none;border-bottom:1px solid var(--border)}.cat:last-child{border-bottom:none}}

/* how it works */
.steps{display:grid;grid-template-columns:repeat(3,1fr);gap:0}
.step{text-align:center;padding:10px 34px 14px;position:relative}
.step+.step::before{content:'';position:absolute;left:0;top:10px;bottom:10px;width:1px;background:linear-gradient(180deg,transparent,var(--gold-dim),transparent)}
.step-n{width:64px;height:64px;margin:0 auto 18px;border:1px solid var(--gold);border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--serif);font-size:20px;font-weight:600;color:var(--gold-bright);background:#fff;box-shadow:inset 0 0 0 4px #fff,inset 0 0 0 5px rgba(176,141,60,.4),0 8px 18px -10px rgba(19,41,75,.35)}
.step h3{font-family:var(--serif);font-size:20px;font-weight:600;color:var(--navy);letter-spacing:.05em}
.step .la{font-style:italic;color:var(--gold-bright);margin:2px 0 10px;font-size:18px}
.step p{color:var(--muted);font-size:17px;max-width:30ch;margin:0 auto}
@media(max-width:800px){.steps{grid-template-columns:1fr;gap:34px}.step+.step::before{display:none}}

/* why COPS band */
.band{background:radial-gradient(ellipse 70% 80% at 85% 20%,rgba(217,187,104,.2),transparent 60%),linear-gradient(180deg,#fff,var(--basalt));border-top:1px solid var(--gold-dim);border-bottom:1px solid var(--gold-dim)}
.band .frieze{padding:10px 0 4px}
.why{display:grid;grid-template-columns:1fr 1fr;gap:34px 56px}
.why-item{border-left:2px solid var(--gold);padding:2px 0 2px 22px}
.why-item h3{font-family:var(--serif);font-size:18px;font-weight:600;color:var(--navy);letter-spacing:.05em;margin-bottom:6px}
.why-item p{color:var(--muted);font-size:17px}
.why-lead{max-width:62ch;margin:0 auto 46px;text-align:center;color:var(--muted);font-size:19px}
@media(max-width:760px){.why{grid-template-columns:1fr}}

/* student feedback */
.maxims{display:grid;grid-template-columns:repeat(3,1fr);gap:26px}
.maxim{border:1px solid var(--border);background:linear-gradient(180deg,#fff,#fbf9f3);padding:30px 26px 26px;box-shadow:0 20px 40px -28px rgba(19,41,75,.35),inset 0 0 0 4px #fff,inset 0 0 0 5px rgba(176,141,60,.25)}
.maxim q{display:block;quotes:none;font-size:25px;line-height:1.3;color:var(--gold-bright);margin-bottom:10px}
.maxim .gloss{font-style:italic;color:var(--gold);font-size:18px;letter-spacing:.1em}
.maxim .src{font-size:14.5px;color:var(--muted);margin:2px 0 14px}
.maxim p{color:var(--muted);font-size:17px;padding-top:14px;border-top:1px solid var(--border)}
@media(max-width:900px){.maxims{grid-template-columns:1fr;max-width:480px;margin-inline:auto}}

/* CTA */
.cta-wrap{max-width:860px;margin:0 auto;padding:10px 28px 96px}
.cta-box{text-align:center;padding:46px 34px 52px;border:1px solid var(--gold);border-top:none;background:radial-gradient(ellipse 60% 80% at 50% 0%,rgba(217,187,104,.22),transparent 70%),linear-gradient(180deg,#fff,var(--champagne));box-shadow:inset 0 0 0 5px #fff,inset 0 0 0 6px rgba(176,141,60,.35),0 30px 50px -34px rgba(19,41,75,.4)}
.cta-box h2{font-family:var(--serif);font-size:clamp(24px,3.6vw,36px);font-weight:600;color:var(--navy);letter-spacing:.03em;margin-bottom:12px}
.cta-box p{color:var(--muted);max-width:46ch;margin:0 auto 28px}

/* footer */
footer{background:linear-gradient(180deg,var(--navy-2),#0d1c36);border-top:3px solid var(--gold)}
footer .frieze{padding:8px 0 0}
.foot{max-width:1200px;margin:0 auto;padding:30px 28px 26px;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:18px}
.foot-brand{display:flex;align-items:center;gap:14px;font-family:var(--serif);font-size:17px;font-weight:700;letter-spacing:.2em;color:#fff}
.foot-brand img{width:52px;height:52px;border-radius:50%;background:#fff;padding:3px;box-shadow:0 0 0 2px #d9b45a}
.foot-brand small{display:block;font-family:var(--body);font-size:15px;font-weight:400;letter-spacing:0;color:#f0dca0;font-style:italic}
.foot-links{display:flex;gap:24px;flex-wrap:wrap}
.foot-links a{font-family:var(--serif);font-size:12px;letter-spacing:.14em;color:rgba(255,255,255,.72);text-decoration:none;transition:color .2s}
.foot-links a:hover{color:#d9bb68}
.foot-bottom{max-width:1200px;margin:0 auto;padding:16px 28px 26px;border-top:1px solid rgba(217,187,104,.25);font-size:15px;color:rgba(255,255,255,.55)}

@media(max-width:700px){.section{padding:68px 20px}.header-inner{padding:0 16px}.hero{padding-left:20px;padding-right:20px}}
@media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important;scroll-behavior:auto!important}}
</style>
</head>
<body>

<!-- shared gradients (used by the laurel ornaments) -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
<defs>
    <linearGradient id="gG" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f0d78e"/><stop offset=".5" stop-color="#c9a24a"/><stop offset="1" stop-color="#8a6a1f"/></linearGradient>
</defs>
</svg>

<div class="top-strip">CARD-MRI DEVELOPMENT INSTITUTE &nbsp;&middot;&nbsp; SCHOOL CANTEEN &nbsp;&middot;&nbsp; ORDER AHEAD, SKIP THE LINE</div>

<header id="siteHeader">
    <div class="header-inner">
        <a href="index.php" class="brand" aria-label="COPS home">
            <span class="brand-logo"><img src="<?= SCHOOL_LOGO ?>" alt="CARD-MRI Development Institute logo"></span>
            <span class="brand-name">COPS<small>CMDI SPC Campus Canteen</small></span>
        </a>
        <nav aria-label="Main">
            <a href="index.php" class="active keep">HOME</a>
            <a href="menu.php">MENU</a>
            <a href="#how">HOW IT WORKS</a>
            <a href="#why">ABOUT</a>
            <?php if ($isLoggedIn): ?>
                <span class="hello">Hello, <?= htmlspecialchars($firstName) ?></span>
                <a href="log-out.php" class="keep">LOG OUT</a>
                <a href="menu.php" class="cta">ORDER NOW</a>
            <?php else: ?>
                <a href="log-in.php" class="keep">LOG IN</a>
                <a href="menu.php" class="cta">ORDER NOW</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="wrap">

<!-- HERO -->
<section class="hero">
    <div class="hero-copy">
        <div class="hero-kicker">CANTEEN ORDERING PLATFORM SYSTEM</div>
        <h1>A sound mind begins with a <em>good meal.</em></h1>
        <p class="latin">Mens sana in corpore sano.</p>
        <p class="lede">COPS lets CMDI students browse the canteen menu, check prices, and reserve their food from a phone, so break time is for eating, not for standing in line.</p>
        <div class="hero-ctas">
            <?php if ($isLoggedIn): ?>
                <a href="menu.php" class="btn btn-gold">BROWSE THE MENU</a>
            <?php else: ?>
                <a href="log-in.php" class="btn btn-gold">LOG IN TO ORDER</a>
            <?php endif; ?>
            <a href="#how" class="btn btn-ghost">HOW IT WORKS</a>
        </div>
        <div class="hero-stats">
            <div><div class="stat-n"><?= number_format($stats['items']) ?></div><div class="stat-l">Items on the menu</div></div>
            <div><div class="stat-n"><?= number_format($stats['categories']) ?></div><div class="stat-l">Food categories</div></div>
            <div><div class="stat-n"><?= number_format($stats['students']) ?></div><div class="stat-l">Registered students</div></div>
        </div>
    </div>

    <!-- the CMDI campus: classroom wing, marble entrance, canteen wing -->
    <div class="hero-scene">
        <div class="scene-frame">
            <span class="scene-corner tl"></span><span class="scene-corner tr"></span><span class="scene-corner bl"></span><span class="scene-corner br"></span>
            <div class="scene-frame-in"><?= school_scene('wide', 'h') ?></div>
            <div class="scene-seal"><img src="<?= SCHOOL_LOGO ?>" alt="CARD-MRI Development Institute seal"></div>
        </div>
    </div>
</section>

<div class="strip">
    <div class="strip-inner"><span>MADE FOR CMDI STUDENTS</span><span>MENU &amp; PRICES, ALWAYS CURRENT</span><span>ORDER AHEAD</span><span>FEWER MIX-UPS</span></div>
</div>

<!-- FEATURED -->
<section class="section" id="featured">
    <div class="sec-hd">
        <h2>Today&rsquo;s offerings</h2>
        <p>Student favorites, straight from the canteen&rsquo;s menu.</p>
        <div class="orn"><span></span><svg viewBox="-17 -17 34 34"><g data-wreath="12" data-pairs="6"></g></svg><span></span></div>
    </div>
    <div class="grid-3">
        <?php if (empty($featured)): ?>
            <div class="empty">No featured items yet. Set <code>is_featured = 1</code> on a product to show it here.</div>
        <?php else: foreach ($featured as $p):
            $low = (int)$p['stock'] <= (int)$p['reorder_level'];
        ?>
        <article class="item">
            <div class="item-media">
                <?= catIcon($p['category']) ?>
                <?php if (!empty($p['image'])): ?>
                    <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy" onerror="this.remove()">
                <?php endif; ?>
            </div>
            <div class="item-body">
                <div class="item-cat"><?= htmlspecialchars(categoryLabel($p['category'])) ?></div>
                <h3 class="item-name"><?= htmlspecialchars($p['name']) ?></h3>
                <p class="item-desc"><?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 90, '…')) ?></p>
                <div class="item-foot">
                    <div class="price"><small>₱</small><?= number_format((float)$p['price'], 2) ?></div>
                    <div class="stock <?= $low ? 'low' : '' ?>"><?= $low ? 'Few left' : 'Available' ?></div>
                </div>
            </div>
        </article>
        <?php endforeach; endif; ?>
    </div>
</section>

<!-- CATEGORIES -->
<section class="section" style="padding-top:0">
    <div class="sec-hd">
        <h2>Find what you&rsquo;re craving</h2>
        <p>Pick a category and go straight to the menu.</p>
        <div class="orn"><span></span><svg viewBox="-17 -17 34 34"><g data-wreath="12" data-pairs="6"></g></svg><span></span></div>
    </div>
    <div class="cats">
        <?php if (empty($categories)): ?>
            <div class="empty" style="border:none">The menu is being prepared. Check back soon.</div>
        <?php else: foreach ($categories as $c): ?>
        <a class="cat" href="menu.php?cat=<?= urlencode($c['category']) ?>">
            <div class="cat-ico"><?= catIcon($c['category']) ?></div>
            <div class="cat-name"><?= htmlspecialchars(categoryLabel($c['category'])) ?></div>
            <div class="cat-n"><?= (int)$c['n'] ?> item<?= (int)$c['n'] === 1 ? '' : 's' ?></div>
        </a>
        <?php endforeach; endif; ?>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="section" id="how" style="padding-top:20px">
    <div class="sec-hd">
        <h2>Three steps to lunch</h2>
        <p>No shouting over the counter. No wrong orders.</p>
        <div class="orn"><span></span><svg viewBox="-17 -17 34 34"><g data-wreath="12" data-pairs="6"></g></svg><span></span></div>
    </div>
    <div class="steps">
        <div class="step"><div class="step-n">I</div><h3>Read the menu</h3><div class="la">Lege</div><p>See what&rsquo;s available today, with prices, before you leave your seat.</p></div>
        <div class="step"><div class="step-n">II</div><h3>Reserve your order</h3><div class="la">Posce</div><p>Choose your items and a pickup time, then send your request to the canteen.</p></div>
        <div class="step"><div class="step-n">III</div><h3>Pick it up</h3><div class="la">Cape</div><p>Staff prepare it and mark it ready. Claim it with your code and pay at the counter.</p></div>
    </div>
</section>

<!-- WHY COPS -->
<div class="band" id="why">
    <div class="frieze"><div class="dentil"></div><div class="meander"></div></div>
    <section class="section">
        <div class="sec-hd">
            <h2>Why the canteen needs COPS</h2>
            <div class="orn"><span></span><svg viewBox="-17 -17 34 34"><g data-wreath="12" data-pairs="6"></g></svg><span></span></div>
        </div>
        <p class="why-lead">Break time is short and the counter is crowded. Orders get mixed up, staff get overwhelmed, and nobody knows what&rsquo;s still available. COPS puts the menu, the orders, and the records in one place.</p>
        <div class="why">
            <div class="why-item"><h3>Shorter queues</h3><p>Orders come in before you reach the counter, so the line moves faster for everyone.</p></div>
            <div class="why-item"><h3>Fewer mistakes</h3><p>What you pick on screen is what staff see. No more miscommunication across the counter.</p></div>
            <div class="why-item"><h3>A menu you can trust</h3><p>Check items and prices first. Sold-out food is marked, so you don&rsquo;t wait for nothing.</p></div>
            <div class="why-item"><h3>Records that keep themselves</h3><p>Every transaction is saved, so staff can follow daily sales and stock without paper tallies.</p></div>
        </div>
    </section>
    <div class="frieze"><div class="meander"></div><div class="dentil" style="border-top:none;border-bottom:1px solid var(--gold-dim)"></div></div>
</div>

<!-- STUDENT FEEDBACK
     NOTE: these three cards are SAMPLE text. Replace them with real feedback from CMDI students before going live. -->
<section class="section">
    <div class="sec-hd">
        <h2>What students say</h2>
        <div class="orn"><span></span><svg viewBox="-17 -17 34 34"><g data-wreath="12" data-pairs="6"></g></svg><span></span></div>
    </div>
    <div class="maxims">
        <div class="maxim">
            <q>No more long lines</q>
            <div class="gloss">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            <div class="src">Student feedback (sample)</div>
            <p>&ldquo;I reserve before break and my food is ready when I get to the canteen. I finally get to sit and eat properly.&rdquo;</p>
        </div>
        <div class="maxim">
            <q>I know what&rsquo;s available</q>
            <div class="gloss">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            <div class="src">Student feedback (sample)</div>
            <p>&ldquo;Seeing the menu and prices first saves me the trip. If it&rsquo;s sold out, it says so.&rdquo;</p>
        </div>
        <div class="maxim">
            <q>Easy to use</q>
            <div class="gloss">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            <div class="src">Student feedback (sample)</div>
            <p>&ldquo;Log in, pick my food, choose a pickup time. It takes less than a minute on my phone.&rdquo;</p>
        </div>
    </div>
</section>

<!-- CTA -->
<div class="cta-wrap">
    <svg viewBox="0 0 440 74" style="display:block;width:100%;height:auto;margin-bottom:-1px" aria-hidden="true">
        <polygon points="0,66 220,4 440,66" fill="#ffffff" stroke="url(#gG)" stroke-width="2"/>
        <polygon points="40,62 220,16 400,62" fill="none" stroke="#c9b373" stroke-width=".8" stroke-dasharray="2 4"/>
        <path d="M220,26 l7,9 l-7,9 l-7,-9z" fill="url(#gG)"/>
        <rect x="0" y="66" width="440" height="8" fill="url(#gG)"/>
    </svg>
    <div class="cta-box">
        <h2>Skip the line. Keep your break.</h2>
        <?php if ($isLoggedIn): ?>
            <p>Your account is ready. See what&rsquo;s cooking today and place your order.</p>
            <a href="menu.php" class="btn btn-gold">BROWSE THE MENU</a>
        <?php else: ?>
            <p>Log in with your student ID to see the menu and place your first order.</p>
            <a href="log-in.php" class="btn btn-gold">LOG IN</a>
        <?php endif; ?>
    </div>
</div>

</main>

<footer>
    <div class="frieze"><div class="dentil"></div><div class="meander"></div></div>
    <div class="foot">
        <div class="foot-brand"><img src="<?= SCHOOL_LOGO ?>" alt="CMDI logo"><span>COPS<small>Canteen Ordering Platform System &middot; CARD-MRI Development Institute</small></span></div>
        <div class="foot-links">
            <a href="index.php">HOME</a>
            <a href="menu.php">MENU</a>
            <a href="about-us.php">ABOUT US</a>
            <?php if ($isLoggedIn): ?><a href="log-out.php">LOG OUT</a><?php else: ?><a href="log-in.php">LOG IN</a><?php endif; ?>
        </div>
    </div>
    <div class="foot-bottom">&copy; 2026 COPS. A CARD-MRI Development Institute canteen ordering project.</div>
</footer>

<script>
/* Laurel wreath: builds two mirrored branches of leaf pairs into every [data-wreath] group */
(function () {
    var NS = 'http://www.w3.org/2000/svg';
    function el(n, a) { var e = document.createElementNS(NS, n); for (var k in a) e.setAttribute(k, a[k]); return e; }
    function wreath(g, R, pairs) {
        var rx = R * .23, ry = R * .095;
        [-1, 1].forEach(function (s) {
            var d = '';
            for (var i = 0; i <= 24; i++) {
                var th = (16 + (152 - 16) * i / 24) * Math.PI / 180;
                d += (i ? 'L' : 'M') + (s * R * Math.sin(th)).toFixed(2) + ' ' + (R * Math.cos(th)).toFixed(2);
            }
            g.appendChild(el('path', { d: d, fill: 'none', stroke: '#8a6a1f', 'stroke-width': Math.max(.5, R * .045), 'stroke-linecap': 'round' }));
            for (var j = 0; j < pairs; j++) {
                var th2 = (22 + (150 - 22) * j / (pairs - 1)) * Math.PI / 180;
                var px = s * R * Math.sin(th2), py = R * Math.cos(th2);
                var tan = Math.atan2(-Math.sin(th2), s * Math.cos(th2)) * 180 / Math.PI;
                [1, -1].forEach(function (o) {
                    var cx = px + s * Math.sin(th2) * o * R * .1, cy = py + Math.cos(th2) * o * R * .1;
                    g.appendChild(el('ellipse', {
                        cx: cx.toFixed(2), cy: cy.toFixed(2), rx: rx.toFixed(2), ry: ry.toFixed(2),
                        transform: 'rotate(' + (tan + o * s * 34).toFixed(1) + ' ' + cx.toFixed(2) + ' ' + cy.toFixed(2) + ')', fill: 'url(#gG)'
                    }));
                });
            }
            g.appendChild(el('circle', { cx: (s * R * Math.sin(.2)).toFixed(2), cy: (R * Math.cos(.2) + R * .1).toFixed(2), r: (R * .07).toFixed(2), fill: '#d9b45a' }));
        });
    }
    document.querySelectorAll('[data-wreath]').forEach(function (g) {
        wreath(g, parseFloat(g.getAttribute('data-wreath')), parseInt(g.getAttribute('data-pairs'), 10) || 7);
    });
})();

/* header gets a solid background once you scroll */
(function () {
    var h = document.getElementById('siteHeader');
    function f() { h.classList.toggle('scrolled', window.scrollY > 30); }
    window.addEventListener('scroll', f, { passive: true }); f();
})();
</script>
</body>
</html>