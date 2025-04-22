<?php
require 'session_start.php';
require 'auth_helper.php';
header('Content-Type: application/json');
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "qashan_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['clientId'])) {
    $clientId = intval($data['clientId']);

    $stmt = $conn->prepare("UPDATE users SET role = 'Admin' WHERE id = ? AND role = 'Client'");
    $stmt->bind_param("i", $clientId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Client successfully converted to admin.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to hire the client.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Client ID not provided.']);
}

$conn->close();
?>
