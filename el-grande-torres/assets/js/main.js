/* =====================================================================
   EL GRANDE DE LA TORRES — Core Interactions
   ===================================================================== */
document.addEventListener('DOMContentLoaded', () => {

    const loadingScreen = document.getElementById('loading-screen');
    if (loadingScreen) {
        window.addEventListener('load', () => setTimeout(() => loadingScreen.classList.add('hidden'), 450));
        setTimeout(() => loadingScreen.classList.add('hidden'), 2600);
    }

    const navbar = document.querySelector('.navbar-lux');
    const scrollProgress = document.querySelector('.scroll-progress');
    function onScroll() {
        if (navbar) navbar.classList.toggle('scrolled', window.scrollY > 40);
        if (scrollProgress) {
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const progress = docHeight > 0 ? (window.scrollY / docHeight) * 100 : 0;
            scrollProgress.style.width = progress + '%';
        }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    const sidebar = document.querySelector('.sidebar-lux');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    function openSidebar() { sidebar?.classList.add('open'); sidebarOverlay?.classList.add('open'); document.body.style.overflow = 'hidden'; }
    function closeSidebar() { sidebar?.classList.remove('open'); sidebarOverlay?.classList.remove('open'); document.body.style.overflow = ''; }
    document.querySelectorAll('[data-sidebar-open]').forEach(b => b.addEventListener('click', openSidebar));
    document.querySelectorAll('[data-sidebar-close]').forEach(b => b.addEventListener('click', closeSidebar));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSidebar(); });

    const revealEls = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && revealEls.length) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add('is-visible'); io.unobserve(entry.target); } });
        }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });
        revealEls.forEach(el => io.observe(el));
    } else { revealEls.forEach(el => el.classList.add('is-visible')); }

    document.querySelectorAll('[data-scroller]').forEach(scroller => {
        const track = scroller.querySelector('.product-scroller');
        scroller.querySelector('[data-scroll-prev]')?.addEventListener('click', () => track.scrollBy({ left: -640, behavior: 'smooth' }));
        scroller.querySelector('[data-scroll-next]')?.addEventListener('click', () => track.scrollBy({ left: 640, behavior: 'smooth' }));
    });

    document.querySelectorAll('.wish-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault(); e.stopPropagation();
            if (!window.IS_LOGGED_IN) { window.location.href = window.SITE_URL + '/auth/login.php'; return; }
            const data = new FormData();
            data.append('product_type', btn.dataset.productType);
            data.append('product_id', btn.dataset.productId);
            data.append('csrf_token', window.CSRF_TOKEN);
            fetch(window.SITE_URL + '/api/wishlist_toggle.php', { method: 'POST', body: data })
                .then(r => r.json()).then(res => {
                    if (res.success) {
                        btn.classList.toggle('active', res.action === 'added');
                        showToast(res.action === 'added' ? 'Added to wishlist' : 'Removed from wishlist');
                    }
                });
        });
    });

    const newsletterForm = document.querySelector('.newsletter-form');
    newsletterForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const input = newsletterForm.querySelector('input[type="email"]');
        if (input && input.value) {
            fetch(window.SITE_URL + '/api/newsletter_subscribe.php', {
                method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'email=' + encodeURIComponent(input.value) + '&csrf_token=' + encodeURIComponent(window.CSRF_TOKEN || '')
            }).then(r => r.json()).then(data => { showToast(data.message || 'Thank you for subscribing.'); if (data.success) input.value = ''; })
              .catch(() => showToast('Something went wrong. Please try again.'));
        }
    });

    window.showToast = function (message, duration = 3200) {
        let container = document.getElementById('toast-container');
        if (!container) { container = document.createElement('div'); container.id = 'toast-container'; document.body.appendChild(container); }
        const toast = document.createElement('div');
        toast.className = 'toast-lux'; toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.4s ease'; setTimeout(() => toast.remove(), 400); }, duration);
    };
});
