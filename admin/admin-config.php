<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Admin session check — gamitin sa lahat ng admin pages
function requireAdmin() {
    if (!isset($_SESSION['admin'])) {
        header("Location: admin-index.php");
        exit();
    }
}

$conn = new mysqli("localhost", "root", "", "restaurant_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure admins table exists
$conn->query("
    CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Default admin account
$conn->query("
    INSERT IGNORE INTO admins (username, password)
    VALUES ('Webmaster', '" . password_hash('ayoscoffeenegosyo_admin', PASSWORD_DEFAULT) . "')
");
?>