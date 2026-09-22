<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/product-functions.php';

$page_title = "El Grande De La Torres — Timeless Elegance";
require_once __DIR__ . '/includes/header.php';

$featured = fetch_flagged_products('is_featured');
$new_arrivals = fetch_flagged_products('is_new_arrival');
$best_sellers = fetch_flagged_products('is_best_seller');

// 6 homepage testimonials (approved), newest first
$stmt = getDB()->prepare("SELECT r.*, u.first_name, u.last_name, u.user_id
                           FROM customer_reviews r JOIN users u ON u.user_id = r.user_id
                           WHERE r.is_homepage_testimonial = 1 AND r.is_approved = 1
                           ORDER BY r.created_at DESC LIMIT 6");
$stmt->execute();
$testimonials = $stmt->fetchAll();

// Does the current user already have a homepage testimonial?
$my_testimonial = null;
if (is_logged_in()) {
    $stmt = getDB()->prepare("SELECT * FROM customer_reviews WHERE user_id = ? AND is_homepage_testimonial = 1 LIMIT 1");
    $stmt->execute([current_user_id()]);
    $my_testimonial = $stmt->fetch() ?: null;
}
?>

<section class="hero-lux">
    <div class="hero-media">
        <img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?q=80&w=1800&auto=format&fit=crop" alt="El Grande De La Torres — editorial campaign">
    </div>
    <div class="hero-content">
        <span class="eyebrow hero-eyebrow">The 2026 Maison Collection</span>
        <h1 class="hero-title">Timeless <em>Elegance.</em></h1>
        <p class="hero-sub">Crafted for those who value excellence — clothing and essentials under one house of quiet, uncompromising distinction.</p>
        <div class="hero-actions">
            <a href="<?= SITE_URL ?>/clothing.php" class="btn-lux btn-lux-filled">Explore Clothing</a>
            <a href="<?= SITE_URL ?>/essentials.php" class="btn-lux">Explore Essentials</a>
            <a href="<?= SITE_URL ?>/new-arrivals.php" class="btn-lux btn-lux-ghost">View Products</a>
        </div>
    </div>
    <div class="hero-scroll-cue"><span>Scroll</span><div class="line"></div></div>
</section>

<div class="marquee-strip">
    <div class="marquee-track">
        <span>Complimentary Shipping Over ₱15,000</span><span>Est. 1974</span><span>Seven-Star Craftsmanship</span><span>Cash on Delivery · GCash · Maya</span>
        <span>Complimentary Shipping Over ₱15,000</span><span>Est. 2026</span><span>Seven-Star Craftsmanship</span><span>Cash on Delivery · GCash · Maya</span>
    </div>
</div>

<!-- STORE SELECTION -->
<section class="section" style="padding-bottom:0;">
    <div class="container-lux section-head centered reveal">
        <span class="eyebrow">Two Houses, One Standard</span>
        <h2 class="section-title">Choose Your World</h2>
    </div>
</section>
<div class="store-grid">
    <a href="<?= SITE_URL ?>/clothing.php" class="store-card reveal reveal-left">
        <img src="https://images.unsplash.com/photo-1490114538077-0a7f8cb49891?q=80&w=1400&auto=format&fit=crop" alt="Clothing Collection">
        <div class="store-card-content">
            <span class="eyebrow">01 — Ready to Wear</span>
            <h3>Clothing Collection</h3>
            <span class="store-card-link">Discover the Range &rarr;</span>
        </div>
    </a>
    <a href="<?= SITE_URL ?>/essentials.php" class="store-card reveal reveal-right">
        <img src="https://images.unsplash.com/photo-1523170335258-f5ed11844a49?q=80&w=1400&auto=format&fit=crop" alt="Essentials Collection">
        <div class="store-card-content">
            <span class="eyebrow">02 — Fine Essentials</span>
            <h3>Essentials Collection</h3>
            <span class="store-card-link">Discover the Range &rarr;</span>
        </div>
    </a>
</div>

<?php
/**
 * Renders one horizontal product rail (slider) with working prev/next
 * controls, edge fades, and a full product card: image, badges, wishlist,
 * category, name, short description and price (with optional strike-through
 * compare-at price). The actual scrolling/nav behaviour is wired up once,
 * at the bottom of this file, for every [data-scroller] on the page.
 */
function render_product_rail(string $title, string $eyebrow, array $products, string $view_all_url) {
    ob_start(); ?>
    <div class="container-lux">
        <div class="section-head-row reveal" style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:3rem;">
            <div><span class="eyebrow"><?= e($eyebrow) ?></span><h2 class="section-title"><?= e($title) ?></h2></div>
            <a href="<?= e($view_all_url) ?>" class="btn-lux btn-lux-sm">View All</a>
        </div>
        <div data-scroller>
            <div class="scroller-nav">
                <button type="button" data-scroll-prev aria-label="Previous products" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <button type="button" data-scroll-next aria-label="Next products">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 18l6-6-6-6"/></svg>
                </button>
            </div>
            <div class="product-scroller">
                <?php if ($products): foreach ($products as $i => $p):
                    $desc = $p['short_description'] ?? $p['description'] ?? $p['summary'] ?? '';
                    $desc = trim(strip_tags((string) $desc));
                    if ($desc !== '' && mb_strlen($desc) > 92) { $desc = mb_substr($desc, 0, 90) . '…'; }
                    $has_discount = !empty($p['compare_at_price']) && (float) $p['compare_at_price'] > (float) $p['price'];
                    $product_url = SITE_URL . '/product.php?type=' . urlencode($p['ptype']) . '&slug=' . urlencode($p['slug']);
                ?>
                <article class="product-card reveal" style="transition-delay:<?= ($i % 4) * 0.09 ?>s">
                    <div class="product-card-media">
                        <a href="<?= e($product_url) ?>" class="product-card-media-link" aria-label="<?= e($p['name']) ?>">
                            <div class="product-card-badges">
                                <?php if (!empty($p['is_limited_edition'])): ?><span class="badge-limited">Limited Edition</span><?php endif; ?>
                                <?php if ($has_discount): ?><span class="badge-sale">-<?= round((1 - ((float) $p['price'] / (float) $p['compare_at_price'])) * 100) ?>%</span><?php endif; ?>
                            </div>
                            <img src="<?= e($p['primary_image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
                            <span class="quick-add">Quick View</span>
                        </a>
                        <button type="button" class="wish-btn" data-product-id="<?= (int) $p['product_id'] ?>" data-product-type="<?= e($p['ptype']) ?>" aria-label="Add to wishlist">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.6z"/></svg>
                        </button>
                    </div>
                    <div class="product-card-body">
                        <?php if (!empty($p['cat_name'])): ?><p class="product-card-cat"><?= e($p['cat_name']) ?></p><?php endif; ?>
                        <h3 class="product-card-title"><a href="<?= e($product_url) ?>"><?= e($p['name']) ?></a></h3>
                        <?php if ($desc !== ''): ?><p class="product-card-desc"><?= e($desc) ?></p><?php endif; ?>
                        <p class="product-card-price">
                            <?php if ($has_discount): ?><span class="was"><?= CURRENCY_SYMBOL . number_format((float) $p['compare_at_price'], 2) ?></span><?php endif; ?>
                            <span class="now"><?= CURRENCY_SYMBOL . number_format((float) $p['price'], 2) ?></span>
                        </p>
                    </div>
                </article>
                <?php endforeach; else: ?>
                    <p style="color:var(--text-secondary); padding:2rem 0;">Pieces will appear here once added from the admin dashboard.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
}

echo '<section class="section">' . render_product_rail('Featured Collection', 'Curated Selection', $featured, SITE_URL . '/featured.php') . '</section>';
echo '<section class="section" style="background:var(--bg-secondary);">' . render_product_rail('New Arrivals', 'Just In · Limited Editions Included', $new_arrivals, SITE_URL . '/new-arrivals.php') . '</section>';
echo '<section class="section">' . render_product_rail('Best Sellers', 'Most Coveted', $best_sellers, SITE_URL . '/best-sellers.php') . '</section>';
?>

<!-- BRAND STORY -->
<section class="section" style="background:var(--bg-secondary);" id="story">
    <div class="container-lux">
        <div class="brand-story">
            <div class="brand-story-media reveal reveal-left">
                <img src="https://images.unsplash.com/photo-1441984904996-e0b6ba687e04?q=80&w=1200&auto=format&fit=crop" alt="The atelier of El Grande De La Torres">
            </div>
            <div class="brand-story-text reveal reveal-right">
                <span class="eyebrow">A House Founded on Refusal</span>
                <h2>We Refused to Follow the Season</h2>
                <p>El Grande De La Torres began not as a brand, but as a quiet rebellion — a refusal to build clothing that expires with the next trend cycle. Three tailors, one atelier, and a conviction that excellence cannot be rushed.</p>
                <p>Today, every piece that carries our name still passes through the same discipline: fabric rejected more often than accepted, stitching measured in hours rather than units, and a standard that answers to no calendar but its own. This is not fast fashion. This is the house that time forgot to hurry.</p>
                <a href="<?= SITE_URL ?>/about.php" class="btn-lux" style="margin-top:1rem;">Read Our Full Story</a>
                <div class="brand-story-stats">
                    <div><div class="stat-num">40+</div><div class="stat-label">Master Artisans</div></div>
                    <div><div class="stat-num">12</div><div class="stat-label">Global Ateliers</div></div>
                    <div><div class="stat-num">100%</div><div class="stat-label">Hand-Finished</div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="section">
    <div class="container-lux">
        <div class="section-head centered reveal">
            <span class="eyebrow">In Their Words</span>
            <h2 class="section-title">What Our Clients Say</h2>
            <?php if (is_logged_in()): ?>
                <button type="button" class="btn-lux btn-lux-sm" id="openTestiModal" style="margin-top:1.5rem;">
                    <?= $my_testimonial ? 'Edit My Testimonial' : 'Share Your Experience' ?>
                </button>
            <?php else: ?>
                <p style="margin-top:1.2rem; font-size:0.85rem;"><a href="<?= SITE_URL ?>/auth/login.php" style="color:var(--gold);">Sign in</a> to share your own testimonial.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="testimonial-grid">
        <?php if (empty($testimonials)): ?>
            <div class="testimonial-card reveal" style="grid-column:1/-1; text-align:center;">
                <p style="color:var(--text-secondary);">Be the first client to share your experience with the house.</p>
            </div>
        <?php else: foreach ($testimonials as $i => $t):
            $is_mine = is_logged_in() && (int) $t['user_id'] === current_user_id(); ?>
        <div class="testimonial-card reveal reveal-delay-<?= min($i % 3 + 1, 3) ?>">
            <?php if ($is_mine): ?><button type="button" class="testimonial-edit-btn" data-edit-testimonial="<?= $t['review_id'] ?>" data-rating="<?= $t['rating'] ?>" data-text="<?= e($t['review_text']) ?>" data-location="<?= e($t['location']) ?>">Edit</button><?php endif; ?>
            <div class="testimonial-stars"><?= str_repeat('★', (int) $t['rating']) . str_repeat('☆', 5 - (int) $t['rating']) ?></div>
            <p class="testimonial-quote">"<?= e($t['review_text']) ?>"</p>
            <div class="testimonial-author"><?= e($t['first_name']) ?> <?= e(substr($t['last_name'], 0, 1)) ?>.</div>
            <div class="testimonial-role"><?= e($t['location'] ?: 'Philippines') ?></div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</section>

<!-- TESTIMONIAL MODAL -->
<?php if (is_logged_in()): ?>
<div class="testi-modal-overlay" id="testiModalOverlay">
    <div class="testi-modal">
        <h3><?= $my_testimonial ? 'Edit Your Testimonial' : 'Share Your Experience' ?></h3>
        <form id="testiForm" class="form-lux">
            <?= csrf_field() ?>
            <input type="hidden" name="review_id" id="testiReviewId" value="<?= $my_testimonial['review_id'] ?? '' ?>">
            <div class="field-group">
                <label>Rating</label>
                <div class="testi-star-picker" id="testiStarPicker">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                    <button type="button" data-star="<?= $s ?>">★</button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="testiRatingInput" value="<?= $my_testimonial['rating'] ?? 5 ?>">
            </div>
            <div class="field-group">
                <label>Your City / Location</label>
                <input type="text" name="location" id="testiLocation" value="<?= e($my_testimonial['location'] ?? '') ?>" placeholder="e.g. Makati City" required>
            </div>
            <div class="field-group">
                <label>Your Testimonial</label>
                <textarea name="review_text" id="testiText" rows="4" required maxlength="280"><?= e($my_testimonial['review_text'] ?? '') ?></textarea>
            </div>
            <div style="display:flex; gap:1rem;">
                <button type="submit" class="btn-lux btn-lux-filled">Save Testimonial</button>
                <button type="button" class="btn-lux btn-lux-ghost" id="closeTestiModal">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- NEWSLETTER -->
<section class="newsletter-lux">
    <div class="container-lux">
        <span class="eyebrow" style="justify-content:center;">Stay Informed</span>
        <h2 class="section-title" style="font-size:clamp(1.8rem,3vw,2.6rem);">Join the House List</h2>
        <p class="section-sub" style="margin:0 auto;">Be first to receive access to new collections, private events, and atelier stories.</p>
        <form class="newsletter-form">
            <input type="email" name="email" placeholder="Your email address" required>
            <button type="submit">Subscribe</button>
        </form>
    </div>
</section>

<!-- INSTAGRAM GALLERY -->
<section class="section" style="padding-bottom:0;">
    <div class="container-lux section-head reveal">
        <span class="eyebrow">@elgrandedelatorres</span>
        <h2 class="section-title">Follow the House</h2>
    </div>
</section>
<div class="insta-slider-wrap">
    <div class="insta-grid">
        <?php
        $insta_imgs = [
            'https://images.unsplash.com/photo-1483985988355-763728e1935b?q=80&w=500&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1490578474895-699cd4e2cf59?q=80&w=500&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?q=80&w=500&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?q=80&w=500&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?q=80&w=500&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?q=80&w=500&auto=format&fit=crop',
        ];
        foreach ($insta_imgs as $img): ?>
        <a href="#" class="insta-item"><img src="<?= e($img) ?>" alt="Instagram post" loading="lazy"></a>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.getElementById('openTestiModal')?.addEventListener('click', () => document.getElementById('testiModalOverlay').classList.add('open'));
document.getElementById('closeTestiModal')?.addEventListener('click', () => document.getElementById('testiModalOverlay').classList.remove('open'));

document.querySelectorAll('[data-edit-testimonial]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('testiReviewId').value = btn.dataset.editTestimonial;
        document.getElementById('testiLocation').value = btn.dataset.location;
        document.getElementById('testiText').value = btn.dataset.text;
        document.getElementById('testiRatingInput').value = btn.dataset.rating;
        setStarDisplay(parseInt(btn.dataset.rating));
        document.getElementById('testiModalOverlay').classList.add('open');
    });
});

