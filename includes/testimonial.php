<?php
include_once 'hotel-config.php';
$activePage = 'testimonial';

$notice = $_SESSION['testi_notice'] ?? null;
$error  = $_SESSION['testi_error'] ?? null;
unset($_SESSION['testi_notice'], $_SESSION['testi_error']);

$res = $conn->query("SELECT * FROM testimonials WHERE is_approved = 1 ORDER BY created_at DESC LIMIT 60");
$testimonials = $res->fetch_all(MYSQLI_ASSOC);

$count = count($testimonials);
$avg = $count ? array_sum(array_column($testimonials, 'rating')) / $count : 0;

function stars_html(int $rating, string $size = '14px'): string {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $filled = $i <= $rating;
        $out .= '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="'.($filled ? '#cfa76b' : 'none').'" stroke="#cfa76b" stroke-width="1.3"><path d="M12 2.5l2.9 6.6 7.1.7-5.4 4.8 1.6 7-6.2-3.7-6.2 3.7 1.6-7-5.4-4.8 7.1-.7z"/></svg>';
    }
    return $out;
}

// Single star outline that inherits currentColor — used inside the interactive
// star-input picker, where CSS (not PHP) controls the fill on hover/checked.
function star_icon(): string {
    return '<svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1"><path d="M12 2.5l2.9 6.6 7.1.7-5.4 4.8 1.6 7-6.2-3.7-6.2 3.7 1.6-7-5.4-4.8 7.1-.7z"/></svg>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Guest Reflections — Nocturne Manila Bay</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="hotel-theme.css">
<style>
/* PAGE-SPECIFIC: testimonial */
.testi-summary{position:relative;z-index:1;max-width:1240px;margin:0 auto;padding:0 32px 50px;display:flex;align-items:center;justify-content:center;gap:34px;flex-wrap:wrap;text-align:center}
.testi-avg{display:flex;flex-direction:column;align-items:center;gap:6px}
.testi-avg-num{font-family:'Cormorant Garamond',serif;font-size:52px;color:var(--h-gold);font-weight:700;line-height:1}
.testi-avg-stars{display:flex;gap:2px}
.testi-avg-count{font-size:11px;color:var(--h-muted);letter-spacing:0.06em;text-transform:uppercase}
.testi-divider{width:1px;height:56px;background:var(--h-line)}
.testi-cta-inline{text-align:left}
.testi-cta-inline p{font-size:12.5px;color:var(--h-muted);max-width:280px;margin-bottom:12px;line-height:1.6}

.testi-notice{max-width:900px;margin:0 auto 30px;padding:0 32px}
.testi-notice .box{padding:13px 16px;border-radius:4px;font-size:12.5px}
.testi-notice .ok{background:rgba(127,168,118,0.1);border:1px solid rgba(127,168,118,0.35);color:#c7e0c2}
.testi-notice .err{background:rgba(192,87,74,0.1);border:1px solid rgba(192,87,74,0.35);color:#f0c9c2}

.testi-grid{position:relative;z-index:1;max-width:1240px;margin:0 auto;padding:0 32px 90px;columns:3 340px;column-gap:22px}
.testi-card{background:var(--h-card);border:1px solid var(--h-line);border-radius:4px;padding:28px 26px;margin-bottom:22px;break-inside:avoid;display:flex;flex-direction:column;gap:14px}
.testi-card .stars-row{display:flex;gap:3px}
.testi-quote{font-family:'Cormorant Garamond',serif;font-style:italic;font-size:17px;line-height:1.6;color:var(--h-text)}
.testi-quote::before{content:'"';color:var(--h-gold)}
.testi-quote::after{content:'"';color:var(--h-gold)}
.testi-foot{display:flex;align-items:center;justify-content:space-between;margin-top:auto;padding-top:4px}
.testi-name{font-family:'Cormorant Garamond',serif;font-size:15px;color:var(--h-champagne);font-weight:600}
.testi-date{font-size:10.5px;color:var(--h-muted);letter-spacing:0.04em}
.testi-empty{text-align:center;color:var(--h-muted);font-size:13px;padding:50px 0}

/* Share section */
.share-section{position:relative;z-index:1;max-width:640px;margin:0 auto;padding:0 24px 130px}
.share-card{background:var(--h-card);border:1px solid var(--h-gold-dim);border-radius:6px;padding:38px 36px;text-align:center}
.share-card h3{font-family:'Cormorant Garamond',serif;font-size:26px;color:var(--h-champagne);margin-bottom:8px}
.share-card p{font-size:12.5px;color:var(--h-muted);margin-bottom:24px;max-width:420px;margin-left:auto;margin-right:auto;line-height:1.7}
.share-name-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(207,167,107,0.08);border:1px solid rgba(207,167,107,0.25);border-radius:100px;padding:8px 18px;font-size:13px;color:var(--h-champagne);margin-bottom:22px}
.share-name-badge svg{flex-shrink:0}

/* Star input (CSS-only, DOM order 5..1 + row-reverse) */
.star-input{display:inline-flex;flex-direction:row-reverse;gap:4px;justify-content:center;margin-bottom:20px}
.star-input input{display:none}
.star-input label{font-size:0;cursor:pointer;color:var(--h-line);transition:color 0.15s}
.star-input label svg{width:32px;height:32px;pointer-events:none}
.star-input input:checked ~ label,
.star-input label:hover,
.star-input label:hover ~ label{color:var(--h-gold)}

.testi-form{display:flex;flex-direction:column;gap:16px;text-align:left}
.testi-form textarea{width:100%;background:var(--h-surface);border:1px solid var(--h-line);border-radius:3px;color:var(--h-champagne);font-size:14px;padding:12px 14px;outline:none;resize:vertical;min-height:100px;font-family:'Jost',sans-serif;transition:border-color 0.2s}
.testi-form textarea:focus{border-color:var(--h-gold)}

.modal-overlay{position:fixed;inset:0;background:rgba(4,4,6,0.75);backdrop-filter:blur(6px);z-index:500;display:none;align-items:center;justify-content:center;padding:24px}
.modal-overlay.open{display:flex}
.testi-modal{width:100%;max-width:440px;background:#101013;border:1px solid var(--h-gold-dim);border-radius:6px;padding:38px 34px;position:relative;text-align:center;max-height:90vh;overflow-y:auto}
.testi-modal h2{font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--h-champagne);margin-bottom:6px}
.testi-modal .modal-sub{font-size:12px;color:var(--h-muted);margin-bottom:22px}
.modal-close{position:absolute;top:14px;right:16px;background:none;border:none;color:var(--h-muted);font-size:20px;cursor:pointer;line-height:1}
.modal-close:hover{color:var(--h-champagne)}

@media(max-width:900px){ .testi-grid{columns:2 280px} }
@media(max-width:640px){ .testi-grid{columns:1} .share-card{padding:30px 22px} }
</style>
</head>
<body>
<?php include_once 'sidebar.php'; ?>
<?php include_once 'topheader.php'; ?>

<section class="page-hero">
    <div class="sec-eyebrow">In Their Words</div>
    <h1>Guest <em>Reflections</em></h1>
    <p>What our guests carry with them after a stay at Nocturne Manila Bay.</p>
</section>

<div class="testi-summary">
    <div class="testi-avg">
        <div class="testi-avg-num"><?= $count ? number_format($avg, 1) : '—' ?></div>
        <div class="testi-avg-stars"><?= stars_html((int)round($avg), '16px') ?></div>
        <div class="testi-avg-count"><?= $count ?> Testimonial<?= $count !== 1 ? 's' : '' ?></div>
    </div>
</div>

<?php if ($notice): ?><div class="testi-notice"><div class="box ok"><?= htmlspecialchars($notice) ?></div></div><?php endif; ?>
<?php if ($error): ?><div class="testi-notice"><div class="box err"><?= htmlspecialchars($error) ?></div></div><?php endif; ?>

<div class="testi-grid">
    <?php if (empty($testimonials)): ?>
        <div class="testi-empty">No testimonials yet — be the first to share your experience.</div>
    <?php else: foreach ($testimonials as $t): ?>
    <div class="testi-card">
        <div class="stars-row"><?= stars_html((int)$t['rating']) ?></div>
        <p class="testi-quote"><?= htmlspecialchars($t['message']) ?></p>
        <div class="testi-foot">
            <span class="testi-name"><?= htmlspecialchars($t['name']) ?></span>
            <span class="testi-date"><?= date('M Y', strtotime($t['created_at'])) ?></span>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<section class="share-section">
    <div class="share-card">
        <h3>Share Your Experience</h3>

        <?php if (isHotelLoggedIn()): ?>
            <p>Your testimonial will be posted under your account name.</p>
            <div class="share-name-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#cfa76b" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
                <?= htmlspecialchars($_SESSION['user']['name']) ?>
            </div>
            <form class="testi-form" method="POST" action="process-testimonial.php" style="text-align:center">
                <div class="star-input">
                    <input type="radio" name="rating" value="5" id="star5"><label for="star5"><?= star_icon() ?></label>
                    <input type="radio" name="rating" value="4" id="star4"><label for="star4"><?= star_icon() ?></label>
                    <input type="radio" name="rating" value="3" id="star3" checked><label for="star3"><?= star_icon() ?></label>
                    <input type="radio" name="rating" value="2" id="star2"><label for="star2"><?= star_icon() ?></label>
                    <input type="radio" name="rating" value="1" id="star1"><label for="star1"><?= star_icon() ?></label>
                </div>
                <textarea name="message" placeholder="Tell us about your stay..." maxlength="600" required></textarea>
                <button type="submit" class="btn-gold" style="align-self:center">Post Testimonial</button>
            </form>
        <?php else: ?>
            <p>Sign in isn't required — just your name and a rating.</p>
            <button type="button" class="btn-gold" id="openTestiModal">Leave a Testimonial</button>
        <?php endif; ?>
    </div>
</section>

<footer><p>© 2026 <span>Nocturne Manila Bay</span> — A Property of AyosCoffeeNegosyo Hospitality.</p></footer>

<?php if (!isHotelLoggedIn()): ?>
<div class="modal-overlay" id="testiOverlay">
    <div class="testi-modal">
        <button class="modal-close" id="testiClose">&times;</button>
        <h2>Leave a Testimonial</h2>
        <p class="modal-sub">Your name and rating help other guests get to know Nocturne.</p>
        <form class="testi-form" method="POST" action="process-testimonial.php">
            <div class="field" style="text-align:left">
                <label>Your Name</label>
                <input type="text" name="name" maxlength="100" placeholder="e.g. Andrea Santos" required>
            </div>
            <div class="star-input" style="align-self:center;margin:4px 0 8px">
                <input type="radio" name="rating" value="5" id="g-star5"><label for="g-star5"><?= star_icon() ?></label>
                <input type="radio" name="rating" value="4" id="g-star4"><label for="g-star4"><?= star_icon() ?></label>
                <input type="radio" name="rating" value="3" id="g-star3" checked><label for="g-star3"><?= star_icon() ?></label>
                <input type="radio" name="rating" value="2" id="g-star2"><label for="g-star2"><?= star_icon() ?></label>
                <input type="radio" name="rating" value="1" id="g-star1"><label for="g-star1"><?= star_icon() ?></label>
            </div>
            <textarea name="message" placeholder="Tell us about your stay..." maxlength="600" required></textarea>
            <button type="submit" class="btn-gold" style="align-self:center">Post Testimonial</button>
        </form>
    </div>
</div>
<script>
document.getElementById('openTestiModal').addEventListener('click', () => document.getElementById('testiOverlay').classList.add('open'));
document.getElementById('testiClose').addEventListener('click', () => document.getElementById('testiOverlay').classList.remove('open'));
document.getElementById('testiOverlay').addEventListener('click', (e) => { if (e.target.id === 'testiOverlay') document.getElementById('testiOverlay').classList.remove('open'); });
</script>
<?php endif; ?>
</body>
</html>