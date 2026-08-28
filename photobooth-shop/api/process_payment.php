<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

function fail(string $msg, int $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) fail('Invalid request. Please refresh and try again.', 403);

$orderNumber = clean_input($_POST['order_number'] ?? '');
$method = $_POST['payment_method'] ?? '';
$reference = clean_input($_POST['payment_reference'] ?? '');

if (!in_array($method, ['cash', 'gcash', 'credit_card'], true)) fail('Please select a valid payment method.');

$order = get_order_by_number($orderNumber);
if (!$order) fail('Order not found.', 404);
if ($order['payment_status'] !== 'pending') fail('This order has already been processed.');

if ($method === 'gcash' && $reference === '') fail('Please enter your GCash reference number.');

// Cash & card are settled at the counter (status becomes "verifying" until staff confirms at pickup).
// GCash requires manual reference verification by staff before being marked fully paid.
$paymentStatus = 'verifying';

$db = getDB();
$stmt = $db->prepare("UPDATE orders SET payment_method = ?, payment_reference = ?, payment_status = ?, order_status = 'processing' WHERE order_id = ?");
$stmt->execute([$method, $reference ?: null, $paymentStatus, $order['order_id']]);

echo json_encode(['success' => true, 'order_number' => $orderNumber]);