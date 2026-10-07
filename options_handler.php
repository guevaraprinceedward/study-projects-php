<?php
/**
 * customize-lib.php
 * -------------------------------------------------------------------------
 * Shared logic for customized cart lines (size / options / add-ons / note),
 * used by BOTH carts: $_SESSION['cart'] (serve) and $_SESSION['res_cart'].
 *
 * Cart format (new):  $cart[lineKey] = [
 *     'pid'=>int, 'qty'=>int, 'size_id'=>int, 'opts'=>[choiceId..],
 *     'addons'=>[addonId..], 'note'=>string ]
 * Old sessions ($cart[productId] = qty) are converted automatically.
 *
 * The browser only sends IDs. Names and prices are ALWAYS read from the DB.
 * Include after config.php:  include_once 'customize-lib.php';
 */

if (!defined('DELIVERY_FEE')) define('DELIVERY_FEE', 0);   // pesos, applied to Delivery only

function custOrderTypes(): array {
    return ['dine-in' => 'Dine-in', 'take-out' => 'Take-out', 'pick-up' => 'Pick-up', 'delivery' => 'Delivery'];
}

function custIds($v): array {
    if (!is_array($v)) $v = ($v === null || $v === '') ? [] : [$v];
    $out = array_values(array_unique(array_filter(array_map('intval', $v), fn($x) => $x > 0)));
    sort($out);
    return $out;
}

function custRows(mysqli_stmt $st): array {
    $r = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return $r;
}

/** Everything the customize modal needs for one product. */
function custProductConfig(mysqli $conn, int $pid): ?array {
    $st = $conn->prepare("SELECT id, name, price, category, description, image, stock FROM products WHERE id = ?");
    $st->bind_param('i', $pid);
    $st->execute();
    $p = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$p) return null;

    $st = $conn->prepare("SELECT id, label, price, is_default FROM product_sizes WHERE product_id = ? ORDER BY sort_order, price");
    $st->bind_param('i', $pid);
    $st->execute();
    $sizes = custRows($st);

    $st = $conn->prepare("SELECT g.id, g.name, g.type, g.is_required
                          FROM option_groups g JOIN product_option_groups pg ON pg.group_id = g.id
                          WHERE pg.product_id = ? ORDER BY g.sort_order, g.id");
    $st->bind_param('i', $pid);
    $st->execute();
    $groups = custRows($st);
    foreach ($groups as &$g) {
        $gid = (int)$g['id'];
        $cs = $conn->prepare("SELECT id, label, price_delta, is_default FROM option_choices WHERE group_id = ? ORDER BY sort_order, id");
        $cs->bind_param('i', $gid);
        $cs->execute();
        $g['choices'] = custRows($cs);
    }
    unset($g);
    $groups = array_values(array_filter($groups, fn($g) => !empty($g['choices'])));

    $cat = (string)$p['category'];
    $st = $conn->prepare("SELECT id, name, price FROM product_addons WHERE is_active = 1 AND (applies_to = 'all' OR applies_to = ?) ORDER BY name");
    $st->bind_param('s', $cat);
    $st->execute();
    $addons = custRows($st);

    return ['product' => $p, 'sizes' => $sizes, 'groups' => $groups, 'addons' => $addons];
}

/** Validate a customer's selection against the DB and return a normalized cart line. */
function custBuildLine(mysqli $conn, int $pid, array $sel): array {
    $cfg = custProductConfig($conn, $pid);
    if (!$cfg) return ['ok' => false, 'error' => 'Product not found.'];

    $sizeId = 0;
    if ($cfg['sizes']) {
        $want = (int)($sel['size_id'] ?? 0);
        foreach ($cfg['sizes'] as $s) if ((int)$s['id'] === $want) $sizeId = $want;
        if (!$sizeId) {
            foreach ($cfg['sizes'] as $s) if (!empty($s['is_default'])) $sizeId = (int)$s['id'];
            if (!$sizeId) $sizeId = (int)$cfg['sizes'][0]['id'];
        }
    }

    $raw  = is_array($sel['options'] ?? null) ? $sel['options'] : [];
    $opts = [];
    foreach ($cfg['groups'] as $g) {
        $valid = array_map(fn($c) => (int)$c['id'], $g['choices']);
        $want  = array_values(array_intersect(custIds($raw[$g['id']] ?? []), $valid));
        if ($g['type'] === 'single') $want = array_slice($want, 0, 1);
        if (!$want && !empty($g['is_required'])) {
            foreach ($g['choices'] as $c) if (!empty($c['is_default'])) $want = [(int)$c['id']];
            if (!$want) return ['ok' => false, 'error' => 'Please choose: ' . $g['name']];
        }
        $opts = array_merge($opts, $want);
    }
    sort($opts);

    $addonValid = array_map(fn($a) => (int)$a['id'], $cfg['addons']);
    $addons     = array_values(array_intersect(custIds($sel['addons'] ?? []), $addonValid));

    return ['ok' => true, 'line' => [
        'pid'     => $pid,
        'qty'     => max(1, min(50, (int)($sel['qty'] ?? 1))),
        'size_id' => $sizeId,
        'opts'    => $opts,
        'addons'  => $addons,
        'note'    => mb_substr(trim((string)($sel['note'] ?? '')), 0, 120),
    ]];
}

