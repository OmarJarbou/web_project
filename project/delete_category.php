<?php
require 'session_start.php';
require 'auth_helper.php';
header('Content-Type: application/json');

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error_log.txt');
error_reporting(E_ALL);

// Debugging: Log raw input
file_put_contents('debug.txt', file_get_contents('php://input') . PHP_EOL, FILE_APPEND);

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['delete_category_id'])) {
    $categoryId = intval($data['delete_category_id']);

    try {
        // Database connection
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "qashan_db";

        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }

        // Delete the product
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }

        $stmt->bind_param("i", $categoryId);
        if (!$stmt->execute()) {
            throw new Exception("Execution failed: " . $stmt->error);
        }

        $stmt->close();

        // Step 2: Rearrange IDs for remaining products
        // Fetch all remaining products in ascending order
        $result = $conn->query("SELECT id FROM categories ORDER BY id ASC");

        if ($result->num_rows > 0) {
            $currentId = 1;
            while ($row = $result->fetch_assoc()) {
                $actualId = $row['id'];
                if ($actualId != $currentId) {
                    // Update the ID to maintain sequential order
                    $stmtUpdate = $conn->prepare("UPDATE categories SET id = ? WHERE id = ?");
                    $stmtUpdate->bind_param("ii", $currentId, $actualId);
                    $stmtUpdate->execute();
                    $stmtUpdate->close();
                }
                $currentId++;
            }
        }

        // Step 3: Reset AUTO_INCREMENT to the next available ID
        $nextId = $currentId; // This is the next available ID
        $conn->query("ALTER TABLE categories AUTO_INCREMENT = $nextId");
        $conn->close();

        echo json_encode(['success' => true, 'message' => "category ID $categoryId deleted successfully."]);
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'message' => "Error occurred: " . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No category ID provided.']);
}
?>
