<?php
require_once __DIR__ . '/../config/app.php';
require_admin();
$db = getDB();

$store_type = ($_GET['store'] ?? $_POST['store_type'] ?? 'clothing') === 'essentials' ? 'essentials' : 'clothing';
$table = $store_type === 'essentials' ? 'essentials_products' : 'clothing_products';
$product_id = (int)($_GET['id'] ?? 0);
$is_edit = $product_id > 0;

$stmt = $db->prepare("SELECT * FROM categories WHERE store_type = ? AND is_active = 1 ORDER BY display_order");
$stmt->execute([$store_type]);
$categories = $stmt->fetchAll();

$product = null;
if ($is_edit) {
    $stmt = $db->prepare("SELECT * FROM $table WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    if (!$product) { header('Location: ' . SITE_URL . '/admin/products.php?store=' . $store_type); exit; }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();

    $name = clean_input($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $compare_at_price = $_POST['compare_at_price'] !== '' ? (float)$_POST['compare_at_price'] : null;
    $gender = in_array($_POST['gender'] ?? '', ['men','women','unisex'], true) ? $_POST['gender'] : 'unisex';
    $short_description = clean_input($_POST['short_description'] ?? '');
    $description = clean_input($_POST['description'] ?? '');
    $material = clean_input($_POST['material'] ?? '');
    $care_instructions = clean_input($_POST['care_instructions'] ?? '');
    $available_sizes = clean_input($_POST['available_sizes'] ?? '');
    $available_colors = clean_input($_POST['available_colors'] ?? '');
    $primary_image = clean_input($_POST['primary_image'] ?? '');
    $stock_quantity = max(0, (int)($_POST['stock_quantity'] ?? 0));
    $is_new_arrival = !empty($_POST['is_new_arrival']) ? 1 : 0;
    $is_best_seller = !empty($_POST['is_best_seller']) ? 1 : 0;
    $is_trending = !empty($_POST['is_trending']) ? 1 : 0;
    $is_featured = !empty($_POST['is_featured']) ? 1 : 0;
    $is_limited_edition = !empty($_POST['is_limited_edition']) ? 1 : 0;
    $limited_edition_note = clean_input($_POST['limited_edition_note'] ?? '') ?: null;
    $season = clean_input($_POST['season'] ?? '') ?: null;
    $status = in_array($_POST['status'] ?? '', ['active','draft','archived'], true) ? $_POST['status'] : 'active';

    if ($name === '') $errors['name'] = 'Product name is required.';
    if ($category_id <= 0) $errors['category_id'] = 'Please select a category.';
    if ($price <= 0) $errors['price'] = 'Price must be greater than zero.';
    if ($primary_image === '') $errors['primary_image'] = 'Primary image URL is required.';

    if (empty($errors)) {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-')) . '-' . substr(md5(uniqid()), 0, 5);

        if ($is_edit) {
            $stmt = $db->prepare("UPDATE $table SET category_id=?, name=?, short_description=?, description=?, price=?, compare_at_price=?, gender=?, material=?, care_instructions=?, available_sizes=?, available_colors=?, primary_image=?, stock_quantity=?, is_new_arrival=?, is_best_seller=?, is_trending=?, is_featured=?, is_limited_edition=?, limited_edition_note=?, season=?, status=? WHERE product_id=?");
            $stmt->execute([$category_id, $name, $short_description, $description, $price, $compare_at_price, $gender, $material, $care_instructions, $available_sizes, $available_colors, $primary_image, $stock_quantity, $is_new_arrival, $is_best_seller, $is_trending, $is_featured, $is_limited_edition, $limited_edition_note, $season, $status, $product_id]);
            set_flash('success', 'Product updated.');
        } else {
            $sku = strtoupper(substr($store_type, 0, 3)) . '-' . strtoupper(substr(uniqid(), -6));
            $stmt = $db->prepare("INSERT INTO $table (category_id, sku, name, slug, short_description, description, price, compare_at_price, gender, material, care_instructions, available_sizes, available_colors, primary_image, stock_quantity, is_new_arrival, is_best_seller, is_trending, is_featured, is_limited_edition, limited_edition_note, season, status)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$category_id, $sku, $name, $slug, $short_description, $description, $price, $compare_at_price, $gender, $material, $care_instructions, $available_sizes, $available_colors, $primary_image, $stock_quantity, $is_new_arrival, $is_best_seller, $is_trending, $is_featured, $is_limited_edition, $limited_edition_note, $season, $status]);
            set_flash('success', 'Product created.');
        }
        header('Location: ' . SITE_URL . '/admin/products.php?store=' . $store_type);
        exit;
    }
}

$admin_active = 'products';
$page_title = ($is_edit ? "Edit Product" : "Add Product") . " — Admin";
require_once __DIR__ . '/../includes/admin-header.php';

$v = fn($key, $default = '') => e($product[$key] ?? $_POST[$key] ?? $default);
?>
<div class="admin-topbar"><h1><?= $is_edit ? 'Edit Product' : 'Add New Product' ?></h1></div>

<form method="POST" class="admin-panel form-lux">
    <?= csrf_field() ?>
    <input type="hidden" name="store_type" value="<?= e($store_type) ?>">

    <div class="admin-form-grid">
        <div class="field-group <?= isset($errors['name']) ? 'field-error' : '' ?>">
            <label>Product Name</label><input type="text" name="name" value="<?= $v('name') ?>" required>
            <?php if (isset($errors['name'])): ?><p class="error-text"><?= e($errors['name']) ?></p><?php endif; ?>
        </div>
        <div class="field-group <?= isset($errors['category_id']) ? 'field-error' : '' ?>">
            <label>Category</label>
            <select name="category_id" required>
                <option value="">— Select —</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?= $c['category_id'] ?>" <?= (($product['category_id'] ?? '') == $c['category_id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['category_id'])): ?><p class="error-text"><?= e($errors['category_id']) ?></p><?php endif; ?>
        </div>

        <div class="field-group <?= isset($errors['price']) ? 'field-error' : '' ?>">
            <label>Price (₱)</label><input type="number" step="0.01" name="price" value="<?= $v('price') ?>" required>
            <?php if (isset($errors['price'])): ?><p class="error-text"><?= e($errors['price']) ?></p><?php endif; ?>
        </div>
        <div class="field-group"><label>Compare-at Price (Optional)</label><input type="number" step="0.01" name="compare_at_price" value="<?= $v('compare_at_price') ?>"></div>

        <div class="field-group">
            <label>Gender</label>
            <select name="gender">
                <?php foreach (['unisex' => 'Unisex', 'men' => 'Men', 'women' => 'Women'] as $val => $label): ?>
                <option value="<?= $val ?>" <?= ($product['gender'] ?? 'unisex') === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field-group"><label>Stock Quantity</label><input type="number" name="stock_quantity" value="<?= $v('stock_quantity', 0) ?>" required></div>

        <div class="field-group <?= isset($errors['primary_image']) ? 'field-error' : '' ?>" style="grid-column:1/-1;">
            <label>Primary Image URL</label><input type="text" name="primary_image" value="<?= $v('primary_image') ?>" placeholder="https://..." required>
            <?php if (isset($errors['primary_image'])): ?><p class="error-text"><?= e($errors['primary_image']) ?></p><?php endif; ?>
        </div>

        <div class="field-group"><label>Available Sizes (comma-separated)</label><input type="text" name="available_sizes" value="<?= $v('available_sizes') ?>" placeholder="S, M, L, XL"></div>
        <div class="field-group"><label>Available Colors (comma-separated)</label><input type="text" name="available_colors" value="<?= $v('available_colors') ?>" placeholder="Black, Ivory, Camel"></div>

        <div class="field-group" style="grid-column:1/-1;"><label>Short Description</label><input type="text" name="short_description" value="<?= $v('short_description') ?>"></div>
        <div class="field-group" style="grid-column:1/-1;"><label>Full Description</label><textarea name="description" rows="4"><?= $v('description') ?></textarea></div>
        <div class="field-group"><label>Material</label><input type="text" name="material" value="<?= $v('material') ?>"></div>
        <div class="field-group"><label>Care Instructions</label><input type="text" name="care_instructions" value="<?= $v('care_instructions') ?>"></div>

        <div class="field-group"><label>Season (Optional)</label><input type="text" name="season" value="<?= $v('season') ?>" placeholder="e.g. Autumn 2026"></div>
        <div class="field-group">
            <label>Status</label>
            <select name="status">
                <?php foreach (['active' => 'Active', 'draft' => 'Draft', 'archived' => 'Archived'] as $val => $label): ?>
                <option value="<?= $val ?>" <?= ($product['status'] ?? 'active') === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div style="margin:1.8rem 0; display:flex; gap:2rem; flex-wrap:wrap;">
        <?php
        $flags = ['is_new_arrival' => 'New Arrival', 'is_best_seller' => 'Best Seller', 'is_trending' => 'Trending', 'is_featured' => 'Featured', 'is_limited_edition' => 'Limited Edition'];
        foreach ($flags as $key => $label): ?>
        <label style="display:flex; align-items:center; gap:0.6rem; font-size:0.85rem; text-transform:none;">
            <input type="checkbox" name="<?= $key ?>" value="1" <?= !empty($product[$key]) ? 'checked' : '' ?> style="accent-color:var(--gold);"> <?= $label ?>
        </label>
        <?php endforeach; ?>
    </div>

    <div class="field-group" id="limitedNoteGroup" style="<?= empty($product['is_limited_edition']) ? 'display:none;' : '' ?>">
        <label>Limited Edition Note</label>
        <input type="text" name="limited_edition_note" value="<?= $v('limited_edition_note') ?>" placeholder="e.g. Only 50 pieces worldwide">
    </div>

    <button type="submit" class="btn-lux btn-lux-filled"><?= $is_edit ? 'Update Product' : 'Create Product' ?></button>
    <a href="<?= SITE_URL ?>/admin/products.php?store=<?= $store_type ?>" class="btn-lux btn-lux-ghost">Cancel</a>
</form>

<script>
document.querySelector('input[name="is_limited_edition"]').addEventListener('change', function () {
    document.getElementById('limitedNoteGroup').style.display = this.checked ? 'block' : 'none';
});
</script>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>