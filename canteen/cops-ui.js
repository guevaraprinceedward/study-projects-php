/* ==========================================================================
   cops-ui.js — shared behaviours for the COPS pages
   Exposes window.COPS = { toast, money, esc, reduced }
   ========================================================================== */
(function () {
    'use strict';
    var NS = 'http://www.w3.org/2000/svg';
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var fine = window.matchMedia('(hover:hover) and (pointer:fine)').matches;

    /* ── helpers ── */
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function money(n) { return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    function toast(msg, type, ms) {
        var host = document.getElementById('toastHost');
        if (!host) { host = document.createElement('div'); host.id = 'toastHost'; host.setAttribute('aria-live', 'polite'); document.body.appendChild(host); }
        var t = document.createElement('div');
        t.className = 'toast' + (type ? ' ' + type : '');
        t.textContent = msg;
        host.appendChild(t);
        setTimeout(function () { t.classList.add('out'); setTimeout(function () { t.remove(); }, 320); }, ms || 3200);
    }

    /* ── shared SVG gradients (gold) ── */
    function injectDefs() {
        if (document.getElementById('copsDefs')) return;
        var s = document.createElementNS(NS, 'svg');
        s.setAttribute('id', 'copsDefs'); s.setAttribute('width', '0'); s.setAttribute('height', '0');
        s.setAttribute('style', 'position:absolute'); s.setAttribute('aria-hidden', 'true'); s.setAttribute('focusable', 'false');
        s.innerHTML = '<defs>' +
            '<linearGradient id="gG" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f0d78e"/><stop offset=".5" stop-color="#c9a24a"/><stop offset="1" stop-color="#8a6a1f"/></linearGradient>' +
            '<linearGradient id="gD" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#c9a24a"/><stop offset="1" stop-color="#6e5318"/></linearGradient>' +
            '</defs>';
        document.body.insertBefore(s, document.body.firstChild);
    }

    /* ── laurel wreath generator (fills every [data-wreath] group) ── */
    function el(n, a) { var e = document.createElementNS(NS, n); for (var k in a) e.setAttribute(k, a[k]); return e; }
    function wreath(g, R, pairs) {
        var rx = R * .23, ry = R * .095;
        [-1, 1].forEach(function (s) {
            var d = '';
            for (var i = 0; i <= 24; i++) {
                var th = (16 + (152 - 16) * i / 24) * Math.PI / 180;
                d += (i ? 'L' : 'M') + (s * R * Math.sin(th)).toFixed(2) + ' ' + (R * Math.cos(th)).toFixed(2);
            }
            g.appendChild(el('path', { d: d, fill: 'none', stroke: '#8a6a1f', 'stroke-width': Math.max(.5, R * .045), 'stroke-linecap': 'round' }));
            for (var j = 0; j < pairs; j++) {
                var t2 = (22 + (150 - 22) * j / (pairs - 1)) * Math.PI / 180;
                var px = s * R * Math.sin(t2), py = R * Math.cos(t2);
                var tan = Math.atan2(-Math.sin(t2), s * Math.cos(t2)) * 180 / Math.PI;
                [1, -1].forEach(function (o) {
                    var cx = px + s * Math.sin(t2) * o * R * .1, cy = py + Math.cos(t2) * o * R * .1;
                    g.appendChild(el('ellipse', {
                        cx: cx.toFixed(2), cy: cy.toFixed(2), rx: rx.toFixed(2), ry: ry.toFixed(2),
                        transform: 'rotate(' + (tan + o * s * 34).toFixed(1) + ' ' + cx.toFixed(2) + ' ' + cy.toFixed(2) + ')', fill: 'url(#gG)'
                    }));
                });
            }
            g.appendChild(el('circle', { cx: (s * R * Math.sin(.2)).toFixed(2), cy: (R * Math.cos(.2) + R * .1).toFixed(2), r: (R * .07).toFixed(2), fill: '#d9b45a' }));
        });
    }

    function init() {
        injectDefs();
        document.querySelectorAll('[data-wreath]').forEach(function (g) {
            wreath(g, parseFloat(g.getAttribute('data-wreath')), parseInt(g.getAttribute('data-pairs'), 10) || 7);
        });

        /* header: solid on scroll + mobile nav */
        var header = document.querySelector('.site-header');
        if (header) {
            var f = function () { header.classList.toggle('scrolled', window.scrollY > 24); };
            window.addEventListener('scroll', f, { passive: true }); f();
            var tg = header.querySelector('.nav-toggle');
            if (tg) {
                tg.addEventListener('click', function () {
                    var open = header.classList.toggle('nav-open');
                    tg.setAttribute('aria-expanded', open ? 'true' : 'false');
                });
                header.querySelectorAll('.main-nav a').forEach(function (a) { a.addEventListener('click', function () { header.classList.remove('nav-open'); }); });
            }
        }

        /* scroll reveal */
        var rv = document.querySelectorAll('[data-reveal]');
        if (reduced || !('IntersectionObserver' in window)) {
            rv.forEach(function (e) { e.classList.add('in'); });
        } else {
            var io = new IntersectionObserver(function (es) {
                es.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
            }, { threshold: .15, rootMargin: '0px 0px -50px 0px' });
            rv.forEach(function (e) { io.observe(e); });
        }

        /* animated counters */
        var cs = document.querySelectorAll('[data-count]');
        if (cs.length) {
            var cio = new IntersectionObserver(function (es) {
                es.forEach(function (en) {
                    if (!en.isIntersecting) return;
                    var e = en.target, target = parseInt(e.dataset.count, 10) || 0;
                    if (reduced) { e.textContent = target.toLocaleString(); cio.unobserve(e); return; }
                    var t0 = performance.now(), dur = 1400;
                    (function step(now) {
                        var p = Math.min(1, (now - t0) / dur), eased = 1 - Math.pow(1 - p, 3);
                        e.textContent = Math.floor(eased * target).toLocaleString();
                        if (p < 1) requestAnimationFrame(step);
                    })(t0);
                    cio.unobserve(e);
                });
            }, { threshold: .6 });
            cs.forEach(function (e) { cio.observe(e); });
        }

        /* card tilt with a soft light glare */
        if (fine && !reduced) {
            document.querySelectorAll('[data-tilt]').forEach(function (card) {
                card.addEventListener('mousemove', function (e) {
                    var r = card.getBoundingClientRect();
                    var px = (e.clientX - r.left) / r.width, py = (e.clientY - r.top) / r.height;
                    card.classList.add('tilting');
                    card.style.setProperty('--gx', (px * 100) + '%'); card.style.setProperty('--gy', (py * 100) + '%');
                    card.style.transform = 'perspective(900px) rotateY(' + ((px - .5) * 8).toFixed(2) + 'deg) rotateX(' + ((.5 - py) * 8).toFixed(2) + 'deg) translateY(-4px)';
                });
                card.addEventListener('mouseleave', function () { card.classList.remove('tilting'); card.style.transform = ''; });
            });

            /* mouse parallax scenes: children with data-depth drift by that many px */
            document.querySelectorAll('[data-parallax]').forEach(function (scene) {
                var layers = Array.prototype.slice.call(scene.querySelectorAll('[data-depth]'));
                var tx = 0, ty = 0, cx = 0, cy = 0, raf = null;
                function loop() {
                    cx += (tx - cx) * .08; cy += (ty - cy) * .08;
                    layers.forEach(function (l) {
                        var d = parseFloat(l.dataset.depth) || 0;
                        l.style.transform = 'translate3d(' + (cx * d).toFixed(2) + 'px,' + (cy * d * .6).toFixed(2) + 'px,0)';
                    });
                    raf = (Math.abs(tx - cx) > .001 || Math.abs(ty - cy) > .001) ? requestAnimationFrame(loop) : null;
                }
                scene.addEventListener('mousemove', function (e) {
                    var r = scene.getBoundingClientRect();
                    tx = ((e.clientX - r.left) / r.width - .5) * 2; ty = ((e.clientY - r.top) / r.height - .5) * 2;
                    if (!raf) raf = requestAnimationFrame(loop);
                });
                scene.addEventListener('mouseleave', function () { tx = 0; ty = 0; if (!raf) raf = requestAnimationFrame(loop); });
            });
        }
    }

    window.COPS = { toast: toast, money: money, esc: esc, reduced: reduced };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();