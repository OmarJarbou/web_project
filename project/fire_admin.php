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

if (isset($data['adminId'])) {
    $adminId = intval($data['adminId']);

    // Check if the admin exists and is currently an admin
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $stmt->bind_result($role);
    $stmt->fetch();
    $stmt->close();

    if ($role !== 'Admin') {
        echo json_encode(['success' => false, 'message' => 'The selected user is not an admin.']);
        exit;
    }

    // Update the role to 'client'
    $stmt = $conn->prepare("UPDATE users SET role = 'Client' WHERE id = ?");
    $stmt->bind_param("i", $adminId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Admin successfully converted to client.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update the role.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Admin ID not provided.']);
}

$conn->close();
?>
