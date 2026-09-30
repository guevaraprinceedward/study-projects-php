<?php
include 'admin-config.php';
requireAdmin();

header('Content-Type: application/json');

$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'pending'");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS branch VARCHAR(20) DEFAULT 'laguna'");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(20) DEFAULT 'cash'");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS guest_name VARCHAR(150) DEFAULT NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS inventory_deducted TINYINT(1) DEFAULT 0");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
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

$flow = ['pending', 'preparing', 'ready', 'completed'];

function deductInventoryForOrder($conn, $orderId, $adminId) {
    $items = $conn->query("SELECT product_id, quantity FROM order_items WHERE order_id = " . (int)$orderId);
    if (!$items) return;
    while ($item = $items->fetch_assoc()) {
        $pid = (int)$item['product_id'];
        $qty = (int)$item['quantity'];
        if (!$pid || $qty <= 0) continue;

        $prod = $conn->query("SELECT stock FROM products WHERE id = $pid LIMIT 1")->fetch_assoc();
        if (!$prod) continue;
        $prev = (int)$prod['stock'];
        $actualQty = min($qty, $prev); // never go negative even if data is inconsistent
        $new = $prev - $actualQty;

        $conn->query("UPDATE products SET stock = $new WHERE id = $pid");

        $reason = "Order #$orderId completed";
        $negQty = -$actualQty;
        $stmt = $conn->prepare("
            INSERT INTO inventory_movements (product_id, type, quantity, previous_stock, new_stock, reason, admin_id)
            VALUES (?, 'stock_out', ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iiiisi', $pid, $negQty, $prev, $new, $reason, $adminId);
        $stmt->execute();
    }
}

if ($action === 'update_status') {
    $orderId   = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $validAll  = array_merge($flow, ['cancelled']);

    if (!$orderId || !in_array($newStatus, $validAll, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid order or status.']); exit;
    }

    $order = $conn->query("SELECT status, inventory_deducted FROM orders WHERE id = $orderId LIMIT 1")->fetch_assoc();
    if (!$order) { echo json_encode(['success' => false, 'message' => 'Order not found.']); exit; }

    if ($order['status'] === 'completed' && $newStatus !== 'completed') {
        echo json_encode(['success' => false, 'message' => 'Completed orders cannot be moved back.']); exit;
    }
    if ($order['status'] === 'cancelled') {
        echo json_encode(['success' => false, 'message' => 'This order is cancelled.']); exit;
    }

    $statusEsc = $conn->real_escape_string($newStatus);
    $conn->query("UPDATE orders SET status = '$statusEsc' WHERE id = $orderId");

    if ($newStatus === 'completed' && !(int)$order['inventory_deducted']) {
        deductInventoryForOrder($conn, $orderId, $adminId);
        $conn->query("UPDATE orders SET inventory_deducted = 1 WHERE id = $orderId");
    }

    echo json_encode(['success' => true, 'message' => 'Order #' . $orderId . ' marked as ' . ucfirst($newStatus) . '.', 'status' => $newStatus]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
