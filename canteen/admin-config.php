<?php
/**
 * admin-config.php — COPS staff console
 * Database connection + session + shared helpers for:
 *   admin-login.php, admin-reservations.php, admin-logout.php
 *
 * Provides:
 *   $adminConn            mysqli connection (used by login + audit log)
 *   $conn                 same connection (admin-reservations.php uses this name too)
 *   getLoggedInAdmin()    returns the current staff member (array) or null
 *   adminLog()            writes one line to the audit log (never breaks the page)
 *
 * Tables it needs (created automatically if missing): admin_users, admin_logs
 * It does NOT create any account or password. See the SQL at the bottom of this
 * file for how to add the first staff account.
 */

/* ── Session ──────────────────────────────────────────────────────────────── */
if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => $https]);
    session_start();
}

date_default_timezone_set('Asia/Manila');

/** Staff are logged out after this many seconds without any request (8 hours). */
if (!defined('ADMIN_IDLE_SECONDS')) {
    define('ADMIN_IDLE_SECONDS', 8 * 3600);
}

/* ── Database ─────────────────────────────────────────────────────────────── */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $adminConn = new mysqli('localhost', 'root', '', 'canteen_cmdi_db');
    $adminConn->set_charset('utf8mb4');
    // No SET time_zone here on purpose: the student pages (config.php) don't set one either, so
    // both sides read and write times the same way (reservation "x min ago" stays correct).
} catch (mysqli_sql_exception $e) {
    error_log('COPS admin database connection failed: ' . $e->getMessage());
    http_response_code(503);
    die('The staff console is temporarily unavailable. Please try again shortly.');
}

/** admin-reservations.php reads reservations through $conn, so point it at the same connection. */
$conn = $adminConn;

/* ── Make sure the staff tables exist (checked once per session) ──────────── */
function ensureAdminTables(mysqli $db): void
{
    if (!empty($_SESSION['admin_tables_ok'])) {
        return;
    }
    try {
        $db->query(
            "CREATE TABLE IF NOT EXISTS `admin_users` (
                `id`         int(11)      NOT NULL AUTO_INCREMENT,
                `staff_id`   varchar(20)  NOT NULL,
                `username`   varchar(50)  NOT NULL,
                `password`   varchar(255) NOT NULL COMMENT 'password_hash() output',
                `full_name`  varchar(120) NOT NULL,
                `role`       enum('staff','admin') NOT NULL DEFAULT 'staff',
                `is_active`  tinyint(1)   NOT NULL DEFAULT 1,
                `last_login` datetime     DEFAULT NULL,
                `created_at` datetime     NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_admin_staff_id` (`staff_id`),
                UNIQUE KEY `uq_admin_username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $db->query(
            "CREATE TABLE IF NOT EXISTS `admin_logs` (
                `id`         int(11)      NOT NULL AUTO_INCREMENT,
                `admin_id`   int(11)      DEFAULT NULL,
                `action`     varchar(60)  NOT NULL,
                `target`     varchar(120) DEFAULT NULL,
                `details`    varchar(255) DEFAULT NULL,
                `ip_address` varchar(45)  DEFAULT NULL,
                `created_at` datetime     NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_logs_admin` (`admin_id`),
                KEY `idx_logs_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        // If an older admin_users table already existed, add any column the login needs but it lacks.
        $have = [];
        $cr = $db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS
                          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_users'");
        while ($c = $cr->fetch_assoc()) {
            $have[$c['COLUMN_NAME']] = true;
        }
        $need = [
            'staff_id'   => "varchar(20) NOT NULL DEFAULT ''",
            'username'   => "varchar(50) NOT NULL DEFAULT ''",
            'password'   => "varchar(255) NOT NULL DEFAULT ''",
            'full_name'  => "varchar(120) NOT NULL DEFAULT ''",
            'role'       => "enum('staff','admin') NOT NULL DEFAULT 'staff'",
            'is_active'  => "tinyint(1) NOT NULL DEFAULT 1",
            'last_login' => "datetime DEFAULT NULL",
        ];
        foreach ($need as $col => $def) {
            if (!isset($have[$col])) {
                $db->query("ALTER TABLE `admin_users` ADD COLUMN `$col` $def");
            }
        }
        $_SESSION['admin_tables_ok'] = true;
    } catch (Throwable $e) {
        error_log('COPS could not create staff tables: ' . $e->getMessage());
    }
}
ensureAdminTables($adminConn);

/* ── Helpers ──────────────────────────────────────────────────────────────── */

/**
 * Returns the logged-in staff member as an array
 * (id, staff_id, username, full_name, role) or null.
 *
 * The database is checked on every call, so a disabled or deleted account is
 * logged out immediately. Sessions also expire after ADMIN_IDLE_SECONDS.
 */
function getLoggedInAdmin(mysqli $db): ?array
{
    $s = $_SESSION['admin'] ?? null;
    if (!is_array($s) || empty($s['id'])) {
        return null;
    }

    $last = (int)($_SESSION['admin_last_seen'] ?? 0);
    if ($last > 0 && (time() - $last) > ADMIN_IDLE_SECONDS) {
        unset($_SESSION['admin'], $_SESSION['admin_last_seen']);
        return null;
    }

    try {
        $id   = (int)$s['id'];
        $stmt = $db->prepare(
            "SELECT id, staff_id, username, full_name, role
             FROM admin_users WHERE id = ? AND is_active = 1 LIMIT 1"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('COPS getLoggedInAdmin failed: ' . $e->getMessage());
        return null;
    }

    if (!$row) {
        unset($_SESSION['admin'], $_SESSION['admin_last_seen']);
        return null;
    }

    $row['id']                  = (int)$row['id'];
    $_SESSION['admin_last_seen'] = time();
    return $row;
}

/** Writes one audit-log line. Logging problems are recorded in the PHP error log, never shown to staff. */
function adminLog(mysqli $db, int $adminId, string $action, ?string $target = null, ?string $details = null): void
{
    try {
        $ip     = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
        $action = mb_substr($action, 0, 60);
        $target = $target !== null ? mb_substr($target, 0, 120) : null;
        $details = $details !== null ? mb_substr($details, 0, 255) : null;

        $stmt = $db->prepare(
            "INSERT INTO admin_logs (admin_id, action, target, details, ip_address) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('issss', $adminId, $action, $target, $details, $ip);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('COPS adminLog failed: ' . $e->getMessage());
    }
}

/*
 * ==========================================================================
 * FIRST STAFF ACCOUNT  (run once in phpMyAdmin → canteen_cmdi_db → SQL tab)
 * ==========================================================================
 * admin-login.php reads the table `admin_users` (not `staff_accounts`).
 * Easiest: import admin-user-insert.sql in phpMyAdmin (creates admin_prince).
 * Or add your own account like this. Replace the values, and use your own password.
 * You may type it as plain text: the first successful login converts it to a
 * secure hash automatically. Change it right after the first login.
 *
 *   INSERT INTO `admin_users` (`staff_id`, `username`, `password`, `full_name`, `role`, `is_active`)
 *   VALUES ('STF-0001', 'your.username', 'your-long-passphrase', 'Your Name', 'admin', 1);
 *
 * Then log in at admin-login.php using either STF-0001 or your.username.
 * ==========================================================================
 */