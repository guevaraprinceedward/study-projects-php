<?php
require_once __DIR__ . '/../config/app.php';
require_admin();
$db = getDB();

$store_type = ($_GET['store'] ?? 'clothing') === 'essentials' ? 'essentials' : 'clothing';
$table = $store_type === 'essentials' ? 'essentials_products' : 'clothing_products';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    require_csrf_or_fail();
    $stmt = $db->prepare("DELETE FROM $table WHERE product_id = ?");
    $stmt->execute([(int)$_POST['delete_id']]);
    header('Location: ' . SITE_URL . '/admin/products.php?store=' . $store_type);
    exit;
}

$search = clean_input($_GET['q'] ?? '');
$sql = "SELECT p.*, c.name AS cat_name FROM $table p JOIN categories c ON c.category_id = p.category_id";
$params = [];
if ($search) { $sql .= " WHERE p.name LIKE ?"; $params[] = "%$search%"; }
$sql .= " ORDER BY p.created_at DESC LIMIT 100";
$stmt = $db->prepare($sql); $stmt->execute($params);
$products = $stmt->fetchAll();

$admin_active = 'products';
$page_title = "Products — Admin";
require_once __DIR__ . '/../includes/admin-header.php';
?>
<div class="admin-topbar">
    <h1>Products</h1>
    <a href="<?= SITE_URL ?>/admin/product-form.php?store=<?= $store_type ?>" class="admin-btn admin-btn-gold">+ Add Product</a>
</div>

<div class="admin-tabs">
    <a href="?store=clothing" class="<?= $store_type === 'clothing' ? 'active' : '' ?>">Clothing</a>
    <a href="?store=essentials" class="<?= $store_type === 'essentials' ? 'active' : '' ?>">Essentials</a>
</div>

<form method="GET" style="margin-bottom:1.5rem;">
    <input type="hidden" name="store" value="<?= e($store_type) ?>">
    <input type="text" name="q" placeholder="Search products..." value="<?= e($search) ?>" style="background:rgba(255,255,255,0.03); border:1px solid var(--border-color); color:var(--text-primary); padding:0.8rem 1.2rem; width:320px;">
</form>

<div class="admin-panel">
    <table class="admin-table">
        <thead><tr><th></th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Flags</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
            <td><img src="<?= e($p['primary_image']) ?>" class="thumb" alt=""></td>
            <td><?= e($p['name']) ?></td>
            <td><?= e($p['cat_name']) ?></td>
            <td><?= CURRENCY_SYMBOL . number_format($p['price'], 2) ?></td>
            <td><?= (int)$p['stock_quantity'] ?></td>
            <td style="font-size:0.7rem; color:var(--gold);">
                <?= $p['is_new_arrival'] ? 'New ' : '' ?><?= $p['is_best_seller'] ? 'Best ' : '' ?><?= $p['is_limited_edition'] ? 'Limited' : '' ?>
            </td>
            <td><?= ucfirst(e($p['status'])) ?></td>
            <td style="display:flex; gap:0.6rem;">
                <a href="<?= SITE_URL ?>/admin/product-form.php?store=<?= $store_type ?>&id=<?= $p['product_id'] ?>" class="admin-btn admin-btn-sm">Edit</a>
                <form method="POST" onsubmit="return confirm('Delete this product?')"><?= csrf_field() ?><input type="hidden" name="delete_id" value="<?= $p['product_id'] ?>"><button type="submit" class="admin-btn admin-btn-sm admin-btn-danger">Delete</button></form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?><tr><td colspan="8" style="text-align:center; color:var(--text-secondary);">No products found.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>