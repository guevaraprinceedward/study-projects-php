<?php
ob_start();
include_once 'hotel-config.php';
include_once 'receipt-helpers.php';

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
$stmt = $conn->prepare("SELECT id, status FROM bookings WHERE id = ? AND user_id = ? LIMIT 1");
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

$receipt = buildReceiptData($conn, $bookingId, $userId);

if (!$receipt) {
    echo json_encode(['ok' => false, 'error' => 'Checked out, but the receipt could not be generated.']);
    exit;
}

echo json_encode(['ok' => true, 'receipt' => $receipt]);