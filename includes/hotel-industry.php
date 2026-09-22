<?php
include_once 'hotel-config.php';
$activePage = 'home';
$tier = currentTier();

// ── SUITES + IMAGES + LIVE ROOM COUNT ───────────────────────────────────
$suites = [];
$res = $conn->query("SELECT * FROM suites ORDER BY id ASC");
while ($row = $res->fetch_assoc()) {
    $imgs = [];
    $ir = $conn->query("SELECT image_url FROM suite_images WHERE suite_id = {$row['id']} ORDER BY sort_order ASC");
    while ($i = $ir->fetch_assoc()) $imgs[] = $i['image_url'];
    if (empty($imgs)) $imgs[] = $row['cover_image'];
    $row['images'] = $imgs;
    $row['features_arr'] = array_filter(explode('|', $row['features']));
    $rc = $conn->query("SELECT COUNT(*) c FROM rooms WHERE suite_id = {$row['id']} AND status='available'");
    $row['room_count'] = (int)$rc->fetch_assoc()['c'];
    $suites[] = $row;
}

// ── ACTIVE OFFERS (hotel-wide + residence-specific) ─────────────────────
$offers = [];
$today = date('Y-m-d');
$or = $conn->query("SELECT o.*, s.name AS suite_name FROM offers o LEFT JOIN suites s ON s.id = o.suite_id
                     WHERE o.active=1 AND o.valid_from <= '$today' AND o.valid_to >= '$today'
                     AND (o.audience='all' OR o.audience='$tier') ORDER BY o.discount_percent DESC");
while ($row = $or->fetch_assoc()) $offers[] = $row;

// ── MEMBERSHIP TIERS ──────────────────────────────────────────────────
$tiers = [];
$tr = $conn->query("SELECT * FROM membership_tiers ORDER BY min_lifetime_spend ASC");
while ($row = $tr->fetch_assoc()) { $row['perks_arr'] = explode('|', $row['perks']); $tiers[] = $row; }

// ── HOURS DATA (signature clock) ─────────────────────────────────────────
$hours = [
    ["time" => "00:00", "label" => "Midnight Concierge", "note" => "A call is answered before the second ring, day or night.", "pos" => 0],
    ["time" => "04:00", "label" => "The Dawn Watch",     "note" => "Early risers find the rooftop lit and the coffee already poured.", "pos" => 1],
    ["time" => "08:00", "label" => "The Morning Table",  "note" => "Breakfast laid under glass, overlooking the water.", "pos" => 2],
    ["time" => "12:00", "label" => "Midday Repose",      "note" => "The spa and the infinity pool, at their quietest.", "pos" => 3],
    ["time" => "16:00", "label" => "Golden Hour Arrival","note" => "Check-in begins the moment the light turns amber.", "pos" => 4],
    ["time" => "20:00", "label" => "The Evening Table",  "note" => "A six-course tasting menu, seated for two hours or six.", "pos" => 5],
];
$clockPos = [
    ["top"=>"6%","left"=>"50%"],["top"=>"28%","left"=>"88.1%"],["top"=>"72%","left"=>"88.1%"],
    ["top"=>"94%","left"=>"50%"],["top"=>"72%","left"=>"11.9%"],["top"=>"28%","left"=>"11.9%"],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nocturne Manila Bay — A Hôtel of Uncommon Hours</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="hotel-theme.css">
<style>
/* PAGE-SPECIFIC: home */
.hero{position:relative;z-index:1;padding:110px 32px 90px;text-align:center;overflow:hidden}
.hero-stars{position:absolute;inset:0;pointer-events:none}
.hero-stars span{position:absolute;width:2px;height:2px;background:var(--h-gold);border-radius:50%;opacity:0.5;animation:twinkle 4s ease-in-out infinite}
@keyframes twinkle{0%,100%{opacity:0.15}50%{opacity:0.7}}
.hero-eyebrow{position:relative;display:inline-flex;align-items:center;gap:12px;font-size:11px;letter-spacing:0.32em;text-transform:uppercase;color:var(--h-gold);margin-bottom:26px}
.hero-eyebrow::before,.hero-eyebrow::after{content:'';width:36px;height:1px;background:var(--h-gold-dim)}
.hero h1{position:relative;font-family:'Cormorant Garamond',serif;font-size:clamp(56px,9vw,118px);font-weight:600;line-height:0.95;letter-spacing:0.02em;color:var(--h-champagne);margin-bottom:22px}
.hero h1 em{font-style:italic;color:var(--h-gold);font-weight:500}
.hero-sub{position:relative;font-family:'Cormorant Garamond',serif;font-style:italic;font-size:19px;color:var(--h-muted);letter-spacing:0.02em;margin-bottom:36px}
.hero-loc{position:relative;display:inline-flex;align-items:center;gap:8px;font-size:11px;letter-spacing:0.16em;text-transform:uppercase;color:var(--h-gold);border:1px solid var(--h-gold-dim);border-radius:100px;padding:8px 20px;background:rgba(207,167,107,0.05);margin-bottom:14px}
.hero-cta{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:26px;position:relative}

.philosophy{position:relative;z-index:1;max-width:760px;margin:0 auto;padding:0 32px 100px;text-align:center}
.philosophy p{font-family:'Cormorant Garamond',serif;font-size:26px;line-height:1.6;color:var(--h-text);font-weight:400}
.philosophy p::first-letter{font-size:64px;font-weight:600;color:var(--h-gold);float:left;line-height:0.8;padding:6px 10px 0 0}

.offers{position:relative;z-index:1;max-width:1240px;margin:0 auto;padding:0 32px 110px}
.offer-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px}
.offer-card{position:relative;border-radius:4px;overflow:hidden;border:1px solid var(--h-line);background:var(--h-card);min-height:230px;display:flex;flex-direction:column;justify-content:flex-end}
.offer-card img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0.4;transition:opacity 0.3s}
.offer-card:hover img{opacity:0.55}
.offer-card::after{content:'';position:absolute;inset:0;background:linear-gradient(0deg,rgba(7,7,10,0.95) 10%,rgba(7,7,10,0.2) 80%)}
.offer-body{position:relative;padding:22px;z-index:1}
.offer-badges{display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap}
.offer-body h4{font-family:'Cormorant Garamond',serif;font-size:21px;color:var(--h-champagne);margin-bottom:6px}
.offer-body p{font-size:12px;color:var(--h-muted);line-height:1.6;margin-bottom:10px}
.offer-discount{font-family:'Cormorant Garamond',serif;font-size:28px;color:var(--h-gold);font-weight:700}

