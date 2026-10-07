<?php
/**
 * cart_handler.php
 * -------------------------------------------------------------------------
 * JSON endpoint for the SERVE cart ($_SESSION['cart']), now line-based so
 * each line can carry its own size / Hot-Iced / Sugar Level / add-ons / note
 * (see customize-lib.php). Old flat carts ([productId] => qty) are migrated
 * automatically the first time this runs.
 *
 *   GET  ?count=1                                   -> { count }
 *   GET  ?add=12                                     -> legacy quick-add (no
 *                                                        customization — used
 *                                                        by index.php's
 *                                                        "Add to Order" cards)
 *   POST action=add_custom&product_id=12&size_id=66&
 *        options={"1":[2],"2":[5]}&addons=[3]&qty=2&note=...
 *                                                     -> customized add, used
 *                                                        by the customize modal
 */
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';
include_once 'customize-lib.php';

header('Content-Type: application/json');

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
custMigrateCart($_SESSION['cart']);

// ── COUNT ONLY ─────────────────────────────────────────────────────────────
if (isset($_GET['count'])) {
    echo json_encode(['count' => custCartCount($_SESSION['cart'])]);
    exit();
}

// ── CUSTOMIZED ADD (from the customize modal) ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_custom') {
    echo json_encode(custAddToCart($conn, 'cart', $_POST, false));
    exit();
}

// ── LEGACY QUICK ADD (no customization — e.g. index.php signature cards) ───
if (isset($_GET['add'])) {
    echo json_encode(custAddToCart($conn, 'cart', ['product_id' => (int)$_GET['add'], 'qty' => 1], false));
    exit();
}

echo json_encode(['error' => 'Unknown action']);
exit();
