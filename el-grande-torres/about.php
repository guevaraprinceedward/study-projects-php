<?php
/**
 * about.php — The Atelier Noir
 *
 * Requires: includes/testimonial-functions.php, includes/team-data.php,
 *           database/about_testimonials.sql (+ about_testimonials_fix.sql)
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/testimonial-functions.php';

$page_title = "About — The Atelier Noir";
$meta_description = "Crafted with intention. Defined by you. The story, philosophy, and team behind The Atelier Noir.";
require_once __DIR__ . '/includes/header.php';

$testimonials = get_about_testimonials();

$roster   = require __DIR__ . '/includes/team-data.php';
$founders = $roster['founders'];
$team     = $roster['team'];

/** A small stylized laurel wreath, reused as a divider / brand mark. */
function noir_laurel(int $w = 96, int $h = 48): string {
    return '<svg width="' . $w . '" height="' . $h . '" viewBox="0 0 120 60" fill="none" aria-hidden="true">
        <path d="M60 55 C 30 55, 10 40, 8 15" stroke="var(--gold)" stroke-width="1.1" opacity="0.85"/>
        <path d="M60 55 C 90 55, 110 40, 112 15" stroke="var(--gold)" stroke-width="1.1" opacity="0.85"/>
        <g fill="var(--gold)">
            <ellipse cx="15" cy="18" rx="5" ry="2.3" transform="rotate(-35 15 18)"/>
            <ellipse cx="19" cy="27" rx="5" ry="2.3" transform="rotate(-22 19 27)"/>
            <ellipse cx="26" cy="36" rx="5" ry="2.3" transform="rotate(-10 26 36)"/>
            <ellipse cx="36" cy="44" rx="5" ry="2.3" transform="rotate(2 36 44)"/>
            <ellipse cx="48" cy="50" rx="5" ry="2.3" transform="rotate(12 48 50)"/>
            <ellipse cx="105" cy="18" rx="5" ry="2.3" transform="rotate(35 105 18)"/>
            <ellipse cx="101" cy="27" rx="5" ry="2.3" transform="rotate(22 101 27)"/>
            <ellipse cx="94" cy="36" rx="5" ry="2.3" transform="rotate(10 94 36)"/>
            <ellipse cx="84" cy="44" rx="5" ry="2.3" transform="rotate(-2 84 44)"/>
            <ellipse cx="72" cy="50" rx="5" ry="2.3" transform="rotate(-12 72 50)"/>
        </g>
    </svg>';
}

/** A simple line-art Doric column, used as hero decoration. */
function noir_column(int $w = 60, int $h = 260): string {
    return '<svg width="' . $w . '" height="' . $h . '" viewBox="0 0 60 260" fill="none" aria-hidden="true">
        <rect x="6" y="8" width="48" height="10" stroke="var(--gold)" stroke-width="1"/>
        <rect x="12" y="18" width="36" height="6" stroke="var(--gold)" stroke-width="1"/>
        <line x1="16" y1="24" x2="16" y2="230" stroke="var(--gold)" stroke-width="1"/>
        <line x1="24" y1="24" x2="24" y2="230" stroke="var(--gold)" stroke-width="1"/>
        <line x1="30" y1="24" x2="30" y2="230" stroke="var(--gold)" stroke-width="1"/>
        <line x1="36" y1="24" x2="36" y2="230" stroke="var(--gold)" stroke-width="1"/>
        <line x1="44" y1="24" x2="44" y2="230" stroke="var(--gold)" stroke-width="1"/>
        <rect x="12" y="230" width="36" height="6" stroke="var(--gold)" stroke-width="1"/>
        <rect x="4" y="236" width="52" height="10" stroke="var(--gold)" stroke-width="1"/>
    </svg>';
}

/** One face of the 3D coin motif — the page's signature element. */
function noir_coin_face(string $glyph): string {
    return '<svg viewBox="0 0 120 120" width="120" height="120" aria-hidden="true">
        <defs>
            <radialGradient id="coinGrad-' . $glyph . '" cx="35%" cy="30%" r="75%">
                <stop offset="0%" stop-color="rgba(200,169,106,0.30)"/>
                <stop offset="100%" stop-color="rgba(200,169,106,0.03)"/>
            </radialGradient>
        </defs>
        <circle cx="60" cy="60" r="57" fill="url(#coinGrad-' . $glyph . ')" stroke="var(--gold)" stroke-width="1.4"/>
        <circle cx="60" cy="60" r="47" fill="none" stroke="var(--gold)" stroke-width="0.7" opacity="0.55"/>
        <text x="60" y="77" text-anchor="middle" font-family="Cinzel, serif" font-size="44" fill="var(--gold)">' . $glyph . '</text>
    </svg>';
}

