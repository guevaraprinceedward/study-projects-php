<?php
/**
 * Shared product query helpers — clothing_products / essentials_products
 */

function product_table(string $type): string {
    return $type === 'essentials' ? 'essentials_products' : 'clothing_products';
}

function query_products(string $store_type, array $filters = [], string $sort = 'newest', int $page = 1, int $per_page = 6): array {
    $db = getDB();
    $table = product_table($store_type);
    $where = ["p.status = 'active'"];
    $params = [];

    if (!empty($filters['category'])) {
        $ph = implode(',', array_fill(0, count($filters['category']), '?'));
        $where[] = "c.slug IN ($ph)";
        foreach ($filters['category'] as $slug) $params[] = $slug;
    }
    if (!empty($filters['gender'])) { $where[] = "p.gender = ?"; $params[] = $filters['gender']; }
    if (!empty($filters['min_price'])) { $where[] = "p.price >= ?"; $params[] = $filters['min_price']; }
    if (!empty($filters['max_price'])) { $where[] = "p.price <= ?"; $params[] = $filters['max_price']; }
    if (!empty($filters['color'])) { $where[] = "p.available_colors LIKE ?"; $params[] = '%' . $filters['color'] . '%'; }
    if (!empty($filters['size'])) { $where[] = "p.available_sizes LIKE ?"; $params[] = '%' . $filters['size'] . '%'; }
    if (!empty($filters['availability']) && $filters['availability'] === 'in_stock') { $where[] = "p.stock_quantity > 0"; }
    if (!empty($filters['limited_only'])) { $where[] = "p.is_limited_edition = 1"; }
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
            FROM $table p JOIN categories c ON c.category_id = p.category_id
            WHERE $whereSql ORDER BY $order LIMIT $per_page OFFSET $offset";
    $countSql = "SELECT COUNT(*) AS total FROM $table p JOIN categories c ON c.category_id = p.category_id WHERE $whereSql";

    try {
        $stmt = $db->prepare($sql); $stmt->execute($params); $items = $stmt->fetchAll();
        $cstmt = $db->prepare($countSql); $cstmt->execute($params);
        $total = (int)($cstmt->fetch()['total'] ?? 0);
    } catch (Throwable $e) {
        error_log('query_products error: ' . $e->getMessage());
        $items = []; $total = 0;
    }
    return ['items' => $items, 'total' => $total, 'pages' => max(1, (int)ceil($total / $per_page))];
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

function fetch_flagged_products(string $flag, int $limit = 8, ?string $gender = null): array {
    $db = getDB();
    $genderClause = $gender ? "AND gender = " . $db->quote($gender) : "";
    $sql = "
        (SELECT product_id, name, slug, price, compare_at_price, primary_image, gender, is_limited_edition, limited_edition_note, 'clothing' AS ptype,
                (SELECT name FROM categories c WHERE c.category_id = p.category_id) AS cat_name
         FROM clothing_products p WHERE $flag = 1 AND status = 'active' $genderClause)
        UNION ALL
        (SELECT product_id, name, slug, price, compare_at_price, primary_image, gender, is_limited_edition, limited_edition_note, 'essentials' AS ptype,
                (SELECT name FROM categories c WHERE c.category_id = p.category_id) AS cat_name
         FROM essentials_products p WHERE $flag = 1 AND status = 'active' $genderClause)
        LIMIT :lim
    ";
    try {
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Throwable $e) { return []; }
}

/**
 * Combined, paginated query across BOTH clothing_products and
 * essentials_products for a set of boolean flag columns (e.g.
 * ['is_new_arrival'] or ['is_limited_edition']). Used by new-arrivals.php
 * so New Arrivals / Limited Edition can show pieces from either store in
 * one consistently-sized, paginated grid — matching the card size used on
 * clothing.php and essentials.php (see product-card.css).
 *
 * $filters supports: gender ('men'|'women'|'unisex'), limited_only (bool)
 */
function query_combined_products(array $flags, array $filters = [], string $sort = 'newest', int $page = 1, int $per_page = 6): array {
    $db = getDB();

    $flagConds = [];
    foreach ($flags as $f) {
        $f = preg_replace('/[^a-z_]/', '', $f); // guard against anything unexpected
        if ($f !== '') $flagConds[] = "p.$f = 1";
    }
    $flagSql = $flagConds ? '(' . implode(' OR ', $flagConds) . ')' : '1=1';

    $gender = $filters['gender'] ?? null;
    $genderSql = ($gender && in_array($gender, ['men', 'women', 'unisex'], true)) ? $db->quote($gender) : null;
    $limitedOnly = !empty($filters['limited_only']);

    $order = match ($sort) {
        'price_asc'    => 'price ASC',
        'price_desc'   => 'price DESC',
        'best_selling' => 'sales_count DESC',
        default        => 'created_at DESC',
    };

    $parts = [];
    foreach (['clothing' => 'clothing_products', 'essentials' => 'essentials_products'] as $ptype => $table) {
        $where = ["p.status = 'active'", $flagSql];
        if ($genderSql) $where[] = "p.gender = $genderSql";
        if ($limitedOnly) $where[] = "p.is_limited_edition = 1";
        $whereSql = implode(' AND ', $where);
        $parts[] = "(SELECT p.product_id, p.name, p.slug, p.price, p.compare_at_price, p.primary_image, p.gender,
                        p.is_limited_edition, p.limited_edition_note, p.is_new_arrival, p.created_at, p.sales_count,
                        '$ptype' AS ptype,
                        (SELECT name FROM categories c WHERE c.category_id = p.category_id) AS cat_name
                     FROM $table p
                     WHERE $whereSql)";
    }
    $unionSql = implode(' UNION ALL ', $parts);

    $offset = max(0, ($page - 1) * $per_page);
    $finalSql = "SELECT * FROM ($unionSql) combined ORDER BY $order LIMIT $per_page OFFSET $offset";
    $countSql = "SELECT COUNT(*) AS total FROM ($unionSql) combined";

    try {
        $items = $db->query($finalSql)->fetchAll();
        $total = (int)($db->query($countSql)->fetch()['total'] ?? 0);
    } catch (Throwable $e) {
        error_log('query_combined_products error: ' . $e->getMessage());
        $items = []; $total = 0;
    }

    return ['items' => $items, 'total' => $total, 'pages' => max(1, (int)ceil($total / $per_page))];
}