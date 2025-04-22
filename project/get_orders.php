<?php
require 'session_start.php';
require 'auth_helper.php';
header('Content-Type: application/json');

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
ini_set('error_log', __DIR__ . '/php_error_log.txt');


// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "qashan_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// Fetch admin users
$query =   "SELECT
                o.id AS order_id,
                u.username AS username,
                u.contact AS contact,
                o.address AS order_address,
                o.date AS order_date,
                o.totalcost AS order_cost,
                o.status AS order_status
            FROM
                orders o
            JOIN
                users u ON o.userid = u.id;";
$result = $conn->query($query);

$orders = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

$conn->close();

// Return JSON response
echo json_encode(['success' => true, 'data' => $orders]);
?>
