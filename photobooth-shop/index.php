<?php
$extra_css = ['shop.css'];
require_once __DIR__ . '/includes/header.php';

$photoTypes = get_photo_types();
$designs = get_carousel_designs();
?>

<section class="hero-shop">
    <div class="container hero-grid">
        <div class="reveal">
            <span class="hero-eyebrow">✨ Now open — walk-ins welcome</span>
            <h1 class="hero-title">Strike a pose,<br><span class="hi">get cute,</span><br>take it <span class="hi2">home.</span></h1>
            <p class="hero-sub">Kislap Booth is your cute corner photobooth shop — solo, duo, couple, or the whole barkada. Smile at the camera, pick your dream frame, and print your memory in minutes.</p>
            <div class="hero-actions">
                <a href="#sessions" class="btn-pill btn-pill-filled btn-pill-lg">Book a Session ✨</a>
                <a href="#how" class="btn-pill btn-pill-ghost btn-pill-lg">See How it Works</a>
            </div>
            <div class="hero-stats">
                <div><strong>5</strong><span>Session Types</span></div>
                <div><strong>5</strong><span>Cute Frames</span></div>
                <div><strong>3 min</strong><span>Avg. Session</span></div>
            </div>
        </div>
        <div class="hero-photostrip-wrap reveal reveal-delay-1">
            <div class="hero-flash-ring"></div>
            <div class="photostrip photostrip-2">
                <div class="frame"><img src="https://images.unsplash.com/photo-1529626455594-4ff0802cfb7e?q=80&w=400&auto=format&fit=crop" alt=""></div>
                <div class="frame"><img src="https://images.unsplash.com/photo-1521119989659-a83eee488004?q=80&w=400&auto=format&fit=crop" alt=""></div>
            </div>
            <div class="photostrip">
                <div class="frame"><img src="https://images.unsplash.com/photo-1524504388940-b1c1722653e1?q=80&w=400&auto=format&fit=crop" alt=""></div>
                <div class="frame"><img src="https://images.unsplash.com/photo-1517841905240-472988babdf9?q=80&w=400&auto=format&fit=crop" alt=""></div>
                <span class="strip-caption">Kislap Booth ✨</span>
            </div>
            <div class="photostrip photostrip-3">
                <div class="frame"><img src="https://images.unsplash.com/photo-1543269865-cbf427effbad?q=80&w=400&auto=format&fit=crop" alt=""></div>
            </div>
            <div class="hero-cam-badge">📸</div>
        </div>
    </div>
</section>

<div class="tape-strip">
    <div class="tape-track">
        <span>✦ SOLO</span><span>✦ DUO</span><span>✦ COUPLE</span><span>✦ GROUP</span><span>✦ BARKADA</span><span>✦ CASH · GCASH · CARD</span>
        <span>✦ SOLO</span><span>✦ DUO</span><span>✦ COUPLE</span><span>✦ GROUP</span><span>✦ BARKADA</span><span>✦ CASH · GCASH · CARD</span>
    </div>
</div>

<!-- SESSIONS -->
<section class="section-cute" id="sessions">
    <div class="container">
        <div class="section-head-cute reveal">
            <span class="eyebrow-cute">Choose your vibe</span>
            <h2>Pick a session type</h2>
            <p>Every session includes your captured shots plus a free classic photo strip design.</p>
        </div>
        <div class="session-grid">
            <?php foreach ($photoTypes as $i => $t): ?>
            <div class="session-card reveal reveal-delay-<?= min($i % 3 + 1, 3) ?>" data-tilt>
                <div class="session-icon" style="background: <?= e($t['accent_color']) ?>22;"><?= e($t['icon_emoji']) ?></div>
                <h3><?= e($t['name']) ?></h3>
                <p><?= e($t['description']) ?></p>
                <div class="session-price"><?= CURRENCY_SYMBOL . number_format($t['base_price'], 0) ?></div>
                <div class="session-meta"><?= (int)$t['shots_required'] ?> shots · up to <?= (int)$t['max_people'] ?> pax</div>
                <a href="<?= SITE_URL ?>/booth.php?type=<?= e($t['slug']) ?>" class="btn-pill btn-pill-filled btn-pill-block">Book This ✨</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="section-cute" id="how" style="background: var(--lilac); border-radius: 48px;">
    <div class="container">
        <div class="section-head-cute reveal">
            <span class="eyebrow-cute">Easy peasy</span>
            <h2>How Kislap Booth works</h2>
            <p>From walk-in to walk-out with a cute keepsake, in four simple steps.</p>
        </div>
        <div class="steps-grid">
            <div class="step-card reveal reveal-delay-1">
                <div class="step-num">1</div>
                <h4>Pick a Session</h4>
                <p>Solo, duo, couple, group, or barkada — choose what fits your squad.</p>
            </div>
            <div class="step-card reveal reveal-delay-2">
                <div class="step-num">2</div>
                <h4>Strike a Pose</h4>
                <p>Our camera detects your face, counts you down, and captures your cutest angles.</p>
            </div>
            <div class="step-card reveal reveal-delay-3">
                <div class="step-num">3</div>
                <h4>Pick a Frame</h4>
                <p>Choose a cute carousel design to turn your shots into a keepsake strip.</p>
            </div>
            <div class="step-card reveal">
                <div class="step-num">4</div>
                <h4>Pay & Go</h4>
                <p>Cash, GCash, or card — pay your way and take your printed memory home.</p>
            </div>
        </div>
    </div>
</section>

<!-- DESIGNS -->
<section class="section-cute" id="designs">
    <div class="container">
        <div class="section-head-cute reveal">
            <span class="eyebrow-cute">Frame it cute</span>
            <h2>Carousel frame designs</h2>
            <p>Classic is always free — upgrade to a themed frame for a little extra sparkle.</p>
        </div>
        <div class="design-grid">
            <?php foreach ($designs as $i => $d): ?>
            <div class="design-swatch reveal reveal-delay-<?= min($i % 3 + 1, 3) ?>">
                <div class="design-swatch-preview" style="background: <?= e($d['bg_color']) ?>;">
                    <?php
                        $stickerIcons = ['sakura' => '🌸','bear' => '🐻','stars' => '⭐','sparkle' => '✨','none' => '🖼️'];
                        echo $stickerIcons[$d['sticker_set']] ?? '🖼️';
                    ?>
                </div>
                <div class="design-swatch-body">
                    <h5><?= e($d['name']) ?></h5>
                    <span><?= e($d['description']) ?></span>
                    <span class="fee"><?= $d['extra_fee'] > 0 ? '+' . CURRENCY_SYMBOL . number_format($d['extra_fee'], 0) : 'Free' ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>