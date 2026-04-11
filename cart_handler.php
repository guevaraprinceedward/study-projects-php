<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// ── ADD ───────────────────────────────────────────────────────────────────
if (isset($_GET['add'])) {
    $id = (int)$_GET['add'];
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
    echo json_encode([
        'success' => true,
        'qty'     => $_SESSION['cart'][$id],
        'count'   => array_sum($_SESSION['cart'])
    ]);
    exit();
}

// ── REMOVE ────────────────────────────────────────────────────────────────
if (isset($_GET['remove'])) {
    $id = (int)$_GET['remove'];
    unset($_SESSION['cart'][$id]);
    echo json_encode([
        'success' => true,
        'count'   => array_sum($_SESSION['cart'] ?? [])
    ]);
    exit();
}

// ── COUNT (for cart badge) ─────────────────────────────────────────────────
if (isset($_GET['count'])) {
    echo json_encode([
        'count' => array_sum($_SESSION['cart'] ?? [])
    ]);
    exit();
}

// ── DEFAULT ───────────────────────────────────────────────────────────────
echo json_encode(['error' => 'Unknown action']);
