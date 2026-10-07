<?php
/**
 * order-lib.php — helpers shared by checkout.php, orders.php and the receipt modal.
 * Include after config.php:   include_once 'order-lib.php';
 */
include_once __DIR__ . '/delivery-lib.php';

function ordPayMethods(): array {
    return ['cash' => 'Cash on Hand', 'gcash' => 'GCash', 'maya' => 'Maya', 'card' => 'Credit / Debit Card'];
}

/** Logged-in user id (validated against the DB) or 0 for guests. */
function ordValidUserId(mysqli $conn): int {
    if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) return 0;
    $uid = (int)($_SESSION['user']['id'] ?? 0);
    $un  = (string)($_SESSION['user']['username'] ?? '');
    if ($uid <= 0) return 0;
    $st = $conn->prepare("SELECT id FROM users WHERE id = ? AND username = ? LIMIT 1");
    $st->bind_param('is', $uid, $un);
    $st->execute(); $st->store_result();
    $ok = $st->num_rows > 0;
    $st->close();
    return $ok ? $uid : 0;
}

/** Makes sure every column the order flow uses exists (runs once per session). */
function ordEnsureSchema(mysqli $conn): void {
    dlvEnsureSchema($conn);
    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['ord_schema_v3'])) return;
    $ok = true;
    foreach ([
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS notes TEXT DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'pending'",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS branch VARCHAR(20) DEFAULT 'laguna'",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT 'cash'",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_ref VARCHAR(60) DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS cash_tendered DECIMAL(10,2) DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS guest_name VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS guest_phone VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS receipt_code VARCHAR(40) DEFAULT NULL",
        "ALTER TABLE order_items ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) DEFAULT NULL",
        "ALTER TABLE order_items ADD COLUMN IF NOT EXISTS addons TEXT DEFAULT NULL",
        "ALTER TABLE order_items ADD COLUMN IF NOT EXISTS addons_total DECIMAL(10,2) DEFAULT 0",
    ] as $sql) {
        try { if (!$conn->query($sql)) $ok = false; } catch (Throwable $e) { $ok = false; }
    }
    if ($ok && session_status() === PHP_SESSION_ACTIVE) $_SESSION['ord_schema_v3'] = 1;
}

function ordCode(array $o): string {
    return !empty($o['receipt_code']) ? (string)$o['receipt_code'] : ('#' . str_pad((string)(int)$o['id'], 5, '0', STR_PAD_LEFT));
}

function ordStatusColor(string $s): string {
    return match (strtolower($s)) {
        'pending' => '#c9a84c', 'confirmed' => '#5b8fd4', 'preparing' => '#c9a84c', 'ready' => '#6aaa52',
        'out_for_delivery' => '#9b7be0', 'completed' => '#4a7a3a', 'cancelled' => '#c0392b', default => '#6b6b58',
    };
}

function ordLoadByToken(mysqli $conn, string $token): ?array {
    if (!preg_match('/^[a-f0-9]{16}$/', $token)) return null;
    $st = $conn->prepare("SELECT * FROM orders WHERE track_token = ? LIMIT 1");
    $st->bind_param('s', $token);
    $st->execute();
    $o = $st->get_result()->fetch_assoc();
    $st->close();
    return $o ?: null;
}

/** Items of one order, with spec / add-ons / note and the line total. */
function ordItems(mysqli $conn, int $oid): array {
    $st = $conn->prepare("SELECT oi.quantity, oi.price, oi.addons, oi.addons_total, oi.spec, oi.item_note,
                                 COALESCE(p.name, CONCAT('Product #', oi.product_id)) AS name
                          FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id
                          WHERE oi.order_id = ? ORDER BY oi.id ASC");
    $st->bind_param('i', $oid);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    $out = [];
    foreach ($rows as $r) {
        $ad = json_decode((string)$r['addons'], true);
        $names = [];
        if (is_array($ad)) foreach ($ad as $a) { $n = is_array($a) ? ($a['name'] ?? '') : $a; if ($n !== '') $names[] = (string)$n; }
        $unit = (float)$r['price']; $addT = (float)$r['addons_total']; $qty = (int)$r['quantity'];
        $out[] = ['name' => (string)$r['name'], 'qty' => $qty, 'unit' => $unit, 'addons' => $names, 'addons_total' => $addT,
                  'spec' => trim((string)$r['spec']), 'note' => trim((string)$r['item_note']), 'line' => ($unit + $addT) * $qty];
    }
    return $out;
}

/** JSON-safe data for the receipt modal. */
function ordReceiptData(mysqli $conn, array $o, string $fallbackName = ''): array {
    $items = ordItems($conn, (int)$o['id']);
    $sub   = array_sum(array_column($items, 'line'));
    $svc   = (string)($o['service_type'] ?? '');
    $types = ['dine-in' => 'Dine-in', 'take-out' => 'Take-out', 'pick-up' => 'Pick-up', 'delivery' => 'Delivery'];
    $st    = strtolower((string)($o['status'] ?? 'pending'));
    $m     = strtolower((string)($o['payment_method'] ?? 'cash'));
    $total = (float)$o['total'];
    $tend  = (isset($o['cash_tendered']) && $o['cash_tendered'] !== null && $o['cash_tendered'] !== '') ? (float)$o['cash_tendered'] : null;
    return [
        'id'            => (int)$o['id'],
        'code'          => ordCode($o),
        'created'       => date('M j, Y · g:i A', strtotime((string)$o['created_at'])),
        'name'          => (string)($o['guest_name'] ?: ($fallbackName !== '' ? $fallbackName : 'Customer')),
        'phone'         => (string)($o['guest_phone'] ?: ($o['delivery_phone'] ?? '')),
        'branch'        => ucfirst((string)($o['branch'] ?? '')),
        'service'       => $svc,
        'service_label' => $types[$svc] ?? '',
        'address'       => $svc === 'delivery' ? (string)$o['delivery_address'] : '',
        'scheduled'     => (!empty($o['scheduled_for'])) ? date('D, M j · g:i A', strtotime((string)$o['scheduled_for'])) : '',
        'notes'         => (string)($o['notes'] ?? ''),
        'items'         => $items,
        'subtotal'      => $sub,
        'fee'           => (float)($o['delivery_fee'] ?? 0),
        'total'         => $total,
        'status_key'    => $st,
        'status_label'  => dlvStatusLabel($st, $svc),
        'status_color'  => ordStatusColor($st),
        'pay_label'     => ordPayMethods()[$m] ?? ucfirst($m),
        'pay_ref'       => ($m === 'gcash' || $m === 'maya' || $m === 'card') ? (string)($o['payment_ref'] ?? '') : '',
        'tendered'      => $m === 'cash' ? $tend : null,
        'change'        => ($m === 'cash' && $tend !== null) ? max(0.0, round($tend - $total, 2)) : 0.0,
        'track_url'     => !empty($o['track_token']) ? 'order-track.php?t=' . $o['track_token'] : '',
    ];
}

function ordJson($data): string {
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
}