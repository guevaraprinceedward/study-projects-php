<?php
// Same defensive pattern as process-reservation.php: buffer everything so a
// stray notice/warning from an include never leaks into the JSON body and
// breaks the frontend's fetch().json() parse.
ob_start();

include_once 'hotel-config.php';

set_exception_handler(function ($e) {
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    exit;
});
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

if (ob_get_length()) ob_clean();
header('Content-Type: application/json');

if (!isHotelLoggedIn()) {
    echo json_encode(['ok' => false, 'error' => 'Please log in or create an account to complete your order.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$items = $input['items'] ?? [];
if (!is_array($items) || empty($items)) {
    echo json_encode(['ok' => false, 'error' => 'No items in order.']);
    exit;
}
if (count($items) > 50) {
    echo json_encode(['ok' => false, 'error' => 'That order has too many line items — please split it up.']);
    exit;
}

$userId = $_SESSION['user']['id'];
$tier   = currentTier();
$ref    = generateRef('SO');
$total  = 0;
$today  = date('Y-m-d');

$conn->begin_transaction();
try {
    foreach ($items as $it) {
        $serviceId = (int)($it['id'] ?? 0);
        $qty       = max(1, (int)($it['qty'] ?? 1));
        $date      = !empty($it['date']) ? $it['date'] : null;
        $time      = !empty($it['time']) ? $it['time'] : null;
        $addonIds  = array_values(array_unique(array_filter(array_map('intval', $it['addons'] ?? []), fn($id) => $id > 0)));
        // Client may send addons as [{id, name, price}] — normalize to ids only, we re-price server-side.
        if (empty($addonIds) && !empty($it['addons']) && is_array($it['addons'])) {
            $addonIds = array_values(array_unique(array_filter(array_map(fn($a) => (int)($a['id'] ?? 0), $it['addons']))));
        }

        if (!$serviceId) {
            throw new Exception('One of the items in your order is invalid.');
        }

        // Lock the row so a concurrent order can't oversell a limited-stock item.
        $stmt = $conn->prepare("SELECT * FROM services WHERE id = ? AND active = 1 FOR UPDATE");
        $stmt->bind_param("i", $serviceId);
        $stmt->execute();
        $svc = $stmt->get_result()->fetch_assoc();
        if (!$svc) {
            throw new Exception('One of the selected services is no longer available.');
        }

        // Scheduled services (anything not sold per item) need a valid date.
        if ($svc['unit_type'] !== 'per_item') {
            if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                throw new Exception("Please choose a date for \"{$svc['name']}\".");
            }
            if ($date < $today) {
                throw new Exception("The date selected for \"{$svc['name']}\" is in the past.");
            }
            if ($time !== null && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
                throw new Exception("The time selected for \"{$svc['name']}\" is invalid.");
            }
        } else {
            // Per-item goods (minibar, snacks, skincare, etc.) carry a stock ceiling instead.
            if ($svc['stock'] !== null && $qty > (int)$svc['stock']) {
                throw new Exception("Only {$svc['stock']} unit(s) of \"{$svc['name']}\" are available.");
            }
        }

        $unitPrice = $tier === 'vip' ? (float)$svc['vip_price'] : (float)$svc['base_price'];

        // ── Validate and re-price add-ons server-side (never trust client-sent prices) ──
        $validAddons = [];
        if (!empty($addonIds)) {
            $placeholders = implode(',', array_fill(0, count($addonIds), '?'));
            $types = str_repeat('i', count($addonIds));
            $aStmt = $conn->prepare("SELECT * FROM service_addons WHERE service_id = ? AND active = 1 AND id IN ($placeholders)");
            $aStmt->bind_param('i' . $types, $serviceId, ...$addonIds);
            $aStmt->execute();
            $validAddons = $aStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            if (count($validAddons) !== count($addonIds)) {
                throw new Exception("One or more add-ons for \"{$svc['name']}\" are no longer available.");
            }
        }
        $addonUnitTotal = array_reduce($validAddons, function ($carry, $a) use ($tier) {
            return $carry + ($tier === 'vip' ? (float)$a['vip_price'] : (float)$a['price']);
        }, 0.0);

        $lineTotal = ($unitPrice + $addonUnitTotal) * $qty;
        $total += $lineTotal;

        $ins = $conn->prepare("INSERT INTO service_orders
            (order_ref, user_id, service_id, quantity, scheduled_date, scheduled_time, tier_applied, unit_price, total_price, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $ins->bind_param("siiisssdd", $ref, $userId, $serviceId, $qty, $date, $time, $tier, $unitPrice, $lineTotal);
        $ins->execute();
        $orderId = $ins->insert_id;

        // Record the exact add-ons chosen for this line, at their locked-in price
        if (!empty($validAddons)) {
            $addonIns = $conn->prepare("INSERT INTO service_order_addons (order_id, addon_id, name, price) VALUES (?, ?, ?, ?)");
            foreach ($validAddons as $a) {
                $aPrice = $tier === 'vip' ? (float)$a['vip_price'] : (float)$a['price'];
                $addonIns->bind_param("iisd", $orderId, $a['id'], $a['name'], $aPrice);
                $addonIns->execute();
            }
        }
    }
    $conn->commit();
    echo json_encode(['ok' => true, 'ref' => $ref, 'total' => $total]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}