/* cops-pager.js — pagination for product cards on menu.php
   4 products per page, per category section (and for the flat grid when sorting).
   Works without touching card markup. If JS is off, all cards simply stay visible. */
(function () {
    'use strict';
    var PER_PAGE = 4;

    function init() {
        var grids = document.querySelectorAll('.menu-wrap .grid-products');
        Array.prototype.forEach.call(grids, setup);
    }

    function setup(grid) {
        var cards = Array.prototype.slice.call(grid.children);
        if (cards.length <= PER_PAGE) return;

        var total = Math.ceil(cards.length / PER_PAGE);
        var page = 1;

        var nav = document.createElement('nav');
        nav.className = 'cp-pager';
        nav.setAttribute('aria-label', 'Product pages');
        grid.insertAdjacentElement('afterend', nav);

        function pageList() {
            // 1 … current-1 current current+1 … last
            var set = {};
            [1, total, page - 1, page, page + 1].forEach(function (n) {
                if (n >= 1 && n <= total) set[n] = true;
            });
            var nums = Object.keys(set).map(Number).sort(function (a, b) { return a - b; });
            var out = [], prev = 0;
            nums.forEach(function (n) {
                if (n - prev > 1) out.push('…');
                out.push(n);
                prev = n;
            });
            return out;
        }

        function btn(label, target, opts) {
            opts = opts || {};
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'cp-btn' + (opts.cls ? ' ' + opts.cls : '');
            b.innerHTML = label;
            if (opts.aria) b.setAttribute('aria-label', opts.aria);
            if (opts.current) b.setAttribute('aria-current', 'page');
            if (opts.disabled) b.disabled = true;
            else b.addEventListener('click', function () { go(target, true); });
            return b;
        }

        function renderNav() {
            nav.innerHTML = '';
            var from = (page - 1) * PER_PAGE + 1;
            var to = Math.min(page * PER_PAGE, cards.length);

            var info = document.createElement('div');
            info.className = 'cp-info';
            info.textContent = 'Showing ' + from + '–' + to + ' of ' + cards.length;
            nav.appendChild(info);

            var row = document.createElement('div');
            row.className = 'cp-row';
            row.appendChild(btn('&lsaquo; Prev', page - 1, { cls: 'cp-edge', aria: 'Previous page', disabled: page === 1 }));
            pageList().forEach(function (n) {
                if (n === '…') {
                    var gap = document.createElement('span');
                    gap.className = 'cp-gap';
                    gap.textContent = '…';
                    row.appendChild(gap);
                } else {
                    row.appendChild(btn(String(n), n, { current: n === page, aria: 'Page ' + n }));
                }
            });
            row.appendChild(btn('Next &rsaquo;', page + 1, { cls: 'cp-edge', aria: 'Next page', disabled: page === total }));
            nav.appendChild(row);
        }

        function go(n, scroll) {
            page = Math.max(1, Math.min(total, n));
            var start = (page - 1) * PER_PAGE, end = start + PER_PAGE;
            cards.forEach(function (card, i) {
                var show = i >= start && i < end;
                card.classList.toggle('cp-hidden', !show);
                if (show) {
                    // cards hidden before never intersected, so make sure scroll-reveal doesn't leave them invisible
                    card.classList.remove('rv-pending');
                    card.classList.add('rv-in');
                    card.classList.remove('cp-enter');
                    void card.offsetWidth;
                    card.classList.add('cp-enter');
                }
            });
            renderNav();
            if (scroll) {
                var holder = grid.closest('.cat-section') || grid;
                var top = holder.getBoundingClientRect().top;
                if (top < 90) {
                    window.scrollTo({ top: window.pageYOffset + top - 120, behavior: 'smooth' });
                }
            }
        }

        go(1, false);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();