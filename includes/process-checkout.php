<?php
include_once 'hotel-config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: dashboard.php"); exit; }
requireHotelLogin('dashboard.php');

$userId    = $_SESSION['user']['id'];
$bookingId = (int)($_POST['booking_id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->bind_param("ii", $bookingId, $userId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    $_SESSION['dash_error'] = "Booking not found.";
    header("Location: dashboard.php");
    exit;
}

if (!in_array($booking['status'], ['pending','confirmed','checked_in'])) {
    $_SESSION['dash_error'] = "This booking is already closed.";
    header("Location: dashboard.php");
    exit;
}

$today = date('Y-m-d');
// If checking out early, shorten the stay to today; otherwise keep the planned date.
$finalCheckout = $booking['checkout_date'] > $today ? $today : $booking['checkout_date'];

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("UPDATE bookings SET status = 'checked_out', checkout_date = ? WHERE id = ?");
    $stmt->bind_param("si", $finalCheckout, $bookingId);
    $stmt->execute();

    // Stamp the exact checkout time on every physical room tied to this booking.
    $stmt = $conn->prepare("UPDATE booking_rooms SET checked_out_at = NOW() WHERE booking_id = ? AND checked_out_at IS NULL");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['dash_error'] = "We couldn't complete the check-out. Please try again.";
    header("Location: dashboard.php");
    exit;
}

$_SESSION['dash_notice'] = "You have been checked out of booking " . $booking['booking_ref'] . ". Thank you for staying at Nocturne Manila Bay.";
header("Location: dashboard.php");
exit;