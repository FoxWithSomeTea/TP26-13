<?php
// Load the database connection
require_once "db.php";

// Fetch all students from the "students" table
$sql = "SELECT * FROM students";
$result = $conn->query($sql);

// Collect all rows into an array
$products = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Return the data as JSON so the frontend JavaScript can use it
echo json_encode($products);
?>
