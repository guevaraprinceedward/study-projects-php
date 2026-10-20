/* ==========================================================================
   menu.js — menu page behaviour: reservation panel, add-to-cart without reload,
   live stock + reservation status updates.
   Needs window.COPS from app.js. The page works without this file.
   ========================================================================== */
(function () {
    'use strict';
    var C = window.COPS;
    var drawer = document.getElementById('drawer');
    var veil = document.getElementById('veil');
    var box = document.getElementById('drawerPanel');
    var title = document.getElementById('drawerTitle');
    if (!C || !drawer || !box || !veil) return;

    var wrap = document.querySelector('.menu-wrap');
    var MAX = (wrap && +wrap.getAttribute('data-max-qty')) || 10;
    var cartCount = document.getElementById('cartCount');
    var ordersBtn = document.getElementById('ordersBtn');
    var loggedIn = document.body.getAttribute('data-logged-in') === '1';
    var currentView = '', lastFocus = null, lastOrders = null, seenReady = {};

    function $(s, r) { return (r || document).querySelector(s); }
    function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }
    function isOpen() { return drawer.classList.contains('is-open'); }

    function getJSON(url) {
        return fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .catch(function () { return { ok: false, message: 'Network error. Check your connection and try again.' }; });
    }

    function setCount(n) {
        if (!cartCount) return;
        cartCount.textContent = n; cartCount.setAttribute('data-n', n);
    }
    function bump() {
        var b = document.getElementById('cartBtn');
        if (!b || C.reduced) return;
        b.classList.remove('bump'); void b.offsetWidth; b.classList.add('bump');
    }

    /* ── panel ── */
    function afterPanel() {
        var p = box.firstElementChild;
        currentView = p ? (p.getAttribute('data-view') || '') : '';
        if (p && title) title.textContent = p.getAttribute('data-title') || '';
        $$('.drawer-tabs a').forEach(function (a) {
            var v = a.getAttribute('data-panel-link');
            var on = (v === 'cart' && (currentView === 'cart' || currentView === 'checkout')) ||
                     (v === 'orders' && (currentView === 'orders' || currentView === 'done'));
            a.setAttribute('aria-current', on ? 'true' : 'false');
        });
        var body = $('.drawer-body', box); if (body) body.scrollTop = 0;
        if (currentView === 'orders' || currentView === 'done') markSeen();
    }
    function setPanel(html) { box.innerHTML = html; afterPanel(); }

    function open() {
        if (isOpen()) return;
        lastFocus = document.activeElement;
        drawer.classList.add('is-open'); veil.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        document.body.classList.add('no-scroll');
        var x = $('#drawerClose'); if (x) x.focus();
    }
    function cleanUrl() {
        var u = new URL(location.href);
        u.searchParams.delete('view'); u.searchParams.delete('ref');
        return u.pathname + u.search + u.hash;
    }
    function close() {
        drawer.classList.remove('is-open', 'static'); veil.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('no-scroll');
        if (history.replaceState && /[?&]view=/.test(location.search)) history.replaceState(null, '', cleanUrl());
        if (lastFocus && lastFocus.focus) lastFocus.focus();
    }

    function showView(view, ref, quiet) {
        if (!quiet) open();
        var url = 'menu.php?view=' + encodeURIComponent(view) + (ref ? '&ref=' + encodeURIComponent(ref) : '');
        getJSON(url).then(function (d) {
            if (d && d.html) setPanel(d.html);
            if (d && typeof d.count === 'number') setCount(d.count);
            if (d && !d.ok && d.message) C.toast(d.message, 'error');
        });
    }

    /* ── stock on the cards ── */
    function applyStock(map) {
        $$('.product-card[data-id]').forEach(function (c) {
            var id = c.getAttribute('data-id');
            if (map[id] === undefined) return;
            var s = +map[id], re = +c.getAttribute('data-reorder'), out = s <= 0, low = !out && s <= re;
            c.setAttribute('data-stock', s);
            c.classList.toggle('is-soldout', out);
            var st = $('.pc-stock', c);
            if (st) { st.className = 'pc-stock' + (out ? ' out' : low ? ' low' : ''); st.textContent = out ? 'Sold out' : low ? 'Only ' + s + ' left' : s + ' available'; }
            var bd = $('.pv-badge', c);
            if (bd) { bd.hidden = !(out || low); bd.className = 'pv-badge' + (out ? ' out' : ''); bd.textContent = out ? 'Sold out' : 'Few left'; }
            var qi = $('.qty input', c);
            if (qi) { var mx = Math.max(1, Math.min(MAX, s)); qi.max = mx; if (+qi.value > mx) qi.value = mx; }
            $$('.pc-buy button, .pc-buy input', c).forEach(function (b) { b.disabled = out; });
            var add = $('.pc-buy [type=submit]', c);
            if (add) add.textContent = out ? 'Sold out' : 'Add to cart';
        });
    }

    /* ── reservation status watching ── */
    var MSG = { confirmed: 'was confirmed by the staff.', preparing: 'is being prepared.', ready: 'is ready for pickup!', rejected: 'could not be accepted.', cancelled: 'was cancelled.' };
    function paintDot() {
        if (!ordersBtn) return;
        var dot = $('.dot', ordersBtn); if (!dot) return;
        var has = false;
        if (lastOrders) Object.keys(lastOrders).forEach(function (id) { if (lastOrders[id].status === 'ready' && !seenReady[id]) has = true; });
        dot.hidden = !has;
    }
    function markSeen() {
        if (lastOrders) Object.keys(lastOrders).forEach(function (id) { if (lastOrders[id].status === 'ready') seenReady[id] = 1; });
        paintDot();
    }
    function checkOrders(map) {
        var changed = false;
        if (lastOrders) {
            Object.keys(map).forEach(function (id) {
                var prev = lastOrders[id], cur = map[id];
                if (prev && prev.status !== cur.status) {
                    changed = true;
                    if (MSG[cur.status]) C.toast(cur.ref + ' ' + MSG[cur.status], cur.status === 'rejected' || cur.status === 'cancelled' ? 'error' : 'ok', 6000);
                }
            });
        }
        lastOrders = map;
        if (isOpen() && (currentView === 'orders' || currentView === 'done')) markSeen(); else paintDot();
        if (changed && isOpen() && currentView === 'orders') showView('orders', null, true);
    }
    function poll() {
        getJSON('menu.php?action=state').then(function (d) {
            if (!d || !d.ok) return;
            if (d.stock) applyStock(d.stock);
            if (typeof d.count === 'number') setCount(d.count);
            if (d.loggedIn) checkOrders(d.orders || {});
        });
    }

    /* ── results of a form post ── */
    function handle(d, action) {
        if (d.login) { location.href = 'log-in.php?next=' + encodeURIComponent(d.next || 'menu.php'); return; }
        if (d.stock) applyStock(d.stock);
        if (typeof d.count === 'number') setCount(d.count);
        if (d.orders) { lastOrders = d.orders; paintDot(); }
        if (d.html) { setPanel(d.html); if (!isOpen()) open(); }
        if (d.message && !(d.html && !d.ok)) C.toast(d.message, d.ok ? 'ok' : 'error');
        if (d.ok && action === 'add') bump();
    }

    /* ── events ── */
    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[data-panel-link]');
        if (link && !(e.metaKey || e.ctrlKey || e.shiftKey || e.button)) {
            e.preventDefault();
            var u = new URL(link.href, location.href);
            showView(link.getAttribute('data-panel-link'), u.searchParams.get('ref'));
            return;
        }
        if (e.target.closest('[data-close-panel]')) { e.preventDefault(); close(); return; }
        if (e.target.closest('#drawerClose')) { e.preventDefault(); close(); return; }
        if (e.target === veil) { close(); return; }

        var step = e.target.closest('button[data-step]');
        if (step) {
            var input = $('input', step.parentNode); if (!input) return;
            var min = +input.min || 1, max = +input.max || MAX;
            input.value = Math.max(min, Math.min(max, (+input.value || 1) + (+step.getAttribute('data-step'))));
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen() && !document.querySelector('dialog[open]')) close();
    });

    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (f.matches && f.matches('form[data-panel-form]')) {
            e.preventDefault();
            showView('orders', (new FormData(f)).get('ref'));
            return;
        }
        if (!f.matches || !f.matches('form[data-enhance]')) return;
        e.preventDefault();
        var sub = e.submitter || f.querySelector('[type=submit]');
        var fd = new FormData(f);
        if (e.submitter && e.submitter.name) fd.append(e.submitter.name, e.submitter.value);
        var action = fd.get('do');
        if (sub) sub.disabled = true;
        C.post('menu.php', fd).then(function (d) {
            if (sub && document.body.contains(sub)) sub.disabled = false;
            handle(d, action);
        });
    });

    /* ── start ── */
    if (isOpen()) {                       // panel was opened by the server (?view=…)
        document.body.classList.add('no-scroll');
        afterPanel();
        setTimeout(function () { drawer.classList.remove('static'); }, 60);
    } else {
        afterPanel();
    }
    poll();
    setInterval(poll, 12000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
})();