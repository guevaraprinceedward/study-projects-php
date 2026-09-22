<?php
/**
 * reservation-init.php  (v2)
 * -------------------------------------------------------------------------
 * Shared bootstrap for the Reservation feature.
 * Include it AFTER config.php (and after session_start):
 *
 *     include 'config.php';
 *     include_once 'reservation-init.php';
 *
 * What it does
 *   1. Sets the timezone to Asia/Manila so "today" / "30 minutes from now"
 *      match the branches (XAMPP's default timezone is often wrong).
 *   2. Makes sure the reservation tables exist (same structure as
 *      reservation-migration.sql) and adds the v2 columns
 *      (payment_ref, card_last4, cash_tendered, picked_up_at).
 *   3. Provides the helper functions used by the reservation pages.
 *
 * Changes in v2
 *   - new columns for the payment details (see reservation-migration-v2.sql)
 *   - resPayMethods() / resPaymentLabel() / resPaymentSummary()
 *   - resListForVisitor() / resGroupKey()  (used by my-reservations.php and
 *     reservation-orders.php)
 *   - resLoadReservation() now also returns the order code once paid
 *   - resReceiptData() carries the payment details + a pay_url
 */
if (!isset($conn) || !($conn instanceof mysqli)) {
    die('reservation-init.php must be included after config.php');
}

date_default_timezone_set('Asia/Manila');

// ── SETTINGS (edit to match your branches) ────────────────────────────────
if (!defined('RES_OPEN'))           define('RES_OPEN', '07:00');   // earliest reservation time (24h)
if (!defined('RES_CLOSE'))          define('RES_CLOSE', '22:00');  // latest reservation time (24h)
if (!defined('RES_MIN_LEAD_MIN'))   define('RES_MIN_LEAD_MIN', 30); // must be booked at least N minutes ahead
if (!defined('RES_MAX_DAYS_AHEAD')) define('RES_MAX_DAYS_AHEAD', 90);
if (!defined('RES_MAX_PARTY'))      define('RES_MAX_PARTY', 50);

