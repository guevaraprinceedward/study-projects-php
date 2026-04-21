<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["user"])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

include 'config.php';
header('Content-Type: application/json');

// Session validation
$uid   = (int)($_SESSION["user"]["id"] ?? 0);
$uname = $conn->real_escape_string($_SESSION["user"]["username"] ?? '');
$chk   = $conn->query("SELECT id FROM users WHERE id = $uid AND username = '$uname' LIMIT 1");
if (!$chk || $chk->num_rows === 0) {
    session_unset();
    session_destroy();
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit();
}

// ── ADD TO CART ───────────────────────────────────────────────────────────
if (isset($_GET['add'])) {
    $id = (int)$_GET['add'];

    // Check kung nag-eexist ang product
    $check   = $conn->query("SELECT id FROM products WHERE id = $id");
    $product = $check ? $check->fetch_assoc() : null;

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }

    // Ilang units na nai-order ng current user para sa product na ito
    $orderedRes = $conn->query("
        SELECT COALESCE(SUM(oi.quantity), 0) AS total_ordered
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE oi.product_id = $id
        AND o.user_id = $uid
        AND o.status != 'cancelled'
    ");
    $totalOrdered = (int)($orderedRes->fetch_assoc()['total_ordered'] ?? 0);

    // Ilang units na nasa cart ng current user
    $currentCartQty = (int)($_SESSION['cart'][$id] ?? 0);

    // Total na ginamit = orders + cart
    $totalUsed = $totalOrdered + $currentCartQty;
    $maxStock  = 100;

    if ($totalUsed >= $maxStock) {
        echo json_encode([
            'success' => false,
            'message' => 'You have reached your maximum stock for this item'
        ]);
        exit();
    }

    // Dagdagan ng isa sa cart
    $_SESSION['cart'][$id] = $currentCartQty + 1;

    // Per-user remaining stock
    $userStock = $maxStock - $totalUsed - 1;

    echo json_encode([
        'success' => true,
        'qty'     => $_SESSION['cart'][$id],
        'count'   => array_sum($_SESSION['cart']),
        'stock'   => $userStock
    ]);
    exit();
}

// ── REMOVE FROM CART ──────────────────────────────────────────────────────
if (isset($_GET['remove'])) {
    $id = (int)$_GET['remove'];
    unset($_SESSION['cart'][$id]);
    echo json_encode([
        'success' => true,
        'count'   => array_sum($_SESSION['cart'] ?? [])
    ]);
    exit();
}

// ── COUNT ─────────────────────────────────────────────────────────────────
if (isset($_GET['count'])) {
    echo json_encode([
        'count' => array_sum($_SESSION['cart'] ?? [])
    ]);
    exit();
}

echo json_encode(['error' => 'Unknown action']);