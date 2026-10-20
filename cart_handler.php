<?php
/** cart_handler.php — serve cart (line-based). count / quick add. */
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';
include_once 'customize-lib.php';
header('Content-Type: application/json');

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
custMigrateCart($_SESSION['cart']);

if (isset($_GET['count'])) {
    echo json_encode(['count' => custCartCount($_SESSION['cart'])]);
    exit();
}

// Quick add with default choices (the menu now uses customize_handler.php instead)
if (isset($_GET['add'])) {
    echo json_encode(custAddToCart($conn, 'cart', ['product_id' => (int)$_GET['add'], 'qty' => 1]));
    exit();
}

echo json_encode(['error' => 'Unknown action']);