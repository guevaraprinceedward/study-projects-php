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

// ── ENSURE TABLE EXISTS ───────────────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    branch VARCHAR(20) DEFAULT 'laguna',
    rating TINYINT(1) DEFAULT 5,
    message TEXT NOT NULL,
    is_approved TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ── FETCH TESTIMONIALS ────────────────────────────────────────────────────
$testimonials = [];
$tRes = $conn->query("SELECT id, name, branch, rating, message, created_at FROM testimonials WHERE is_approved = 1 ORDER BY created_at DESC LIMIT 50");
if ($tRes) { while ($tr = $tRes->fetch_assoc()) $testimonials[] = $tr; }

// ── PARTNER CLIENTS ───────────────────────────────────────────────────────
// These are our featured business partners / client testimonials
$partnerTestimonials = [
    [
        "name"    => "Carlo Reyes",
        "role"    => "CEO, Reyes & Co. Catering",
        "branch"  => "laguna",
        "rating"  => 5,
        "avatar"  => "CR",
        "color"   => "#c9a84c",
        "message" => "AyosCoffeeNegosyo has been our go-to coffee partner for corporate events. Their Laguna branch delivers consistently excellent quality — every espresso shot is dialed in perfectly. We've partnered with them for over a year and our clients always rave about the coffee selection.",
        "date"    => "April 2026",
    ],
    [
        "name"    => "Isabelle Tan",
        "role"    => "Owner, La Mesa Events Place",
        "branch"  => "manila",
        "rating"  => 5,
        "avatar"  => "IT",
        "color"   => "#6aaa52",
        "message" => "We exclusively recommend AyosCoffeeNegosyo to all our event clients. The Manila branch team is incredibly professional — they set up coffee stations that look and taste amazing. My guests always ask where the coffee is from, and I'm proud to say it's from our trusted partners.",
        "date"    => "March 2026",
    ],
    [
        "name"    => "Miguel Santos",
        "role"    => "Director, Santos Hospitality Group",
        "branch"  => "laguna",
        "rating"  => 5,
        "avatar"  => "MS",
        "color"   => "#c0392b",
        "message" => "From the Affogato to the Spanish Latte — the menu is thoughtfully curated. As a hospitality professional, I value consistency above all else, and AyosCoffeeNegosyo delivers every single time. Their stock management and ordering system is also impressively seamless.",
        "date"    => "March 2026",
    ],
    [
        "name"    => "Patricia Lim",
        "role"    => "Founder, Bloom Flower Studio",
        "branch"  => "manila",
        "rating"  => 5,
        "avatar"  => "PL",
        "color"   => "#8a6f2e",
        "message" => "I pop in before every big arrangement day. The Caramel Macchiato from the Manila branch is my fuel — perfectly sweet, never overpowering. It's the kind of coffee shop that feels like it was designed for people who actually care about what they drink.",
        "date"    => "February 2026",
    ],
    [
        "name"    => "Jerome dela Cruz",
        "role"    => "GM, Pinnacle Co-Working Spaces",
        "branch"  => "laguna",
        "rating"  => 5,
        "avatar"  => "JD",
        "color"   => "#4a7a3a",
        "message" => "We partnered with AyosCoffeeNegosyo to supply daily coffee for our 200+ co-working members. The subscription arrangement has been flawless — zero delays, premium quality, and our members love the rotating specials. Genuinely one of the best business decisions we've made.",
        "date"    => "January 2026",
    ],
    [
        "name"    => "Sophia Navarro",
        "role"    => "Head Chef, Navarro's Kitchen",
        "branch"  => "laguna",
        "rating"  => 5,
        "avatar"  => "SN",
        "color"   => "#d4820a",
        "message" => "As someone who's worked in professional kitchens for 15 years, I have very high standards. AyosCoffeeNegosyo's Tiramisu and Crème Brûlée match restaurant-level quality. We've been sourcing desserts from their Laguna branch for our catering arm and the feedback has been overwhelmingly positive.",
        "date"    => "December 2025",
    ],
];

