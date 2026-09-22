<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/product-functions.php';
require_login();

$user_id = current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_wishlist_id'])) {
    require_csrf_or_fail();
    $stmt = getDB()->prepare("DELETE FROM wishlist WHERE wishlist_id = ? AND user_id = ?");
    $stmt->execute([(int)$_POST['remove_wishlist_id'], $user_id]);
    header('Location: ' . SITE_URL . '/customer/wishlist.php');
    exit;
}

$stmt = getDB()->prepare("SELECT * FROM wishlist WHERE user_id = ? ORDER BY added_at DESC");
$stmt->execute([$user_id]);
$wishlist_rows = $stmt->fetchAll();

$wishlist_items = [];
foreach ($wishlist_rows as $row) {
    $table = product_table($row['product_type']);
    $stmt2 = getDB()->prepare("SELECT p.*, c.name AS category_name FROM $table p JOIN categories c ON c.category_id = p.category_id WHERE p.product_id = ?");
    $stmt2->execute([$row['product_id']]);
    $product = $stmt2->fetch();
    if ($product) { $product['wishlist_id'] = $row['wishlist_id']; $product['ptype'] = $row['product_type']; $wishlist_items[] = $product; }
}

$page_title = "My Wishlist — El Grande De La Torres";
$extra_css = SITE_URL . '/assets/css/account.css';
require_once __DIR__ . '/../includes/header.php';
$active_page = 'wishlist';
require_once __DIR__ . '/../includes/customer-header.php';
?>

<div class="section-head" style="justify-content:flex-start; text-align:left; margin-bottom:1.5rem;">
    <h2 class="section-title" style="font-size:1.5rem;">My Wishlist</h2>
</div>

<?php if (empty($wishlist_items)): ?>
    <div class="empty-state" style="text-align:left; padding:2rem 0;">
        <p>Your wishlist is empty.</p>
        <a href="<?= SITE_URL ?>/clothing.php" class="btn-lux" style="margin-top:1.5rem;">Discover Pieces</a>
    </div>
<?php else: ?>
    <div class="wishlist-grid">
        <?php foreach ($wishlist_items as $item): ?>
        <article class="product-card">
            <a href="<?= SITE_URL ?>/product.php?type=<?= $item['ptype'] ?>&slug=<?= e($item['slug']) ?>" class="product-card-media">
                <?php if ($item['is_limited_edition']): ?><div class="product-card-badges"><span class="badge-limited">Limited</span></div><?php endif; ?>
                <img src="<?= e($item['primary_image']) ?>" alt="<?= e($item['name']) ?>" loading="lazy">
            </a>
            <p class="product-card-cat"><?= e($item['category_name']) ?></p>
            <h3 class="product-card-title"><?= e($item['name']) ?></h3>
            <p class="product-card-price"><?= CURRENCY_SYMBOL . number_format($item['price'], 2) ?></p>
            <form method="POST" style="margin-top:0.8rem;">
                <?= csrf_field() ?>
                <input type="hidden" name="remove_wishlist_id" value="<?= $item['wishlist_id'] ?>">
                <button type="submit" class="btn-lux btn-lux-ghost btn-lux-sm btn-lux-block">Remove</button>
            </form>
        </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/customer-footer.php';
require_once __DIR__ . '/../includes/footer.php';
?>