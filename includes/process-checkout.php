<?php
// Buffer everything so any stray warning/notice never leaks into the response
// and breaks the JSON the frontend expects.
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
    echo json_encode(['ok' => false, 'error' => 'Please log in to continue.']);
    exit;
}

$userId    = $_SESSION['user']['id'];
$bookingId = (int)($_POST['booking_id'] ?? 0);

if (!$bookingId) {
    echo json_encode(['ok' => false, 'error' => 'Invalid booking.']);
    exit;
}

// Confirm this booking belongs to the logged-in guest and is still active
$stmt = $conn->prepare("SELECT b.*, s.name AS suite_name
                         FROM bookings b
                         JOIN suites s ON s.id = b.suite_id
                         WHERE b.id = ? AND b.user_id = ? LIMIT 1");
$stmt->bind_param("ii", $bookingId, $userId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    echo json_encode(['ok' => false, 'error' => 'Booking not found.']);
    exit;
}
if ($booking['status'] === 'checked_out') {
    echo json_encode(['ok' => false, 'error' => 'This booking is already checked out.']);
    exit;
}
if ($booking['status'] === 'cancelled') {
    echo json_encode(['ok' => false, 'error' => 'This booking was cancelled.']);
    exit;
}

// Mark the booking — and every room tied to it — as checked out
$upd = $conn->prepare("UPDATE bookings SET status = 'checked_out' WHERE id = ?");
$upd->bind_param("i", $bookingId);
$upd->execute();

$updRooms = $conn->prepare("UPDATE booking_rooms SET checked_out_at = NOW() WHERE booking_id = ? AND checked_out_at IS NULL");
$updRooms->bind_param("i", $bookingId);
$updRooms->execute();

// Room numbers assigned to this booking
$roomNumbers = [];
$rr = $conn->prepare("SELECT r.room_number FROM booking_rooms br
                       JOIN rooms r ON r.id = br.room_id
                       WHERE br.booking_id = ? ORDER BY r.room_number ASC");
$rr->bind_param("i", $bookingId);
$rr->execute();
$rres = $rr->get_result();
while ($row = $rres->fetch_assoc()) $roomNumbers[] = $row['room_number'];

// Every dining / drinks / champagne order placed during this stay
$diningItems = [];
$diningTotal = 0.00;
$dr = $conn->prepare("SELECT so.scheduled_date, so.quantity, so.total_price, s.name, s.type
                       FROM service_orders so JOIN services s ON s.id = so.service_id
                       WHERE so.booking_id = ?
                       ORDER BY so.scheduled_date ASC, s.type ASC");
$dr->bind_param("i", $bookingId);
$dr->execute();
$dres = $dr->get_result();
while ($row = $dres->fetch_assoc()) {
    $diningItems[] = $row;
    $diningTotal += (float)$row['total_price'];
}

$roomTotal = (float)$booking['total_price'];

$receipt = [
    'booking_ref'     => $booking['booking_ref'],
    'guest_name'      => $booking['guest_name'],
    'guest_tier'      => $booking['tier_applied'],
    'suite_name'      => $booking['suite_name'],
    'room_numbers'    => $roomNumbers,
    'checkin_date'    => $booking['checkin_date'],
    'checkout_date'   => $booking['checkout_date'],
    'nights'          => (int)$booking['nights'],
    'nightly_rate'    => (float)$booking['nightly_rate'],
    'quantity'        => (int)$booking['quantity'],
    'subtotal'        => (float)$booking['subtotal'],
    'discount_amount' => (float)$booking['discount_amount'],
    'room_total'      => $roomTotal,
    'dining_items'    => $diningItems,
    'dining_total'    => round($diningTotal, 2),
    'grand_total'     => round($roomTotal + $diningTotal, 2),
];

echo json_encode(['ok' => true, 'receipt' => $receipt]);