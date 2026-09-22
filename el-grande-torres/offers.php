<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/product-functions.php';

/**
 * Bundles are defined here as curated combinations of existing product
 * slugs (2–7 items each). Each bundle gets its own discount rate off
 * the combined retail price. Add new bundles by adding an entry below —
 * no schema change needed since it references existing products.
 */
$bundle_defs = [
    [
        'slug' => 'the-essential-edit',
        'name' => 'The Essential Edit',
        'tagline' => 'A quiet, everyday foundation.',
        'discount' => 0.12,
        'items' => [
            ['type' => 'clothing', 'slug' => 'signature-crest-tee'],
            ['type' => 'essentials', 'slug' => 'everyday-leather-card-holder'],
            ['type' => 'essentials', 'slug' => 'minimalist-money-clip'],
        ],
    ],
    [
        'slug' => 'the-gentlemans-atelier',
        'name' => "The Gentleman's Atelier",
        'tagline' => 'Tailoring, finished with intent.',
        'discount' => 0.15,
        'items' => [
            ['type' => 'clothing', 'slug' => 'ivory-oxford-shirt'],
            ['type' => 'clothing', 'slug' => 'tailored-house-trousers'],
            ['type' => 'essentials', 'slug' => 'torres-signature-loafer'] ,
            ['type' => 'essentials', 'slug' => 'silk-pocket-square'],
            ['type' => 'essentials', 'slug' => 'brushed-steel-cufflinks'],
        ],
    ],
    [
        'slug' => 'weekend-away',
        'name' => 'Weekend Away',
        'tagline' => 'Everything for a well-dressed departure.',
        'discount' => 0.18,
        'items' => [
            ['type' => 'clothing', 'slug' => 'cloud-zip-hoodie'],
            ['type' => 'essentials', 'slug' => 'weekender-leather-duffel'],
            ['type' => 'essentials', 'slug' => 'leather-toiletry-case'],
            ['type' => 'essentials', 'slug' => 'passport-holder-set'],
        ],
    ],
    [
        'slug' => 'evening-in-noir',
        'name' => 'Evening in Noir',
        'tagline' => 'Column silhouettes, finished in gold.',
        'discount' => 0.14,
        'items' => [
            ['type' => 'clothing', 'slug' => 'torres-column-dress'],
            ['type' => 'essentials', 'slug' => 'house-crest-pendant'],
            ['type' => 'essentials', 'slug' => 'pearl-drop-earrings'],
        ],
    ],
    [
        'slug' => 'the-house-signature',
        'name' => 'The House Signature',
        'tagline' => 'The full statement — outerwear to fragrance.',
        'discount' => 0.20,
        'items' => [
            ['type' => 'clothing', 'slug' => 'atelier-bomber-jacket'],
            ['type' => 'clothing', 'slug' => 'tailored-house-trousers'],
            ['type' => 'essentials', 'slug' => 'maison-oud-eau-de-parfum'],
            ['type' => 'essentials', 'slug' => 'torres-automatic-watch'],
            ['type' => 'essentials', 'slug' => 'wool-felt-fedora'],
            ['type' => 'essentials', 'slug' => 'everyday-leather-card-holder'],
        ],
    ],
    [
        'slug' => 'grooming-and-scent',
        'name' => 'Grooming &amp; Scent',
        'tagline' => 'The quiet rituals of the everyday.',
        'discount' => 0.10,
        'items' => [
            ['type' => 'essentials', 'slug' => 'atelier-grooming-kit'],
            ['type' => 'essentials', 'slug' => 'cedarwood-beard-oil'],
            ['type' => 'essentials', 'slug' => 'vetiver-homme-cologne'],
        ],
    ],
];

// Resolve each bundle's live product data + pricing from the DB
$bundles = [];
foreach ($bundle_defs as $def) {
    $resolved = [];
    $retail_total = 0.0;
    foreach ($def['items'] as $ref) {
        $p = get_product_by_slug($ref['type'], $ref['slug']);
        if ($p) {
            $resolved[] = array_merge($p, ['ptype' => $ref['type']]);
            $retail_total += (float)$p['price'];
        }
    }
    if (count($resolved) < 2) continue; // skip if referenced products are missing
    $bundle_price = round($retail_total * (1 - $def['discount']), 2);
    $bundles[] = [
        'slug' => $def['slug'],
        'name' => $def['name'],
        'tagline' => $def['tagline'],
        'discount' => $def['discount'],
        'items' => $resolved,
        'retail_total' => $retail_total,
        'bundle_price' => $bundle_price,
        'savings' => $retail_total - $bundle_price,
    ];
}