/** Same product + same choices = same key (so the qty just goes up). */
function custLineKey(array $l): string {
    return 'L' . substr(md5(json_encode([(int)$l['pid'], (int)$l['size_id'], array_values($l['opts']), array_values($l['addons']), (string)$l['note']])), 0, 12);
}

/** Price one line from the DB. Returns the product row + priced fields, or null. */
function custPriceLine(mysqli $conn, array $line): ?array {
    $pid = (int)$line['pid'];
    $st = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $st->bind_param('i', $pid);
    $st->execute();
    $p = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$p) return null;

    $price = (float)$p['price'];
    $sizeLabel = '';
    if (!empty($line['size_id'])) {
        $sid = (int)$line['size_id'];
        $st = $conn->prepare("SELECT label, price FROM product_sizes WHERE id = ? AND product_id = ?");
        $st->bind_param('ii', $sid, $pid);
        $st->execute();
        if ($s = $st->get_result()->fetch_assoc()) { $price = (float)$s['price']; $sizeLabel = (string)$s['label']; }
        $st->close();
    }

    $labels = [];
    $delta  = 0.0;
    $optIds = custIds($line['opts'] ?? []);
    if ($optIds) {
        $in = implode(',', $optIds);
        $q = $conn->query("SELECT c.label, c.price_delta FROM option_choices c JOIN option_groups g ON g.id = c.group_id
                           WHERE c.id IN ($in) ORDER BY g.sort_order, g.id, c.sort_order");
        if ($q) while ($r = $q->fetch_assoc()) { $labels[] = $r['label']; $delta += (float)$r['price_delta']; }
    }

    $addonRows = [];
    $addTotal  = 0.0;
    $addIds    = custIds($line['addons'] ?? []);
    if ($addIds) {
        $in = implode(',', $addIds);
        $q = $conn->query("SELECT name, price FROM product_addons WHERE id IN ($in) AND is_active = 1 ORDER BY name");
        if ($q) while ($r = $q->fetch_assoc()) { $addonRows[] = ['name' => $r['name'], 'price' => (float)$r['price']]; $addTotal += (float)$r['price']; }
    }

    $unit = $price + $delta;
    $qty  = (int)$line['qty'];
    return array_merge($p, [
        'qty'              => $qty,
        'price'            => $unit,                 // base for this size + option deltas (add-ons separate)
        'size_label'       => $sizeLabel,
        'opt_labels'       => $labels,
        'spec'             => implode(' · ', array_filter(array_merge([$sizeLabel], $labels))),
        'note'             => (string)($line['note'] ?? ''),
        'addon_ids'        => $addIds,
        'addon_names'      => $addonRows,
        'addon_rows'       => $addonRows,            // alias used by reservation-cart.php
        'addon_unit_total' => $addTotal,
        'subtotal'         => ($unit + $addTotal) * $qty,
        'max_stock'        => (int)$p['stock'],
    ]);
}

/** Turn old-style carts ([productId] => qty) into line carts. */
function custMigrateCart(array &$cart): void {
    foreach ($cart as $k => $v) {
        if (is_array($v)) continue;
        unset($cart[$k]);
        $line = ['pid' => (int)$k, 'qty' => max(1, (int)$v), 'size_id' => 0, 'opts' => [], 'addons' => [], 'note' => ''];
        $cart[custLineKey($line)] = $line;
    }
}

function custCartCount(array $cart): int {
    return (int)array_sum(array_map(fn($l) => is_array($l) ? (int)$l['qty'] : (int)$l, $cart));
}

/** Total qty of one product across all its lines (stock is per product). */
function custProductQty(array $cart, int $pid, ?string $exceptKey = null): int {
    $n = 0;
    foreach ($cart as $k => $l) {
        if ($k === $exceptKey || !is_array($l)) continue;
        if ((int)$l['pid'] === $pid) $n += (int)$l['qty'];
    }
    return $n;
}

