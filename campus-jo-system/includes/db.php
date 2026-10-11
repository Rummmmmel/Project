<?php
// DATABASE CONNECTION (PDO) - included by every page that needs the database.
// XAMPP defaults: user "root", empty password. Change if yours is different.

$host = 'localhost';
$db   = 'campus_jo';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed. Check includes/db.php and that MySQL is running in XAMPP.');
}