function setStarDisplay(rating) {
    document.querySelectorAll('#testiStarPicker button').forEach(b => {
        b.classList.toggle('active', parseInt(b.dataset.star) <= rating);
    });
}
document.querySelectorAll('#testiStarPicker button').forEach(btn => {
    btn.addEventListener('click', () => {
        const val = parseInt(btn.dataset.star);
        document.getElementById('testiRatingInput').value = val;
        setStarDisplay(val);
    });
});
setStarDisplay(parseInt(document.getElementById('testiRatingInput')?.value || 5));

document.getElementById('testiForm')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const data = new FormData(e.target);
    fetch(window.SITE_URL + '/api/testimonial_save.php', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) { showToast('Testimonial saved.'); setTimeout(() => window.location.reload(), 900); }
            else { showToast(res.message || 'Could not save testimonial.'); }
        });
});

/* ---- Product rail sliders: working prev/next + edge fades ---- */
(function () {
    document.querySelectorAll('[data-scroller]').forEach((wrap) => {
        const track = wrap.querySelector('.product-scroller');
        const prevBtn = wrap.querySelector('[data-scroll-prev]');
        const nextBtn = wrap.querySelector('[data-scroll-next]');
        if (!track) return;

        function step() {
            const card = track.querySelector('.product-card');
            const gap = parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap || 24);
            const width = card ? card.getBoundingClientRect().width : 300;
            return (width + gap) * 2;
        }

        function refresh() {
            const max = track.scrollWidth - track.clientWidth - 1;
            const atStart = track.scrollLeft <= 1;
            const atEnd = max <= 0 || track.scrollLeft >= max;
            wrap.classList.toggle('at-start', atStart);
            wrap.classList.toggle('at-end', atEnd);
            if (prevBtn) prevBtn.disabled = atStart;
            if (nextBtn) nextBtn.disabled = atEnd;
        }

        prevBtn?.addEventListener('click', () => track.scrollBy({ left: -step(), behavior: 'smooth' }));
        nextBtn?.addEventListener('click', () => track.scrollBy({ left: step(), behavior: 'smooth' }));

        let ticking = false;
        track.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => { refresh(); ticking = false; });
                ticking = true;
            }
        });
        window.addEventListener('resize', refresh);
        refresh();
    });

    /* Fallback reveal-on-scroll, in case the global site script hasn't
       already wired one up for elements carrying the .reveal class. */
    const revealEls = document.querySelectorAll('.reveal:not(.is-visible)');
    if (revealEls.length && 'IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });
        revealEls.forEach((el) => io.observe(el));
    }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>