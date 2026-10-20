<?php
/**
 * admin-reservations.php — COPS staff console: student reservations
 *
 * Every request a student sends from menu.php appears here by itself (the page checks for
 * new ones every few seconds). Staff move each reservation through:
 *     Pending → Confirmed → Preparing → Ready → Completed       (or Rejected / Cancelled)
 * Rules (allowed moves, stock returning to the menu, the status log) live in
 * includes/reservations.php, shared with the student side.
 *
 * AJAX (same file):
 *   GET  ?action=list&date=YYYY-MM-DD|all
 *   POST action=status  id  status  expected  reason  csrf
 */
require_once __DIR__ . '/admin-bootstrap.php';

$action = (string)($_GET['action'] ?? $_POST['action'] ?? '');
$admin  = admin_require($adminConn, $action !== '');

/** Everything the page needs for one date, in the shape the JavaScript wants. */
function resv_payload(mysqli $c, array $f): array
{
    $out = [];
    foreach (staff_reservations($c, $f) as $r) {
        $hist = [];
        foreach ($r['history'] ?? [] as $h) {
            $hist[] = ['to' => $h['to_status'], 'who' => $h['actor_name'], 'type' => $h['actor_type'], 'reason' => $h['reason'], 'ts' => (int)$h['ts']];
        }
        $out[] = [
            'id' => (int)$r['id'], 'reference' => $r['reference'], 'status' => $r['status'], 'reason' => $r['status_reason'],
            'note' => $r['note'], 'total' => (float)$r['total'], 'pickup_date' => $r['pickup_date'],
            'slot' => $r['slot_label'], 'time' => $r['time_label'],
            'student' => $r['student_name'], 'student_ref' => $r['student_ref'], 'handled_by' => $r['handled_by_name'],
            'created_ts' => (int)$r['created_ts'], 'updated_ts' => (int)$r['updated_ts'],
            'items' => $r['items'], 'history' => $hist,
        ];
    }
    return [
        'ok' => true, 'server_ts' => time(), 'date' => $f['date'],
        'reservations' => $out, 'counts' => staff_counts($c, $f['date']), 'nav' => admin_nav_counts($c),
    ];
}

if ($action === 'list') {
    try {
        json_out(resv_payload($adminConn, staff_filters(['date' => $_GET['date'] ?? null])));
    } catch (Throwable $e) {
        app_log('admin-reservations list: ' . $e->getMessage());
        json_out(['ok' => false, 'error' => 'Could not load reservations.']);
    }
}

if ($action === 'status' && is_post()) {
    if (!admin_csrf_ok()) json_out(['ok' => false, 'error' => 'Session expired. Refresh the page.'], 419);
    $id       = (int)($_POST['id'] ?? 0);
    $to       = (string)($_POST['status'] ?? '');
    $expected = (string)($_POST['expected'] ?? '');
    $reason   = trim((string)($_POST['reason'] ?? ''));

    $res = res_transition($adminConn, $id, $to,
        ['type' => 'staff', 'id' => (int)$admin['id'], 'name' => $admin['full_name']],
        $expected !== '' ? $expected : null, $reason !== '' ? $reason : null);
    if (!$res['ok']) json_out(['ok' => false, 'error' => $res['error']]);

    adminLog($adminConn, (int)$admin['id'], 'reservation_' . $to, $res['reference'], $reason !== '' ? mb_substr($reason, 0, 160) : null);
    json_out(['ok' => true, 'reference' => $res['reference'], 'status' => $to]);
}

