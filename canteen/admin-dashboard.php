<?php
/**
 * admin-dashboard.php — COPS staff console: sales, analytics and daily transactions
 *
 * A SALE is a reservation the student has picked up (status "completed"), because students pay
 * at the counter on pickup. Requests that are still open are shown separately as "expected".
 * Rejected and cancelled requests never count as sales.
 *
 *   GET ?action=data&range=today|yesterday|7d|30d|month|custom&from=&to=&tx=completed|all   → JSON
 *   GET ?export=csv&range=...                                                                → CSV download
 */
require_once __DIR__ . '/admin-bootstrap.php';

$action = (string)($_GET['action'] ?? '');
$admin  = admin_require($adminConn, $action !== '');

function dash_valid_date($d): ?string
{
    return (is_string($d) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1])) ? $d : null;
}

function dash_range(array $in): array
{
    $today = date('Y-m-d'); $range = (string)($in['range'] ?? '7d'); $to = $today;
    switch ($range) {
        case 'today':     $from = $today; break;
        case 'yesterday': $from = $to = date('Y-m-d', strtotime('-1 day')); break;
        case '30d':       $from = date('Y-m-d', strtotime('-29 days')); break;
        case 'month':     $from = date('Y-m-01'); break;
        case 'custom':
            $from = dash_valid_date($in['from'] ?? null) ?? date('Y-m-d', strtotime('-6 days'));
            $to   = dash_valid_date($in['to'] ?? null) ?? $today;
            break;
        default: $range = '7d'; $from = date('Y-m-d', strtotime('-6 days'));
    }
    if ($from > $to) { [$from, $to] = [$to, $from]; }
    if (strtotime($to) - strtotime($from) > 365 * 86400) $from = date('Y-m-d', strtotime($to . ' -365 days'));
    $label = $from === $to ? date('M j, Y', strtotime($from)) : date('M j', strtotime($from)) . ' – ' . date('M j, Y', strtotime($to));
    return ['range' => $range, 'from' => $from, 'to' => $to, 'label' => $label];
}

function dash_pct(float $cur, float $prev): ?int
{
    return $prev > 0 ? (int)round(($cur - $prev) / $prev * 100) : null;
}

function dash_ledger(mysqli $c, string $from, string $to, string $tx): array
{
    $sql = RES_SELECT . " WHERE r.pickup_date BETWEEN ? AND ?" . ($tx === 'completed' ? " AND r.status = 'completed'" : '')
         . " ORDER BY r.pickup_date DESC, r.updated_at DESC, r.id DESC LIMIT 200";
    $out = [];
    foreach (res_attach_items($c, db_all($c, $sql, 'ss', [$from, $to])) as $r) {
        $out[] = ['id' => $r['id'], 'reference' => $r['reference'], 'status' => $r['status'], 'pickup_date' => $r['pickup_date'],
                  'slot' => $r['slot_label'], 'time' => $r['time_label'], 'student' => $r['student_name'], 'student_ref' => $r['student_ref'],
                  'total' => $r['total'], 'items' => $r['items'], 'handled_by' => $r['handled_by_name'], 'updated_ts' => $r['updated_ts']];
    }
    return $out;
}

