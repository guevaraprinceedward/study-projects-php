<?php
require_once __DIR__ . '/../config/app.php';
header('Content-Type: application/json');

if (!is_logged_in()) { echo json_encode(['success' => false, 'message' => 'Please sign in to save items.']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) { echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit; }

$product_type = ($_POST['product_type'] ?? '') === 'essentials' ? 'essentials' : 'clothing';
$product_id = (int)($_POST['product_id'] ?? 0);
if (!$product_id) { echo json_encode(['success' => false, 'message' => 'Invalid product.']); exit; }

$db = getDB();
$stmt = $db->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_type = ? AND product_id = ?");
$stmt->execute([current_user_id(), $product_type, $product_id]);
$existing = $stmt->fetch();

if ($existing) {
    $stmt = $db->prepare("DELETE FROM wishlist WHERE wishlist_id = ?");
    $stmt->execute([$existing['wishlist_id']]);
    echo json_encode(['success' => true, 'action' => 'removed']);
} else {
    $stmt = $db->prepare("INSERT INTO wishlist (user_id, product_type, product_id) VALUES (?, ?, ?)");
    $stmt->execute([current_user_id(), $product_type, $product_id]);
    echo json_encode(['success' => true, 'action' => 'added']);
}