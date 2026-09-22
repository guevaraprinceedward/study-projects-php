<?php
include_once 'hotel-config.php';
$activePage = 'about';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About the Founder — Prince Edward Guevara</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Jost:wght@300;400;500;600;700&family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="hotel-theme.css">
<style>
/* ══════════════════════════════════════════════════════════════
   PAGE-SPECIFIC: about-us — "The Founder's Dossier"
   Signature idea: a numbered dossier index rail that tracks scroll
   position, echoing the source portfolio's own Table of Contents.
   ══════════════════════════════════════════════════════════════ */
:root{
    --f-serif:'Fraunces', 'Cormorant Garamond', serif;
}

.dossier{position:relative;z-index:1;max-width:1180px;margin:0 auto;padding:0 32px}

/* ── Index rail (desktop only) ── */
.index-rail{position:fixed;top:50%;left:26px;transform:translateY(-50%);z-index:60;display:flex;flex-direction:column;gap:2px}
.index-rail a{display:flex;align-items:center;gap:10px;text-decoration:none;padding:6px 0;group:link}
.index-rail .ir-num{font-family:var(--f-serif);font-size:11px;color:var(--h-gold-dim);width:18px;text-align:right;transition:color .3s ease}
.index-rail .ir-bar{width:14px;height:1px;background:var(--h-line);transition:all .35s cubic-bezier(.2,.7,.3,1)}
.index-rail .ir-label{font-size:9.5px;letter-spacing:.14em;text-transform:uppercase;color:transparent;white-space:nowrap;max-width:0;overflow:hidden;transition:all .35s ease}
.index-rail a:hover .ir-label,.index-rail a.active .ir-label{color:var(--h-muted);max-width:160px;margin-left:2px}
.index-rail a.active .ir-num{color:var(--h-gold)}
.index-rail a.active .ir-bar{width:26px;background:var(--h-gold)}
@media(max-width:1240px){.index-rail{display:none}}

/* ── Dossier hero ── */
.dh{position:relative;padding:150px 32px 100px;overflow:hidden}
.dh-grain{position:absolute;inset:0;pointer-events:none;opacity:.5;
  background-image:radial-gradient(circle at 20% 20%, rgba(207,167,107,0.05), transparent 40%),
                    radial-gradient(circle at 85% 75%, rgba(92,31,46,0.14), transparent 45%)}
.dh-inner{position:relative;z-index:1;max-width:1180px;margin:0 auto;display:grid;grid-template-columns:0.82fr 1.18fr;gap:64px;align-items:center}
.dh-photo-wrap{position:relative}
.dh-photo{position:relative;border-radius:3px;overflow:hidden;aspect-ratio:4/5;border:1px solid var(--h-line);box-shadow:0 40px 90px rgba(0,0,0,0.55)}
.dh-photo img{width:100%;height:100%;object-fit:cover;object-position:top center;filter:grayscale(0.15) contrast(1.03)}
.dh-photo::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(7,7,10,0) 60%,rgba(7,7,10,0.55) 100%)}
.dh-photo-tag{position:absolute;left:18px;bottom:16px;z-index:2;font-family:var(--f-serif);font-size:13px;font-style:italic;color:var(--h-champagne);letter-spacing:.02em}
.dh-frame-tl,.dh-frame-br{position:absolute;width:34px;height:34px;pointer-events:none;z-index:2}
.dh-frame-tl{top:-11px;left:-11px;border-top:1px solid var(--h-gold);border-left:1px solid var(--h-gold)}
.dh-frame-br{bottom:-11px;right:-11px;border-bottom:1px solid var(--h-gold);border-right:1px solid var(--h-gold)}

.dh-eyebrow{display:inline-flex;align-items:center;gap:12px;font-size:11px;letter-spacing:.32em;text-transform:uppercase;color:var(--h-gold);margin-bottom:24px}
.dh-eyebrow::before{content:'';width:34px;height:1px;background:var(--h-gold-dim)}
.dh-name{font-family:var(--f-serif);font-size:clamp(42px,5.4vw,74px);font-weight:600;line-height:1.02;color:var(--h-champagne);letter-spacing:-.01em;margin-bottom:18px}
.dh-name em{font-style:italic;color:var(--h-gold);font-weight:500}
.dh-roles{display:flex;flex-wrap:wrap;gap:9px;margin-bottom:26px}
.dh-role-pill{font-size:10.5px;letter-spacing:.08em;text-transform:uppercase;color:var(--h-text);border:1px solid var(--h-line);border-radius:100px;padding:7px 15px;background:rgba(255,255,255,0.02)}
.dh-quote{font-family:var(--f-serif);font-style:italic;font-size:19px;line-height:1.6;color:var(--h-muted);max-width:480px;margin-bottom:34px;border-left:2px solid var(--h-gold-dim);padding-left:20px}
.dh-cta{display:flex;gap:12px;flex-wrap:wrap}
.btn-outline-gh{display:inline-flex;align-items:center;gap:10px;padding:12px 24px;border:1px solid var(--h-gold-dim);border-radius:2px;color:var(--h-champagne);font-size:12px;letter-spacing:.08em;text-transform:uppercase;text-decoration:none;transition:all .25s}
.btn-outline-gh:hover{background:var(--h-champagne);color:#0a0a0c;border-color:var(--h-champagne)}
.btn-outline-gh svg{flex-shrink:0}

.dh-meta-row{display:flex;gap:34px;margin-top:44px;flex-wrap:wrap}
.dh-meta-item .mk{font-size:9.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--h-gold-dim);margin-bottom:6px}
.dh-meta-item .mv{font-family:var(--f-serif);font-size:16px;color:var(--h-text)}

@media(max-width:900px){
    .dh{padding:110px 20px 60px}
    .dh-inner{grid-template-columns:1fr;gap:40px}
    .dh-photo{max-width:340px;margin:0 auto}
}

