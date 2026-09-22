<?php
require_once __DIR__ . '/../config/app.php';
require_admin();
$db = getDB();

$low_stock_clothing = $db->query("SELECT name, stock_quantity, 'clothing' AS ptype FROM clothing_products WHERE stock_quantity <= 5 AND status = 'active' ORDER BY stock_quantity ASC")->fetchAll();
$low_stock_essentials = $db->query("SELECT name, stock_quantity, 'essentials' AS ptype FROM essentials_products WHERE stock_quantity <= 5 AND status = 'active' ORDER BY stock_quantity ASC")->fetchAll();
$low_stock = array_merge($low_stock_clothing, $low_stock_essentials);

$admin_active = 'inventory';
$page_title = "Inventory — Admin";
require_once __DIR__ . '/../includes/admin-header.php';
?>
<div class="admin-topbar"><h1>Inventory Alerts</h1></div>
<div class="admin-panel">
    <h3>Low Stock (5 or fewer units)</h3>
    <table class="admin-table">
        <thead><tr><th>Product</th><th>Store</th><th>Stock Left</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($low_stock as $item): ?>
        <tr>
            <td><?= e($item['name']) ?></td>
            <td><?= ucfirst(e($item['ptype'])) ?></td>
            <td style="color:<?= $item['stock_quantity'] == 0 ? '#E39A9A' : 'var(--gold)' ?>;"><?= (int)$item['stock_quantity'] ?></td>
            <td><a href="<?= SITE_URL ?>/admin/products.php?store=<?= $item['ptype'] ?>" class="admin-btn admin-btn-sm">Manage</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($low_stock)): ?><tr><td colspan="4" style="text-align:center; color:var(--text-secondary);">All products are well-stocked.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>