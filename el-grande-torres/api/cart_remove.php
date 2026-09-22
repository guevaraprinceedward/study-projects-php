<?php
require_once __DIR__ . '/../config/app.php';
header('Content-Type: application/json');

if (!is_logged_in()) { echo json_encode(['success' => false, 'message' => 'Please sign in.']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) { echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit; }

$cart_item_id = (int)($_POST['cart_item_id'] ?? 0);
$stmt = getDB()->prepare("DELETE ci FROM cart_items ci JOIN carts c ON c.cart_id = ci.cart_id WHERE ci.cart_item_id = ? AND c.user_id = ?");
$stmt->execute([$cart_item_id, current_user_id()]);
echo json_encode(['success' => true]);