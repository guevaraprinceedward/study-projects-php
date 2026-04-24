<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ── COUNT ONLY ─────────────────────────────────────────────────────────────
if (isset($_GET['count'])) {
    echo json_encode(['count' => array_sum($_SESSION['cart'])]);
    exit();
}

// ── ADD TO CART ────────────────────────────────────────────────────────────
if (isset($_GET['add'])) {
    $id = (int)$_GET['add'];

    $check   = $conn->query("SELECT stock FROM products WHERE id = $id");
    $product = $check ? $check->fetch_assoc() : null;

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }

    $actualStock    = (int)$product['stock'];
    $currentCartQty = (int)($_SESSION['cart'][$id] ?? 0);

    if ($actualStock <= 0 || $currentCartQty >= $actualStock) {
        echo json_encode(['success' => false, 'message' => 'Out of stock']);
        exit();
    }

    $_SESSION['cart'][$id] = $currentCartQty + 1;

    echo json_encode([
        'success' => true,
        'qty'     => $_SESSION['cart'][$id],
        'count'   => array_sum($_SESSION['cart']),
        'stock'   => $actualStock - $currentCartQty - 1
    ]);
    exit();
}

echo json_encode(['error' => 'Unknown action']);
exit();
?>