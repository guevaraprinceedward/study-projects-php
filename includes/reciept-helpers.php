<?php
/**
 * Builds the full receipt data for a booking: room charges, every dining /
 * champagne order placed during the stay, and the grand total. Used by both
 * process-checkout.php (right after checkout) and get-receipt.php (viewing
 * a past receipt anytime later from the dashboard).
 *
 * Returns null if the booking doesn't exist or doesn't belong to this user.
 */
function buildReceiptData(mysqli $conn, int $bookingId, int $userId): ?array {
    $stmt = $conn->prepare("SELECT b.*, s.name AS suite_name, ts.label AS slot_label
                             FROM bookings b
                             JOIN suites s ON s.id = b.suite_id
                             JOIN time_slots ts ON ts.id = b.time_slot_id
                             WHERE b.id = ? AND b.user_id = ? LIMIT 1");
    $stmt->bind_param("ii", $bookingId, $userId);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    if (!$booking) return null;

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

    return [
        'booking_ref'     => $booking['booking_ref'],
        'guest_name'      => $booking['guest_name'],
        'suite_name'      => $booking['suite_name'],
        'slot_label'      => $booking['slot_label'],
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
        'status'          => $booking['status'],
    ];
}