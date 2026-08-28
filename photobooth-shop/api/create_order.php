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

$typeSlug = clean_input($_POST['type_slug'] ?? '');
$photoType = get_photo_type_by_slug($typeSlug);
if (!$photoType) fail('That session type is no longer available.');

$designId = !empty($_POST['design_id']) ? (int)$_POST['design_id'] : null;
$design = get_carousel_design_by_id($designId);

$customerName = clean_input($_POST['customer_name'] ?? '');
$customerPhone = clean_input($_POST['customer_phone'] ?? '');
$customerEmail = clean_input($_POST['customer_email'] ?? '');

if ($customerName === '' || $customerPhone === '') fail('Please fill in your name and phone number.');

$shotsJson = $_POST['shots'] ?? '[]';
$shots = json_decode($shotsJson, true);
if (!is_array($shots) || empty($shots)) fail('No photos were captured. Please retake your session.');

$finalImage = $_POST['final_image'] ?? '';
if ($finalImage === '') fail('Missing the composed photo strip. Please try again.');

$subtotal = (float)$photoType['base_price'];
$designFee = $design ? (float)$design['extra_fee'] : 0.00;
$total = $subtotal + $designFee;

$orderNumber = generate_order_number();
$orderDir = UPLOAD_DIR . '/' . $orderNumber;
if (!is_dir($orderDir)) {
    if (!mkdir($orderDir, 0755, true)) fail('Could not prepare storage for your photos.', 500);
}

function save_base64_image(string $dataUrl, string $destPath): bool {
    if (!preg_match('/^data:image\/(png|jpe?g);base64,/', $dataUrl, $m)) return false;
    $raw = substr($dataUrl, strpos($dataUrl, ',') + 1);
    $decoded = base64_decode($raw, true);
    if ($decoded === false) return false;
    return file_put_contents($destPath, $decoded) !== false;
}

$db = getDB();
try {
    $db->beginTransaction();

    $stmt = $db->prepare("INSERT INTO orders
        (order_number, customer_id, photo_type_id, carousel_design_id, customer_name, customer_email, customer_phone,
         num_shots, subtotal, design_fee, total_amount, payment_status, order_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')");
    $stmt->execute([
        $orderNumber,
        current_customer_id(),
        $photoType['type_id'],
        $design['design_id'] ?? null,
        $customerName,
        $customerEmail ?: null,
        $customerPhone,
        count($shots),
        $subtotal,
        $designFee,
        $total,
    ]);
    $orderId = (int)$db->lastInsertId();

    // Save individual shots
    $photoStmt = $db->prepare("INSERT INTO order_photos (order_id, photo_path, sort_order) VALUES (?, ?, ?)");
    foreach ($shots as $i => $shotData) {
        $filename = 'shot-' . ($i + 1) . '.jpg';
        $destPath = $orderDir . '/' . $filename;
        if (!save_base64_image($shotData, $destPath)) continue;
        $photoStmt->execute([$orderId, 'uploads/orders/' . $orderNumber . '/' . $filename, $i]);
    }

    // Save the composed final strip
    $finalFilename = 'final-strip.png';
    $finalDest = $orderDir . '/' . $finalFilename;
    if (!save_base64_image($finalImage, $finalDest)) {
        throw new Exception('Could not save the final composed image.');
    }
    $finalRelPath = 'uploads/orders/' . $orderNumber . '/' . $finalFilename;
    $updateStmt = $db->prepare("UPDATE orders SET final_image_path = ? WHERE order_id = ?");
    $updateStmt->execute([$finalRelPath, $orderId]);

    $db->commit();

    if (is_logged_in()) {
        $_SESSION['customer_name'] = $customerName;
        $_SESSION['customer_email'] = $customerEmail;
    }

    echo json_encode(['success' => true, 'order_number' => $orderNumber]);
} catch (Throwable $e) {
    $db->rollBack();
    error_log('create_order error: ' . $e->getMessage());
    fail('Something went wrong while creating your order. Please try again.', 500);
}