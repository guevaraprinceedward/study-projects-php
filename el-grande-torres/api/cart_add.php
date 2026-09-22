<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/product-functions.php';
header('Content-Type: application/json');

if (!is_logged_in()) { echo json_encode(['success' => false, 'message' => 'Please sign in to add items to your bag.']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) { echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit; }

$product_type = ($_POST['product_type'] ?? '') === 'essentials' ? 'essentials' : 'clothing';
$product_id   = (int)($_POST['product_id'] ?? 0);
$size         = clean_input($_POST['size'] ?? '') ?: null;
$color        = clean_input($_POST['color'] ?? '') ?: null;
$quantity     = max(1, (int)($_POST['quantity'] ?? 1));

if (!$product_id) { echo json_encode(['success' => false, 'message' => 'Invalid product.']); exit; }

$table = product_table($product_type);
$db = getDB();

try {
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT product_id, price, stock_quantity FROM $table WHERE product_id = ? AND status = 'active' FOR UPDATE");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) throw new Exception('This item is no longer available.');
    if ($product['stock_quantity'] < $quantity) throw new Exception('Not enough stock available for this quantity.');

    $stmt = $db->prepare("SELECT cart_id FROM carts WHERE user_id = ?");
    $stmt->execute([current_user_id()]);
    $cart = $stmt->fetch();

    if (!$cart) {
        $stmt = $db->prepare("INSERT INTO carts (user_id) VALUES (?)");
        $stmt->execute([current_user_id()]);
        $cart_id = (int)$db->lastInsertId();
    } else {
        $cart_id = (int)$cart['cart_id'];
    }

    $stmt = $db->prepare("SELECT cart_item_id, quantity FROM cart_items WHERE cart_id = ? AND product_type = ? AND product_id = ? AND size <=> ? AND color <=> ?");
    $stmt->execute([$cart_id, $product_type, $product_id, $size, $color]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $db->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
        $stmt->execute([$existing['quantity'] + $quantity, $existing['cart_item_id']]);
    } else {
        $stmt = $db->prepare("INSERT INTO cart_items (cart_id, product_type, product_id, size, color, quantity, price_at_add) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$cart_id, $product_type, $product_id, $size, $color, $quantity, $product['price']]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Added to your bag.']);
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage() ?: 'Could not add to bag.']);
}