// ── TABLES ────────────────────────────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS `reservations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `reservation_code` VARCHAR(40) DEFAULT NULL,
  `user_id` INT(11) DEFAULT NULL,
  `guest_name` VARCHAR(100) NOT NULL,
  `guest_phone` VARCHAR(20) DEFAULT NULL,
  `branch` VARCHAR(20) NOT NULL DEFAULT 'laguna',
  `reservation_date` DATE NOT NULL,
  `reservation_time` TIME NOT NULL,
  `party_size` INT(11) NOT NULL DEFAULT 1,
  `notes` TEXT DEFAULT NULL,
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` VARCHAR(20) NOT NULL DEFAULT 'reserved',
  `payment_status` VARCHAR(20) NOT NULL DEFAULT 'unpaid',
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `order_id` INT(11) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `paid_at` DATETIME DEFAULT NULL,
  `cancelled_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_res_user` (`user_id`),
  KEY `idx_res_when` (`reservation_date`, `reservation_time`),
  KEY `idx_res_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$conn->query("CREATE TABLE IF NOT EXISTS `reservation_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `product_name` VARCHAR(150) DEFAULT NULL,
  `quantity` INT(11) NOT NULL DEFAULT 1,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `addons` TEXT DEFAULT NULL,
  `addons_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_resitem_res` (`reservation_id`),
  CONSTRAINT `fk_resitem_res` FOREIGN KEY (`reservation_id`)
    REFERENCES `reservations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$conn->query("CREATE TABLE IF NOT EXISTS product_addons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    applies_to VARCHAR(20) DEFAULT 'all',
    is_active TINYINT(1) DEFAULT 1
)");

// ── v2 COLUMNS (payment details + pick-up) — runs once per browser session ─
// Same statements as reservation-migration-v2.sql. The PIN of a card is never
// stored: only card_last4.
if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['res_schema_v2'])) {
    $conn->query("ALTER TABLE `reservations` ADD COLUMN IF NOT EXISTS `payment_ref`   VARCHAR(60)   DEFAULT NULL AFTER `payment_method`");
    $conn->query("ALTER TABLE `reservations` ADD COLUMN IF NOT EXISTS `card_last4`    CHAR(4)       DEFAULT NULL AFTER `payment_ref`");
    $conn->query("ALTER TABLE `reservations` ADD COLUMN IF NOT EXISTS `cash_tendered` DECIMAL(10,2) DEFAULT NULL AFTER `card_last4`");
    $conn->query("ALTER TABLE `reservations` ADD COLUMN IF NOT EXISTS `picked_up_at`  DATETIME      DEFAULT NULL AFTER `paid_at`");
    $conn->query("ALTER TABLE `reservations` ADD INDEX IF NOT EXISTS `idx_res_order`  (`order_id`)");
    $conn->query("ALTER TABLE `reservations` ADD INDEX IF NOT EXISTS `idx_res_payref` (`payment_ref`)");
    $conn->query("ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `receipt_code` VARCHAR(40) DEFAULT NULL");
    $conn->query("ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `payment_ref`  VARCHAR(60) DEFAULT NULL");
    if (session_status() === PHP_SESSION_ACTIVE) $_SESSION['res_schema_v2'] = 1;
}

// ── HELPERS ───────────────────────────────────────────────────────────────

/** Validated logged-in user id (same check the other pages do), or 0 for guests. */
function resValidUserId(mysqli $conn): int {
    if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) return 0;
    $uid   = (int)($_SESSION['user']['id'] ?? 0);
    $uname = (string)($_SESSION['user']['username'] ?? '');
    if ($uid <= 0) return 0;
    $st = $conn->prepare("SELECT id FROM users WHERE id = ? AND username = ? LIMIT 1");
    $st->bind_param('is', $uid, $uname);
    $st->execute();
    $st->store_result();
    $ok = $st->num_rows > 0;
    $st->close();
    return $ok ? $uid : 0;
}

/** Can the current visitor see / act on this reservation? */
function resCanAccess(array $r, int $uid): bool {
    if ($uid > 0 && !empty($r['user_id']) && (int)$r['user_id'] === $uid) return true;
    if (empty($r['user_id'])) {
        $mine = $_SESSION['guest_reservations'] ?? [];
        return is_array($mine) && in_array((int)$r['id'], array_map('intval', $mine), true);
    }
    return false;
}

/** Payment methods accepted at reservation-checkout.php (key => label). */
function resPayMethods(): array {
    return [
        'cash'  => 'Cash on Hand',
        'gcash' => 'GCash',
        'maya'  => 'Maya',
        'card'  => 'Credit / Debit Card',
    ];
}

function resPaymentLabel($method): string {
    $key = strtolower(trim((string)$method));
    if ($key === '') return '—';
    return resPayMethods()[$key] ?? ucfirst($key);
}

/** One line describing how a reservation was paid, e.g. "GCash · Ref. 1234567890123". */
function resPaymentSummary(array $r): string {
    $out = resPaymentLabel($r['payment_method'] ?? '');
    $m   = strtolower((string)($r['payment_method'] ?? ''));
    if (($m === 'gcash' || $m === 'maya') && !empty($r['payment_ref'])) {
        $out .= ' · Ref. ' . $r['payment_ref'];
    } elseif ($m === 'card' && !empty($r['card_last4'])) {
        $out .= ' · ending ' . $r['card_last4'];
    } elseif ($m === 'cash' && isset($r['cash_tendered']) && $r['cash_tendered'] !== null && $r['cash_tendered'] !== '') {
        $out .= ' · ₱' . number_format((float)$r['cash_tendered'], 2) . ' received';
    }
    return $out;
}

/** [label, color] for a reservation status. */
function resStatusMeta(string $status, string $paymentStatus = 'unpaid'): array {
    switch (strtolower($status)) {
        case 'reserved':  return $paymentStatus === 'paid'
                              ? ['Paid · Ready for pick up', '#6aaa52']
                              : ['Reserved · Unpaid', '#c9a84c'];
        case 'paid':      return ['Paid · Ready for pick up', '#6aaa52'];
        case 'completed': return ['Picked up', '#4a7a3a'];
        case 'cancelled': return ['Cancelled', '#c0392b'];
        default:          return [ucfirst($status), '#6b6b58'];
    }
}

/** Filter group used by my-reservations.php / reservation-orders.php: waiting | paid | done | cancelled */
function resGroupKey(array $r): string {
    $status = strtolower((string)$r['status']);
    if ($status === 'cancelled') return 'cancelled';
    if ($status === 'completed') return 'done';
    if (strtolower((string)$r['payment_status']) === 'paid') return 'paid';
    return 'waiting';
}

/** Where "Pick up now" sends the customer. */
function resPayUrl(int $id): string { return 'reservation-checkout.php?reservation=' . $id; }

/** "Mon, Sep 21, 2026" */
function resFormatDate(string $date): string { return date('D, M j, Y', strtotime($date)); }
/** "7:00 PM" */
function resFormatTime(string $time): string { return date('g:i A', strtotime($time)); }

/** Decode the stored add-ons JSON into [['name'=>..,'price'=>..], ...]. */
function resDecodeAddons($json): array {
    $raw = json_decode((string)$json, true);
    if (!is_array($raw)) return [];
    $out = [];
    foreach ($raw as $a) {
        if (is_array($a) && isset($a['name'])) $out[] = ['name' => (string)$a['name'], 'price' => (float)($a['price'] ?? 0)];
        elseif (is_string($a))                  $out[] = ['name' => $a, 'price' => 0.0];
    }
    return $out;
}

/** Load one reservation with its items (and product image/description). */
function resLoadReservation(mysqli $conn, int $id): ?array {
    if ($id <= 0) return null;
    $st = $conn->prepare("SELECT * FROM reservations WHERE id = ? LIMIT 1");
    $st->bind_param('i', $id);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$r) return null;

    $st = $conn->prepare("SELECT ri.*, p.image AS p_image, p.description AS p_description, p.category AS p_category
                          FROM reservation_items ri
                          LEFT JOIN products p ON p.id = ri.product_id
                          WHERE ri.reservation_id = ? ORDER BY ri.id ASC");
    $st->bind_param('i', $id);
    $st->execute();
    $res = $st->get_result();
    $items = [];
    while ($row = $res->fetch_assoc()) {
        $row['addon_list'] = resDecodeAddons($row['addons']);
        $row['line_total'] = ((float)$row['price'] + (float)$row['addons_total']) * (int)$row['quantity'];
        $items[] = $row;
    }
    $st->close();
    $r['items'] = $items;

    // Once paid, the reservation is linked to a normal order: carry its receipt code
    $r['order_code'] = '';
    if (!empty($r['order_id'])) {
        $oid = (int)$r['order_id'];
        $q = $conn->query("SELECT receipt_code FROM orders WHERE id = $oid LIMIT 1");
        if ($q && ($o = $q->fetch_assoc())) $r['order_code'] = (string)($o['receipt_code'] ?? '');
    }
    return $r;
}

/**
 * All reservations of the current visitor, newest first.
 * Logged in  -> by user_id.   Guest -> the ids kept in $_SESSION['guest_reservations'].
 * $paidOnly  -> only the ones that were paid (these are the "reservation orders").
 */
function resListForVisitor(mysqli $conn, int $uid, bool $paidOnly = false): array {
    if ($uid > 0) {
        $st = $conn->prepare("SELECT id FROM reservations WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 200");
        $st->bind_param('i', $uid);
    } else {
        $mine = $_SESSION['guest_reservations'] ?? [];
        $ids  = is_array($mine) ? array_values(array_unique(array_filter(array_map('intval', $mine), fn($x) => $x > 0))) : [];
        if (!$ids) return [];
        $list = implode(',', $ids); // integers only
        $st = $conn->prepare("SELECT id FROM reservations WHERE user_id IS NULL AND id IN ($list) ORDER BY created_at DESC, id DESC");
    }
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    $out = [];
    foreach ($rows as $row) {
        $r = resLoadReservation($conn, (int)$row['id']);
        if (!$r) continue;
        if ($paidOnly && strtolower((string)$r['payment_status']) !== 'paid') continue;
        $out[] = $r;
    }
    return $out;
}

/** Everything the receipt modal (reservation-receipt.inc.php) needs, JSON-safe. */
function resReceiptData(array $r): array {
    [$label, $color] = resStatusMeta($r['status'], $r['payment_status']);
    $items = [];
    foreach ($r['items'] as $it) {
        $items[] = [
            'name'       => (string)($it['product_name'] ?? 'Item'),
            'qty'        => (int)$it['quantity'],
            'unit_price' => (float)$it['price'],
            'addons'     => $it['addon_list'],
            'subtotal'   => (float)$it['line_total'],
        ];
    }
    $tendered = (isset($r['cash_tendered']) && $r['cash_tendered'] !== null && $r['cash_tendered'] !== '')
              ? (float)$r['cash_tendered'] : null;
    $change   = $tendered !== null ? max(0.0, round($tendered - (float)$r['total'], 2)) : 0.0;

    return [
        'id'             => (int)$r['id'],
        'code'           => (string)($r['reservation_code'] ?: ('RSV-' . str_pad((string)$r['id'], 5, '0', STR_PAD_LEFT))),
        'order_code'     => (string)($r['order_code'] ?? ''),
        'name'           => (string)$r['guest_name'],
        'phone'          => (string)($r['guest_phone'] ?? ''),
        'branch'         => ucfirst((string)$r['branch']),
        'date'           => resFormatDate($r['reservation_date']),
        'time'           => resFormatTime($r['reservation_time']),
        'party'          => (int)$r['party_size'],
        'notes'          => (string)($r['notes'] ?? ''),
        'total'          => (float)$r['total'],
        'status_key'     => (string)$r['status'],
        'status_label'   => $label,
        'status_color'   => $color,
        'payment_status' => (string)$r['payment_status'],
        'payment_method' => (string)($r['payment_method'] ?? ''),
        'payment_label'  => resPaymentLabel($r['payment_method'] ?? ''),
        'payment_ref'    => (string)($r['payment_ref'] ?? ''),
        'card_last4'     => (string)($r['card_last4'] ?? ''),
        'cash_tendered'  => $tendered,
        'change'         => $change,
        'pay_url'        => resPayUrl((int)$r['id']),
        'items'          => $items,
    ];
}

/** Safe JSON for embedding inside a <script> block. */
function resJson($data): string {
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
}