<?php
$host = '127.0.0.1';
$db_name = 'event_platform';
$username = 'event_user';
$password = 'password123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection Error: " . $e->getMessage();
}
?>
