<?php
/**
 * config.php — COPS (Canteen Ordering Platform System)
 * Settings + session + database + loads every shared include.
 * Every page starts with:  require_once __DIR__ . '/config.php';
 * Staff pages must define('COPS_AREA', 'staff') BEFORE including this file (separate session cookie).
 */
if (!defined('COPS_AREA')) define('COPS_AREA', 'public');
if (!defined('COPS_DEBUG')) define('COPS_DEBUG', false);

// ── Settings you can change ───────────────────────────────────────────────
const COPS_MAX_QTY_PER_ITEM       = 10;     // most of one item per reservation
const COPS_MAX_LINES              = 15;     // most different items per reservation
const COPS_MAX_ACTIVE_PER_STUDENT = 3;      // open reservations per student
const COPS_ENFORCE_SLOT_CUTOFF    = false;  // true = a slot closes COPS_SLOT_CUTOFF_MINUTES before it starts
const COPS_SLOT_CUTOFF_MINUTES    = 10;
const COPS_STAFF_IDLE_SECONDS     = 1800;   // staff are logged out after 30 min idle
const COPS_PAYMENT_NOTE           = 'You pay at the counter when you pick it up.';
const COPS_INFO                   = ['location' => '', 'hours' => 'Open 6:00 AM – 6:00 PM'];

// ── Session ───────────────────────────────────────────────────────────────
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    ini_set('session.use_strict_mode', '1');
    session_name(COPS_AREA === 'staff' ? 'COPS_STAFF' : 'COPS_SID');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => $https]);
    session_start();
}

date_default_timezone_set('Asia/Manila');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ── Database ──────────────────────────────────────────────────────────────
try {
    $conn = new mysqli('localhost', 'root', '', 'canteen_cmdi_db');
    $conn->set_charset('utf8mb4');
    $conn->query("SET time_zone = '+08:00'");   // same clock as PHP (Asia/Manila)
} catch (mysqli_sql_exception $e) {
    error_log('COPS database connection failed: ' . $e->getMessage());
    http_response_code(503);
    die('The canteen system is temporarily unavailable. Please try again shortly.');
}

// ── Shared code ───────────────────────────────────────────────────────────
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/reservations.php';
require_once __DIR__ . '/includes/menu_views.php';
require_once __DIR__ . '/includes/auth.php';

/**
 * The logged-in student (array) or null. Re-checked against the database on every call,
 * so a deleted or banned account is logged out immediately. The cart is kept.
 */
function getLoggedInUser(mysqli $conn): ?array
{
    $s = $_SESSION['user'] ?? null;
    if (!is_array($s) || empty($s['id'])) return null;

    $id = (int)$s['id'];
    $row = db_one($conn, "SELECT id, student_id, username, first_name, last_name, role FROM users WHERE id = ? AND is_banned = 0 LIMIT 1", 'i', [$id]);
    if (!$row) { unset($_SESSION['user']); return null; }

    $row['name'] = trim($row['first_name'] . ' ' . $row['last_name']);
    return $row;
}

/** Human-friendly names for product categories (keys match products.category). */
function categoryLabel(string $key): string
{
    static $labels = ['rice_meals' => 'Rice Meals', 'snacks' => 'Snacks', 'drinks' => 'Drinks', 'desserts' => 'Desserts'];
    return $labels[$key] ?? ucwords(str_replace('_', ' ', $key));
}