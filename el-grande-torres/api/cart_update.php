<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/product-functions.php';
header('Content-Type: application/json');

if (!is_logged_in()) { echo json_encode(['success' => false, 'message' => 'Please sign in.']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) { echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit; }

$cart_item_id = (int)($_POST['cart_item_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 0);
$db = getDB();

try {
    $stmt = $db->prepare("SELECT ci.cart_item_id, ci.product_type, ci.product_id FROM cart_items ci JOIN carts c ON c.cart_id = ci.cart_id WHERE ci.cart_item_id = ? AND c.user_id = ?");
    $stmt->execute([$cart_item_id, current_user_id()]);
    $item = $stmt->fetch();
    if (!$item) { echo json_encode(['success' => false, 'message' => 'Item not found in your bag.']); exit; }

    if ($quantity <= 0) {
        $stmt = $db->prepare("DELETE FROM cart_items WHERE cart_item_id = ?");
        $stmt->execute([$cart_item_id]);
        echo json_encode(['success' => true, 'removed' => true]);
        exit;
    }

    $table = product_table($item['product_type']);
    $stmt = $db->prepare("SELECT stock_quantity FROM $table WHERE product_id = ?");
    $stmt->execute([$item['product_id']]);
    $stock = (int)($stmt->fetch()['stock_quantity'] ?? 0);

    if ($quantity > $stock) { echo json_encode(['success' => false, 'message' => "Only $stock left in stock."]); exit; }

    $stmt = $db->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
    $stmt->execute([$quantity, $cart_item_id]);
    echo json_encode(['success' => true]);
} catch (Throwable $e) { echo json_encode(['success' => false, 'message' => 'Something went wrong.']); }