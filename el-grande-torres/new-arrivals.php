<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/product-functions.php';

// view=new (default) shows is_new_arrival pieces; view=limited shows the
// Limited Edition rail across both clothing and essentials.
$view = ($_GET['view'] ?? 'new') === 'limited' ? 'limited' : 'new';
$gender = in_array($_GET['gender'] ?? '', ['men', 'women', 'unisex'], true) ? $_GET['gender'] : null;
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 6;

$flags = $view === 'limited' ? ['is_limited_edition'] : ['is_new_arrival'];
$filters = ['gender' => $gender];
if ($view === 'limited') { $filters['limited_only'] = true; }

$results = query_combined_products($flags, $filters, $sort, $page, $per_page);

$page_title = ($view === 'limited' ? "Limited Edition" : "New Arrivals") . " — El Grande De La Torres";
$extra_css = SITE_URL . '/assets/css/shop.css';
require_once __DIR__ . '/includes/header.php';

function na_tab_qs(array $overrides): string {
    $base = $_GET;
    unset($base['page']);
    foreach ($overrides as $k => $v) {
        if ($v === null) { unset($base[$k]); } else { $base[$k] = $v; }
    }
    $qs = http_build_query($base);
    return $qs ? '?' . $qs : '?';
}
?>

<div class="shop-header">
    <span class="eyebrow" style="justify-content:center;"><?= $view === 'limited' ? 'Individually Numbered' : 'Just In' ?></span>
    <h1 class="section-title"><?= $view === 'limited' ? 'Limited Edition' : 'New Arrivals' ?></h1>
    <p class="badge-count"><?= $results['total'] ?> pieces available</p>

    <div class="gender-tabs">
        <a href="<?= na_tab_qs(['view' => 'new', 'gender' => null]) ?>" class="<?= $view === 'new' && !$gender ? 'active' : '' ?>">All</a>
        <a href="<?= na_tab_qs(['view' => 'new', 'gender' => 'men']) ?>" class="<?= $view === 'new' && $gender === 'men' ? 'active' : '' ?>">Men</a>
        <a href="<?= na_tab_qs(['view' => 'new', 'gender' => 'women']) ?>" class="<?= $view === 'new' && $gender === 'women' ? 'active' : '' ?>">Women</a>
        <a href="<?= na_tab_qs(['view' => 'new', 'gender' => 'unisex']) ?>" class="<?= $view === 'new' && $gender === 'unisex' ? 'active' : '' ?>">Unisex</a>
        <a href="<?= na_tab_qs(['view' => 'limited']) ?>" class="<?= $view === 'limited' ? 'active' : '' ?>" style="border-color:var(--gold); color:var(--gold);">Limited Edition</a>
    </div>
</div>

<div class="container-lux" style="padding:3.6rem 0 6rem;">
    <div class="shop-toolbar" style="justify-content:flex-end;">
        <select class="sort-select" onchange="window.location.href=updateQueryParam('sort', this.value)">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
            <option value="best_selling" <?= $sort === 'best_selling' ? 'selected' : '' ?>>Best Selling</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
        </select>
    </div>

    <?php if (empty($results['items'])): ?>
        <div class="empty-state">
            <p style="font-family:var(--font-display); font-size:1.4rem; margin-bottom:0.8rem;">
                <?= $view === 'limited' ? 'No limited edition pieces match your filter.' : 'No new arrivals match your filter.' ?>
            </p>
            <p>Try a different tab, or explore the full collection.</p>
            <a href="<?= SITE_URL ?>/new-arrivals.php" class="btn-lux" style="margin-top:1.5rem;">Reset</a>
        </div>
    <?php else: ?>
    <!-- Same .product-grid / .product-card markup used on clothing.php & essentials.php,
         so cards render at the same size — sizing itself lives in product-card.css. -->
    <div class="product-grid">
        <?php foreach ($results['items'] as $p):
            $has_discount = !empty($p['compare_at_price']) && (float)$p['compare_at_price'] > (float)$p['price'];
            $product_url = SITE_URL . '/product.php?type=' . urlencode($p['ptype']) . '&slug=' . urlencode($p['slug']);
        ?>
        <article class="product-card reveal">
            <a href="<?= e($product_url) ?>" class="product-card-media">
                <div class="product-card-badges">
                    <?php if (!empty($p['is_new_arrival'])): ?><span class="badge-lux">New</span><?php endif; ?>
                    <?php if (!empty($p['is_limited_edition'])): ?><span class="badge-limited"><?= e($p['limited_edition_note'] ?: 'Limited Edition') ?></span><?php endif; ?>
                    <?php if ($has_discount): ?><span class="badge-sale">-<?= round((1 - ((float)$p['price'] / (float)$p['compare_at_price'])) * 100) ?>%</span><?php endif; ?>
                </div>
                <img src="<?= e($p['primary_image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
                <button class="wish-btn" data-product-id="<?= (int)$p['product_id'] ?>" data-product-type="<?= e($p['ptype']) ?>" aria-label="Add to wishlist"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.6z"/></svg></button>
                <button class="quick-add">Quick View</button>
            </a>
            <p class="product-card-cat"><?= e($p['cat_name']) ?></p>
            <h3 class="product-card-title"><?= e($p['name']) ?><?= !empty($p['is_limited_edition']) ? ' (Limited Edition)' : '' ?></h3>
            <p class="product-card-price">
                <?php if ($has_discount): ?><span class="was"><?= CURRENCY_SYMBOL . number_format($p['compare_at_price'], 2) ?></span><?php endif; ?>
                <?= CURRENCY_SYMBOL . number_format($p['price'], 2) ?>
            </p>
        </article>
        <?php endforeach; ?>
    </div>

    <?php if ($results['pages'] > 1): ?>
    <div class="pagination-lux">
        <?php for ($i = 1; $i <= $results['pages']; $i++): ?>
            <?php if ($i === $page): ?><span class="active"><?= $i ?></span>
            <?php else: ?><a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a><?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function updateQueryParam(key, value) {
    const url = new URL(window.location);
    url.searchParams.set(key, value);
    return url.toString();
}
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.reveal').forEach(el => el.classList.add('is-visible'));
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>