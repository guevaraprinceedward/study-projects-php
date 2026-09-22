<?php
include_once 'hotel-config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: testimonial.php"); exit; }

function back(string $key, string $msg) {
    $_SESSION[$key] = $msg;
    header("Location: testimonial.php");
    exit;
}

$rating  = (int)($_POST['rating'] ?? 0);
$message = trim($_POST['message'] ?? '');

if ($rating < 1 || $rating > 5) back('testi_error', "Please choose a rating between 1 and 5 stars.");
if ($message === '' || mb_strlen($message) < 5) back('testi_error', "Please write a short message about your stay.");
if (mb_strlen($message) > 600) $message = mb_substr($message, 0, 600);

// Logged-in guests always post under their account name — never trust a
// posted "name" field for them, so no one can impersonate another account.
if (isHotelLoggedIn()) {
    $name = $_SESSION['user']['name'];
} else {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') back('testi_error', "Please tell us your name.");
    if (mb_strlen($name) > 100) $name = mb_substr($name, 0, 100);
}

$stmt = $conn->prepare("INSERT INTO testimonials (name, rating, message, is_approved) VALUES (?, ?, ?, 1)");
$stmt->bind_param("sis", $name, $rating, $message);

if ($stmt->execute()) {
    back('testi_notice', "Thank you, " . $name . " — your testimonial has been posted.");
} else {
    back('testi_error', "Something went wrong posting your testimonial. Please try again.");
}