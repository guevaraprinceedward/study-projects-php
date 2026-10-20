<?php
/**
 * admin-logout.php — staff logout
 * Records the logout in the database (admin_logs + auth_events), clears the staff
 * part of the session, and ends the whole session when no student is using it.
 */
include __DIR__ . '/admin-config.php';
require_once __DIR__ . '/auth-helpers.php';

header('Cache-Control: no-store');

$admin = getLoggedInAdmin($adminConn);
if ($admin) {
    adminLog($adminConn, (int)$admin['id'], 'logout', null, 'Logged out');
    authLogEvent($adminConn, 'staff', (int)$admin['id'], (string)$admin['username'], 'logout');
}

unset($_SESSION['admin'], $_SESSION['admin_csrf'], $_SESSION['admin_last_seen']);

if (empty($_SESSION['user'])) {
    authDestroySession();
}

header('Location: admin-login.php?out=1');
exit();