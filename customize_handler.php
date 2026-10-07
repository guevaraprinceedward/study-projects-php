<?php
/**
 * customize_handler.php — JSON endpoint for the customize modal.
 *   GET  ?action=config&id=12
 *   POST action=add  cart=serve|res  product_id size_id options addons qty note service_type
 */
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';
include_once 'customize-lib.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if ($action === 'config') {
    $cfg = custProductConfig($conn, (int)($_GET['id'] ?? 0));
    echo json_encode($cfg ?: ['error' => 'Product not found.']);
    exit();
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $isRes   = ($_POST['cart'] ?? 'serve') === 'res';
    $sessKey = $isRes ? 'res_cart' : 'cart';
    $types   = custOrderTypes();
    if ($isRes) unset($types['delivery']);            // reservations are picked up at the counter

    $svc = (string)($_POST['service_type'] ?? '');
    if (!isset($types[$svc])) {
        echo json_encode(['success' => false, 'message' => 'Please choose Dine-in, Take-out' . ($isRes ? ' or Pick-up.' : ', Pick-up or Delivery.')]);
        exit();
    }
    $r = custAddToCart($conn, $sessKey, $_POST, $isRes);
    if (!empty($r['success'])) {
        $_SESSION[$isRes ? 'res_svc_type' : 'svc_type'] = $svc;
        $r['service_type'] = $svc;
    }
    echo json_encode($r);
    exit();
}

echo json_encode(['error' => 'Unknown action']);