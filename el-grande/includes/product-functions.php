<?php
/**
 * Shared product query helpers for clothing_products / accessories_products
 */

function product_table(string $type): string {
    return $type === 'accessories' ? 'accessories_products' : 'clothing_products';
}

function query_products(string $store_type, array $filters = [], string $sort = 'newest', int $page = 1, int $per_page = 12): array {
    $db = getDB();
    $table = product_table($store_type);

    $where = ["p.status = 'active'"];
    $params = [];

    if (!empty($filters['category'])) {
        $placeholders = implode(',', array_fill(0, count($filters['category']), '?'));
        $where[] = "c.slug IN ($placeholders)";
        foreach ($filters['category'] as $slug) $params[] = $slug;
    }
    if (!empty($filters['min_price'])) {
        $where[] = "p.price >= ?";
        $params[] = $filters['min_price'];
    }
    if (!empty($filters['max_price'])) {
        $where[] = "p.price <= ?";
        $params[] = $filters['max_price'];
    }
    if (!empty($filters['color'])) {
        $where[] = "p.available_colors LIKE ?";
        $params[] = '%' . $filters['color'] . '%';
    }
    if (!empty($filters['size'])) {
        $where[] = "p.available_sizes LIKE ?";
        $params[] = '%' . $filters['size'] . '%';
    }
    if (!empty($filters['availability']) && $filters['availability'] === 'in_stock') {
        $where[] = "p.stock_quantity > 0";
    }
    if (!empty($filters['search'])) {
        $where[] = "(p.name LIKE ? OR p.short_description LIKE ?)";
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
    }

    $order = match ($sort) {
        'price_asc'    => 'p.price ASC',
        'price_desc'   => 'p.price DESC',
        'best_selling' => 'p.sales_count DESC',
        default        => 'p.created_at DESC',
    };

    $offset = max(0, ($page - 1) * $per_page);
    $whereSql = implode(' AND ', $where);

    $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM $table p
            JOIN categories c ON c.category_id = p.category_id
            WHERE $whereSql
            ORDER BY $order
            LIMIT $per_page OFFSET $offset";

    $countSql = "SELECT COUNT(*) AS total FROM $table p JOIN categories c ON c.category_id = p.category_id WHERE $whereSql";

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)($countStmt->fetch()['total'] ?? 0);
    } catch (Throwable $e) {
        error_log('query_products error: ' . $e->getMessage());
        $items = [];
        $total = 0;
    }

    return ['items' => $items, 'total' => $total, 'pages' => (int)ceil($total / $per_page)];
}

function get_product_by_slug(string $store_type, string $slug): ?array {
    $table = product_table($store_type);
    $stmt = getDB()->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug
                               FROM $table p JOIN categories c ON c.category_id = p.category_id
                               WHERE p.slug = ? AND p.status = 'active' LIMIT 1");
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_categories(string $store_type): array {
    $stmt = getDB()->prepare("SELECT * FROM categories WHERE store_type = ? AND is_active = 1 ORDER BY display_order");
    $stmt->execute([$store_type]);
    return $stmt->fetchAll();
}