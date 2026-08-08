<?php
/**
 * Shared listing engine. clothing.php and essentials.php set $store_type
 * and $page_categories_note before including this.
 */
require_once __DIR__ . '/product-functions.php';

$store_type = $store_type ?? 'clothing';
$store_label = $store_type === 'essentials' ? 'Essentials' : 'Clothing';

$filters = [
    'category'     => isset($_GET['category']) ? (array)$_GET['category'] : [],
    'gender'       => in_array($_GET['gender'] ?? '', ['men','women','unisex'], true) ? $_GET['gender'] : null,
    'min_price'    => $_GET['min_price'] ?? null,
    'max_price'    => $_GET['max_price'] ?? null,
    'availability' => $_GET['availability'] ?? null,
    'limited_only' => !empty($_GET['limited_only']),
    'search'       => $_GET['q'] ?? null,
];
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));

$results = query_products($store_type, $filters, $sort, $page, 12);
$categories = get_categories($store_type);

$page_title = "$store_label — El Grande De La Torres";
$extra_css = SITE_URL . '/assets/css/shop.css';
require_once __DIR__ . '/header.php';
?>

<div class="shop-header">
    <span class="eyebrow" style="justify-content:center;"><?= $store_type === 'essentials' ? 'Fine Essentials' : 'Ready to Wear' ?></span>
    <h1 class="section-title"><?= e($store_label) ?> Collection</h1>
    <p class="badge-count"><?= $results['total'] ?> pieces available</p>

    <div class="gender-tabs">
        <a href="?" class="<?= empty($filters['gender']) ? 'active' : '' ?>">All</a>
        <a href="?gender=men" class="<?= $filters['gender'] === 'men' ? 'active' : '' ?>">Men</a>
        <a href="?gender=women" class="<?= $filters['gender'] === 'women' ? 'active' : '' ?>">Women</a>
        <a href="?gender=unisex" class="<?= $filters['gender'] === 'unisex' ? 'active' : '' ?>">Unisex</a>
    </div>
</div>

<div class="container-lux shop-layout">
    <aside class="filter-panel">
        <form method="GET" id="filterForm">
            <?php if (!empty($filters['gender'])): ?><input type="hidden" name="gender" value="<?= e($filters['gender']) ?>"><?php endif; ?>
            <?php if (!empty($filters['search'])): ?><input type="hidden" name="q" value="<?= e($filters['search']) ?>"><?php endif; ?>

            <div class="filter-group">
                <h4>Category</h4>
                <?php foreach ($categories as $cat): ?>
                <label>
                    <input type="checkbox" name="category[]" value="<?= e($cat['slug']) ?>"
                        <?= in_array($cat['slug'], $filters['category']) ? 'checked' : '' ?>
                        onchange="document.getElementById('filterForm').submit()">
                    <?= e($cat['name']) ?>
                </label>
                <?php endforeach; ?>
            </div>

            <div class="filter-group">
                <h4>Price Range</h4>
                <input type="number" name="min_price" placeholder="Min ₱" value="<?= e($filters['min_price'] ?? '') ?>" style="margin-bottom:0.6rem; background:transparent; border:1px solid var(--border-color); color:var(--text-primary); padding:0.6rem; width:100%; font-size:0.82rem;">
                <input type="number" name="max_price" placeholder="Max ₱" value="<?= e($filters['max_price'] ?? '') ?>" style="background:transparent; border:1px solid var(--border-color); color:var(--text-primary); padding:0.6rem; width:100%; font-size:0.82rem;">
            </div>

            <div class="filter-group">
                <h4>Availability</h4>
                <label><input type="checkbox" name="availability" value="in_stock" <?= ($filters['availability'] ?? '') === 'in_stock' ? 'checked' : '' ?> onchange="document.getElementById('filterForm').submit()"> In Stock Only</label>
                <label><input type="checkbox" name="limited_only" value="1" <?= $filters['limited_only'] ? 'checked' : '' ?> onchange="document.getElementById('filterForm').submit()"> Limited Editions Only</label>
            </div>

            <button type="submit" class="btn-lux btn-lux-sm btn-lux-block">Apply Filters</button>
            <a href="?" class="btn-lux btn-lux-ghost btn-lux-sm btn-lux-block" style="margin-top:0.8rem;">Clear All</a>
        </form>
    </aside>

    <div>
        <div class="shop-toolbar">
            <form class="search-box" method="GET">
                <?php if (!empty($filters['gender'])): ?><input type="hidden" name="gender" value="<?= e($filters['gender']) ?>"><?php endif; ?>
                <input type="text" name="q" placeholder="Search <?= strtolower(e($store_label)) ?>..." value="<?= e($filters['search'] ?? '') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </form>
            <select class="sort-select" onchange="window.location.href=updateQueryParam('sort', this.value)">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                <option value="best_selling" <?= $sort === 'best_selling' ? 'selected' : '' ?>>Best Selling</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
            </select>
        </div>

        <?php if (empty($results['items'])): ?>
            <div class="empty-state">
                <p style="font-family:var(--font-display); font-size:1.4rem; margin-bottom:0.8rem;">No pieces match your selection.</p>
                <p>Try adjusting your filters, or explore the full collection.</p>
                <a href="?" class="btn-lux" style="margin-top:1.5rem;">Reset Filters</a>
            </div>
        <?php else: ?>
        <div class="product-grid">
            <?php foreach ($results['items'] as $p): ?>
            <article class="product-card reveal">
                <a href="<?= SITE_URL ?>/product.php?type=<?= $store_type ?>&slug=<?= e($p['slug']) ?>" class="product-card-media">
                    <div class="product-card-badges">
                        <?php if ($p['is_new_arrival']): ?><span class="badge-lux">New</span><?php endif; ?>
                        <?php if ($p['is_limited_edition']): ?><span class="badge-limited">Limited</span><?php endif; ?>
                    </div>
                    <img src="<?= e($p['primary_image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
                    <button class="wish-btn" data-product-id="<?= $p['product_id'] ?>" data-product-type="<?= $store_type ?>" aria-label="Add to wishlist"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.6z"/></svg></button>
                    <button class="quick-add">Quick View</button>
                </a>
                <p class="product-card-cat"><?= e($p['category_name']) ?></p>
                <h3 class="product-card-title"><?= e($p['name']) ?></h3>
                <p class="product-card-price">
                    <?php if ($p['compare_at_price']): ?><span class="was"><?= CURRENCY_SYMBOL . number_format($p['compare_at_price'], 2) ?></span><?php endif; ?>
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
</div>

<script>
function updateQueryParam(key, value) {
    const url = new URL(window.location);
    url.searchParams.set(key, value);
    return url.toString();
}
</script>
<?php require_once __DIR__ . '/footer.php'; ?>