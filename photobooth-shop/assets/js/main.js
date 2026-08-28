/* =====================================================================
   KISLAP BOOTH ✨ — Core Interactions
   ===================================================================== */
document.addEventListener('DOMContentLoaded', () => {

    // Reveal-on-scroll
    const revealEls = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && revealEls.length) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add('is-visible'); io.unobserve(entry.target); } });
        }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });
        revealEls.forEach(el => io.observe(el));
    } else { revealEls.forEach(el => el.classList.add('is-visible')); }

    // Cute page transition on internal navigation
    const transitionEl = document.getElementById('page-transition');
    if (transitionEl) {
        document.querySelectorAll('a[href]').forEach(link => {
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
            if (link.target === '_blank') return;
            if (link.hostname && link.hostname !== window.location.hostname) return;

            link.addEventListener('click', (e) => {
                e.preventDefault();
                transitionEl.classList.add('active');
                setTimeout(() => { window.location.href = href; }, 650);
            });
        });
    }

    // 3D tilt effect for cards that opt in
    document.querySelectorAll('[data-tilt]').forEach(card => {
        card.style.setProperty('--dur', '0.15s');
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width - 0.5;
            const y = (e.clientY - rect.top) / rect.height - 0.5;
            card.style.transform = `perspective(800px) rotateY(${x * 10}deg) rotateX(${-y * 10}deg) translateY(-6px)`;
        });
        card.addEventListener('mouseleave', () => { card.style.transform = ''; });
    });

    window.confettiBurst = function (x, y, count = 14) {
        const emojis = ['✨', '💗', '⭐', '🎀', '💫'];
        for (let i = 0; i < count; i++) {
            const el = document.createElement('span');
            el.textContent = emojis[Math.floor(Math.random() * emojis.length)];
            el.style.cssText = `position:fixed; left:${x}px; top:${y}px; font-size:${14 + Math.random() * 14}px; pointer-events:none; z-index:4000; transition: transform 0.9s cubic-bezier(.22,1,.36,1), opacity 0.9s; opacity:1;`;
            document.body.appendChild(el);
            const angle = Math.random() * Math.PI * 2;
            const dist = 60 + Math.random() * 90;
            requestAnimationFrame(() => {
                el.style.transform = `translate(${Math.cos(angle) * dist}px, ${Math.sin(angle) * dist - 40}px) rotate(${Math.random() * 360}deg)`;
                el.style.opacity = '0';
            });
            setTimeout(() => el.remove(), 950);
        }
    };

    window.showToast = function (message, type = 'success', duration = 3200) {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.cssText = 'position:fixed;top:100px;left:50%;transform:translateX(-50%);z-index:3000;';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.className = 'flash-toast flash-' + type;
        toast.style.position = 'relative';
        toast.style.marginBottom = '0.6rem';
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), duration);
    };
});