<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/product-functions.php';

$store_type = ($_GET['type'] ?? '') === 'essentials' ? 'essentials' : 'clothing';
$slug = clean_input($_GET['slug'] ?? '');
$product = $slug ? get_product_by_slug($store_type, $slug) : null;

if (!$product) {
    http_response_code(404);
    $page_title = "Piece Not Found — El Grande De La Torres";
    $extra_css = SITE_URL . '/assets/css/shop.css';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="section" style="padding-top:calc(var(--nav-height) + 6rem); text-align:center;">
            <h1 class="section-title">This piece could not be found.</h1>
            <a href="' . SITE_URL . '/' . $store_type . '.php" class="btn-lux" style="margin-top:2rem;">Return to Collection</a>
          </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

try {
    $table = product_table($store_type);
    getDB()->prepare("UPDATE $table SET views_count = views_count + 1 WHERE product_id = ?")->execute([$product['product_id']]);
} catch (Throwable $e) {}

$gallery = [];
if (!empty($product['gallery_images'])) {
    $decoded = json_decode($product['gallery_images'], true);
    if (is_array($decoded)) $gallery = $decoded;
}
array_unshift($gallery, $product['primary_image']);
$gallery = array_unique($gallery);

$sizes = !empty($product['available_sizes']) ? array_map('trim', explode(',', $product['available_sizes'])) : [];
$colors = !empty($product['available_colors']) ? array_map('trim', explode(',', $product['available_colors'])) : [];

$related_stmt = getDB()->prepare("SELECT p.*, c.name AS category_name FROM " . product_table($store_type) . " p
                                   JOIN categories c ON c.category_id = p.category_id
                                   WHERE p.category_id = ? AND p.product_id != ? AND p.status = 'active'
                                   ORDER BY RAND() LIMIT 4");
$related_stmt->execute([$product['category_id'], $product['product_id']]);
$related = $related_stmt->fetchAll();

// Product reviews (separate from homepage testimonials)
$review_stmt = getDB()->prepare("SELECT r.*, u.first_name FROM customer_reviews r JOIN users u ON u.user_id = r.user_id
                                  WHERE r.product_type = ? AND r.product_id = ? AND r.is_approved = 1
                                  ORDER BY r.created_at DESC LIMIT 10");
$review_stmt->execute([$store_type, $product['product_id']]);
$reviews = $review_stmt->fetchAll();

$page_title = $product['name'] . " — El Grande De La Torres";
$meta_description = $product['short_description'] ?? ('Shop ' . $product['name'] . ' at El Grande De La Torres.');
$extra_css = SITE_URL . '/assets/css/shop.css';
require_once __DIR__ . '/includes/header.php';
?>

<div class="product-detail container-lux">
    <nav class="breadcrumb-lux">
        <a href="<?= SITE_URL ?>/index.php">Home</a> &nbsp;/&nbsp;
        <a href="<?= SITE_URL ?>/<?= $store_type ?>.php"><?= $store_type === 'essentials' ? 'Essentials' : 'Clothing' ?></a> &nbsp;/&nbsp;
        <a href="<?= SITE_URL ?>/<?= $store_type ?>.php?category[]=<?= e($product['category_slug']) ?>"><?= e($product['category_name']) ?></a> &nbsp;/&nbsp;
        <span><?= e($product['name']) ?></span>
    </nav>

    <div class="pd-layout">
        <div class="pd-gallery reveal reveal-left">
            <div class="pd-gallery-main">
                <?php if ($product['is_limited_edition']): ?><span class="badge-limited pd-limited-flag">Limited Edition</span><?php endif; ?>
                <img id="mainImage" src="<?= e($gallery[0]) ?>" alt="<?= e($product['name']) ?>">
            </div>
            <?php if (count($gallery) > 1): ?>
            <div class="pd-thumbs">
                <?php foreach ($gallery as $i => $img): ?>
                <button class="<?= $i === 0 ? 'active' : '' ?>" onclick="document.getElementById('mainImage').src='<?= e($img) ?>'; document.querySelectorAll('.pd-thumbs button').forEach(b=>b.classList.remove('active')); this.classList.add('active');">
                    <img src="<?= e($img) ?>" alt="View <?= $i + 1 ?>">
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="pd-info reveal reveal-right">
            <span class="eyebrow"><?= e($product['category_name']) ?> · <?= ucfirst(e($product['gender'])) ?></span>
            <h1><?= e($product['name']) ?></h1>
            <?php if ($product['is_limited_edition'] && $product['limited_edition_note']): ?>
            <p class="pd-limited-note"><?= e($product['limited_edition_note']) ?></p>
            <?php endif; ?>
            <p class="pd-price">
                <?php if ($product['compare_at_price']): ?><span class="was"><?= CURRENCY_SYMBOL . number_format($product['compare_at_price'], 2) ?></span><?php endif; ?>
                <?= CURRENCY_SYMBOL . number_format($product['price'], 2) ?>
            </p>
            <p class="pd-desc"><?= e($product['short_description'] ?: substr(strip_tags($product['description'] ?? ''), 0, 200)) ?></p>

            <form id="addToCartForm" data-product-id="<?= $product['product_id'] ?>" data-product-type="<?= $store_type ?>">
                <!-- FIX: hidden inputs so FormData() actually picks up product_id / product_type.
                     data-* attributes on the <form> tag are NOT included by FormData(). -->
                <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                <input type="hidden" name="product_type" value="<?= $store_type ?>">

                <?php if ($sizes): ?>
                <div class="pd-option-group">
                    <h5>Size</h5>
                    <div class="size-options" id="sizeOptions">
                        <?php foreach ($sizes as $i => $s): ?>
                        <button type="button" class="size-btn <?= $i === 0 ? 'active' : '' ?>" data-value="<?= e($s) ?>"><?= e($s) ?></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="size" id="selectedSize" value="<?= e($sizes[0]) ?>">
                </div>
                <?php endif; ?>

                <?php if ($colors): ?>
                <div class="pd-option-group">
                    <h5>Color</h5>
                    <div class="size-options" id="colorOptions">
                        <?php foreach ($colors as $i => $c): ?>
                        <button type="button" class="size-btn <?= $i === 0 ? 'active' : '' ?>" data-value="<?= e($c) ?>"><?= e($c) ?></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="color" id="selectedColor" value="<?= e($colors[0]) ?>">
                </div>
                <?php endif; ?>

                <div class="pd-option-group">
                    <h5>Quantity</h5>
                    <div class="qty-selector">
                        <button type="button" onclick="stepQty(-1)">&minus;</button>
                        <input type="number" name="quantity" id="qtyInput" value="1" min="1" max="<?= max(1, (int)$product['stock_quantity']) ?>" readonly>
                        <button type="button" onclick="stepQty(1)">&plus;</button>
                    </div>
                    <?php if ($product['stock_quantity'] <= 0): ?>
                        <p class="error-text" style="margin-top:0.8rem;">Currently out of stock.</p>
                    <?php elseif ($product['stock_quantity'] <= 5): ?>
                        <p style="margin-top:0.8rem; font-size:0.8rem; color:var(--gold);">Only <?= (int)$product['stock_quantity'] ?> left<?= $product['is_limited_edition'] ? ' — limited edition' : '' ?>.</p>
                    <?php endif; ?>
                </div>

                <div class="pd-actions">
                    <button type="button" class="pd-wish-btn" id="pdWishBtn" data-product-id="<?= $product['product_id'] ?>" data-product-type="<?= $store_type ?>" aria-label="Add to wishlist">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" width="20" height="20"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.6z"/></svg>
                    </button>
                    <button type="submit" class="btn-lux" id="addToCartBtn" <?= $product['stock_quantity'] <= 0 ? 'disabled' : '' ?>>Add to Cart</button>
                    <button type="button" class="btn-lux btn-lux-filled" id="buyNowBtn" <?= $product['stock_quantity'] <= 0 ? 'disabled' : '' ?>>Buy Now</button>
                </div>
            </form>

            <div class="pd-meta-list">
                <details open>
                    <summary>Description <span>+</span></summary>
                    <p><?= nl2br(e($product['description'] ?: 'A piece defined by considered proportion and material integrity.')) ?></p>
                </details>
                <?php if (!empty($product['material'])): ?>
                <details><summary>Material &amp; Craft <span>+</span></summary><p><?= e($product['material']) ?></p></details>
                <?php endif; ?>
                <?php if (!empty($product['care_instructions'])): ?>
                <details><summary>Care Instructions <span>+</span></summary><p><?= nl2br(e($product['care_instructions'])) ?></p></details>
                <?php endif; ?>
                <details>
                    <summary>Shipping &amp; Returns <span>+</span></summary>
                    <p>Complimentary shipping on orders over <?= CURRENCY_SYMBOL ?><?= number_format(FREE_SHIPPING_THRESHOLD, 0) ?>. Returns accepted within 14 days in original condition.</p>
                </details>
                <?php if ($reviews): ?>
                <details>
                    <summary>Client Reviews (<?= count($reviews) ?>) <span>+</span></summary>
                    <?php foreach ($reviews as $rev): ?>
                    <div style="margin-top:1.2rem; padding-top:1.2rem; border-top:1px solid var(--border-color);">
                        <div style="color:var(--gold); font-size:0.8rem;"><?= str_repeat('★', $rev['rating']) . str_repeat('☆', 5 - $rev['rating']) ?></div>
                        <p style="margin-top:0.5rem;"><?= e($rev['review_text']) ?></p>
                        <p style="font-size:0.75rem; color:var(--text-secondary); margin-top:0.4rem;">— <?= e($rev['first_name']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </details>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($related): ?>
    <section class="section" style="padding-bottom:0;">
        <div class="section-head-row reveal" style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:2.5rem;">
            <div><span class="eyebrow">You May Also Like</span><h2 class="section-title" style="font-size:1.8rem;">Related Pieces</h2></div>
        </div>
        <div class="product-grid">
            <?php foreach ($related as $r): ?>
            <article class="product-card reveal">
                <a href="<?= SITE_URL ?>/product.php?type=<?= $store_type ?>&slug=<?= e($r['slug']) ?>" class="product-card-media">
                    <img src="<?= e($r['primary_image']) ?>" alt="<?= e($r['name']) ?>" loading="lazy">
                </a>
                <p class="product-card-cat"><?= e($r['category_name']) ?></p>
                <h3 class="product-card-title"><?= e($r['name']) ?></h3>
                <p class="product-card-price"><?= CURRENCY_SYMBOL . number_format($r['price'], 2) ?></p>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<script>
function stepQty(delta) {
    const input = document.getElementById('qtyInput');
    let val = parseInt(input.value) + delta;
    const max = parseInt(input.max) || 99;
    if (val < 1) val = 1; if (val > max) val = max;
    input.value = val;
}
document.querySelectorAll('#sizeOptions .size-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#sizeOptions .size-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('selectedSize').value = btn.dataset.value;
    });
});
document.querySelectorAll('#colorOptions .size-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#colorOptions .size-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('selectedColor').value = btn.dataset.value;
    });
});

function submitAddToCart(redirectToCheckout) {
    if (!window.IS_LOGGED_IN) { window.location.href = window.SITE_URL + '/auth/login.php'; return; }
    const form = document.getElementById('addToCartForm');
    const data = new FormData(form);
    data.append('csrf_token', window.CSRF_TOKEN);

    fetch(window.SITE_URL + '/api/cart_add.php', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast(redirectToCheckout ? 'Added — heading to checkout' : 'Added to your bag');
                if (redirectToCheckout) window.location.href = window.SITE_URL + '/checkout.php';
            } else { showToast(res.message || 'Could not add to bag.'); }
        })
        .catch(() => showToast('Something went wrong. Please try again.'));
}
document.getElementById('addToCartForm').addEventListener('submit', (e) => { e.preventDefault(); submitAddToCart(false); });
document.getElementById('buyNowBtn').addEventListener('click', () => submitAddToCart(true));
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>