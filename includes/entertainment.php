<?php
include_once 'hotel-config.php';
$activePage = 'services';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Entertainment — Nocturne Manila Bay</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="hotel-theme.css">
<style>
.choice-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:24px;max-width:1000px;margin:0 auto;padding:20px 32px 130px}
.choice-card{position:relative;border-radius:6px;overflow:hidden;border:1px solid var(--h-line);min-height:340px;display:flex;flex-direction:column;justify-content:flex-end;text-decoration:none;transition:border-color 0.25s}
.choice-card:hover{border-color:var(--h-gold-dim)}
.choice-card img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0.5;transition:opacity 0.3s,transform 0.4s}
.choice-card:hover img{opacity:0.65;transform:scale(1.04)}
.choice-card::after{content:'';position:absolute;inset:0;background:linear-gradient(0deg,rgba(7,7,10,0.96) 15%,rgba(7,7,10,0.1) 85%)}
.choice-body{position:relative;z-index:1;padding:30px}
.choice-eyebrow{font-size:10.5px;letter-spacing:0.2em;text-transform:uppercase;color:var(--h-gold);margin-bottom:10px}
.choice-body h3{font-family:'Cormorant Garamond',serif;font-size:30px;color:var(--h-champagne);margin-bottom:10px}
.choice-body p{font-size:13px;color:var(--h-muted);line-height:1.7;margin-bottom:16px;max-width:340px}
.choice-cta{font-size:11px;letter-spacing:0.1em;text-transform:uppercase;color:var(--h-gold);display:inline-flex;align-items:center;gap:8px}
</style>
</head>
<body>
<?php include_once 'sidebar.php'; ?>
<?php include_once 'topheader.php'; ?>

<section class="page-hero">
    <div class="sec-eyebrow"><a href="hotel-services.php" style="color:var(--h-gold-dim);text-decoration:none">Spa, Shop &amp; Entertainment</a> / Entertainment</div>
    <h1>Choose Your <em>Evening</em></h1>
    <p>Billiards or the casino floor — each residence keeps its own tables, games, and rates.</p>
</section>

<div class="choice-grid">
    <a href="billiards.php" class="choice-card">
        <img src="https://images.unsplash.com/photo-1611416517420-f0da7a53f9a4?w=1200" alt="Billiards">
        <div class="choice-body">
            <div class="choice-eyebrow">Option One</div>
            <h3>Billiards</h3>
            <p>Reserve a table by the hour. Standard, snooker, and VIP felt tables available, priced per residence.</p>
            <span class="choice-cta">Reserve a table →</span>
        </div>
    </a>
    <a href="casino.php" class="choice-card">
        <img src="https://images.unsplash.com/photo-1596838132731-3301c3fd4317?w=1200" alt="Casino">
        <div class="choice-body">
            <div class="choice-eyebrow">Option Two</div>
            <h3>Casino</h3>
            <p>Blackjack, poker, baccarat, roulette, and slots — a private session with a house dealer, per game.</p>
            <span class="choice-cta">Pick a game →</span>
        </div>
    </a>
</div>

<footer><p>© 2026 <span>Nocturne Manila Bay</span> — A Property of AyosCoffeeNegosyo Hospitality.</p></footer>
</body>
</html>