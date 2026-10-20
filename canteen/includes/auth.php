<?php
/**
 * includes/auth.php — staff authentication.
 * Load config.php with COPS_AREA = 'staff' first (separate session cookie).
 */
require_once __DIR__ . '/db.php';

const STAFF_MAX_FAILS   = 5;     // failed attempts …
const STAFF_FAIL_WINDOW = 600;   // … within this many seconds → temporary lock

/** The signed-in staff member, or null. Re-checked against the database on every request. */
function staff_current(mysqli $c): ?array
{
    $s = $_SESSION['staff'] ?? null;
    if (!is_array($s) || empty($s['id'])) return null;

    if (time() - (int)($_SESSION['staff_last_seen'] ?? 0) > COPS_STAFF_IDLE_SECONDS) {
        staff_clear_session();
        return null;
    }

    $row = db_one($c, "SELECT id, full_name, username, role FROM staff_accounts WHERE id = ? AND status = 'active'", 'i', [(int)$s['id']]);
    if (!$row) { staff_clear_session(); return null; }

    $_SESSION['staff_last_seen'] = time();
    return $row;
}

/** Blocks access: JSON clients get 401, browsers are sent to the login page. */
function staff_require(mysqli $c, bool $json = false): array
{
    $staff = staff_current($c);
    if ($staff) return $staff;
    if ($json) json_out(['ok' => false, 'login' => true, 'message' => 'Your session has ended. Please log in again.'], 401);
    redirect('admin-login.php');
}

function staff_clear_session(): void
{
    unset($_SESSION['staff'], $_SESSION['staff_last_seen']);
}

/** Seconds the caller must still wait, or 0 if login is allowed. */
function staff_lock_remaining(mysqli $c, string $username, string $ip): int
{
    $row = db_one($c,
        "SELECT COUNT(*) AS n, UNIX_TIMESTAMP(MAX(attempted_at)) AS last_ts
         FROM staff_login_attempts
         WHERE was_success = 0 AND ip_address = ? AND username = ? AND attempted_at > (NOW() - INTERVAL " . STAFF_FAIL_WINDOW . " SECOND)",
        'ss', [$ip, mb_strtolower($username)]);
    if ((int)$row['n'] < STAFF_MAX_FAILS) return 0;
    return max(0, (int)$row['last_ts'] + 60 - time());   // 60 s after the most recent failure
}

function staff_record_attempt(mysqli $c, string $username, string $ip, bool $ok): void
{
    db_exec($c, "INSERT INTO staff_login_attempts (username, ip_address, was_success) VALUES (?,?,?)", 'ssi', [mb_strtolower(mb_substr($username, 0, 100)), $ip, $ok ? 1 : 0]);
    if ($ok) {
        db_exec($c, "DELETE FROM staff_login_attempts WHERE username = ? AND ip_address = ?", 'ss', [mb_strtolower($username), $ip]);
    }
    db_exec($c, "DELETE FROM staff_login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)");
}

/**
 * Checks the credentials. Returns [staffRow|null, errorMessage|null].
 * The same message is used for "no such user" and "wrong password".
 */
function staff_attempt_login(mysqli $c, string $username, string $password): array
{
    $ip   = client_ip();
    $wait = staff_lock_remaining($c, $username, $ip);
    if ($wait > 0) return [null, "Too many failed attempts. Try again in {$wait} seconds."];

    $row = db_one($c, "SELECT id, full_name, username, role, status, password_hash FROM staff_accounts WHERE username = ? OR email = ? LIMIT 1", 'ss', [$username, $username]);

    // Always run a real hash comparison, so response time does not reveal whether the account exists
    $hash = $row['password_hash'] ?? password_hash('not-a-real-password', PASSWORD_DEFAULT);
    $ok   = password_verify($password, $hash) && $row !== null;

    if (!$ok) {
        staff_record_attempt($c, $username, $ip, false);
        return [null, 'Incorrect username or password.'];
    }
    if ($row['status'] !== 'active') {
        return [null, 'This staff account is disabled. Please contact the administrator.'];
    }

    staff_record_attempt($c, $username, $ip, true);
    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
        db_exec($c, "UPDATE staff_accounts SET password_hash = ? WHERE id = ?", 'si', [password_hash($password, PASSWORD_DEFAULT), (int)$row['id']]);
    }
    db_exec($c, "UPDATE staff_accounts SET last_login_at = NOW() WHERE id = ?", 'i', [(int)$row['id']]);

    session_regenerate_id(true);
    $_SESSION['staff'] = ['id' => (int)$row['id']];
    $_SESSION['staff_last_seen'] = time();
    unset($_SESSION['csrf']);
    return [$row, null];
}

/** Ends the staff session completely (data, cookie, server-side session). */
function staff_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}