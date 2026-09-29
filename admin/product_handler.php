<?php
include 'admin-config.php';
requireAdmin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// ── IMAGE UPLOAD HELPER ───────────────────────────────────────────────────
function handleImageUpload(): ?string {
    if (empty($_FILES['image_file']['tmp_name'])) return null;

    $file    = $_FILES['image_file'];
    $maxSize = 5 * 1024 * 1024; // 5 MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Upload error code: ' . $file['error']]);
        exit;
    }
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'Image must be under 5 MB.']);
        exit;
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mime    = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, WebP, or GIF images are allowed.']);
        exit;
    }

    $ext = match($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        default      => 'jpg',
    };

    $uploadDir = __DIR__ . '/uploads/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest     = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(['success' => false, 'message' => 'Could not save the uploaded image.']);
        exit;
    }

    return 'uploads/products/' . $filename;
}

// Only deletes local files — ignores external URLs (e.g. old GitHub image links)
function deleteImageFile(string $path): void {
    if (!$path) return;
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return;
    $full = __DIR__ . '/' . ltrim($path, '/');
    if (file_exists($full)) @unlink($full);
}

// ── ADD ───────────────────────────────────────────────────────────────────
if ($action === 'add') {
    $name        = trim($_POST['name']        ?? '');
    $price       = floatval($_POST['price']   ?? 0);
    $branch      = $_POST['branch']           ?? 'laguna';
    $type        = $_POST['type']             ?? 'food';
    $category    = $_POST['category']         ?? 'mains';
    $sku         = trim($_POST['sku']         ?? '');
    $description = trim($_POST['description'] ?? '');
    $stock       = intval($_POST['stock']         ?? 100);
    $reorder     = intval($_POST['reorder_level'] ?? 10);

    if (!$name)      { echo json_encode(['success' => false, 'message' => 'Product name is required.']); exit; }
    if ($price <= 0) { echo json_encode(['success' => false, 'message' => 'Please enter a valid price.']); exit; }

    // New upload takes priority, then fallback to any URL passed from the form
    $imagePath = handleImageUpload() ?? trim($_POST['image_url'] ?? '');

    $stmt = $conn->prepare(
        "INSERT INTO products (name, price, branch, type, category, sku, description, stock, reorder_level, image)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    // s=name, d=price, s=branch, s=type, s=category, s=sku, s=description, i=stock, i=reorder, s=image
    $stmt->bind_param('sdsssssiis',
        $name, $price, $branch, $type, $category,
        $sku, $description, $stock, $reorder, $imagePath
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product added successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    exit;
}

// ── EDIT ──────────────────────────────────────────────────────────────────
if ($action === 'edit') {
    $id          = intval($_POST['id']        ?? 0);
    $name        = trim($_POST['name']        ?? '');
    $price       = floatval($_POST['price']   ?? 0);
    $branch      = $_POST['branch']           ?? 'laguna';
    $type        = $_POST['type']             ?? 'food';
    $category    = $_POST['category']         ?? 'mains';
    $sku         = trim($_POST['sku']         ?? '');
    $description = trim($_POST['description'] ?? '');
    $stock       = intval($_POST['stock']         ?? 100);
    $reorder     = intval($_POST['reorder_level'] ?? 10);
    $imageDelete = $_POST['image_delete']     ?? '0';
    $existingUrl = trim($_POST['image_url']   ?? '');

    if (!$id)        { echo json_encode(['success' => false, 'message' => 'Invalid product ID.']); exit; }
    if (!$name)      { echo json_encode(['success' => false, 'message' => 'Product name is required.']); exit; }
    if ($price <= 0) { echo json_encode(['success' => false, 'message' => 'Please enter a valid price.']); exit; }

    // Fetch current image from DB
    $row = $conn->query("SELECT image FROM products WHERE id = " . intval($id))->fetch_assoc();
    $currentImage = $row['image'] ?? '';

    $newUpload = handleImageUpload();

    if ($newUpload) {
        // New file uploaded — remove old local file if any
        deleteImageFile($currentImage);
        $imagePath = $newUpload;
    } elseif ($imageDelete === '1') {
        // User clicked "Remove Image"
        deleteImageFile($currentImage);
        $imagePath = '';
    } else {
        // No change — preserve existing path/URL
        $imagePath = $existingUrl ?: $currentImage;
    }

    $stmt = $conn->prepare(
        "UPDATE products
         SET name=?, price=?, branch=?, type=?, category=?, sku=?, description=?, stock=?, reorder_level=?, image=?
         WHERE id=?"
    );
    // s=name, d=price, s=branch, s=type, s=category, s=sku, s=description, i=stock, i=reorder, s=image, i=id
    $stmt->bind_param('sdsssssiisi',
        $name, $price, $branch, $type, $category,
        $sku, $description, $stock, $reorder, $imagePath, $id
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    exit;
}

// ── DELETE ────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'message' => 'Invalid product ID.']); exit; }

    $row = $conn->query("SELECT image FROM products WHERE id = $id")->fetch_assoc();
    if ($row) deleteImageFile($row['image']);

    if ($conn->query("DELETE FROM products WHERE id = $id")) {
        echo json_encode(['success' => true, 'message' => 'Product deleted.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);