function dash_data(mysqli $c, array $rg, string $tx): array
{
    $from = $rg['from']; $to = $rg['to'];

    // one entry per day, so quiet days show as zero instead of disappearing
    $days = [];
    for ($x = new DateTimeImmutable($from), $end = new DateTimeImmutable($to); $x <= $end; $x = $x->modify('+1 day')) {
        $days[$x->format('Y-m-d')] = ['date' => $x->format('Y-m-d'), 'label' => $x->format('M j'), 'dow' => $x->format('D'),
            'sales' => 0.0, 'orders' => 0, 'units' => 0, 'requests' => 0, 'closed' => 0, 'open' => 0, 'open_amt' => 0.0];
    }

    $status = [];
    foreach (db_all($c, "SELECT pickup_date AS d, status, COUNT(*) AS n, COALESCE(SUM(total_amount),0) AS amt
                         FROM reservations WHERE pickup_date BETWEEN ? AND ? GROUP BY pickup_date, status", 'ss', [$from, $to]) as $r) {
        $k = $r['d']; if (!isset($days[$k])) continue;
        $n = (int)$r['n']; $amt = (float)$r['amt']; $s = $r['status'];
        $status[$s] = ($status[$s] ?? 0) + $n;
        $days[$k]['requests'] += $n;
        if ($s === 'completed') { $days[$k]['sales'] += $amt; $days[$k]['orders'] += $n; }
        elseif ($s === 'rejected' || $s === 'cancelled') { $days[$k]['closed'] += $n; }
        else { $days[$k]['open'] += $n; $days[$k]['open_amt'] += $amt; }
    }
    foreach (db_all($c, "SELECT r.pickup_date AS d, SUM(ri.quantity) AS u FROM reservations r JOIN reservation_items ri ON ri.reservation_id = r.id
                         WHERE r.status = 'completed' AND r.pickup_date BETWEEN ? AND ? GROUP BY r.pickup_date", 'ss', [$from, $to]) as $r) {
        if (isset($days[$r['d']])) $days[$r['d']]['units'] = (int)$r['u'];
    }

    $sales = 0.0; $orders = 0; $units = 0; $openAmt = 0.0; $openN = 0; $closed = 0; $requests = 0;
    foreach ($days as $d) {
        $sales += $d['sales']; $orders += $d['orders']; $units += $d['units'];
        $openAmt += $d['open_amt']; $openN += $d['open']; $closed += $d['closed']; $requests += $d['requests'];
    }

    $len   = count($days);
    $prev  = db_one($c, "SELECT COUNT(*) AS n, COALESCE(SUM(total_amount),0) AS amt FROM reservations WHERE status = 'completed' AND pickup_date BETWEEN ? AND ?",
                    'ss', [date('Y-m-d', strtotime($from . " -{$len} days")), date('Y-m-d', strtotime($from . ' -1 day'))]);
    $cust  = db_one($c, "SELECT COUNT(DISTINCT user_id) AS n FROM reservations WHERE status = 'completed' AND pickup_date BETWEEN ? AND ?", 'ss', [$from, $to]);

    $top = [];
    foreach (db_all($c, "SELECT ri.product_name AS name, SUM(ri.quantity) AS u, SUM(ri.line_subtotal) AS amt
                         FROM reservation_items ri JOIN reservations r ON r.id = ri.reservation_id
                         WHERE r.status = 'completed' AND r.pickup_date BETWEEN ? AND ?
                         GROUP BY ri.product_name ORDER BY u DESC, amt DESC LIMIT 8", 'ss', [$from, $to]) as $r) {
        $top[] = ['name' => $r['name'], 'units' => (int)$r['u'], 'sales' => (float)$r['amt']];
    }
    $cats = [];
    foreach (db_all($c, "SELECT COALESCE(p.category, 'other') AS cat, SUM(ri.quantity) AS u, SUM(ri.line_subtotal) AS amt
                         FROM reservation_items ri JOIN reservations r ON r.id = ri.reservation_id LEFT JOIN products p ON p.id = ri.product_id
                         WHERE r.status = 'completed' AND r.pickup_date BETWEEN ? AND ?
                         GROUP BY cat ORDER BY amt DESC", 'ss', [$from, $to]) as $r) {
        $cats[] = ['name' => admin_category_label($r['cat']), 'units' => (int)$r['u'], 'sales' => (float)$r['amt']];
    }
    $slots = [];
    foreach (db_all($c, "SELECT s.label, COUNT(*) AS n, COALESCE(SUM(r.total_amount),0) AS amt
                         FROM reservations r JOIN pickup_slots s ON s.id = r.pickup_slot_id
                         WHERE r.status = 'completed' AND r.pickup_date BETWEEN ? AND ?
                         GROUP BY s.id, s.label, s.sort_order, s.start_time ORDER BY s.sort_order, s.start_time", 'ss', [$from, $to]) as $r) {
        $slots[] = ['name' => $r['label'], 'orders' => (int)$r['n'], 'sales' => (float)$r['amt']];
    }

    $daily = array_values($days);
    return [
        'ok' => true, 'server_ts' => time(), 'range' => $rg['range'], 'from' => $from, 'to' => $to, 'label' => $rg['label'], 'tx' => $tx,
        'kpi' => [
            'sales' => round($sales, 2), 'orders' => $orders, 'avg' => $orders ? round($sales / $orders, 2) : 0, 'units' => $units,
            'open_amt' => round($openAmt, 2), 'open_n' => $openN, 'closed' => $closed, 'requests' => $requests,
            'cancel_rate' => $requests ? (int)round($closed / $requests * 100) : 0, 'customers' => (int)($cust['n'] ?? 0),
            'sales_delta' => dash_pct($sales, (float)($prev['amt'] ?? 0)), 'orders_delta' => dash_pct((float)$orders, (float)($prev['n'] ?? 0)),
        ],
        'daily' => $daily, 'status' => $status, 'top' => $top, 'cats' => $cats, 'slots' => $slots,
        'ledger' => dash_ledger($c, $from, $to, $tx), 'nav' => admin_nav_counts($c),
    ];
}

function csv_cell($v): string
{
    $v = (string)$v;
    return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;   // stops spreadsheet formula injection
}

$rg = dash_range($_GET);
$tx = ($_GET['tx'] ?? 'completed') === 'all' ? 'all' : 'completed';

if (($_GET['export'] ?? '') === 'csv') {
    $rows = dash_ledger($adminConn, $rg['from'], $rg['to'], 'all');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cops-transactions-' . $rg['from'] . '_to_' . $rg['to'] . '.csv"');
    echo "\xEF\xBB\xBF";
    $o = fopen('php://output', 'w');
    fputcsv($o, ['Reference', 'Pickup date', 'Pickup slot', 'Student', 'Student ID', 'Items', 'Total (PHP)', 'Status', 'Handled by']);
    foreach ($rows as $r) {
        $items = implode('; ', array_map(fn($i) => $i['qty'] . 'x ' . $i['name'], $r['items']));
        fputcsv($o, array_map('csv_cell', [$r['reference'], $r['pickup_date'], $r['slot'] . ' ' . $r['time'], $r['student'], $r['student_ref'], $items, number_format((float)$r['total'], 2, '.', ''), $r['status'], $r['handled_by']]));
    }
    fclose($o);
    exit;
}

if ($action === 'data') {
    try {
        json_out(dash_data($adminConn, $rg, $tx));
    } catch (Throwable $e) {
        app_log('admin-dashboard data: ' . $e->getMessage());
        json_out(['ok' => false, 'error' => 'Could not load the dashboard.']);
    }
}

$init = null; $dbError = '';
try {
    $init = dash_data($adminConn, $rg, $tx);
} catch (Throwable $e) {
    app_log('admin-dashboard first load: ' . $e->getMessage());
    $dbError = 'The dashboard could not be loaded. Check that the database tables from the dump are imported.';
}
$initJs = json_encode($init, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

admin_head($admin, 'dashboard', 'Dashboard');
?>
    <?php if ($dbError !== ''): ?><div class="banner bad" role="alert"><div class="grow"><b>Problem:</b> <?= e($dbError) ?></div></div><?php endif; ?>

    <div class="page-hd">
        <div>
            <h1>Sales dashboard</h1>
            <p id="rangeLabel"><?= e($rg['label']) ?></p>
        </div>
        <div class="ctl">
            <div class="chips" id="ranges">
                <button class="chip" data-r="today" type="button">TODAY</button>
                <button class="chip" data-r="yesterday" type="button">YESTERDAY</button>
                <button class="chip" data-r="7d" type="button">7 DAYS</button>
                <button class="chip" data-r="30d" type="button">30 DAYS</button>
                <button class="chip" data-r="month" type="button">THIS MONTH</button>
            </div>
            <input type="date" id="fromInput" aria-label="From date" value="<?= e($rg['from']) ?>">
            <span style="color:var(--muted)">to</span>
            <input type="date" id="toInput" aria-label="To date" value="<?= e($rg['to']) ?>">
            <button class="btn btn-ghost btn-sm" id="applyBtn" type="button">APPLY</button>
        </div>
    </div>
    <div class="meander" aria-hidden="true"></div>

    <div class="kpis" id="kpis"></div>

    <div class="grid2">
        <div class="panel">
            <h2>DAILY SALES</h2>
            <p class="hint">Completed (picked-up) reservations only.</p>
            <div class="chart" id="chart"></div>
            <div class="best" id="best"></div>
        </div>
        <div class="panel">
            <h2>REQUEST OUTCOMES</h2>
            <p class="hint">What happened to every request in this period.</p>
            <div id="donut"></div>
        </div>
    </div>

    <div class="grid3">
        <div class="panel"><h2>TOP PRODUCTS</h2><p class="hint">By units sold.</p><ul class="bl" id="topList"></ul></div>
        <div class="panel"><h2>SALES BY CATEGORY</h2><p class="hint">Which part of the menu earns the most.</p><ul class="bl" id="catList"></ul></div>
        <div class="panel"><h2>BUSIEST PICKUP TIMES</h2><p class="hint">Completed orders per pickup slot.</p><ul class="bl" id="slotList"></ul></div>
    </div>

    <div class="panel">
        <div class="panel-hd"><div><h2>DAILY TRANSACTIONS</h2><p class="hint">One line per day.</p></div></div>
        <div class="tbl-wrap"><table class="tbl" id="dayTbl"></table></div>
    </div>

    <div class="panel">
        <div class="panel-hd">
            <div><h2>TRANSACTION LEDGER</h2><p class="hint" id="ledgerHint">Latest 200 in this period.</p></div>
            <div class="ctl">
                <div class="chips" id="txChips">
                    <button class="chip" data-tx="completed" type="button">SALES ONLY</button>
                    <button class="chip" data-tx="all" type="button">ALL REQUESTS</button>
                </div>
                <a class="btn btn-ghost btn-sm" id="csvBtn" href="#">EXPORT CSV</a>
            </div>
        </div>
        <div class="tbl-wrap"><table class="tbl" id="ledgerTbl"></table></div>
    </div>
<?php
$js = 'var INIT=' . $initJs . ';' . <<<'JS'

(function () {
    var COPS = window.COPS, esc = COPS.esc, money = COPS.money;
    var state = { d: INIT, range: INIT ? INIT.range : '7d', from: INIT ? INIT.from : '', to: INIT ? INIT.to : '', tx: INIT ? INIT.tx : 'completed' };
    var COLORS = { completed: '#2f7d4f', ready: '#3aa17e', preparing: '#6b4a9a', confirmed: '#2c5aa0', pending: '#b7791f', rejected: '#a63a3a', cancelled: '#c98f8f' };
    var LABEL = { completed: 'Completed', ready: 'Ready', preparing: 'Preparing', confirmed: 'Confirmed', pending: 'Pending', rejected: 'Rejected', cancelled: 'Cancelled' };
    function $(id) { return document.getElementById(id); }
    function fmtDate(s) { return new Date(s + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }); }
    function query() { return state.range === 'custom' ? 'range=custom&from=' + state.from + '&to=' + state.to : 'range=' + state.range; }

    function delta(p) {
        if (p === null || p === undefined) return '<span class="sub">no earlier data to compare</span>';
        return '<span class="' + (p >= 0 ? 'up' : 'down') + '">' + (p >= 0 ? '▲ ' : '▼ ') + Math.abs(p) + '%</span> <span class="sub">vs previous period</span>';
    }

    function renderKpis(k) {
        var cards = [
            ['gold', 'TOTAL SALES', money(k.sales), delta(k.sales_delta)],
            ['green', 'TRANSACTIONS', k.orders, delta(k.orders_delta)],
            ['navy', 'AVERAGE ORDER', money(k.avg), '<span class="sub">' + k.customers + ' student' + (k.customers === 1 ? '' : 's') + ' served</span>'],
            ['blue', 'UNITS SOLD', k.units, '<span class="sub">items picked up</span>'],
            ['purple', 'EXPECTED (OPEN)', money(k.open_amt), '<span class="sub">' + k.open_n + ' not yet picked up</span>'],
            ['red', 'CANCEL / REJECT', k.cancel_rate + '%', '<span class="sub">' + k.closed + ' of ' + k.requests + ' requests</span>']
        ];
        $('kpis').innerHTML = cards.map(function (c) { return '<div class="kpi ' + c[0] + '"><div class="k">' + c[1] + '</div><div class="v">' + c[2] + '</div><div>' + c[3] + '</div></div>'; }).join('');
    }

    function niceStep(x) { var p = Math.pow(10, Math.floor(Math.log10(x))), f = x / p; return (f <= 1 ? 1 : f <= 2 ? 2 : f <= 5 ? 5 : 10) * p; }
    function renderChart(daily) {
        var host = $('chart'), best = null;
        daily.forEach(function (d) { if (!best || d.sales > best.sales) best = d; });
        if (!best || best.sales <= 0) { host.innerHTML = '<div class="empty-chart">No completed sales in this period yet.</div>'; $('best').innerHTML = ''; return; }
        var W = 780, H = 280, L = 56, R = 14, T = 16, B = 40, n = daily.length;
        var step = niceStep(best.sales / 4), top = Math.ceil(best.sales / step) * step, iw = W - L - R, ih = H - T - B, bw = iw / n, w = Math.min(46, bw * .62);
        var s = '<svg viewBox="0 0 ' + W + ' ' + H + '" role="img" aria-label="Daily sales"><defs><linearGradient id="dbar" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#e2c06c"/><stop offset="1" stop-color="#b8903a"/></linearGradient></defs>';
        for (var i = 0, steps = Math.round(top / step); i <= steps; i++) {
            var v = Math.round(i * step * 100) / 100, y = T + ih - (v / top) * ih;
            s += '<line x1="' + L + '" x2="' + (W - R) + '" y1="' + y + '" y2="' + y + '" class="gl"/><text x="' + (L - 8) + '" y="' + (y + 4) + '" text-anchor="end" class="ax">' + (v >= 1000 ? (v / 1000) + 'k' : v) + '</text>';
        }
        var every = Math.ceil(n / 10);
        daily.forEach(function (d, i) {
            var h = (d.sales / top) * ih, x = L + i * bw + (bw - w) / 2, y = T + ih - h;
            s += '<rect x="' + x + '" y="' + y + '" width="' + w + '" height="' + Math.max(h, d.sales > 0 ? 2 : 0) + '" rx="2" fill="url(#dbar)"><title>' + esc(d.label) + ': ' + money(d.sales) + ' · ' + d.orders + ' order' + (d.orders === 1 ? '' : 's') + '</title></rect>';
            if (i % every === 0) s += '<text x="' + (L + i * bw + bw / 2) + '" y="' + (H - 20) + '" text-anchor="middle" class="ax">' + esc(d.label) + '</text><text x="' + (L + i * bw + bw / 2) + '" y="' + (H - 6) + '" text-anchor="middle" class="ax">' + esc(d.dow) + '</text>';
        });
        host.innerHTML = s + '</svg>';
        $('best').innerHTML = 'Best day: <b>' + esc(best.label) + '</b> with ' + money(best.sales) + ' from ' + best.orders + ' order' + (best.orders === 1 ? '' : 's') + '.';
    }

    function renderDonut(st) {
        var order = ['completed', 'ready', 'preparing', 'confirmed', 'pending', 'rejected', 'cancelled'], total = 0;
        order.forEach(function (k) { total += st[k] || 0; });
        if (!total) { $('donut').innerHTML = '<div class="empty-note">No requests in this period.</div>'; return; }
        var acc = 0, stops = [], legend = '';
        order.forEach(function (k) {
            var n = st[k] || 0; if (!n) return;
            stops.push(COLORS[k] + ' ' + (acc / total * 360) + 'deg ' + ((acc + n) / total * 360) + 'deg'); acc += n;
            legend += '<li><i style="background:' + COLORS[k] + '"></i>' + LABEL[k] + '<em>' + n + '</em></li>';
        });
        $('donut').innerHTML = '<div class="donut-wrap"><div class="donut" style="background:conic-gradient(' + stops.join(',') + ')"><span><b>' + total + '</b>requests</span></div><ul class="legend">' + legend + '</ul></div>';
    }

    function barList(id, rows, valKey, fmt, sub) {
        var host = $(id);
        if (!rows.length) { host.innerHTML = '<li class="empty-note">Nothing sold in this period yet.</li>'; return; }
        var max = Math.max.apply(null, rows.map(function (r) { return r[valKey]; })) || 1;
        host.innerHTML = rows.map(function (r) {
            return '<li><div class="n">' + esc(r.name) + (sub ? '<small>' + sub(r) + '</small>' : '') + '</div><div class="val">' + fmt(r) + '</div><div class="bar"><i style="width:' + Math.max(3, r[valKey] / max * 100) + '%"></i></div></li>';
        }).join('');
    }

    function renderDays(daily) {
        var rows = daily.filter(function (d) { return d.requests > 0; }).reverse();
        if (!rows.length) { $('dayTbl').innerHTML = '<tr><td class="empty-note">No transactions in this period.</td></tr>'; return; }
        $('dayTbl').innerHTML = '<thead><tr><th>DATE</th><th class="r">REQUESTS</th><th class="r">COMPLETED</th><th class="r">UNITS</th><th class="r">SALES</th><th class="r">AVG ORDER</th><th class="r">STILL OPEN</th><th class="r">CANCELLED / REJECTED</th></tr></thead><tbody>' +
            rows.map(function (d) {
                return '<tr><td><b>' + esc(d.label) + '</b> <span class="sm">' + esc(d.dow) + '</span></td><td class="r">' + d.requests + '</td><td class="r">' + d.orders + '</td><td class="r">' + d.units + '</td><td class="r money">' + money(d.sales) + '</td><td class="r">' + (d.orders ? money(d.sales / d.orders) : '—') + '</td><td class="r">' + d.open + '</td><td class="r">' + d.closed + '</td></tr>';
            }).join('') + '</tbody>';
    }

    function renderLedger(rows) {
        $('ledgerHint').textContent = rows.length >= 200 ? 'Showing the latest 200. Use Export CSV for everything.' : rows.length + ' transaction' + (rows.length === 1 ? '' : 's') + ' in this period.';
        if (!rows.length) { $('ledgerTbl').innerHTML = '<tr><td class="empty-note">Nothing here yet. Completed reservations show up as soon as staff mark them picked up.</td></tr>'; return; }
        $('ledgerTbl').innerHTML = '<thead><tr><th>REFERENCE</th><th>PICKUP</th><th>STUDENT</th><th>ITEMS</th><th class="r">TOTAL</th><th>STATUS</th><th>HANDLED BY</th></tr></thead><tbody>' +
            rows.map(function (r) {
                var items = r.items.map(function (i) { return i.qty + '× ' + esc(i.name); }).join(', ');
                return '<tr><td class="mono">' + esc(r.reference) + '</td><td>' + esc(fmtDate(r.pickup_date)) + '<div class="sm">' + esc(r.slot) + ' · ' + esc(r.time) + '</div></td><td>' + esc(r.student) + '<div class="sm">' + esc(r.student_ref || '') + '</div></td><td class="items">' + items + '</td><td class="r money">' + money(r.total) + '</td><td><span class="pill ' + r.status + '"><i></i>' + esc(LABEL[r.status] || r.status) + '</span></td><td class="sm">' + esc(r.handled_by || '—') + '</td></tr>';
            }).join('') + '</tbody>';
    }

    function setBadge(id, n) { var b = $(id); if (!b) return; b.textContent = n; b.hidden = !(n > 0); }
    function setLive(ok) { var l = $('live'); l.classList.toggle('off', !ok); l.querySelector('span').textContent = ok ? 'LIVE' : 'OFFLINE'; }

    function render() {
        var d = state.d; if (!d) return;
        $('rangeLabel').textContent = d.label;
        $('fromInput').value = d.from; $('toInput').value = d.to;
        document.querySelectorAll('#ranges .chip').forEach(function (b) { b.classList.toggle('active', b.dataset.r === state.range); });
        document.querySelectorAll('#txChips .chip').forEach(function (b) { b.classList.toggle('active', b.dataset.tx === state.tx); });
        $('csvBtn').href = 'admin-dashboard.php?export=csv&' + query();
        renderKpis(d.kpi); renderChart(d.daily); renderDonut(d.status);
        barList('topList', d.top, 'units', function (r) { return r.units + ' sold'; }, function (r) { return money(r.sales); });
        barList('catList', d.cats, 'sales', function (r) { return money(r.sales); }, function (r) { return r.units + ' unit' + (r.units === 1 ? '' : 's'); });
        barList('slotList', d.slots, 'orders', function (r) { return r.orders + ' order' + (r.orders === 1 ? '' : 's'); }, function (r) { return money(r.sales); });
        renderDays(d.daily); renderLedger(d.ledger);
        if (d.nav) { setBadge('nbRes', d.nav.pending); setBadge('nbInv', d.nav.low); }
    }

    function load() {
        fetch('admin-dashboard.php?action=data&' + query() + '&tx=' + state.tx, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.login) { location.href = 'admin-login.php'; return; }
                if (!d.ok) { setLive(false); return; }
                setLive(true); state.d = d; render();
                if (history.replaceState) history.replaceState(null, '', 'admin-dashboard.php?' + query() + '&tx=' + state.tx);
            }).catch(function () { setLive(false); });
    }

    $('ranges').addEventListener('click', function (e) { var b = e.target.closest('.chip'); if (b) { state.range = b.dataset.r; load(); } });
    $('txChips').addEventListener('click', function (e) { var b = e.target.closest('.chip'); if (b) { state.tx = b.dataset.tx; load(); } });
    $('applyBtn').addEventListener('click', function () {
        if (!$('fromInput').value || !$('toInput').value) { COPS.toast('Pick both dates first.', 'error'); return; }
        state.range = 'custom'; state.from = $('fromInput').value; state.to = $('toInput').value; load();
    });
    if (state.d) { render(); } else { load(); }
    setInterval(load, 20000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) load(); });
})();
JS;
admin_foot($js);