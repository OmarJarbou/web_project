<?php
header('Content-Type: application/json');
require 'session_start.php';
require 'auth_helper.php';
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

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'Client'");
    $stmt->bind_param("i", $clientId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Client successfully deleted.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete the client.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Client ID not provided.']);
}

$conn->close();
?>
