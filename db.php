<?php
// db.php - Database connection using PHP Data Objects (PDO)
$host    = 'localhost';
$dbname  = 'ui_hotels_db';
$user    = 'root';
$pass    = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // Prepared statements protect against SQL Injection
    ]);
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>
