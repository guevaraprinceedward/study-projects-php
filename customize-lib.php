<?php
/**
 * customize-lib.php  (v2 — creates its own tables, nothing to run in phpMyAdmin)
 * Customized cart lines (size / options / add-ons / note) for BOTH carts:
 *   $_SESSION['cart'] (serve) and $_SESSION['res_cart'] (reservation).
 * Cart: $cart[lineKey] = ['pid','qty','size_id','opts'=>[],'addons'=>[],'note']
 * Browser sends IDs only; names/prices are always read from the DB.
 */
if (!defined('DELIVERY_FEE')) define('DELIVERY_FEE', 0);   // pesos, Delivery only

function custOrderTypes(): array {
    return ['dine-in' => 'Dine-in', 'take-out' => 'Take-out', 'pick-up' => 'Pick-up', 'delivery' => 'Delivery'];
}

/** Creates the customization tables/columns + sample data on first use. Safe to call every request. */
function custEnsureSchema(mysqli $conn): void {
    static $ran = false;
    if ($ran) return;
    $ran = true;
    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['cust_schema_v2'])) return;

    $ok  = true;
    $run = function (string $sql, bool $must = true) use ($conn, &$ok): bool {
        try { $r = $conn->query($sql); } catch (Throwable $e) { $r = false; }
        if (!$r && $must) $ok = false;
        return (bool)$r;
    };

    $run("CREATE TABLE IF NOT EXISTS product_sizes (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, label VARCHAR(40) NOT NULL, price DECIMAL(10,2) NOT NULL, is_default TINYINT(1) NOT NULL DEFAULT 0, sort_order INT NOT NULL DEFAULT 0, KEY idx_ps_product (product_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $run("CREATE TABLE IF NOT EXISTS option_groups (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(60) NOT NULL, type ENUM('single','multi') NOT NULL DEFAULT 'single', is_required TINYINT(1) NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $run("CREATE TABLE IF NOT EXISTS option_choices (id INT AUTO_INCREMENT PRIMARY KEY, group_id INT NOT NULL, label VARCHAR(60) NOT NULL, price_delta DECIMAL(10,2) NOT NULL DEFAULT 0, is_default TINYINT(1) NOT NULL DEFAULT 0, sort_order INT NOT NULL DEFAULT 0, KEY idx_oc_group (group_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $run("CREATE TABLE IF NOT EXISTS product_option_groups (product_id INT NOT NULL, group_id INT NOT NULL, PRIMARY KEY (product_id, group_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $run("CREATE TABLE IF NOT EXISTS product_addons (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, price DECIMAL(10,2) NOT NULL DEFAULT 0, applies_to VARCHAR(20) DEFAULT 'all', is_active TINYINT(1) DEFAULT 1)");

    foreach ([
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS notes TEXT DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'pending'",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS branch VARCHAR(20) DEFAULT 'laguna'",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT 'cash'",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS guest_name VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS guest_phone VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS receipt_code VARCHAR(40) DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS service_type VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_address VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0",
        "ALTER TABLE order_items ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) DEFAULT NULL",
        "ALTER TABLE order_items ADD COLUMN IF NOT EXISTS addons TEXT DEFAULT NULL",
        "ALTER TABLE order_items ADD COLUMN IF NOT EXISTS addons_total DECIMAL(10,2) DEFAULT 0",
        "ALTER TABLE order_items ADD COLUMN IF NOT EXISTS spec VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE order_items ADD COLUMN IF NOT EXISTS item_note VARCHAR(120) DEFAULT NULL",
        "ALTER TABLE reservations ADD COLUMN IF NOT EXISTS service_type VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE reservations ADD COLUMN IF NOT EXISTS delivery_address VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE reservation_items ADD COLUMN IF NOT EXISTS spec VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE reservation_items ADD COLUMN IF NOT EXISTS item_note VARCHAR(120) DEFAULT NULL",
    ] as $sql) $run($sql);   // fails (and retries next request) only while the reservation tables don't exist yet

    // sample data, only when the customization tables are still completely empty
    $n = 1;
    try {
        $r = $conn->query("SELECT (SELECT COUNT(*) FROM option_groups) + (SELECT COUNT(*) FROM product_sizes) AS n");
        if ($r) $n = (int)$r->fetch_assoc()['n'];
    } catch (Throwable $e) {}
    if ($n === 0) {
        $run("INSERT INTO option_groups (name,type,is_required,sort_order) VALUES ('Temperature','single',1,1),('Sugar Level','single',1,2)");
        $run("INSERT INTO option_choices (group_id,label,price_delta,is_default,sort_order)
              SELECT g.id, v.label, 0, v.def, v.so FROM option_groups g JOIN
              (SELECT 'Temperature' gname,'Hot' label,1 def,1 so UNION ALL SELECT 'Temperature','Iced',0,2
               UNION ALL SELECT 'Sugar Level','No Sugar',0,1 UNION ALL SELECT 'Sugar Level','Less Sugar',0,2
               UNION ALL SELECT 'Sugar Level','Regular Sugar',1,3) v ON v.gname = g.name");
        $run("INSERT IGNORE INTO product_option_groups (product_id, group_id)
              SELECT p.id, g.id FROM products p JOIN option_groups g WHERE p.category IN ('coffee','drinks')");
        $run("INSERT INTO product_sizes (product_id,label,price,is_default,sort_order)
              SELECT p.id, s.label, p.price + s.plus, s.def, s.so FROM products p JOIN
              (SELECT '10oz' label,0 plus,1 def,1 so UNION ALL SELECT '16oz',20,0,2 UNION ALL SELECT '22oz',40,0,3) s
              WHERE p.category IN ('coffee','drinks')");
    }

    if ($ok && session_status() === PHP_SESSION_ACTIVE) $_SESSION['cust_schema_v2'] = 1;
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
    custEnsureSchema($conn);
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
            if (!$want) $want = [(int)$g['choices'][0]['id']];
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

function custLineKey(array $l): string {
    return 'L' . substr(md5(json_encode([(int)$l['pid'], (int)$l['size_id'], array_values($l['opts']), array_values($l['addons']), (string)$l['note']])), 0, 12);
}

/** Price one line from the DB. */
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
        'price'            => $unit,
        'size_label'       => $sizeLabel,
        'opt_labels'       => $labels,
        'spec'             => implode(' · ', array_filter(array_merge([$sizeLabel], $labels))),
        'note'             => (string)($line['note'] ?? ''),
        'addon_ids'        => $addIds,
        'addon_names'      => $addonRows,
        'addon_rows'       => $addonRows,
        'addon_unit_total' => $addTotal,
        'subtotal'         => ($unit + $addTotal) * $qty,
        'max_stock'        => (int)$p['stock'],
    ]);
}

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

function custProductQty(array $cart, int $pid, ?string $exceptKey = null): int {
    $n = 0;
    foreach ($cart as $k => $l) {
        if ($k === $exceptKey || !is_array($l)) continue;
        if ((int)$l['pid'] === $pid) $n += (int)$l['qty'];
    }
    return $n;
}

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

/** Priced rows for cart / checkout pages. Returns [rows, total]. Drops dead lines. */
function custBuildCart(mysqli $conn, array &$cart): array {
    custEnsureSchema($conn);
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
        $p['max_stock'] = $max;
        $p['key']       = $key;
        $total += $p['subtotal'];
        $rows[] = $p;
    }
    return [$rows, $total];
}

/** Add one customized line to a cart. $singleBranch = true for the reservation cart. */
function custAddToCart(mysqli $conn, string $sessKey, array $req, bool $singleBranch = false): array {
    custEnsureSchema($conn);
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

    return ['success' => true, 'count' => custCartCount($cart), 'stock' => $stock - $inCart - $line['qty']];
}