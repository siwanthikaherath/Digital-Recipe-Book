<?php
// db.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "recipe_app";

try {
    $conn = "mysql:host=$servername;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($conn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

?>
