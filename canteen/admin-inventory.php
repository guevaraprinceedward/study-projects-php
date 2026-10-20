<?php
/**
 * admin-inventory.php — COPS staff console: stock levels, low-stock alerts, movements, restock
 *
 * Stock goes DOWN when a student sends a reservation, and comes BACK when a reservation is rejected
 * or cancelled (includes/reservations.php does that). This page reads that history and also lets
 * staff add stock (restock) or remove it (spoiled, damaged, count correction).
 * Manual changes are saved in the `inventory_log` table (created automatically).
 *
 * Alert levels: OUT = 0 left · LOW = at or below the product's "alert at" level (products.reorder_level).
 *
 *   GET  ?action=data
 *   POST action=stock  pid  mode=add|remove  qty  reason  note  reorder  csrf
 */
require_once __DIR__ . '/admin-bootstrap.php';

$action = (string)($_GET['action'] ?? $_POST['action'] ?? '');
$admin  = admin_require($adminConn, $action !== '');

function inv_ensure_table(mysqli $c): void
{
    if (!empty($_SESSION['inventory_table_ok'])) return;
    $c->query(
        "CREATE TABLE IF NOT EXISTS `inventory_log` (
            `id`           int(11)      NOT NULL AUTO_INCREMENT,
            `product_id`   int(11)      NOT NULL,
            `product_name` varchar(120) NOT NULL,
            `kind`         enum('restock','adjust') NOT NULL,
            `change_qty`   int(11)      NOT NULL,
            `qty_before`   int(11)      NOT NULL,
            `qty_after`    int(11)      NOT NULL,
            `reason`       varchar(60)  DEFAULT NULL,
            `note`         varchar(160) DEFAULT NULL,
            `admin_id`     int(11)      DEFAULT NULL,
            `admin_name`   varchar(120) NOT NULL,
            `created_at`   datetime     NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_inv_product` (`product_id`, `created_at`),
            KEY `idx_inv_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $_SESSION['inventory_table_ok'] = true;
}

function inv_status(int $stock, int $level): string
{
    return $stock <= 0 ? 'out' : ($stock <= $level ? 'low' : 'ok');
}

function inv_products(mysqli $c): array
{
    $since = date('Y-m-d', strtotime('-6 days'));
    $used = [];
    foreach (db_all($c, "SELECT ri.product_id AS pid, SUM(ri.quantity) AS u FROM reservation_items ri JOIN reservations r ON r.id = ri.reservation_id
                         WHERE ri.product_id IS NOT NULL AND r.status NOT IN ('rejected','cancelled') AND r.pickup_date >= ? GROUP BY ri.product_id", 's', [$since]) as $r) {
        $used[(int)$r['pid']] = (int)$r['u'];
    }
    $out = [];
    foreach (db_all($c, "SELECT p.id, p.sku, p.name, p.category, p.price, p.stock, p.reorder_level, p.is_available,
                                COALESCE((SELECT SUM(ri.quantity) FROM reservation_items ri JOIN reservations r ON r.id = ri.reservation_id
                                          WHERE ri.product_id = p.id AND r.status IN ('pending','confirmed','preparing','ready')), 0) AS held
                         FROM products p ORDER BY p.category, p.name") as $p) {
        $id = (int)$p['id']; $stock = (int)$p['stock']; $lvl = (int)$p['reorder_level']; $u7 = $used[$id] ?? 0;
        $out[] = [
            'id' => $id, 'sku' => $p['sku'], 'name' => $p['name'], 'category' => $p['category'], 'cat_label' => admin_category_label($p['category']),
            'price' => (float)$p['price'], 'stock' => $stock, 'level' => $lvl, 'available' => (int)$p['is_available'] === 1,
            'held' => (int)$p['held'], 'used7' => $u7, 'days_left' => ($u7 > 0 && $stock > 0) ? round($stock / ($u7 / 7), 1) : null,
            'status' => inv_status($stock, $lvl),
        ];
    }
    return $out;
}

/**
 * Every stock movement, newest first:
 *   reserved  (−)  a student sent a reservation          ← reservation_items
 *   returned  (+)  a reservation was rejected/cancelled  ← reservation_status_log
 *   restock   (+)  staff added stock                     ← inventory_log
 *   adjust    (±)  staff removed / corrected stock       ← inventory_log
 * "Stock after" is worked out backwards from today's real stock, so it always ends on the true number.
 */
function inv_movements(mysqli $c, array $stockNow, int $limit = 400): array
{
    $m = [];
    foreach (db_all($c, "SELECT ri.product_id, ri.product_name, ri.quantity, r.reference, r.student_name, UNIX_TIMESTAMP(r.created_at) AS ts
                         FROM reservation_items ri JOIN reservations r ON r.id = ri.reservation_id
                         WHERE ri.product_id IS NOT NULL ORDER BY r.created_at DESC, ri.id DESC LIMIT 700") as $r) {
        $m[] = ['kind' => 'reserved', 'ts' => (int)$r['ts'], 'pid' => (int)$r['product_id'], 'name' => $r['product_name'], 'delta' => -(int)$r['quantity'],
                'ref' => $r['reference'], 'note' => 'Reserved by a student', 'by' => $r['student_name'], 'ord' => 1];
    }
    foreach (db_all($c, "SELECT l.to_status, l.actor_type, l.actor_name, l.reason, UNIX_TIMESTAMP(l.created_at) AS ts, ri.product_id, ri.product_name, ri.quantity, r.reference
                         FROM reservation_status_log l JOIN reservations r ON r.id = l.reservation_id JOIN reservation_items ri ON ri.reservation_id = r.id
                         WHERE l.to_status IN ('rejected','cancelled') AND ri.product_id IS NOT NULL ORDER BY l.created_at DESC, l.id DESC LIMIT 700") as $r) {
        $m[] = ['kind' => 'returned', 'ts' => (int)$r['ts'], 'pid' => (int)$r['product_id'], 'name' => $r['product_name'], 'delta' => (int)$r['quantity'],
                'ref' => $r['reference'], 'note' => ($r['to_status'] === 'rejected' ? 'Rejected by staff' : 'Cancelled') . ($r['reason'] ? ': ' . $r['reason'] : ''),
                'by' => $r['actor_name'], 'ord' => 2];
    }
    foreach (db_all($c, "SELECT kind, product_id, product_name, change_qty, reason, note, admin_name, UNIX_TIMESTAMP(created_at) AS ts
                         FROM inventory_log ORDER BY created_at DESC, id DESC LIMIT 700") as $r) {
        $m[] = ['kind' => $r['kind'], 'ts' => (int)$r['ts'], 'pid' => (int)$r['product_id'], 'name' => $r['product_name'], 'delta' => (int)$r['change_qty'],
                'ref' => null, 'note' => trim(($r['reason'] ?? '') . ($r['note'] ? ' — ' . $r['note'] : '')), 'by' => $r['admin_name'], 'ord' => 3];
    }
    usort($m, fn($a, $b) => [$b['ts'], $b['ord']] <=> [$a['ts'], $a['ord']]);
    $m = array_slice($m, 0, $limit);

    $bal = $stockNow;
    foreach ($m as &$row) {
        $after = $bal[$row['pid']] ?? 0;
        $row['after'] = $after; $row['before'] = $after - $row['delta'];
        $bal[$row['pid']] = $row['before'];
    }
    unset($row);
    return $m;
}

function inv_payload(mysqli $c): array
{
    inv_ensure_table($c);
    $products = inv_products($c);
    $now = [];
    foreach ($products as $p) $now[$p['id']] = $p['stock'];
    $low = 0; $out = 0; $value = 0.0;
    foreach ($products as $p) {
        if (!$p['available']) continue;
        if ($p['status'] === 'out') $out++; elseif ($p['status'] === 'low') $low++;
        $value += $p['stock'] * $p['price'];
    }
    return ['ok' => true, 'server_ts' => time(), 'products' => $products, 'movements' => inv_movements($c, $now), 'summary' => ['low' => $low, 'out' => $out, 'value' => round($value, 2)],
            'nav' => ['pending' => admin_nav_counts($c)['pending'], 'low' => $low + $out]];
}

if ($action === 'data') {
    try { json_out(inv_payload($adminConn)); }
    catch (Throwable $e) { app_log('admin-inventory data: ' . $e->getMessage()); json_out(['ok' => false, 'error' => 'Could not load inventory.']); }
}

if ($action === 'stock' && is_post()) {
    if (!admin_csrf_ok()) json_out(['ok' => false, 'error' => 'Session expired. Refresh the page.'], 419);
    $pid    = (int)($_POST['pid'] ?? 0);
    $mode   = ($_POST['mode'] ?? 'add') === 'remove' ? 'remove' : 'add';
    $qty    = (int)($_POST['qty'] ?? 0);
    $reason = trim(mb_substr((string)($_POST['reason'] ?? ''), 0, 60));
    $note   = trim(mb_substr((string)($_POST['note'] ?? ''), 0, 160));
    $rawLvl = trim((string)($_POST['reorder'] ?? ''));
    $level  = $rawLvl === '' ? null : (int)$rawLvl;

    $adminConn->begin_transaction();
    try {
        inv_ensure_table($adminConn);
        if ($qty < 1 || $qty > 100000) throw new UserFacingError('Enter a quantity between 1 and 100,000.');
        if ($level !== null && ($level < 0 || $level > 100000)) throw new UserFacingError('The alert level must be between 0 and 100,000.');
        if ($mode === 'remove' && $reason === '') throw new UserFacingError('Choose a reason for removing stock.');
        if ($reason === '') $reason = 'Restock';

        $p = db_one($adminConn, "SELECT id, name, stock FROM products WHERE id = ? FOR UPDATE", 'i', [$pid]);
        if (!$p) throw new UserFacingError('Product not found.');
        $before = (int)$p['stock'];
        $delta  = $mode === 'add' ? $qty : -$qty;
        $after  = $before + $delta;
        if ($after < 0) throw new UserFacingError('You cannot remove more than the ' . $before . ' in stock.');

        db_exec($adminConn, "UPDATE products SET stock = ? WHERE id = ?", 'ii', [$after, $pid]);
        if ($level !== null) db_exec($adminConn, "UPDATE products SET reorder_level = ? WHERE id = ?", 'ii', [$level, $pid]);
        db_exec($adminConn, "INSERT INTO inventory_log (product_id, product_name, kind, change_qty, qty_before, qty_after, reason, note, admin_id, admin_name)
                             VALUES (?,?,?,?,?,?,?,?,?,?)", 'issiiissis',
                [$pid, $p['name'], $mode === 'add' ? 'restock' : 'adjust', $delta, $before, $after, $reason, $note !== '' ? $note : null, (int)$admin['id'], $admin['full_name']]);
        $adminConn->commit();
    } catch (UserFacingError $e) {
        $adminConn->rollback();
        json_out(['ok' => false, 'error' => $e->getMessage()]);
    } catch (Throwable $e) {
        $adminConn->rollback();
        app_log('admin-inventory stock: ' . $e->getMessage());
        json_out(['ok' => false, 'error' => 'Something went wrong. Please try again.']);
    }

    adminLog($adminConn, (int)$admin['id'], $mode === 'add' ? 'stock_restock' : 'stock_adjust', $p['name'], ($delta > 0 ? '+' : '') . $delta . ' (' . $before . ' → ' . $after . ') ' . $reason);
    $payload = inv_payload($adminConn);
    $payload['message'] = $p['name'] . ': ' . ($delta > 0 ? '+' : '') . $delta . ' → now ' . $after . ' in stock.';
    json_out($payload);
}

/* ── Page ─────────────────────────────────────────────────────────────────── */
$init = null; $dbError = '';
try { $init = inv_payload($adminConn); }
catch (Throwable $e) { app_log('admin-inventory first load: ' . $e->getMessage()); $dbError = 'Inventory could not be loaded. Check that the products table from the dump is imported.'; }
$initJs = json_encode($init, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

admin_head($admin, 'inventory', 'Inventory');
?>
    <?php if ($dbError !== ''): ?><div class="banner bad" role="alert"><div class="grow"><b>Problem:</b> <?= e($dbError) ?></div></div><?php endif; ?>

    <div class="page-hd">
        <div>
            <h1>Inventory &amp; stock</h1>
            <p>Watch stock go down as students reserve, spot what is running out, and restock in one click.</p>
        </div>
    </div>
    <div class="meander" aria-hidden="true"></div>

    <div id="alerts"></div>
    <div class="kpis" id="kpis"></div>

    <div class="tabs-row">
        <div class="chips" id="tabs">
            <button class="chip active" data-tab="stock" type="button">STOCK LEVELS</button>
            <button class="chip" data-tab="moves" type="button">STOCK MOVEMENTS</button>
        </div>
        <div class="ctl" id="stockCtl">
            <input type="search" id="q" placeholder="Search product or SKU" aria-label="Search products" style="min-width:230px">
            <select id="catSel" aria-label="Category"><option value="">All categories</option></select>
            <select id="stSel" aria-label="Stock status">
                <option value="">All stock levels</option><option value="attention">Needs attention</option><option value="out">Out of stock</option><option value="low">Low stock</option><option value="ok">Healthy</option>
            </select>
        </div>
        <div class="ctl" id="moveCtl" hidden>
            <select id="mvProduct" aria-label="Product"><option value="">All products</option></select>
            <select id="mvType" aria-label="Type">
                <option value="">All movements</option><option value="reserved">Reserved (stock out)</option><option value="returned">Returned (cancelled / rejected)</option><option value="restock">Restocked</option><option value="adjust">Adjusted</option>
            </select>
            <select id="mvDays" aria-label="Period"><option value="1">Today</option><option value="7" selected>Last 7 days</option><option value="30">Last 30 days</option><option value="0">All time</option></select>
        </div>
    </div>

    <div class="panel" id="stockPanel"><div class="tbl-wrap"><table class="tbl" id="stockTbl"></table></div></div>
    <div class="panel" id="movePanel" hidden>
        <p class="hint" id="moveHint"></p>
        <div class="tbl-wrap"><table class="tbl" id="moveTbl"></table></div>
    </div>

    <div class="modal" id="stockModal" role="dialog" aria-modal="true" aria-labelledby="smTitle">
        <div class="sheet">
            <h3 id="smTitle">Restock</h3>
            <p class="sub" id="smSub"></p>
            <div class="seg" role="group" aria-label="Action">
                <button type="button" data-mode="add" class="on">ADD STOCK</button>
                <button type="button" data-mode="remove">REMOVE STOCK</button>
            </div>
            <label for="smQty">QUANTITY</label>
            <input type="number" id="smQty" min="1" max="100000" step="1" inputmode="numeric" placeholder="e.g. 20">
            <div class="quick" id="smQuick"><button type="button" data-n="5">+5</button><button type="button" data-n="10">+10</button><button type="button" data-n="20">+20</button><button type="button" data-n="50">+50</button></div>
            <label for="smReason">REASON</label>
            <select id="smReason"></select>
            <label for="smNote">NOTE (OPTIONAL)</label>
            <input type="text" id="smNote" maxlength="160" placeholder="e.g. Delivery from supplier, receipt #1042">
            <label for="smLevel">LOW-STOCK ALERT LEVEL</label>
            <input type="number" id="smLevel" min="0" max="100000" step="1" inputmode="numeric">
            <div class="preview" id="smPreview"></div>
            <div class="row">
                <button class="btn btn-ghost btn-sm" type="button" id="smCancel">CANCEL</button>
                <button class="btn btn-gold btn-sm" type="button" id="smOk">SAVE</button>
            </div>
        </div>
    </div>
<?php
$js = 'var CSRF=' . json_encode($_SESSION['admin_csrf']) . ';var INIT=' . $initJs . ';' . <<<'JS'

(function () {
    var COPS = window.COPS, esc = COPS.esc, money = COPS.money;
    function $(id) { return document.getElementById(id); }
    var state = { d: INIT, tab: 'stock' };
    var LABEL = { ok: 'Healthy', low: 'Low stock', out: 'Out of stock' };
    var KIND = { reserved: 'Reserved', returned: 'Returned', restock: 'Restocked', adjust: 'Adjusted' };
    var REASONS = {
        add: ['Delivery / purchase', 'Returned to stock', 'Count correction'],
        remove: ['Spoiled / expired', 'Damaged', 'Used by the canteen', 'Count correction', 'Other']
    };
    var cur = null, mode = 'add';

    function setBadge(id, n) { var b = $(id); if (!b) return; b.textContent = n; b.hidden = !(n > 0); }
    function setLive(ok) { var l = $('live'); l.classList.toggle('off', !ok); l.querySelector('span').textContent = ok ? 'LIVE' : 'OFFLINE'; }
    function when(ts) { return new Date(ts * 1000).toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', timeZone: 'Asia/Manila' }); }
    function sign(n) { return (n > 0 ? '+' : n < 0 ? '−' : '') + Math.abs(n); }
    function active(p) { return p.available; }

    function renderTop() {
        var d = state.d, ps = d.products.filter(active);
        var out = ps.filter(function (p) { return p.status === 'out'; }), low = ps.filter(function (p) { return p.status === 'low'; });
        var box = $('alerts');
        if (!out.length && !low.length) { box.innerHTML = ''; }
        else {
            var tag = function (p) { return '<button class="tag" data-restock="' + p.id + '" type="button">' + esc(p.name) + ' · ' + p.stock + ' left</button>'; };
            box.innerHTML = '<div class="banner' + (out.length ? ' bad' : '') + '"><div class="grow"><b>' +
                (out.length ? out.length + ' product' + (out.length === 1 ? ' is' : 's are') + ' out of stock' : '') + (out.length && low.length ? ' and ' : '') + (low.length ? low.length + ' running low' : '') +
                '.</b> Click a product to restock it.<div class="tags">' + out.concat(low).map(tag).join('') + '</div></div></div>';
        }
        var held = ps.reduce(function (a, p) { return a + p.held; }, 0);
        var cards = [
            ['navy', 'PRODUCTS TRACKED', ps.length, '<span class="sub">on the menu</span>'],
            ['green', 'HEALTHY', ps.length - out.length - low.length, '<span class="sub">above their alert level</span>'],
            ['warn', 'LOW STOCK', low.length, '<span class="sub">at or below alert level</span>'],
            ['red', 'OUT OF STOCK', out.length, '<span class="sub">students cannot reserve</span>'],
            ['purple', 'HELD FOR RESERVATIONS', held, '<span class="sub">units in open reservations</span>'],
            ['gold', 'STOCK VALUE', money(d.summary.value), '<span class="sub">at menu prices</span>']
        ];
        $('kpis').innerHTML = cards.map(function (c) { return '<div class="kpi ' + c[0] + '"><div class="k">' + c[1] + '</div><div class="v">' + c[2] + '</div><div>' + c[3] + '</div></div>'; }).join('');
    }

    function fillSelects() {
        var cats = {}, d = state.d;
        d.products.forEach(function (p) { cats[p.category] = p.cat_label; });
        var cs = $('catSel'), v = cs.value;
        cs.innerHTML = '<option value="">All categories</option>' + Object.keys(cats).map(function (k) { return '<option value="' + esc(k) + '">' + esc(cats[k]) + '</option>'; }).join('');
        cs.value = v;
        var ms = $('mvProduct'), mv = ms.value;
        ms.innerHTML = '<option value="">All products</option>' + d.products.map(function (p) { return '<option value="' + p.id + '">' + esc(p.name) + '</option>'; }).join('');
        ms.value = mv;
    }

    function renderStock() {
        var q = $('q').value.trim().toLowerCase(), cat = $('catSel').value, st = $('stSel').value;
        var order = { out: 0, low: 1, ok: 2 };
        var rows = state.d.products.filter(function (p) {
            if (cat && p.category !== cat) return false;
            if (st === 'attention' ? p.status === 'ok' : (st && p.status !== st)) return false;
            return !q || (p.name + ' ' + (p.sku || '')).toLowerCase().indexOf(q) !== -1;
        }).sort(function (a, b) { return (order[a.status] - order[b.status]) || a.name.localeCompare(b.name); });
        if (!rows.length) { $('stockTbl').innerHTML = '<tr><td class="empty-note">No products match that filter.</td></tr>'; return; }
        $('stockTbl').innerHTML = '<thead><tr><th>PRODUCT</th><th>STOCK</th><th class="r">ALERT AT</th><th class="r">HELD</th><th class="r">SOLD (7 DAYS)</th><th>RUNS OUT</th><th>STATUS</th><th></th></tr></thead><tbody>' + rows.map(function (p) {
            var pct = Math.min(100, p.stock / Math.max(p.level * 3, 1) * 100);
            var left = p.days_left === null ? (p.stock <= 0 ? '—' : '<span class="sm">no recent sales</span>') : (p.days_left < 1 ? '<b class="down">today</b>' : '~' + p.days_left + ' day' + (p.days_left === 1 ? '' : 's'));
            return '<tr' + (p.available ? '' : ' style="opacity:.55"') + '><td><b>' + esc(p.name) + '</b><div class="sm">' + esc(p.cat_label) + (p.sku ? ' · ' + esc(p.sku) : '') + (p.available ? '' : ' · hidden from menu') + '</div></td>' +
                '<td class="stockcell"><b>' + p.stock + '</b><div class="sbar ' + p.status + '"><i style="width:' + Math.max(pct, p.stock > 0 ? 4 : 0) + '%"></i></div></td>' +
                '<td class="r">' + p.level + '</td><td class="r">' + p.held + '</td><td class="r">' + p.used7 + '</td><td>' + left + '</td>' +
                '<td><span class="pill ' + p.status + '"><i></i>' + LABEL[p.status] + '</span></td>' +
                '<td class="r" style="white-space:nowrap"><button class="act go sm" data-restock="' + p.id + '" type="button">RESTOCK</button> <button class="act sm" data-remove="' + p.id + '" type="button">REMOVE</button></td></tr>';
        }).join('') + '</tbody>';
    }

    function renderMoves() {
        var pid = +$('mvProduct').value || 0, type = $('mvType').value, days = +$('mvDays').value, cutoff = days ? (state.d.server_ts - days * 86400) : 0;
        if (days === 1) { var t = new Date(); t.setHours(0, 0, 0, 0); cutoff = t.getTime() / 1000; }
        var rows = state.d.movements.filter(function (m) { return (!pid || m.pid === pid) && (!type || m.kind === type) && m.ts >= cutoff; }).slice(0, 200);
        $('moveHint').textContent = rows.length + ' movement' + (rows.length === 1 ? '' : 's') + ' shown. "Stock after" is the amount left right after that movement.';
        if (!rows.length) { $('moveTbl').innerHTML = '<tr><td class="empty-note">No stock movements for this filter yet.</td></tr>'; return; }
        $('moveTbl').innerHTML = '<thead><tr><th>WHEN</th><th>PRODUCT</th><th>TYPE</th><th class="r">CHANGE</th><th class="r">STOCK AFTER</th><th>DETAILS</th><th>BY</th></tr></thead><tbody>' + rows.map(function (m) {
            return '<tr><td class="sm" style="white-space:nowrap">' + when(m.ts) + '</td><td><b>' + esc(m.name) + '</b></td><td><span class="pill ' + m.kind + '"><i></i>' + KIND[m.kind] + '</span></td>' +
                '<td class="r ' + (m.delta < 0 ? 'minus' : 'plus') + '">' + sign(m.delta) + '</td><td class="r">' + m.before + ' → <b>' + m.after + '</b></td>' +
                '<td class="sm">' + (m.ref ? '<span class="mono">' + esc(m.ref) + '</span> · ' : '') + esc(m.note || '') + '</td><td class="sm">' + esc(m.by || '') + '</td></tr>';
        }).join('') + '</tbody>';
    }

    function render() {
        if (!state.d) return;
        renderTop(); fillSelects(); renderStock(); renderMoves();
        if (state.d.nav) { setBadge('nbRes', state.d.nav.pending); setBadge('nbInv', state.d.nav.low); }
    }

    function load() {
        fetch('admin-inventory.php?action=data', { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.login) { location.href = 'admin-login.php'; return; }
                if (!d.ok) { setLive(false); return; }
                setLive(true); state.d = d; render();
            }).catch(function () { setLive(false); });
    }

    /* ── tabs + filters ── */
    $('tabs').addEventListener('click', function (e) {
        var b = e.target.closest('.chip'); if (!b) return;
        state.tab = b.dataset.tab;
        document.querySelectorAll('#tabs .chip').forEach(function (x) { x.classList.toggle('active', x === b); });
        $('stockPanel').hidden = $('stockCtl').hidden = state.tab !== 'stock';
        $('movePanel').hidden = $('moveCtl').hidden = state.tab !== 'moves';
    });
    ['q', 'catSel', 'stSel'].forEach(function (id) { $(id).addEventListener('input', renderStock); });
    ['mvProduct', 'mvType', 'mvDays'].forEach(function (id) { $(id).addEventListener('input', renderMoves); });

    /* ── restock / remove dialog ── */
    var modal = $('stockModal');
    function setMode(m) {
        mode = m;
        document.querySelectorAll('.seg button').forEach(function (b) { b.classList.toggle('on', b.dataset.mode === m); });
        $('smReason').innerHTML = REASONS[m].map(function (r) { return '<option>' + esc(r) + '</option>'; }).join('');
        $('smQuick').hidden = m !== 'add';
        $('smTitle').textContent = (m === 'add' ? 'Restock · ' : 'Remove stock · ') + cur.name;
        $('smOk').textContent = m === 'add' ? 'ADD TO STOCK' : 'REMOVE FROM STOCK';
        preview();
    }
    function preview() {
        var n = parseInt($('smQty').value, 10) || 0, after = cur.stock + (mode === 'add' ? n : -n);
        $('smPreview').innerHTML = n > 0 ? 'Stock will change from <b>' + cur.stock + '</b> to <b' + (after < 0 ? ' class="down"' : '') + '>' + after + '</b>.' : 'Current stock: <b>' + cur.stock + '</b>. Enter how many to ' + (mode === 'add' ? 'add' : 'remove') + '.';
    }
    function openModal(id, m) {
        cur = state.d.products.filter(function (p) { return p.id === id; })[0]; if (!cur) return;
        $('smSub').textContent = cur.cat_label + ' · ' + cur.stock + ' in stock now, ' + cur.held + ' held for open reservations.';
        $('smQty').value = ''; $('smNote').value = ''; $('smLevel').value = cur.level;
        setMode(m); modal.classList.add('show'); setTimeout(function () { $('smQty').focus(); }, 60);
    }
    function closeModal() { modal.classList.remove('show'); cur = null; }
    document.addEventListener('click', function (e) {
        var r = e.target.closest('[data-restock]'); if (r) { openModal(+r.dataset.restock, 'add'); return; }
        var x = e.target.closest('[data-remove]'); if (x) openModal(+x.dataset.remove, 'remove');
    });
    document.querySelector('.seg').addEventListener('click', function (e) { var b = e.target.closest('button'); if (b && cur) setMode(b.dataset.mode); });
    $('smQty').addEventListener('input', preview);
    $('smQuick').addEventListener('click', function (e) { var b = e.target.closest('button'); if (!b) return; $('smQty').value = (parseInt($('smQty').value, 10) || 0) + (+b.dataset.n); preview(); });
    $('smCancel').addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
    $('smOk').addEventListener('click', function () {
        if (!cur) return;
        var n = parseInt($('smQty').value, 10);
        if (!n || n < 1) { COPS.toast('Enter a quantity first.', 'error'); return; }
        var fd = new FormData(), btn = $('smOk');
        fd.append('action', 'stock'); fd.append('csrf', CSRF); fd.append('pid', cur.id); fd.append('mode', mode); fd.append('qty', n);
        fd.append('reason', $('smReason').value); fd.append('note', $('smNote').value.trim()); fd.append('reorder', $('smLevel').value);
        btn.disabled = true;
        fetch('admin-inventory.php', { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                btn.disabled = false;
                if (d.login) { location.href = 'admin-login.php'; return; }
                if (!d.ok) { COPS.toast(d.error || 'Could not save.', 'error', 4500); return; }
                COPS.toast(d.message, 'ok', 3200); closeModal(); state.d = d; render();
            }).catch(function () { btn.disabled = false; COPS.toast('Network error. Try again.', 'error'); });
    });

    if (state.d) render(); else load();
    setInterval(load, 12000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) load(); });
})();
JS;
admin_foot($js);