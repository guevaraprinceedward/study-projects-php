<?php
// Buffer everything so any stray warning/notice text (or accidental whitespace
// from an included file) never leaks into the response and breaks the JSON.
ob_start();

include_once 'hotel-config.php';

// Turn PHP errors/warnings/fatals into a clean JSON response instead of raw
// HTML, so the frontend's fetch().json() never fails to parse the reply.
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
    echo json_encode(['ok' => false, 'error' => 'Please log in or create an account to complete your reservation.']);
    exit;
}

$suiteId    = (int)($_POST['suite_id'] ?? 0);
$checkin    = $_POST['checkin_date'] ?? '';
$checkout   = $_POST['checkout_date'] ?? '';
$slotId     = (int)($_POST['time_slot_id'] ?? 0);
$guests     = max(1, (int)($_POST['guests_count'] ?? 1));

// room_ids[] arrives as an array (multiple checkboxes with the same name)
$roomIds = $_POST['room_ids'] ?? [];
if (!is_array($roomIds)) $roomIds = [$roomIds];
$roomIds = array_values(array_unique(array_filter(array_map('intval', $roomIds))));
$quantity = count($roomIds);

// ── Basic validation ─────────────────────────────────────────────────
if (!$suiteId || !$checkin || !$checkout || !$slotId) {
    echo json_encode(['ok' => false, 'error' => 'Missing or invalid reservation details.']);
    exit;
}
if ($quantity < 1) {
    echo json_encode(['ok' => false, 'error' => 'Please select at least one room.']);
    exit;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkin) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkout)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid date format.']);
    exit;
}
if ($checkout <= $checkin) {
    echo json_encode(['ok' => false, 'error' => 'Check-out date must be after check-in date.']);
    exit;
}
if ($checkin < date('Y-m-d')) {
    echo json_encode(['ok' => false, 'error' => 'Check-in date cannot be in the past.']);
    exit;
}

$userId = $_SESSION['user']['id'];
$tier   = currentTier();

// ── Fetch suite ──────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM suites WHERE id = ?");
$stmt->bind_param("i", $suiteId);
$stmt->execute();
$suite = $stmt->get_result()->fetch_assoc();
if (!$suite) {
    echo json_encode(['ok' => false, 'error' => 'Selected residence could not be found.']);
    exit;
}
if ($guests > (int)$suite['max_guests']) {
    echo json_encode(['ok' => false, 'error' => 'Guest count exceeds the maximum for this residence.']);
    exit;
}

// ── Fetch time slot ──────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM time_slots WHERE id = ?");
$stmt->bind_param("i", $slotId);
$stmt->execute();
$slot = $stmt->get_result()->fetch_assoc();
if (!$slot) {
    echo json_encode(['ok' => false, 'error' => 'Selected check-in window could not be found.']);
    exit;
}

// ── Verify every submitted room really belongs to this suite ──────────
$placeholders = implode(',', array_fill(0, $quantity, '?'));
$types = str_repeat('i', $quantity);
$stmt = $conn->prepare("SELECT id, room_number FROM rooms WHERE suite_id = ? AND status = 'available' AND id IN ($placeholders)");
$stmt->bind_param('i' . $types, $suiteId, ...$roomIds);
$stmt->execute();
$validRooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
if (count($validRooms) !== $quantity) {
    echo json_encode(['ok' => false, 'error' => 'One or more selected rooms are no longer valid — please refresh and try again.']);
    exit;
}

// ── Price calculation (server-side, source of truth) ──────────────────
$nights = (int) round((strtotime($checkout) - strtotime($checkin)) / 86400);
$baseRate = $tier === 'vip' ? (float)$suite['vip_price'] : (float)$suite['base_price'];
$nightlyRate = $baseRate * (float)$slot['price_modifier'];
$subtotal = $nightlyRate * $nights * $quantity;

