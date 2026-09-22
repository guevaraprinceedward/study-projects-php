<?php
/**
 * hotel-config.php — shared database connection for the Nocturne Manila Bay HOTEL system.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "nocturne_hotel";

$conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    die("Hotel database connection failed: " . $conn->connect_error .
        " — import database.sql and check your credentials in hotel-config.php");
}

$conn->set_charset("utf8mb4");

function currentTier(): string {
    if (isset($_SESSION['user']['tier']) && $_SESSION['user']['tier'] === 'vip') {
        return 'vip';
    }
    return 'regular';
}

function isHotelLoggedIn(): bool {
    return isset($_SESSION['user']['id']);
}

function requireHotelLogin(string $redirectTo = 'hotel-industry.php'): void {
    if (!isHotelLoggedIn()) {
        header("Location: register.php?next=" . urlencode($redirectTo));
        exit;
    }
}

function generateRef(string $prefix = 'NB'): string {
    return $prefix . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}