// ── STATS ─────────────────────────────────────────────────────────────────
$totalReviews  = count($testimonials) + count($partnerTestimonials);
$avgRating     = 5.0;
if (!empty($testimonials)) {
    $sum = array_sum(array_column($testimonials, 'rating'));
    $avgRating = round(($sum / count($testimonials) + 25.0) / (count($testimonials) + 5), 1); // weighted with partner 5s
}
$laguna = count(array_filter($testimonials, fn($t) => $t['branch'] === 'laguna'));
$manila = count($testimonials) - $laguna;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Testimonials — AyosCoffeeNegosyo</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400;1,600;1,700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --bg:#0b0b09;--surface:#131310;--card:#1a1a16;--card2:#1e1e1a;--border:#2c2c24;
    --gold:#c9a84c;--gold-dim:#8a6f2e;--gold-pale:rgba(201,168,76,0.06);
    --green:#4a7a3a;--green-lt:#6aaa52;
    --cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8;--shadow:rgba(0,0,0,0.6);
    --sidebar-w:270px;--red:#c0392b;--amber:#d4820a;
}
html{scroll-behavior:smooth}
body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;position:relative;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;
    background:
        radial-gradient(ellipse 80% 60% at 5% -10%,rgba(201,168,76,0.07) 0%,transparent 55%),
        radial-gradient(ellipse 60% 80% at 95% 110%,rgba(74,122,58,0.06) 0%,transparent 55%),
        radial-gradient(ellipse 40% 40% at 50% 50%,rgba(201,168,76,0.02) 0%,transparent 70%);
    pointer-events:none;z-index:0}

/* SIDEBAR OVERLAY */
#sidebarOverlay{position:fixed;inset:0;background:rgba(0,0,0,0);z-index:199;pointer-events:none;transition:background 0.4s ease}
#sidebarOverlay.active{background:rgba(0,0,0,0.55);pointer-events:all}