/* ── Page ─────────────────────────────────────────────────────────────────── */
$f    = staff_filters(['date' => $_GET['date'] ?? null]);
$init = ['ok' => false, 'reservations' => [], 'counts' => [], 'server_ts' => time(), 'date' => $f['date']];
$dbError = '';
try {
    $init = resv_payload($adminConn, $f);
} catch (Throwable $e) {
    app_log('admin-reservations first load: ' . $e->getMessage());
    $dbError = 'Reservations could not be loaded. Check that the database tables from the dump are imported.';
}
$initJs = json_encode($init, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

admin_head($admin, 'reservations', 'Reservations');
?>
    <?php if ($dbError !== ''): ?><div class="banner bad" role="alert"><div class="grow"><b>Problem:</b> <?= e($dbError) ?></div></div><?php endif; ?>

    <div class="page-hd">
        <div>
            <h1>Student reservations</h1>
            <p>New requests appear here on their own. Confirm, prepare, mark ready, then mark picked up.</p>
        </div>
        <div class="ctl">
            <input type="date" id="dateInput" value="<?= $f['date'] === 'all' ? '' : e($f['date']) ?>" aria-label="Pickup date">
            <button class="btn btn-ghost btn-sm" id="todayBtn" type="button">TODAY</button>
            <button class="btn btn-ghost btn-sm" id="allBtn" type="button">ALL DATES</button>
            <button class="icon-btn" id="soundBtn" type="button" title="Sound alert for new requests" aria-label="Toggle sound alert" aria-pressed="false" style="width:40px;height:40px;border:1px solid var(--line-strong);background:var(--paper);cursor:pointer;color:var(--muted)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
            </button>
        </div>
    </div>
    <div class="meander" aria-hidden="true"></div>

    <div id="earlier"></div>

    <div class="kpis" id="kpis"></div>

    <div class="tabs-row">
        <div class="chips" id="tabs" role="tablist"></div>
        <div class="ctl"><input type="search" id="q" placeholder="Search code, name, or student ID" aria-label="Search reservations" style="min-width:260px"></div>
    </div>

    <div class="grid" id="list"></div>

    <div class="modal" id="reasonModal" role="dialog" aria-modal="true" aria-labelledby="rmTitle">
        <div class="sheet">
            <h3 id="rmTitle">Reject this reservation?</h3>
            <p class="sub" id="rmSub">The student will see your reason. Reserved stock goes back to the menu.</p>
            <div class="reasons" id="reasons">
                <button type="button">Item sold out</button>
                <button type="button">Item unavailable today</button>
                <button type="button">Canteen is closed</button>
                <button type="button">Pickup time not possible</button>
            </div>
            <input type="text" id="reasonInput" maxlength="160" placeholder="Reason (optional)">
            <div class="row">
                <button class="btn btn-ghost btn-sm" type="button" id="rmCancel">KEEP IT</button>
                <button class="btn btn-sm" type="button" id="rmOk" style="background:var(--bad);border-color:var(--bad);color:#fff">CONFIRM</button>
            </div>
        </div>
    </div>
<?php
$js = 'var CSRF=' . json_encode($_SESSION['admin_csrf']) . ';var INIT=' . $initJs . ';' . <<<'JS'

(function () {
    var COPS = window.COPS, esc = COPS.esc, money = COPS.money;
    var LABEL = { pending: 'Pending', confirmed: 'Confirmed', preparing: 'Preparing', ready: 'Ready for pickup', completed: 'Completed', rejected: 'Rejected', cancelled: 'Cancelled' };
    var FLOW = {
        pending:   [['confirmed', 'CONFIRM', 'go'], ['rejected', 'REJECT', 'bad']],
        confirmed: [['preparing', 'START PREPARING', 'go'], ['cancelled', 'CANCEL', 'bad']],
        preparing: [['ready', 'MARK READY', 'go'], ['cancelled', 'CANCEL', 'bad']],
        ready:     [['completed', 'MARK PICKED UP', 'good'], ['cancelled', 'CANCEL', 'bad']]
    };
    var DONE = { confirmed: ' is confirmed', preparing: ' is being prepared', ready: ' is ready for pickup', completed: ' was picked up', rejected: ' was rejected', cancelled: ' was cancelled' };
    var TABS = [['all', 'ALL'], ['pending', 'PENDING'], ['confirmed', 'CONFIRMED'], ['preparing', 'PREPARING'], ['ready', 'READY'], ['completed', 'COMPLETED'], ['closed', 'CLOSED']];
    var state = { list: INIT.reservations || [], counts: INIT.counts || {}, filter: 'all', q: '', date: INIT.date, fetchedAt: Date.now(), serverTs: INIT.server_ts, seen: {}, first: true };
    var listEl = document.getElementById('list'), flash = {};

    /* ── sound alert ── */
    function store(k, v) { try { if (v === undefined) return localStorage.getItem(k); localStorage.setItem(k, v); } catch (e) { return null; } }
    var soundOn = store('cops_sound') === '1', actx = null, soundBtn = document.getElementById('soundBtn');
    function paintSound() { soundBtn.setAttribute('aria-pressed', soundOn ? 'true' : 'false'); soundBtn.style.color = soundOn ? 'var(--gold-deep)' : ''; soundBtn.style.borderColor = soundOn ? 'var(--gold)' : ''; }
    paintSound();
    function beep() {
        try {
            actx = actx || new (window.AudioContext || window.webkitAudioContext)();
            [660, 880].forEach(function (f, i) {
                var o = actx.createOscillator(), g = actx.createGain(), t = actx.currentTime + i * .16;
                o.type = 'sine'; o.frequency.value = f; g.gain.setValueAtTime(.0001, t); g.gain.exponentialRampToValueAtTime(.18, t + .02); g.gain.exponentialRampToValueAtTime(.0001, t + .3);
                o.connect(g); g.connect(actx.destination); o.start(t); o.stop(t + .32);
            });
        } catch (e) {}
    }
    soundBtn.addEventListener('click', function () {
        soundOn = !soundOn; store('cops_sound', soundOn ? '1' : '0'); paintSound();
        if (soundOn) { beep(); COPS.toast('Sound alert on', 'ok', 1600); } else COPS.toast('Sound alert off', '', 1600);
    });

    /* ── helpers ── */
    function ago(ts) {
        var now = state.serverTs + (Date.now() - state.fetchedAt) / 1000, d = Math.max(0, Math.round(now - ts));
        if (d < 60) return 'just now';
        if (d < 3600) return Math.floor(d / 60) + ' min ago';
        if (d < 86400) return Math.floor(d / 3600) + ' hr ago';
        return Math.floor(d / 86400) + ' d ago';
    }
    function initials(n) { var p = (n || '?').trim().split(/\s+/); return ((p[0] || '?')[0] + (p.length > 1 ? p[p.length - 1][0] : '')).toUpperCase(); }
    function fmtDate(s) { var d = new Date(s + 'T00:00:00'); return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' }); }
    function when(ts) { return new Date(ts * 1000).toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', timeZone: 'Asia/Manila' }); }
    function inTab(r, f) { return f === 'all' || (f === 'closed' ? (r.status === 'rejected' || r.status === 'cancelled') : r.status === f); }

    function actions(r) {
        var flow = FLOW[r.status];
        if (!flow) return r.handled_by ? '<span class="by">Handled by ' + esc(r.handled_by) + '</span>' : '';
        return flow.map(function (a) { return '<button class="act ' + a[2] + '" data-id="' + r.id + '" data-from="' + r.status + '" data-st="' + a[0] + '" type="button">' + a[1] + '</button>'; }).join('');
    }

    function card(r) {
        var items = r.items.map(function (i) { return '<li><span><span class="q">' + i.qty + '×</span>' + esc(i.name) + '</span><span class="p">' + money(i.subtotal) + '</span></li>'; }).join('');
        var note = r.note ? '<div class="note"><b>Student note:</b> “' + esc(r.note) + '”</div>' : '';
        var why = r.reason && (r.status === 'rejected' || r.status === 'cancelled') ? '<div class="note bad"><b>Reason:</b> ' + esc(r.reason) + '</div>' : '';
        var hist = r.history.length ? '<details class="hist"><summary>History (' + r.history.length + ')</summary><ol>' + r.history.map(function (h) {
            return '<li>' + esc(LABEL[h.to] || h.to) + ' · ' + esc(h.who) + (h.type === 'student' ? ' (student)' : '') + ' · ' + when(h.ts) + (h.reason ? ' — ' + esc(h.reason) : '') + '</li>';
        }).join('') + '</ol></details>' : '';
        return '<article class="res ' + r.status + (flash[r.id] ? ' flash' : '') + '">' +
            '<div class="res-top"><div><div class="code">' + esc(r.reference) + '</div><div class="ago">Requested ' + ago(r.created_ts) + '</div></div>' +
            '<span class="pill ' + r.status + '"><i></i>' + esc(LABEL[r.status] || r.status) + '</span></div>' +
            '<div class="who-row"><div class="av">' + esc(initials(r.student)) + '</div><div><div class="n">' + esc(r.student) + '</div><div class="m">' + esc(r.student_ref || 'No student ID') + '</div></div></div>' +
            '<div class="pick"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15.5 14"/></svg>' +
            '<div><div class="t">' + esc(r.slot) + ' · ' + esc(r.time) + '</div><div class="l">Pickup ' + esc(fmtDate(r.pickup_date)) + '</div></div></div>' +
            '<ul class="items">' + items + '</ul>' + note + why +
            '<div class="res-foot"><div class="total"><small>Pay at the counter</small>' + money(r.total) + '</div><div class="acts">' + actions(r) + '</div></div>' + hist + '</article>';
    }

    function render() {
        var c = { pending: 0, confirmed: 0, preparing: 0, ready: 0, completed: 0, closed: 0 }, expected = 0;
        state.list.forEach(function (r) {
            if (r.status === 'rejected' || r.status === 'cancelled') c.closed++; else { c[r.status]++; expected += r.total; }
        });
        var kp = [['pending', 'NEEDS ACTION', 'warn', c.pending], ['confirmed', 'CONFIRMED', 'blue', c.confirmed], ['preparing', 'PREPARING', 'purple', c.preparing], ['ready', 'READY', 'green', c.ready], ['completed', 'COMPLETED', 'navy', c.completed]];
        document.getElementById('kpis').innerHTML = kp.map(function (k) {
            return '<button class="kpi ' + k[2] + (state.filter === k[0] ? ' sel' : '') + '" data-s="' + k[0] + '" type="button"><div class="k">' + k[1] + '</div><div class="v">' + k[3] + '</div></button>';
        }).join('') + '<div class="kpi gold"><div class="k">EXPECTED TOTAL</div><div class="v">' + money(expected) + '</div><div class="sub">excluding rejected / cancelled</div></div>';

        document.getElementById('tabs').innerHTML = TABS.map(function (t) {
            var n = t[0] === 'all' ? state.list.length : (t[0] === 'closed' ? c.closed : c[t[0]]);
            return '<button class="chip' + (state.filter === t[0] ? ' active' : '') + '" data-f="' + t[0] + '" type="button" role="tab">' + t[1] + '<b>' + n + '</b></button>';
        }).join('');

        var e = state.counts.earlier_open || 0;
        document.getElementById('earlier').innerHTML = (e > 0 && state.date !== 'all')
            ? '<div class="banner"><div class="grow"><b>' + e + ' open reservation' + (e === 1 ? '' : 's') + ' from earlier days</b> still need attention. <a href="#" id="seeAll">Show all dates</a></div></div>' : '';

        document.title = (c.pending ? '(' + c.pending + ') ' : '') + 'Reservations — COPS Staff';
        var q = state.q.toLowerCase();
        var rows = state.list.filter(function (r) {
            if (!inTab(r, state.filter)) return false;
            return !q || (r.reference + ' ' + r.student + ' ' + (r.student_ref || '')).toLowerCase().indexOf(q) !== -1;
        });
        listEl.innerHTML = rows.length ? rows.map(card).join('')
            : '<div class="empty"><h3>' + (state.list.length ? 'Nothing matches that filter' : 'No reservations for this date yet') + '</h3><p>' + (state.list.length ? 'Try another tab or clear the search.' : 'When a student sends a request, it shows up here automatically.') + '</p></div>';
        flash = {};
    }

    function setBadge(id, n) { var b = document.getElementById(id); if (!b) return; b.textContent = n; b.hidden = !(n > 0); }
    function setLive(ok) { var l = document.getElementById('live'); l.classList.toggle('off', !ok); l.querySelector('span').textContent = ok ? 'LIVE' : 'OFFLINE'; }

    function apply(d) {
        var fresh = [];
        d.reservations.forEach(function (r) {
            var prev = state.seen[r.id];
            if (!state.first) {
                if (!prev) { flash[r.id] = true; if (r.status === 'pending') fresh.push(r); }
                else if (prev !== r.status) flash[r.id] = true;
            }
            state.seen[r.id] = r.status;
        });
        state.list = d.reservations; state.counts = d.counts; state.serverTs = d.server_ts; state.fetchedAt = Date.now(); state.first = false;
        if (d.nav) { setBadge('nbRes', d.nav.pending); setBadge('nbInv', d.nav.low); }
        render();
        if (fresh.length) {
            COPS.toast(fresh.length === 1 ? 'New request from ' + fresh[0].student : fresh.length + ' new reservation requests', 'ok', 4200);
            if (soundOn) beep();
        }
    }

    function load() {
        fetch('admin-reservations.php?action=list&date=' + encodeURIComponent(state.date), { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.login) { location.href = 'admin-login.php'; return; }
                if (!d.ok) { setLive(false); return; }
                setLive(true); apply(d);
            }).catch(function () { setLive(false); });
    }

    /* first paint comes from the server; later updates arrive by polling */
    state.list.forEach(function (r) { state.seen[r.id] = r.status; });
    state.first = false; render();
    setInterval(load, 6000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) load(); });
    setInterval(render, 60000);

    /* ── filters ── */
    document.addEventListener('click', function (e) {
        var t = e.target.closest('#tabs .chip'); if (t) { state.filter = t.dataset.f; render(); return; }
        var k = e.target.closest('#kpis button.kpi'); if (k) { state.filter = state.filter === k.dataset.s ? 'all' : k.dataset.s; render(); return; }
        if (e.target.id === 'seeAll') { e.preventDefault(); setDate('all'); }
    });
    document.getElementById('q').addEventListener('input', function (e) { state.q = e.target.value.trim(); render(); });
    function setDate(v) {
        state.date = v; state.seen = {}; state.first = true;
        document.getElementById('dateInput').value = v === 'all' ? '' : v;
        load();
    }
    document.getElementById('dateInput').addEventListener('change', function (e) { if (e.target.value) setDate(e.target.value); });
    document.getElementById('allBtn').addEventListener('click', function () { setDate('all'); });
    document.getElementById('todayBtn').addEventListener('click', function () {
        var d = new Date(), p = function (n) { return String(n).padStart(2, '0'); };
        setDate(d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()));
    });

    /* ── status changes ── */
    function send(id, from, status, reason, btn) {
        var fd = new FormData();
        fd.append('action', 'status'); fd.append('csrf', CSRF); fd.append('id', id); fd.append('status', status); fd.append('expected', from); fd.append('reason', reason || '');
        if (btn) btn.disabled = true;
        fetch('admin-reservations.php', { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.login) { location.href = 'admin-login.php'; return; }
                if (!d.ok) { COPS.toast(d.error || 'Could not update.', 'error', 4500); if (btn) btn.disabled = false; load(); return; }
                COPS.toast(d.reference + (DONE[status] || ' updated'), 'ok', 2600);
                flash[id] = true; load();
            })
            .catch(function () { COPS.toast('Network error. Try again.', 'error'); if (btn) btn.disabled = false; });
    }

    var pending = null, modal = document.getElementById('reasonModal'), reasonInput = document.getElementById('reasonInput');
    function closeModal() { modal.classList.remove('show'); pending = null; }
    listEl.addEventListener('click', function (e) {
        var b = e.target.closest('.act'); if (!b) return;
        var st = b.dataset.st;
        if (st === 'rejected' || st === 'cancelled') {
            pending = { id: b.dataset.id, from: b.dataset.from, st: st, btn: b };
            document.getElementById('rmTitle').textContent = st === 'rejected' ? 'Reject this reservation?' : 'Cancel this reservation?';
            reasonInput.value = '';
            document.querySelectorAll('#reasons button').forEach(function (x) { x.classList.remove('sel'); });
            modal.classList.add('show'); setTimeout(function () { reasonInput.focus(); }, 60);
        } else send(b.dataset.id, b.dataset.from, st, '', b);
    });
    document.getElementById('reasons').addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) return;
        document.querySelectorAll('#reasons button').forEach(function (x) { x.classList.remove('sel'); });
        b.classList.add('sel'); reasonInput.value = b.textContent;
    });
    document.getElementById('rmCancel').addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
    document.getElementById('rmOk').addEventListener('click', function () {
        if (!pending) return;
        var p = pending; closeModal(); send(p.id, p.from, p.st, reasonInput.value.trim(), p.btn);
    });
})();
JS;
admin_foot($js);