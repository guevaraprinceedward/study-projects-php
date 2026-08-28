<?php
/**
 * Kislap Booth ✨ — Shared data helpers
 */

function get_photo_types(): array {
    $stmt = getDB()->prepare("SELECT * FROM photo_types WHERE is_active = 1 ORDER BY display_order");
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_photo_type_by_slug(string $slug): ?array {
    $stmt = getDB()->prepare("SELECT * FROM photo_types WHERE slug = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function get_photo_type_by_id(int $id): ?array {
    $stmt = getDB()->prepare("SELECT * FROM photo_types WHERE type_id = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function get_carousel_designs(): array {
    $stmt = getDB()->prepare("SELECT * FROM carousel_designs WHERE is_active = 1 ORDER BY display_order");
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_carousel_design_by_id(?int $id): ?array {
    if (!$id) return null;
    $stmt = getDB()->prepare("SELECT * FROM carousel_designs WHERE design_id = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function get_order_by_number(string $orderNumber): ?array {
    $stmt = getDB()->prepare("SELECT o.*, pt.name AS type_name, pt.slug AS type_slug, pt.icon_emoji,
                                      cd.name AS design_name, cd.bg_color, cd.accent_color
                               FROM orders o
                               JOIN photo_types pt ON pt.type_id = o.photo_type_id
                               LEFT JOIN carousel_designs cd ON cd.design_id = o.carousel_design_id
                               WHERE o.order_number = ? LIMIT 1");
    $stmt->execute([$orderNumber]);
    return $stmt->fetch() ?: null;
}

function get_order_photos(int $orderId): array {
    $stmt = getDB()->prepare("SELECT * FROM order_photos WHERE order_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

function admin_dashboard_stats(): array {
    $db = getDB();

    $totalSales = $db->query("SELECT COALESCE(SUM(total_amount),0) AS total FROM orders WHERE order_status != 'cancelled' AND payment_status = 'paid'")->fetch()['total'];
    $totalOrders = $db->query("SELECT COUNT(*) AS total FROM orders")->fetch()['total'];
    $pendingOrders = $db->query("SELECT COUNT(*) AS total FROM orders WHERE order_status = 'pending'")->fetch()['total'];
    $todayOrders = $db->query("SELECT COUNT(*) AS total FROM orders WHERE DATE(created_at) = CURDATE()")->fetch()['total'];
    $todaySales = $db->query("SELECT COALESCE(SUM(total_amount),0) AS total FROM orders WHERE DATE(created_at) = CURDATE() AND payment_status = 'paid'")->fetch()['total'];

    $last7 = $db->query("
        SELECT DATE(created_at) AS d, COALESCE(SUM(total_amount),0) AS total
        FROM orders WHERE created_at >= (CURDATE() - INTERVAL 6 DAY) AND payment_status = 'paid'
        GROUP BY DATE(created_at)
    ")->fetchAll(PDO::FETCH_KEY_PAIR);

    $chart = [];
    for ($i = 6; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i day"));
        $chart[] = ['label' => date('M j', strtotime($day)), 'value' => (float)($last7[$day] ?? 0)];
    }

    $recentOrders = $db->query("
        SELECT o.*, pt.name AS type_name, pt.icon_emoji
        FROM orders o JOIN photo_types pt ON pt.type_id = o.photo_type_id
        ORDER BY o.created_at DESC LIMIT 8
    ")->fetchAll();

    $byType = $db->query("
        SELECT pt.name, pt.icon_emoji, COUNT(o.order_id) AS cnt
        FROM photo_types pt LEFT JOIN orders o ON o.photo_type_id = pt.type_id
        GROUP BY pt.type_id ORDER BY cnt DESC
    ")->fetchAll();

    return compact('totalSales','totalOrders','pendingOrders','todayOrders','todaySales','chart','recentOrders','byType');
}