/* cops-menu.js — smooth motion for menu.php (works alongside assets/js/menu.js) */
(function () {
    'use strict';
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* gold scroll-progress bar */
    var bar = document.createElement('div');
    bar.className = 'cm-progress';
    bar.setAttribute('aria-hidden', 'true');
    document.body.appendChild(bar);
    function progress() {
        var h = document.documentElement, max = h.scrollHeight - h.clientHeight;
        bar.style.transform = 'scaleX(' + (max > 0 ? Math.min(1, h.scrollTop / max) : 0) + ')';
    }
    window.addEventListener('scroll', progress, { passive: true });
    progress();

    if (reduced) return;

    /* reveal: what is already on screen eases in one by one; the rest eases in as you scroll to it */
    var vh = window.innerHeight;
    var targets = [].slice.call(document.querySelectorAll('.cat-section > h2, .grid-products > *, .no-results'));
    var io = ('IntersectionObserver' in window) ? new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (!en.isIntersecting) return;
            var el = en.target;
            io.unobserve(el);
            el.classList.add('rv-in');
            var sec = el.closest('.cat-section'); if (sec) sec.classList.add('cm-seen');
            setTimeout(function () {                       // hand control back to the hover transitions
                el.classList.remove('rv-pending', 'rv-in');
                el.style.transitionDelay = '';
            }, 1400);
        });
    }, { threshold: .12, rootMargin: '0px 0px -40px 0px' }) : null;

    var shown = 0;
    targets.forEach(function (el, i) {
        var r = el.getBoundingClientRect();
        if (!io || r.top < vh) {
            el.classList.add('cm-enter');
            el.style.animationDelay = Math.min(shown++, 10) * 70 + 'ms';
            var sec = el.closest('.cat-section'); if (sec) sec.classList.add('cm-seen');
        } else {
            var idx = Array.prototype.indexOf.call(el.parentNode.children, el);
            el.classList.add('rv-pending');
            el.style.transitionDelay = (idx % 4) * 90 + 'ms';
            io.observe(el);
        }
    });

    /* the school picture leans gently toward the mouse */
    var frame = document.querySelector('.menu-hero .scene-frame');
    if (frame && window.matchMedia('(hover:hover) and (pointer:fine)').matches) {
        var host = frame.parentNode;
        host.addEventListener('mousemove', function (e) {
            var r = host.getBoundingClientRect();
            var px = (e.clientX - r.left) / r.width - .5, py = (e.clientY - r.top) / r.height - .5;
            frame.style.transform = 'rotateY(' + (px * 5).toFixed(2) + 'deg) rotateX(' + (-py * 4).toFixed(2) + 'deg)';
        });
        host.addEventListener('mouseleave', function () { frame.style.transform = ''; });
    }
})();