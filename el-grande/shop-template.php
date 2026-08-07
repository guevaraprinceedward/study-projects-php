<?php
/**
 * Shared listing template. Included by clothing.php and accessories.php,
 * which set $store_type before including this file.
 */
require_once __DIR__ . '/includes/product-functions.php';

$store_type = $store_type ?? 'clothing';
$store_label = $store_type === 'accessories' ? 'Accessories' : 'Clothing';

$filters = [
    'category'     => isset($_GET['category']) ? (array)$_GET['category'] : [],
    'min_price'    => $_GET['min_price'] ?? null,
    'max_price'    => $_GET['max_price'] ?? null,
    'color'        => $_GET['color'] ?? null,
    'size'         => $_GET['size'] ?? null,
    'availability' => $_GET['availability'] ?? null,
    'search'       => $_GET['q'] ?? null,
];
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));

$results = query_products($store_type, $filters, $sort, $page, 12);
$categories = get_categories($store_type);

$page_title = "$store_label — El Grande De La Torres";
$extra_css = SITE_URL . '/assets/css/shop.css';
require_once __DIR__ . '/includes/header.php';
?>

<div class="shop-header">
    <span class="eyebrow"><?= $store_type === 'accessories' ? 'Fine Objects' : 'Ready to Wear' ?></span>
    <h1 class="section-title"><?= e($store_label) ?> Collection</h1>
</div>

<div class="container-lux shop-layout">
    <!-- FILTERS -->
    <aside class="filter-panel" id="filterPanel">
        <form method="GET" id="filterForm">
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
                <label>
                    <input type="checkbox" name="availability" value="in_stock" <?= ($filters['availability'] ?? '') === 'in_stock' ? 'checked' : '' ?>
                        onchange="document.getElementById('filterForm').submit()">
                    In Stock Only
                </label>
            </div>

            <button type="submit" class="btn-lux btn-lux-sm btn-lux-block">Apply Filters</button>
            <a href="?" class="btn-lux btn-lux-ghost btn-lux-sm btn-lux-block" style="margin-top:0.8rem;">Clear All</a>
        </form>
    </aside>

    <!-- PRODUCT GRID -->
    <div>
        <div class="shop-toolbar">
            <form class="search-box" method="GET">
                <input type="text" name="q" placeholder="Search <?= strtolower(e($store_label)) ?>..." value="<?= e($filters['search'] ?? '') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </form>

            <div style="display:flex; align-items:center; gap:1rem;">
                <span style="font-size:0.8rem; color:var(--text-secondary);"><?= $results['total'] ?> pieces</span>
                <select class="sort-select" onchange="window.location.href=updateQueryParam('sort', this.value)">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                    <option value="best_selling" <?= $sort === 'best_selling' ? 'selected' : '' ?>>Best Selling</option>
                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                </select>
            </div>
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
                    <?php if ($p['is_new_arrival']): ?><span class="badge-lux product-card-badge">New</span><?php endif; ?>
                    <img src="<?= e($p['primary_image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
                    <button class="wish-btn" aria-label="Add to wishlist"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.6z"/></svg></button>
                    <button class="quick-add">Quick Add</button>
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
                <?php if ($i === $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                <?php endif; ?>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>