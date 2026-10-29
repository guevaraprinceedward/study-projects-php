<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/school-art.php';   // SCHOOL_LOGO

// ── SESSION (same as index.php) ───────────────────────────────────────────
$user       = getLoggedInUser($conn);
$isLoggedIn = $user !== null;
$firstName  = $isLoggedIn ? ($user['first_name'] ?? '') : '';

// ── CLIENT TESTIMONIALS (table: personal_testimonials) ────────────────────
// Optional: if the table isn't in canteen_cmdi_db yet, the section just doesn't render
// (mysqli is in strict mode, so the query is wrapped in try/catch).
$testimonials = [];
try {
    $ttr = $conn->query("SELECT client_name, client_location, avatar_url, rating, testimonial, venture
                          FROM personal_testimonials
                          WHERE is_active = 1
                          ORDER BY sort_order ASC, id ASC");
    if ($ttr) {
        while ($row = $ttr->fetch_assoc()) {
            $row['rating'] = max(1, min(5, (int)$row['rating']));
            $testimonials[] = $row;
        }
    }
} catch (Throwable $e) {
    $testimonials = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About the Founder — Prince Edward Guevara · COPS</title>
<link rel="icon" href="<?= SCHOOL_LOGO ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=EB+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&display=swap" rel="stylesheet">
<style>
/* ══════════════════════════════════════════════════════════════
   ABOUT-US — "The Forum" · Roman / Greek marble, white theme
   Intro: temple gates (meander seam + laurel) that swing open.
   ══════════════════════════════════════════════════════════════ */
:root{
  --m:#faf8f2; --m2:#f1ece0; --stone:#e5dcc6; --ink:#1c1f2a; --mute:#5b6070;
  --gold:#8a6a1f; --gold2:#b08d3c; --navy:#13294b; --navy2:#1f3d6e;
  --f-disp:'Cinzel',serif; --f-body:'EB Garamond',Georgia,serif; --f-ui:'Cinzel',serif;
  --key:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24'%3E%3Cpath d='M3 21V3h18v14H9V9h6' fill='none' stroke='%23b08d3c' stroke-width='1.6' stroke-linecap='square'/%3E%3C/svg%3E");
  --vein:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='700' height='700'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.008 .018' numOctaves='6' seed='7'/%3E%3CfeColorMatrix values='0 0 0 0 .52 0 0 0 0 .46 0 0 0 0 .38 0 0 0 -2.2 1.05'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.42'/%3E%3C/svg%3E");
  color:var(--ink);font-family:var(--f-body);font-size:18px;position:relative}
html{scroll-behavior:smooth}
body{margin:0;background:var(--m) !important;color:var(--ink);font-family:var(--f-body);font-size:18px;line-height:1.6;-webkit-font-smoothing:antialiased;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;background:var(--vein);pointer-events:none;z-index:0}
body.gate-lock{overflow:hidden}
*,*::before,*::after{box-sizing:border-box}

/* ══ TEMPLE GATE — Greek temple facade, hinged marble doors, walk-through ══ */
:root{--ease:cubic-bezier(.2,.7,.2,1)}
@keyframes spin{to{transform:rotate(360deg)}}
.gate{position:fixed;inset:0;z-index:99999;overflow:hidden;background:radial-gradient(ellipse 95% 75% at 50% 64%,#fffaf0 0,#f2e6c6 36%,#d9c598 68%,#a98f54 100%)}
.gate.done{display:none}
.gate.enter{opacity:0;transition:opacity 1.5s ease .5s}
.rays{position:absolute;left:50%;top:62%;width:230vmax;height:230vmax;margin:-115vmax 0 0 -115vmax;background:repeating-conic-gradient(rgba(255,244,205,.36) 0 3deg,transparent 3deg 9deg);-webkit-mask:radial-gradient(circle,#000 0,transparent 38%);mask:radial-gradient(circle,#000 0,transparent 38%);animation:spin 120s linear infinite}
.facade{position:absolute;inset:0;transform-origin:50% 56%;transition:transform 2s cubic-bezier(.75,0,.2,1),opacity 1.4s ease .3s}
.gate.enter .facade{transform:scale(3.2);opacity:0}
.ped{position:absolute;top:4vh;left:50%;width:min(94vw,1120px);height:22vh;transform:translateX(-50%);filter:drop-shadow(0 12px 18px rgba(60,40,10,.38))}
.ped .o,.ped .i{position:absolute;inset:0}
.ped .o{clip-path:polygon(0 100%,50% 0,100% 100%);background:linear-gradient(180deg,#e2c06c,#8a6a1f)}
.ped .i{clip-path:polygon(2.4% calc(100% - 3px),50% 9%,97.6% calc(100% - 3px));background:var(--vein),linear-gradient(180deg,#fffdf6,#eadfc2);background-color:#f6efdc}
.emb{position:absolute;left:50%;bottom:5%;width:min(17vh,150px);aspect-ratio:1;transform:translateX(-50%)}
.emb svg{width:100%;height:100%;display:block;filter:drop-shadow(0 3px 4px rgba(90,65,20,.35))}
.emb .gate-logo{position:absolute;left:50%;top:52%;width:34%;aspect-ratio:1;transform:translate(-50%,-50%);border-radius:50%;background:#fff;padding:2px;box-shadow:0 0 0 2px var(--gold2)}
.ent{position:absolute;top:26vh;left:50%;width:min(98vw,1180px);height:8vh;transform:translateX(-50%);box-shadow:0 14px 26px rgba(60,40,10,.35)}
.ent .arc{height:3.2vh;display:flex;align-items:center;justify-content:center;padding-left:.5em;font-family:var(--f-disp);font-size:clamp(7px,1.5vh,13px);letter-spacing:.5em;color:var(--gold);background:linear-gradient(180deg,#fffdf6,#e6dbbd);border:1px solid var(--gold2);white-space:nowrap;overflow:hidden}
.ent .frz{height:3.4vh;background:var(--key) repeat-x center/2.4vh 2.4vh,linear-gradient(180deg,#f3ebd3,#dfd2ae);border:1px solid var(--gold2);border-top:0}
.ent .cor{height:1.4vh;margin:0 -1.2%;background:linear-gradient(180deg,#fffdf6,#cdbf9a);border:1px solid var(--gold)}
.cl{position:absolute;top:34vh;bottom:9vh;left:50%;width:min(94vw,1100px);transform:translateX(-50%);display:flex;align-items:stretch;justify-content:space-between}
.cg{display:flex;flex:1;justify-content:space-around}
.col{display:flex;flex-direction:column;width:clamp(34px,5.8vw,74px)}
.col .cap{height:2.6vh;margin:0 -14%;background:linear-gradient(180deg,#fffdf6,#d8cba8);border:1px solid #9a7a30;border-radius:3px 3px 0 0;box-shadow:0 3px 6px rgba(60,40,10,.3)}
.col .sh{flex:1;clip-path:polygon(5% 0,95% 0,100% 100%,0 100%);background:repeating-linear-gradient(90deg,rgba(80,60,25,.16) 0 1px,transparent 1px 6px,rgba(255,255,255,.55) 6px 7px,transparent 7px 11px),linear-gradient(90deg,#a99c7c 0,#e9e0c8 14%,#fffdf6 38%,#f2ead6 62%,#c9bc9b 88%,#8f8262 100%)}
.col .bs{height:2.2vh;margin:0 -12%;background:linear-gradient(180deg,#d8cba8,#fffdf6);border:1px solid #9a7a30;border-radius:0 0 3px 3px;box-shadow:0 5px 8px rgba(60,40,10,.3)}
.dw{position:relative;flex:0 0 min(42%,500px);margin:0 1.5vw;perspective:1500px;border:clamp(4px,.8vw,9px) solid transparent;border-image:linear-gradient(180deg,#f0d78e,#8a6a1f) 1;box-shadow:0 0 40px rgba(60,40,10,.35)}
.lt{position:absolute;inset:0;background:radial-gradient(ellipse 70% 80% at 50% 62%,#fffef6 0,#fff3c4 34%,#efd27c 64%,#b98d2f 100%);filter:brightness(.78);transition:filter 2.2s ease .2s}
.gate.open .lt{filter:brightness(1.2)}
.lf{position:absolute;top:0;bottom:0;width:50%;transform-style:preserve-3d;transition:transform 2.6s cubic-bezier(.62,0,.22,1) .1s;padding:6% 9%;display:grid;grid-template-rows:repeat(3,1fr);gap:4%;
  background:var(--vein),linear-gradient(180deg,#fcf9f0,#eadfc3);background-color:#f4edda;box-shadow:inset 0 0 0 4px #f8f2e1,inset 0 0 0 5px #b08d3c,inset 0 0 36px rgba(120,90,30,.3)}
.lf.l{left:0;transform-origin:left center}.lf.r{right:0;transform-origin:right center}
.gate.open .lf.l{transform:rotateY(86deg)}.gate.open .lf.r{transform:rotateY(-86deg)}
.lf::before{content:'';position:absolute;top:0;bottom:0;width:10px;background:var(--key) repeat-y center/10px 10px,#f3e7c0}
.lf.l::before{right:0}.lf.r::before{left:0}
.lf::after{content:'';position:absolute;inset:0;background:linear-gradient(90deg,rgba(24,14,0,0),rgba(24,14,0,.62));opacity:0;transition:opacity 2.6s ease .1s;pointer-events:none}
.lf.r::after{background:linear-gradient(270deg,rgba(24,14,0,0),rgba(24,14,0,.62))}
.gate.open .lf::after{opacity:1}
.lf .pn{--st:#f6df9b 0 2px,#8a6a1f 3px,transparent 4.5px;position:relative;border:1px solid var(--gold2);box-shadow:inset 3px 3px 8px rgba(120,90,30,.3),inset -3px -3px 7px rgba(255,255,255,.85);
  background:radial-gradient(circle at 9px 9px,var(--st)),radial-gradient(circle at calc(100% - 9px) 9px,var(--st)),radial-gradient(circle at 9px calc(100% - 9px),var(--st)),radial-gradient(circle at calc(100% - 9px) calc(100% - 9px),var(--st)),var(--key) center/14px 14px no-repeat}
.lf .hd{position:absolute;top:50%;width:3.2vh;height:3.2vh;min-width:22px;min-height:22px;margin-top:-1.6vh;border-radius:50%;border:4px solid #c9a24a;background:radial-gradient(circle at 35% 30%,#fff3c4,#b08d3c 60%,#6a4f14);box-shadow:0 3px 8px rgba(0,0,0,.45);z-index:2}
.lf.l .hd{right:5%}.lf.r .hd{left:5%}
.stp{position:absolute;left:50%;bottom:0;width:min(104vw,1220px);height:9vh;transform:translateX(-50%);clip-path:polygon(3% 0,97% 0,100% 100%,0 100%);background:repeating-linear-gradient(180deg,#fffdf6 0 2.4vh,#cdbf9a 2.4vh 2.8vh)}
.gb{position:absolute;left:50%;bottom:2.6vh;width:min(60vw,240px);height:2px;transform:translateX(-50%);background:rgba(138,106,31,.3);overflow:hidden;z-index:3}
.gb i{display:block;height:100%;width:0;background:linear-gradient(90deg,var(--gold),#f0d78e);transition:width 2.5s cubic-bezier(.3,.6,.2,1)}
.gate.go .gb i{width:100%}
@media(max-width:760px){.cg .col:first-child{display:none}.dw{flex-basis:58%}.cg{justify-content:center}}

/* ══ PAGE ══ */
.progress{position:fixed;top:0;left:0;height:3px;width:0;z-index:300;background:linear-gradient(90deg,var(--gold2),#f0d78e)}
.stage{opacity:0;transform:translateY(22px);transition:opacity 1.3s ease,transform 1.5s var(--ease)}
body.revealed .stage{opacity:1;transform:none}
.dossier{position:relative;z-index:1;max-width:1240px;margin:0 auto;padding:0 32px}
.rv{opacity:0}.rv.in{opacity:1;animation:rise .95s var(--ease) backwards}
@keyframes rise{from{opacity:0;transform:translateY(28px)}}

/* index rail */
.index-rail{position:fixed;top:50%;left:20px;transform:translateY(-50%);z-index:60;display:flex;flex-direction:column;gap:2px;opacity:0;transition:opacity 1.2s ease .4s}
body.revealed .index-rail{opacity:1}
.index-rail a{display:flex;align-items:center;gap:10px;text-decoration:none;padding:6px 0}
.index-rail .ir-num{font-family:var(--f-disp);font-size:10px;color:var(--stone);width:26px;text-align:right;transition:color .3s}
.index-rail .ir-bar{width:12px;height:1px;background:var(--stone);transition:all .35s var(--ease)}
.index-rail .ir-label{font-family:var(--f-ui);font-size:9.5px;letter-spacing:.14em;text-transform:uppercase;color:transparent;white-space:nowrap;max-width:0;overflow:hidden;transition:all .35s}
.index-rail a:hover .ir-label,.index-rail a.active .ir-label{color:var(--mute);max-width:160px;margin-left:2px}
.index-rail a.active .ir-num{color:var(--gold)}
.index-rail a.active .ir-bar{width:26px;background:var(--gold)}
@media(max-width:1380px){.index-rail{display:none}}

/* marble slab · frieze · buttons · cursor spotlight */
.slab{background:linear-gradient(155deg,#fffdf9,var(--m2));border:1px solid var(--stone);border-top:2px solid var(--gold2);box-shadow:inset 0 1px 0 #fff,0 18px 40px -22px rgba(19,41,75,.35);position:relative}
.slab::before{content:'';position:absolute;inset:5px;border:1px solid rgba(176,141,60,.18);pointer-events:none}
.slab::after,.fam-mentor-card::after,.goal-cell::after,.contact-cell::after,.collab-card::after,.chip::after,.org-item::after{content:'';position:absolute;inset:0;background:radial-gradient(340px circle at var(--mx,50%) var(--my,50%),rgba(201,164,92,.2),transparent 60%);opacity:0;transition:opacity .4s;pointer-events:none}
.slab:hover::after,.fam-mentor-card:hover::after,.goal-cell:hover::after,.contact-cell:hover::after,.collab-card:hover::after,.chip:hover::after,.org-item:hover::after{opacity:1}
.chip,.org-item,.goal-cell,.contact-cell,.fam-mentor-card{position:relative}
.frieze{height:24px;background:var(--key) repeat-x center/24px 24px;opacity:.7}
.btn-gold,.btn-line{display:inline-flex;align-items:center;gap:10px;font-family:var(--f-disp);font-size:11.5px;letter-spacing:.16em;text-transform:uppercase;text-decoration:none;transition:transform .35s var(--ease),box-shadow .35s,background .3s,color .3s}
.btn-gold{padding:14px 28px;color:#1a1400;font-weight:700;background:linear-gradient(180deg,#f0d78e,#c9a24a 55%,#a9822c);border:1px solid #d9b45a;box-shadow:0 12px 22px -12px rgba(138,106,31,.85)}
.btn-gold:hover{transform:translateY(-3px);box-shadow:0 18px 28px -12px rgba(138,106,31,.9)}
.btn-line{padding:13px 28px;color:var(--gold);border:1px solid var(--gold)}
.btn-line:hover{background:var(--gold);color:#fff;transform:translateY(-3px)}
[data-tilt]{transition:transform .5s var(--ease);will-change:transform}

/* hero — text left, arched portrait right */
.dh{position:relative;z-index:1;min-height:calc(100vh - 118px);display:flex;align-items:center;padding:56px 32px 80px;overflow:hidden}
.dh::before{content:'';position:absolute;right:-10%;top:50%;width:min(70vw,900px);aspect-ratio:1;transform:translateY(-50%);background:radial-gradient(circle,rgba(240,215,142,.42),transparent 62%);pointer-events:none}
.dh-inner{position:relative;max-width:1240px;width:100%;margin:0 auto;display:grid;grid-template-columns:1.1fr .9fr;gap:80px;align-items:center}
.arch-wrap{order:2;position:relative;max-width:440px;margin-left:auto;width:100%}
.arch-wrap::before,.arch-wrap::after{content:'';position:absolute;border:1px solid var(--gold2);border-radius:999px 999px 6px 6px;pointer-events:none}
.arch-wrap::before{inset:-18px;opacity:.7;animation:breathe 6s ease-in-out infinite}
.arch-wrap::after{inset:-36px;opacity:.35;animation:breathe 6s ease-in-out 1.2s infinite}
@keyframes breathe{50%{transform:scale(1.025);opacity:.2}}
.dh-photo{position:relative;aspect-ratio:4/5.3;border-radius:999px 999px 4px 4px;overflow:hidden;border:7px solid #f6f1e3;outline:1px solid var(--gold);box-shadow:0 40px 70px -24px rgba(19,41,75,.5),inset 0 0 50px rgba(0,0,0,.18)}
.dh-photo img{width:100%;height:100%;object-fit:cover;object-position:top center;filter:sepia(.1) contrast(1.04);transition:transform 1.4s var(--ease)}
.dh-photo:hover img{transform:scale(1.05)}
.dh-photo-tag{position:absolute;left:0;right:0;bottom:0;padding:40px 10px 16px;text-align:center;font-family:var(--f-disp);font-size:11px;letter-spacing:.26em;color:#fff;text-transform:uppercase;background:linear-gradient(transparent,rgba(19,41,75,.7))}
.dh-eyebrow{font-family:var(--f-disp);font-size:12px;letter-spacing:.4em;text-transform:uppercase;color:var(--gold);margin-bottom:20px}
.dh-name{font-family:var(--f-disp);font-size:clamp(44px,6.4vw,92px);font-weight:600;line-height:1.02;color:var(--navy);letter-spacing:.015em;margin-bottom:24px;text-shadow:0 1px 0 #fff}
.dh-roles{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:26px}
.dh-role-pill{font-family:var(--f-ui);font-size:10.5px;letter-spacing:.12em;text-transform:uppercase;color:var(--mute);border:1px solid var(--stone);padding:7px 14px;background:rgba(255,255,255,.75);transition:border-color .3s,color .3s}
.dh-role-pill:hover{border-color:var(--gold);color:var(--gold)}
.dh-quote{font-style:italic;font-size:22px;line-height:1.6;color:var(--mute);max-width:540px;margin-bottom:32px;border-left:2px solid var(--gold2);padding-left:20px}
.dh-cta{display:flex;gap:12px;flex-wrap:wrap}
.dh-meta-row{display:grid;grid-template-columns:repeat(4,auto);justify-content:start;margin-top:44px;border:1px solid var(--stone);background:rgba(255,255,255,.7)}
.dh-meta-item{padding:16px 24px;border-right:1px solid var(--stone)}
.dh-meta-item:last-child{border-right:0}
.dh-meta-item .mk{font-family:var(--f-ui);font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:var(--gold);margin-bottom:4px}
.dh-meta-item .mv{font-size:19px;color:var(--navy)}
@media(max-width:980px){.dh{min-height:0;padding:44px 20px 50px}.dh-inner{grid-template-columns:1fr;gap:64px}.arch-wrap{order:0;max-width:320px;margin:20px auto 0}.dh-meta-row{grid-template-columns:1fr 1fr}.dh-meta-item{border-bottom:1px solid var(--stone)}}

/* chapters — sticky carved heading on the left, content on the right */
.dossier-section{display:grid;grid-template-columns:minmax(250px,.34fr) 1fr;gap:72px;padding:110px 0 90px;position:relative}
.ds-head{position:sticky;top:112px;align-self:start}
.ds-num{display:block;font-family:var(--f-disp);font-size:clamp(70px,8vw,118px);font-weight:600;line-height:.95;letter-spacing:.02em;color:transparent;-webkit-text-stroke:1.2px var(--gold2);margin-bottom:10px}
.ds-eyebrow{display:block;font-family:var(--f-ui);font-size:11px;letter-spacing:.3em;text-transform:uppercase;color:var(--gold);margin-bottom:8px}
.ds-head h2{font-family:var(--f-disp);font-size:clamp(26px,2.9vw,38px);font-weight:600;color:var(--navy);line-height:1.18;letter-spacing:.04em;text-shadow:0 1px 0 #fff}
.ds-lede{font-style:italic;font-size:19px;line-height:1.65;color:var(--mute);margin-top:14px}
.ds-head::after{content:'';display:block;width:170px;height:14px;margin-top:24px;background:var(--key) repeat-x left center/14px 14px;opacity:.8}
.ds-body{min-width:0}
@media(max-width:980px){.dossier-section{grid-template-columns:1fr;gap:34px;padding:80px 0 60px}.ds-head{position:static}}

/* profile */
.profile-copy{display:grid;grid-template-columns:1fr;gap:34px}
.profile-copy p{font-size:20px;line-height:1.85;margin-bottom:20px}
.profile-copy p:first-of-type::first-letter{font-family:var(--f-disp);font-size:76px;font-weight:600;color:var(--gold);float:left;line-height:.8;padding:8px 14px 0 0}
.info-card{padding:30px 32px}
.info-card h4{font-family:var(--f-disp);font-size:14px;letter-spacing:.16em;text-transform:uppercase;color:var(--gold);margin-bottom:10px}
.info-card{display:grid;grid-template-columns:1fr 1fr;column-gap:40px}
.info-card h4{grid-column:1/-1}
.info-row{display:flex;justify-content:space-between;gap:10px;padding:13px 0;border-bottom:1px solid var(--stone);font-size:17px}
.info-row .ik{color:var(--mute);font-family:var(--f-ui);font-size:11.5px;letter-spacing:.08em;align-self:center}
.info-row .iv{text-align:right;color:var(--navy)}
@media(max-width:620px){.info-card{grid-template-columns:1fr}}

/* family */
.family-grid{display:grid;grid-template-columns:.8fr 1.2fr;gap:44px;align-items:center}
.bust{position:relative;aspect-ratio:4/5.1;border:7px solid #f6f1e3;outline:1px solid var(--gold);box-shadow:0 36px 60px -26px rgba(19,41,75,.5);overflow:hidden}
.bust img{width:100%;height:100%;object-fit:cover;object-position:50% 22%;filter:sepia(.08);transition:transform 1.4s var(--ease)}
.bust:hover img{transform:scale(1.05)}
.bust-cap{position:absolute;left:0;right:0;bottom:0;padding:34px 10px 14px;text-align:center;font-family:var(--f-disp);font-size:11px;letter-spacing:.22em;text-transform:uppercase;color:#fff;background:linear-gradient(transparent,rgba(19,41,75,.75))}
.fam-copy h3{font-family:var(--f-disp);font-size:30px;letter-spacing:.06em;margin-bottom:4px;color:var(--navy)}
.fam-name{font-family:var(--f-ui);font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:var(--gold);margin-bottom:22px}
.fam-tags{display:flex;flex-wrap:wrap;gap:8px}
.fam-tag{font-size:15px;border:1px solid var(--stone);padding:6px 14px;background:rgba(255,255,255,.8)}
.fam-mother{margin-top:26px;padding-top:22px;border-top:1px solid var(--stone);display:flex;gap:16px;align-items:baseline}
.fm-label{font-family:var(--f-ui);font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:var(--gold)}
.fm-val{font-size:20px}
.fam-story{margin-top:70px;padding-top:50px;border-top:1px solid var(--stone)}
.fam-story-head{display:flex;gap:14px;align-items:baseline;flex-wrap:wrap}
.fs-eyebrow{font-family:var(--f-ui);font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:var(--gold)}
.fs-years{font-style:italic;color:var(--mute)}
.fam-story h3{font-family:var(--f-disp);font-size:32px;letter-spacing:.05em;margin:6px 0 28px;color:var(--navy)}
.fam-story-body{display:grid;grid-template-columns:1fr;gap:36px}
.fam-story-body>div:first-child{columns:2;column-gap:44px}
.fam-story-body p{font-size:18px;line-height:1.78;margin-bottom:18px;break-inside:avoid-column}
.fam-story-body>div:first-child p:first-child::first-letter{font-family:var(--f-disp);font-size:60px;font-weight:600;color:var(--gold);float:left;line-height:.8;padding:6px 10px 0 0}
.fam-ventures{padding:26px 28px 8px;display:grid;grid-template-columns:repeat(3,1fr);column-gap:26px}
.fam-ventures h5{grid-column:1/-1;font-family:var(--f-disp);font-size:13px;letter-spacing:.16em;text-transform:uppercase;color:var(--gold);margin-bottom:8px}
.fam-venture-item{padding:12px 0 18px}
.fv-name{font-family:var(--f-disp);font-size:14px;letter-spacing:.04em;margin-bottom:4px;color:var(--navy)}
.fv-desc{font-size:16px;color:var(--mute);line-height:1.5}
.fam-pullquote{position:relative;margin:50px 0;padding:44px 48px 40px 78px;background:linear-gradient(160deg,rgba(201,164,92,.14),rgba(255,255,255,.5));border:1px solid var(--stone);border-left:3px solid var(--gold)}
.fam-pullquote::before{content:'\201C';position:absolute;top:0;left:20px;font-family:var(--f-disp);font-size:130px;line-height:1;color:var(--gold2);opacity:.45}
.fam-pullquote p{font-style:italic;font-size:22px;line-height:1.7;margin-bottom:14px}
.fq-attr{font-family:var(--f-ui);font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:var(--gold)}
.fam-legacy h5{font-family:var(--f-disp);font-size:15px;letter-spacing:.14em;text-transform:uppercase;color:var(--gold);margin-bottom:16px}
.fam-legacy .intro{font-size:19px;line-height:1.8;color:var(--mute);margin-bottom:24px}
.fam-legacy .intro em{color:var(--ink)}
.fam-mentor-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
.fam-mentor-card{padding:17px 18px 17px 46px;border:1px solid var(--stone);background:rgba(255,255,255,.75);font-family:var(--f-disp);font-size:13px;letter-spacing:.03em;color:var(--navy);transition:border-color .3s,transform .4s var(--ease)}
.fam-mentor-card::before{content:'';position:absolute;left:17px;top:50%;width:14px;height:14px;margin-top:-7px;background:var(--key) center/14px 14px}
.fam-mentor-card:hover{border-color:var(--gold);transform:translateY(-3px)}
@media(max-width:820px){.family-grid{grid-template-columns:1fr}.bust{max-width:320px}.fam-story-body>div:first-child{columns:1}.fam-ventures{grid-template-columns:1fr}.fam-pullquote{padding:40px 24px 30px 24px}}
@media(max-width:560px){.fam-mentor-grid{grid-template-columns:1fr}}

/* skills */
.skills-layout{display:grid;grid-template-columns:1fr;gap:34px}
.skill-block h4{font-family:var(--f-disp);font-size:14px;letter-spacing:.16em;text-transform:uppercase;color:var(--gold);margin-bottom:16px}
.chip-wrap{display:flex;flex-wrap:wrap;gap:9px;margin-bottom:34px}
.chip{font-size:16px;border:1px solid var(--stone);padding:6px 16px;background:rgba(255,255,255,.85);transition:all .35s var(--ease)}
.chip:hover{border-color:var(--gold);transform:translateY(-3px);box-shadow:0 10px 16px -10px rgba(138,106,31,.6)}
.chip.lang{color:var(--gold);border-color:var(--gold2);font-family:var(--f-disp);font-size:13px;letter-spacing:.05em}
.strength-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.strength-card{padding:28px 24px;transition:transform .4s var(--ease),box-shadow .4s}
.strength-card:hover{transform:translateY(-4px);box-shadow:inset 0 1px 0 #fff,0 26px 40px -22px rgba(19,41,75,.45)}
.strength-card h5{font-family:var(--f-disp);font-size:15px;letter-spacing:.06em;margin-bottom:8px;color:var(--navy)}
.strength-card p{font-size:17px;color:var(--mute);line-height:1.6}
@media(max-width:620px){.strength-grid{grid-template-columns:1fr}}

/* leadership — photo, then a timeline of roles */
.lead-grid{display:grid;grid-template-columns:1fr;gap:40px}
.lead-photo{position:relative;aspect-ratio:16/9;border:7px solid #f6f1e3;outline:1px solid var(--gold);box-shadow:0 36px 60px -26px rgba(19,41,75,.5);overflow:hidden}
.lead-photo img{width:100%;height:100%;object-fit:cover;object-position:30% 30%;transition:transform 1.4s var(--ease)}
.lead-photo:hover img{transform:scale(1.05)}
.lead-photo-cap{position:absolute;left:0;right:0;bottom:0;padding:44px 24px 18px;background:linear-gradient(transparent,rgba(19,41,75,.78));color:#fff}
.lc-eyebrow{font-family:var(--f-ui);font-size:10px;letter-spacing:.18em;text-transform:uppercase;color:#f0d78e;margin-bottom:4px}
.lc-text{font-style:italic;font-size:18px;line-height:1.4}
.org-list{position:relative;padding-left:34px}
.org-list::before{content:'';position:absolute;left:7px;top:10px;bottom:10px;width:1px;background:linear-gradient(var(--gold2),rgba(176,141,60,.1))}
.org-item{display:grid;grid-template-columns:150px 1fr;gap:18px;padding:22px 0;border-bottom:1px solid var(--stone);transition:padding-left .45s var(--ease)}
.org-item:last-child{border:none}
.org-item::before{content:'';position:absolute;left:-31px;top:30px;width:9px;height:9px;background:var(--gold2);transform:rotate(45deg);box-shadow:0 0 0 4px var(--m)}
.org-item:hover{padding-left:14px}
.org-role{font-family:var(--f-ui);font-size:10.5px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);line-height:1.5;padding-top:4px}
.org-body h5{font-family:var(--f-disp);font-size:16px;letter-spacing:.03em;margin-bottom:4px;color:var(--navy)}
.org-body p{font-size:17px;color:var(--mute);line-height:1.55}
@media(max-width:620px){.org-item{grid-template-columns:1fr;gap:2px}}

/* collaborations — plaques with round medallions */
.collab-card{position:relative;display:grid;grid-template-columns:220px 1fr;gap:42px;align-items:start;margin-bottom:34px;padding:38px;background:linear-gradient(155deg,#fffdf9,var(--m2));border:1px solid var(--stone);border-top:2px solid var(--gold2);box-shadow:0 24px 46px -28px rgba(19,41,75,.45)}
.collab-card.reverse .collab-emblem-wrap,.collab-card.reverse .collab-copy{order:unset}
.collab-emblem-wrap{position:relative;width:220px}
.collab-emblem-wrap::before{content:'';position:absolute;inset:-9px 9px 17px -9px;border:1px dashed var(--gold2);border-radius:50%;animation:spin 50s linear infinite;opacity:.8;pointer-events:none}
.collab-emblem{position:relative;overflow:hidden;aspect-ratio:1;border-radius:50%;padding:14%;display:flex;align-items:center;justify-content:center;background:linear-gradient(160deg,#f4ecd9,#e3d5b2);border:5px solid #f6f1e3;outline:1px solid var(--gold);box-shadow:0 24px 40px -18px rgba(19,41,75,.5)}
.collab-emblem img{width:100%;height:100%;object-fit:contain;transition:transform 1s var(--ease)}
.collab-card:hover .collab-emblem img{transform:scale(1.06)}
.collab-emblem.is-compact img{width:86%;height:86%}
.collab-emblem.is-zoomed img{width:200%;height:200%}
.collab-emblem.is-white{background:#fff}
.collab-emblem-wrap .collab-emblem.is-bleed{padding:0 !important;background:transparent !important;display:block !important}
.collab-emblem-wrap .collab-emblem.is-bleed img{display:block !important;width:100% !important;height:100% !important;max-width:none !important;object-fit:cover !important;border-radius:50%}
.collab-emblem-tag{position:absolute;left:50%;transform:translateX(-50%);bottom:-14px;z-index:2;font-family:var(--f-disp);font-size:9.5px;letter-spacing:.18em;text-transform:uppercase;color:#fff;background:var(--gold);padding:6px 14px;white-space:nowrap}
.collab-eyebrow{font-family:var(--f-ui);font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:10px}
.collab-copy h3{font-family:var(--f-disp);font-size:30px;letter-spacing:.04em;margin-bottom:3px;color:var(--navy)}
.collab-by{font-style:italic;font-size:20px;color:var(--gold);margin-bottom:18px}
.collab-copy p{font-size:18px;line-height:1.78;color:var(--mute)}
.collab-story{border-left:2px solid var(--gold2);padding-left:20px;margin:20px 0 24px}
.collab-story p{color:var(--ink)}
.collab-owner-row{display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding-top:20px;border-top:1px solid var(--stone)}
.collab-owner-id{display:flex;align-items:center;gap:12px}
.collab-owner-avatar{width:48px;height:48px;border-radius:50%;border:1px solid var(--gold);color:var(--gold);font-family:var(--f-disp);font-size:16px;font-weight:600;display:flex;align-items:center;justify-content:center;background:#fffdf8;overflow:hidden;flex-shrink:0;box-shadow:0 0 0 3px #f6f1e3}
.collab-owner-avatar img{width:100%;height:100%;object-fit:cover;object-position:center top;display:block}
.ow-name{font-family:var(--f-disp);font-size:14px;color:var(--navy)}
.ow-role{font-family:var(--f-ui);font-size:10px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold)}
.collab-owner-row .btn-line{margin-left:auto;padding:10px 20px;font-size:10.5px}
@media(max-width:700px){.collab-card,.collab-card.reverse{grid-template-columns:1fr;padding:26px}.collab-emblem-wrap{margin:0 auto 14px}.collab-owner-row .btn-line{margin-left:0}}

/* ventures — browser frames that scroll their screenshot on hover */
.venture{display:block;margin-bottom:64px}
.venture.reverse .venture-shot,.venture.reverse .venture-copy{order:unset}
.browser-frame{overflow:hidden;border:1px solid var(--stone);box-shadow:0 34px 60px -28px rgba(19,41,75,.5);background:#fff}
.browser-bar{display:flex;align-items:center;gap:8px;padding:11px 14px;background:var(--m2);border-bottom:1px solid var(--stone)}
.browser-dot{width:9px;height:9px;border-radius:50%}
.browser-dot.r{background:#c8645a}.browser-dot.y{background:#d3a850}.browser-dot.g{background:#7fa876}
.browser-url{margin-left:10px;flex:1;font-family:var(--f-ui);font-size:10.5px;color:var(--mute);background:#fff;border:1px solid var(--stone);padding:4px 14px;border-radius:100px}
.browser-shot{width:100%;display:block;height:380px;object-fit:cover;object-position:top center;transition:object-position 7s linear}
.browser-frame:hover .browser-shot{object-position:bottom center}
.venture-copy{padding-top:28px}
.venture-eyebrow{font-family:var(--f-ui);font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:var(--mute);margin-bottom:12px}
.venture-role{color:var(--gold)}
.venture-copy h3{font-family:var(--f-disp);font-size:32px;letter-spacing:.04em;margin-bottom:14px;color:var(--navy)}
.venture-copy p{font-size:18px;line-height:1.75;color:var(--mute);margin-bottom:20px}
.venture-features{list-style:none;display:grid;grid-template-columns:1fr 1fr;gap:10px 24px;padding:0}
.venture-features li{font-size:16.5px;display:flex;align-items:center;gap:10px}
.venture-features li::before{content:'';width:6px;height:6px;background:var(--gold);transform:rotate(45deg);flex-shrink:0}
@media(max-width:620px){.venture-features{grid-template-columns:1fr}.browser-shot{height:240px}}

/* monte carlo */
.mc-panel{padding:40px;display:flex;flex-direction:column;gap:34px}
.mc-shot .browser-shot{height:auto}
.mc-shot .browser-frame:hover .browser-shot{object-position:top center}
.mc-lower{display:grid;grid-template-columns:1fr;gap:20px}
.mc-eyebrow{font-family:var(--f-ui);font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:var(--gold);margin-bottom:10px}
.mc-copy h3{font-family:var(--f-disp);font-size:26px;letter-spacing:.04em;color:var(--navy)}
.mc-copy p{font-size:18px;line-height:1.75;color:var(--mute);margin-bottom:16px}
.mc-roles{display:flex;flex-direction:column;gap:10px}
.mc-role-line{display:flex;gap:14px;font-size:17px}
.mc-role-line b{color:var(--gold);font-family:var(--f-disp);font-weight:600;font-size:13px;flex-shrink:0;width:150px;padding-top:4px}
@media(max-width:620px){.mc-panel{padding:22px}.mc-role-line{flex-direction:column;gap:2px}}

/* vision */
.vision-wrap{text-align:left}
.vision-quote{font-style:italic;font-size:clamp(26px,3vw,36px);line-height:1.5;margin-bottom:18px;color:var(--navy)}
.vision-quote::before,.vision-quote::after{content:'"';color:var(--gold)}
.vision-attr{font-family:var(--f-ui);font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:40px}
.goals-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:1px;background:var(--stone);border:1px solid var(--stone)}
.goal-cell{background:#fffdf9;padding:26px 24px;display:flex;gap:16px;align-items:center;transition:background .35s}
.goal-cell:hover{background:var(--m2)}
.goal-cell::before{content:'';flex:none;width:20px;height:20px;background:var(--key) center/20px 20px}
.goal-cell p{font-size:17.5px;line-height:1.45}
@media(max-width:560px){.goals-grid{grid-template-columns:1fr}}

/* contact */
.contact-panel{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--stone);border:1px solid var(--stone)}
.contact-cell{background:#fffdf9;padding:34px 26px;display:flex;flex-direction:column;gap:12px;text-decoration:none;color:var(--ink);transition:background .35s}
.contact-cell:hover{background:var(--m2)}
.contact-cell:last-child:nth-child(3n+2){grid-column:span 2}
@media(max-width:820px){.contact-cell:last-child:nth-child(3n+2){grid-column:auto}}
.cc-icon{width:44px;height:44px;border:1px solid var(--gold);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--gold);transition:background .35s,color .35s,transform .5s var(--ease)}
.contact-cell:hover .cc-icon{background:var(--gold);color:#fff;transform:rotate(-8deg) scale(1.08)}
.cc-label{font-family:var(--f-ui);font-size:10.5px;letter-spacing:.18em;text-transform:uppercase;color:var(--gold)}
.cc-handle{font-family:var(--f-disp);font-size:15px;color:var(--navy);word-break:break-word}
.cc-sub{font-size:15px;color:var(--mute);word-break:break-word}
@media(max-width:820px){.contact-panel{grid-template-columns:1fr}}

/* testimonials */
.testi-wrap{max-width:none}
.testi-track-wrap{overflow:hidden}
.testi-track{display:flex;transition:transform .8s cubic-bezier(.65,0,.2,1)}
.testi-slide{flex:0 0 100%;min-width:0;text-align:left;padding:0 6px}
.testi-stars{display:flex;gap:4px;margin-bottom:22px}
.testi-stars svg{width:17px;height:17px}
.testi-stars .filled{color:var(--gold)}.testi-stars .empty{color:var(--stone)}
.testi-quote{font-style:italic;font-size:clamp(22px,2.4vw,28px);line-height:1.6;margin-bottom:28px;color:var(--navy)}
.testi-quote::before,.testi-quote::after{content:'"';color:var(--gold)}
.testi-person{display:flex;flex-direction:column;align-items:flex-start;gap:6px}
.testi-avatar{width:52px;height:52px;border-radius:50%;border:1px solid var(--gold);color:var(--gold);font-family:var(--f-disp);font-size:18px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#fffdf8}
.testi-avatar img{width:100%;height:100%;object-fit:cover}
.testi-name{font-family:var(--f-disp);font-size:15px;color:var(--navy)}
.testi-loc{font-family:var(--f-ui);font-size:10.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--gold)}
.testi-venture{font-style:italic;color:var(--mute);font-size:15px}
.testi-nav{display:flex;align-items:center;gap:22px;margin-top:34px}
.testi-arrow{width:40px;height:40px;border-radius:50%;border:1px solid var(--stone);background:#fffdf9;color:var(--mute);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .3s;padding:0}
.testi-arrow:hover{border-color:var(--gold);color:#fff;background:var(--gold)}
.testi-dots{display:flex;gap:9px;align-items:center}
.testi-dots span{width:7px;height:7px;background:var(--stone);transform:rotate(45deg);cursor:pointer;transition:all .3s}
.testi-dots span.active{background:var(--gold);transform:rotate(45deg) scale(1.35)}

.closing-line{text-align:center;padding:70px 32px 30px;font-style:italic;font-size:22px;color:var(--mute);max-width:720px;margin:0 auto}

/* pagination */
.pg-hide{display:none !important}
.pg-last{margin-bottom:0 !important}
.pg-in{animation:pgIn .8s var(--ease) both}
@keyframes pgIn{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:none}}
.pg-nav{display:flex;align-items:center;justify-content:center;gap:10px;margin-top:50px;padding-top:30px;border-top:1px solid var(--stone)}
.pg-btn{min-width:40px;height:40px;padding:0 12px;border:1px solid var(--stone);background:#fffdf9;color:var(--mute);font-family:var(--f-disp);font-size:14px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:all .3s}
.pg-btn:hover:not(:disabled){border-color:var(--gold);color:var(--gold)}
.pg-btn.active{background:var(--gold);border-color:var(--gold);color:#fff}
.pg-btn:disabled{opacity:.35;cursor:default}
.pg-info{font-family:var(--f-ui);font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:var(--gold);margin:0 8px}
@media(max-width:560px){.pg-nav{gap:6px}.pg-info{display:none}}

/* ── COPS header / footer (same as index.php) ── */
.top-strip{position:relative;z-index:101;background:linear-gradient(180deg,var(--navy2),var(--navy));border-bottom:2px solid var(--gold2);text-align:center;padding:8px 16px;font-family:var(--f-disp);font-size:11px;letter-spacing:.3em;color:#f0dca0}
#siteHeader{position:sticky;top:0;z-index:100;background:rgba(255,255,255,.82);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border-bottom:1px solid transparent;transition:background .3s,border-color .3s,box-shadow .3s}
#siteHeader.scrolled{background:rgba(255,255,255,.96);border-bottom-color:var(--stone);box-shadow:0 10px 30px -22px rgba(19,41,75,.4)}
.header-inner{max-width:1200px;margin:0 auto;padding:0 28px;height:78px;display:flex;align-items:center;justify-content:space-between;gap:20px}
.brand{display:flex;align-items:center;gap:13px;text-decoration:none}
.brand-logo{width:50px;height:50px;border-radius:50%;background:#fff;padding:3px;box-shadow:0 0 0 2px var(--gold2),0 8px 16px -8px rgba(19,41,75,.55);transition:transform .5s cubic-bezier(.2,.8,.2,1)}
.brand:hover .brand-logo{transform:rotate(-8deg) scale(1.05)}
.brand-logo img{display:block;width:100%;height:100%;border-radius:50%;object-fit:cover}
.brand-name{font-family:var(--f-disp);font-size:22px;font-weight:700;letter-spacing:.2em;color:var(--navy);line-height:1.1}
.brand-name small{display:block;font-family:var(--f-body);font-style:italic;font-weight:400;font-size:13px;letter-spacing:0;color:var(--gold)}
#siteHeader nav{display:flex;align-items:center;gap:6px}
#siteHeader nav a{font-family:var(--f-disp);font-size:12px;font-weight:500;letter-spacing:.14em;color:var(--mute);text-decoration:none;padding:9px 13px;position:relative;transition:color .2s}
#siteHeader nav a:not(.cta)::after{content:'';position:absolute;left:13px;right:13px;bottom:4px;height:1px;background:var(--gold2);transform:scaleX(0);transform-origin:left;transition:transform .3s cubic-bezier(.2,.8,.2,1)}
#siteHeader nav a:not(.cta):hover{color:var(--navy)}
#siteHeader nav a:not(.cta):hover::after,#siteHeader nav a.active::after{transform:scaleX(1)}
#siteHeader nav a.active{color:var(--gold)}
#siteHeader nav .hello{font-style:italic;font-size:16px;color:var(--gold);padding:0 10px}
#siteHeader nav a.cta{margin-left:8px;padding:11px 22px;color:#1a1400;font-weight:700;background:linear-gradient(180deg,#f0d78e,#c9a24a 55%,#a9822c);border:1px solid #d9b45a;box-shadow:0 10px 20px -12px rgba(138,106,31,.8);transition:filter .2s,transform .2s}
#siteHeader nav a.cta:hover{filter:brightness(1.08);transform:translateY(-2px)}
@media(max-width:860px){#siteHeader nav a:not(.cta):not(.keep),#siteHeader nav .hello{display:none}.brand-name small{display:none}}
footer{position:relative;z-index:1;background:linear-gradient(180deg,var(--navy2),#0d1c36);border-top:3px solid var(--gold2)}
.foot{max-width:1200px;margin:0 auto;padding:30px 28px 26px;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:18px}
.foot-brand{display:flex;align-items:center;gap:14px;font-family:var(--f-disp);font-size:17px;font-weight:700;letter-spacing:.2em;color:#fff}
.foot-brand img{width:52px;height:52px;border-radius:50%;background:#fff;padding:3px;box-shadow:0 0 0 2px #d9b45a}
.foot-brand small{display:block;font-family:var(--f-body);font-size:15px;font-weight:400;letter-spacing:0;color:#f0dca0;font-style:italic}
.foot-links{display:flex;gap:24px;flex-wrap:wrap}
.foot-links a{font-family:var(--f-disp);font-size:12px;letter-spacing:.14em;color:rgba(255,255,255,.72);text-decoration:none;transition:color .2s}
.foot-links a:hover{color:#d9bb68}
.foot-bottom{max-width:1200px;margin:0 auto;padding:16px 28px 26px;border-top:1px solid rgba(217,187,104,.25);font-size:15px;color:rgba(255,255,255,.6)}
.foot-bottom span{color:#f0dca0}
a:focus-visible,button:focus-visible{outline:2px solid var(--gold);outline-offset:3px}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.01s !important;transition-duration:.01s !important;transition-delay:0s !important}html{scroll-behavior:auto}}
</style>
</head>
<body class="gate-lock">
<div class="progress" id="progress"></div>
<div class="top-strip">CARD-MRI DEVELOPMENT INSTITUTE &nbsp;&middot;&nbsp; SCHOOL CANTEEN &nbsp;&middot;&nbsp; ORDER AHEAD, SKIP THE LINE</div>

<header id="siteHeader">
    <div class="header-inner">
        <a href="index.php" class="brand" aria-label="COPS home">
            <span class="brand-logo"><img src="<?= SCHOOL_LOGO ?>" alt="CARD-MRI Development Institute logo"></span>
            <span class="brand-name">COPS<small>CMDI SPC Campus Canteen</small></span>
        </a>
        <nav aria-label="Main">
            <a href="index.php" class="keep">HOME</a>
            <a href="menu.php">MENU</a>
            <a href="index.php#how">HOW IT WORKS</a>
            <a href="about-us.php" class="active keep">ABOUT</a>
            <?php if ($isLoggedIn): ?>
                <span class="hello">Hello, <?= htmlspecialchars($firstName) ?></span>
                <a href="log-out.php" class="keep">LOG OUT</a>
            <?php else: ?>
                <a href="log-in.php" class="keep">LOG IN</a>
            <?php endif; ?>
            <a href="menu.php" class="cta">ORDER NOW</a>
        </nav>
    </div>
</header>

<div class="forum">

<!-- ══════════ TEMPLE GATE INTRO ══════════ -->
<div class="gate" id="gate" aria-hidden="true">
    <div class="rays"></div>
    <div class="facade">
        <div class="ped"><div class="o"></div><div class="i"></div>
            <div class="emb"><svg viewBox="0 0 100 100" id="wreath"><defs><linearGradient id="lg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#e6c677"/><stop offset=".55" stop-color="#9a7430"/><stop offset="1" stop-color="#c9a45c"/></linearGradient></defs></svg><img class="gate-logo" src="<?= SCHOOL_LOGO ?>" alt=""></div>
        </div>
        <div class="ent"><div class="arc">COPS &middot; CARD-MRI DEVELOPMENT INSTITUTE</div><div class="frz"></div><div class="cor"></div></div>
        <div class="cl">
            <div class="cg"><?php for($i=0;$i<2;$i++): ?><div class="col"><i class="cap"></i><i class="sh"></i><i class="bs"></i></div><?php endfor; ?></div>
            <div class="dw"><div class="lt"></div><div class="lf l"><i class="pn"></i><i class="pn"></i><i class="pn"></i><b class="hd"></b></div><div class="lf r"><i class="pn"></i><i class="pn"></i><i class="pn"></i><b class="hd"></b></div></div>
            <div class="cg"><?php for($i=0;$i<2;$i++): ?><div class="col"><i class="cap"></i><i class="sh"></i><i class="bs"></i></div><?php endfor; ?></div>
        </div>
        <div class="stp"></div>
        <div class="gb"><i></i></div>
    </div>
</div>

<!-- ── Scroll-tracking index rail ── -->
<nav class="index-rail" id="indexRail">
    <a href="#profile" data-target="profile"><span class="ir-num">I</span><span class="ir-bar"></span><span class="ir-label">Executive Profile</span></a>
    <a href="#family" data-target="family"><span class="ir-num">II</span><span class="ir-bar"></span><span class="ir-label">Family Background</span></a>
    <a href="#skills" data-target="skills"><span class="ir-num">III</span><span class="ir-bar"></span><span class="ir-label">Skills &amp; Strengths</span></a>
    <a href="#leadership" data-target="leadership"><span class="ir-num">IV</span><span class="ir-bar"></span><span class="ir-label">Organizations</span></a>
    <a href="#collaborations" data-target="collaborations"><span class="ir-num">V</span><span class="ir-bar"></span><span class="ir-label">Collaborations</span></a>
    <a href="#ventures" data-target="ventures"><span class="ir-num">VI</span><span class="ir-bar"></span><span class="ir-label">Ventures</span></a>
    <a href="#montecarlo" data-target="montecarlo"><span class="ir-num">VII</span><span class="ir-bar"></span><span class="ir-label">Monte Carlo</span></a>
    <a href="#vision" data-target="vision"><span class="ir-num">VIII</span><span class="ir-bar"></span><span class="ir-label">Vision</span></a>
    <a href="#testimonials" data-target="testimonials"><span class="ir-num">IX</span><span class="ir-bar"></span><span class="ir-label">Testimonials</span></a>
    <a href="#contact" data-target="contact"><span class="ir-num">X</span><span class="ir-bar"></span><span class="ir-label">Contact</span></a>
</nav>

<div class="stage">

<!-- ══════════ HERO ══════════ -->
<section class="dh">
    <div class="dh-inner">
        <div class="arch-wrap">
            <div class="dh-photo">
                <img src="assets/img/about-profile.jpg" alt="Prince Edward Guevara">
                <span class="dh-photo-tag">San Pablo City, Laguna</span>
            </div>
        </div>
        <div>
            <div class="dh-eyebrow">The Founder's Dossier</div>
            <h1 class="dh-name">Prince Edward<br>Guevara</h1>
            <div class="dh-roles">
                <span class="dh-role-pill">Founder</span>
                <span class="dh-role-pill">Technology Leader</span>
                <span class="dh-role-pill">Tech Prodigy</span>
                <span class="dh-role-pill">Programmer Developer</span>
                <span class="dh-role-pill">Editor Trainee</span>
            </div>
            <p class="dh-quote">"Technology is not only about writing code — it is about creating solutions that improve lives, empower businesses, and inspire continuous innovation."</p>
            <div class="dh-cta">
                <a href="https://github.com/guevaraprinceedward/" target="_blank" rel="noopener" class="btn-gold">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.58 2 12.25c0 4.53 2.87 8.37 6.84 9.73.5.1.68-.22.68-.5 0-.24-.01-1.04-.01-1.89-2.78.61-3.37-1.2-3.37-1.2-.45-1.18-1.11-1.5-1.11-1.5-.9-.63.07-.62.07-.62 1 .07 1.53 1.05 1.53 1.05.89 1.56 2.34 1.11 2.91.85.09-.66.35-1.11.63-1.37-2.22-.26-4.56-1.14-4.56-5.06 0-1.12.39-2.03 1.03-2.75-.1-.26-.45-1.31.1-2.73 0 0 .84-.28 2.75 1.05a9.34 9.34 0 0 1 5 0c1.91-1.33 2.75-1.05 2.75-1.05.55 1.42.2 2.47.1 2.73.64.72 1.03 1.63 1.03 2.75 0 3.93-2.35 4.79-4.58 5.05.36.32.68.94.68 1.9 0 1.37-.01 2.47-.01 2.81 0 .28.18.61.69.5A10.26 10.26 0 0 0 22 12.25C22 6.58 17.52 2 12 2z"/></svg>
                    View GitHub
                </a>
                <a href="#contact" class="btn-line">Get in Touch</a>
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
<div class="frieze"></div>

<div class="dossier">

<!-- ══════════ I. EXECUTIVE PROFILE ══════════ -->
<section class="dossier-section" id="profile">
    <div class="ds-head">
        <span class="ds-num">I</span><span class="ds-eyebrow">Introduction</span><h2>Executive Profile</h2>
        <p class="ds-lede">An aspiring Software Engineer building at the intersection of discipline, leadership, and code — one project at a time.</p>
    </div>
    <div class="ds-body">
    <div class="profile-copy">
        <div>
            <p>Prince Edward Guevara is an aspiring Software Engineer, Programmer Developer, Technology Leader, and Editor Trainee passionate about creating innovative digital solutions that provide practical value to businesses and organizations. His journey in technology is driven by curiosity, discipline, and an unwavering commitment to continuous improvement.</p>
            <p>At the age of seventeen, Prince has dedicated countless hours to studying programming, web development, system architecture, user experience, and digital media — always favoring real-world application over theory alone.</p>
            <p>His philosophy is centered on solving problems through technology. Every project is designed with functionality, scalability, and user experience in mind, built on the belief that successful systems should simplify operations, improve efficiency, and contribute to long-term business growth. Beyond software, he continues to sharpen his leadership and strategic thinking through philosophy, stoicism, and business strategy — believing that technical excellence paired with integrity creates the most meaningful impact.</p>
        </div>
        <div class="info-card slab">
            <h4>Personal Information</h4>
            <div class="info-row"><span class="ik">Full Name</span><span class="iv">Prince Edward Guevara</span></div>
            <div class="info-row"><span class="ik">Age</span><span class="iv">17</span></div>
            <div class="info-row"><span class="ik">Sex</span><span class="iv">Male</span></div>
            <div class="info-row"><span class="ik">Birthdate</span><span class="iv">July 15, 2009</span></div>
            <div class="info-row"><span class="ik">Birthplace</span><span class="iv">San Pablo City, Laguna</span></div>
        </div>
    </div>
    </div>
</section>
<div class="frieze"></div>

<!-- ══════════ II. FAMILY BACKGROUND ══════════ -->
<section class="dossier-section" id="family">
    <div class="ds-head">
        <span class="ds-num">II</span><span class="ds-eyebrow">Roots</span><h2>Family Background</h2>
        <p class="ds-lede">The foundation of leadership and discipline that shaped his outlook, long before the first line of code.</p>
    </div>
    <div class="ds-body">

    <div class="family-grid">
        <div class="bust">
            <img src="assets/img/about-father.jpeg" alt="Rogelio Calderon Guevara">
            <span class="bust-cap">Rogelio C. Guevara</span>
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
            <div class="fam-ventures slab">
                <h5>Ventures Built</h5>
                <div class="fam-venture-item"><div class="fv-name">Monte Carlo Group of Companies</div><div class="fv-desc">Founded 1984 — expanded across multiple industries and branches nationwide.</div></div>
                <div class="fam-venture-item"><div class="fv-name">Monte Carlo Farm</div><div class="fv-desc">A resort built through years of dedication, sacrifice, and hard work.</div></div>
                <div class="fam-venture-item"><div class="fv-name">Monte Carlo Executive Villa</div><div class="fv-desc">An elegant residential community built on comfort, security, and a refined lifestyle.</div></div>
            </div>
        </div>

        <div class="fam-pullquote">
            <p>"My son, always remember that no matter how difficult life becomes — no matter how deeply you feel defeated — never allow hardship to define your future. Instead, transform every setback into the fuel that strengthens your heart and sharpens your determination. Every great leader experiences moments of exhaustion, doubt, and mental struggle. I have faced those challenges myself. What separates successful people is not the absence of hardship, but the courage to continue despite it."</p>
            <div class="fq-attr">— Rogelio Calderon Guevara, to his son</div>
        </div>

        <div class="fam-legacy">
            <h5>How He Shaped Me</h5>
            <p class="intro">Among his children is his son, Prince Edward Guevara — affectionately known as <em>"Dokdok."</em> My father personally mentored me across the areas below, always insisting that true leadership begins with humility, integrity, and inspiring others through action rather than words. Every principle I carry into SIPPERÉ CAFÉ, Noctrune, The Atelier Noir, Kislap Booth, Canteen Ordering Platform System, and my work at Monte Carlo Technologies traces back to lessons he taught me long before I ever wrote a line of code.</p>
            <div class="fam-mentor-grid">
                <div class="fam-mentor-card">Humanitarian &amp; Great Leadership</div>
                <div class="fam-mentor-card">Professional Communication</div>
                <div class="fam-mentor-card">Strategic Thinking</div>
                <div class="fam-mentor-card">Business Development</div>
                <div class="fam-mentor-card">Professionalism in Several Aspects</div>
                <div class="fam-mentor-card">Kindness &amp; Respect for Others</div>
                <div class="fam-mentor-card">Case Problem Solving</div>
            </div>
        </div>
    </div>
    </div>
</section>
<div class="frieze"></div>

<!-- ══════════ III. SKILLS & STRENGTHS ══════════ -->
<section class="dossier-section" id="skills">
    <div class="ds-head">
        <span class="ds-num">III</span><span class="ds-eyebrow">Capabilities</span><h2>Skills &amp; Core Competencies</h2>
        <p class="ds-lede">Technical expertise alone isn't enough — leadership, adaptability, and communication build the rest of the system.</p>
    </div>
    <div class="ds-body">
    <div class="skills-layout">
        <div>
            <div class="skill-block">
                <h4>Professional Skills</h4>
                <div class="chip-wrap">
                    <span class="chip">Fast &amp; Adaptive Learner</span><span class="chip">Leadership</span><span class="chip">Critical Thinking</span><span class="chip">Tech Strategy</span>
                    <span class="chip">Front-End Development</span><span class="chip">Back-End Development</span><span class="chip">UI/UX Design (Moderate)</span><span class="chip">Web App Development</span>
                    <span class="chip">Video Editing &amp; Advertisement</span><span class="chip">Digital Media Production</span><span class="chip">Team Collaboration</span><span class="chip">Project Planning</span>
                    <span class="chip">Problem Solving</span><span class="chip">Database Management</span><span class="chip">System Architecture</span><span class="chip">SEO</span><span class="chip">Social Media Marketing</span>
                    <span class="chip">DevOps</span><span class="chip">API Intergration</span><span class="chip">Version Control (Git &amp; GitHub)</span>
                </div>
            </div>
            <div class="skill-block">
                <h4>Programming Languages</h4>
                <div class="chip-wrap">
                    <span class="chip lang">HTML</span><span class="chip lang">CSS</span><span class="chip lang">JavaScript</span><span class="chip lang">PHP</span><span class="chip lang">jQuery</span><span class="chip lang">SQL</span><span class="chip lang">JAVA</span>
                    <span class="chip lang">Bootstrap</span><span class="chip lang">Tailwind</span><span class="chip lang">React</span><span class="chip lang">Node.js</span><span class="chip lang">Database Management</span><span class="chip lang">Git &amp; GitHub</span>
                    <span class="chip lang">SQL &amp; MySQL</span>
                </div>
            </div>
        </div>
        <div class="strength-grid">
            <div class="strength-card slab"><h5>Problem Solving</h5><p>Analyzing complex challenges and finding practical, scalable solutions through logical, systematic development.</p></div>
            <div class="strength-card slab"><h5>Continuous Learning</h5><p>Dedicating significant time to studying new technologies, programming concepts, and modern development methodologies.</p></div>
            <div class="strength-card slab"><h5>Leadership</h5><p>Valuing responsibility, accountability, and teamwork — creating an environment where ideas grow into outcomes.</p></div>
            <div class="strength-card slab"><h5>Adaptability</h5><p>Adapting quickly to new technologies, workflows, and environments while holding quality as a constant.</p></div>
        </div>
    </div>
    </div>
</section>
<div class="frieze"></div>

<!-- ══════════ IV. LEADERSHIP & ORGANIZATIONS ══════════ -->
<section class="dossier-section" id="leadership">
    <div class="ds-head">
        <span class="ds-num">IV</span><span class="ds-eyebrow">In Practice</span><h2>Organizations &amp; Leadership</h2>
        <p class="ds-lede">From wireframes on a hotel counter to boardroom titles — leadership shown through the actual work.</p>
    </div>
    <div class="ds-body">
    <div class="lead-grid">
        <div class="lead-photo">
            <img src="assets/img/about-leaderships.jpeg" alt="Mapping out system wireframes and UI flows">
            <div class="lead-photo-cap">
                <div class="lc-eyebrow">On Site</div>
                <div class="lc-text">Mapping wireframes and system flows — leadership shown in the details.</div>
            </div>
        </div>
        <div class="org-list">
            <div class="org-item"><div class="org-role">Founder &amp; CEO</div><div class="org-body"><h5>SIPPERÉ Café &amp; Noctrune</h5><p>Founder and Chief Executive Officer of both platforms — overseeing planning, system architecture, and continuous development.</p></div></div>
            <div class="org-item"><div class="org-role">Tech Lead / Main Programmer</div><div class="org-body"><h5>SIPPERÉ Café &amp; Noctrune </h5><p>Leading system architecture, front-end and back-end development, database structure, and continuous feature planning.</p></div></div>
            <div class="org-item"><div class="org-role">Editor Trainee</div><div class="org-body"><h5>Monte Carlo Technologies</h5><p>Video editing, advertisement production, marketing and social media content, and corporate branding videos.</p></div></div>
            <div class="org-item"><div class="org-role">Boxing Trainee</div><div class="org-body"><h5>Monte Carlo Fitness Gym</h5><p>Active member building physical fitness, discipline, endurance, and personal resilience.</p></div></div>
            <div class="org-item"><div class="org-role">Head Member</div><div class="org-body"><h5>Stoicism Society</h5><p>Promoting continuous learning, discipline, resilience, and philosophical thinking among members.</p></div></div>
            <div class="org-item"><div class="org-role">Former Member</div><div class="org-body"><h5>LEO Club</h5><p>Affiliated with Lions Clubs International — community-oriented initiatives and leadership development.</p></div></div>
        </div>
    </div>
    </div>
</section>
<div class="frieze"></div>

<!-- ══════════ V. COLLABORATIONS (2 per page) ══════════ -->
<section class="dossier-section" id="collaborations">
    <div class="ds-head">
        <span class="ds-num">V</span><span class="ds-eyebrow">In Partnership</span><h2>Collaborations</h2>
        <p class="ds-lede collab-intro">A few businesses built or strengthened alongside their owners, credited here by name rather than left behind the scenes.</p>
    </div>
    <div class="ds-body">

    <div data-paginate="2" data-item=".collab-card">

        <div class="collab-card">
            <div class="collab-emblem-wrap">
                <div class="collab-emblem is-white"><img src="assets/img/collab-axlrose-cafe-img.jpg" alt="Axl Rose Café logo"></div>
                <span class="collab-emblem-tag">Brand Identity</span>
            </div>
            <div class="collab-copy">
                <div class="collab-eyebrow">Collaboration I</div>
                <h3>Axl Rose Café</h3>
                <div class="collab-by">by Mr. Felix</div>
                <p>Axl Rose Café is a coffee house built around sound as much as flavor — a place where live bands and spontaneous jam sessions are a near-daily occurrence, and guests are welcomed to sing along rather than simply watch. Even on quieter days, there is always someone filling the room with music and easy conversation, the kind of atmosphere that lets a visitor slow down and stay a while. The coffee holds to the same standard: the sort that settles into your taste and keeps pulling you back for another cup.</p>
                <div class="collab-story"><p>I helped Axl Rose Café grow from its earliest days at Paseo, back when the shop was still finding its footing. I encouraged Mr. Felix to keep pushing — to treat every hard week as proof the business was worth the effort, and to think bigger about what it could become. Mr. Felix has been one of the kindest people in our circle in return, never leaving us behind and always pushing us to do our best. It was with his encouragement that we set out to build a coffee-themed digital platform of our own, carrying the Axl Rose name into a business idea of its own.</p></div>
                <div class="collab-owner-row">
                    <div class="collab-owner-id">
                        <span class="collab-owner-avatar"><img src="assets/img/collab-felix-avatar.jpg" alt="Mr. Felix Kilakiga" onerror="this.replaceWith(document.createTextNode('F'))"></span>
                        <div><div class="ow-name">Mr. Felix Kilakiga</div><div class="ow-role">Owner, Axl Rose Café</div></div>
                    </div>
                    <a href="#" class="btn-line">Visit Axl Rose Café</a>
                </div>
            </div>
        </div>

        <div class="collab-card reverse">
            <div class="collab-emblem-wrap">
                <div class="collab-emblem is-bleed"><img src="assets/img/collab-busy-brees-img.png" alt="Busy Bree's Manju logo"></div>
                <span class="collab-emblem-tag">Brand Identity</span>
            </div>
            <div class="collab-copy">
                <div class="collab-eyebrow">Collaboration II</div>
                <h3>Busy Bree's Manju</h3>
                <div class="collab-by">by Mr. Brylle Balbuena</div>
                <p>Busy Bree's Manju, founded by Mr. Brylle Balbuena, turns a familiar fish-shaped waffle into a small catalog of flavor — Chocolate, Mango, Ube, Strawberry, Cookies and Cream, and more — built with the tourists and locals around San Pablo Lake in mind. Mr. Brylle's instinct was never just to make a product; it was to make one worth stopping for, something that earns a second visit on taste alone.</p>
                <div class="collab-story"><p>Busy Bree's Manju was the very first client of AyosNegosyoPH, the all-in-one point-of-sale platform we built — complete with a staff monitoring system that tracks time-in and time-out, alongside a wider set of operational features. We spent nearly a month alongside Mr. Brylle, thinking carefully through how the product would sell and what would make it work for his customers. Our team mapped out every likely problem around the business, strategized around each one, and timed the launch with care. We're glad to say it took off well.</p></div>
                <div class="collab-owner-row">
                    <div class="collab-owner-id">
                        <span class="collab-owner-avatar"><img src="assets/img/collab-brylle-avatar.jpg" alt="Mr. Brylle Balbuena" onerror="this.replaceWith(document.createTextNode('B'))"></span>
                        <div><div class="ow-name">Mr. Brylle Balbuena</div><div class="ow-role">Owner, Busy Bree's Manju</div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="collab-card">
            <div class="collab-emblem-wrap">
                <div class="collab-emblem is-bleed"><img src="assets/img/collab-hotel-101-img.png" alt="Hotel 101 logo"></div>
                <span class="collab-emblem-tag">Brand Identity</span>
            </div>
            <div class="collab-copy">
                <div class="collab-eyebrow">Collaboration III</div>
                <h3>Hotel 101</h3>
                <div class="collab-by">by Mr. George Uy</div>
                <p>Hotel 101 is built around a distinctive hotel-and-investment model — a "condotel" format where individual units are owned by investors while the hotel itself operates day to day around them, serving tourists, business travelers, and every guest in between. Founded and led by Mr. George Uy, a Filipino entrepreneur who runs a wide portfolio of companies under his own direction, Hotel 101 has grown into one of his strongest-performing ventures: dozens of branches across the Philippines, each carrying a full slate of in-house services within the building itself.</p>
                <div class="collab-story"><p>We helped Hotel 101 grow further by putting our own product, AyosNegosyoPH, to work as the point-of-sale system behind its food and essentials operations. Mr. George Uy uses it to track every transaction that happens on the property and to monitor staff performance — keeping a clear read on who's working and how the floor is running. It has since become one of the core systems inside the condominium, and as the partnership has grown, we've been building biometric facial recognition and RFID scanning into the same AyosNegosyoPH platform.</p></div>
                <div class="collab-owner-row">
                    <div class="collab-owner-id"><span class="collab-owner-avatar">G</span><div><div class="ow-name">Mr. George Uy</div><div class="ow-role">Founder &amp; CEO, Hotel 101</div></div></div>
                    <a href="https://hotel101.com/" class="btn-line">Visit Hotel 101</a>
                </div>
            </div>
        </div>

        <div class="collab-card reverse">
            <div class="collab-emblem-wrap">
                <div class="collab-emblem is-zoomed"><img src="assets/img/collab-ca-phe-img.jpg" alt="Cà Phê Vietnam Style logo"></div>
                <span class="collab-emblem-tag">Brand Identity</span>
            </div>
            <div class="collab-copy">
                <div class="collab-eyebrow">Collaboration IV</div>
                <h3>Cà Phê</h3>
                <div class="collab-by">by Mr. Eric</div>
                <p>Cà Phê is a Vietnamese-style coffee shop founded by Mr. Eric, who also runs a fitness gym right next door and built this one as a second, passive stream of income. It draws a steady crowd of tourists looking for something different from the usual cup — the beans are sourced from Vietnam rather than the typical local blend, giving the coffee a noticeably sweeter, smoother character than what most nearby cafés serve.</p>
                <div class="collab-story"><p>Our role with Mr. Eric has been less about building software and more about thinking alongside him — helping him spot new business ideas worth exploring and pushing his plans further than he'd first imagined. We continue to work through strategy with him and advise on where it makes sense to invest next.</p></div>
                <div class="collab-owner-row">
                    <div class="collab-owner-id"><span class="collab-owner-avatar">E</span><div><div class="ow-name">Mr. Eric</div><div class="ow-role">Owner, Cà Phê</div></div></div>
                    <a href="https://www.facebook.com/cafe.suada9/" class="btn-line">Visit Cà Phê</a>
                </div>
            </div>
        </div>

    </div><!-- /paginate collaborations -->
    </div>
</section>
<div class="frieze"></div>

<!-- ══════════ VI. VENTURES (3 per page) ══════════ -->
<section class="dossier-section" id="ventures">
    <div class="ds-head">
        <span class="ds-num">VI</span><span class="ds-eyebrow">Personal Projects</span><h2>Ventures</h2>
        <p class="ds-lede">Full business management platforms, founded, architected, and built from the ground up.</p>
    </div>
    <div class="ds-body">

    <div data-paginate="3" data-item=".venture">

        <div class="venture">
            <div class="venture-shot"><div class="browser-frame">
                <div class="browser-bar"><span class="browser-dot r"></span><span class="browser-dot y"></span><span class="browser-dot g"></span><span class="browser-url">sipperé.com/homepage</span></div>
                <img class="browser-shot" src="assets/img/about-sippre-cafe-img.jpg" alt="SIPPERÉ Café menu interface">
            </div></div>
            <div class="venture-copy">
                <div class="venture-eyebrow">Project I <span class="venture-role">&middot; Founder &amp; Tech Lead</span></div>
                <h3>SIPPERÉ Café</h3>
                <p>A centralized coffee business management platform built to replace fragmented manual processes with one integrated system — designed for owners who need real visibility into daily operations.</p>
                <ul class="venture-features"><li>User account management</li><li>Inventory &amp; stock tracking</li><li>Employee attendance</li><li>Payroll processing</li><li>Online product showcase</li><li>Centralized operations data</li></ul>
            </div>
        </div>

        <div class="venture reverse">
            <div class="venture-shot"><div class="browser-frame">
                <div class="browser-bar"><span class="browser-dot r"></span><span class="browser-dot y"></span><span class="browser-dot g"></span><span class="browser-url">nocturnemanilabay.com</span></div>
                <img class="browser-shot" src="assets/img/about-the-nocturne-hotel.jpg" alt="TheNoctrune hotel management homepage">
            </div></div>
            <div class="venture-copy">
                <div class="venture-eyebrow">Project II <span class="venture-role">&middot; Founder &amp; Tech Lead</span></div>
                <h3>The Noctrune</h3>
                <p>A comprehensive hotel management system streamlining operations through one centralized platform — from reservations to housekeeping, replacing fragmented manual workflows with organized, reliable software.</p>
                <ul class="venture-features"><li>Guest reservations &amp; check-in/out</li><li>Room availability &amp; housekeeping</li><li>Employee &amp; payroll management</li><li>Inventory &amp; supply tracking</li><li>Occupancy &amp; revenue reporting</li><li>Guest records &amp; booking history</li></ul>
            </div>
        </div>

        <div class="venture">
            <div class="venture-shot"><div class="browser-frame">
                <div class="browser-bar"><span class="browser-dot r"></span><span class="browser-dot y"></span><span class="browser-dot g"></span><span class="browser-url">ayoscoffeenegosyo.com/menu</span></div>
                <img class="browser-shot" src="assets/img/about-the-atelier-noir-img.jpg" alt="The Atelier Noir menu interface">
            </div></div>
            <div class="venture-copy">
                <div class="venture-eyebrow">Project III <span class="venture-role">&middot; Founder &amp; Tech Lead</span></div>
                <h3>The Atelier Noir</h3>
                <p>A modern fashion and essentials platform created to bring clothing, accessories, collections, and everyday essentials into one refined digital experience — giving customers a seamless way to discover their style, explore curated collections, and shop products while giving the brand a strong platform to showcase its identity.</p>
                <ul class="venture-features"><li>Curated Fashion Collections</li><li>Personalized &amp; Style Discovering</li><li>Clothing &amp; Essentials Marketplace</li><li>Wishlist &amp; Saved Items</li><li>Online product showcase</li><li>Shop-the-Look Experience</li></ul>
            </div>
        </div>

    </div><!-- /paginate ventures -->
    </div>
</section>
<div class="frieze"></div>

<!-- ══════════ VII. MONTE CARLO TECHNOLOGIES ══════════ -->
<section class="dossier-section" id="montecarlo">
    <div class="ds-head">
        <span class="ds-num">VII</span><span class="ds-eyebrow">Professional Home</span><h2>Monte Carlo Technologies</h2>
        <p class="ds-lede">An early professional environment that continues to sharpen both the technical and creative sides of the craft.</p>
    </div>
    <div class="ds-body">
    <div class="mc-panel slab">
        <div class="mc-shot"><div class="browser-frame">
            <div class="browser-bar"><span class="browser-dot r"></span><span class="browser-dot y"></span><span class="browser-dot g"></span><span class="browser-url">montecarlotechnologies.com</span></div>
            <img class="browser-shot" src="assets/img/about-montecarlo-technologies.png" alt="Monte Carlo Technologies website featuring Prince Edward as Editor Trainee">
        </div></div>
        <div class="mc-lower">
            <div class="mc-copy"><div class="mc-eyebrow">Currently Serving As</div><h3>Editor Trainee &amp; UI/UX Trainee</h3></div>
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
    </div>
</section>
<div class="frieze"></div>

<!-- ══════════ VIII. VISION & PHILOSOPHY ══════════ -->
<section class="dossier-section" id="vision">
    <div class="ds-head">
        <span class="ds-num">VIII</span><span class="ds-eyebrow">Looking Ahead</span><h2>Vision &amp; Philosophy</h2>
        <p class="ds-lede">Success built through discipline, consistency, humility, and continuous self-improvement.</p>
    </div>
    <div class="ds-body">
    <div class="vision-wrap">
        <p class="vision-quote">Every challenge presents an opportunity to learn. Every mistake becomes a valuable lesson. Every project represents another step toward excellence.</p>
        <div class="vision-attr">Professional Philosophy</div>
        <div class="goals-grid">
            <div class="goal-cell"><p>Become a professional Software Engineer</p></div>
            <div class="goal-cell"><p>Develop enterprise-level business systems</p></div>
            <div class="goal-cell"><p>Expand full-stack development expertise</p></div>
            <div class="goal-cell"><p>Strengthen leadership &amp; project management</p></div>
            <div class="goal-cell"><p>Keep learning emerging technologies</p></div>
            <div class="goal-cell"><p>Build scalable, real-world web apps</p></div>
            <div class="goal-cell"><p>Collaborate with innovation-driven teams</p></div>
            <div class="goal-cell"><p>Create technology with lasting impact</p></div>
        </div>
    </div>
    </div>
</section>

<!-- ══════════ IX. TESTIMONIALS ══════════ -->
<?php if (!empty($testimonials)): ?>
<div class="frieze"></div>
<section class="dossier-section" id="testimonials">
    <div class="ds-head">
        <span class="ds-num">IX</span><span class="ds-eyebrow">Client Voices</span><h2>Testimonials</h2>
        <p class="ds-lede">A few words from the people and businesses this work was built for.</p>
    </div>
    <div class="ds-body">
    <div class="testi-wrap">
        <div class="testi-track-wrap">
            <div class="testi-track" id="testiTrack">
                <?php foreach ($testimonials as $t):
                    $initial = strtoupper(mb_substr(trim($t['client_name']), 0, 1));
                ?>
                <div class="testi-slide">
                    <div class="testi-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <svg viewBox="0 0 24 24" fill="currentColor" class="<?= $i <= $t['rating'] ? 'filled' : 'empty' ?>"><path d="M12 2l2.9 6.6 7.1.6-5.4 4.7 1.6 7-6.2-3.8L6 21l1.6-7L2.2 9.2l7.1-.6z"/></svg>
                        <?php endfor; ?>
                    </div>
                    <p class="testi-quote"><?= htmlspecialchars($t['testimonial']) ?></p>
                    <div class="testi-person">
                        <span class="testi-avatar">
                            <?php if (!empty($t['avatar_url'])): ?>
                                <img src="<?= htmlspecialchars($t['avatar_url']) ?>" alt="<?= htmlspecialchars($t['client_name']) ?>">
                            <?php else: ?>
                                <?= htmlspecialchars($initial) ?>
                            <?php endif; ?>
                        </span>
                        <span class="testi-name"><?= htmlspecialchars($t['client_name']) ?></span>
                        <span class="testi-loc"><?= htmlspecialchars($t['client_location']) ?></span>
                        <?php if (!empty($t['venture'])): ?><span class="testi-venture"><?= htmlspecialchars($t['venture']) ?></span><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php if (count($testimonials) > 1): ?>
        <div class="testi-nav">
            <button class="testi-arrow" id="testiPrev" aria-label="Previous testimonial"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg></button>
            <div class="testi-dots" id="testiDots">
                <?php foreach ($testimonials as $di => $_): ?><span class="<?= $di === 0 ? 'active' : '' ?>"></span><?php endforeach; ?>
            </div>
            <button class="testi-arrow" id="testiNext" aria-label="Next testimonial"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></button>
        </div>
        <?php endif; ?>
    </div>
    </div>
</section>
<?php endif; ?>
<div class="frieze"></div>

<!-- ══════════ X. CONTACT ══════════ -->
<section class="dossier-section" id="contact">
    <div class="ds-head">
        <span class="ds-num">X</span><span class="ds-eyebrow">Say Hello</span><h2>Contact Information</h2>
        <p class="ds-lede">Open to collaboration, networking, and opportunities that value innovation.</p>
    </div>
    <div class="ds-body">
    <div class="contact-panel">
        <a href="https://github.com/guevaraprinceedward/" target="_blank" rel="noopener" class="contact-cell">
            <span class="cc-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.58 2 12.25c0 4.53 2.87 8.37 6.84 9.73.5.1.68-.22.68-.5 0-.24-.01-1.04-.01-1.89-2.78.61-3.37-1.2-3.37-1.2-.45-1.18-1.11-1.5-1.11-1.5-.9-.63.07-.62.07-.62 1 .07 1.53 1.05 1.53 1.05.89 1.56 2.34 1.11 2.91.85.09-.66.35-1.11.63-1.37-2.22-.26-4.56-1.14-4.56-5.06 0-1.12.39-2.03 1.03-2.75-.1-.26-.45-1.31.1-2.73 0 0 .84-.28 2.75 1.05a9.34 9.34 0 0 1 5 0c1.91-1.33 2.75-1.05 2.75-1.05.55 1.42.2 2.47.1 2.73.64.72 1.03 1.63 1.03 2.75 0 3.93-2.35 4.79-4.58 5.05.36.32.68.94.68 1.9 0 1.37-.01 2.47-.01 2.81 0 .28.18.61.69.5A10.26 10.26 0 0 0 22 12.25C22 6.58 17.52 2 12 2z"/></svg></span>
            <span class="cc-label">GitHub</span><span class="cc-handle">guevaraprinceedward</span><span class="cc-sub">github.com/guevaraprinceedward</span>
        </a>
        <a href="https://www.facebook.com/guevaraprinceedward.01/" target="_blank" rel="noopener" class="contact-cell">
            <span class="cc-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5 3.66 9.15 8.44 9.94v-7.03H7.9v-2.9h2.54V9.85c0-2.5 1.5-3.89 3.79-3.89 1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.87h2.78l-.44 2.9h-2.34V22c4.78-.79 8.44-4.93 8.44-9.94z"/></svg></span>
            <span class="cc-label">Facebook</span><span class="cc-handle">Prince Edward Guevara</span><span class="cc-sub">facebook.com/guevaraprinceedward.01</span>
        </a>
        <a href="https://www.instagram.com/_thepresence.noir/" target="_blank" rel="noopener" class="contact-cell">
            <span class="cc-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1"/></svg></span>
            <span class="cc-label">Instagram</span><span class="cc-handle">@_thepresence.noir</span><span class="cc-sub">instagram.com/_thepresence.noir</span>
        </a>
        <a href="mailto:guevaraprinceedward@gmail.com" class="contact-cell">
            <span class="cc-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3 7 12 13 21 7"/></svg></span>
            <span class="cc-label">Gmail</span><span class="cc-handle">guevaraprinceedward</span><span class="cc-sub">guevaraprinceedward@gmail.com</span>
        </a>
        <a href="tel:+639910654627" class="contact-cell">
            <span class="cc-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg></span>
            <span class="cc-label">Contact Number</span><span class="cc-handle">0991 065 4627</span><span class="cc-sub">Call or text</span>
        </a>
    </div>
    </div>
</section>

<p class="closing-line">"This portfolio represents not only the projects I have built, but the values that guide the work — integrity, discipline, continuous learning, and a genuine passion for technology."</p>

</div><!-- /.dossier -->

<footer>
    <div class="foot">
        <div class="foot-brand"><img src="<?= SCHOOL_LOGO ?>" alt="CMDI logo"><span>COPS<small>Canteen Ordering Platform System &middot; CARD-MRI Development Institute</small></span></div>
        <div class="foot-links">
            <a href="index.php">HOME</a>
            <a href="menu.php">MENU</a>
            <a href="about-us.php">ABOUT</a>
            <?php if ($isLoggedIn): ?><a href="log-out.php">LOG OUT</a><?php else: ?><a href="log-in.php">LOG IN</a><?php endif; ?>
        </div>
    </div>
    <div class="foot-bottom">© 2026 <span>Prince Edward Guevara</span> — Portfolio &amp; Professional Dossier.</div>
</footer>

</div><!-- /.stage -->
</div><!-- /.forum -->

<script>

// ── Laurel wreath (generated leaves) ──
(function() {
    
    var svg = document.getElementById('wreath'), ns = 'http://www.w3.org/2000/svg';
    
    [-1, 1].forEach(function(s) {

        for (var i = 0; i < 11; i++) {

            var phi = (8 + i * 15.5) * Math.PI / 180, R = 33;
            var x = 50 + s * R * Math.sin(phi), y = 52 + R * Math.cos(phi);
            var tang = Math.atan2(-Math.sin(phi), s * Math.cos(phi)) * 180 / Math.PI;

            [-1, 1].forEach(function(side) {

                var l = document.createElementNS(ns, 'ellipse');
                var off = side * 3.2, nx = Math.sin(phi) * s, ny = Math.cos(phi);

                l.setAttribute('cx', x + nx * off); l.setAttribute('cy', y + ny * off);
                l.setAttribute('rx', 7 - i * .25); l.setAttribute('ry', 2.4);
                l.setAttribute('transform', 'rotate(' + (tang + side * 28) + ' ' + (x + nx * off) + ' ' + (y + ny * off) + ')');
                l.setAttribute('fill', 'url(#lg)'); l.setAttribute('opacity', '.95');

                svg.appendChild(l);

            });
        }
    });
})();

// ── Temple gate: load bar → hinged doors swing open → camera walks through → page rises ──
(function() {

    var gate = document.getElementById('gate'), body = document.body;
    var calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function finish() { 
        
        gate.classList.add('done'); body.classList.remove('gate-lock'); body.classList.add('revealed'); 
    
    }

    if (calm) { 
            
        finish(); return; 
    
    }
    
        setTimeout(finish, 12000); // safety net
        requestAnimationFrame(function() { 
                
                requestAnimationFrame(function() { 
            
                gate.classList.add('go');
        
                }
            
            ); 
        }); 

    var loaded = new Promise(function(r) { 
        
        if (document.readyState === 'complete') r(); else window.addEventListener('load', r); 
    
    }

    );

    var minimum = new Promise(function(r) {
        
        setTimeout(r, 2700); 
    
    }

    );

    Promise.all([loaded, minimum]).then(function() {

        gate.classList.add('open');
        setTimeout(function(){ gate.classList.add('enter'); }, 2000);
        setTimeout(function(){ body.classList.add('revealed'); }, 2700);
        setTimeout(finish, 4400);

    }

    );
}

)();

// ── Header gets solid once you scroll ──
(function(){ var h=document.getElementById('siteHeader'); function f(){h.classList.toggle('scrolled',window.scrollY>30);} window.addEventListener('scroll',f,{passive:true}); f(); })();

// ── Scroll progress bar ──
(function(){ var p=document.getElementById('progress'); function f(){ var h=document.documentElement; p.style.width=(h.scrollTop/((h.scrollHeight-h.clientHeight)||1)*100)+'%'; } window.addEventListener('scroll',f,{passive:true}); f(); })();

// ── Cursor spotlight on marble cards ──
document.addEventListener('pointermove', function(e){
    var t = e.target.closest && e.target.closest('.slab,.fam-mentor-card,.goal-cell,.contact-cell,.collab-card,.chip,.org-item');
    if (!t) return;
    var r = t.getBoundingClientRect();
    t.style.setProperty('--mx', (e.clientX - r.left) + 'px');
    t.style.setProperty('--my', (e.clientY - r.top) + 'px');
});

// ── 3D tilt on portraits, medallions and browser frames (mouse only) ──
document.querySelectorAll('.arch-wrap,.bust,.collab-emblem-wrap,.lead-photo,.browser-frame').forEach(function(el){
    el.setAttribute('data-tilt','');
    el.addEventListener('pointermove', function(e){
        if (e.pointerType !== 'mouse') return;
        var r = el.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
        el.style.transform = 'perspective(1000px) rotateY(' + (x * 7) + 'deg) rotateX(' + (-y * 7) + 'deg)';
    });
    el.addEventListener('pointerleave', function(){ el.style.transform = ''; });
});

// ── Quiet reveal for chapter content ──
(function(){
    if (!('IntersectionObserver' in window)) return;
    var els = document.querySelectorAll('.ds-body > *:not([data-paginate]),.fam-mentor-card,.goal-cell,.strength-card,.contact-cell,.org-item');
    var io = new IntersectionObserver(function(entries){
        entries.forEach(function(en){ if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
    }, { threshold: .12 });
    els.forEach(function(el, i){ el.classList.add('rv'); el.style.animationDelay = ((i % 4) * 90) + 'ms'; io.observe(el); });
})();

// ── Age counts up once the page is revealed ──
(function(){
    var el = document.querySelector('.dh-meta-item .mv');
    if (!el || el.textContent.trim() !== '17') return;
    var done = false;
    new MutationObserver(function(_, mo){
        if (done || !document.body.classList.contains('revealed')) return;
        done = true; mo.disconnect();
        var t0 = null;
        (function step(t){ t0 = t0 || t; var k = Math.min((t - t0) / 1400, 1); el.textContent = Math.round(17 * (1 - Math.pow(1 - k, 3))); if (k < 1) requestAnimationFrame(step); })(performance.now());
    }).observe(document.body, { attributes: true, attributeFilter: ['class'] });
})();

// ── Scroll-spy for the index rail ──
const railLinks = document.querySelectorAll('#indexRail a');
const sections = Array.from(railLinks).map(a => document.getElementById(a.dataset.target)).filter(Boolean);
function updateRail(){
    let current = sections[0]?.id;
    const trigger = window.innerHeight * 0.4;
    sections.forEach(sec => { if (sec.getBoundingClientRect().top <= trigger) current = sec.id; });
    railLinks.forEach(a => a.classList.toggle('active', a.dataset.target === current));
}
window.addEventListener('scroll', updateRail, { passive: true });
updateRail();
railLinks.forEach(a => a.addEventListener('click', e => {
    e.preventDefault();
    document.getElementById(a.dataset.target)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}));

// ── Testimonials carousel ──
(function(){
    const track = document.getElementById('testiTrack');
    if (!track) return;
    const total = track.children.length;
    if (total <= 1) return;
    const dots = document.querySelectorAll('#testiDots span');
    const wrap = track.closest('.testi-wrap');
    let idx = 0, auto;
    function go(n){
        idx = (n + total) % total;
        track.style.transform = `translateX(-${idx * 100}%)`;
        dots.forEach((d, i) => d.classList.toggle('active', i === idx));
    }
    function startAuto(){ auto = setInterval(() => go(idx + 1), 6000); }
    function stopAuto(){ clearInterval(auto); }
    document.getElementById('testiPrev')?.addEventListener('click', () => { go(idx - 1); stopAuto(); startAuto(); });
    document.getElementById('testiNext')?.addEventListener('click', () => { go(idx + 1); stopAuto(); startAuto(); });
    dots.forEach((d, i) => d.addEventListener('click', () => { go(i); stopAuto(); startAuto(); }));
    wrap?.addEventListener('mouseenter', stopAuto);
    wrap?.addEventListener('mouseleave', startAuto);
    go(0); startAuto();
})();

// ── Generic paginator: Collaborations = 2/page, Ventures = 3/page ──
document.querySelectorAll('[data-paginate]').forEach(function(box){
    var per = parseInt(box.dataset.paginate, 10) || 1;
    var items = Array.from(box.children).filter(el => el.matches(box.dataset.item));
    var pages = Math.ceil(items.length / per);
    if (pages <= 1) return;
    var section = box.closest('section');
    var nav = document.createElement('div');
    nav.className = 'pg-nav';
    box.after(nav);
    var current = 0;
    function btn(label, onClick, opts){
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'pg-btn' + (opts && opts.active ? ' active' : '');
        b.innerHTML = label;
        if (opts && opts.aria) b.setAttribute('aria-label', opts.aria);
        if (opts && opts.disabled) b.disabled = true;
        b.addEventListener('click', onClick);
        return b;
    }
    function render(){
        nav.innerHTML = '';
        nav.appendChild(btn('&#8249;', () => show(current - 1, true), { aria: 'Previous page', disabled: current === 0 }));
        for (var i = 0; i < pages; i++) (function(n){
            nav.appendChild(btn(String(n + 1), () => show(n, true), { active: n === current, aria: 'Page ' + (n + 1) }));
        })(i);
        nav.appendChild(btn('&#8250;', () => show(current + 1, true), { aria: 'Next page', disabled: current === pages - 1 }));
        var info = document.createElement('span');
        info.className = 'pg-info';
        info.textContent = 'Page ' + (current + 1) + ' of ' + pages;
        nav.insertBefore(info, nav.children[1]);
    }
    function show(p, scroll){
        current = p;
        items.forEach(function(el, i){
            var on = Math.floor(i / per) === p;
            el.classList.toggle('pg-hide', !on);
            el.classList.remove('pg-last', 'pg-in');
            if (on) { void el.offsetWidth; el.classList.add('pg-in'); }
        });
        items[Math.min((p + 1) * per, items.length) - 1].classList.add('pg-last');
        render();
        if (scroll && section) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    show(0, false);
});
</script>
</body>
</html>