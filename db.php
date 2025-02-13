<?php
$host = 'localhost';
$dbname = 'user_management';
$username = 'root'; // Replace with your DB username
$password = '2377'; // Replace with your DB password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
