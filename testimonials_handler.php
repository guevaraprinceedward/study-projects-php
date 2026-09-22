<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';
header('Content-Type: application/json');

// ── SUBMIT REVIEW ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->query("CREATE TABLE IF NOT EXISTS testimonials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        branch VARCHAR(20) DEFAULT 'laguna',
        rating TINYINT(1) DEFAULT 5,
        message TEXT NOT NULL,
        is_approved TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $name    = trim($conn->real_escape_string($_POST['name'] ?? ''));
    $branch  = in_array($_POST['branch'] ?? '', ['laguna', 'manila']) ? $_POST['branch'] : 'laguna';
    $rating  = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $message = trim($conn->real_escape_string($_POST['message'] ?? ''));

    if (empty($name) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Name and message are required.']);
        exit();
    }
    if (strlen($name) > 100) {
        echo json_encode(['success' => false, 'message' => 'Name is too long.']);
        exit();
    }
    if (strlen($message) < 10) {
        echo json_encode(['success' => false, 'message' => 'Message is too short.']);
        exit();
    }
    if (strlen($message) > 500) {
        echo json_encode(['success' => false, 'message' => 'Message must be under 500 characters.']);
        exit();
    }

    $conn->query("INSERT INTO testimonials (name, branch, rating, message, is_approved)
                  VALUES ('$name', '$branch', $rating, '$message', 1)");

    echo json_encode(['success' => true, 'message' => 'Review submitted!']);
    exit();
}

// ── FETCH TESTIMONIALS ─────────────────────────────────────────────────────
$testimonials = $conn->query("
    SELECT id, name, branch, rating, message, created_at
    FROM testimonials
    WHERE is_approved = 1
    ORDER BY created_at DESC
    LIMIT 12
")->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'data' => $testimonials]);
?>