/* SIDEBAR */
#sidebar{position:fixed;top:0;left:0;width:var(--sidebar-w);height:100vh;background:#111109;border-right:1px solid var(--border);z-index:200;display:flex;flex-direction:column;transform:translateX(calc(-1 * var(--sidebar-w)));transition:transform 0.42s cubic-bezier(0.4,0,0.2,1);will-change:transform}
#sidebar.open{transform:translateX(0);box-shadow:6px 0 40px rgba(0,0,0,0.7)}
.sb-header{padding:28px 24px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.sb-brand{display:flex;align-items:center;gap:11px}
.sb-brand-icon{width:34px;height:34px;border:1px solid var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.sb-brand-name{font-family:'Cormorant Garamond',serif;font-size:15px;font-weight:600;color:var(--cream);letter-spacing:0.03em;line-height:1.2}
.sb-brand-name span{display:block;font-size:11px;font-weight:400;font-family:'Jost',sans-serif;color:var(--gold);letter-spacing:0.1em;text-transform:uppercase}
.sb-close{width:30px;height:30px;border:1px solid var(--border);border-radius:50%;background:transparent;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:color 0.2s,border-color 0.2s,background 0.2s;flex-shrink:0}
.sb-close:hover{color:var(--cream);border-color:var(--gold-dim);background:rgba(201,168,76,0.06)}
.sb-nav{flex:1;padding:20px 16px;display:flex;flex-direction:column;gap:4px;overflow-y:auto}
.sb-nav-label{font-size:9.5px;letter-spacing:0.18em;text-transform:uppercase;color:var(--muted);padding:4px 10px 10px;margin-top:6px}
.sb-link{display:flex;align-items:center;gap:13px;padding:11px 14px;border-radius:6px;text-decoration:none;color:var(--muted);font-size:13.5px;font-weight:400;letter-spacing:0.02em;transition:color 0.2s,background 0.2s;position:relative;border:1px solid transparent}
.sb-link:hover{color:var(--cream);background:rgba(255,255,255,0.04)}
.sb-link.active{color:var(--gold);background:rgba(201,168,76,0.08);border-color:rgba(201,168,76,0.12)}
.sb-link svg{flex-shrink:0;opacity:0.7;transition:opacity 0.2s}
.sb-link:hover svg,.sb-link.active svg{opacity:1}
.sb-badge{margin-left:auto;background:var(--gold);color:#1a1400;font-size:10px;font-weight:700;min-width:20px;height:20px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;padding:0 6px}
.sb-divider{height:1px;background:var(--border);margin:8px 0}
.sb-footer{padding:16px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:4px}
.sb-logout{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:6px;text-decoration:none;color:#c0574a;font-size:13.5px;font-weight:400;letter-spacing:0.02em;transition:color 0.2s,background 0.2s;border:1px solid transparent}
.sb-logout:hover{color:#e06b5d;background:rgba(192,87,74,0.08);border-color:rgba(192,87,74,0.14)}
.sb-login{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:6px;text-decoration:none;color:var(--green-lt);font-size:13.5px;font-weight:400;letter-spacing:0.02em;transition:color 0.2s,background 0.2s;border:1px solid transparent}
.sb-login:hover{color:#fff;background:rgba(74,122,58,0.12);border-color:rgba(74,122,58,0.2)}
.sb-register{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:6px;text-decoration:none;color:var(--gold);font-size:13.5px;font-weight:400;letter-spacing:0.02em;transition:color 0.2s,background 0.2s;border:1px solid transparent}
.sb-register:hover{color:#fff;background:rgba(201,168,76,0.1);border-color:rgba(201,168,76,0.2)}

/* HEADER */
header{position:sticky;top:0;z-index:100;background:rgba(11,11,9,0.88);backdrop-filter:blur(18px);border-bottom:1px solid var(--border)}
.header-inner{max-width:1200px;margin:0 auto;padding:0 32px;height:68px;display:flex;align-items:center;justify-content:space-between}
.header-left{display:flex;align-items:center;gap:18px}
#sidebarToggle{width:40px;height:40px;border:1px solid var(--border);border-radius:6px;background:transparent;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:color 0.2s,border-color 0.2s,background 0.2s}
#sidebarToggle:hover{color:var(--gold);border-color:var(--gold-dim);background:rgba(201,168,76,0.06)}
.hb{display:flex;flex-direction:column;gap:5px;width:18px}
.hb span{display:block;height:1.5px;background:currentColor;border-radius:2px;transition:transform 0.3s ease,opacity 0.3s ease,width 0.3s ease;transform-origin:center}
.hb span:nth-child(3){width:12px}
#sidebarToggle.open .hb span:nth-child(1){transform:translateY(6.5px) rotate(45deg)}
#sidebarToggle.open .hb span:nth-child(2){opacity:0;transform:scaleX(0)}
#sidebarToggle.open .hb span:nth-child(3){transform:translateY(-6.5px) rotate(-45deg);width:18px}
.brand{display:flex;align-items:center;gap:12px;text-decoration:none}
.brand-icon{width:36px;height:36px;border:1px solid var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center}
.brand-name{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream);letter-spacing:0.04em}
.brand-name span{color:var(--gold)}
.header-right{display:flex;align-items:center;gap:10px}
.header-pill{display:flex;align-items:center;gap:8px;padding:7px 16px;border:1px solid var(--border);border-radius:100px;background:transparent;color:var(--muted);font-family:'Jost',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:0.06em;text-decoration:none;transition:color 0.2s,border-color 0.2s,background 0.2s;cursor:pointer}
.header-pill:hover{color:var(--gold);border-color:var(--gold-dim);background:rgba(201,168,76,0.06)}

/* HERO */
.hero{position:relative;z-index:1;text-align:center;padding:90px 32px 60px;max-width:760px;margin:0 auto}
.hero-eyebrow{display:inline-flex;align-items:center;gap:12px;font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:var(--gold);margin-bottom:26px}
.hero-eyebrow::before,.hero-eyebrow::after{content:'';width:36px;height:1px;background:var(--gold-dim)}
.hero h1{font-family:'Cormorant Garamond',serif;font-size:clamp(48px,7vw,80px);font-weight:700;line-height:1.05;color:var(--cream);letter-spacing:-0.02em;margin-bottom:20px}
.hero h1 em{font-style:italic;color:var(--gold)}
.hero p{font-size:15.5px;color:var(--muted);line-height:1.75;font-weight:300;max-width:520px;margin:0 auto}

/* STATS BAR */
.stats-bar{position:relative;z-index:1;max-width:800px;margin:0 auto 80px;padding:0 32px}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:var(--border);border:1px solid var(--border);border-radius:4px;overflow:hidden}
.stat-item{background:var(--card);padding:28px 20px;text-align:center}
.stat-num{font-family:'Cormorant Garamond',serif;font-size:42px;font-weight:700;color:var(--gold);line-height:1;margin-bottom:6px;letter-spacing:-0.02em}
.stat-label{font-size:10.5px;letter-spacing:0.14em;text-transform:uppercase;color:var(--muted);font-weight:500}

/* SECTION LABEL */
.section-wrap{position:relative;z-index:1;max-width:1200px;margin:0 auto;padding:0 32px 80px}
.section-hd{margin-bottom:40px}
.section-eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:var(--gold);margin-bottom:12px}
.section-eyebrow::before{content:'';width:22px;height:1px;background:var(--gold-dim)}
.section-hd h2{font-family:'Cormorant Garamond',serif;font-size:clamp(30px,4vw,48px);font-weight:700;color:var(--cream);line-height:1.1}
.section-hd h2 em{font-style:italic;color:var(--gold)}
.section-hd p{font-size:14px;color:var(--muted);margin-top:10px;line-height:1.7;font-weight:300;max-width:480px}

/* PARTNER CARDS — large featured layout */
.partner-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:24px}
.partner-grid-wide{grid-template-columns:repeat(2,1fr)}
.partner-card{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:32px 30px 28px;display:flex;flex-direction:column;gap:16px;transition:border-color 0.3s,box-shadow 0.3s,transform 0.3s;position:relative;overflow:hidden}
.partner-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--card-accent,var(--gold-dim)),transparent);opacity:0;transition:opacity 0.3s}
.partner-card:hover{border-color:var(--gold-dim);box-shadow:0 24px 60px rgba(0,0,0,0.45);transform:translateY(-4px)}
.partner-card:hover::before{opacity:1}
.partner-card.featured{grid-column:span 2}
.partner-stars{display:flex;gap:3px;margin-bottom:2px}
.p-star{width:13px;height:13px;color:var(--gold)}
.partner-quote{font-family:'Cormorant Garamond',serif;font-size:17.5px;font-style:italic;color:var(--cream);line-height:1.7;flex:1;position:relative;padding-left:20px}
.partner-quote::before{content:'\201C';position:absolute;left:0;top:-4px;font-size:48px;color:var(--gold-dim);line-height:1;font-style:normal;opacity:0.6}
.partner-author{display:flex;align-items:center;gap:14px;padding-top:18px;border-top:1px solid var(--border)}
.p-avatar{width:44px;height:44px;border-radius:50%;border:1px solid;display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:700;flex-shrink:0;text-transform:uppercase}
.p-name{font-size:14px;font-weight:600;color:var(--cream);letter-spacing:0.01em}
.p-role{font-size:11.5px;color:var(--muted);margin-top:2px;line-height:1.4}
.p-branch-tag{margin-left:auto;display:flex;align-items:center;gap:5px;font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);flex-shrink:0}
.p-dot{width:6px;height:6px;border-radius:50%;background:var(--green-lt);flex-shrink:0}
.p-dot.manila{background:var(--gold)}
.p-date{font-size:10px;color:var(--muted);margin-top:3px}

