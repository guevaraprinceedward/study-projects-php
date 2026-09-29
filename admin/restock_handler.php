<?php
include 'admin-config.php';
requireAdmin();

header('Content-Type: application/json');

$conn->query("
    CREATE TABLE IF NOT EXISTS inventory_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        type ENUM('stock_in','stock_out','damaged','restock','adjustment') NOT NULL,
        quantity INT NOT NULL,
        previous_stock INT NOT NULL,
        new_stock INT NOT NULL,
        reason VARCHAR(255) DEFAULT NULL,
        admin_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$adminId = (int)($_SESSION['admin']['id'] ?? 0);
$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['success' => false, 'message' => 'Invalid product ID.']); exit; }

$prod = $conn->query("SELECT id, name, stock, reorder_level FROM products WHERE id = $id LIMIT 1")->fetch_assoc();
if (!$prod) { echo json_encode(['success' => false, 'message' => 'Product not found.']); exit; }

$prevStock = (int)$prod['stock'];
$reorder   = (int)$prod['reorder_level'];

// Quick one-tap restock target: comfortably above reorder level (at least 3x reorder, min 50)
$target = max($reorder * 3, 50, $prevStock);
$qtyAdded = $target - $prevStock;

if ($qtyAdded <= 0) {
    echo json_encode(['success' => true, 'stock' => $prevStock, 'name' => $prod['name'], 'message' => 'Already well-stocked.']);
    exit;
}

$conn->query("UPDATE products SET stock = $target WHERE id = $id");

$reason = 'Quick restock from Low Stock Alert';
$stmt = $conn->prepare("
    INSERT INTO inventory_movements (product_id, type, quantity, previous_stock, new_stock, reason, admin_id)
    VALUES (?, 'restock', ?, ?, ?, ?, ?)
");
$stmt->bind_param('iiiisi', $id, $qtyAdded, $prevStock, $target, $reason, $adminId);
$stmt->execute();

echo json_encode(['success' => true, 'stock' => $target, 'name' => $prod['name']]);