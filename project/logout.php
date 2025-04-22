<?php
require 'session_start.php';
require 'auth_helper.php';
require 'db.php';

if ($_SESSION['role'] === 'Client') {
    // Delete the uncompleted order of this session (uc_order_id = $_SESSION['uc_order_id'])
    $sql = "SELECT * FROM uncompleted_orders WHERE id = :order_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['order_id' => $_SESSION['uc_order_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $delete_sql = $pdo->prepare("
            DELETE FROM uncompleted_orders
            WHERE id = :order_id
        ");
        $delete_sql->execute(['order_id' => $_SESSION['uc_order_id']]);
        $delete_details_sql = $pdo->prepare("
            DELETE FROM uncompleted_order_details
            WHERE orderid = :order_id
        ");
        $delete_details_sql->execute(['order_id' => $_SESSION['uc_order_id']]);

        // Fetch the maximum id from uncompleted_orders table
        $maxIdStmt = $pdo->query("SELECT MAX(id) AS max_id FROM uncompleted_orders");
        $maxId = $maxIdStmt->fetch(PDO::FETCH_ASSOC)['max_id'];

        // Reset AUTO_INCREMENT to max_id + 1
        if ($maxId !== null) {
            $resetIdSql = "ALTER TABLE uncompleted_orders AUTO_INCREMENT = " . ($maxId + 1);
            $pdo->exec($resetIdSql); // Directly execute the query without prepared statements
        }
    }
}

session_unset(); // Unset all session variables
session_destroy(); // Destroy the session
header("Location: index.php"); // Redirect to index.php
exit();
?>