/* COMMUNITY REVIEWS */
.community-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}
.review-card{background:var(--card2);border:1px solid var(--border);border-radius:4px;padding:22px 22px 18px;display:flex;flex-direction:column;gap:12px;transition:border-color 0.3s,box-shadow 0.3s;opacity:0;transform:translateY(12px);animation:fadeUp 0.4s ease forwards}
@keyframes fadeUp{to{opacity:1;transform:translateY(0)}}
.review-card:hover{border-color:rgba(201,168,76,0.2);box-shadow:0 12px 32px rgba(0,0,0,0.35)}
.r-stars{display:flex;gap:2px}
.r-star{width:12px;height:12px;color:var(--gold)}
.r-star.empty{color:var(--border)}
.r-text{font-family:'Cormorant Garamond',serif;font-size:16px;font-style:italic;color:var(--cream);line-height:1.65;flex:1}
.r-meta{display:flex;align-items:center;gap:10px;padding-top:12px;border-top:1px solid var(--border)}
.r-avatar{width:34px;height:34px;border-radius:50%;border:1px solid var(--gold-dim);background:var(--surface);display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-size:13px;font-weight:700;color:var(--gold);flex-shrink:0;text-transform:uppercase}
.r-name{font-size:13px;font-weight:500;color:var(--cream)}
.r-branch{font-size:10.5px;color:var(--muted);display:flex;align-items:center;gap:4px;margin-top:1px}
.r-branch-dot{width:5px;height:5px;border-radius:50%;background:var(--green-lt)}
.r-branch-dot.manila{background:var(--gold)}
.r-date{margin-left:auto;font-size:10px;color:var(--muted);flex-shrink:0}

/* EMPTY STATE */
.empty-community{text-align:center;padding:60px 20px;border:1px dashed var(--border);border-radius:4px;background:var(--card)}
.empty-community svg{opacity:0.15;margin-bottom:16px}
.empty-community p{font-size:14px;color:var(--muted);line-height:1.7}
.empty-community h3{font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--text);margin-bottom:8px}

