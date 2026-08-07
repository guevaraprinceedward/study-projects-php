<?php
/**
 * El Grande De La Torres — Database Connection
 * Database name: el_grande_torres
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'el_grande_torres');
define('DB_USER', 'root');
define('DB_PASS', '');       // default XAMPP MySQL password is empty
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die('We are unable to connect right now. Please import el_grande_torres.sql and check your credentials in config/database.php');
        }
    }
    return $pdo;
}