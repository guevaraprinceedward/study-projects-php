<?php
/**
 * Kislap Booth ✨ — Core App Configuration
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

define('SITE_NAME', 'Kislap Booth');

// Auto-detect SITE_URL so this works regardless of the folder path it's placed in
if (!defined('SITE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    // If we're inside a subfolder like /auth or /admin or /api, strip it back to the project root
    foreach (['/auth', '/admin', '/api'] as $sub) {
        if (substr($scriptDir, -strlen($sub)) === $sub) {
            $scriptDir = substr($scriptDir, 0, -strlen($sub));
            break;
        }
    }
    define('SITE_URL', $protocol . $host . $scriptDir);
}

define('CURRENCY_SYMBOL', '₱');
define('UPLOAD_DIR', __DIR__ . '/../uploads/orders');
define('UPLOAD_URL', SITE_URL . '/uploads/orders');

require_once __DIR__ . '/database.php';

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}
function verify_csrf(): bool {
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
function clean_input(?string $value): string {
    return trim(strip_tags($value ?? ''));
}

function is_logged_in(): bool { return !empty($_SESSION['customer_id']); }
function current_customer_id(): ?int { return $_SESSION['customer_id'] ?? null; }

function is_admin_logged_in(): bool { return !empty($_SESSION['admin_id']); }
function require_admin(): void {
    if (!is_admin_logged_in()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
function get_flash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function generate_order_number(): string {
    return 'KB' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');