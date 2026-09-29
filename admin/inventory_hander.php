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
$action  = $_POST['action'] ?? '';

function logMovement($conn, $productId, $type, $qty, $prev, $new, $reason, $adminId) {
    $stmt = $conn->prepare("
        INSERT INTO inventory_movements (product_id, type, quantity, previous_stock, new_stock, reason, admin_id)
        VALUES (?,?,?,?,?,?,?)
    ");
    $stmt->bind_param('isiiisi', $productId, $type, $qty, $prev, $new, $reason, $adminId);
    return $stmt->execute();
}

if ($action === 'record_movement') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $type      = $_POST['type'] ?? '';
    $reason    = trim($_POST['reason'] ?? '');

    $validTypes = ['stock_in', 'stock_out', 'damaged', 'restock', 'adjustment'];
    if (!$productId || !in_array($type, $validTypes, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid product or movement type.']); exit;
    }

    $prodRow = $conn->query("SELECT stock, name FROM products WHERE id = $productId LIMIT 1")->fetch_assoc();
    if (!$prodRow) { echo json_encode(['success' => false, 'message' => 'Product not found.']); exit; }
    $prevStock = (int)$prodRow['stock'];

    if ($type === 'adjustment') {
        // Admin sets the exact counted stock directly (e.g. after a physical inventory count)
        $newStock = (int)($_POST['new_stock'] ?? -1);
        if ($newStock < 0) { echo json_encode(['success' => false, 'message' => 'Please enter a valid stock count.']); exit; }
        if (!$reason) { echo json_encode(['success' => false, 'message' => 'A reason is required for manual adjustments.']); exit; }
        $qty = $newStock - $prevStock; // signed delta, can be negative
    } else {
        $qty = (int)($_POST['quantity'] ?? 0);
        if ($qty <= 0) { echo json_encode(['success' => false, 'message' => 'Please enter a quantity greater than zero.']); exit; }

        if ($type === 'stock_in' || $type === 'restock') {
            $newStock = $prevStock + $qty;
        } else { // stock_out, damaged
            if ($qty > $prevStock) {
                echo json_encode(['success' => false, 'message' => "Only $prevStock unit(s) currently in stock — cannot remove $qty."]); exit;
            }
            $newStock = $prevStock - $qty;
            $qty = -$qty; // store as negative delta for stock_out/damaged
        }
    }

    $conn->query("UPDATE products SET stock = " . (int)$newStock . " WHERE id = $productId");
    $ok = logMovement($conn, $productId, $type, $qty, $prevStock, $newStock, $reason ?: null, $adminId);

    if ($ok) {
        echo json_encode([
            'success' => true,
            'message' => htmlspecialchars($prodRow['name']) . ' updated: ' . $prevStock . ' → ' . $newStock,
            'new_stock' => $newStock,
            'product_id' => $productId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);