<?php
// Same defensive pattern as process-reservation.php / process-service-order.php:
// buffer everything so a stray notice/warning from an include never leaks
// into the JSON body and breaks the frontend's fetch().json() parse.
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
    echo json_encode(['ok' => false, 'error' => 'Please log in to order for your room.']);
    exit;
}

$userId = $_SESSION['user']['id'];
$tier   = currentTier();
$today  = date('Y-m-d');

$input      = json_decode(file_get_contents('php://input'), true);
$bookingId  = (int)($input['booking_id'] ?? 0);
$forDate    = $input['for_date'] ?? $today;
$items      = $input['items'] ?? [];

if (!$bookingId) {
    echo json_encode(['ok' => false, 'error' => 'Missing booking reference.']);
    exit;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $forDate)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid date.']);
    exit;
}
if (!is_array($items) || empty($items)) {
    echo json_encode(['ok' => false, 'error' => 'Please choose at least one item.']);
    exit;
}
if (count($items) > 30) {
    echo json_encode(['ok' => false, 'error' => 'That order has too many items — please split it up.']);
    exit;
}

// ── This booking must belong to the guest, and be an active stay ────────
$stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->bind_param("ii", $bookingId, $userId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    echo json_encode(['ok' => false, 'error' => 'Booking not found.']);
    exit;
}
if (!in_array($booking['status'], ['pending', 'confirmed', 'checked_in'])) {
    echo json_encode(['ok' => false, 'error' => 'This stay is no longer active, so room orders are closed.']);
    exit;
}
// Requested serving date should fall within (or right up to) the stay window.
if ($forDate < $booking['checkin_date'] || $forDate > $booking['checkout_date']) {
    echo json_encode(['ok' => false, 'error' => 'Please choose a date within your stay.']);
    exit;
}

$conn->begin_transaction();
try {
    // One shared reference identifies "this order" to the guest, but every
    // row in service_orders must have its own UNIQUE order_ref — so each
    // line item gets the base ref plus a running suffix (DN-XXXXXX-01, -02, ...).
    $baseRef = generateRef('DN');
    $addedItems = [];
    $extraCharge = 0.00; // only champagne carries a charge; food & drinks are complimentary
    $lineNumber = 0;

    foreach ($items as $it) {
        $lineNumber++;
        $ref = $baseRef . '-' . str_pad($lineNumber, 2, '0', STR_PAD_LEFT);
        $serviceId = (int)($it['id'] ?? 0);
        $qty       = max(1, min(10, (int)($it['qty'] ?? 1)));
        if (!$serviceId) continue;

        // Lock the row so limited-stock champagne can't be oversold by a
        // concurrent request.
        $stmt = $conn->prepare("SELECT * FROM services WHERE id = ? AND active = 1 AND category = 'dining' FOR UPDATE");
        $stmt->bind_param("i", $serviceId);
        $stmt->execute();
        $svc = $stmt->get_result()->fetch_assoc();
        if (!$svc) {
            throw new Exception('One of the selected items is no longer available.');
        }

        if ($svc['type'] === 'champagne' && $svc['stock'] !== null && $qty > (int)$svc['stock']) {
            throw new Exception("Only {$svc['stock']} bottle(s) of \"{$svc['name']}\" are available.");
        }

        // Food and drinks are always complimentary regardless of the listed
        // price column; only champagne is a paid add-on.
        $unitPrice = ($svc['type'] === 'champagne')
            ? ($tier === 'vip' ? (float)$svc['vip_price'] : (float)$svc['base_price'])
            : 0.00;
        $lineTotal = $unitPrice * $qty;
        $extraCharge += $lineTotal;

        $ins = $conn->prepare("INSERT INTO service_orders
            (order_ref, user_id, booking_id, service_id, quantity, scheduled_date, tier_applied, unit_price, total_price, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $ins->bind_param("siiiissdd", $ref, $userId, $bookingId, $serviceId, $qty, $forDate, $tier, $unitPrice, $lineTotal);
        $ins->execute();

        // Decrement stock for limited champagne bottles.
        if ($svc['type'] === 'champagne' && $svc['stock'] !== null) {
            $upd = $conn->prepare("UPDATE services SET stock = stock - ? WHERE id = ?");
            $upd->bind_param("ii", $qty, $serviceId);
            $upd->execute();
        }

        $addedItems[] = [
            'name' => $svc['name'],
            'type' => $svc['type'],
            'qty'  => $qty,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
        ];
    }

    if (empty($addedItems)) {
        throw new Exception('Please choose at least one item.');
    }

    $conn->commit();
    echo json_encode([
        'ok' => true,
        'ref' => $baseRef,
        'for_date' => $forDate,
        'items' => $addedItems,
        'extra_charge' => $extraCharge,
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}