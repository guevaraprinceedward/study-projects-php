<?php
/**
 * about.php — The Atelier Noir
 *
 * Brand-new page (replaces the old founder-dossier adaptation entirely).
 * Requires: includes/testimonial-functions.php + database/about_testimonials.sql
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/testimonial-functions.php';

$page_title = "About — The Atelier Noir";
$meta_description = "Crafted with intention. Defined by you. The story, philosophy, and team behind The Atelier Noir.";
require_once __DIR__ . '/includes/header.php';

$testimonials = get_about_testimonials();

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

$founders = [
    ['name' => 'Elizabeth Jeanel Torres', 'role' => 'Founder &amp; Owner &middot; UI/UX Consultant', 'initials' => 'EJT',
     'bio' => 'Leading The Atelier Noir with a vision centered on refined fashion and thoughtful experiences. Elizabeth oversees the brand\'s direction while bringing a strong perspective in user experience and interface design.'],
    ['name' => 'Prince Edward Guevara', 'role' => 'Founder &amp; Co-Owner &middot; Full-Stack Developer', 'initials' => 'PEG',
     'bio' => 'Bringing technology and innovation into the foundation of The Atelier Noir. Prince develops and maintains the digital systems behind the brand, ensuring that its online experience remains functional, modern, and seamless.'],
];

$team = [
    ['name' => 'Mark Andrei Vertudez', 'role' => 'Professional Marketing Strategist', 'initials' => 'MV',
     'bio' => 'Shapes the strategies that connect The Atelier Noir with its audience. Mark focuses on positioning, marketing direction, and creating meaningful opportunities for the brand to grow.'],
    ['name' => 'Ralf A. Arenas', 'role' => 'Feature &amp; Product Analyzer', 'initials' => 'RA',
     'bio' => 'Examines products and platform features with a focus on functionality, relevance, and customer experience. Ralf helps ensure that every feature and offering provides meaningful value.'],
    ['name' => 'Ckiel Abraham Eronico', 'role' => 'Social Media Strategist &amp; Client Communicator', 'initials' => 'CE',
     'bio' => 'Strengthens the connection between The Atelier Noir and its community. Ckiel develops social media strategies while maintaining clear and meaningful communication with clients and audiences.'],
    ['name' => 'Katherine Jasmine Gesmundo', 'role' => 'UI/UX Designer', 'initials' => 'KG',
     'bio' => 'Transforms ideas into intuitive and visually refined digital experiences. Katherine focuses on creating interfaces that reflect the elegance of The Atelier Noir while keeping every interaction simple and engaging.'],
];
?>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
.noir-page{ --noir-line:rgba(200,169,106,.22); --font-greek:'Cinzel','Cormorant Garamond',serif; --font-serif:'Cormorant Garamond',serif; --font-body:'Jost',sans-serif; font-family:var(--font-body); font-weight:300; overflow-x:hidden; }
.noir-page h1,.noir-page h2,.noir-page h3{ font-family:var(--font-greek); font-weight:600; letter-spacing:.01em; }
.noir-kicker{ display:inline-flex; align-items:center; gap:12px; font-family:var(--font-body); font-size:11px; letter-spacing:.34em; text-transform:uppercase; color:var(--gold); margin-bottom:18px; }
.noir-kicker::before{ content:''; width:30px; height:1px; background:var(--gold); }
.noir-kicker.center{ justify-content:center; }
.noir-h2{ font-size:clamp(28px,4vw,46px); color:var(--text-primary); line-height:1.12; margin-bottom:1.4rem; }
.noir-h2.center{ text-align:center; }
.center{ text-align:center; margin-left:auto; margin-right:auto; }

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

.defines-section{ text-align:center; }
.laurel-divider{ display:flex; justify-content:center; margin-bottom:1.4rem; }
.defines-wrap{ max-width:760px; margin:0 auto; }
.defines-quote{ font-family:var(--font-serif); font-style:italic; font-size:1.3rem; line-height:1.75; color:var(--text-primary); margin-bottom:1.6rem; }

.team-intro{ font-family:var(--font-serif); font-size:1.05rem; line-height:1.85; color:var(--text-secondary); max-width:640px; margin:0 auto 3.5rem; }
.founders-row{ display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2.5rem; }
.founder-card, .team-card{ background:var(--bg-card); border:1px solid var(--border-color); padding:2.4rem 2.2rem; transition:border-color .35s ease; }
.founder-card:hover, .team-card:hover{ border-color:rgba(200,169,106,0.4); }
.member-avatar{ width:58px; height:58px; border-radius:50%; border:1px solid var(--gold); color:var(--gold); display:flex; align-items:center; justify-content:center; font-family:var(--font-greek); font-size:1.05rem; margin-bottom:1.3rem; transform:translateZ(20px); }
.founder-card h3, .team-card h3{ font-size:1.25rem; color:var(--text-primary); margin-bottom:.4rem; }
.founder-card .role, .team-card .role{ font-size:.72rem; letter-spacing:.1em; text-transform:uppercase; color:var(--gold); margin-bottom:1rem; }
.founder-card .bio, .team-card .bio{ font-size:.88rem; line-height:1.8; color:var(--text-secondary); }
.team-grid{ display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:1.6rem; }
@media(max-width:992px){ .founders-row{ grid-template-columns:1fr; } .team-grid{ grid-template-columns:repeat(2, minmax(0,1fr)); } }
@media(max-width:560px){ .team-grid{ grid-template-columns:1fr; } }

.noir-closing{ text-align:center; padding:7rem 5vw; }
.noir-closing-title{ font-size:clamp(2rem,4.4vw,3.2rem); color:var(--text-primary); margin-bottom:1rem; }
.noir-closing-title em{ color:var(--gold); font-style:italic; font-family:var(--font-serif); }
.noir-closing-sub{ font-family:var(--font-serif); font-style:italic; color:var(--gold); font-size:1.05rem; margin-bottom:1.6rem; }
.noir-closing-body{ font-size:.95rem; color:var(--text-secondary); max-width:560px; margin:0 auto; line-height:1.85; }

.testimonial-section .section-head{ margin-bottom:3rem; }
.marquee-row-wrap{ display:flex; flex-direction:column; gap:1.6rem; }
.marquee-row{ overflow:hidden; position:relative; -webkit-mask-image:linear-gradient(90deg, transparent, #000 6%, #000 94%, transparent); mask-image:linear-gradient(90deg, transparent, #000 6%, #000 94%, transparent); }
.marquee-track{ display:flex; gap:1.4rem; width:max-content; animation:marqueeLeft 42s linear infinite; }
.marquee-track.reverse{ animation-name:marqueeRight; animation-duration:48s; }
.marquee-row:hover .marquee-track{ animation-play-state:paused; }
@keyframes marqueeLeft{ from{ transform:translateX(0); } to{ transform:translateX(-50%); } }
@keyframes marqueeRight{ from{ transform:translateX(-50%); } to{ transform:translateX(0); } }

.testi-card{ flex:0 0 auto; width:320px; background:var(--bg-card); border:1px solid var(--border-color); padding:1.8rem 1.9rem; transform-style:preserve-3d; transition:transform .25s cubic-bezier(.2,.7,.3,1), border-color .3s ease; }
.testi-card:hover{ border-color:rgba(200,169,106,0.4); }
.testi-stars{ color:var(--gold); font-size:.82rem; letter-spacing:.18em; margin-bottom:.9rem; }
.testi-quote{ font-family:var(--font-serif); font-size:1rem; line-height:1.7; color:var(--text-primary); margin-bottom:1.2rem; min-height:4.5em; }
.testi-foot{ display:flex; align-items:center; gap:.8rem; }
.testi-avatar{ width:38px; height:38px; border-radius:50%; border:1px solid var(--gold); color:var(--gold); display:flex; align-items:center; justify-content:center; font-family:var(--font-greek); font-size:.78rem; flex-shrink:0; }
.testi-name{ font-size:.85rem; color:var(--text-primary); }
.testi-branch{ font-size:.72rem; color:var(--text-secondary); }

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

<!-- ══════════ WHAT DEFINES US ══════════ -->
<section class="noir-section defines-section reveal-3d">
    <div class="container-lux defines-wrap">
        <div class="laurel-divider"><?= noir_laurel(80, 40) ?></div>
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
            <p class="team-intro">Behind The Atelier Noir is a multidisciplinary team bringing together fashion, design, technology, marketing, product analysis, and communication. Together, we transform ideas into a cohesive fashion experience&mdash;both online and beyond.</p>
        </div>

        <div class="founders-row">
            <?php foreach ($founders as $f): ?>
            <article class="founder-card tilt-card">
                <div class="member-avatar"><?= e($f['initials']) ?></div>
                <h3><?= $f['name'] ?></h3>
                <p class="role"><?= $f['role'] ?></p>
                <p class="bio"><?= $f['bio'] ?></p>
            </article>
            <?php endforeach; ?>
        </div>

        <div class="team-grid">
            <?php foreach ($team as $m): ?>
            <article class="team-card tilt-card">
                <div class="member-avatar"><?= e($m['initials']) ?></div>
                <h3><?= $m['name'] ?></h3>
                <p class="role"><?= $m['role'] ?></p>
                <p class="bio"><?= $m['bio'] ?></p>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══════════ CLOSING TAGLINE ══════════ -->
<section class="noir-closing reveal-3d">
    <div class="laurel-divider"><?= noir_laurel(80, 40) ?></div>
    <h2 class="noir-closing-title">Together, We Shape<br><em>The Atelier Noir</em></h2>
    <p class="noir-closing-sub">Different disciplines. One vision.</p>
    <p class="noir-closing-body">From the first concept to the final experience, our team works together to build a fashion brand where style, technology, and individuality meet.</p>
</section>
<div class="greek-key-divider"></div>

<!-- ══════════ TESTIMONIALS ══════════ -->
<section class="noir-section testimonial-section reveal-3d">
    <div class="section-head centered container-lux">
        <span class="noir-kicker center">04 &mdash; Client Voices</span>
        <h2 class="noir-h2 center">What Our Clients Say</h2>
    </div>

    <div class="marquee-row-wrap">
        <div class="marquee-row">
            <div class="marquee-track">
                <?php foreach (array_merge($testimonials['row1'], $testimonials['row1']) as $t): ?>
                <article class="testi-card">
                    <div class="testi-stars"><?= str_repeat('&#9733;', (int)$t['rating']) . str_repeat('&#9734;', 5 - (int)$t['rating']) ?></div>
                    <p class="testi-quote">"<?= e($t['quote_text']) ?>"</p>
                    <div class="testi-foot">
                        <div class="testi-avatar"><?= e($t['avatar_initial'] ?: substr($t['client_name'], 0, 2)) ?></div>
                        <div>
                            <div class="testi-name"><?= e($t['client_name']) ?></div>
                            <div class="testi-branch">Customer &middot; <?= e($t['client_branch']) ?></div>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="marquee-row">
            <div class="marquee-track reverse">
                <?php foreach (array_merge($testimonials['row2'], $testimonials['row2']) as $t): ?>
                <article class="testi-card">
                    <div class="testi-stars"><?= str_repeat('&#9733;', (int)$t['rating']) . str_repeat('&#9734;', 5 - (int)$t['rating']) ?></div>
                    <p class="testi-quote">"<?= e($t['quote_text']) ?>"</p>
                    <div class="testi-foot">
                        <div class="testi-avatar"><?= e($t['avatar_initial'] ?: substr($t['client_name'], 0, 2)) ?></div>
                        <div>
                            <div class="testi-name"><?= e($t['client_name']) ?></div>
                            <div class="testi-branch">Customer &middot; <?= e($t['client_branch']) ?></div>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

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
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>