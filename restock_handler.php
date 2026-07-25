<?php
// restock_handler.php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION["user"])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include 'config.php';
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

$stmt = $conn->prepare("UPDATE products SET stock = 100 WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute() && $stmt->affected_rows >= 0) {
    $res = $conn->prepare("SELECT stock, name FROM products WHERE id = ?");
    $res->bind_param("i", $id);
    $res->execute();
    $row = $res->get_result()->fetch_assoc();
    echo json_encode(['success' => true, 'stock' => (int)$row['stock'], 'name' => $row['name']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}