$page_title = "Offers — El Grande De La Torres";
$meta_description = "Curated bundles from El Grande De La Torres — considered pairings at a house price.";
$extra_css = SITE_URL . '/assets/css/shop.css';
require_once __DIR__ . '/includes/header.php';
?>
<style>
#offers-loading {
    position: fixed; inset: 0; background: var(--bg-primary); z-index: 9998;
    display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 1.6rem;
    transition: opacity 0.8s var(--ease-lux), visibility 0.8s var(--ease-lux);
}
#offers-loading.hidden { opacity: 0; visibility: hidden; pointer-events: none; }
#offers-loading .coin-loader { width: 64px; height: 64px; perspective: 600px; }
#offers-loading .coin-face {
    width: 64px; height: 64px; border-radius: 50%;
    border: 1px solid var(--gold); display: flex; align-items: center; justify-content: center;
    font-family: var(--font-display); font-style: italic; font-size: 1.4rem; color: var(--gold);
    animation: coinFlip 1.6s linear infinite;
}
@keyframes coinFlip { from { transform: rotateY(0deg); } to { transform: rotateY(360deg); } }
#offers-loading .load-label { font-size: 0.7rem; letter-spacing: 0.28em; text-transform: uppercase; color: var(--text-secondary); }

.offers-hero { padding: calc(var(--nav-height) + 4rem) 0 4rem; text-align: center; }
.offers-hero h1 { font-size: clamp(2.4rem, 5vw, 3.8rem); margin: 1rem 0 1.2rem; }
.offers-hero h1 em { font-style: italic; color: var(--gold); }
.offers-hero p { max-width: 600px; margin: 0 auto; color: var(--text-secondary); line-height: 1.85; }

.bundle-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 2.4rem; padding: 0 0 6rem; }
@media (max-width: 900px) { .bundle-grid { grid-template-columns: 1fr; } }

.bundle-card {
    background: var(--bg-card); border: 1px solid var(--border-color);
    padding: 2.4rem; perspective: 1200px; position: relative; overflow: hidden;
    transition: border-color 0.4s var(--ease-lux);
}
.bundle-card:hover { border-color: rgba(200,169,106,0.4); }
.bundle-card-inner { transform-style: preserve-3d; transition: transform 0.3s cubic-bezier(0.2,0.7,0.3,1); }

.bundle-head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.8rem; transform: translateZ(20px); }
.bundle-head h3 { font-size: 1.5rem; margin-bottom: 0.4rem; }
.bundle-head p { font-size: 0.85rem; color: var(--text-secondary); font-style: italic; font-family: var(--font-display); }
.bundle-save-badge { background: var(--gold); color: var(--bg-primary); font-size: 0.62rem; letter-spacing: 0.1em; text-transform: uppercase; padding: 0.4rem 0.8rem; white-space: nowrap; }

.bundle-items-stack { display: flex; margin-bottom: 1.8rem; transform: translateZ(14px); }
.bundle-item-thumb {
    width: 70px; height: 88px; border: 2px solid var(--bg-primary); border-radius: 4px; overflow: hidden;
    margin-right: -20px; transition: transform 0.35s var(--ease-lux), z-index 0s;
    box-shadow: 0 8px 20px rgba(0,0,0,0.4);
}
.bundle-item-thumb img { width: 100%; height: 100%; object-fit: cover; }
.bundle-card:hover .bundle-item-thumb { transform: translateY(-6px); }
.bundle-card:hover .bundle-item-thumb:nth-child(1) { transition-delay: 0s; }
.bundle-card:hover .bundle-item-thumb:nth-child(2) { transition-delay: 0.05s; }
.bundle-card:hover .bundle-item-thumb:nth-child(3) { transition-delay: 0.1s; }
.bundle-card:hover .bundle-item-thumb:nth-child(4) { transition-delay: 0.15s; }
.bundle-card:hover .bundle-item-thumb:nth-child(5) { transition-delay: 0.2s; }
.bundle-card:hover .bundle-item-thumb:nth-child(6) { transition-delay: 0.25s; }
.bundle-card:hover .bundle-item-thumb:nth-child(7) { transition-delay: 0.3s; }

.bundle-item-list { list-style: none; margin-bottom: 1.8rem; transform: translateZ(10px); }
.bundle-item-list li { font-size: 0.82rem; color: var(--text-secondary); padding: 0.4rem 0; border-bottom: 1px dotted var(--border-color); display: flex; justify-content: space-between; }
.bundle-item-list li:last-child { border-bottom: none; }
.bundle-item-list li span:last-child { color: var(--text-primary); }

