<?php
/**
 * log-out.php — student logout
 * Records the logout in the database (auth_events), clears the student part of the
 * session, and ends the whole session when no staff member is using it.
 */
include 'config.php';
require_once 'auth-helpers.php';

header('Cache-Control: no-store');

$user = null;
try {
    $user = getLoggedInUser($conn);
} catch (Throwable $e) {
    error_log('COPS log-out lookup failed: ' . $e->getMessage());
}

if ($user) {
    $who = (string)($user['student_id'] ?: $user['username']);
    authLogEvent($conn, 'student', (int)$user['id'], $who, 'logout');
}

unset($_SESSION['user'], $_SESSION['csrf'], $_SESSION['login_csrf']);

// Nobody else is signed in on this browser session, so end it completely.
if (empty($_SESSION['admin'])) {
    authDestroySession();
}

header('Location: log-in.php?out=1');
exit();