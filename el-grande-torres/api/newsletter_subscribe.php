<?php
require_once __DIR__ . '/../config/app.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) { echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit; }

$email = clean_input($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']); exit; }

try {
    $stmt = getDB()->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?) ON DUPLICATE KEY UPDATE is_active = 1");
    $stmt->execute([$email]);
    echo json_encode(['success' => true, 'message' => 'Thank you for subscribing.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}