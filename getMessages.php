<?php
// Tell the browser we're returning JSON
header("Content-Type: application/json");

// Load the database connection
require_once "db.php";

// Fetch all messages from the "messages" table
$sql = "SELECT * FROM messages";
$result = $conn->query($sql);

// Collect all rows into an array
$products = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Return the data as JSON
echo json_encode($products);
?>