// ── Best applicable offer ────────────────────────────────────────────
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT * FROM offers
                         WHERE active = 1 AND valid_from <= ? AND valid_to >= ?
                         AND (audience = 'all' OR audience = ?)
                         AND (suite_id IS NULL OR suite_id = ?)
                         AND min_nights <= ?
                         ORDER BY discount_percent DESC LIMIT 1");
$stmt->bind_param("sssii", $today, $today, $tier, $suiteId, $nights);
$stmt->execute();
$offer = $stmt->get_result()->fetch_assoc();

$discountAmount = 0.00;
$offerId = null;
if ($offer) {
    $discountAmount = round($subtotal * ((float)$offer['discount_percent'] / 100), 2);
    $offerId = (int)$offer['id'];
}
$total = round($subtotal - $discountAmount, 2);

// ── Guest contact info from session ──────────────────────────────────
$guestName  = $_SESSION['user']['name'] ?? '';
$guestEmail = $_SESSION['user']['email'] ?? '';
$ref        = generateRef('NB');

// ── Insert booking + lock the specific rooms to prevent a double-booking race ──
$conn->begin_transaction();
try {
    // Lock the specific room rows themselves — this serializes any concurrent
    // attempt to book the same physical room for overlapping dates.
    $lock = $conn->prepare("SELECT id FROM rooms WHERE id IN ($placeholders) FOR UPDATE");
    $lock->bind_param($types, ...$roomIds);
    $lock->execute();
    // IMPORTANT: the result set must be consumed (even though we don't need
    // the rows) or the connection is left "out of sync" for the next query.
    $lock->get_result();

    // Now that we hold the lock, re-check for a conflicting active booking on these exact rooms
    $conflictStmt = $conn->prepare("SELECT DISTINCT br.room_id FROM booking_rooms br
                                     JOIN bookings b ON b.id = br.booking_id
                                     WHERE br.room_id IN ($placeholders)
                                     AND b.status IN ('pending','confirmed','checked_in')
                                     AND b.checkin_date < ? AND b.checkout_date > ?");
    $conflictStmt->bind_param($types . 'ss', ...[...$roomIds, $checkout, $checkin]);
    $conflictStmt->execute();
    $conflicts = $conflictStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (!empty($conflicts)) {
        throw new Exception('One or more of your selected rooms were just booked by another guest — please pick different rooms.');
    }

    $ins = $conn->prepare("INSERT INTO bookings
        (booking_ref, user_id, guest_name, guest_email, suite_id, time_slot_id,
         checkin_date, checkout_date, quantity, guests_count, tier_applied,
         offer_id, nightly_rate, nights, subtotal, discount_amount, total_price, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");

    $ins->bind_param(
        "sissiissiisididdd",
        $ref, $userId, $guestName, $guestEmail, $suiteId, $slotId,
        $checkin, $checkout, $quantity, $guests, $tier,
        $offerId, $nightlyRate, $nights, $subtotal, $discountAmount, $total
    );
    $ins->execute();
    $bookingId = $ins->insert_id;

    // Record exactly which physical rooms this booking occupies, and who
    // (by name) is the guest checking into each of them.
    $roomIns = $conn->prepare("INSERT INTO booking_rooms (booking_id, room_id, guest_name) VALUES (?, ?, ?)");
    foreach ($roomIds as $rid) {
        $roomIns->bind_param("iis", $bookingId, $rid, $guestName);
        $roomIns->execute();
    }

    $conn->commit();

    // Build the room + occupant list for the notification modal
    $roomDetails = array_map(fn($r) => [
        'room_number' => $r['room_number'],
        'guest_name'  => $guestName,
    ], $validRooms);
    usort($roomDetails, fn($a, $b) => strcmp($a['room_number'], $b['room_number']));

    echo json_encode([
        'ok' => true,
        'ref' => $ref,
        'booking_id' => $bookingId,
        'rooms' => $roomDetails,
        'nights' => $nights,
        'nightly_rate' => $nightlyRate,
        'subtotal' => $subtotal,
        'discount' => $discountAmount,
        'total' => $total,
        'offer_title' => $offer['title'] ?? null,
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}