/* WRITE REVIEW CTA */
.cta-section{position:relative;z-index:1;text-align:center;padding:0 32px 100px;max-width:640px;margin:0 auto}
.cta-card{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:56px 48px;position:relative;overflow:hidden}
.cta-card::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 50% 0%,rgba(201,168,76,0.05) 0%,transparent 70%);pointer-events:none}
.cta-card h2{font-family:'Cormorant Garamond',serif;font-size:clamp(28px,4vw,42px);font-weight:700;color:var(--cream);line-height:1.1;margin-bottom:14px}
.cta-card h2 em{font-style:italic;color:var(--gold)}
.cta-card p{font-size:14px;color:var(--muted);line-height:1.7;margin-bottom:28px}
.cta-btn{display:inline-flex;align-items:center;gap:10px;padding:14px 32px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:#fff;cursor:pointer;transition:background 0.2s,transform 0.15s}
.cta-btn:hover{background:var(--green-lt);transform:translateY(-2px)}
.cta-btn:active{transform:translateY(0)}

/* FILTER TABS */
.filter-tabs{display:flex;gap:8px;margin-bottom:32px;flex-wrap:wrap}
.ftab{font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;padding:8px 20px;border-radius:100px;border:1px solid var(--border);background:transparent;color:var(--muted);cursor:pointer;transition:all 0.2s}
.ftab:hover,.ftab.active{border-color:var(--gold);color:var(--gold);background:var(--gold-pale)}

/* REVIEW MODAL */
#reviewModal{position:fixed;inset:0;z-index:300;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.75);backdrop-filter:blur(6px);opacity:0;pointer-events:none;transition:opacity 0.3s}
#reviewModal.show{opacity:1;pointer-events:all}
.review-modal-card{background:var(--card);border:1px solid var(--border);border-radius:6px;padding:36px;max-width:480px;width:94%;transform:translateY(20px);transition:transform 0.3s;position:relative;max-height:90vh;overflow-y:auto}
#reviewModal.show .review-modal-card{transform:translateY(0)}
.review-modal-title{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--cream);margin-bottom:8px}
.review-modal-title em{font-style:italic;color:var(--gold)}
.review-modal-sub{font-size:13px;color:var(--muted);line-height:1.65;margin-bottom:24px}
.review-close{position:absolute;top:14px;right:14px;width:30px;height:30px;border:1px solid var(--border);border-radius:50%;background:transparent;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s}
.review-close:hover{border-color:var(--gold-dim);color:var(--cream)}
.r-field{margin-bottom:16px}
.r-label{display:block;font-size:11px;font-weight:500;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted);margin-bottom:8px}
.r-input,.r-textarea,.r-select{width:100%;background:var(--surface);border:1px solid var(--border);border-radius:3px;color:var(--cream);font-family:'Jost',sans-serif;font-size:14px;padding:11px 14px;outline:none;transition:border-color 0.2s}
.r-input:focus,.r-textarea:focus,.r-select:focus{border-color:var(--gold-dim)}
.r-textarea{resize:vertical;min-height:100px;line-height:1.6}
.r-select option{background:var(--card)}
.star-picker{display:flex;gap:6px}
.star-pick{font-size:26px;cursor:pointer;color:var(--border);transition:color 0.15s,transform 0.15s;line-height:1;background:none;border:none;padding:0}
.star-pick.selected,.star-pick:hover{color:var(--gold)}
.star-pick:hover{transform:scale(1.15)}
.r-submit{width:100%;padding:14px;margin-top:10px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:#fff;cursor:pointer;transition:background 0.2s}
.r-submit:hover{background:var(--green-lt)}
.r-submit:disabled{opacity:0.5;cursor:not-allowed}
.r-error{background:rgba(192,57,43,0.1);border:1px solid rgba(192,57,43,0.3);border-radius:3px;padding:10px 14px;color:#e05a5a;font-size:13px;margin-bottom:14px;display:none}
.r-success{display:none;text-align:center;padding:20px 0}
.r-success-icon{width:64px;height:64px;margin:0 auto 16px;border:1px solid var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--green-lt)}
.r-success h3{font-family:'Cormorant Garamond',serif;font-size:26px;color:var(--cream);margin-bottom:8px}
.r-success p{font-size:13.5px;color:var(--muted);line-height:1.6}

/* TOAST */
#toast{position:fixed;bottom:30px;right:30px;z-index:999;background:var(--card);border:1px solid var(--gold-dim);border-radius:4px;padding:14px 20px;display:flex;align-items:center;gap:12px;font-size:14px;color:var(--cream);box-shadow:0 8px 32px rgba(0,0,0,0.5);transform:translateY(20px);opacity:0;transition:all 0.3s ease;pointer-events:none}
#toast.show{transform:translateY(0);opacity:1}
#toast .t-icon{color:var(--green-lt)}

footer{position:relative;z-index:1;border-top:1px solid var(--border);padding:30px 32px;text-align:center}
footer p{font-size:12px;color:var(--muted);letter-spacing:0.06em}
footer p span{color:var(--gold-dim)}

@media(max-width:900px){
    .partner-grid{grid-template-columns:repeat(2,1fr)}
    .partner-card.featured{grid-column:span 2}
    .stats-grid{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:640px){
    .header-inner{padding:0 16px}
    .hero{padding:60px 20px 50px}
    .stats-bar{padding:0 16px}
    .stats-grid{grid-template-columns:repeat(2,1fr)}
    .section-wrap{padding:0 16px 60px}
    .cta-section{padding:0 16px 80px}
    .cta-card{padding:40px 24px}
    .partner-grid{grid-template-columns:1fr}
    .partner-card.featured{grid-column:auto}
    .community-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>

<div id="sidebarOverlay"></div>

<aside id="sidebar" role="navigation" aria-label="Main navigation">
    <div class="sb-header">
        <div class="sb-brand">
            <div class="sb-brand-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
            </div>
            <div class="sb-brand-name">AyosCoffeeNegosyo<span>Est. 2026</span></div>
        </div>
        <button class="sb-close" id="sidebarClose" aria-label="Close sidebar">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    <nav class="sb-nav">
        <div class="sb-nav-label">Navigation</div>
        <a href="index.php" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Menu
        </a>
        <a href="testimonial.php" class="sb-link active">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Testimonials
        </a>

        <div class="sb-nav-label">Branch</div>
        <a href="index.php?branch=laguna" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Laguna Branch
        </a>
        <a href="index.php?branch=manila" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Manila Branch
        </a>

        <?php if ($isLoggedIn): ?>
        <div class="sb-divider"></div>
        <div class="sb-nav-label">Account</div>
        <a href="profile.php" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profile
        </a>
        <a href="dashboard.php" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a>
        <div class="sb-divider"></div>
        <div class="sb-nav-label">Orders</div>
        <a href="orders.php" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            My Orders
        </a>
        <a href="cart.php" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            Cart
            <span class="sb-badge" id="sidebarCartCount">0</span>
        </a>
        <?php else: ?>
        <div class="sb-divider"></div>
        <div class="sb-nav-label">Orders</div>
        <a href="cart.php" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            Cart
            <span class="sb-badge" id="sidebarCartCount">0</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sb-footer">
        <?php if ($isLoggedIn): ?>
        <a href="log-out.php" class="sb-logout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
        </a>
        <?php else: ?>
        <a href="log-in.php" class="sb-login">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            Login
        </a>
        <a href="register.php" class="sb-register">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            Sign Up
        </a>
        <?php endif; ?>
    </div>
</aside>

<header>
    <div class="header-inner">
        <div class="header-left">
            <button id="sidebarToggle" aria-label="Open sidebar" aria-expanded="false">
                <div class="hb"><span></span><span></span><span></span></div>
            </button>
            <a href="index.php" class="brand">
                <div class="brand-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
                </div>
                <span class="brand-name">My <span>AyosCoffeeNegosyo</span></span>
            </a>
        </div>
        <div class="header-right">
            <button class="header-pill" onclick="openReviewModal()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Write a Review
            </button>
        </div>
    </div>
</header>

<!-- HERO -->
<section class="hero">
    <div class="hero-eyebrow">Since 2026</div>
    <h1>What Our <em>Partners</em><br>Are Saying</h1>
    <p>Voices from our valued business partners and guests across both branches — real experiences, honest words.</p>
</section>

<!-- STATS -->
<div class="stats-bar">
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-num"><?= $totalReviews ?>+</div>
            <div class="stat-label">Total Reviews</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">5.0</div>
            <div class="stat-label">Avg. Rating</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">2</div>
            <div class="stat-label">Branches</div>
        </div>  
        <div class="stat-item">
            <div class="stat-num">12+</div>
            <div class="stat-label">Partners</div>
        </div>
    </div>
</div>

<!-- PARTNER TESTIMONIALS -->
<div class="section-wrap">
    <div class="section-hd">
        <div class="section-eyebrow">Business Partners</div>
        <h2>Trusted by <em>Professionals</em></h2>
        <p>Featured testimonials from our partner businesses and corporate clients.</p>
    </div>

    <div class="partner-grid">
        <?php foreach ($partnerTestimonials as $i => $p):
            $isFirst = $i === 0;
        ?>
        <div class="partner-card <?= $isFirst ? 'featured' : '' ?>" style="--card-accent:<?= $p['color'] ?>">
            <div class="partner-stars">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                <svg class="p-star" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <?php endfor; ?>
            </div>
            <div class="partner-quote"><?= htmlspecialchars($p['message']) ?></div>
            <div class="partner-author">
                <div class="p-avatar" style="border-color:<?= $p['color'] ?>;color:<?= $p['color'] ?>;background:rgba(0,0,0,0.3)">
                    <?= $p['avatar'] ?>
                </div>
                <div>
                    <div class="p-name"><?= htmlspecialchars($p['name']) ?></div>
                    <div class="p-role"><?= htmlspecialchars($p['role']) ?></div>
                </div>
                <div style="margin-left:auto;text-align:right">
                    <div class="p-branch-tag">
                        <span class="p-dot <?= $p['branch'] === 'manila' ? 'manila' : '' ?>"></span>
                        <?= ucfirst($p['branch']) ?>
                    </div>
                    <div class="p-date"><?= $p['date'] ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- COMMUNITY REVIEWS -->
<div class="section-wrap" style="padding-top:0">
    <div class="section-hd">
        <div class="section-eyebrow">Community Reviews</div>
        <h2>From Our <em>Guests</em></h2>
        <p>Reviews shared by everyday visitors who love AyosCoffeeNegosyo.</p>
    </div>

    <!-- Filter tabs -->
    <div class="filter-tabs">
        <button class="ftab active" data-filter="all">All Reviews</button>
        <button class="ftab" data-filter="laguna">Laguna</button>
        <button class="ftab" data-filter="manila">Manila</button>
        <button class="ftab" data-filter="5">★★★★★ 5 Stars</button>
        <button class="ftab" data-filter="4">★★★★ 4 Stars</button>
    </div>

    <div class="community-grid" id="communityGrid">
        <?php if (empty($testimonials)): ?>
        <div class="empty-community" style="grid-column:1/-1">
            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <h3>No community reviews yet.</h3>
            <p>Be the first to share your experience with AyosCoffeeNegosyo!<br>Your review helps others discover something great.</p>
        </div>
        <?php else: ?>
        <?php foreach ($testimonials as $i => $t):
            $initials = strtoupper(substr(trim($t['name']), 0, 1));
            if (strpos(trim($t['name']), ' ') !== false) {
                $parts = explode(' ', trim($t['name']));
                $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
            }
            $rating  = (int)$t['rating'];
            $daysAgo = floor((time() - strtotime($t['created_at'])) / 86400);
            $dateStr = $daysAgo === 0 ? 'Today' : ($daysAgo === 1 ? 'Yesterday' : ($daysAgo < 30 ? $daysAgo . 'd ago' : date('M j, Y', strtotime($t['created_at']))));
        ?>
        <div class="review-card" data-branch="<?= $t['branch'] ?>" data-rating="<?= $rating ?>" style="animation-delay:<?= $i * 40 ?>ms">
            <div class="r-stars">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                <svg class="r-star <?= $s > $rating ? 'empty' : '' ?>" viewBox="0 0 24 24" fill="<?= $s <= $rating ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="1.5">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                <?php endfor; ?>
            </div>
            <div class="r-text"><?= htmlspecialchars($t['message']) ?></div>
            <div class="r-meta">
                <div class="r-avatar"><?= $initials ?></div>
                <div>
                    <div class="r-name"><?= htmlspecialchars($t['name']) ?></div>
                    <div class="r-branch">
                        <span class="r-branch-dot <?= $t['branch'] === 'manila' ? 'manila' : '' ?>"></span>
                        <?= ucfirst($t['branch']) ?> Branch
                    </div>
                </div>
                <div class="r-date"><?= $dateStr ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- CTA -->
<div class="cta-section">
    <div class="cta-card">
        <h2>Share Your <em>Story</em></h2>
        <p>Had a great experience at AyosCoffeeNegosyo? We'd love to hear from you. Your honest review helps us improve and helps new guests know what to expect.</p>
        <button class="cta-btn" onclick="openReviewModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Write a Review
        </button>
    </div>
</div>

<!-- REVIEW MODAL -->
<div id="reviewModal">
    <div class="review-modal-card">
        <button class="review-close" onclick="closeReviewModal()">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>

        <div id="reviewForm">
            <div class="review-modal-title">Share Your <em>Experience</em></div>
            <div class="review-modal-sub">Real reviews from real guests. Your feedback makes AyosCoffeeNegosyo better for everyone.</div>

            <div class="r-error" id="rError"></div>

            <div class="r-field">
                <label class="r-label">Your Name</label>
                <input type="text" class="r-input" id="rName" placeholder="e.g. Maria Santos" maxlength="100">
            </div>

            <div class="r-field">
                <label class="r-label">Branch Visited</label>
                <select class="r-select" id="rBranch">
                    <option value="laguna">Laguna Branch</option>
                    <option value="manila">Manila Branch</option>
                </select>
            </div>

            <div class="r-field">
                <label class="r-label">Rating</label>
                <div class="star-picker" id="starPicker">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button class="star-pick" data-val="<?= $i ?>" onclick="setRating(<?= $i ?>)">★</button>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="r-field">
                <label class="r-label">Your Review <span style="color:var(--muted);text-transform:none;letter-spacing:0;font-size:10px">(10–500 characters)</span></label>
                <textarea class="r-textarea" id="rMessage" placeholder="Tell us about your experience — the coffee, the food, the ambiance..." maxlength="500"></textarea>
                <div style="font-size:11px;color:var(--muted);margin-top:4px;text-align:right"><span id="rCharCount">0</span>/500</div>
            </div>

            <button class="r-submit" id="rSubmitBtn" onclick="submitReview()">Submit Review</button>
        </div>

        <div class="r-success" id="reviewSuccess">
            <div class="r-success-icon">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <h3>Thank You!</h3>
            <p>Your review has been submitted. We truly appreciate you taking the time to share your experience with us!</p>
        </div>
    </div>
</div>

<div id="toast">
    <svg class="t-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    <span id="toastMsg">Review submitted!</span>
</div>

<footer><p>© 2026 <span>My AyosCoffeeNegosyo</span> — All rights reserved.</p></footer>

<script>
// Sidebar
const sidebar=document.getElementById('sidebar'),sidebarOverlay=document.getElementById('sidebarOverlay'),sidebarToggle=document.getElementById('sidebarToggle'),sidebarClose=document.getElementById('sidebarClose');
function openSidebar(){sidebar.classList.add('open');sidebarOverlay.classList.add('active');sidebarToggle.classList.add('open');sidebarToggle.setAttribute('aria-expanded','true');document.body.style.overflow='hidden'}
function closeSidebar(){sidebar.classList.remove('open');sidebarOverlay.classList.remove('active');sidebarToggle.classList.remove('open');sidebarToggle.setAttribute('aria-expanded','false');document.body.style.overflow=''}
sidebarToggle.addEventListener('click',()=>sidebar.classList.contains('open')?closeSidebar():openSidebar());
sidebarClose.addEventListener('click',closeSidebar);
sidebarOverlay.addEventListener('click',closeSidebar);
document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeSidebar();closeReviewModal()}});