.tiers{position:relative;z-index:1;max-width:1240px;margin:0 auto;padding:0 32px 120px}
.tier-grid{display:grid;grid-template-columns:1fr 1fr;gap:1px;background:var(--h-line);border:1px solid var(--h-line);border-radius:4px;overflow:hidden}
.tier-card{background:var(--h-card);padding:38px 34px}
.tier-card.vip{background:linear-gradient(160deg,#171310,#141417)}
.tier-card h3{font-family:'Cormorant Garamond',serif;font-size:26px;color:var(--h-champagne);margin-bottom:4px}
.tier-card .tier-sub{font-size:11.5px;color:var(--h-muted);margin-bottom:22px}
.tier-card.vip h3{color:var(--h-vip)}
.tier-perks{list-style:none;display:flex;flex-direction:column;gap:10px}
.tier-perks li{font-size:13px;color:var(--h-text);display:flex;align-items:flex-start;gap:10px}
.tier-perks li::before{content:'✓';color:var(--h-gold);font-weight:700;flex-shrink:0}

.suites{position:relative;z-index:1;max-width:1240px;margin:0 auto;padding:0 32px 110px}
.suite-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
.suite-card{background:var(--h-card);border:1px solid var(--h-line);border-radius:4px;overflow:hidden;display:flex;flex-direction:column;transition:border-color 0.3s}
.suite-card:hover{border-color:var(--h-gold-dim)}
.suite-body{padding:26px 26px 28px;display:flex;flex-direction:column;gap:14px;flex:1}
.suite-num{font-size:11px;letter-spacing:0.14em;color:var(--h-gold-dim);text-transform:uppercase}
.suite-card h3{font-family:'Cormorant Garamond',serif;font-size:25px;font-weight:600;color:var(--h-champagne)}
.suite-meta{display:flex;gap:14px;font-size:11px;color:var(--h-muted);letter-spacing:0.04em}
.suite-desc{font-size:13px;line-height:1.7;color:var(--h-muted);font-weight:300;flex:1}
.suite-features{list-style:none;display:flex;flex-direction:column;gap:7px;padding:14px 0;border-top:1px solid var(--h-line);border-bottom:1px solid var(--h-line)}
.suite-features li{font-size:12px;color:var(--h-text);display:flex;align-items:center;gap:9px}
.suite-features li::before{content:'';width:4px;height:4px;background:var(--h-gold);border-radius:50%;flex-shrink:0}
.suite-foot{display:flex;align-items:center;justify-content:space-between}
.suite-price{font-family:'Cormorant Garamond',serif;font-size:23px;color:var(--h-gold);font-weight:700}
.suite-price span{font-size:10.5px;font-weight:400;font-family:'Jost',sans-serif;color:var(--h-muted);text-transform:uppercase;letter-spacing:0.06em;display:block;margin-top:2px}

.clock-section{position:relative;z-index:1;padding:20px 32px 130px}
.clock-wrap{max-width:1240px;margin:0 auto;background:var(--h-surface);border:1px solid var(--h-line);border-radius:4px;padding:70px 40px;display:grid;grid-template-columns:1fr 1.1fr;gap:60px;align-items:center}
.clock-intro h2{font-family:'Cormorant Garamond',serif;font-size:clamp(30px,3.6vw,42px);color:var(--h-champagne);line-height:1.15;margin-bottom:18px}
.clock-intro h2 em{color:var(--h-gold);font-style:italic}
.clock-intro p{font-size:14px;color:var(--h-muted);line-height:1.8;font-weight:300;max-width:400px}
.clock-face{position:relative;width:min(440px,100%);aspect-ratio:1;margin:0 auto}
.clock-ring{position:absolute;inset:0;border:1px solid var(--h-line);border-radius:50%}
.clock-ring::before{content:'';position:absolute;inset:16%;border:1px dashed rgba(207,167,107,0.18);border-radius:50%}
.clock-center{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;width:130px}
.clock-center span{display:block;font-family:'Cormorant Garamond',serif;font-size:13px;font-style:italic;color:var(--h-gold);letter-spacing:0.04em}
.clock-point{position:absolute;transform:translate(-50%,-50%);text-align:center;width:118px}
.clock-dot{width:8px;height:8px;background:var(--h-gold);border-radius:50%;margin:0 auto 8px;box-shadow:0 0 0 4px rgba(207,167,107,0.12)}
.clock-time{font-size:10px;letter-spacing:0.12em;color:var(--h-gold-dim);text-transform:uppercase;margin-bottom:3px}
.clock-label{font-family:'Cormorant Garamond',serif;font-size:15px;font-weight:600;color:var(--h-champagne);line-height:1.2;margin-bottom:5px}
.clock-note{font-size:10.5px;color:var(--h-muted);line-height:1.5;font-weight:300}

.amenities{position:relative;z-index:1;max-width:1240px;margin:0 auto;padding:0 32px 120px}
.amen-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:2px;background:var(--h-line)}
.amen-card{background:var(--h-bg);padding:36px 28px;display:flex;flex-direction:column;gap:14px;transition:background 0.3s}
.amen-card:hover{background:var(--h-card)}
.amen-icon{width:38px;height:38px;display:flex;align-items:center;justify-content:center;color:var(--h-gold)}
.amen-card h4{font-family:'Cormorant Garamond',serif;font-size:19px;color:var(--h-champagne);font-weight:600}
.amen-card p{font-size:12.5px;color:var(--h-muted);line-height:1.6;font-weight:300}

.quote-section{position:relative;z-index:1;padding:0 32px 120px;text-align:center}
.quote-section blockquote{max-width:720px;margin:0 auto;font-family:'Cormorant Garamond',serif;font-style:italic;font-size:clamp(24px,3vw,34px);line-height:1.5;color:var(--h-champagne);font-weight:500}
.quote-section blockquote::before{content:'"';color:var(--h-gold)}
.quote-section blockquote::after{content:'"';color:var(--h-gold)}
.quote-attr{margin-top:22px;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;color:var(--h-gold-dim)}

@media(max-width:900px){
    .suite-grid,.offer-grid{grid-template-columns:1fr}
    .amen-grid{grid-template-columns:1fr 1fr}
    .clock-wrap{grid-template-columns:1fr;padding:50px 24px}
    .tier-grid{grid-template-columns:1fr}
    .clock-point{width:88px}
    .clock-label{font-size:12.5px}
    .clock-note{display:none}
}
@media(max-width:640px){
    .hero{padding:80px 16px 60px}
    .amen-grid{grid-template-columns:1fr}
    .suites,.amenities,.offers,.tiers{padding-left:16px;padding-right:16px}
}
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>
<?php include 'topheader.php'; ?>

<section class="hero">
    <div class="hero-stars" id="heroStars"></div>
    <div class="hero-eyebrow">Manila Bay · 7-Star Hospitality</div>
    <h1>A Hôtel of<br><em>Uncommon Hours</em></h1>
    <p class="hero-sub">Seven stars are a rating. We built a hotel around the clock instead.</p>
    <div class="hero-loc">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        Nocturne Manila Bay
    </div>
    <div class="hero-cta">
        <a href="hotel-reserve.php" class="btn-gold">Reserve a Suite</a>
        <a href="hotel-services.php" class="btn-ghost">Spa, Shop &amp; Entertainment</a>
    </div>
    <?php if (!isHotelLoggedIn()): ?>
    <p style="position:relative;margin-top:18px;font-size:11.5px;color:var(--h-muted)">
        New here? <a href="register.php" style="color:var(--h-gold)">Create an account</a> — required to reserve or order, so we can apply your Regular or VIP rate.
    </p>
    <?php endif; ?>
</section>

<section class="philosophy">
    <p>Most hotels measure themselves in stars. We measure ourselves in hours — the ones other houses leave unattended. A request made at 3 a.m. is answered with the same composure as one made at 3 p.m. That is the whole of what we do, done without exception, for as long as you choose to stay.</p>
</section>

<section class="offers" id="offers">
    <div class="sec-head">
        <div><div class="sec-eyebrow">Limited-Time</div><h2>Vacation Offers</h2></div>
        <p><?= $tier === 'vip' ? 'As a Nocturne Noir member, VIP-exclusive offers are already applied at checkout.' : 'Sign in as a member to unlock additional VIP-only offers.' ?></p>
    </div>
    <div class="offer-grid">
        <?php foreach ($offers as $o): ?>
        <div class="offer-card">
            <img src="<?= htmlspecialchars($o['image_url']) ?>" alt="<?= htmlspecialchars($o['title']) ?>" loading="lazy">
            <div class="offer-body">
                <div class="offer-badges">
                    <?php if ($o['audience']==='vip'): ?><span class="badge-vip">VIP Only</span><?php endif; ?>
                    <?php if ($o['suite_name']): ?><span class="badge-cat"><?= htmlspecialchars($o['suite_name']) ?></span><?php endif; ?>
                    <span class="badge-offer">Min. <?= (int)$o['min_nights'] ?> night<?= $o['min_nights']>1?'s':'' ?></span>
                </div>
                <h4><?= htmlspecialchars($o['title']) ?></h4>
                <p><?= htmlspecialchars($o['description']) ?></p>
                <div class="offer-discount"><?= rtrim(rtrim(number_format($o['discount_percent'],1),'0'),'.') ?>% off</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="tiers">
    <div class="sec-head">
        <div><div class="sec-eyebrow">Membership</div><h2>Regular vs. Nocturne Noir</h2></div>
        <p>Every stay earns points toward Nocturne Noir — our VIP tier, with lower rates across rooms, spa, shop and entertainment.</p>
    </div>
    <div class="tier-grid">
        <?php foreach ($tiers as $t): ?>
        <div class="tier-card <?= $t['tier'] ?>">
            <h3><?= htmlspecialchars($t['label']) ?></h3>
            <div class="tier-sub"><?= $t['tier']==='vip' ? '₱'.number_format($t['min_lifetime_spend']).'+ lifetime spend' : 'Open to every registered guest' ?></div>
            <ul class="tier-perks"><?php foreach ($t['perks_arr'] as $perk): ?><li><?= htmlspecialchars($perk) ?></li><?php endforeach; ?></ul>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="suites" id="suites">
    <div class="sec-head">
        <div><div class="sec-eyebrow">Accommodations</div><h2>Three Residences</h2></div>
        <p>Each residence has its own rooms, its own spa, its own skincare shop, and its own entertainment lounge.</p>
    </div>
    <div class="suite-grid">
        <?php foreach ($suites as $i => $s):
            $price = $tier === 'vip' ? $s['vip_price'] : $s['base_price']; ?>
        <div class="suite-card">
            <div class="slider" data-slider>
                <div class="slider-track">
                    <?php foreach ($s['images'] as $img): ?>
                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($s['name']) ?>" loading="lazy">
                    <?php endforeach; ?>
                </div>
                <?php if (count($s['images']) > 1): ?>
                <button class="slider-arrow prev" aria-label="Previous photo"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg></button>
                <button class="slider-arrow next" aria-label="Next photo"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></button>
                <div class="slider-dots"><?php foreach ($s['images'] as $di=>$_): ?><span class="<?= $di===0?'active':'' ?>"></span><?php endforeach; ?></div>
                <?php endif; ?>
            </div>
            <div class="suite-body">
                <div class="suite-num">0<?= $i+1 ?> — Residence · <?= $s['room_count'] ?> rooms available</div>
                <h3><?= htmlspecialchars($s['name']) ?></h3>
                <div class="suite-meta"><span><?= (int)$s['size_sqm'] ?> sqm</span><span>·</span><span><?= htmlspecialchars($s['view_desc']) ?></span></div>
                <p class="suite-desc"><?= htmlspecialchars($s['description']) ?></p>
                <ul class="suite-features"><?php foreach ($s['features_arr'] as $f): ?><li><?= htmlspecialchars($f) ?></li><?php endforeach; ?></ul>
                <div class="suite-foot">
                    <div class="suite-price">₱<?= number_format($price) ?><span>per night<?= $tier==='vip' ? ' · VIP rate' : '' ?></span></div>
                    <a href="hotel-reserve.php?suite=<?= $s['slug'] ?>" class="btn-gold">Reserve</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="clock-section">
    <div class="clock-wrap">
        <div class="clock-intro">
            <div class="sec-eyebrow">Our Signature</div>
            <h2>The House Runs<br>on <em>Six Hours</em>, Not One</h2>
            <p>Every hotel has a check-in time. Nocturne has six moments it is built for — choose your own check-in window when you reserve.</p>
        </div>
        <div class="clock-face">
            <div class="clock-ring"></div>
            <div class="clock-center">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#cfa76b" stroke-width="1.2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>
                <span>Nocturne</span>
            </div>
            <?php foreach ($hours as $h): $p = $clockPos[$h['pos']]; ?>
            <div class="clock-point" style="top:<?= $p['top'] ?>;left:<?= $p['left'] ?>;">
                <div class="clock-dot"></div>
                <div class="clock-time"><?= $h['time'] ?></div>
                <div class="clock-label"><?= htmlspecialchars($h['label']) ?></div>
                <div class="clock-note"><?= htmlspecialchars($h['note']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="amenities">
    <div class="sec-head"><div><div class="sec-eyebrow">In the House</div><h2>Everything, Attended</h2></div>
        <p>Spa, skincare, and entertainment are unique to each residence — browse them per suite.</p></div>
    <div class="amen-grid">
        <div class="amen-card">
            <div class="amen-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M2 18c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0"/><path d="M2 13c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0"/><path d="M6 8V5a2 2 0 0 1 2-2h1"/></svg></div>
            <h4>Rooftop Infinity Pool</h4><p>Heated to 30°C year-round, unbroken sightline to the bay after dark.</p>
        </div>
        <div class="amen-card">
            <div class="amen-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M12 2C8 6 6 9 6 13a6 6 0 0 0 12 0c0-4-2-7-6-11z"/></svg></div>
            <h4>Spa, Per Residence</h4><p>Each suite has its own treatment menu. <a href="hotel-services.php" style="color:var(--h-gold)">Book a session →</a></p>
        </div>
        <div class="amen-card">
            <div class="amen-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M20 21a8 8 0 1 0-16 0"/><circle cx="12" cy="9" r="4"/></svg></div>
            <h4>Skincare Shop — Men &amp; Women</h4><p>In-house formulas, split by residence. <a href="hotel-services.php" style="color:var(--h-gold)">Browse the shop →</a></p>
        </div>
        <div class="amen-card">
            <div class="amen-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><circle cx="12" cy="12" r="9"/><circle cx="9" cy="9" r="1"/><circle cx="15" cy="9" r="1"/><circle cx="9" cy="15" r="1"/><circle cx="15" cy="15" r="1"/></svg></div>
            <h4>Casino &amp; Billiards</h4><p>Private rooms per residence, billed hourly or per session.</p>
        </div>
        <div class="amen-card">
            <div class="amen-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M8 21h8M12 17v4M6 3h12l-1 9a5 5 0 0 1-10 0z"/></svg></div>
            <h4>In-Room Minibar</h4><p>A distinct minibar selection curated for each residence.</p>
        </div>
        <div class="amen-card">
            <div class="amen-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>
            <h4>Personal Butler</h4><p>Assigned on arrival, reachable by name, not by extension.</p>
        </div>
    </div>
</section>

<section class="quote-section">
    <blockquote>We didn't stay at Nocturne so much as we were kept — the way a good house keeps time.</blockquote>
    <div class="quote-attr">Condé Nast Traveler, Fictional Review</div>
</section>

<footer><p>© 2026 <span>Nocturne Manila Bay</span> — A Property of AyosCoffeeNegosyo Hospitality.</p></footer>

<script>
const starsWrap = document.getElementById('heroStars');
for (let i = 0; i < 40; i++) {
    const s = document.createElement('span');
    s.style.top = Math.random()*100 + '%';
    s.style.left = Math.random()*100 + '%';
    s.style.animationDelay = (Math.random()*4) + 's';
    starsWrap.appendChild(s);
}
document.querySelectorAll('[data-slider]').forEach(slider => {
    const track = slider.querySelector('.slider-track');
    const slides = track.children.length;
    if (slides <= 1) return;
    let idx = 0;
    const dots = slider.querySelectorAll('.slider-dots span');
    function go(n){ idx = (n + slides) % slides; track.style.transform = `translateX(-${idx*100}%)`; dots.forEach((d,i)=>d.classList.toggle('active', i===idx)); }
    slider.querySelector('.slider-arrow.prev')?.addEventListener('click', ()=>go(idx-1));
    slider.querySelector('.slider-arrow.next')?.addEventListener('click', ()=>go(idx+1));
    dots.forEach((d,i)=>d.addEventListener('click', ()=>go(i)));
    let auto = setInterval(()=>go(idx+1), 5000);
    slider.addEventListener('mouseenter', ()=>clearInterval(auto));
    slider.addEventListener('mouseleave', ()=>{ auto = setInterval(()=>go(idx+1), 5000); });
});
</script>
</body>
</html>