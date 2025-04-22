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

if (isset($data['userId'])) {
    $userId = intval($data['userId']);
    $currentTime = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("UPDATE users SET last_activity = ? WHERE id = ?");
    $stmt->bind_param("si", $currentTime, $userId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update activity.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'User ID not provided.']);
}

$conn->close();
?>