/* ── Section scaffolding ── */
.dossier-section{padding:100px 0;border-top:1px solid var(--h-line)}
.ds-head{display:grid;grid-template-columns:0.9fr 2.1fr;gap:48px;margin-bottom:52px}
.ds-tag{display:flex;align-items:baseline;gap:14px}
.ds-num{font-family:var(--f-serif);font-size:15px;color:var(--h-gold-dim);font-style:italic}
.ds-eyebrow{font-size:11px;letter-spacing:.24em;text-transform:uppercase;color:var(--h-gold)}
.ds-head h2{font-family:var(--f-serif);font-size:clamp(30px,3.4vw,44px);font-weight:600;color:var(--h-champagne);line-height:1.1;margin-top:10px}
.ds-lede{font-size:14.5px;line-height:1.85;color:var(--h-muted);font-weight:300;align-self:end}
@media(max-width:820px){ .ds-head{grid-template-columns:1fr;gap:16px} }

/* ── Executive profile ── */
.profile-copy{display:grid;grid-template-columns:1.6fr 1fr;gap:56px}
.profile-copy p{font-size:15px;line-height:1.9;color:var(--h-text);font-weight:300;margin-bottom:20px}
.profile-copy p:first-of-type::first-letter{font-family:var(--f-serif);font-size:58px;font-weight:600;color:var(--h-gold);float:left;line-height:.78;padding:7px 12px 0 0}
.info-card{background:var(--h-card);border:1px solid var(--h-line);border-radius:4px;padding:28px 26px;align-self:start;height:fit-content}
.info-card h4{font-family:var(--f-serif);font-size:16px;color:var(--h-gold);margin-bottom:18px;font-style:italic}
.info-row{display:flex;justify-content:space-between;gap:10px;padding:11px 0;border-bottom:1px solid var(--h-line);font-size:12.5px}
.info-row:last-child{border-bottom:none}
.info-row .ik{color:var(--h-muted);letter-spacing:.03em}
.info-row .iv{color:var(--h-champagne);font-family:var(--f-serif);font-size:14px;text-align:right}
@media(max-width:820px){ .profile-copy{grid-template-columns:1fr} }

/* ── Family / father block ── */
.family-grid{display:grid;grid-template-columns:0.85fr 1.15fr;gap:54px;align-items:center}
.fam-photo{position:relative;border-radius:3px;overflow:hidden;aspect-ratio:4/5.1;border:1px solid var(--h-line);box-shadow:0 30px 70px rgba(0,0,0,.5)}
.fam-photo img{width:100%;height:100%;object-fit:cover;object-position:50% 22%}
.fam-photo-cap{position:absolute;left:16px;bottom:14px;z-index:2;font-size:10px;letter-spacing:.1em;text-transform:uppercase;color:var(--h-gold);background:rgba(7,7,10,0.6);backdrop-filter:blur(4px);padding:6px 12px;border-radius:100px;border:1px solid rgba(207,167,107,0.3)}
.fam-copy h3{font-family:var(--f-serif);font-size:26px;color:var(--h-champagne);margin-bottom:4px}
.fam-copy .fam-name{font-size:12px;color:var(--h-gold-dim);letter-spacing:.08em;text-transform:uppercase;margin-bottom:22px}
.fam-tags{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:22px}
.fam-tag{font-size:11px;color:var(--h-text);border:1px solid var(--h-line);border-radius:100px;padding:6px 13px;background:rgba(255,255,255,0.02)}
.fam-mother{margin-top:30px;padding-top:26px;border-top:1px solid var(--h-line);display:flex;gap:16px;align-items:baseline}
.fam-mother .fm-label{font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:var(--h-gold-dim);flex-shrink:0}
.fam-mother .fm-val{font-family:var(--f-serif);font-size:16px;color:var(--h-text)}
@media(max-width:820px){ .family-grid{grid-template-columns:1fr} .fam-photo{max-width:320px;margin:0 auto} }

/* ── Father's extended story ── */
.fam-story{margin-top:64px;padding-top:56px;border-top:1px solid var(--h-line)}
.fam-story-head{display:flex;align-items:baseline;gap:14px;margin-bottom:8px}
.fam-story-head .fs-eyebrow{font-size:10.5px;letter-spacing:.16em;text-transform:uppercase;color:var(--h-gold)}
.fam-story-head .fs-years{font-family:var(--f-serif);font-style:italic;font-size:13px;color:var(--h-gold-dim)}
.fam-story h3{font-family:var(--f-serif);font-size:28px;color:var(--h-champagne);margin-bottom:28px}
.fam-story-body{display:grid;grid-template-columns:1.15fr 0.85fr;gap:56px}
.fam-story-body p{font-size:14px;line-height:1.9;color:var(--h-text);font-weight:300;margin-bottom:18px}
.fam-story-body p:first-child::first-letter{font-family:var(--f-serif);font-size:50px;font-weight:600;color:var(--h-gold);float:left;line-height:.78;padding:6px 10px 0 0}
.fam-ventures{background:var(--h-card);border:1px solid var(--h-line);border-radius:4px;padding:24px 24px 8px;align-self:start}
.fam-ventures h5{font-family:var(--f-serif);font-size:14px;font-style:italic;color:var(--h-gold);margin-bottom:16px}
.fam-venture-item{padding:14px 0;border-bottom:1px solid var(--h-line)}
.fam-venture-item:last-child{border-bottom:none}
.fam-venture-item .fv-name{font-size:13px;color:var(--h-champagne);font-family:var(--f-serif);margin-bottom:3px}
.fam-venture-item .fv-desc{font-size:11.5px;color:var(--h-muted);line-height:1.6;font-weight:300}

.fam-pullquote{margin:44px 0;padding:36px 40px;background:linear-gradient(160deg,rgba(207,167,107,0.06),transparent 60%);border-left:2px solid var(--h-gold);border-radius:2px}
.fam-pullquote p{font-family:var(--f-serif);font-style:italic;font-size:17px;line-height:1.75;color:var(--h-champagne);margin-bottom:14px}
.fam-pullquote .fq-attr{font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:var(--h-gold-dim)}

.fam-legacy{margin-top:8px}
.fam-legacy h5{font-family:var(--f-serif);font-size:15px;font-style:italic;color:var(--h-gold);margin-bottom:18px}
.fam-mentor-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.fam-mentor-card{border:1px solid var(--h-line);border-radius:4px;padding:16px 18px;background:rgba(255,255,255,0.015)}
.fam-mentor-card .fm-num{font-family:var(--f-serif);font-style:italic;font-size:11px;color:var(--h-gold-dim);margin-bottom:6px}
.fam-mentor-card .fm-title{font-size:12.5px;color:var(--h-champagne);font-family:var(--f-serif)}
@media(max-width:820px){ .fam-story-body{grid-template-columns:1fr} .fam-mentor-grid{grid-template-columns:1fr 1fr} }
@media(max-width:560px){ .fam-mentor-grid{grid-template-columns:1fr} .fam-pullquote{padding:26px 22px} }

