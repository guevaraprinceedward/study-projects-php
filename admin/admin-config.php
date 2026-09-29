<?php
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * requireAdmin() — any logged-in admin-panel user (owner / super_admin / admin / manager)
 */
function requireAdmin() {
    if (!isset($_SESSION['admin'])) {
        header("Location: admin-index.php");
        exit();
    }
}

/**
 * requireRole(['owner']) — restrict a page to specific roles.
 * Call requireAdmin() first, then this, e.g. on admin-payroll.php:
 *     requireAdmin();
 *     requireRole(['owner']);
 */
function requireRole(array $allowedRoles) {
    requireAdmin();
    $role = $_SESSION['admin']['role'] ?? 'admin';
    if (!in_array($role, $allowedRoles, true)) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;padding:40px;text-align:center;color:#888">
                <h2 style="color:#c0392b">Access denied</h2>
                <p>This page is restricted to: ' . htmlspecialchars(implode(', ', $allowedRoles)) . '.</p>
                <p><a href="admin-dashboard.php">&larr; Back to Dashboard</a></p>
             </div>');
    }
}

$conn = new mysqli("localhost", "root", "", "restaurant_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ── Ensure admins table exists with a role column ───────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");
// In case an older install already had this table without `role`
$conn->query("ALTER TABLE admins ADD COLUMN IF NOT EXISTS role VARCHAR(20) NOT NULL DEFAULT 'admin'");

// Default admin account (only created the first time, thanks to INSERT IGNORE + UNIQUE username)
$conn->query("
    INSERT IGNORE INTO admins (username, password, role)
    VALUES ('Webmaster', '" . password_hash('ayoscoffeenegosyo_admin', PASSWORD_DEFAULT) . "', 'owner')
");
?>