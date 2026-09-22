<?php
require_once __DIR__ . '/../config/app.php';

if (!empty($_COOKIE['remember_token'])) {
    $stmt = getDB()->prepare("UPDATE users SET remember_token = NULL WHERE remember_token = ?");
    $stmt->execute([$_COOKIE['remember_token']]);
    setcookie('remember_token', '', time() - 3600, '/');
}

$_SESSION = [];
session_destroy();

header('Location: ' . SITE_URL . '/index.php?loggedout=1');
exit;