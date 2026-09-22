<?php
require_once __DIR__ . '/product-functions.php';

function get_cart_items(int $user_id): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT ci.* FROM cart_items ci JOIN carts c ON c.cart_id = ci.cart_id WHERE c.user_id = ? ORDER BY ci.added_at DESC");
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll();

    $items = []; $subtotal = 0.00;
    foreach ($rows as $row) {
        $table = product_table($row['product_type']);
        $stmt2 = $db->prepare("SELECT name, slug, primary_image, price, stock_quantity, is_limited_edition FROM $table WHERE product_id = ?");
        $stmt2->execute([$row['product_id']]);
        $product = $stmt2->fetch();
        if (!$product) continue;

        $line_total = $product['price'] * $row['quantity'];
        $subtotal += $line_total;

        $items[] = [
            'cart_item_id' => $row['cart_item_id'], 'product_type' => $row['product_type'], 'product_id' => $row['product_id'],
            'name' => $product['name'], 'slug' => $product['slug'], 'image' => $product['primary_image'],
            'size' => $row['size'], 'color' => $row['color'], 'quantity' => $row['quantity'],
            'unit_price' => $product['price'], 'line_total' => $line_total, 'stock' => $product['stock_quantity'],
            'is_limited' => (bool)$product['is_limited_edition'],
        ];
    }
    return ['items' => $items, 'subtotal' => $subtotal];
}

function calculate_shipping(float $subtotal): float {
    return $subtotal >= FREE_SHIPPING_THRESHOLD ? 0.00 : SHIPPING_FEE_STANDARD;
}

function get_cart_item_count(int $user_id): int {
    $stmt = getDB()->prepare("SELECT COALESCE(SUM(ci.quantity),0) AS cnt FROM cart_items ci JOIN carts c ON c.cart_id = ci.cart_id WHERE c.user_id = ?");
    $stmt->execute([$user_id]);
    return (int)($stmt->fetch()['cnt'] ?? 0);
}