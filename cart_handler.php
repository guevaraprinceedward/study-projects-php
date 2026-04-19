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

// ── ADD TO CART ───────────────────────────────────────────────────────────
// Hindi na babawasan ang stock dito — bawasan lang sa checkout
if (isset($_GET['add'])) {
    $id = (int)$_GET['add'];

    // Kunin ang actual stock mula sa DB
    $check   = $conn->query("SELECT stock FROM products WHERE id = $id");
    $product = $check ? $check->fetch_assoc() : null;

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }

    $actualStock = (int)$product['stock'];

    if ($actualStock <= 0) {
        echo json_encode(['success' => false, 'message' => 'Out of stock']);
        exit();
    }

    // Ilang qty na nandoon sa cart para sa product na ito
    $currentCartQty = (int)($_SESSION['cart'][$id] ?? 0);

    // Huwag payagan kung lampas na sa stock
    if ($currentCartQty >= $actualStock) {
        echo json_encode([
            'success' => false,
            'message' => 'Only ' . $actualStock . ' left in stock'
        ]);
        exit();
    }

    // Dagdagan ng isa sa cart
    $_SESSION['cart'][$id] = $currentCartQty + 1;

    echo json_encode([
        'success' => true,
        'qty'     => $_SESSION['cart'][$id],
        'count'   => array_sum($_SESSION['cart']),
        'stock'   => $actualStock  // ibabalik para ma-update ang UI sa index.php
    ]);
    exit();
}

// ── REMOVE FROM CART ──────────────────────────────────────────────────────
// Hindi na kailangang ibalik ang stock kasi hindi naman binawasan
if (isset($_GET['remove'])) {
    $id = (int)$_GET['remove'];
    unset($_SESSION['cart'][$id]);

    echo json_encode([
        'success' => true,
        'count'   => array_sum($_SESSION['cart'] ?? [])
    ]);
    exit();
}

// ── COUNT (para sa cart badge) ────────────────────────────────────────────
if (isset($_GET['count'])) {
    echo json_encode([
        'count' => array_sum($_SESSION['cart'] ?? [])
    ]);
    exit();
}

// ── GET STOCK (para malaman ng index.php ang actual stock) ────────────────
if (isset($_GET['stock'])) {
    $id    = (int)$_GET['stock'];
    $check = $conn->query("SELECT stock FROM products WHERE id = $id");
    $row   = $check ? $check->fetch_assoc() : null;
    echo json_encode(['stock' => $row ? (int)$row['stock'] : 0]);
    exit();
}

echo json_encode(['error' => 'Unknown action']);
