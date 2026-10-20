/* ==========================================================================
   app.js — shared behaviour for every COPS page.
   The site works without JavaScript; this file only makes it smoother.
   Exposes window.COPS = { toast, confirm, post, esc, money, reduced }
   ========================================================================== */
(function () {
    'use strict';
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function money(n) { return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    /* ── toasts ── */
    var ICON = {
        ok: '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>',
        error: '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5M12 16.5h.01"/></svg>',
        info: '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>'
    };
    function toast(message, type, ms) {
        var host = document.getElementById('toasts');
        if (!host) { host = document.createElement('div'); host.id = 'toasts'; host.setAttribute('role', 'status'); host.setAttribute('aria-live', 'polite'); document.body.appendChild(host); }
        var t = document.createElement('div');
        t.className = 'toast ' + (type || 'info');
        t.innerHTML = (ICON[type] || ICON.info) + '<span>' + esc(message) + '</span>';
        host.appendChild(t);
        while (host.children.length > 3) host.removeChild(host.firstChild);
        setTimeout(function () { t.classList.add('out'); setTimeout(function () { t.remove(); }, 320); }, ms || (type === 'error' ? 5200 : 3400));
    }

    /* ── fetch helper: always JSON, never throws ── */
    function post(url, formData) {
        return fetch(url, { method: 'POST', body: formData, credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' } })
            .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'Unexpected response from the server.' }; }); })
            .catch(function () { return { ok: false, message: 'Network error. Check your connection and try again.' }; });
    }

    /* ── confirmation dialog (promise based, built on <dialog>) ── */
    var dlg = null;
    function ensureDialog() {
        if (dlg) return dlg;
        dlg = document.createElement('dialog');
        dlg.className = 'dlg'; dlg.setAttribute('aria-labelledby', 'cdTitle');
        dlg.innerHTML = '<form method="dialog" class="dlg-in"><h3 id="cdTitle"></h3><p class="msg" id="cdMsg"></p>' +
            '<div id="cdReasonWrap" hidden><label class="label" for="cdReason" id="cdReasonLabel">Reason</label>' +
            '<select class="select" id="cdReasonSel" style="margin-bottom:8px"></select>' +
            '<input class="input" id="cdReason" type="text" maxlength="160" placeholder="Add a short note (optional)"></div>' +
            '<div class="actions"><button class="btn btn-outline" value="cancel" id="cdNo">Keep it</button><button class="btn btn-danger" value="ok" id="cdYes">Confirm</button></div></form>';
        document.body.appendChild(dlg);
        dlg.querySelector('#cdReasonSel').addEventListener('change', function (e) { dlg.querySelector('#cdReason').value = e.target.value; });
        return dlg;
    }
    function confirmDialog(o) {
        return new Promise(function (resolve) {
            var d = ensureDialog();
            d.querySelector('#cdTitle').textContent = o.title || 'Are you sure?';
            d.querySelector('#cdMsg').textContent = o.message || '';
            d.querySelector('#cdYes').textContent = o.confirmLabel || 'Confirm';
            d.querySelector('#cdYes').className = 'btn ' + (o.danger === false ? 'btn-dark' : 'btn-danger');
            d.querySelector('#cdNo').textContent = o.cancelLabel || 'Keep it';
            var wrap = d.querySelector('#cdReasonWrap'), sel = d.querySelector('#cdReasonSel'), txt = d.querySelector('#cdReason');
            txt.value = '';
            if (o.reasons && o.reasons.length) {
                wrap.hidden = false;
                sel.innerHTML = '<option value="">Choose a reason…</option>' + o.reasons.map(function (r) { return '<option>' + esc(r) + '</option>'; }).join('');
            } else wrap.hidden = true;
            d.addEventListener('close', function onClose() {
                d.removeEventListener('close', onClose);
                resolve({ ok: d.returnValue === 'ok', reason: txt.value.trim() });
            });
            d.returnValue = 'cancel';
            d.showModal();
            d.querySelector('#cdNo').focus();
        });
    }

    /* ── upgrade <details class="confirm"> (works without JS as inline confirmation) ── */
    document.addEventListener('click', function (e) {
        var sum = e.target.closest('details.confirm > summary');
        if (!sum) return;
        var det = sum.parentNode, form = det.querySelector('form');
        if (!form || typeof HTMLDialogElement === 'undefined') return;
        e.preventDefault();
        var reasons = []; try { reasons = JSON.parse(det.dataset.reasons || '[]'); } catch (x) {}
        confirmDialog({ title: det.dataset.title, message: det.dataset.message, confirmLabel: det.dataset.confirmLabel, reasons: reasons })
            .then(function (r) {
                if (!r.ok) return;
                var field = form.querySelector('[name="reason"]'); if (field) field.value = r.reason;
                var submitter = form.querySelector('[type="submit"]');
                if (form.requestSubmit) form.requestSubmit(submitter); else form.submit();
            });
    });

    /* ── header + nav + reveal ── */
    function init() {
        var header = document.querySelector('.site-header');
        if (header) {
            var onScroll = function () { header.classList.toggle('scrolled', window.scrollY > 20); };
            window.addEventListener('scroll', onScroll, { passive: true }); onScroll();
            var tg = header.querySelector('.nav-toggle');
            if (tg) {
                tg.addEventListener('click', function () { var o = header.classList.toggle('nav-open'); tg.setAttribute('aria-expanded', o ? 'true' : 'false'); });
                document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && header.classList.contains('nav-open')) { header.classList.remove('nav-open'); tg.setAttribute('aria-expanded', 'false'); tg.focus(); } });
                header.querySelectorAll('.nav a').forEach(function (a) { a.addEventListener('click', function () { header.classList.remove('nav-open'); tg.setAttribute('aria-expanded', 'false'); }); });
            }
        }
        var rv = document.querySelectorAll('.reveal');
        if (reduced || !('IntersectionObserver' in window)) { rv.forEach(function (el) { el.classList.add('in'); }); return; }
        var io = new IntersectionObserver(function (es) {
            es.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
        }, { threshold: .12, rootMargin: '0px 0px -40px 0px' });
        rv.forEach(function (el, i) { el.style.transitionDelay = ((i % 4) * 70) + 'ms'; io.observe(el); });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();

    window.COPS = { toast: toast, confirm: confirmDialog, post: post, esc: esc, money: money, reduced: reduced };
})();