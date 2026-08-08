<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/product-functions.php';

$page_title = "New Arrivals — El Grande De La Torres";
$extra_css = SITE_URL . '/assets/css/shop.css';
require_once __DIR__ . '/includes/header.php';

$new_clothing = fetch_flagged_products('is_new_arrival', 100);
$men_items = array_filter($new_clothing, fn($p) => $p['gender'] === 'men');
$women_items = array_filter($new_clothing, fn($p) => $p['gender'] === 'women');
?>
<div class="shop-header">
    <span class="eyebrow" style="justify-content:center;">Just In</span>
    <h1 class="section-title">New Arrivals</h1>
    <p class="badge-count">The newest pieces from the house — including limited editions, available in men's and women's fits.</p>
</div>

<div class="container-lux" style="padding:3.6rem 0 6rem;">
    <?php foreach (['Women' => $women_items, 'Men' => $men_items] as $label => $items): if (empty($items)) continue; ?>
    <div class="section-head-row reveal" style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:2.5rem;">
        <h2 class="section-title" style="font-size:1.8rem;"><?= e($label) ?>'s New Arrivals</h2>
    </div>
    <div class="product-grid" style="margin-bottom:5rem;">
        <?php foreach ($items as $p): ?>
        <article class="product-card reveal">
            <a href="<?= SITE_URL ?>/product.php?type=<?= e($p['ptype']) ?>&slug=<?= e($p['slug']) ?>" class="product-card-media">
                <div class="product-card-badges">
                    <span class="badge-lux">New</span>
                    <?php if ($p['is_limited_edition']): ?><span class="badge-limited"><?= e($p['limited_edition_note'] ?: 'Limited Edition') ?></span><?php endif; ?>
                </div>
                <img src="<?= e($p['primary_image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
                <button class="wish-btn" data-product-id="<?= $p['product_id'] ?>" data-product-type="<?= e($p['ptype']) ?>" aria-label="Add to wishlist"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.6z"/></svg></button>
                <button class="quick-add">Quick View</button>
            </a>
            <p class="product-card-cat"><?= e($p['cat_name']) ?></p>
            <h3 class="product-card-title"><?= e($p['name']) ?></h3>
            <p class="product-card-price"><?= CURRENCY_SYMBOL . number_format($p['price'], 2) ?></p>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <?php if (empty($new_clothing)): ?>
    <div class="empty-state"><p>No new arrivals yet — check back soon.</p></div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>