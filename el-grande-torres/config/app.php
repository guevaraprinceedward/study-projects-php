<?php
/**
 * El Grande De La Torres — Core App Configuration
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

define('SITE_NAME', 'El Grande De La Torres');
define('SITE_URL', 'http://localhost/restaurant/study-projects-php/el-grande-torres');
define('CURRENCY_SYMBOL', '₱');
define('SHIPPING_FEE_STANDARD', 250.00);
define('FREE_SHIPPING_THRESHOLD', 15000.00);
define('DEVELOPER_CREDIT', 'Prince Edward Guevara');

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
    $token = $_POST['csrf_token'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
function require_csrf_or_fail(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf()) {
        http_response_code(403);
        die('Invalid request. Please refresh the page and try again.');
    }
}

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
function clean_input(?string $value): string {
    return trim(strip_tags($value ?? ''));
}

function is_logged_in(): bool { return !empty($_SESSION['user_id']); }
function is_admin_logged_in(): bool { return !empty($_SESSION['admin_id']); }

function require_login(): void {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? (SITE_URL . '/index.php');
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit;
    }
}
function require_admin(): void {
    if (!is_admin_logged_in()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}
function current_user_id(): ?int { return $_SESSION['user_id'] ?? null; }

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

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
