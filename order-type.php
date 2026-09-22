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
<title>Choose Order Type — AyosCoffeeNegosyo</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;
    --gold:#c9a84c;--gold-dim:#8a6f2e;--green:#4a7a3a;--green-lt:#6aaa52;
    --cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8;
}
html{scroll-behavior:smooth}
body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 10% 0%,rgba(201,168,76,0.06) 0%,transparent 55%),radial-gradient(ellipse 50% 70% at 90% 100%,rgba(74,122,58,0.07) 0%,transparent 55%);pointer-events:none;z-index:0}
:focus-visible{outline:2px solid var(--gold);outline-offset:3px}

header{position:sticky;top:0;z-index:100;background:rgba(11,11,9,0.88);backdrop-filter:blur(18px);border-bottom:1px solid var(--border)}
.header-inner{max-width:1100px;margin:0 auto;padding:0 32px;height:68px;display:flex;align-items:center;justify-content:space-between}
.brand{display:flex;align-items:center;gap:12px;text-decoration:none}
.brand-icon{width:36px;height:36px;border:1px solid var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center}
.brand-name{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream);letter-spacing:0.04em}
.brand-name span{color:var(--gold)}
nav{display:flex;align-items:center;gap:6px}
nav a{font-size:12.5px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);text-decoration:none;padding:8px 14px;border-radius:3px;transition:color .2s,background .2s}
nav a:hover{color:var(--cream);background:rgba(255,255,255,0.04)}

.page-hero{position:relative;z-index:1;text-align:center;padding:64px 32px 30px;max-width:720px;margin:0 auto}
.page-hero h1{font-family:'Cormorant Garamond',serif;font-size:clamp(34px,5vw,54px);font-weight:700;color:var(--cream);line-height:1.15;margin-bottom:14px}
.page-hero p{font-size:15px;color:var(--muted);line-height:1.7;font-weight:300}

.branch-bar{position:relative;z-index:1;display:flex;justify-content:center;align-items:center;gap:10px;padding:8px 32px 40px;flex-wrap:wrap}
.branch-bar .lbl{font-size:12px;color:var(--muted);margin-right:4px}
.branch-btn{display:flex;align-items:center;gap:8px;padding:9px 22px;border-radius:100px;border:1px solid var(--border);color:var(--muted);font-size:12px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;text-decoration:none;transition:all .25s ease}
.branch-btn:hover{border-color:var(--gold-dim);color:var(--gold)}
.branch-btn.active{border-color:var(--gold);color:var(--gold);background:rgba(201,168,76,.1)}
.branch-dot{width:7px;height:7px;border-radius:50%;background:var(--green-lt)}
.branch-btn.active .branch-dot{background:var(--gold)}

.choices{position:relative;z-index:1;max-width:940px;margin:0 auto;padding:0 32px 90px;display:grid;grid-template-columns:1fr 1fr;gap:24px}
.choice{display:flex;flex-direction:column;background:var(--card);border:1px solid var(--border);border-radius:6px;padding:36px 32px 30px;text-decoration:none;color:inherit;transition:border-color .3s,transform .3s cubic-bezier(.2,.8,.2,1),box-shadow .3s}
.choice:hover{border-color:var(--gold-dim);transform:translateY(-5px);box-shadow:0 26px 60px -24px rgba(0,0,0,.7)}
.choice-icon{width:56px;height:56px;border:1px solid var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--gold);margin-bottom:22px}
.choice h2{font-family:'Cormorant Garamond',serif;font-size:30px;font-weight:700;color:var(--cream);margin-bottom:10px}
.choice p{font-size:14px;color:var(--muted);line-height:1.75;margin-bottom:20px;flex:1}
.choice ul{list-style:none;margin-bottom:26px;display:flex;flex-direction:column;gap:8px}
.choice li{font-size:12.5px;color:var(--text);display:flex;gap:9px;align-items:flex-start;line-height:1.5}
.choice li svg{flex-shrink:0;margin-top:2px;color:var(--green-lt)}
.choice-cta{display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:13px 24px;background:var(--green);border-radius:3px;font-size:13px;font-weight:500;letter-spacing:.09em;text-transform:uppercase;color:#fff;transition:background .2s}
.choice:hover .choice-cta{background:var(--green-lt)}
.choice.alt .choice-cta{background:transparent;border:1px solid var(--gold-dim);color:var(--gold)}
.choice.alt:hover .choice-cta{background:rgba(201,168,76,.1);border-color:var(--gold)}

footer{position:relative;z-index:1;border-top:1px solid var(--border);padding:28px 32px;text-align:center}
footer p{font-size:12px;color:var(--muted);letter-spacing:.06em}
footer p span{color:var(--gold-dim)}

@media(max-width:768px){
    .header-inner{padding:0 16px}
    nav a:not(:last-child){display:none}
    .choices{grid-template-columns:1fr;padding:0 16px 60px}
    .choice{padding:28px 22px 24px}
}
@media (prefers-reduced-motion: reduce){*{transition-duration:.01ms !important}}
</style>
</head>
<body>

<header>
    <div class="header-inner">
        <a href="index.php" class="brand">
            <div class="brand-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
            </div>
            <span class="brand-name">My <span>AyosCoffeeNegosyo</span></span>
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
    <h1>How would you like to order?</h1>
    <p>Get your coffee, meals and packages served fresh right now, or reserve them ahead for a date and time that works for you.</p>
</section>

<div class="branch-bar">
    <span class="lbl">Ordering from</span>
    <a href="?branch=laguna" class="branch-btn <?= $branch === 'laguna' ? 'active' : '' ?>"><span class="branch-dot"></span>Laguna</a>
    <a href="?branch=manila" class="branch-btn <?= $branch === 'manila' ? 'active' : '' ?>"><span class="branch-dot"></span>Manila</a>
</div>

<main class="choices">
    <a href="menu.php" class="choice">
        <div class="choice-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
        </div>
        <h2>Serve Now</h2>
        <p>Order right now and we prepare it fresh the moment you check out.</p>
        <ul>
            <li><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Made to order, no waiting for a set time</li>
            <li><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Checkout right after you pick your items</li>
        </ul>
        <span class="choice-cta">Order Now</span>
    </a>

    <a href="reservation-menu.php" class="choice alt">
        <div class="choice-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <h2>Reservation</h2>
        <p>Reserve your items for a date and time you choose, then pay now or pay later.</p>
        <ul>
            <li><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Your items are held for you until you arrive</li>
            <li><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Includes <?= $pkgCount ?> Packages Combo at <?= ucfirst($branch) ?></li>
        </ul>
        <span class="choice-cta">Make a Reservation</span>
    </a>
</main>

<footer><p>© 2026 <span>My AyosCoffeeNegosyo</span> — All rights reserved.</p></footer>

</body>
</html>