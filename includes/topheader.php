<?php
/**
 * includes/topheader.php
 * Requires hotel-config.php already included. Expects optional $activePage.
 */
$activePage = $activePage ?? '';
$isLoggedIn = isHotelLoggedIn();
?>
<header class="site-header">
    <div class="header-inner">
        <div class="header-left">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false">
                <span class="hb"><span></span><span></span><span></span></span>
            </button>
            <a href="hotel-industry.php" class="brand">
                <div class="brand-mark">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#cfa76b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
                </div>
                <span class="brand-name">Nocturne</span>
                <span class="brand-sub">Manila Bay</span>
            </a>
        </div>

        <nav class="header-nav">
            <a href="hotel-industry.php" class="<?= $activePage==='home'?'active':'' ?>">Home</a>
            <a href="hotel-industry.php#suites">Residences</a>
            <a href="hotel-industry.php#offers">Offers</a>
            <a href="hotel-reserve.php" class="<?= $activePage==='reserve'?'active':'' ?>">Reserve</a>
            <a href="hotel-services.php" class="<?= $activePage==='services'?'active':'' ?>">Spa &amp; Shop</a>
            <a href="testimonial.php" class="<?= $activePage==='testimonial'?'active':'' ?>">Testimonials</a>
            <a href="about-us.php" class="<?= $activePage==='about'?'active':'' ?>">About Us</a>
        </nav>

        <div class="header-right">
            <?php if ($isLoggedIn): ?>
                <span class="guest-name">Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?></span>
                <a href="dashboard.php" class="btn-brass">My Account</a>
            <?php else: ?>
                <a href="hotel-login.php" class="btn-brass">Login</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (isset($_GET['loggedout'])): ?>
<div class="toast" id="logoutToast">You've been logged out — come back soon.</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const t = document.getElementById('logoutToast');
    requestAnimationFrame(() => t.classList.add('show'));
    setTimeout(() => t.classList.remove('show'), 3800);
});
</script>
<?php endif; ?>