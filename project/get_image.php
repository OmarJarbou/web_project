<?php
require 'session_start.php';
require 'auth_helper.php';
// Database connection
$servername = "localhost";
$username = "root"; 
$password = "";     
$dbname = "qashan_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Validate and sanitize inputs
if (isset($_GET['id'], $_GET['table'], $_GET['column'], $_GET['mime'])) {
    $id = (int)$_GET['id']; // Sanitize ID as an integer
    $table_name = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['table']); // Allow only alphanumeric and underscores
    $image_column = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['column']); // Allow only alphanumeric and underscores
    $mime = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['mime']); // Allow only alphanumeric and underscores

    // Validate table name and column name against allowed values
    $allowed_tables = ['products','categories']; // List of allowed table names
    $allowed_columns = ['prodimg','catimg','catimg2']; // List of allowed column names

    if (!in_array($table_name, $allowed_tables) || !in_array($image_column, $allowed_columns)) {
        http_response_code(400);
        echo "Invalid request parameters.";
        exit;
    }

    // Prepare and execute the query
    $query = "SELECT $image_column, $mime FROM $table_name WHERE id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($image, $mime_type);
        $stmt->fetch();
        $stmt->close();
    } else {
        http_response_code(500);
        echo "Query preparation failed.";
        exit;
    }

    $conn->close();

    // Output the image
    if ($image) {
        header("Content-Type: $mime_type"); // Adjust as needed
        echo $image;
    } else {
        http_response_code(404);
        echo "Image not found.";
    }
} else {
    http_response_code(400);
    echo "Missing required parameters.";
}
?>
