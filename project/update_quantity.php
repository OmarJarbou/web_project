<?php
require 'session_start.php';
require 'auth_helper.php';

// Ensure the user is logged in
redirectIfNotLoggedIn();

require 'db.php'; // Include the database connection

header('Content-Type: application/json');

// Get the JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['productId'], $data['action'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input.']);
    exit;
}

$productId = intval($data['productId']);
$action = $data['action'];

// Validate product ID and action
if ($productId <= 0 || !in_array($action, ['increase', 'decrease'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID or action.']);
    exit;
}

try {
    // Fetch product details from the database
    $prodstmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $prodstmt->execute(['id' => $productId]);
    $product = $prodstmt->fetch();

    // Fetch current product quantity and total count
    $stmt = $pdo->prepare("
        SELECT prodcount, totalcount 
        FROM uncompleted_order_details 
        WHERE orderid = :orderid AND prodid = :prodid
    ");
    $stmt->execute([
        'orderid' => $_SESSION['uc_order_id'],
        'prodid' => $productId
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Product not found in order.']);
        exit;
    }

    $currentQuantity = $row['prodcount'];
    $totalCount = $row['totalcount'];

    // Calculate new quantity
    $incdec = 0;
    if ($action === 'increase') {
        if ($currentQuantity >= $totalCount) {
            echo json_encode(['success' => false, 'error' => 'Cannot exceed available stock.']);
            exit;
        }
        $newQuantity = $currentQuantity + 1;
        $incdec = 1;
    } else { // decrease
        if ($currentQuantity <= 1) {
            echo json_encode(['success' => false, 'error' => 'Quantity cannot be less than 1.']);
            exit;
        }
        $newQuantity = $currentQuantity - 1;
        $incdec = -1;
    }

    // Update quantity in the database
    $updateStmt = $pdo->prepare("
        UPDATE uncompleted_order_details 
        SET prodcount = :prodcount 
        WHERE orderid = :orderid AND prodid = :prodid
    ");
    $updateStmt->execute([
        'prodcount' => $newQuantity,
        'orderid' => $_SESSION['uc_order_id'],
        'prodid' => $productId
    ]);

    $updateTotalcost = "UPDATE uncompleted_orders SET totalcost = (totalcost + ". $incdec*intval($product['cost']) .") WHERE id = ". $_SESSION['uc_order_id'] .";";
    $pdo->query($updateTotalcost);

    echo json_encode([
        'success' => true,
        'newQuantity' => $newQuantity,
        'totalCount' => $totalCount
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
