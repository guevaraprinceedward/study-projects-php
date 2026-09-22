<?php
ob_start();
include_once 'hotel-config.php';
if (ob_get_length()) ob_clean();
header('Content-Type: application/json');

$suiteId  = (int)($_GET['suite_id'] ?? 0);
$checkin  = $_GET['checkin']  ?? '';
$checkout = $_GET['checkout'] ?? '';

if (!$suiteId || !$checkin || !$checkout || $checkout <= $checkin) {
    echo json_encode(['ok' => false, 'error' => 'Invalid parameters']);
    exit;
}

// All active rooms belonging to this suite
$stmt = $conn->prepare("SELECT id, room_number FROM rooms WHERE suite_id = ? AND status = 'available' ORDER BY room_number ASC");
$stmt->bind_param("i", $suiteId);
$stmt->execute();
$allRooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Room ids already taken by an overlapping active booking
$stmt2 = $conn->prepare("SELECT DISTINCT br.room_id FROM booking_rooms br
                          JOIN bookings b ON b.id = br.booking_id
                          WHERE b.suite_id = ? AND b.status IN ('pending','confirmed','checked_in')
                          AND b.checkin_date < ? AND b.checkout_date > ?");
$stmt2->bind_param("iss", $suiteId, $checkout, $checkin);
$stmt2->execute();
$takenIds = array_column($stmt2->get_result()->fetch_all(MYSQLI_ASSOC), 'room_id');

$availableRooms = array_values(array_filter($allRooms, fn($r) => !in_array((int)$r['id'], $takenIds)));

echo json_encode([
    'ok' => true,
    'total' => count($allRooms),
    'booked' => count($takenIds),
    'available' => count($availableRooms),
    'rooms' => $availableRooms, // [{id, room_number}, ...] — the specific rooms the guest can pick
]);