.bundle-pricing { display: flex; align-items: baseline; gap: 1rem; margin-bottom: 1.8rem; transform: translateZ(20px); }
.bundle-pricing .was { font-size: 1rem; color: var(--text-secondary); text-decoration: line-through; }
.bundle-pricing .now { font-family: var(--font-display); font-size: 1.9rem; color: var(--gold); }

.bundle-card .btn-lux { transform: translateZ(24px); width: 100%; }
</style>

<div id="offers-loading">
    <div class="coin-loader"><div class="coin-face">EG</div></div>
    <span class="load-label">Curating Offers</span>
</div>

<section class="offers-hero">
    <span class="eyebrow" style="justify-content:center;">Considered Pairings</span>
    <h1>House <em>Bundles.</em></h1>
    <p>Each bundle is curated, not assembled — pieces chosen to be worn together, offered at a price reserved only for those who take the whole story.</p>
</section>

<div class="container-lux bundle-grid">
    <?php foreach ($bundles as $i => $b): ?>
    <article class="bundle-card reveal reveal-delay-<?= min($i % 3 + 1, 3) ?>" data-bundle-slug="<?= e($b['slug']) ?>">
        <div class="bundle-card-inner">
            <div class="bundle-head">
                <div>
                    <h3><?= $b['name'] ?></h3>
                    <p><?= $b['tagline'] ?></p>
                </div>
                <span class="bundle-save-badge">Save <?= round($b['discount'] * 100) ?>%</span>
            </div>

            <div class="bundle-items-stack">
                <?php foreach (array_slice($b['items'], 0, 7) as $item): ?>
                <div class="bundle-item-thumb"><img src="<?= e($item['primary_image']) ?>" alt="<?= e($item['name']) ?>" loading="lazy"></div>
                <?php endforeach; ?>
            </div>

            <ul class="bundle-item-list">
                <?php foreach ($b['items'] as $item): ?>
                <li><span><?= e($item['name']) ?></span><span><?= CURRENCY_SYMBOL . number_format($item['price'], 2) ?></span></li>
                <?php endforeach; ?>
            </ul>

            <div class="bundle-pricing">
                <span class="was"><?= CURRENCY_SYMBOL . number_format($b['retail_total'], 2) ?></span>
                <span class="now"><?= CURRENCY_SYMBOL . number_format($b['bundle_price'], 2) ?></span>
            </div>

            <button type="button" class="btn-lux btn-lux-filled add-bundle-btn" data-bundle-slug="<?= e($b['slug']) ?>">
                Add Bundle to Cart — Save <?= CURRENCY_SYMBOL . number_format($b['savings'], 0) ?>
            </button>
        </div>
    </article>
    <?php endforeach; ?>

    <?php if (empty($bundles)): ?>
    <div class="empty-state"><p>No bundles are configured yet.</p></div>
    <?php endif; ?>
</div>

<script>
// 3D tilt on mouse move, per bundle card
document.querySelectorAll('.bundle-card').forEach(card => {
    const inner = card.querySelector('.bundle-card-inner');
    card.addEventListener('mousemove', (e) => {
        const r = card.getBoundingClientRect();
        const x = (e.clientX - r.left) / r.width - 0.5;
        const y = (e.clientY - r.top) / r.height - 0.5;
        inner.style.transform = `rotateY(${x * 6}deg) rotateX(${-y * 6}deg)`;
    });
    card.addEventListener('mouseleave', () => { inner.style.transform = 'rotateY(0) rotateX(0)'; });
});

// Loading screen
window.addEventListener('load', () => {
    setTimeout(() => document.getElementById('offers-loading').classList.add('hidden'), 500);
});
setTimeout(() => document.getElementById('offers-loading')?.classList.add('hidden'), 2500);

// Add whole bundle to cart
document.querySelectorAll('.add-bundle-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        if (!window.IS_LOGGED_IN) { window.location.href = window.SITE_URL + '/auth/login.php'; return; }
        const data = new FormData();
        data.append('bundle_slug', btn.dataset.bundleSlug);
        data.append('csrf_token', window.CSRF_TOKEN);
        btn.disabled = true;
        btn.textContent = 'Adding…';
        fetch(window.SITE_URL + '/api/bundle_add.php', { method: 'POST', body: data })
            .then(r => r.json())
            .then(res => {
                if (res.success) { showToast('Bundle added to your bag.'); window.location.href = window.SITE_URL + '/cart.php'; }
                else { showToast(res.message || 'Could not add bundle.'); btn.disabled = false; btn.textContent = 'Add Bundle to Cart'; }
            })
            .catch(() => { showToast('Something went wrong.'); btn.disabled = false; });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>