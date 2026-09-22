<?php
require_once __DIR__ . '/../config/app.php';
header('Content-Type: application/json');

if (!is_logged_in()) { echo json_encode(['success' => false, 'message' => 'Please sign in.']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) { echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit; }

$user_id = current_user_id();
$review_id = (int)($_POST['review_id'] ?? 0);
$rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
$location = clean_input($_POST['location'] ?? '');
$review_text = clean_input($_POST['review_text'] ?? '');

if ($review_text === '' || $location === '') {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit;
}
if (mb_strlen($review_text) > 280) $review_text = mb_substr($review_text, 0, 280);

$db = getDB();

// If review_id given, verify ownership before editing
if ($review_id) {
    $stmt = $db->prepare("SELECT review_id FROM customer_reviews WHERE review_id = ? AND user_id = ? AND is_homepage_testimonial = 1");
    $stmt->execute([$review_id, $user_id]);
    if (!$stmt->fetch()) { echo json_encode(['success' => false, 'message' => 'Testimonial not found.']); exit; }

    $stmt = $db->prepare("UPDATE customer_reviews SET rating = ?, location = ?, review_text = ? WHERE review_id = ?");
    $stmt->execute([$rating, $location, $review_text, $review_id]);
} else {
    // One homepage testimonial per user — check first
    $stmt = $db->prepare("SELECT review_id FROM customer_reviews WHERE user_id = ? AND is_homepage_testimonial = 1 LIMIT 1");
    $stmt->execute([$user_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $db->prepare("UPDATE customer_reviews SET rating = ?, location = ?, review_text = ? WHERE review_id = ?");
        $stmt->execute([$rating, $location, $review_text, $existing['review_id']]);
    } else {
        $stmt = $db->prepare("INSERT INTO customer_reviews (user_id, location, rating, review_text, is_homepage_testimonial, is_approved) VALUES (?, ?, ?, ?, 1, 1)");
        $stmt->execute([$user_id, $location, $rating, $review_text]);
    }
}

echo json_encode(['success' => true]);