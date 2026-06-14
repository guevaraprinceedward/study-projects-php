<?php
/**
 * restock_handler.php
 * Must be in the SAME folder as admin-dashboard.php
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache');

// ── SESSION ───────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();

// ── ADMIN CHECK (AJAX-safe — no redirect, returns JSON) ───────────────────
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in as admin.']);
    exit;
}

// ── DB CONNECTION (direct — avoids requireAdmin() redirect issue) ──────────
$conn = new mysqli("localhost", "root", "", "restaurant_db");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection failed: ' . $conn->connect_error]);
    exit;
}

// ── VALIDATE INPUT ────────────────────────────────────────────────────────
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
    exit;
}

// ── ENSURE COLUMNS EXIST ──────────────────────────────────────────────────
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS stock INT NOT NULL DEFAULT 100");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS reorder_level INT NOT NULL DEFAULT 10");

// ── FETCH PRODUCT ─────────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT id, name, stock FROM products WHERE id = ? LIMIT 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare error: ' . $conn->error]);
    exit;
}
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found (ID: ' . $id . ').']);
    exit;
}

// ── RESTOCK TO 100 ────────────────────────────────────────────────────────
$newStock = 100;
$upd = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
if (!$upd) {
    echo json_encode(['success' => false, 'message' => 'Prepare error: ' . $conn->error]);
    exit;
}
$upd->bind_param("ii", $newStock, $id);

if ($upd->execute()) {
    echo json_encode([
        'success' => true,
        'id'      => $id,
        'name'    => $product['name'],
        'stock'   => $newStock,
        'message' => $product['name'] . ' restocked to ' . $newStock . ' units.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $upd->error]);
}

$upd->close();
$conn->close();