// Cart badge
fetch('cart_handler.php?count=1').then(r=>r.json()).then(data=>{
    const sb=document.getElementById('sidebarCartCount');
    if(sb)sb.textContent=data.count||0;
}).catch(()=>{});

// Filter tabs
const allCards = Array.from(document.querySelectorAll('.review-card'));
document.querySelectorAll('.ftab').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.ftab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const f = btn.dataset.filter;
        allCards.forEach(card => {
            const show = f === 'all'
                || (f === 'laguna' && card.dataset.branch === 'laguna')
                || (f === 'manila' && card.dataset.branch === 'manila')
                || (f === '5' && card.dataset.rating === '5')
                || (f === '4' && card.dataset.rating === '4');
            card.style.display = show ? '' : 'none';
        });
    });
});

// Review Modal
let selectedRating = 5;
setRating(5);

function setRating(val) {
    selectedRating = val;
    document.querySelectorAll('.star-pick').forEach((s, i) => s.classList.toggle('selected', i < val));
}

function openReviewModal() {
    document.getElementById('reviewModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeReviewModal() {
    document.getElementById('reviewModal').classList.remove('show');
    document.body.style.overflow = '';
}

document.getElementById('reviewModal').addEventListener('click', function(e) {
    if (e.target === this) closeReviewModal();
});

document.getElementById('rMessage').addEventListener('input', function() {
    document.getElementById('rCharCount').textContent = this.value.length;
});

async function submitReview() {
    const name    = document.getElementById('rName').value.trim();
    const branch  = document.getElementById('rBranch').value;
    const message = document.getElementById('rMessage').value.trim();
    const errEl   = document.getElementById('rError');
    errEl.style.display = 'none';

    if (!name) { errEl.textContent = 'Please enter your name.'; errEl.style.display = 'block'; return; }
    if (!message || message.length < 10) { errEl.textContent = 'Review must be at least 10 characters.'; errEl.style.display = 'block'; return; }

    const btn = document.getElementById('rSubmitBtn');
    btn.disabled = true; btn.textContent = 'Submitting...';

    const fd = new FormData();
    fd.append('name', name); fd.append('branch', branch);
    fd.append('rating', selectedRating); fd.append('message', message);

    try {
        const res  = await fetch('testimonials_handler.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            document.getElementById('reviewForm').style.display = 'none';
            document.getElementById('reviewSuccess').style.display = 'block';
            setTimeout(() => { closeReviewModal(); location.reload(); }, 2500);
        } else {
            errEl.textContent = data.message || 'Something went wrong.';
            errEl.style.display = 'block';
            btn.disabled = false; btn.textContent = 'Submit Review';
        }
    } catch(e) {
        errEl.textContent = 'Network error. Please try again.';
        errEl.style.display = 'block';
        btn.disabled = false; btn.textContent = 'Submit Review';
    }
}
</script>
</body>
</html>