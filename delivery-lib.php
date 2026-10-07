<?php
/**
 * delivery-lib.php
 * Schema + status flow for orders that carry a service type (Dine-in / Take-out /
 * Pick-up / Delivery). Delivery orders go through staff approval:
 *   pending -> preparing (staff sets minutes) -> ready -> out_for_delivery -> completed
 * Other service types keep the normal flow: pending -> preparing -> ready -> completed.
 * Include after config.php (customer side) or admin-config.php (admin side).
 */
date_default_timezone_set('Asia/Manila');
if (!defined('DLV_OPEN'))           define('DLV_OPEN', '07:00');
if (!defined('DLV_CLOSE'))          define('DLV_CLOSE', '21:30');
if (!defined('DLV_MIN_LEAD_MIN'))   define('DLV_MIN_LEAD_MIN', 30);
if (!defined('DLV_MAX_DAYS_AHEAD')) define('DLV_MAX_DAYS_AHEAD', 14);

function dlvEnsureSchema(mysqli $conn): void {
    static $ran = false;
    if ($ran) return;
    $ran = true;
    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['dlv_schema_v1'])) return;
    $ok = true;
    foreach ([
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS service_type VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_address VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_phone VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS scheduled_for DATETIME DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS eta_minutes INT DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS approved_at DATETIME DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS eta_at DATETIME DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS ready_at DATETIME DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS dispatched_at DATETIME DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivered_at DATETIME DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS rider_name VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS rider_phone VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS reject_reason VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS track_token CHAR(16) DEFAULT NULL",
        "ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_orders_track (track_token)",
        "ALTER TABLE order_items ADD COLUMN IF NOT EXISTS spec VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE order_items ADD COLUMN IF NOT EXISTS item_note VARCHAR(120) DEFAULT NULL",
        "ALTER TABLE order_items ADD COLUMN IF NOT EXISTS addons TEXT DEFAULT NULL",
        "ALTER TABLE order_items ADD COLUMN IF NOT EXISTS addons_total DECIMAL(10,2) DEFAULT 0",
    ] as $sql) {
        try { if (!$conn->query($sql)) $ok = false; } catch (Throwable $e) { $ok = false; }
    }
    if ($ok && session_status() === PHP_SESSION_ACTIVE) $_SESSION['dlv_schema_v1'] = 1;
}

function dlvFlow(string $service): array {
    if ($service === 'delivery') {
        return ['pending' => 'Waiting for approval', 'preparing' => 'Preparing', 'ready' => 'Ready for rider',
                'out_for_delivery' => 'Out for delivery', 'completed' => 'Delivered'];
    }
    return ['pending' => 'Pending', 'preparing' => 'Preparing', 'ready' => 'Ready', 'completed' => 'Completed'];
}

function dlvStatusLabel(string $status, string $service = ''): string {
    $status = strtolower($status);
    if ($status === 'cancelled') return 'Cancelled';
    return dlvFlow($service)[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function dlvNewToken(): string { return bin2hex(random_bytes(8)); }

/** Plain-language status line for the customer. */
function dlvMessage(array $o): string {
    $svc = (string)($o['service_type'] ?? '');
    $st  = strtolower((string)($o['status'] ?? 'pending'));
    $eta = !empty($o['eta_at']) ? strtotime($o['eta_at']) : 0;
    $min = (int)($o['eta_minutes'] ?? 0);

    if ($st === 'cancelled') {
        return 'This order was cancelled.' . (!empty($o['reject_reason']) ? ' Reason: ' . $o['reject_reason'] : '');
    }
    if ($svc === 'delivery') {
        return match ($st) {
            'pending'          => 'Waiting for the staff to approve your delivery order. Keep this page open. The estimated time will show here once it is approved.',
            'preparing'        => $eta ? "Approved! We are preparing your order. Estimated ready in about {$min} minutes (around " . date('g:i A', $eta) . ').'
                                       : 'Approved! We are preparing your order.',
            'ready'            => 'Your order is ready and waiting for the rider.',
            'out_for_delivery' => 'Your rider is on the way' . (!empty($o['rider_name']) ? ' (' . $o['rider_name'] . ')' : '') . '.',
            'completed'        => 'Delivered. Enjoy your order!',
            default            => '',
        };
    }
    return match ($st) {
        'pending'   => 'Order received. The staff will start on it shortly.',
        'preparing' => 'We are preparing your order.',
        'ready'     => match ($svc) {
            'dine-in'  => 'Your order is ready and will be served at your table.',
            'take-out' => 'Your take-out order is packed and ready.',
            default    => 'Your order is ready for pick up at the counter.',
        },
        'completed' => 'Completed. Thank you!',
        default     => '',
    };
}

/** JSON-safe snapshot used by order-track.php (page + polling). */
function dlvPayload(array $o): array {
    $svc  = (string)($o['service_type'] ?? '');
    $st   = strtolower((string)$o['status']);
    $flow = [];
    foreach (dlvFlow($svc) as $k => $l) $flow[] = ['key' => $k, 'label' => $l];
    return [
        'status'       => $st,
        'status_label' => dlvStatusLabel($st, $svc),
        'message'      => dlvMessage($o),
        'flow'         => $flow,
        'eta_at'       => (!empty($o['eta_at']) && $st === 'preparing') ? strtotime($o['eta_at']) : null,
        'now'          => time(),
        'rider_name'   => (string)($o['rider_name'] ?? ''),
        'rider_phone'  => (string)($o['rider_phone'] ?? ''),
        'done'         => in_array($st, ['completed', 'cancelled'], true),
    ];
}