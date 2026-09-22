<?php
include_once 'hotel-config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: dashboard.php"); exit; }
requireHotelLogin('dashboard.php');

$userId      = $_SESSION['user']['id'];
$bookingId   = (int)($_POST['booking_id'] ?? 0);
$extraNights = max(1, min(14, (int)($_POST['extra_nights'] ?? 1)));

function backWithError(string $msg) {
    $_SESSION['dash_error'] = $msg;
    header("Location: dashboard.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->bind_param("ii", $bookingId, $userId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) backWithError("Booking not found.");
if (!in_array($booking['status'], ['pending','confirmed','checked_in'])) {
    backWithError("This booking can no longer be extended.");
}

$oldCheckout = $booking['checkout_date'];
$newCheckout = date('Y-m-d', strtotime($oldCheckout . " +$extraNights day"));

// ── Re-verify the specific rooms (if tracked) stay free for the extension window ──
$roomIds = [];
$rr = $conn->query("SELECT room_id FROM booking_rooms WHERE booking_id = " . (int)$bookingId);
if ($rr) { while ($row = $rr->fetch_assoc()) $roomIds[] = (int)$row['room_id']; }

if (!empty($roomIds)) {
    $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
    $types = str_repeat('i', count($roomIds));
    $sql = "SELECT DISTINCT br.room_id FROM booking_rooms br
            JOIN bookings b ON b.id = br.booking_id
            WHERE br.room_id IN ($placeholders) AND br.booking_id != ?
            AND b.status IN ('pending','confirmed','checked_in')
            AND b.checkin_date < ? AND b.checkout_date > ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types . "iss", ...[...$roomIds, $bookingId, $newCheckout, $oldCheckout]);
    $stmt->execute();
    $conflicts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if (!empty($conflicts)) {
        backWithError("Sorry, your room is already booked by another guest right after your current check-out date, so it can't be extended. Please check out as planned or contact the front desk.");
    }
} else {
    // Fallback: check aggregate room availability for the suite over the extension window
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM rooms WHERE suite_id = ? AND status = 'available'");
    $stmt->bind_param("i", $booking['suite_id']);
    $stmt->execute();
    $totalRooms = (int)$stmt->get_result()->fetch_assoc()['c'];

    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) q FROM bookings
                             WHERE suite_id = ? AND id != ? AND status IN ('pending','confirmed','checked_in')
                             AND checkin_date < ? AND checkout_date > ?");
    $stmt->bind_param("iiss", $booking['suite_id'], $bookingId, $newCheckout, $oldCheckout);
    $stmt->execute();
    $bookedElsewhere = (int)$stmt->get_result()->fetch_assoc()['q'];

    if ($bookedElsewhere + (int)$booking['quantity'] > $totalRooms) {
        backWithError("Sorry, this residence is fully booked right after your current check-out date, so it can't be extended.");
    }
}

// ── Compute extra payment using the ORIGINAL locked-in nightly rate ─────
$extraCost = round((float)$booking['nightly_rate'] * (int)$booking['quantity'] * $extraNights, 2);
$newNights = (int)$booking['nights'] + $extraNights;
$newSubtotal = (float)$booking['subtotal'] + $extraCost;
$newTotal = (float)$booking['total_price'] + $extraCost;

$stmt = $conn->prepare("UPDATE bookings SET checkout_date = ?, nights = ?, subtotal = ?, total_price = ? WHERE id = ?");
$stmt->bind_param("siddi", $newCheckout, $newNights, $newSubtotal, $newTotal, $bookingId);
$stmt->execute();

$_SESSION['dash_notice'] = "Stay extended by $extraNights night(s) — new check-out date is " .
    date('M j, Y', strtotime($newCheckout)) . ". An additional ₱" . number_format($extraCost, 2) . " was charged.";
header("Location: dashboard.php");
exit;