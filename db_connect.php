<?php
// db_connect.php

$host = 'localhost';
$dbname = 'truck_delivery_db'; // Change this to your database name
$user = 'root'; // Change this to your database user
$pass = ''; // Change this to your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