/** Apply qty[lineKey] changes from the cart forms, clamped to real stock. */
function custApplyQtyUpdate(mysqli $conn, array &$cart, array $qtys): void {
    foreach ($qtys as $key => $qty) {
        $key = preg_replace('/[^A-Za-z0-9]/', '', (string)$key);
        if (!isset($cart[$key])) continue;
        $qty = (int)$qty;
        if ($qty <= 0) { unset($cart[$key]); continue; }
        $pid = (int)$cart[$key]['pid'];
        $st = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $st->bind_param('i', $pid);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        $max = $row ? (int)$row['stock'] - custProductQty($cart, $pid, $key) : 0;
        if ($max <= 0) { unset($cart[$key]); continue; }
        $cart[$key]['qty'] = min($qty, $max);
    }
}

/** Priced rows for the cart / checkout pages. Returns [rows, total]. Drops dead lines. */
function custBuildCart(mysqli $conn, array &$cart): array {
    $rows  = [];
    $total = 0.0;
    foreach ($cart as $key => $line) {
        if (!is_array($line)) { unset($cart[$key]); continue; }
        $p = custPriceLine($conn, $line);
        if (!$p) { unset($cart[$key]); continue; }
        $stock = (int)$p['stock'];
        $max   = $stock - custProductQty($cart, (int)$line['pid'], $key);
        if ($stock <= 0 || $max <= 0) { unset($cart[$key]); continue; }
        $q = min((int)$line['qty'], $max);
        $cart[$key]['qty'] = $q;
        $p['qty']       = $q;
        $p['subtotal']  = ($p['price'] + $p['addon_unit_total']) * $q;
        $p['max_stock'] = $max;           // most this line can go up to
        $p['key']       = $key;
        $total += $p['subtotal'];
        $rows[] = $p;
    }
    return [$rows, $total];
}

/**
 * Add one customized line to a cart. $req = $_REQUEST-like array.
 * $singleBranch = true for the reservation cart (one reservation = one branch).
 */
function custAddToCart(mysqli $conn, string $sessKey, array $req, bool $singleBranch = false): array {
    if (!isset($_SESSION[$sessKey]) || !is_array($_SESSION[$sessKey])) $_SESSION[$sessKey] = [];
    custMigrateCart($_SESSION[$sessKey]);
    $cart = &$_SESSION[$sessKey];

    $pid = (int)($req['product_id'] ?? $req['id'] ?? 0);
    $st = $conn->prepare("SELECT stock, branch FROM products WHERE id = ?");
    $st->bind_param('i', $pid);
    $st->execute();
    $p = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$p) return ['success' => false, 'message' => 'Product not found'];

    if ($singleBranch) {
        $others = array_values(array_unique(array_filter(array_map(fn($l) => (int)$l['pid'], $cart), fn($x) => $x !== $pid)));
        if ($others) {
            $in = implode(',', $others);
            $b = $conn->query("SELECT branch FROM products WHERE id IN ($in) LIMIT 1");
            $ex = $b ? $b->fetch_assoc() : null;
            if ($ex && $ex['branch'] !== $p['branch']) {
                return ['success' => false, 'message' => 'Your reservation already has ' . ucfirst($ex['branch']) . ' items. One reservation can only be for one branch.'];
            }
        }
    }

    $dec = fn($v) => is_string($v) ? (json_decode($v, true) ?: []) : (is_array($v) ? $v : []);
    $built = custBuildLine($conn, $pid, [
        'size_id' => (int)($req['size_id'] ?? 0),
        'options' => $dec($req['options'] ?? []),
        'addons'  => $dec($req['addons'] ?? []),
        'qty'     => (int)($req['qty'] ?? 1),
        'note'    => (string)($req['note'] ?? ''),
    ]);
    if (!$built['ok']) return ['success' => false, 'message' => $built['error']];

    $line   = $built['line'];
    $key    = custLineKey($line);
    $stock  = (int)$p['stock'];
    $inCart = custProductQty($cart, $pid);
    if ($stock <= 0) return ['success' => false, 'message' => 'Out of stock'];
    if ($inCart + $line['qty'] > $stock) {
        $left = max(0, $stock - $inCart);
        return ['success' => false, 'message' => $left ? "Only $left more available." : 'Out of stock'];
    }

    if (isset($cart[$key])) $cart[$key]['qty'] += $line['qty'];
    else                    $cart[$key] = $line;

    return [
        'success' => true,
        'count'   => custCartCount($cart),
        'stock'   => $stock - $inCart - $line['qty'],   // still available for this product
    ];
}