<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_mathventure"; // pastikan nama database sesuai

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