/**
 * Truncates a testimonial quote for card display so it can never overflow
 * a fixed-height .testi-card, regardless of how long the source text in
 * `about_testimonials.quote_text` is. The FULL text still shows in the
 * read-more modal via the card's data-full attribute.
 */
function noir_excerpt(string $text, int $maxLen = 170): string {
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if (mb_strlen($text) <= $maxLen) return $text;
    return rtrim(mb_substr($text, 0, $maxLen)) . '…';
}

/** Short display date for the testimonial card footer, e.g. "Jul 2, 2026". */
function noir_testi_date(?string $createdAt): string {
    if (!$createdAt) return '';
    $ts = strtotime($createdAt);
    return $ts ? date('M j, Y', $ts) : '';
}
?>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
.noir-page{ --noir-line:rgba(200,169,106,.22); --font-greek:'Cinzel','Cormorant Garamond',serif; --font-serif:'Cormorant Garamond',serif; --font-body:'Jost',sans-serif; font-family:var(--font-body); font-weight:300; overflow-x:hidden; }
.noir-page h1,.noir-page h2,.noir-page h3{ font-family:var(--font-greek); font-weight:600; letter-spacing:.01em; }
.noir-kicker{ display:inline-flex; align-items:center; gap:12px; font-family:var(--font-body); font-size:11px; letter-spacing:.34em; text-transform:uppercase; color:var(--gold); margin-bottom:18px; }
.noir-kicker::before{ content:''; width:30px; height:1px; background:var(--gold); }
.noir-kicker.center{ justify-content:center; }
.noir-h2{ font-size:clamp(28px,4vw,46px); color:var(--text-primary); line-height:1.12; margin-bottom:1.4rem; }
.noir-h2 em{ font-style:italic; color:var(--gold); font-weight:500; font-family:var(--font-serif); }
.noir-h2.center{ text-align:center; }
.center{ text-align:center; margin-left:auto; margin-right:auto; }

@media (prefers-reduced-motion: reduce){
    .noir-page *, .noir-page *::before, .noir-page *::after{
        animation-duration:.001ms !important; animation-iteration-count:1 !important;
        transition-duration:.001ms !important; scroll-behavior:auto !important;
    }
}

