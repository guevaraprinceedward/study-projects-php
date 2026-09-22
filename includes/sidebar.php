<?php
/**
 * includes/sidebar.php
 * Requires hotel-config.php already included. Expects optional $activePage.
 */
$activePage = $activePage ?? '';
$isLoggedIn = isHotelLoggedIn();
$guestTier  = $_SESSION['user']['tier'] ?? 'regular';

// Optional: lifetime spend tracking for the progress-to-VIP bar.
// If $_SESSION['user']['lifetime_spend'] isn't set anywhere in your login/booking
// flow yet, this block just quietly skips the progress bar — tier badge still shows.
$lifetimeSpend = $_SESSION['user']['lifetime_spend'] ?? null;
$nextTierRow   = null;
if ($isLoggedIn && $guestTier !== 'vip' && isset($conn) && $lifetimeSpend !== null) {
    $nt = $conn->query("SELECT * FROM membership_tiers WHERE tier != 'regular' ORDER BY min_lifetime_spend ASC LIMIT 1");
    if ($nt && $nt->num_rows > 0) $nextTierRow = $nt->fetch_assoc();
}
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
        <a href="about-us.php" class="sidebar-link <?= $activePage==='about'?'active':'' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M12 2v20M4 8h16M6 8v9a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V8"/></svg>
            <span>About Us</span>
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

    <!-- ═══ FOOTER: account card + login/register/logout ═══ -->
    <div class="sidebar-footer">
        <?php if ($isLoggedIn):
            $initial = strtoupper(mb_substr($_SESSION['user']['name'], 0, 1));
        ?>
        <div class="sidebar-account">
            <div class="sa-top">
                <div class="sa-avatar"><?= htmlspecialchars($initial) ?></div>
                <div class="sa-info">
                    <span class="sa-name"><?= htmlspecialchars($_SESSION['user']['name']) ?></span>
                    <span class="sa-tier <?= $guestTier==='vip'?'vip':'' ?>">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.4 7.2H22l-6 4.6 2.3 7.2L12 16.4 5.7 21l2.3-7.2-6-4.6h7.6z"/></svg>
                        <?= $guestTier==='vip' ? 'Nocturne Noir' : 'Regular Guest' ?>
                    </span>
                </div>
            </div>

            <?php if ($nextTierRow && $lifetimeSpend !== null && $nextTierRow['min_lifetime_spend'] > 0):
                $threshold = (float)$nextTierRow['min_lifetime_spend'];
                $pct       = max(0, min(100, ($lifetimeSpend / $threshold) * 100));
                $remaining = max(0, $threshold - $lifetimeSpend);
            ?>
            <div class="sa-progress">
                <div class="sa-progress-bar"><div class="sa-progress-fill" style="width:<?= $pct ?>%"></div></div>
                <span class="sa-progress-label">₱<?= number_format($remaining) ?> to <?= htmlspecialchars($nextTierRow['label']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <a href="logout.php" class="sidebar-logout">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
            <span>Logout</span>
        </a>

        <?php else: ?>
        <div class="sidebar-guest">
            <p class="sg-text">Sign in to reserve suites, place orders, and track your Nocturne points.</p>
            <a href="hotel-login.php" class="btn-ghost sg-btn">Login</a>
            <a href="register.php" class="btn-gold sg-btn">Create an Account</a>
        </div>
        <?php endif; ?>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
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
});
</script>