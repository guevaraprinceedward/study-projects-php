<?php
/**
 * reservation_handler.php
 * -------------------------------------------------------------------------
 * JSON endpoint for the RESERVATION cart (separate from the serve cart that
 * cart_handler.php manages, so the two never mix).
 *
 *   GET  ?action=count                     -> { count }
 *   GET  ?action=add&id=12                 -> { success, qty, count, stock }
 *   POST action=addons&item_id=12&addons=[1,2]  -> { success }
 *   POST action=cancel&id=7                -> { success, message }
 *
 * Session keys: $_SESSION['res_cart'][productId] = qty
 *               $_SESSION['res_cart_addons'][productId] = '[addonId,...]'
 */
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';
include_once 'reservation-init.php';

header('Content-Type: application/json');

if (!isset($_SESSION['res_cart']) || !is_array($_SESSION['res_cart']))               $_SESSION['res_cart'] = [];
if (!isset($_SESSION['res_cart_addons']) || !is_array($_SESSION['res_cart_addons'])) $_SESSION['res_cart_addons'] = [];

$action = $_REQUEST['action'] ?? '';

// ── COUNT ─────────────────────────────────────────────────────────────────
if ($action === 'count') {
    echo json_encode(['count' => array_sum($_SESSION['res_cart'])]);
    exit();
}

// ── ADD ───────────────────────────────────────────────────────────────────
if ($action === 'add') {
    $id = (int)($_REQUEST['id'] ?? 0);

    $st = $conn->prepare("SELECT stock, branch FROM products WHERE id = ?");
    $st->bind_param('i', $id);
    $st->execute();
    $product = $st->get_result()->fetch_assoc();
    $st->close();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }

    // One reservation = one branch. Block mixing Laguna + Manila items.
    $cartIds = array_map('intval', array_keys($_SESSION['res_cart']));
    $cartIds = array_values(array_filter($cartIds, fn($x) => $x !== $id));
    if (!empty($cartIds)) {
        $idList = implode(',', $cartIds);
        $b = $conn->query("SELECT branch FROM products WHERE id IN ($idList) LIMIT 1");
        $existing = $b ? $b->fetch_assoc() : null;
        if ($existing && $existing['branch'] !== $product['branch']) {
            echo json_encode([
                'success' => false,
                'message' => 'Your reservation already has ' . ucfirst($existing['branch']) . ' items. One reservation can only be for one branch.',
            ]);
            exit();
        }
    }

    $actualStock    = (int)$product['stock'];
    $currentCartQty = (int)($_SESSION['res_cart'][$id] ?? 0);

    if ($actualStock <= 0 || $currentCartQty >= $actualStock) {
        echo json_encode(['success' => false, 'message' => 'Out of stock']);
        exit();
    }

    $_SESSION['res_cart'][$id] = $currentCartQty + 1;

    echo json_encode([
        'success' => true,
        'qty'     => $_SESSION['res_cart'][$id],
        'count'   => array_sum($_SESSION['res_cart']),
        'stock'   => $actualStock - $_SESSION['res_cart'][$id],
    ]);
    exit();
}

// ── ADD-ONS (per cart line) ───────────────────────────────────────────────
if ($action === 'addons' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = (int)($_POST['item_id'] ?? 0);
    if (!isset($_SESSION['res_cart'][$itemId])) {
        echo json_encode(['success' => false, 'message' => 'Item is not in your reservation cart.']);
        exit();
    }
    $ids = json_decode($_POST['addons'] ?? '[]', true);
    if (!is_array($ids)) $ids = [];
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($x) => $x > 0)));

    $valid = [];
    if (!empty($ids)) {
        $idList = implode(',', $ids);
        $r = $conn->query("SELECT id FROM product_addons WHERE id IN ($idList) AND is_active = 1");
        if ($r) while ($row = $r->fetch_assoc()) $valid[] = (int)$row['id'];
    }
    $_SESSION['res_cart_addons'][$itemId] = json_encode($valid);
    echo json_encode(['success' => true, 'addons' => $valid]);
    exit();
}

// ── CANCEL (unpaid reservation -> stock goes back on the shelf) ───────────
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
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

echo json_encode(['error' => 'Unknown action']);
exit();