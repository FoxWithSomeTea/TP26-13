<?php
$host = "sql113.infinityfree.com";
$user = "if0_41978049";
$password = "EY3lMpJzoc";
$database = "if0_41978049_db_dupeapp";

// Připojení k MySQL databázi
$conn = new mysqli($host, $user, $password, $database);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Database connection failed. Check db.php credentials.");
}