/* ── Skills ── */
.skills-layout{display:grid;grid-template-columns:1fr 1fr;gap:60px}
.skill-block h4{font-family:var(--f-serif);font-size:17px;color:var(--h-gold);margin-bottom:18px;font-style:italic}
.chip-wrap{display:flex;flex-wrap:wrap;gap:9px;margin-bottom:38px}
.chip{font-size:12px;color:var(--h-text);border:1px solid var(--h-line);border-radius:100px;padding:8px 16px;background:rgba(255,255,255,0.02);transition:border-color .2s}
.chip:hover{border-color:var(--h-gold-dim)}
.chip.lang{color:var(--h-gold);border-color:rgba(207,167,107,0.3);background:rgba(207,167,107,0.06)}
.strength-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.strength-card{border:1px solid var(--h-line);border-radius:4px;padding:20px 20px 22px;background:var(--h-card)}
.strength-card .sc-num{font-family:var(--f-serif);font-size:12px;color:var(--h-gold-dim);font-style:italic;margin-bottom:10px}
.strength-card h5{font-family:var(--f-serif);font-size:16px;color:var(--h-champagne);margin-bottom:8px}
.strength-card p{font-size:12px;color:var(--h-muted);line-height:1.7;font-weight:300}
@media(max-width:820px){ .skills-layout{grid-template-columns:1fr} .strength-grid{grid-template-columns:1fr} }

/* ── Leadership (image + list) ── */
.lead-grid{display:grid;grid-template-columns:1.05fr 0.95fr;gap:54px;align-items:center}
.lead-photo{position:relative;border-radius:3px;overflow:hidden;border:1px solid var(--h-line);box-shadow:0 30px 70px rgba(0,0,0,.5);aspect-ratio:5/4.4}
.lead-photo img{width:100%;height:100%;object-fit:cover;object-position:30% 30%}
.lead-photo-cap{position:absolute;left:16px;bottom:14px;z-index:2;max-width:80%}
.lead-photo-cap .lc-eyebrow{font-size:9.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--h-gold);margin-bottom:4px}
.lead-photo-cap .lc-text{font-family:var(--f-serif);font-style:italic;font-size:14px;color:var(--h-champagne);line-height:1.4}
.org-list{display:flex;flex-direction:column}
.org-item{display:flex;gap:16px;padding:18px 0;border-bottom:1px solid var(--h-line)}
.org-item:first-child{padding-top:0}
.org-item:last-child{border-bottom:none}
.org-role{flex-shrink:0;width:120px;font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:var(--h-gold-dim);line-height:1.5}
.org-body h5{font-family:var(--f-serif);font-size:16.5px;color:var(--h-champagne);margin-bottom:4px}
.org-body p{font-size:12px;color:var(--h-muted);line-height:1.65;font-weight:300}
@media(max-width:820px){ .lead-grid{grid-template-columns:1fr} .org-item{flex-direction:column;gap:4px} .org-role{width:auto} }

