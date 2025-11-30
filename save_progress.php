<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_mathventure";

$user_id = 1;

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) { die("DB Error"); }

$level_id = $_POST['level_id'] ?? null;
$stars    = $_POST['stars'] ?? null;

if ($level_id !== null) {

    $stmt = $conn->prepare("
        INSERT INTO user_progress (user_id, level_id, stars)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE stars = VALUES(stars)
    ");
    $stmt->bind_param("iii", $user_id, $level_id, $stars);
    $stmt->execute();
    echo "OK";
    exit;
}

echo "NO DATA";
?>