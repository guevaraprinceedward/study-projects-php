<?php
/**
 * reservation_handler.php (v3) — JSON endpoint for the RESERVATION cart (line-based).
 *   GET  ?action=count            -> { count }
 *   GET  ?action=add&id=12        -> quick add with default choices (menu now uses customize_handler.php)
 *   POST action=cancel&id=7       -> cancel an unpaid reservation and put the held stock back
 */
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';
include_once 'customize-lib.php';
include_once 'reservation-init.php';

header('Content-Type: application/json');

if (!isset($_SESSION['res_cart']) || !is_array($_SESSION['res_cart'])) $_SESSION['res_cart'] = [];
custMigrateCart($_SESSION['res_cart']);

$action = $_REQUEST['action'] ?? '';

if ($action === 'count') {
    echo json_encode(['count' => custCartCount($_SESSION['res_cart'])]);
    exit();
}

if ($action === 'add') {
    echo json_encode(custAddToCart($conn, 'res_cart', ['product_id' => (int)($_REQUEST['id'] ?? 0), 'qty' => 1], true));
    exit();
}

if ($action === 'cancel' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = resValidUserId($conn);
    $id  = (int)($_POST['id'] ?? 0);
    $res = resLoadReservation($conn, $id);

    if (!$res || !resCanAccess($res, $uid)) {
        echo json_encode(['success' => false, 'message' => 'Reservation not found.']);
        exit();
    }

    $conn->begin_transaction();
    try {
        $st = $conn->prepare("SELECT status, payment_status FROM reservations WHERE id = ? FOR UPDATE");
        $st->bind_param('i', $id);
        $st->execute();
        $cur = $st->get_result()->fetch_assoc();
        $st->close();

        if (!$cur || $cur['status'] !== 'reserved' || $cur['payment_status'] !== 'unpaid') {
            throw new Exception('Only unpaid reservations can be cancelled. For paid ones, please contact the branch.');
        }

        $restore = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        foreach ($res['items'] as $it) {
            $q = (int)$it['quantity'];
            $p = (int)$it['product_id'];
            $restore->bind_param('ii', $q, $p);
            $restore->execute();
        }
        $restore->close();

        $st = $conn->prepare("UPDATE reservations SET status = 'cancelled', cancelled_at = NOW() WHERE id = ?");
        $st->bind_param('i', $id);
        $st->execute();
        $st->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Reservation cancelled.']);
    } catch (Throwable $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

echo json_encode(['error' => 'Unknown action']);
exit();