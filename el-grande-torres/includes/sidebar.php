<?php
/**
 * Full account/navigation sidebar — slides in from the right.
 * Requires config/app.php already included.
 */
$cart_count_sb = 0;
$wishlist_count_sb = 0;
if (is_logged_in()) {
    try {
        $stmt = getDB()->prepare("SELECT COALESCE(SUM(ci.quantity),0) AS cnt FROM cart_items ci JOIN carts c ON c.cart_id = ci.cart_id WHERE c.user_id = ?");
        $stmt->execute([current_user_id()]);
        $cart_count_sb = (int)($stmt->fetch()['cnt'] ?? 0);

        $stmt = getDB()->prepare("SELECT COUNT(*) AS cnt FROM wishlist WHERE user_id = ?");
        $stmt->execute([current_user_id()]);
        $wishlist_count_sb = (int)($stmt->fetch()['cnt'] ?? 0); 
    } catch (Throwable $e) {}
}
?>
<div class="sidebar-overlay" data-sidebar-close></div>
<aside class="sidebar-lux" aria-label="Site menu">
    <div class="sidebar-top">
        <a href="<?= SITE_URL ?>/index.php" class="sidebar-logo">The Atelier Noir<br><em>El Grande</em></a>
        <button class="sidebar-close" aria-label="Close menu" data-sidebar-close>
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    <?php if (is_logged_in()): ?>
    <a href="<?= SITE_URL ?>/customer/dashboard.php" class="sidebar-account-link">
        <span class="sidebar-account-avatar"><?= e(strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1))) ?></span>
        <span>Dashboard</span>
    </a>
    <?php endif; ?>

    <nav class="sidebar-nav">
        <div class="sb-group">
            <div class="sb-group-title">Shop</div>
            <a href="<?= SITE_URL ?>/new-arrivals.php">New Arrivals</a>
            <a href="<?= SITE_URL ?>/best-sellers.php">Best Sellers</a>
            <a href="<?= SITE_URL ?>/trending.php">Trending Now</a>
            <a href="<?= SITE_URL ?>/featured.php">Featured Collection</a>
        </div>

        <div class="sb-group">
            <div class="sb-group-title">Fashion — Clothing</div>
            <a href="<?= SITE_URL ?>/clothing.php?category[]=t-shirts">T-Shirts</a>
            <a href="<?= SITE_URL ?>/clothing.php?category[]=shirts">Shirts</a>
            <a href="<?= SITE_URL ?>/clothing.php?category[]=hoodies-jackets">Hoodies &amp; Jackets</a>
            <a href="<?= SITE_URL ?>/clothing.php?category[]=coats">Coats</a>
            <a href="<?= SITE_URL ?>/clothing.php?category[]=vests">Vests</a>
            <a href="<?= SITE_URL ?>/clothing.php?category[]=pants">Pants</a>
            <a href="<?= SITE_URL ?>/clothing.php?category[]=dresses">Dresses</a>
            <a href="<?= SITE_URL ?>/clothing.php?category[]=sneakers">Sneakers</a>
            <a href="<?= SITE_URL ?>/clothing.php?category[]=loafers">Loafers</a>
        </div>

        <div class="sb-group">
            <div class="sb-group-title">Essentials</div>
            <a href="<?= SITE_URL ?>/essentials.php?category[]=everyday-essentials">Everyday Essentials</a>
            <a href="<?= SITE_URL ?>/essentials.php?category[]=travel-essentials">Travel Essentials</a>
            <a href="<?= SITE_URL ?>/essentials.php?category[]=lifestyle">Lifestyle</a>
            <a href="<?= SITE_URL ?>/essentials.php?category[]=grooming">Grooming</a>
            <a href="<?= SITE_URL ?>/essentials.php?category[]=necklaces">Necklaces</a>
            <a href="<?= SITE_URL ?>/essentials.php?category[]=rings">Rings</a>
            <a href="<?= SITE_URL ?>/essentials.php?category[]=hats">Hats</a>
            <a href="<?= SITE_URL ?>/essentials.php?category[]=glasses">Glasses</a>
            <a href="<?= SITE_URL ?>/essentials.php?category[]=watches">Watches</a>
        </div>

        <div class="sb-group">
            <div class="sb-group-title">Collections</div>
            <a href="<?= SITE_URL ?>/clothing.php?gender=men">Men's Collection</a>
            <a href="<?= SITE_URL ?>/clothing.php?gender=women">Women's Collection</a>
            <a href="<?= SITE_URL ?>/clothing.php?gender=unisex">Unisex</a>
            <a href="<?= SITE_URL ?>/seasonal.php">Seasonal Collection</a>
        </div>

        <div class="sb-group">
            <div class="sb-group-title">Discover</div>
            <a href="<?= SITE_URL ?>/about.php#story">Our Story</a>
            <a href="<?= SITE_URL ?>/lookbook.php">Lookbook</a>
            <a href="<?= SITE_URL ?>/journal.php">Journal</a>
            <a href="<?= SITE_URL ?>/about.php">About Us</a>
            <a href="<?= SITE_URL ?>/contact.php">Contacts</a>
            <a href="<?= SITE_URL ?>/offers.php">Offers</a>
        </div>
    </nav>

    <div class="sidebar-bottom">
        <a href="<?= SITE_URL ?>/customer/wishlist.php" class="sb-utility-link">
            <span>Wishlist</span>
            <?php if ($wishlist_count_sb > 0): ?><span class="sb-count"><?= $wishlist_count_sb ?></span><?php endif; ?>
        </a>
        <a href="<?= SITE_URL ?>/cart.php" class="sb-utility-link">
            <span>Cart</span>
            <?php if ($cart_count_sb > 0): ?><span class="sb-count"><?= $cart_count_sb ?></span><?php endif; ?>
        </a>
        <?php if (is_logged_in()): ?>
            <a href="<?= SITE_URL ?>/auth/logout.php" class="sb-utility-link sb-logout">Log Out</a>
        <?php else: ?>
            <a href="<?= SITE_URL ?>/auth/login.php" class="btn-lux btn-lux-block" style="margin-top:0.6rem;">Sign In</a>
            <a href="<?= SITE_URL ?>/auth/register.php" class="btn-lux btn-lux-ghost btn-lux-block" style="margin-top:0.7rem;">Create Account</a>
        <?php endif; ?>
        <div class="sidebar-meta">Manila · Est. 2026 · 7-Star House</div>
    </div>
</aside>