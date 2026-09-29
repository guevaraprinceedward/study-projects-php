<?php
include 'admin-config.php';
requireAdmin();

header('Content-Type: application/json');

$conn->query("ALTER TABLE admins ADD COLUMN IF NOT EXISTS profile_photo TEXT DEFAULT NULL");

$adminId = (int)($_SESSION['admin']['id'] ?? 0);
$action  = $_POST['action'] ?? '';

function deletePhotoFile(?string $path): void {
    if (!$path) return;
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return;
    $full = __DIR__ . '/' . ltrim($path, '/');
    if (file_exists($full)) @unlink($full);
}

if ($action === 'upload_photo') {
    if (empty($_FILES['photo']['tmp_name'])) {
        echo json_encode(['success' => false, 'message' => 'No photo received.']); exit;
    }
    $file    = $_FILES['photo'];
    $maxSize = 5 * 1024 * 1024;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Upload error code: ' . $file['error']]); exit;
    }
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'Image must be under 5 MB.']); exit;
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $mime    = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, or WebP images are allowed.']); exit;
    }
    $ext = match($mime) { 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', default => 'jpg' };

    $uploadDir = __DIR__ . '/uploads/admins/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $filename = 'admin_' . $adminId . '_' . time() . '.' . $ext;
    $dest     = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(['success' => false, 'message' => 'Could not save the uploaded image.']); exit;
    }

    $newPath = 'uploads/admins/' . $filename;

    $old = $conn->query("SELECT profile_photo FROM admins WHERE id = $adminId LIMIT 1")->fetch_assoc();
    if ($old) deletePhotoFile($old['profile_photo']);

    $stmt = $conn->prepare("UPDATE admins SET profile_photo = ? WHERE id = ?");
    $stmt->bind_param('si', $newPath, $adminId);
    if ($stmt->execute()) {
        $_SESSION['admin']['photo'] = $newPath;
        echo json_encode(['success' => true, 'photo' => $newPath, 'message' => 'Profile photo updated!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    exit;
}

if ($action === 'remove_photo') {
    $old = $conn->query("SELECT profile_photo FROM admins WHERE id = $adminId LIMIT 1")->fetch_assoc();
    if ($old) deletePhotoFile($old['profile_photo']);
    $conn->query("UPDATE admins SET profile_photo = NULL WHERE id = $adminId");
    unset($_SESSION['admin']['photo']);
    echo json_encode(['success' => true, 'message' => 'Profile photo removed.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);