.greek-key-divider{ height:22px; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='20' viewBox='0 0 40 20'%3E%3Cpath d='M0 15 H10 V5 H20 V15 H30 V5 H40' fill='none' stroke='%23C8A96A' stroke-width='1.1' opacity='0.4'/%3E%3C/svg%3E"); background-repeat:repeat-x; background-position:center; opacity:.8; }

.noir-hero{ position:relative; padding:calc(var(--nav-height) + 70px) 5vw 90px; overflow:hidden; background:radial-gradient(ellipse at 50% -10%, rgba(200,169,106,0.07), transparent 55%); }
.noir-hero-inner{ position:relative; z-index:2; max-width:760px; margin:0 auto; text-align:center; }
.laurel-mark{ display:flex; justify-content:center; margin-bottom:1.6rem; animation:laurelPulse 6s ease-in-out infinite; transform-style:preserve-3d; }
@keyframes laurelPulse{ 0%,100%{ transform:rotateY(0deg) translateY(0); } 50%{ transform:rotateY(8deg) translateY(-4px); } }
.noir-hero-title{ font-size:clamp(2.4rem,6vw,4.4rem); line-height:1.08; margin-bottom:1.6rem; color:var(--text-primary); }
.noir-hero-title em{ font-style:italic; color:var(--gold); font-weight:500; font-family:var(--font-serif); }
.noir-hero-lede{ font-family:var(--font-serif); font-size:1.15rem; line-height:1.85; color:var(--text-secondary); max-width:620px; margin:0 auto 1.4rem; }
.noir-hero-cta{ display:flex; justify-content:center; gap:1rem; flex-wrap:wrap; margin-top:2.2rem; }

.noir-columns{ position:absolute; top:120px; bottom:0; width:60px; opacity:.35; pointer-events:none; z-index:1; animation:columnFloat 9s ease-in-out infinite; transform-style:preserve-3d; }
.noir-columns-left{ left:3vw; animation-delay:0s; }
.noir-columns-right{ right:3vw; animation-delay:1.2s; }
@keyframes columnFloat{ 0%,100%{ transform:translateY(0) rotateY(0deg); } 50%{ transform:translateY(-14px) rotateY(6deg); } }
@media(max-width:1100px){ .noir-columns{ display:none; } }

.reveal-3d{ opacity:0; transform:perspective(1000px) translateY(46px) rotateX(8deg); transition:opacity 1s cubic-bezier(.2,.7,.2,1), transform 1s cubic-bezier(.2,.7,.2,1); }
.reveal-3d.in{ opacity:1; transform:perspective(1000px) translateY(0) rotateX(0deg); }

.noir-section{ padding:6.5rem 0; position:relative; }

.essentials-grid{ display:grid; grid-template-columns:1.1fr 0.9fr; gap:4.5rem; align-items:center; }
.essentials-copy p{ color:var(--text-secondary); font-size:.98rem; line-height:1.9; margin-bottom:1.3rem; }
.tilt-card{ transform-style:preserve-3d; transition:transform .25s cubic-bezier(.2,.7,.3,1); will-change:transform; }
.essentials-visual{ position:relative; aspect-ratio:4/5; border:1px solid var(--border-color); background:linear-gradient(160deg, rgba(200,169,106,0.07), var(--bg-card) 70%); display:flex; align-items:center; justify-content:center; }
.essentials-visual::before{ content:''; position:absolute; inset:16px; border:1px solid var(--noir-line); pointer-events:none; }
.essentials-visual svg{ transform:translateZ(30px); }
@media(max-width:900px){ .essentials-grid{ grid-template-columns:1fr; gap:2.5rem; } }

/* ---- Philosophy / signature 3D coin ---- */
.defines-section{ text-align:center; }
.defines-wrap{ max-width:760px; margin:0 auto; }
.coin-wrap{ perspective:900px; display:flex; justify-content:center; margin-bottom:1.6rem; }
.coin-3d{ position:relative; width:120px; height:120px; transform-style:preserve-3d; animation:coinSpin 11s linear infinite; }
.coin-face{ position:absolute; inset:0; backface-visibility:hidden; -webkit-backface-visibility:hidden; border-radius:50%; }
.coin-back{ transform:rotateY(180deg); }
@keyframes coinSpin{ from{ transform:rotateY(0deg); } to{ transform:rotateY(360deg); } }
.defines-quote{ font-family:var(--font-serif); font-style:italic; font-size:1.3rem; line-height:1.75; color:var(--text-primary); margin-bottom:1.6rem; }

.team-intro{ font-family:var(--font-serif); font-size:1.05rem; line-height:1.85; color:var(--text-secondary); max-width:640px; margin:0 auto 3.5rem; }
.founders-row{ display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2.5rem; }

/* ---- Team / founder cards: 3D tilt + hover skill reveal ---- */
.founder-card, .team-card{ position:relative; overflow:hidden; background:var(--bg-card); border:1px solid var(--border-color); padding:2.4rem 2.2rem; transition:border-color .35s ease; cursor:pointer; }
.founder-card:hover, .team-card:hover{ border-color:rgba(200,169,106,0.45); }
.member-avatar{ width:100%; aspect-ratio:1/1; max-height:280px; border-radius:10px; border:1px solid var(--noir-line); background:linear-gradient(160deg, rgba(200,169,106,0.16), rgba(200,169,106,0.03)); color:var(--gold); display:flex; align-items:center; justify-content:center; font-family:var(--font-greek); font-size:2rem; margin-bottom:1.4rem; transform:translateZ(20px); overflow:hidden; flex-shrink:0; }
.member-avatar img{ width:100%; height:100%; object-fit:cover; border-radius:10px; display:block; }
.founder-card h3, .team-card h3{ font-size:1.25rem; color:var(--text-primary); margin-bottom:.4rem; }
.founder-card .role, .team-card .role{ font-size:.72rem; letter-spacing:.1em; text-transform:uppercase; color:var(--gold); margin-bottom:1rem; }
.founder-card .bio, .team-card .bio{ font-size:.88rem; line-height:1.8; color:var(--text-secondary); }

.skills-overlay{
    position:absolute; inset:0; z-index:3; padding:2rem 2.1rem;
    display:flex; flex-direction:column; justify-content:center; gap:1rem;
    background:linear-gradient(165deg, rgba(10,9,8,0.97), rgba(20,17,13,0.97));
    border-top:1px solid var(--gold);
    transform:perspective(700px) rotateX(-12deg) translateY(100%);
    transform-origin:bottom center;
    opacity:0;
    transition:transform .4s cubic-bezier(.2,.7,.2,1), opacity .4s ease;
    pointer-events:none;
}
.founder-card:hover .skills-overlay, .team-card:hover .skills-overlay,
.founder-card.skills-open .skills-overlay, .team-card.skills-open .skills-overlay{
    transform:perspective(700px) rotateX(0deg) translateY(0%); opacity:1; pointer-events:auto;
}
.skills-label{ font-size:.68rem; letter-spacing:.24em; text-transform:uppercase; color:var(--gold); margin:0; }
.skills-chips{ display:flex; flex-wrap:wrap; gap:.5rem; }
.skill-chip{ font-size:.72rem; letter-spacing:.03em; color:var(--text-primary); border:1px solid var(--noir-line); padding:.35rem .7rem; }
.view-more-btn{ align-self:flex-start; margin-top:.4rem; font-size:.72rem; letter-spacing:.14em; text-transform:uppercase; color:var(--gold); text-decoration:none; border-bottom:1px solid var(--gold); padding-bottom:2px; transition:opacity .2s ease; }
.view-more-btn:hover{ opacity:.7; }

.team-grid{ display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:1.6rem; }
@media(max-width:992px){ .founders-row{ grid-template-columns:1fr; } .team-grid{ grid-template-columns:repeat(2, minmax(0,1fr)); } }
@media(max-width:560px){ .team-grid{ grid-template-columns:1fr; } }

/* ---- What Drives Us Forward ---- */
.drives-section{ text-align:center; padding:7rem 5vw; }
.drives-wrap{ max-width:680px; margin:0 auto; }
.drives-lede{ font-size:.98rem; line-height:1.9; color:var(--text-secondary); margin-bottom:2.4rem; }
.drives-quote{ margin:0; padding:2.2rem 2rem 1.8rem; border-top:1px solid var(--noir-line); border-bottom:1px solid var(--noir-line); }
.drives-quote-mark{ display:block; font-family:var(--font-serif); font-size:3rem; line-height:1; color:var(--gold); margin-bottom:.4rem; }
.drives-quote p{ font-family:var(--font-serif); font-style:italic; font-size:1.35rem; line-height:1.7; color:var(--text-primary); margin-bottom:1rem; }
.drives-quote footer{ font-size:.78rem; letter-spacing:.06em; color:var(--gold); }

.testimonial-section .section-head{ margin-bottom:1.4rem; }
.marquee-row-wrap{ display:flex; flex-direction:column; gap:1.6rem; margin-top:2.4rem; }
.marquee-row{ overflow:hidden; position:relative; -webkit-mask-image:linear-gradient(90deg, transparent, #000 6%, #000 94%, transparent); mask-image:linear-gradient(90deg, transparent, #000 6%, #000 94%, transparent); }
.marquee-track{ display:flex; align-items:stretch; gap:1.4rem; width:max-content; animation:marqueeLeft 42s linear infinite; transform:translateZ(0); will-change:transform; }
.marquee-track.reverse{ animation-name:marqueeRight; animation-duration:48s; }
.marquee-row:hover .marquee-track{ animation-play-state:paused; }
@keyframes marqueeLeft{ from{ transform:translateX(0) translateZ(0); } to{ transform:translateX(-50%) translateZ(0); } }
@keyframes marqueeRight{ from{ transform:translateX(-50%) translateZ(0); } to{ transform:translateX(0) translateZ(0); } }

/* ---- Testimonial card ----
   Restyled to match the reference layout: stars top-left, a subtle
   expand hint top-right that only appears on hover (keeps the card
   clean at rest since the whole card is already clickable — no
   permanent "Read Full Testimonial" line competing with the quote),
   a thin divider before the footer, and the footer itself laid out
   as [avatar] [name / branch-with-dot] ... [date] on the right.
   backface-visibility + translateZ(0) are intentionally left OFF this
   rule (only the marquee-track itself uses translateZ(0)) — with ~56
   of these animating at once, that combo is what caused Chrome to
   ghost/overlap text from neighboring cards in an earlier version. */
.testi-card{
    flex:0 0 auto; width:320px; height:225px;
    display:flex; flex-direction:column; overflow:hidden;
    background:var(--bg-card); border:1px solid var(--border-color); padding:1.8rem 1.9rem;
    transform-style:preserve-3d;
    transition:transform .25s cubic-bezier(.2,.7,.3,1), border-color .3s ease;
    cursor:pointer;
}
.testi-card:hover, .testi-card:focus-visible{ border-color:rgba(200,169,106,0.45); outline:none; }
.testi-card-top{ display:flex; align-items:flex-start; justify-content:space-between; gap:.6rem; margin-bottom:.9rem; flex-shrink:0; }
.testi-stars{ color:var(--gold); font-size:.82rem; letter-spacing:.18em; }
.testi-expand-hint{ color:var(--gold); font-size:1rem; line-height:1; opacity:0; transform:translate(-3px, 3px); transition:opacity .25s ease, transform .25s ease; flex-shrink:0; }
.testi-card:hover .testi-expand-hint{ opacity:1; transform:translate(0,0); }
.testi-quote{
    flex:1; overflow:hidden; display:-webkit-box; -webkit-line-clamp:5; -webkit-box-orient:vertical;
    font-family:var(--font-serif); font-size:1rem; line-height:1.7; color:var(--text-primary); margin-bottom:1.1rem;
    word-wrap:break-word; overflow-wrap:break-word;
}
.testi-divider{ height:1px; background:var(--noir-line); margin-bottom:1.1rem; flex-shrink:0; }
.testi-foot{ display:flex; align-items:center; gap:.8rem; flex-shrink:0; }
.testi-avatar{ width:38px; height:38px; border-radius:50%; border:1px solid var(--gold); color:var(--gold); display:flex; align-items:center; justify-content:center; font-family:var(--font-greek); font-size:.78rem; flex-shrink:0; overflow:hidden; }
.testi-avatar img{ width:100%; height:100%; object-fit:cover; border-radius:50%; }
.testi-meta{ flex:1; min-width:0; }
.testi-name{ font-size:.85rem; color:var(--text-primary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.testi-branch{ font-size:.72rem; color:var(--text-secondary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:flex; align-items:center; gap:.4rem; margin-top:.15rem; }
.testi-branch-dot{ width:6px; height:6px; border-radius:50%; background:var(--gold); flex-shrink:0; }
.testi-date{ font-size:.68rem; color:var(--text-secondary); flex-shrink:0; white-space:nowrap; }

/* ---- Testimonial modal ---- */
.noir-modal{ position:fixed; inset:0; z-index:300; display:flex; align-items:center; justify-content:center; padding:5vw; visibility:hidden; opacity:0; transition:opacity .3s ease, visibility 0s linear .3s; }
.noir-modal.open{ visibility:visible; opacity:1; transition:opacity .3s ease; }
.noir-modal-backdrop{ position:absolute; inset:0; background:rgba(6,5,4,0.85); backdrop-filter:blur(3px); }
.noir-modal-panel{ position:relative; max-width:560px; width:100%; background:var(--bg-card); border:1px solid rgba(200,169,106,0.45); padding:2.6rem 2.4rem; transform:perspective(1000px) rotateX(8deg) translateY(20px) scale(.96); opacity:0; transition:transform .35s cubic-bezier(.2,.7,.2,1), opacity .35s ease; }
.noir-modal.open .noir-modal-panel{ transform:perspective(1000px) rotateX(0) translateY(0) scale(1); opacity:1; }
.noir-modal-close{ position:absolute; top:14px; right:16px; width:32px; height:32px; background:none; border:none; color:var(--gold); font-size:1.6rem; line-height:1; cursor:pointer; }
.noir-modal-stars{ color:var(--gold); font-size:.9rem; letter-spacing:.2em; margin-bottom:1rem; }
.noir-modal-quote{ font-family:var(--font-serif); font-style:italic; font-size:1.2rem; line-height:1.85; color:var(--text-primary); margin-bottom:1.8rem; }
.noir-modal-foot{ display:flex; align-items:center; gap:.9rem; border-top:1px solid var(--noir-line); padding-top:1.2rem; }

@media(max-width:768px){ .noir-section{ padding:4.5rem 0; } .noir-hero{ padding:calc(var(--nav-height) + 40px) 5vw 60px; } }
</style>

<main class="noir-page">

<!-- ══════════ HERO ══════════ -->
<section class="noir-hero">
    <div class="noir-columns noir-columns-left"><?= noir_column() ?></div>
    <div class="noir-columns noir-columns-right"><?= noir_column() ?></div>
    <div class="noir-hero-inner">
        <div class="laurel-mark"><?= noir_laurel(110, 54) ?></div>
        <p class="noir-kicker center">The Atelier Noir</p>
        <h1 class="noir-hero-title">Crafted With <em>Intention.</em><br>Defined By You.</h1>
        <p class="noir-hero-lede">At The Atelier Noir, fashion is more than what you wear&mdash;it is an expression of identity, confidence, and individuality. We create refined clothing and everyday essentials designed to complement different lifestyles, while bringing the experience of discovering and choosing fashion into a seamless digital platform.</p>
        <p class="noir-hero-lede">Our online experience is thoughtfully built to make every interaction effortless&mdash;from exploring collections and discovering essentials to viewing product details and making confident purchasing decisions. Every element is designed with the same attention to detail, sophistication, and individuality that defines The Atelier Noir.</p>
        <div class="noir-hero-cta">
            <a href="<?= SITE_URL ?>/clothing.php" class="btn-lux btn-lux-filled">Explore the Collection</a>
            <a href="#team" class="btn-lux btn-lux-ghost">Meet the Team</a>
        </div>
    </div>
</section>
<div class="greek-key-divider"></div>

<!-- ══════════ ESSENTIALS MADE FOR YOUR EVERYDAY ══════════ -->
<section class="noir-section essentials-section reveal-3d">
    <div class="container-lux essentials-grid">
        <div class="essentials-copy">
            <span class="noir-kicker">01 &mdash; The Collection</span>
            <h2 class="noir-h2">Essentials Made<br>For Your Everyday</h2>
            <p>We offer carefully selected clothing and essentials created for individuals who value quality, versatility, and timeless style. From statement pieces to refined everyday essentials, each product is chosen to become a meaningful part of your wardrobe rather than simply another purchase.</p>
            <p>The Atelier Noir brings these pieces closer to our clients through a digital storefront designed around convenience and discovery. With organized collections, intuitive navigation, detailed product presentation, and a seamless shopping experience, finding something that reflects your personal style becomes effortless.</p>
        </div>
        <div class="essentials-visual tilt-card">
            <?= noir_laurel(160, 80) ?>
        </div>
    </div>
</section>
<div class="greek-key-divider"></div>

<!-- ══════════ WHAT DEFINES US (signature 3D coin) ══════════ -->
<section class="noir-section defines-section reveal-3d">
    <div class="container-lux defines-wrap">
        <div class="coin-wrap">
            <div class="coin-3d">
                <div class="coin-face coin-front"><?= noir_coin_face('AN') ?></div>
                <div class="coin-face coin-back"><?= noir_coin_face('Ω') ?></div>
            </div>
        </div>
        <span class="noir-kicker center">02 &mdash; Our Philosophy</span>
        <h2 class="noir-h2 center">What Defines Us</h2>
        <p class="defines-quote">"We believe fashion should feel personal. Our purpose is to bring together thoughtful design, refined essentials, and a digital experience that allows every individual to discover pieces that genuinely represent who they are."</p>
        <p class="defines-quote">"We are driven by creativity, attention to detail, and the belief that simplicity can create lasting impact. From the way a garment is presented to the way a client experiences our platform, every detail is intentionally crafted to build something timeless, distinctive, and worthy of being remembered."</p>
    </div>
</section>
<div class="greek-key-divider"></div>

<!-- ══════════ OUR TEAM ══════════ -->
<section class="noir-section team-section reveal-3d" id="team">
    <div class="container-lux">
        <div class="section-head centered">
            <span class="noir-kicker center">03 &mdash; The People</span>
            <h2 class="noir-h2 center">Our Team</h2>
            <p class="team-intro">Behind The Atelier Noir is a multidisciplinary team bringing together fashion, design, technology, marketing, product analysis, and communication. Hover a card to see each member's expertise, or view their full profile.</p>
        </div>

        <div class="founders-row">
            <?php foreach ($founders as $f): ?>
            <article class="founder-card tilt-card">
                <div class="member-avatar">
                    <?php if (!empty($f['image'])): ?>
                        <img src="<?= e($f['image']) ?>" alt="" loading="lazy" data-fallback="<?= e($f['initials']) ?>">
                    <?php else: ?>
                        <?= e($f['initials']) ?>
                    <?php endif; ?>
                </div>
                <h3><?= $f['name'] ?></h3>
                <p class="role"><?= $f['role'] ?></p>
                <p class="bio"><?= $f['bio'] ?></p>
                <div class="skills-overlay">
                    <p class="skills-label">Expertise</p>
                    <div class="skills-chips">
                        <?php foreach ($f['skills'] as $skill): ?>
                            <span class="skill-chip"><?= $skill ?></span>
                        <?php endforeach; ?>
                    </div>
                    <a class="view-more-btn" href="<?= SITE_URL ?>/team-member.php?slug=<?= urlencode($f['slug']) ?>">View More &rarr;</a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <div class="team-grid">
            <?php foreach ($team as $m): ?>
            <article class="team-card tilt-card">
                <div class="member-avatar">
                    <?php if (!empty($m['image'])): ?>
                        <img src="<?= e($m['image']) ?>" alt="" loading="lazy" data-fallback="<?= e($m['initials']) ?>">
                    <?php else: ?>
                        <?= e($m['initials']) ?>
                    <?php endif; ?>
                </div>
                <h3><?= $m['name'] ?></h3>
                <p class="role"><?= $m['role'] ?></p>
                <p class="bio"><?= $m['bio'] ?></p>
                <div class="skills-overlay">
                    <p class="skills-label">Expertise</p>
                    <div class="skills-chips">
                        <?php foreach ($m['skills'] as $skill): ?>
                            <span class="skill-chip"><?= $skill ?></span>
                        <?php endforeach; ?>
                    </div>
                    <a class="view-more-btn" href="<?= SITE_URL ?>/team-member.php?slug=<?= urlencode($m['slug']) ?>">View More &rarr;</a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<div class="greek-key-divider"></div>

<!-- ══════════ WHAT DRIVES US FORWARD ══════════ -->
<section class="noir-section drives-section reveal-3d">
    <div class="drives-wrap">
        <span class="noir-kicker center">04 &mdash; Our Purpose</span>
        <h2 class="noir-h2 center">What Drives Us <em>Forward</em></h2>
        <p class="drives-lede">Every collection, every line of code, and every client interaction is measured against the same question&mdash;does this feel like The Atelier Noir. That standard is what our clients notice first, and it is what keeps our team, across every discipline, moving in the same direction.</p>
        <blockquote class="drives-quote">
            <span class="drives-quote-mark" aria-hidden="true">&ldquo;</span>
            <p>From browsing to unboxing, everything about The Atelier Noir feels intentional. This is how online fashion should feel.</p>
            <footer>&mdash; Arthur Forgarty, Customer &middot; Texas, USA</footer>
        </blockquote>
    </div>
</section>
<div class="greek-key-divider"></div>

<!-- ══════════ TESTIMONIALS ══════════ -->
<section class="noir-section testimonial-section reveal-3d">
    <div class="section-head centered container-lux">
        <span class="noir-kicker center">05 &mdash; Client Voices</span>
        <h2 class="noir-h2 center">What Our Clients Say</h2>
        <p class="team-intro">Real experiences from clients across the Philippines, in their own words. Tap any card to read the full testimonial.</p>
    </div>

    <div class="marquee-row-wrap">
        <div class="marquee-row">
            <div class="marquee-track">
                <?php foreach (array_merge($testimonials['row1'], $testimonials['row1']) as $t): ?>
                <article class="testi-card"
                    data-name="<?= e($t['client_name']) ?>"
                    data-branch="<?= e($t['client_branch']) ?>"
                    data-rating="<?= (int)$t['rating'] ?>"
                    data-full="<?= e($t['quote_text']) ?>"
                    data-initial="<?= e($t['avatar_initial'] ?: mb_substr($t['client_name'], 0, 2)) ?>"
                    <?php if (!empty($t['avatar_image'])): ?>data-avatar-img="<?= e($t['avatar_image']) ?>"<?php endif; ?>
                    tabindex="0" role="button" aria-haspopup="dialog">
                    <div class="testi-card-top">
                        <div class="testi-stars"><?= str_repeat('&#9733;', (int)$t['rating']) . str_repeat('&#9734;', 5 - (int)$t['rating']) ?></div>
                        <span class="testi-expand-hint" aria-hidden="true">&#8599;</span>
                    </div>
                    <p class="testi-quote">"<?= e(noir_excerpt($t['quote_text'])) ?>"</p>
                    <div class="testi-divider"></div>
                    <div class="testi-foot">
                        <div class="testi-avatar">
                            <?php if (!empty($t['avatar_image'])): ?>
                                <img src="<?= e($t['avatar_image']) ?>" alt="" loading="lazy" data-fallback="<?= e($t['avatar_initial'] ?: mb_substr($t['client_name'], 0, 2)) ?>">
                            <?php else: ?>
                                <?= e($t['avatar_initial'] ?: mb_substr($t['client_name'], 0, 2)) ?>
                            <?php endif; ?>
                        </div>
                        <div class="testi-meta">
                            <div class="testi-name"><?= e($t['client_name']) ?></div>
                            <div class="testi-branch"><span class="testi-branch-dot"></span><?= e($t['client_branch']) ?></div>
                        </div>
                        <div class="testi-date"><?= e(noir_testi_date($t['created_at'] ?? null)) ?></div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="marquee-row">
            <div class="marquee-track reverse">
                <?php foreach (array_merge($testimonials['row2'], $testimonials['row2']) as $t): ?>
                <article class="testi-card"
                    data-name="<?= e($t['client_name']) ?>"
                    data-branch="<?= e($t['client_branch']) ?>"
                    data-rating="<?= (int)$t['rating'] ?>"
                    data-full="<?= e($t['quote_text']) ?>"
                    data-initial="<?= e($t['avatar_initial'] ?: mb_substr($t['client_name'], 0, 2)) ?>"
                    <?php if (!empty($t['avatar_image'])): ?>data-avatar-img="<?= e($t['avatar_image']) ?>"<?php endif; ?>
                    tabindex="0" role="button" aria-haspopup="dialog">
                    <div class="testi-card-top">
                        <div class="testi-stars"><?= str_repeat('&#9733;', (int)$t['rating']) . str_repeat('&#9734;', 5 - (int)$t['rating']) ?></div>
                        <span class="testi-expand-hint" aria-hidden="true">&#8599;</span>
                    </div>
                    <p class="testi-quote">"<?= e(noir_excerpt($t['quote_text'])) ?>"</p>
                    <div class="testi-divider"></div>
                    <div class="testi-foot">
                        <div class="testi-avatar">
                            <?php if (!empty($t['avatar_image'])): ?>
                                <img src="<?= e($t['avatar_image']) ?>" alt="" loading="lazy" data-fallback="<?= e($t['avatar_initial'] ?: mb_substr($t['client_name'], 0, 2)) ?>">
                            <?php else: ?>
                                <?= e($t['avatar_initial'] ?: mb_substr($t['client_name'], 0, 2)) ?>
                            <?php endif; ?>
                        </div>
                        <div class="testi-meta">
                            <div class="testi-name"><?= e($t['client_name']) ?></div>
                            <div class="testi-branch"><span class="testi-branch-dot"></span><?= e($t['client_branch']) ?></div>
                        </div>
                        <div class="testi-date"><?= e(noir_testi_date($t['created_at'] ?? null)) ?></div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ══════════ TESTIMONIAL MODAL ══════════ -->
<div class="noir-modal" id="testiModal" aria-hidden="true">
    <div class="noir-modal-backdrop" data-modal-close></div>
    <div class="noir-modal-panel" role="dialog" aria-modal="true" aria-labelledby="testiModalName">
        <button class="noir-modal-close" type="button" aria-label="Close" data-modal-close>&times;</button>
        <div class="noir-modal-stars" id="testiModalStars"></div>
        <p class="noir-modal-quote" id="testiModalQuote"></p>
        <div class="noir-modal-foot">
            <div class="testi-avatar" id="testiModalAvatar"></div>
            <div>
                <div class="testi-name" id="testiModalName"></div>
                <div class="testi-branch" id="testiModalBranch"></div>
            </div>
        </div>
    </div>
</div>

</main>

<script>
(function () {
    /* Scroll-triggered 3D reveal */
    const revealEls = document.querySelectorAll('.reveal-3d');
    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) { entry.target.classList.add('in'); io.unobserve(entry.target); }
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });
        revealEls.forEach((el) => io.observe(el));
    } else {
        revealEls.forEach((el) => el.classList.add('in'));
    }

    /* Mouse-driven 3D tilt for team cards / essentials visual */
    const tiltEls = document.querySelectorAll('.tilt-card');
    tiltEls.forEach((card) => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width - 0.5;
            const y = (e.clientY - rect.top) / rect.height - 0.5;
            card.style.transform = `perspective(900px) rotateY(${x * 10}deg) rotateX(${-y * 10}deg) translateZ(6px)`;
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(900px) rotateY(0deg) rotateX(0deg) translateZ(0)';
        });
    });

    /* Also tilt testimonial cards, lighter effect */
    document.querySelectorAll('.testi-card').forEach((card) => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width - 0.5;
            const y = (e.clientY - rect.top) / rect.height - 0.5;
            card.style.transform = `perspective(800px) rotateY(${x * 6}deg) rotateX(${-y * 6}deg)`;
        });
        card.addEventListener('mouseleave', () => { card.style.transform = 'perspective(800px) rotateY(0) rotateX(0)'; });
    });

    /* Broken/empty avatar images fall back to initials, silently */
    document.querySelectorAll('.testi-avatar img, .member-avatar img').forEach((img) => {
        img.addEventListener('error', function () {
            this.parentElement.textContent = this.dataset.fallback || '';
        }, { once: true });
    });

    /* Team & founder cards: tap-to-toggle skill overlay for touch devices
       (hover already handles this for mouse users). Links inside the
       overlay are left alone so "View More" still navigates normally. */
    document.querySelectorAll('.team-card, .founder-card').forEach((card) => {
        card.addEventListener('click', (e) => {
            if (e.target.closest('a')) return;
            card.classList.toggle('skills-open');
        });
    });

    /* Testimonial modal */
    const modal = document.getElementById('testiModal');
    if (modal) {
        const els = {
            stars: document.getElementById('testiModalStars'),
            quote: document.getElementById('testiModalQuote'),
            avatar: document.getElementById('testiModalAvatar'),
            name: document.getElementById('testiModalName'),
            branch: document.getElementById('testiModalBranch'),
        };

        function openModalFrom(card) {
            const rating = parseInt(card.dataset.rating || '5', 10);
            els.stars.textContent = '\u2605'.repeat(rating) + '\u2606'.repeat(5 - rating);
            els.quote.textContent = '\u201C' + (card.dataset.full || '') + '\u201D';
            els.name.textContent = card.dataset.name || '';
            els.branch.textContent = 'Customer \u00B7 ' + (card.dataset.branch || '');
            const img = card.dataset.avatarImg;
            els.avatar.innerHTML = img
                ? '<img src="' + img + '" alt="" onerror="this.parentElement.textContent=this.dataset.fb" data-fb="' + (card.dataset.initial || '') + '">'
                : (card.dataset.initial || '');
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        }
        function closeModal() {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
        }
        document.querySelectorAll('.testi-card').forEach((card) => {
            card.addEventListener('click', () => openModalFrom(card));
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openModalFrom(card); }
            });
        });
        modal.querySelectorAll('[data-modal-close]').forEach((el) => el.addEventListener('click', closeModal));
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });
    }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
