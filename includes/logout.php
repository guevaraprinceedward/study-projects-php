<?php
/**
 * logout.php — securely ends the guest's session and returns them home
 * with a clear logout confirmation.
 */
include_once 'hotel-config.php';

// Nothing to tear down if they weren't logged in anyway.
if (!isHotelLoggedIn()) {
    header("Location: hotel-industry.php");
    exit;
}

// ── Fully clear the session data ─────────────────────────────────────
$_SESSION = [];

// Also expire the session cookie itself — clearing $_SESSION alone leaves
// a stale cookie that PHP would otherwise try to resume on the next request.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );  
}

session_unset();
session_destroy();

// ── Back to the homepage with a one-time confirmation flag ──────────
header("Location: hotel-industry.php?loggedout=1");
exit;