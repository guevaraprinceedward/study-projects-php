<?php
// best-sellers.php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/product-functions.php';

$page_title = "Best Sellers — El Grande De La Torres";
$extra_css = SITE_URL . '/assets/css/shop.css';
require_once __DIR__ . '/includes/header.php';
$products = fetch_flagged_products('is_best_seller', 100);
?>
<div class="shop-header">
    <span class="eyebrow" style="justify-content:center;">Most Coveted</span>
    <h1 class="section-title">Best Sellers</h1>
</div>
<div class="container-lux" style="padding:3.6rem 0 6rem;">
    <div class="product-grid">
        <?php foreach ($products as $p): ?>
        <article class="product-card reveal">
            <a href="<?= SITE_URL ?>/product.php?type=<?= e($p['ptype']) ?>&slug=<?= e($p['slug']) ?>" class="product-card-media">
                <div class="product-card-badges"><?php if ($p['is_limited_edition']): ?><span class="badge-limited">Limited</span><?php endif; ?></div>
                <img src="<?= e($p['primary_image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
                <button class="wish-btn" data-product-id="<?= $p['product_id'] ?>" data-product-type="<?= e($p['ptype']) ?>" aria-label="Add to wishlist"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.6z"/></svg></button>
                <button class="quick-add">Quick View</button>
            </a>
            <p class="product-card-cat"><?= e($p['cat_name']) ?></p>
            <h3 class="product-card-title"><?= e($p['name']) ?></h3>
            <p class="product-card-price"><?= CURRENCY_SYMBOL . number_format($p['price'], 2) ?></p>
        </article>
        <?php endforeach; ?>
        <?php if (empty($products)): ?><div class="empty-state"><p>No best sellers yet.</p></div><?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>