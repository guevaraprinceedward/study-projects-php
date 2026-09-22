<?php
require_once __DIR__ . '/../config/app.php';
require_admin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();
    if (isset($_POST['add_category'])) {
        $name = clean_input($_POST['name'] ?? '');
        $store_type = $_POST['store_type'] === 'essentials' ? 'essentials' : 'clothing';
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        if ($name && $slug) {
            $stmt = $db->prepare("INSERT INTO categories (store_type, name, slug) VALUES (?, ?, ?)");
            try { $stmt->execute([$store_type, $name, $slug]); } catch (Throwable $e) {}
        }
    }
    if (isset($_POST['toggle_id'])) {
        $stmt = $db->prepare("UPDATE categories SET is_active = 1 - is_active WHERE category_id = ?");
        $stmt->execute([(int)$_POST['toggle_id']]);
    }
    if (isset($_POST['delete_id'])) {
        $stmt = $db->prepare("DELETE FROM categories WHERE category_id = ?");
        try { $stmt->execute([(int)$_POST['delete_id']]); } catch (Throwable $e) {}
    }
    header('Location: ' . SITE_URL . '/admin/categories.php');
    exit;
}

$clothing_cats = $db->query("SELECT * FROM categories WHERE store_type = 'clothing' ORDER BY display_order")->fetchAll();
$essentials_cats = $db->query("SELECT * FROM categories WHERE store_type = 'essentials' ORDER BY display_order")->fetchAll();

$admin_active = 'categories';
$page_title = "Categories — Admin";
require_once __DIR__ . '/../includes/admin-header.php';
?>
<div class="admin-topbar"><h1>Categories</h1></div>

<div class="admin-panel">
    <h3>Add New Category</h3>
    <form method="POST" style="display:flex; gap:1rem; align-items:flex-end; flex-wrap:wrap;">
        <?= csrf_field() ?>
        <input type="hidden" name="add_category" value="1">
        <div class="field-group" style="margin:0; flex:1; min-width:200px;"><label style="text-transform:uppercase; font-size:0.7rem; color:var(--gold);">Name</label><input type="text" name="name" required style="width:100%; background:rgba(255,255,255,0.03); border:1px solid var(--border-color); color:var(--text-primary); padding:0.8rem;"></div>
        <div class="field-group" style="margin:0;">
            <label style="text-transform:uppercase; font-size:0.7rem; color:var(--gold);">Store</label>
            <select name="store_type" style="background:rgba(255,255,255,0.03); border:1px solid var(--border-color); color:var(--text-primary); padding:0.8rem;">
                <option value="clothing">Clothing</option><option value="essentials">Essentials</option>
            </select>
        </div>
        <button type="submit" class="admin-btn admin-btn-gold">Add Category</button>
    </form>
</div>

<div class="admin-panel">
    <h3>Clothing Categories</h3>
    <table class="admin-table">
        <thead><tr><th>Name</th><th>Slug</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($clothing_cats as $c): ?>
        <tr>
            <td><?= e($c['name']) ?></td><td><?= e($c['slug']) ?></td>
            <td><?= $c['is_active'] ? '<span style="color:#8FC9A0;">Active</span>' : '<span style="color:#E39A9A;">Hidden</span>' ?></td>
            <td style="display:flex; gap:0.6rem;">
                <form method="POST"><?= csrf_field() ?><input type="hidden" name="toggle_id" value="<?= $c['category_id'] ?>"><button type="submit" class="admin-btn admin-btn-sm">Toggle</button></form>
                <form method="POST" onsubmit="return confirm('Delete this category? Products in it must be reassigned first.')"><?= csrf_field() ?><input type="hidden" name="delete_id" value="<?= $c['category_id'] ?>"><button type="submit" class="admin-btn admin-btn-sm admin-btn-danger">Delete</button></form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="admin-panel">
    <h3>Essentials Categories</h3>
    <table class="admin-table">
        <thead><tr><th>Name</th><th>Slug</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($essentials_cats as $c): ?>
        <tr>
            <td><?= e($c['name']) ?></td><td><?= e($c['slug']) ?></td>
            <td><?= $c['is_active'] ? '<span style="color:#8FC9A0;">Active</span>' : '<span style="color:#E39A9A;">Hidden</span>' ?></td>
            <td style="display:flex; gap:0.6rem;">
                <form method="POST"><?= csrf_field() ?><input type="hidden" name="toggle_id" value="<?= $c['category_id'] ?>"><button type="submit" class="admin-btn admin-btn-sm">Toggle</button></form>
                <form method="POST" onsubmit="return confirm('Delete this category?')"><?= csrf_field() ?><input type="hidden" name="delete_id" value="<?= $c['category_id'] ?>"><button type="submit" class="admin-btn admin-btn-sm admin-btn-danger">Delete</button></form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
