<?php
/**
 * auth-helpers.php — shared login helpers for COPS
 * Used by: log-in.php, log-out.php, admin-login.php, admin-logout.php
 *
 * Every function takes the mysqli connection as its first argument, so this file
 * works with both config.php ($conn) and admin-config.php ($adminConn).
 *
 * Creates the `auth_events` table automatically. It is the login / logout /
 * failed-attempt history, and it is also what powers the lock-out after too
 * many wrong passwords (so the lock cannot be bypassed by clearing cookies).
 */

const AUTH_MAX_FAILS    = 5;    // wrong passwords for one account from one device ...
const AUTH_IP_MAX_FAILS = 25;   // ... or wrong passwords from one device across all accounts
const AUTH_LOCK_SECS    = 300;  // ... lock that device for this long (5 minutes)

/** The visitor's IP address as seen by the web server. */
function authClientIp(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

/** Normalises what the person typed so the same account always maps to the same key. */
function authKey(string $identifier): string
{
    return mb_strtolower(mb_substr(trim($identifier), 0, 100));
}

/** Creates the auth_events table if it is missing (checked once per session). */
function authEnsureEventsTable(mysqli $db): void
{
    if (!empty($_SESSION['auth_events_ok'])) {
        return;
    }
    try {
        $db->query(
            "CREATE TABLE IF NOT EXISTS `auth_events` (
                `id`         bigint(20)   NOT NULL AUTO_INCREMENT,
                `scope`      enum('student','staff') NOT NULL,
                `user_id`    int(11)      DEFAULT NULL,
                `identifier` varchar(100) NOT NULL DEFAULT '',
                `event`      enum('login','logout','failed','blocked') NOT NULL,
                `ip_address` varchar(45)  NOT NULL DEFAULT '',
                `user_agent` varchar(255) DEFAULT NULL,
                `created_at` datetime     NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_auth_account` (`scope`, `event`, `identifier`, `ip_address`, `created_at`),
                KEY `idx_auth_ip`      (`scope`, `event`, `ip_address`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $_SESSION['auth_events_ok'] = true;
    } catch (Throwable $e) {
        error_log('COPS could not create auth_events: ' . $e->getMessage());
    }
}

/** Records login / logout / failed / blocked. Never throws, so a logging problem cannot block a login. */
function authLogEvent(mysqli $db, string $scope, ?int $userId, string $identifier, string $event): void
{
    authEnsureEventsTable($db);
    try {
        $key = authKey($identifier);
        $ip  = authClientIp();
        $ua  = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $stmt = $db->prepare(
            "INSERT INTO auth_events (scope, user_id, identifier, event, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sissss', $scope, $userId, $key, $event, $ip, $ua);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('COPS authLogEvent failed: ' . $e->getMessage());
    }
}

/**
 * Seconds this visitor must still wait before trying again (0 = not locked).
 * A successful login for the account resets its failure count.
 */
function authLockRemaining(mysqli $db, string $scope, string $identifier): int
{
    authEnsureEventsTable($db);
    $remaining = 0;
    try {
        $key = authKey($identifier);
        $ip  = authClientIp();
        $win = (int)AUTH_LOCK_SECS;

        $stmt = $db->prepare(
            "SELECT COUNT(*) AS c, TIMESTAMPDIFF(SECOND, MAX(created_at), NOW()) AS since
             FROM auth_events
             WHERE scope = ? AND event = 'failed' AND identifier = ? AND ip_address = ?
               AND created_at > NOW() - INTERVAL {$win} SECOND
               AND created_at > COALESCE(
                    (SELECT MAX(l.created_at) FROM auth_events l
                     WHERE l.scope = ? AND l.event = 'login' AND l.identifier = ?),
                    '1970-01-01 00:00:00')"
        );
        $stmt->bind_param('sssss', $scope, $key, $ip, $scope, $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row && (int)$row['c'] >= AUTH_MAX_FAILS) {
            $remaining = max($remaining, $win - max(0, (int)$row['since']));
        }

        $stmt = $db->prepare(
            "SELECT COUNT(*) AS c, TIMESTAMPDIFF(SECOND, MAX(created_at), NOW()) AS since
             FROM auth_events
             WHERE scope = ? AND event = 'failed' AND ip_address = ?
               AND created_at > NOW() - INTERVAL {$win} SECOND"
        );
        $stmt->bind_param('ss', $scope, $ip);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row && (int)$row['c'] >= AUTH_IP_MAX_FAILS) {
            $remaining = max($remaining, $win - max(0, (int)$row['since']));
        }
    } catch (Throwable $e) {
        error_log('COPS authLockRemaining failed: ' . $e->getMessage());
    }
    return $remaining;
}

/**
 * Checks a typed password against the stored value.
 * Returns [bool $ok, ?string $newHash]. When $newHash is not null, save it to the
 * database: it means the stored value was plain text (typed by hand in phpMyAdmin)
 * or an outdated hash, and it has just been upgraded.
 */
function authCheckPassword(string $stored, string $plain): array
{
    if ($stored === '') {
        return [false, null];
    }
    $info = password_get_info($stored);
    if (!empty($info['algo'])) {
        if (!password_verify($plain, $stored)) {
            return [false, null];
        }
        return [true, password_needs_rehash($stored, PASSWORD_DEFAULT) ? password_hash($plain, PASSWORD_DEFAULT) : null];
    }
    if (hash_equals($stored, $plain)) {
        return [true, password_hash($plain, PASSWORD_DEFAULT)];
    }
    return [false, null];
}

/**
 * Only allows a plain page name such as "menu.php" or "menu.php?cat=drinks" as a
 * redirect target, so the login page can never send someone to another website.
 */
function authSafeNext($next, string $default): string {
    
    $next = is_string($next) ? trim($next) : '';
        if ($next === '' || !preg_match('/^[A-Za-z0-9_-]+\.php(\?[A-Za-z0-9_=&%.-]*)?$/', $next)) {
            
            return $default;
        }

        if (stripos($next, 'log-in.php') === 0 || stripos($next, 'log-out.php') === 0 || stripos($next, 'admin-login.php') === 0) {
        
            return $default;
        }

        return $next;

        }

/** Ends the session completely and removes its cookie. */
function authDestroySession(): void {

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        
        $p = session_get_cookie_params();
        
            setcookie(session_name(), '', [
                
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => 'Lax',
        ]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        
        session_destroy();
    
    }
    
}