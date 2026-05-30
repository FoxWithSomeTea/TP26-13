<?php
// Database connection settings — update these to match your MySQL setup
$host = "localhost";
$user = "root";
$password = "";
$database = "my_database";

// Create a new MySQL connection using mysqli
$conn = new mysqli($host, $user, $password, $database);

// If the connection failed, show an error and stop
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