/* ── Ventures / projects (browser-frame screenshots) ── */
.venture{display:grid;grid-template-columns:1fr 1fr;gap:54px;align-items:center;margin-bottom:90px}
.venture:last-child{margin-bottom:0}
.venture.reverse .venture-shot{order:2}
.venture.reverse .venture-copy{order:1}
.browser-frame{border-radius:6px;overflow:hidden;border:1px solid var(--h-line);box-shadow:0 30px 80px rgba(0,0,0,0.55);background:#0c0c0e}
.browser-bar{display:flex;align-items:center;gap:8px;padding:11px 14px;background:#141417;border-bottom:1px solid var(--h-line)}
.browser-dot{width:8px;height:8px;border-radius:50%}
.browser-dot.r{background:#e0645a}
.browser-dot.y{background:#e0b95a}
.browser-dot.g{background:#7fa876}
.browser-url{margin-left:10px;font-size:10.5px;color:var(--h-muted);background:rgba(255,255,255,0.03);border:1px solid var(--h-line);border-radius:100px;padding:4px 14px;flex:1;letter-spacing:.02em}
.browser-shot{width:100%;display:block;max-height:340px;object-fit:cover;object-position:top center}
.venture-eyebrow{display:flex;align-items:center;gap:10px;font-size:10.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--h-gold-dim);margin-bottom:14px}
.venture-role{color:var(--h-gold)}
.venture-copy h3{font-family:var(--f-serif);font-size:30px;color:var(--h-champagne);margin-bottom:14px}
.venture-copy p{font-size:13.5px;line-height:1.85;color:var(--h-muted);font-weight:300;margin-bottom:20px}
.venture-features{list-style:none;display:grid;grid-template-columns:1fr 1fr;gap:9px;margin-bottom:8px}
.venture-features li{font-size:12px;color:var(--h-text);display:flex;align-items:center;gap:8px}
.venture-features li::before{content:'';width:4px;height:4px;background:var(--h-gold);border-radius:50%;flex-shrink:0}
@media(max-width:900px){
    .venture,.venture.reverse{grid-template-columns:1fr}
    .venture.reverse .venture-shot,.venture.reverse .venture-copy{order:unset}
    .venture-features{grid-template-columns:1fr}
}

/* ── Monte Carlo panel ── */
.mc-panel{background:linear-gradient(160deg,rgba(207,167,107,0.05),var(--h-card) 60%);border:1px solid var(--h-line);border-radius:6px;padding:44px;display:flex;flex-direction:column;gap:38px}
.mc-shot .browser-frame{box-shadow:0 30px 70px rgba(0,0,0,.5)}
.mc-shot .browser-shot{max-height:none}
.mc-lower{display:grid;grid-template-columns:0.8fr 1.2fr;gap:54px;align-items:start}
.mc-copy .mc-eyebrow{font-size:10.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--h-gold-dim);margin-bottom:12px}
.mc-copy h3{font-family:var(--f-serif);font-size:27px;color:var(--h-champagne);margin-bottom:14px}
.mc-copy p{font-size:13.5px;line-height:1.85;color:var(--h-muted);font-weight:300;margin-bottom:16px}
.mc-roles{display:flex;flex-direction:column;gap:10px}
.mc-role-line{display:flex;gap:12px;font-size:12.5px;color:var(--h-text)}
.mc-role-line b{color:var(--h-gold);font-family:var(--f-serif);font-weight:600;flex-shrink:0;width:170px}
@media(max-width:820px){ .mc-panel{padding:26px} .mc-lower{grid-template-columns:1fr} }

/* ── Vision / philosophy ── */
.vision-wrap{text-align:center;max-width:820px;margin:0 auto}
.vision-quote{font-family:var(--f-serif);font-style:italic;font-size:clamp(24px,3.2vw,34px);line-height:1.55;color:var(--h-champagne);margin-bottom:20px}
.vision-quote::before{content:'"';color:var(--h-gold)}
.vision-quote::after{content:'"';color:var(--h-gold)}
.vision-attr{font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--h-gold-dim);margin-bottom:56px}
.goals-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:var(--h-line);border:1px solid var(--h-line);border-radius:4px;overflow:hidden;text-align:left}
.goal-cell{background:var(--h-bg);padding:26px 22px;display:flex;flex-direction:column;gap:8px}
.goal-cell .gc-num{font-family:var(--f-serif);font-size:12px;color:var(--h-gold-dim);font-style:italic}
.goal-cell p{font-size:12px;color:var(--h-text);line-height:1.6;font-weight:300}
@media(max-width:900px){ .goals-grid{grid-template-columns:1fr 1fr} }
@media(max-width:560px){ .goals-grid{grid-template-columns:1fr} }

/* ── Contact ── */
.contact-panel{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--h-line);border:1px solid var(--h-line);border-radius:6px;overflow:hidden}
.contact-cell{background:var(--h-card);padding:38px 30px;display:flex;flex-direction:column;gap:14px;text-decoration:none;transition:background .25s}
.contact-cell:hover{background:#181818}
.contact-cell .cc-icon{width:38px;height:38px;border:1px solid var(--h-gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--h-gold)}
.contact-cell .cc-label{font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:var(--h-gold-dim)}
.contact-cell .cc-handle{font-family:var(--f-serif);font-size:19px;color:var(--h-champagne)}
.contact-cell .cc-sub{font-size:11.5px;color:var(--h-muted)}
@media(max-width:820px){ .contact-panel{grid-template-columns:1fr} }

.closing-line{text-align:center;padding:70px 32px 20px;font-family:var(--f-serif);font-style:italic;font-size:16px;color:var(--h-muted);max-width:640px;margin:0 auto}
</style>
</head>
<body>
<?php include_once 'sidebar.php'; ?>
<?php include_once 'topheader.php'; ?>

<!-- ── Scroll-tracking index rail ── -->
<nav class="index-rail" id="indexRail">
    <a href="#profile" data-target="profile"><span class="ir-num">I</span><span class="ir-bar"></span><span class="ir-label">Executive Profile</span></a>
    <a href="#family" data-target="family"><span class="ir-num">II</span><span class="ir-bar"></span><span class="ir-label">Family Background</span></a>
    <a href="#skills" data-target="skills"><span class="ir-num">III</span><span class="ir-bar"></span><span class="ir-label">Skills &amp; Strengths</span></a>
    <a href="#leadership" data-target="leadership"><span class="ir-num">IV</span><span class="ir-bar"></span><span class="ir-label">Organizations</span></a>
    <a href="#ventures" data-target="ventures"><span class="ir-num">V</span><span class="ir-bar"></span><span class="ir-label">Ventures</span></a>
    <a href="#montecarlo" data-target="montecarlo"><span class="ir-num">VI</span><span class="ir-bar"></span><span class="ir-label">Monte Carlo</span></a>
    <a href="#vision" data-target="vision"><span class="ir-num">VII</span><span class="ir-bar"></span><span class="ir-label">Vision</span></a>
    <a href="#contact" data-target="contact"><span class="ir-num">VIII</span><span class="ir-bar"></span><span class="ir-label">Contact</span></a>
</nav>

<!-- ══════════ HERO ══════════ -->
<section class="dh">
    <div class="dh-grain"></div>
    <div class="dh-inner">
        <div class="dh-photo-wrap">
            <div class="dh-photo">
                <span class="dh-frame-tl"></span>
                <img src="images/about-profile.jpeg" alt="Prince Edward Guevara">
                <span class="dh-photo-tag">San Pablo City, Laguna</span>
                <span class="dh-frame-br"></span>
            </div>
        </div>
        <div>
            <div class="dh-eyebrow">The Founder's Dossier</div>
            <h1 class="dh-name">Prince Edward<br><em>Guevara</em></h1>
            <div class="dh-roles">
                <span class="dh-role-pill">Founder</span>
                <span class="dh-role-pill">Technology Leader</span>
                <span class="dh-role-pill">Tech Prodigy</span>
                <span class="dh-role-pill">Programmer Developer</span>
                <span class="dh-role-pill">Editor Trainee</span>
            </div>
            <p class="dh-quote">"Technology is not only about writing code — it is about creating solutions that improve lives, empower businesses, and inspire continuous innovation."</p>
            <div class="dh-cta">
                <a href="https://github.com/guevaraprinceedward/" target="_blank" rel="noopener" class="btn-outline-gh">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.58 2 12.25c0 4.53 2.87 8.37 6.84 9.73.5.1.68-.22.68-.5 0-.24-.01-1.04-.01-1.89-2.78.61-3.37-1.2-3.37-1.2-.45-1.18-1.11-1.5-1.11-1.5-.9-.63.07-.62.07-.62 1 .07 1.53 1.05 1.53 1.05.89 1.56 2.34 1.11 2.91.85.09-.66.35-1.11.63-1.37-2.22-.26-4.56-1.14-4.56-5.06 0-1.12.39-2.03 1.03-2.75-.1-.26-.45-1.31.1-2.73 0 0 .84-.28 2.75 1.05a9.34 9.34 0 0 1 5 0c1.91-1.33 2.75-1.05 2.75-1.05.55 1.42.2 2.47.1 2.73.64.72 1.03 1.63 1.03 2.75 0 3.93-2.35 4.79-4.58 5.05.36.32.68.94.68 1.9 0 1.37-.01 2.47-.01 2.81 0 .28.18.61.69.5A10.26 10.26 0 0 0 22 12.25C22 6.58 17.52 2 12 2z"/></svg>
                    View GitHub
                </a>
                <a href="#contact" class="btn-ghost">Get in Touch</a>
            </div>
            <div class="dh-meta-row">
                <div class="dh-meta-item"><div class="mk">Age</div><div class="mv">17</div></div>
                <div class="dh-meta-item"><div class="mk">Birthdate</div><div class="mv">July 15, 2009</div></div>
                <div class="dh-meta-item"><div class="mk">Based In</div><div class="mv">Philippines</div></div>
                <div class="dh-meta-item"><div class="mk">Ventures</div><div class="mv">AyosCoffeeNegosyo &middot; Noctrune</div></div>
            </div>
        </div>
    </div>
</section>

<div class="dossier">

<!-- ══════════ I. EXECUTIVE PROFILE ══════════ -->
<section class="dossier-section" id="profile">
    <div class="ds-head">
        <div class="ds-tag"><span class="ds-num">I.</span><div><span class="ds-eyebrow">Introduction</span><h2>Executive<br>Profile</h2></div></div>
        <p class="ds-lede">An aspiring Software Engineer building at the intersection of discipline, leadership, and code — one project at a time.</p>
    </div>

    <div class="profile-copy">
        <div>
            <p>Prince Edward Guevara is an aspiring Software Engineer, Programmer Developer, Technology Leader, and Editor Trainee passionate about creating innovative digital solutions that provide practical value to businesses and organizations. His journey in technology is driven by curiosity, discipline, and an unwavering commitment to continuous improvement.</p>
            <p>At the age of seventeen, Prince has dedicated countless hours to studying programming, web development, system architecture, user experience, and digital media — always favoring real-world application over theory alone.</p>
            <p>His philosophy is centered on solving problems through technology. Every project is designed with functionality, scalability, and user experience in mind, built on the belief that successful systems should simplify operations, improve efficiency, and contribute to long-term business growth. Beyond software, he continues to sharpen his leadership and strategic thinking through philosophy, stoicism, and business strategy — believing that technical excellence paired with integrity creates the most meaningful impact.</p>
        </div>
        <div class="info-card">
            <h4>Personal Information</h4>
            <div class="info-row"><span class="ik">Full Name</span><span class="iv">Prince Edward Guevara</span></div>
            <div class="info-row"><span class="ik">Age</span><span class="iv">17</span></div>
            <div class="info-row"><span class="ik">Sex</span><span class="iv">Male</span></div>
            <div class="info-row"><span class="ik">Birthdate</span><span class="iv">July 15, 2009</span></div>
            <div class="info-row"><span class="ik">Birthplace</span><span class="iv">San Pablo City, Laguna</span></div>
        </div>
    </div>
</section>

<!-- ══════════ II. FAMILY BACKGROUND ══════════ -->
<section class="dossier-section" id="family">
    <div class="ds-head">
        <div class="ds-tag"><span class="ds-num">II.</span><div><span class="ds-eyebrow">Roots</span><h2>Family<br>Background</h2></div></div>
        <p class="ds-lede">The foundation of leadership and discipline that shaped his outlook, long before the first line of code.</p>
    </div>

    <div class="family-grid">
        <div class="fam-photo">
            <img src="images/about-father.jpeg" alt="Rogelio Calderon Guevara">
            <span class="fam-photo-cap">Rogelio C. Guevara</span>
        </div>
        <div class="fam-copy">
            <h3>Father</h3>
            <div class="fam-name">Rogelio Calderon Guevara</div>
            <div class="fam-tags">
                <span class="fam-tag">Founder &amp; Chairman, Monte Carlo Group of Companies — Est. 1984</span>
                <span class="fam-tag">Former CIS Investigator</span>
                <span class="fam-tag">Geodetic Engineer</span>
                <span class="fam-tag">Attorney</span>
                <span class="fam-tag">Former Philippine Constabulary — Investigation Agent / Major</span>
                <span class="fam-tag">Humanitarian Leader</span>
                <span class="fam-tag">Eagles of the Philippines Member</span>
                <span class="fam-tag">Lions International Member</span>
            </div>
            <div class="fam-mother">
                <span class="fm-label">Mother</span>
                <span class="fm-val">Jane S. Mendoza — Housewife</span>
            </div>
        </div>
    </div>

    <!-- ── Father's extended story ── -->
    <div class="fam-story">
        <div class="fam-story-head">
            <span class="fs-eyebrow">In His Own Chapter</span>
            <span class="fs-years">Monte Carlo Group of Companies &middot; Est. 1984</span>
        </div>
        <h3>The Man Behind the Name</h3>

        <div class="fam-story-body">
            <div>
                <p>Rogelio Calderon Guevara was an extraordinary entrepreneur, visionary leader, and philanthropist who dedicated his life to building businesses, empowering communities, and shaping future generations. As the Founder of the Monte Carlo Group of Companies, established in 1984, he transformed a single vision into a respected organization that served clients across the Philippines and internationally.</p>
                <p>What distinguished him was not merely his intelligence, but his unwavering determination, strategic mindset, and exceptional leadership. He began with limited resources and without relying on the support of influential people — through discipline, resilience, and countless sacrifices, he laid the foundation of a company that would later expand into multiple industries and branches throughout the Philippines.</p>
                <p>His path was never easy. While carrying the responsibility of supporting a large family and ensuring his children's education, he also managed demanding business operations and the needs of countless clients. There were moments when the weight of it all nearly forced him to abandon everything he had built. He chose courage over defeat — and that conviction became the cornerstone of everything he later built.</p>
                <p>Beyond business, he devoted himself to humanitarian service — an active member of the Lions Clubs International and the Fraternal Order of Eagles, leading Medical Missions that brought free healthcare to underprivileged Filipino families, and supporting Brigada Eskwela with school supplies for children who needed them most. For Rogelio, success was never measured solely by profit, but by the people he empowered along the way.</p>
            </div>

            <div class="fam-ventures">
                <h5>Ventures Built</h5>
                <div class="fam-venture-item">
                    <div class="fv-name">Monte Carlo Group of Companies</div>
                    <div class="fv-desc">Founded 1984 — expanded across multiple industries and branches nationwide.</div>
                </div>
                <div class="fam-venture-item">
                    <div class="fv-name">Monte Carlo Farm</div>
                    <div class="fv-desc">A resort built through years of dedication, sacrifice, and hard work.</div>
                </div>
                <div class="fam-venture-item">
                    <div class="fv-name">Monte Carlo Executive Villa</div>
                    <div class="fv-desc">An elegant residential community built on comfort, security, and a refined lifestyle.</div>
                </div>
            </div>
        </div>

        <div class="fam-pullquote">
            <p>"My son, always remember that no matter how difficult life becomes — no matter how deeply you feel defeated — never allow hardship to define your future. Instead, transform every setback into the fuel that strengthens your heart and sharpens your determination. Every great leader experiences moments of exhaustion, doubt, and mental struggle. I have faced those challenges myself. What separates successful people is not the absence of hardship, but the courage to continue despite it."</p>
            <div class="fq-attr">— Rogelio Calderon Guevara, to his son</div>
        </div>

        <div class="fam-legacy">
            <h5>How He Shaped Me</h5>
            <p style="font-size:13.5px;line-height:1.85;color:var(--h-muted);font-weight:300;max-width:760px;margin-bottom:22px">Among his children is his son, Prince Edward Guevara — affectionately known as <em style="color:var(--h-champagne);font-style:italic">"Dokdok."</em> My father personally mentored me across the areas below, always insisting that true leadership begins with humility, integrity, and inspiring others through action rather than words. Every principle I carry into AyosCoffeeNegosyo, Noctrune, and my work at Monte Carlo Technologies traces back to lessons he taught me long before I ever wrote a line of code.</p>
            <div class="fam-mentor-grid">
                <div class="fam-mentor-card"><div class="fm-num">01</div><div class="fm-title">Humanitarian & Great Leadership</div></div>
                <div class="fam-mentor-card"><div class="fm-num">02</div><div class="fm-title">Professional Communication</div></div>
                <div class="fam-mentor-card"><div class="fm-num">03</div><div class="fm-title">Strategic Thinking</div></div>
                <div class="fam-mentor-card"><div class="fm-num">04</div><div class="fm-title">Business Development</div></div>
                <div class="fam-mentor-card"><div class="fm-num">05</div><div class="fm-title">Professionalism in Several Aspects</div></div>  
                <div class="fam-mentor-card"><div class="fm-num">06</div><div class="fm-title">Kindness &amp; Respect for Others</div></div>
                <div class="fam-mentor-card"><div class="fm-num">07</div><div class="fm-title">Case Problem Solving</div></div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════ III. SKILLS & STRENGTHS ══════════ -->
<section class="dossier-section" id="skills">
    <div class="ds-head">
        <div class="ds-tag"><span class="ds-num">III.</span><div><span class="ds-eyebrow">Capabilities</span><h2>Skills &amp; Core<br>Competencies</h2></div></div>
        <p class="ds-lede">Technical expertise alone isn't enough — leadership, adaptability, and communication build the rest of the system.</p>
    </div>

    <div class="skills-layout">
        <div>
            <div class="skill-block">
                <h4>Professional Skills</h4>
                <div class="chip-wrap">
                    <span class="chip">Fast &amp; Adaptive Learner</span>
                    <span class="chip">Leadership</span>
                    <span class="chip">Critical Thinking</span>
                    <span class="chip">Tech Strategy</span>
                    <span class="chip">Front-End Development</span>
                    <span class="chip">Back-End Development</span>
                    <span class="chip">UI/UX Design (Trainee)</span>
                    <span class="chip">Web App Development</span>
                    <span class="chip">Video Editing</span>
                    <span class="chip">Digital Media Production</span>
                    <span class="chip">Team Collaboration</span>
                    <span class="chip">Project Planning</span>
                </div>
            </div>
            <div class="skill-block">
                <h4>Programming Languages</h4>
                <div class="chip-wrap">
                    <span class="chip lang">HTML</span>
                    <span class="chip lang">CSS</span>
                    <span class="chip lang">JavaScript</span>
                    <span class="chip lang">PHP</span>
                    <span class="chip lang">jQuery</span>
                    <span class="chip lang">SQL</span>
                    <span class="chip lang">JAVA</span>

                </div>
            </div>
        </div>

        <div class="strength-grid">
            <div class="strength-card"><div class="sc-num">01</div><h5>Problem Solving</h5><p>Analyzing complex challenges and finding practical, scalable solutions through logical, systematic development.</p></div>
            <div class="strength-card"><div class="sc-num">02</div><h5>Continuous Learning</h5><p>Dedicating significant time to studying new technologies, programming concepts, and modern development methodologies.</p></div>
            <div class="strength-card"><div class="sc-num">03</div><h5>Leadership</h5><p>Valuing responsibility, accountability, and teamwork — creating an environment where ideas grow into outcomes.</p></div>
            <div class="strength-card"><div class="sc-num">04</div><h5>Adaptability</h5><p>Adapting quickly to new technologies, workflows, and environments while holding quality as a constant.</p></div>
        </div>
    </div>
</section>

<!-- ══════════ IV. LEADERSHIP & ORGANIZATIONS ══════════ -->
<section class="dossier-section" id="leadership">
    <div class="ds-head">
        <div class="ds-tag"><span class="ds-num">IV.</span><div><span class="ds-eyebrow">In Practice</span><h2>Organizations<br>&amp; Leadership</h2></div></div>
        <p class="ds-lede">From wireframes on a hotel counter to boardroom titles — leadership shown through the actual work.</p>
    </div>

    <div class="lead-grid">
        <div class="lead-photo">
            <img src="images/about-leadership.jpeg" alt="Mapping out system wireframes and UI flows">
            <div class="lead-photo-cap">
                <div class="lc-eyebrow">On Site</div>
                <div class="lc-text">Mapping wireframes and system flows — leadership shown in the details.</div>
            </div>
        </div>
        <div class="org-list">
            <div class="org-item">
                <div class="org-role">Founder &amp; CEO</div>
                <div class="org-body"><h5>AyosCoffeeNegosyo &amp; Noctrune</h5><p>Founder and Chief Executive Officer of both platforms — overseeing planning, system architecture, and continuous development.</p></div>
            </div>
            <div class="org-item">
                <div class="org-role">Tech Lead / Main Programmer</div>
                <div class="org-body"><h5>AyosCoffeeNegosyo &amp; Noctrune </h5><p>Leading system architecture, front-end and back-end development, database structure, and continuous feature planning.</p></div>
            </div>
            <div class="org-item">
                <div class="org-role">Editor Trainee</div>
                <div class="org-body"><h5>Monte Carlo Technologies</h5><p>Video editing, advertisement production, marketing and social media content, and corporate branding videos.</p></div>
            </div>
            <div class="org-item">
                <div class="org-role">Boxing Trainee</div>
                <div class="org-body"><h5>Monte Carlo Fitness Gym</h5><p>Active member building physical fitness, discipline, endurance, and personal resilience.</p></div>
            </div>
            <div class="org-item">
                <div class="org-role">Head Member</div>
                <div class="org-body"><h5>Stoicism Society</h5><p>Promoting continuous learning, discipline, resilience, and philosophical thinking among members.</p></div>
            </div>
            <div class="org-item">
                <div class="org-role">Former Member</div>
                <div class="org-body"><h5>LEO Club</h5><p>Affiliated with Lions Clubs International — community-oriented initiatives and leadership development.</p></div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════ V. VENTURES / PROJECTS ══════════ -->
<section class="dossier-section" id="ventures">
    <div class="ds-head">
        <div class="ds-tag"><span class="ds-num">V.</span><div><span class="ds-eyebrow">Personal Projects</span><h2>Ventures</h2></div></div>
        <p class="ds-lede">Two full business management platforms, founded, architected, and built from the ground up.</p>
    </div>

    <div class="venture">
        <div class="venture-shot">
            <div class="browser-frame">
                <div class="browser-bar">
                    <span class="browser-dot r"></span><span class="browser-dot y"></span><span class="browser-dot g"></span>
                    <span class="browser-url">ayoscoffeenegosyo.com/menu</span>
                </div>
                <img class="browser-shot" src="images/about-ayoscoffee.png" alt="AyosCoffeeNegosyo menu interface">
            </div>
        </div>
        <div class="venture-copy">
            <div class="venture-eyebrow">Project I <span class="venture-role">&middot; Founder &amp; Tech Lead</span></div>
            <h3>AyosCoffeeNegosyo</h3>
            <p>A centralized coffee business management platform built to replace fragmented manual processes with one integrated system — designed for owners who need real visibility into daily operations.</p>
            <ul class="venture-features">
                <li>User account management</li>
                <li>Inventory &amp; stock tracking</li>
                <li>Employee attendance</li>
                <li>Payroll processing</li>
                <li>Online product showcase</li>
                <li>Centralized operations data</li>
            </ul>
        </div>
    </div>

    <div class="venture reverse">
        <div class="venture-shot">
            <div class="browser-frame">
                <div class="browser-bar">
                    <span class="browser-dot r"></span><span class="browser-dot y"></span><span class="browser-dot g"></span>
                    <span class="browser-url">nocturnemanilabay.com</span>
                </div>
                <img class="browser-shot" src="images/about-noctrune.png" alt="Noctrune hotel management homepage">
            </div>
        </div>
        <div class="venture-copy">
            <div class="venture-eyebrow">Project II <span class="venture-role">&middot; Founder &amp; Tech Lead</span></div>
            <h3>Noctrune</h3>
            <p>A comprehensive hotel management system streamlining operations through one centralized platform — from reservations to housekeeping, replacing fragmented manual workflows with organized, reliable software.</p>
            <ul class="venture-features">
                <li>Guest reservations &amp; check-in/out</li>
                <li>Room availability &amp; housekeeping</li>
                <li>Employee &amp; payroll management</li>
                <li>Inventory &amp; supply tracking</li>
                <li>Occupancy &amp; revenue reporting</li>
                <li>Guest records &amp; booking history</li>
            </ul>
        </div>
    </div>

        <div class="venture">
        <div class="venture-shot">
            <div class="browser-frame">
                <div class="browser-bar">
                    <span class="browser-dot r"></span><span class="browser-dot y"></span><span class="browser-dot g"></span>
                    <span class="browser-url">ayoscoffeenegosyo.com/menu</span>
                </div>
                <img class="browser-shot" src="images/about-the-atelier-noir.png" alt="The Atelier Noir menu interface">
            </div>
        </div>
        <div class="venture-copy">
            <div class="venture-eyebrow">Project III <span class="venture-role">&middot; Founder &amp; Tech Lead</span></div>
            <h3>The Atelier Noir</h3>
            <p>A modern fashion and essentials platform created to bring clothing, accessories, collections, and everyday essentials into one refined digital experience — giving customers a seamless way to discover their style, explore curated collections, and shop products while giving the brand a strong platform to showcase its identity.</p>
            <ul class="venture-features">
                <li>Curated Fashion Collections</li>
                <li>Personalized &amp; Style Discovering</li>
                <li>Clothing & Essentials Marketplace</li>
                <li>Wishlist & Saved Items</li>
                <li>Online product showcase</li>
                <li>Shop-the-Look Experience</li>
            </ul>
        </div>
    </div>
</section>

<!-- ══════════ VI. MONTE CARLO TECHNOLOGIES ══════════ -->
<section class="dossier-section" id="montecarlo">
    <div class="ds-head">
        <div class="ds-tag"><span class="ds-num">VI.</span><div><span class="ds-eyebrow">Professional Home</span><h2>Monte Carlo<br>Technologies</h2></div></div>
        <p class="ds-lede">An early professional environment that continues to sharpen both the technical and creative sides of the craft.</p>
    </div>

    <div class="mc-panel">
        <div class="mc-shot">
            <div class="browser-frame">
                <div class="browser-bar">
                    <span class="browser-dot r"></span><span class="browser-dot y"></span><span class="browser-dot g"></span>
                    <span class="browser-url">montecarlotechnologies.com</span>
                </div>
                <img class="browser-shot" src="images/about-montecarlo.png" alt="Monte Carlo Technologies website featuring Prince Edward as Editor Trainee">
            </div>
        </div>
        <div class="mc-lower">
            <div class="mc-copy">
                <div class="mc-eyebrow">Currently Serving As</div>
                <h3>Editor Trainee &amp; UI/UX Trainee</h3>
            </div>
            <div class="mc-copy">
                <p>Working within a professional environment at Monte Carlo Technologies has strengthened both technical and creative abilities — from video editing techniques to professional work ethics, communication, and a sharper understanding of business branding.</p>
                <div class="mc-roles">
                    <div class="mc-role-line"><b>Editor Trainee</b><span>Video editing, advertisements, marketing &amp; social content, corporate branding.</span></div>
                    <div class="mc-role-line"><b>UI/UX Trainee</b><span>Front-end development and user experience improvements for responsive web design.</span></div>
                    <div class="mc-role-line"><b>Member</b><span>Contributing to technology-related projects across the organization.</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════ VII. VISION & PHILOSOPHY ══════════ -->
<section class="dossier-section" id="vision">
    <div class="ds-head">
        <div class="ds-tag"><span class="ds-num">VII.</span><div><span class="ds-eyebrow">Looking Ahead</span><h2>Vision &amp;<br>Philosophy</h2></div></div>
        <p class="ds-lede">Success built through discipline, consistency, humility, and continuous self-improvement.</p>
    </div>

    <div class="vision-wrap">
        <p class="vision-quote">Every challenge presents an opportunity to learn. Every mistake becomes a valuable lesson. Every project represents another step toward excellence.</p>
        <div class="vision-attr">Professional Philosophy</div>

        <div class="goals-grid">
            <div class="goal-cell"><span class="gc-num">01</span><p>Become a professional Software Engineer</p></div>
            <div class="goal-cell"><span class="gc-num">02</span><p>Develop enterprise-level business systems</p></div>
            <div class="goal-cell"><span class="gc-num">03</span><p>Expand full-stack development expertise</p></div>
            <div class="goal-cell"><span class="gc-num">04</span><p>Strengthen leadership &amp; project management</p></div>
            <div class="goal-cell"><span class="gc-num">05</span><p>Keep learning emerging technologies</p></div>
            <div class="goal-cell"><span class="gc-num">06</span><p>Build scalable, real-world web apps</p></div>
            <div class="goal-cell"><span class="gc-num">07</span><p>Collaborate with innovation-driven teams</p></div>
            <div class="goal-cell"><span class="gc-num">08</span><p>Create technology with lasting impact</p></div>
        </div>
    </div>
</section>

<!-- ══════════ VIII. CONTACT ══════════ -->
<section class="dossier-section" id="contact" style="border-bottom:1px solid var(--h-line)">
    <div class="ds-head">
        <div class="ds-tag"><span class="ds-num">VIII.</span><div><span class="ds-eyebrow">Say Hello</span><h2>Contact<br>Information</h2></div></div>
        <p class="ds-lede">Open to collaboration, networking, and opportunities that value innovation.</p>
    </div>

    <div class="contact-panel">
        <a href="https://github.com/guevaraprinceedward/" target="_blank" rel="noopener" class="contact-cell">
            <span class="cc-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.58 2 12.25c0 4.53 2.87 8.37 6.84 9.73.5.1.68-.22.68-.5 0-.24-.01-1.04-.01-1.89-2.78.61-3.37-1.2-3.37-1.2-.45-1.18-1.11-1.5-1.11-1.5-.9-.63.07-.62.07-.62 1 .07 1.53 1.05 1.53 1.05.89 1.56 2.34 1.11 2.91.85.09-.66.35-1.11.63-1.37-2.22-.26-4.56-1.14-4.56-5.06 0-1.12.39-2.03 1.03-2.75-.1-.26-.45-1.31.1-2.73 0 0 .84-.28 2.75 1.05a9.34 9.34 0 0 1 5 0c1.91-1.33 2.75-1.05 2.75-1.05.55 1.42.2 2.47.1 2.73.64.72 1.03 1.63 1.03 2.75 0 3.93-2.35 4.79-4.58 5.05.36.32.68.94.68 1.9 0 1.37-.01 2.47-.01 2.81 0 .28.18.61.69.5A10.26 10.26 0 0 0 22 12.25C22 6.58 17.52 2 12 2z"/></svg></span>
            <span class="cc-label">GitHub</span>
            <span class="cc-handle">guevaraprinceedward</span>
            <span class="cc-sub">github.com/guevaraprinceedward</span>
        </a>
        <a href="https://www.facebook.com/guevaraprinceedward.01/" target="_blank" rel="noopener" class="contact-cell">
            <span class="cc-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5 3.66 9.15 8.44 9.94v-7.03H7.9v-2.9h2.54V9.85c0-2.5 1.5-3.89 3.79-3.89 1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.87h2.78l-.44 2.9h-2.34V22c4.78-.79 8.44-4.93 8.44-9.94z"/></svg></span>
            <span class="cc-label">Facebook</span>
            <span class="cc-handle">Prince Edward Guevara</span>
            <span class="cc-sub">facebook.com/guevaraprinceedward.01</span>
        </a>
        <a href="https://www.instagram.com/_thepresence.noir/" target="_blank" rel="noopener" class="contact-cell">
            <span class="cc-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1"/></svg></span>
            <span class="cc-label">Instagram</span>
            <span class="cc-handle">@_thepresence.noir</span>
            <span class="cc-sub">instagram.com/_thepresence.noir</span>
        </a>
    </div>
</section>

<p class="closing-line">"This portfolio represents not only the projects I have built, but the values that guide the work — integrity, discipline, continuous learning, and a genuine passion for technology."</p>

</div><!-- /.dossier -->

<footer><p>© 2026 <span>Prince Edward Guevara</span> — Portfolio &amp; Professional Dossier.</p></footer>

<script>
// Scroll-spy for the index rail
const railLinks = document.querySelectorAll('#indexRail a');
const sections = Array.from(railLinks).map(a => document.getElementById(a.dataset.target)).filter(Boolean);

function updateRail(){
    let current = sections[0]?.id;
    const trigger = window.innerHeight * 0.4;
    sections.forEach(sec => {
        const rect = sec.getBoundingClientRect();
        if (rect.top <= trigger) current = sec.id;
    });
    railLinks.forEach(a => a.classList.toggle('active', a.dataset.target === current));
}
window.addEventListener('scroll', updateRail, { passive: true });
updateRail();

railLinks.forEach(a => {
    a.addEventListener('click', (e) => {
        e.preventDefault();
        document.getElementById(a.dataset.target)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});
</script>
</body>
</html>
