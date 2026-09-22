<?php
/**
 * process-meal-order.php
 * -------------------------------------------------------------------------
 * Handles "Send Order to Room" from the Dining & Champagne panel on
 * dashboard.php (My Bookings). Called via fetch() with a JSON body:
 *   { "booking_id": 12, "for_date": "2026-09-14", "items": [ { "id": 3, "qty": 2 }, ... ] }
 *
 * FIX HISTORY:
 * Previously failed with "Unknown column 'user_id' in field list" because
 * an earlier version of this file tried to INSERT (or SELECT) a `user_id`
 * column directly on `service_orders`. The real schema is:
 *   id, booking_id, service_id, guest_name, scheduled_date, quantity,
 *   total_price, status, created_at
 * There is no user_id column on service_orders — ownership is established
 * indirectly, through booking_id -> bookings.user_id. This file verifies
 * that link explicitly (see "OWNERSHIP CHECK" below) instead of ever
 * trying to store the guest's user id on the order row itself.
 */

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

// ── Must be logged in ──────────────────────────────────────────────────
if (!isHotelLoggedIn()) {
    echo json_encode(['ok' => false, 'error' => 'Please log in to continue.']);
    exit;
}
$userId    = (int)$_SESSION['user']['id'];
$guestName = $_SESSION['user']['name'];
$tier      = currentTier();

// ── Parse the request body ───────────────────────────────────────────────
$payload   = json_decode(file_get_contents('php://input'), true);
$bookingId = (int)($payload['booking_id'] ?? 0);
$forDate   = $payload['for_date'] ?? '';
$items     = $payload['items'] ?? null;

if (!$bookingId) {
    echo json_encode(['ok' => false, 'error' => 'Invalid booking.']);
    exit;
}
if (!$forDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $forDate)) {
    echo json_encode(['ok' => false, 'error' => 'Please choose a valid serve date.']);
    exit;
}
if (!is_array($items) || count($items) === 0) {
    echo json_encode(['ok' => false, 'error' => 'Please choose at least one item first.']);
    exit;
}

// ── OWNERSHIP CHECK — this is how we know the order belongs to this guest,
//    since service_orders itself has no user_id column to check directly. ──
$bStmt = $conn->prepare("SELECT id, status, checkin_date, checkout_date FROM bookings WHERE id = ? AND user_id = ? LIMIT 1");
$bStmt->bind_param("ii", $bookingId, $userId);
$bStmt->execute();
$booking = $bStmt->get_result()->fetch_assoc();

if (!$booking) {
    echo json_encode(['ok' => false, 'error' => 'Booking not found.']);
    exit;
}
if (!in_array($booking['status'], ['pending', 'confirmed', 'checked_in'])) {
    echo json_encode(['ok' => false, 'error' => 'This booking is no longer active.']);
    exit;
}
if ($forDate < $booking['checkin_date'] || $forDate > $booking['checkout_date']) {
    echo json_encode(['ok' => false, 'error' => 'The serve date must fall within your stay.']);
    exit;
}

// ── Process each item ────────────────────────────────────────────────────
$conn->begin_transaction();

try {
    $svcStmt = $conn->prepare("SELECT id, name, base_price, vip_price, stock, active, category FROM services WHERE id = ? LIMIT 1");
    $insStmt = $conn->prepare("
        INSERT INTO service_orders (booking_id, service_id, guest_name, scheduled_date, quantity, total_price, status)
        VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ");

    $extraCharge = 0.00;
    $orderedCount = 0;

    foreach ($items as $item) {
        $serviceId = (int)($item['id'] ?? 0);
        $qty = max(1, min(10, (int)($item['qty'] ?? 0)));

        if ($serviceId <= 0 || $qty <= 0) continue; // skip anything malformed rather than fail the whole order

        $svcStmt->bind_param("i", $serviceId);
        $svcStmt->execute();
        $svc = $svcStmt->get_result()->fetch_assoc();

        if (!$svc || (int)$svc['active'] !== 1 || $svc['category'] !== 'dining') {
            throw new RuntimeException('One of the items you selected is no longer available.');
        }
        if ($svc['stock'] !== null && $qty > (int)$svc['stock']) {
            throw new RuntimeException('Only ' . (int)$svc['stock'] . ' left in stock for "' . $svc['name'] . '".');
        }

        // Price always comes from the database, never trusted from the browser.
        $unitPrice = $tier === 'vip' ? (float)$svc['vip_price'] : (float)$svc['base_price'];
        $lineTotal = round($unitPrice * $qty, 2);

        $insStmt->bind_param("iissid", $bookingId, $serviceId, $guestName, $forDate, $qty, $lineTotal);
        $insStmt->execute();

        $extraCharge += $lineTotal;
        $orderedCount++;
    }

    if ($orderedCount === 0) {
        throw new RuntimeException('Please choose at least one item first.');
    }

    $conn->commit();

    echo json_encode([
        'ok' => true,
        'extra_charge' => round($extraCharge, 2),
        'count' => $orderedCount,
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}