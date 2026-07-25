<?php
/**
 * includes/sidebar.php
 * Requires hotel-config.php already included. Expects optional $activePage.
 */
$activePage = $activePage ?? '';
$isLoggedIn = isHotelLoggedIn();
$guestTier  = $_SESSION['user']['tier'] ?? 'regular';
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside id="hotelSidebar" class="hotel-sidebar" role="navigation" aria-label="Main navigation">
    <div class="sidebar-head">
        <a href="hotel-industry.php" class="brand">
            <div class="brand-mark">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#cfa76b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
            </div>
            <span class="brand-name">Nocturne</span>
        </a>
        <button class="sidebar-close" id="sidebarClose" aria-label="Close sidebar">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    <?php if ($isLoggedIn): ?>
    <div class="sidebar-tier <?= $guestTier==='vip'?'vip':'regular' ?>">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2l2.4 7.2H22l-6 4.6 2.3 7.2L12 16.4 5.7 21l2.3-7.2-6-4.6h7.6z"/></svg>
        <?= $guestTier==='vip' ? 'Nocturne Noir — VIP Member' : 'Nocturne Guest — Regular' ?>
    </div>
    <?php endif; ?>

    <nav class="sidebar-nav">
        <div class="sidebar-label">Navigation</div>
        <a href="hotel-industry.php" class="sidebar-link <?= $activePage==='home'?'active':'' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M3 11l9-7 9 7"/><path d="M5 10v9h14v-9"/></svg>
            <span>Home</span>
        </a>
        <a href="hotel-industry.php#suites" class="sidebar-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="3" y="7" width="18" height="12" rx="1"/><path d="M3 11h18"/><path d="M7 7V5.5A1.5 1.5 0 0 1 8.5 4h7A1.5 1.5 0 0 1 17 5.5V7"/></svg>
            <span>Residences</span>
        </a>
        <a href="hotel-industry.php#offers" class="sidebar-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M20.6 12.2 12.8 20a2 2 0 0 1-2.83 0l-7-7A2 2 0 0 1 2.4 11.6V4.4A2 2 0 0 1 4.4 2.4h7.2a2 2 0 0 1 1.4.6l7.6 7.6a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="7.5" r="1"/></svg>
            <span>Offers</span>
        </a>
        <a href="hotel-reserve.php" class="sidebar-link <?= $activePage==='reserve'?'active':'' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="3" y="4" width="18" height="17" rx="1.5"/><path d="M3 9h18"/><path d="M8 2v4M16 2v4"/></svg>
            <span>Reserve a Suite</span>
        </a>
        <a href="hotel-services.php" class="sidebar-link <?= $activePage==='services'?'active':'' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M12 2v20M4 8h16M6 8v9a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V8"/></svg>
            <span>Spa, Shop &amp; Entertainment</span>
        </a>
        <a href="testimonial.php" class="sidebar-link <?= $activePage==='testimonial'?'active':'' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <span>Testimonials</span>
        </a>
        <?php if ($isLoggedIn): ?>
        <div class="sidebar-divider"></div>
        <div class="sidebar-label">Account</div>
        <a href="dashboard.php" class="sidebar-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <span>My Bookings &amp; Orders</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <?php if ($isLoggedIn): ?>
            <div class="sidebar-user">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#cfa76b" stroke-width="1.2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
                <span><?= htmlspecialchars($_SESSION['user']['name']) ?></span>
            </div>
            <a href="logout.php" class="sidebar-link" style="color:#c0574a">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                <span>Logout</span>
            </a>
        <?php else: ?>
            <a href="hotel-login.php" class="sidebar-link" style="color:#7fa876">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/></svg>
                <span>Login</span>
            </a>
            <a href="register.php" class="sidebar-link" style="color:var(--h-gold)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                <span>Create an Account</span>
            </a>
        <?php endif; ?>
    </div>
</aside>

<script>
(function () {
    const sidebar = document.getElementById('hotelSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('sidebarToggle');
    const closeBtn = document.getElementById('sidebarClose');
    if (!sidebar || !toggleBtn) return;

    function openSidebar(){ sidebar.classList.add('open'); overlay.classList.add('active'); toggleBtn.classList.add('open'); toggleBtn.setAttribute('aria-expanded','true'); document.body.style.overflow='hidden'; }
    function closeSidebar(){ sidebar.classList.remove('open'); overlay.classList.remove('active'); toggleBtn.classList.remove('open'); toggleBtn.setAttribute('aria-expanded','false'); document.body.style.overflow=''; }

    toggleBtn.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
    closeBtn?.addEventListener('click', closeSidebar);
    overlay?.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });
})();
</script>