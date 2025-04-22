<?php
require 'session_start.php';
require 'auth_helper.php';

// Restrict access to admins only
redirectIfNotAdmin();

$dialogDisplay = "none";

// Database connection
$servername = "localhost";
$username = "root"; 
$password = "";     
$dbname = "qashan_db";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->begin_transaction();

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all categories securely
$categories = [];
$categoryQuery = "SELECT id, category FROM categories";
$result = $conn->query($categoryQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[$row['category']] = $row['id'];
    }
} else {
    $dialogDisplay = "block";
    displayDialog("Unable to fetch categories. Please try again later.", "error", $dialogDisplay);
    exit;
}

// Retrieve all categories
$categories2 = [];

$stmtcat = $conn->prepare("SELECT id, category FROM categories ORDER BY id");
if ($stmtcat) {
    $stmtcat->execute();
    $resultcat = $stmtcat->get_result();
    
    while ($rowcat = $resultcat->fetch_assoc()) {
        $categories2[] = $rowcat;
    }

    $stmtcat->close();
} else {
    die("Failed to prepare the SQL statement: " . $conn->error);
}

// Retrieve all products
$products = [];

$prodstmt = $conn->prepare("SELECT id, name, catid, cost, quantity FROM products");
if ($prodstmt) {
    $prodstmt->execute();
    $prodresult = $prodstmt->get_result();
    
    while ($prodrow = $prodresult->fetch_assoc()) {
        $products[] = $prodrow;
    }

    $prodstmt->close();
} else {
    die("Failed to prepare the SQL statement: " . $conn->error);
}

// Fetch admin users
// $admins = [];
// $admins_query = "SELECT id, CONCAT(firstname, ' ', lastname) AS name, department, email, contact, gender, location, role 
//           FROM users 
//           WHERE role = 'admin'";
// $admins_result = $conn->query($admins_query);
// if ($admins_result && $admins_result->num_rows > 0) {
//     while ($row = $admins_result->fetch_assoc()) {
//         $admins[] = $row;
//     }
// } else {
//     echo "<script>console.error('No admin data found.');</script>";
// }

// Initialize success message
$message = null;
$messageType = null;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $errors = [];
    $operation = "none";
    if (isset($_POST['operation'])) {
        $operation = $_POST['operation'];
        $operation = htmlspecialchars($operation);
    } else {
            $message = "No Operation!";
            $messageType = "error";

            // Redirect to the same page with error messages
            header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . urlencode($messageType));
            exit;
    }
    
    if ($operation == "editcategory") {
        //---------------------------------- Edit Category -------------------------------------//
        $image1Updated = 'no';
        $image2Updated = 'no';
        // Update existing cat
        if(!isset($_POST['category_id'])){
            $errors[] = "Category ID is required.";
        } else {
            $category_id = (int)$_POST['category_id'];
        }

        // Validate category name
        if (!isset($_POST['catname']) || empty(trim($_POST['catname']))) {
            $errors[] = "Category name is required.";
        } else {
            $catname = trim($_POST['catname']);
            if (strlen($catname) > 20) {
                $errors[] = "Category name must not exceed 20 characters.";
            }
        }

        // Validate cat image1
        if (!isset($_FILES['catimage']) || $_FILES['catimage']['error'] !== UPLOAD_ERR_OK) {
            $image1Updated = 'no';
        } else {
            $catimage = file_get_contents($_FILES['catimage']['tmp_name']);
            $catimage_mime_type = mime_content_type($_FILES['catimage']['tmp_name']); // Get the MIME type of the image
            $image1Updated = 'yes';
        }

        // Validate cat image2
        if (!isset($_FILES['catimage2']) || $_FILES['catimage2']['error'] !== UPLOAD_ERR_OK) {
            $image2Updated = 'no';
        } else {
            $catimage2 = file_get_contents($_FILES['catimage2']['tmp_name']);
            $catimage2_mime_type = mime_content_type($_FILES['catimage2']['tmp_name']); // Get the MIME type of the image
            $image2Updated = 'yes';
        }

        // If there are no errors, insert the product
        if (empty($errors)) {
            $stmtEditCat = $conn->prepare("UPDATE categories SET category = ? WHERE id = ?");
            $stmtEditCat->bind_param("si", $catname, $category_id);

            $stmtEditCat2 = null;
            if($image1Updated == 'yes'){
                $stmtEditCat2 = $conn->prepare("UPDATE categories SET catimg = ?, mime_type = ? WHERE id = ?");
                $stmtEditCat2->bind_param("ssi", $catimage, $catimage_mime_type, $category_id);
            }

            $stmtEditCat3 = null;
            if($image2Updated == 'yes'){
                $stmtEditCat3 = $conn->prepare("UPDATE categories SET catimg2 = ?, mime_type2 = ? WHERE id = ?");
                $stmtEditCat3->bind_param("ssi", $catimage2, $catimage2_mime_type, $category_id);
            }

            if ($stmtEditCat->execute() && ($image1Updated == 'yes'? $stmtEditCat2->execute() : true) && ($image2Updated == 'yes'? $stmtEditCat3->execute() : true)) {
                $message = "Category updated successfully!";
                $messageType = "success";
                $conn->commit();
            } else {
                $message = "Error: Could not update category.";
                $messageType = "error";
            }

            $stmtEditCat->close();
            if($image1Updated == 'yes'){
                $stmtEditCat2->close();
            }
            if($image2Updated == 'yes'){
                $stmtEditCat3->close();
            }
            // Redirect to the same page with a success or error message
            header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . urlencode($messageType));
            exit;
        } else {
            $message = implode("<br>", $errors);
            $messageType = "error";

            // Redirect to the same page with error messages
            header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . urlencode($messageType));
            exit;
        }
    }
    else if ($operation == "editproduct") {
        //---------------------------------- Edit Product -------------------------------------//
        $imageUpdated = 'no';
        // Update existing product
        if(!isset($_POST['product_id'])){
            $errors[] = "Product ID is required.";
        } else {
            $product_id = (int)$_POST['product_id'];
        }

        // Validate product name
        if (!isset($_POST['name']) || empty(trim($_POST['name']))) {
            $errors[] = "Product name is required.";
        } else {
            $name = trim($_POST['name']);
            if (strlen($name) > 20) {
                $errors[] = "Product name must not exceed 20 characters.";
            }
        }

        // Validate category
        if (!isset($_POST['category']) || empty(trim($_POST['category']))) {
            $errors[] = "Category is required.";
        } else {
            $category = trim($_POST['category']);
            if (!array_key_exists($category, $categories)) {
                $errors[] = "Invalid category selected.";
            } else {
                $catid = $categories[$category];
            }
        }

        // Validate cost
        if (!isset($_POST['cost']) || empty($_POST['cost']) || !filter_var($_POST['cost'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
            $errors[] = "Cost must be a positive integer.";
        } else {
            $cost = (int)$_POST['cost'];
        }

        // Validate quantity
        if (!isset($_POST['quantity']) || empty($_POST['quantity']) || !filter_var($_POST['quantity'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
            $errors[] = "Quantity must be a positive integer.";
        } else {
            $quantity = (int)$_POST['quantity'];
        }

        // Validate product image
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $imageUpdated = 'no';
        } else {
            $image = file_get_contents($_FILES['image']['tmp_name']);
            $mime_type = mime_content_type($_FILES['image']['tmp_name']);
            $imageUpdated = 'yes';
        }

        // If there are no errors, insert the product
        if (empty($errors)) {
            $stmtEditProd = $conn->prepare("UPDATE products SET name = ?, catid = ?, cost = ?, quantity = ? WHERE id = ?");
            $stmtEditProd->bind_param("siiii", $name, $catid, $cost, $quantity, $product_id);

            $stmtEditProd2 = null;
            if($imageUpdated == 'yes'){
                $stmtEditProd2 = $conn->prepare("UPDATE products SET prodimg = ?, mime_type = ? WHERE id = ?");
                $stmtEditProd2->bind_param("ssi", $image, $mime_type, $product_id);
            }

            if ($stmtEditProd->execute() && ($imageUpdated == 'yes'? $stmtEditProd2->execute() : true)) {
                $message = "Product updated successfully!";
                $messageType = "success";
                $conn->commit();
            } else {
                $message = "Error: Could not update product.";
                $messageType = "error";
            }

            $stmtEditProd->close();
            if($imageUpdated == 'yes'){
                $stmtEditProd2->close();
            }
            // Redirect to the same page with a success or error message
            header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . urlencode($messageType));
            exit;
        } else {
            $message = implode("<br>", $errors);
            $messageType = "error";

            // Redirect to the same page with error messages
            header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . urlencode($messageType));
            exit;
        }
    }
    else if ($operation == "addcategory"){
        //----------------------category form-------------------------//
        // Validate category name
        if (!isset($_POST['catname']) || empty(trim($_POST['catname']))) {
            $errors[] = "Category name is required.";
        } else {
            $catname = trim($_POST['catname']);
            if (strlen($catname) > 20) {
                $errors[] = "Category name must not exceed 20 characters.";
            }
        }

        // Validate cat image1
        if (!isset($_FILES['catimage']) || $_FILES['catimage']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Caregory image 1 is required.";
        } else {
            $catimage = file_get_contents($_FILES['catimage']['tmp_name']);
            $catimage_mime_type = mime_content_type($_FILES['catimage']['tmp_name']); // Get the MIME type of the image
        }

        // Validate cat image2
        if (!isset($_FILES['catimage2']) || $_FILES['catimage2']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Caregory image 2 is required.";
        } else {
            $catimage2 = file_get_contents($_FILES['catimage2']['tmp_name']);
            $catimage2_mime_type = mime_content_type($_FILES['catimage2']['tmp_name']); // Get the MIME type of the image
        }

        // If there are no errors, insert the category
        if (empty($errors)) {
            $stmt2 = $conn->prepare("INSERT INTO categories (category, catimg, mime_type, catimg2, mime_type2) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("sbsbs", $catname, $null, $catimage_mime_type, $null, $catimage2_mime_type);

            // Bind the image as long data
            $stmt2->send_long_data(1, $catimage);
            $stmt2->send_long_data(3, $catimage2);

            if ($stmt2->execute()) {
                $message = "Category added successfully!";
                $messageType = "success";
                $conn->commit();
            } else {
                $message = "Error: Could not add category. Please try again later.";
                $messageType = "error";
            }

            $stmt2->close();

            // Redirect to the same page with a success or error message
            header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . urlencode($messageType));
            exit;
        } else {
            $message = implode("<br>", $errors);
            $messageType = "error";

            // Redirect to the same page with error messages
            header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . urlencode($messageType));
            exit;
        }
    }
    else if($operation == 'addproduct'){
        //----------------------product form-------------------------//
        // Validate product name
        if (!isset($_POST['name']) || empty(trim($_POST['name']))) {
            $errors[] = "Product name is required.";
        } else {
            $name = trim($_POST['name']);
            if (strlen($name) > 20) {
                $errors[] = "Product name must not exceed 20 characters.";
            }
        }

        // Validate category
        if (!isset($_POST['category']) || empty(trim($_POST['category']))) {
            $errors[] = "Category is required.";
        } else {
            $category = trim($_POST['category']);
            if (!array_key_exists($category, $categories)) {
                $errors[] = "Invalid category selected.";
            } else {
                $catid = $categories[$category];
            }
        }

        // Validate cost
        if (!isset($_POST['cost']) || empty($_POST['cost']) || !filter_var($_POST['cost'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
            $errors[] = "Cost must be a positive integer.";
        } else {
            $cost = (int)$_POST['cost'];
        }

        // Validate quantity
        if (!isset($_POST['quantity']) || empty($_POST['quantity']) || !filter_var($_POST['quantity'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
            $errors[] = "Quantity must be a positive integer.";
        } else {
            $quantity = (int)$_POST['quantity'];
        }

        // Validate product image
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Product image is required.";
        } else {
            $image = file_get_contents($_FILES['image']['tmp_name']);
            $mime_type = mime_content_type($_FILES['image']['tmp_name']); // Get the MIME type of the image
        }

        // If there are no errors, insert the product
        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO products (name, catid, prodimg, mime_type, cost, quantity) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sibsii", $name, $catid, $null, $mime_type, $cost, $quantity);

            // Bind the image as long data
            $stmt->send_long_data(2, $image);

            if ($stmt->execute()) {
                $message = "Product added successfully!";
                $messageType = "success";
                $conn->commit();
            } else {
                $message = "Error: Could not add product. Please try again later.";
                $messageType = "error";
            }

            $stmt->close();

            // Redirect to the same page with a success or error message
            header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . urlencode($messageType));
            exit;
        } else {
            $message = implode("<br>", $errors);
            $messageType = "error";

            // Redirect to the same page with error messages
            header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . urlencode($messageType));
            exit;
        }
    }
    //}
}

$conn->close();

// Function to display a dialog message
function displayDialog($message, $type, $dialogDisplay) {
    $dialogType = $type === "success" ? "success-dialog" : "error-dialog";
    echo <<<HTML
    <div class="overlay2" style="display: $dialogDisplay">
        <div class="dialog $dialogType" style="display: $dialogDisplay">
            <p>$message</p>
            <button onclick="closeDialog()">Close</button>
        </div>
    </div>
    <script>
        function closeDialog() {
            document.querySelector('.dialog').remove();
            document.querySelector('.overlay2').remove();
        }
    </script>
HTML;
}

function showMessage($msg, $typ) {
    $dialogDisplay = "block";
    displayDialog($msg, $typ, $dialogDisplay);
}

// Show dialog if message is set in the URL
if (isset($_GET['message']) && isset($_GET['type'])) {
    showMessage($_GET['message'], $_GET['type']);
}

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
    <title>Admin</title>
    <link rel="stylesheet" href="shop_products.css">
    <style>
        :root{
            --body-color: #dcdcdc;
            --color-white: #fff;
            --text-color: #fff;
            --text-color-black: rgb(30, 30, 30);
            --card-bg: #f8f9fa;
            --font-family: "Faculty Glyphic", serif;

            --primary-color: #201a56;/*#007d46*/
            --primary-color-light: #2e257c;
            --pc: #8555ff;
            --opacity-color: #4e497c;
            --secondary-color: #eb765a;
            --secondary-color-strong: #ee603c;
            --secondary-color-light: #e08169;
            --secondary-color-double-light: #da8d7a;
            --third-color: #9A98A6;

            --header-color: #201A56;

            --red-color: rgb(209, 0, 0);
            --red-color-opacity: rgba(209, 0, 0, 0.65);

            --dark-gray: #333;
            --light-gray: #5a5a5a;

            --shadow1: rgb(0,0,0,0.1);
            --shadow2: rgb(0,0,0,0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--body-color);
            color: var(--primary-color);
        }

        /* Sidebar */
        #sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 250px;
            z-index: 10;
            background-color: var(--color-white);
            color: var(--text-color);
            padding: 0px;
            display: flex;
            flex-direction: column;
            transform: translateX(0);
            transition: transform 0.3s ease-in-out;
            box-shadow: 5px 5px 10px 5px var(--shadow1);
        }

        .text-center{
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: var(--primary-color);
            width: 100%;
            padding-top: 30px;
            padding-bottom: 40px;
        }

        #sidebar.collapsed {
            transform: translateX(-100%);
        }

        #sidebar img {
            margin-bottom: 10px;
        }

        #sidebar ul {
            list-style: none;
            flex-grow: 1;
            margin: 20px;
        }

        #sidebar ul ul {
            display: none;
        }

        #sidebar ul li {
            margin: 15px 0;
            background-color: var(--body-color);
            border-radius: 5px;
        }

        #profileSidebar {
            display: none;
        }
        #logoutSidebar {
            display: none;
        }

        #sidebar ul li a {
            color: var(--text-color-black);
            text-decoration: none;
            display: block;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        #sidebar ul li a:hover {
            background-color: var(--secondary-color);
            color: var(--color-white);
            box-shadow: 0 4px 8px var(--secondary-color-light);
        }

        /* Hamburger Menu (for small screens) */
        #sidebarToggle {
            display: none;
            background-color: var(--primary-color);
            color: var(--text-color);
            border: none;
            border-radius: 0px;
            padding: 0px;
            font-size: 1.5rem;
            cursor: pointer;
            transition-duration: 0.4s;
        }

        #sidebarToggle:hover{
            transform: scale(1.5);
        }

        /* Main Content */
        .main {
            margin-left: 250px;
            transition: margin-left 0.3s ease-in-out;
        }

        .main.fullscreen {
            margin-left: 0;
        }

        .profile{
            padding: 10px;
            display: flex;
            justify-content: end;
            align-items: center;
            background-color: var(--primary-color-light);
        }
        .profile button{
            background-color: var(--primary-color-light);
            border: none;
            transition-duration: 0.3s;
        }
        .profile button:hover{
            cursor: pointer;
            transform: scale(1.1);
        }

        .main > .header {
            background-color: var(--primary-color);
            color: var(--body-color);
            width: 100%;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .row {
            display: flex;
            margin: 20px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .col {
            flex: 1 1 calc(50% - 20px); /* 2 cards per row by default */
            min-width: 300px;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
        }

        .card h3 {
            margin-bottom: 20px;
            color: var(--primary-color);
        }
        .card .inside-card{
            display: flex;
            justify-content: space-around;
        }
        .card .inside-card div{
            width: 125px;
            height: 125px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: var(--body-color);
            border-radius: 50%;
        }

        .inside-card > div:hover{
            background-color: var(--primary-color);
            color: var(--color-white);
            transform: scale(1.1);
            box-shadow: 0 8px 16px var(--opacity-color);
            cursor: pointer;
        }

        .icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        /* Footer */
        footer {
            margin-top: 20px;
            margin-bottom: 20px;
            text-align: center;
            color: #777;
        }

        /*Products*/
        .main-container {
            display: none;
        }
        .main-container .new-card {
            position: relative;
            width: 250px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: center;
            align-items: center;
            transition: transform 0.3s;
            background-color: var(--secondary-color);
        }
        .main-container .new-card:hover {
            transform: translateY(-10px);
            cursor: pointer;
        }
        .main-container .remove {
            transition-duration: 0.4s;
        }
        .main-container .remove:hover {
            transform: rotate(360deg);
            cursor: pointer;
        }
        .main-container .action-button{
            transition-duration: 0.2s;
        }
        .main-container .action-button:hover{
            transform: scale(1.05);
            box-shadow: 0 4px 8px var(--shadow2);
        }

        /* Categories */
        #cat-sec {
            display: none;
            width: 90%;
            margin: 20px auto;
            background-color: var(--color-white);
            border-radius: 10px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            flex-direction: row;
            justify-content: center;
            align-self: start;
            flex-wrap: wrap;
        }

        #cat-sec > div {
            width: 150px;
            height: 150px;
            margin: 10px;
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: var(--primary-color);
            color: var(--body-color);
            font-size: small;
            position: relative; /* Required for positioning the remove button */
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 8px var(--shadow1);
            overflow: hidden;
        }

        #cat-sec .add-cat{
            background-color: var(--secondary-color);
        }
        #cat-sec .add-cat:hover{
            transform: scale(1.05);
            box-shadow: 0 8px 16px var(--shadow2);
        }

        #cat-sec .cedit{
            padding: 5px;
            border-radius: 25%;
            background-color: var(--opacity-color);
            transition-duration: 0.2s;
        }
        #cat-sec .cedit:hover{
            cursor: pointer;
            background-color: var(--primary-color);
        }

        /* Remove Button */
        .remove-btn {
            position: absolute;
            top: 5px;
            left: 5px;
            width: 25px;
            height: 25px;
            border: none;
            border-radius: 50%;
            background-color: var(--body-color);
            color: var(--primary-color);
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s, transform 0.2s ease-in-out;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .remove-btn:hover {
            background-color: rgba(209, 0, 0, 0.65);
            color: var(--body-color);
            transform: scale(1.1); /* Slightly enlarge the button on hover */
        }

        /*Customer Management*/
        #customer-management,
        #admin-management,
        #order-management {
            display: none;
            width: 90%;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            color: #333;
        }

        #act-management {
            width: 60%;
            margin: 0px;
            margin-right: 10px;
            margin-top: 10px;
            margin-bottom: 10px;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            color: #333;
        }

        /* Header Section */
        #customer-management .header,
        #admin-management .header,
        #act-management .header,
        #order-management .header {
            background-color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        #customer-management .header h2, 
        #admin-management .header h2,
        #act-management .header h2,
        #order-management .header h2 {
            font-size: 24px;
            color: var(--text-color-black);
            margin: 0;
        }

        #admin-management .header .add-emp-btn {
            background-color: var(--primary-color-light);
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        #admin-management .header .add-emp-btn:hover {
            background-color: var(--primary-color);
        }

        #customer-management th button,
        #admin-management th button,
        #act-management th button,
        #order-management th button {
            background: none;
            border: none;
            font-size: 14px;
            cursor: pointer;
            color: #007bff;
            margin-left: 5px;
        }

        #customer-management th button:hover,
        #admin-management th button:hover,
        #act-management th button:hover,
        #order-management th button:hover {
            color: #0056b3;
        }


        /* Table Styling */
        /* Responsive Table Container */
        #customer-management .table-container, 
        #admin-management .table-container,
        #act-management .table-container,
        #order-management .table-container {
            width: 100%;
            overflow-x: auto; /* Enables horizontal scrolling */
            margin: 20px 0;
            border: 1px solid #ddd; /* Optional styling */
            border-radius: 5px;
            position: relative;
        }

        #customer-management .client-table, 
        #admin-management .admin-table,
        #act-management .act-table,
        #order-management .order-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            text-align: left;
        }

        #customer-management .client-table th, #customer-management .client-table td,
        #admin-management .admin-table th, #admin-management .admin-table td,
        #act-management .act-table th, #act-management .act-table td,
        #order-management .order-table th, #order-management .order-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
            text-wrap: nowrap;
            position: relative; /* To contain the sliding block */
        }

        #customer-management .client-table thead,
        #admin-management .admin-table thead,
        #act-management .act-table thead,
        #order-management .order-table thead {
            background-color: #f4f4f4;
        }

        /* Sliding block styles */
        .slide-block,.slide-blockA,.slide-blockB {
        position: absolute;
        top: 0;
        left: -100px; /* Start off-screen */
        height: 100%;
        width: 100px;
        z-index: 1;
        background-color: red;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: left 0.3s ease; /* Smooth sliding effect */
        border-right: 1px solid #ddd;
        }

        /* Active class to show the sliding block */
        .row-active .slide-block, .row-active .slide-blockA {
        left: 0; /* Slide into view */
        }
        .row-active .slide-blockB {
        left: 100px; /* Slide into view */
        }

        .slide-block:hover,.slide-block2:hover,.slide-block3:hover,.slide-blockA:hover,.slide-blockB:hover {
            cursor: pointer;
        }

        /* Responsive Font and Padding */
        @media screen and (max-width: 768px) {
            #customer-management .client-tableth, #customer-management .client-table td,
            #admin-management .admin-table th, #admin-management .admin-table td,
            #act-management .act-table th, #act-management .act-table td,
            #order-management .order-table th, #order-management .order-table td {
                font-size: 12px; /* Smaller text on smaller screens */
                padding: 8px;
            }
        }
        @media screen and (max-width: 1250px) {
            #act-management{
                width: 100%;
            }
        }

        #customer-management .client-table th,
        #admin-management .admin-table th,
        #act-management .act-table th,
        #order-management .order-table th {
            font-weight: bold;
        }

        #customer-management .client-table tbody tr:hover,
        #admin-management .admin-table tbody tr:hover,
        #act-management .act-table tbody tr:hover,
        #order-management .order-table tbody tr:hover {
            background-color: #f1f1f1;
        }

        #customer-management .client-info,
        #admin-management .admin-info,
        #act-management .act-info,
        #order-management .order-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #customer-management .client-info img,
        #admin-management .admin-info img,
        #act-management .act-info img,
        #order-management .order-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        #customer-management .client-info .client-name,
        #admin-management .admin-info .admin-name,
        #act-management .act-info .act-name,
        #order-management .order-info .order-name {
            font-weight: bold;
            margin: 0;
        }


        /* Status Styling */
        #customer-management .status,
        #admin-management .status,
        #act-management .status,
        #order-management .status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            display: inline-block;
            color: #fff;
        }

        #customer-management .status.active,
        #admin-management .status.active,
        #act-management .status.active,
        #order-management .status.active {
            background-color: #28a745;
        }

        #customer-management .status.inactive,
        #admin-management .status.inactive,
        #act-management .status.inactive,
        #order-management .status.inactive {
            background-color: #dc3545;
        }

        /* Pagination Styling */
        #customer-management .pagination1,
        #admin-management .pagination2,
        #act-management .pagination3,
        #order-management .pagination4 {
            display: flex;
            justify-content: space-around;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 20px;
            font-size: 14px;
        }

        #customer-management .pagination1 .entry-info1,
        #admin-management .pagination2 .entry-info2,
        #act-management .pagination3 .entry-info3,
        #order-management .pagination4 .entry-info4 {
            margin: 10px;
        }

        #customer-management .page-controls1,
        #admin-management .page-controls2,
        #act-management .page-controls3,
        #order-management .page-controls4 {
            display: flex;
            margin: 10px;
            align-items: center;
            justify-content: center;
        }

        #customer-management .pagination1 .page-controls1 button,
        #admin-management .pagination2 .page-controls2 button,
        #act-management .pagination3 .page-controls3 button,
        #order-management .pagination4 .page-controls4 button {
            border: 1px solid #ddd;
            background-color: #fff;
            padding: 5px 10px;
            cursor: pointer;
            margin: 0 5px;
            transition: background-color 0.3s;
        }

        #customer-management .pagination1 .page-controls1 button:hover,
        #admin-management .pagination2 .page-controls2 button:hover,
        #act-management .pagination3 .page-controls3 button:hover,
        #order-management .pagination4 .page-controls4 button:hover {
            background-color: #f1f1f1;
        }


        /* Floating Action Button */
        .fab {
            width: 50px;
            height: 50px;
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 20;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .fab2 {
            width: 50px;
            height: 50px;
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 20;
            border-radius: 50%;
            background-color: var(--secondary-color);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .fab button {
            width: 50px;
            height: 50px;
            border: none;
            border-radius: 50%;
            background-color: var(--secondary-color);
            color: white;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            position: absolute;
            bottom: 0px;
            left: 0px;
        }

        .fabs:hover {
            background-color: var(--secondary-color-light);
        }
        .show:hover,.fab2:hover {
            background-color: var(--secondary-color);
            box-shadow: 0 8px 16px var(--secondary-color-light);
            transform: scale(1.05);
        }
        .fabs{
            opacity: 0;
        }
        .fabs:nth-child(1){
            z-index: -1;
            transition: opacity 0.2s ease-in-out;
        }
        .fabs:nth-child(2){
            z-index: -2;
            transition: opacity 0.4s ease-in-out;
        }
        .fabs:nth-child(3){
            z-index: -3;
            transition: opacity 0.6s ease-in-out;
        }
        .fabs:nth-child(4){
            z-index: -4;
            transition: opacity 0.8s ease-in-out;
        }

        /* Tooltip Styling */
        .tooltip {
            visibility: hidden;
            position: absolute;
            top: -10%;
            right: 50%;
            transform: translateX(-25%);
            background-color: #333;
            color: #fff;
            border-radius: 5px;
            padding: 5px 10px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .fab button:hover .tooltip {
            visibility: visible;
            opacity: 1;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            #sidebar {
                transform: translateX(-100%);
            }

            #profileSidebar {
                display: block;
            }
            #logoutSidebar {
                display: block;
            }

            #sidebarToggle {
                display: block;
            }

            .profile {
                display: none;
            }

            #sidebar.collapsed {
                transform: translateX(0);
            }

            .main {
                margin-left: 0;
            }

            .col {
                flex: 1 1 calc(100% - 20px); /* 1 card per row on small screens */
            }
        }

        @media (min-width: 1200px) {
            .col {
                flex: 1 1 calc(50% - 20px); /* 2 cards per row on large screens */
            }
        }

        /*Statistics*/
        #stat-sec{
            display: none;
        }
        #stat-sec .chart-container {
            width: 80%;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        #stat-sec .chart-title {
            font-size: 24px;
            font-weight: bold;
            align-self: center;
            justify-self: center;
            margin: 20px;
            margin-top: 0px;
        }

        #stat-sec .stat-date{
            display: flex;
            justify-content: space-around;
            align-items: center;
            flex-wrap: wrap-reverse;
        }

        #stat-sec .header {
            padding-bottom: 0px;
            margin-bottom: 20px;
            background-color: var(--color-white);
        }

        /* Wrapper to make sure the dropdown behaves correctly */
        .yearSelector-wrapper {
        position: relative;
        display: inline-block;
        }

        #yearSelector {
        appearance: none; /* Hides default arrow */
        background-color: var(--secondary-color); /* Primary blue */
        color: white; /* White text */
        font-size: 16px; /* Font size */
        padding: 10px 12px; /* Padding */
        border: none; /* No border */
        border-radius: 5px; /* Rounded corners */
        cursor: pointer; /* Pointer cursor for dropdown */
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Subtle shadow */
        transition: background-color 0.3s ease, transform 0.2s ease;
        position: relative;
        z-index: 1; /* Stays on top */
        }

        #yearSelector:hover {
        background-color: var(--secondary-color-strong); /* Darker blue on hover */
        }

        #yearSelector:focus {
        outline: none; /* Removes focus outline */
        transform: scale(1.05); /* Slight zoom */
        box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15); /* Emphasized shadow */
        }

        /* Custom dropdown styling */
        #yearSelector-dropdown {
        position: absolute;
        top: calc(100% + 5px); /* Positions dropdown below the button */
        left: 0;
        background-color: #ffffff; /* White background */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Subtle shadow */
        border-radius: 5px; /* Rounded corners */
        overflow: hidden;
        z-index: 999; /* Dropdown stays on top */
        display: none; /* Hidden by default */
        }

        #yearSelector-dropdown.active {
        display: block; /* Show dropdown when active */
        }

        /* Dropdown items */
        #yearSelector-dropdown option {
        padding: 10px 15px; /* Padding for options */
        font-size: 14px; /* Text size */
        color: #333; /* Dark text */
        cursor: pointer;
        background-color: transparent;
        transition: background-color 0.2s ease;
        }

        #yearSelector-dropdown option:hover {
        background-color: #f0f0f0; /* Light gray hover effect */
        }

        #yearSelector-dropdown option:focus {
        background-color: #e8f1ff; /* Light blue for selected */
        }

        #stat-sec .month-navigation {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        #stat-sec .arrow-btn {
            background-color: var(--primary-color-light);
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 18px;
        }

        #stat-sec .arrow-btn:hover {
            background-color: var(--primary-color);
        }

        #stat-sec #monthTitle {
            margin: 0 20px;
            font-size: 20px;
            font-weight: bold;
            }

            #stat-sec #chartArea {
            width: 100%;
            height: 400px;
        }

        #stat-sec .summary {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
            font-size: 16px;
            background-color: var(--color-white);
            padding: 10px;
            border-radius: 5px;
        }

        #stat-sec .summary p {
            font-size: 10pt;
            color: var(--light-gray);
            text-align: center;
        }

        #stat-sec .summ1, #stat-sec .summ2{
            width: 32.5%;
            height: 100px;
            border-radius: 30px;
            padding: 20px;
            background-color: var(--body-color);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        #stat-sec .summ2 h3{
            display: flex;
        }

        #stat-sec .stat-row2 {
            width: 80%;
            display: flex;
            flex-wrap: wrap;
            justify-self: center;
            justify-content: space-around;
            align-items: center;
        }

        /*top customers*/
        #topCustomersContainer {
            overflow: hidden;
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            width: 300px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            font-family: Arial, sans-serif;
            margin: 0px;
            margin-left: 10px;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        #topCustomersContainer h3 {
            text-align: center;
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
        }

            #topCustomersList {
            list-style: none;
            margin: 0;
            padding: 0;
            position: relative;
        }

        #topCustomersList li {
            position: relative;
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
            text-wrap: nowrap;
            flex-wrap: wrap;
        }

        #topCustomersList li:last-child {
            border-bottom: none;
        }

        #topCustomersContainer .rank {
            width: 30px;
            height: 30px;
            background-color: #CD7F32;/*bronze*/
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
            flex-shrink: 0;
        }
        #topCustomersContainer .rank1{
            background-color: goldenrod;
        }
        #topCustomersContainer .rank2{
            background-color: silver;
        }

        #topCustomersContainer .customer-info {
            flex: 1;
            text-align: center; /* Center-align profile and text */
        }

        #topCustomersContainer .profile-pic {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            margin-bottom: 5px;
            object-fit: cover;
        }

        #topCustomersContainer .customer-info .name {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }

        #topCustomersContainer .customer-info .purchases {
            font-size: 12px;
            color: #666;
        }

        /* Sliding Block Styling for top customers*/
        .slide-block2 {
            position: absolute;
            top: 0;
            left: -150px; /* Start hidden off-screen */
            height: 100%;
            width: 100px; /* Fixed width for the sliding block */
            background-color: var(--secondary-color); /* Red background */
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            border-radius: 5px;
            transition: left 0.3s ease; /* Smooth sliding effect */
            z-index: 1;
        }

        /* Active Row Styling */
        .row-active2 .slide-block2 {
            left: 0; /* Slide into view */
        }
        
        /* Sliding Block Styling for orders*/
        .slide-block3 {
            position: absolute;
            top: 0;
            left: -100px; /* Start off-screen */
            height: 100%;
            width: 100px;
            z-index: 1;
            border: none;
            background-color: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: left 0.3s ease; /* Smooth sliding effect */
            border-right: 1px solid #ddd;
        }

        /* Active class to show the sliding block */
        .row-active3 .slide-block3 {
            left: 0; /* Slide into view */
        }

        /* Options Buttons */
        .slide-block3 .options button {
            margin: 5px 0;
            padding: 8px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            width: 100%;
        }

        .slide-block3 .show-btn {
            background-color: #007bff;
            color: white;
        }

        .slide-block3 .accept-btn {
            background-color: #28a745;
            color: white;
        }

        .slide-block3 .reject-btn {
            background-color: #dc3545;
            color: white;
        }

        .slide-block3 .delete-btn {
            background-color: #ffc107;
            color: black;
        }

        .slide-block3 .options button:hover {
            opacity: 0.8;
        }

        /*add product*/
        /* Overlay and modal styling */
        #add-prod .overlay,
        #add-prod2 .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        }

        #add-prod .modal,
        #add-prod2 .modal {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        width: 400px;
        max-width: 90%;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        animation: fadeIn 0.3s ease-in-out;
        }

        #add-prod .modal h2,
        #add-prod2 .modal h2 {
        margin-top: 0;
        margin-bottom: 10px;
        color: #333;
        font-size: 1.5rem;
        }

        #add-prod .modal form,
        #add-prod2 .modal form {
        display: flex;
        flex-direction: column;
        gap: 15px;
        }

        #add-prod .modal input[type="text"],
        #add-prod .modal input[type="number"],
        #add-prod .modal input[type="file"],
        #add-prod .modal button,
        #add-prod2 .modal input[type="text"],
        #add-prod2 .modal input[type="number"],
        #add-prod2 .modal input[type="file"],
        #add-prod2 .modal button {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 1rem;
        }

        #add-prod .modal .cat-sel, #add-prod .modal .submit-prod,
        #add-prod2 .modal .cat-sel, #add-prod2 .modal .submit-prod {
        background-color: var(--secondary-color);
        color: #fff;
        cursor: pointer;
        border: none;
        transition: background-color 0.3s ease-in-out;
        }

        #add-prod .modal .cat-sel:hover, #add-prod .modal .submit-prod:hover,
        #add-prod2 .modal .cat-sel:hover, #add-prod2 .modal .submit-prod:hover {
        background-color: var(--secondary-color-strong);
        }

        #add-prod .category-select,
        #add-prod2 .category-select {
        position: relative;
        }

        #add-prod .category-select button,
        #add-prod2 .category-select button {
        display: block;
        text-align: left;
        background: var(--secondary-color);
        }

        #add-prod .category-options,
        #add-prod2 .category-options {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background: #fff;
        border: 1px solid #ccc;
        max-height: 120px;
        overflow-y: auto;
        z-index: 10;
        border-radius: 5px;
        }

        #add-prod .category-options label,
        #add-prod2 .category-options label {
        display: block;
        padding: 10px;
        cursor: pointer;
        }

        #add-prod .category-options input[type="radio"],
        #add-prod2 .category-options input[type="radio"] {
        margin-right: 10px;
        }

        #add-prod .close-modal,
        #add-prod2 .close-modal {
        background-color: var(--primary-color-light);
        margin-bottom: 10px;
        border: none;
        color: var(--color-white);
        font-size: 1.2rem;
        cursor: pointer;
        }

        #add-prod .close-modal:hover,
        #add-prod2 .close-modal:hover {
        background-color: var(--primary-color);
        }


        @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
        }
        .overlay2 {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        }

        .dialog {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            width: 80%;
            max-width: 400px;
            text-align: center;
        }

        .dialog p {
            margin: 0 0 15px;
        }

        .dialog button {
            padding: 10px 20px;
            background: #d9534f;
            border: none;
            color: #fff;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .dialog button:hover {
            background:rgb(184, 64, 59);
        }

        .success-dialog {
            border-left: 5px solid #5cb85c;
            color: #5cb85c;
        }

        .error-dialog {
            border-left: 5px solid #d9534f;
            color: #d9534f;
        }

        /*Add Category*/
        #add-catg .overlay,
        #add-catg2 .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        }

        #add-catg .modal,
        #add-catg2 .modal {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        width: 400px;
        max-width: 90%;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        animation: fadeIn 0.3s ease-in-out;
        }

        #add-catg .modal h2,
        #add-catg2 .modal h2 {
        margin-top: 0;
        margin-bottom: 10px;
        color: #333;
        font-size: 1.5rem;
        }

        #add-catg .modal form,
        #add-catg2 .modal form {
        display: flex;
        flex-direction: column;
        gap: 15px;
        }

        #add-catg .modal input[type="text"],
        #add-catg .modal input[type="number"],
        #add-catg .modal input[type="file"],
        #add-catg .modal button,
        #add-catg2 .modal input[type="text"],
        #add-catg2 .modal input[type="number"],
        #add-catg2 .modal input[type="file"],
        #add-catg2 .modal button {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 1rem;
        }

        #add-catg .modal .submit-cat,
        #add-catg2 .modal .submit-cat {
        background-color: var(--secondary-color);
        color: #fff;
        cursor: pointer;
        border: none;
        transition: background-color 0.3s ease-in-out;
        }

        #add-catg .modal .submit-cat:hover,
        #add-catg2 .modal .submit-cat:hover {
        background-color: var(--secondary-color-strong);
        }

        #add-catg .close-modal,
        #add-catg2 .close-modal {
        background-color: var(--primary-color-light);
        margin-bottom: 10px;
        border: none;
        color: var(--color-white);
        font-size: 1.2rem;
        cursor: pointer;
        }

        #add-catg .close-modal:hover,
        #add-catg2 .close-modal:hover {
        background-color: var(--primary-color);
        }
        
        .profile .b6{
            background-color: var(--red-color);
            color: var(--body-color);
            padding: 10px;
            margin-left: 10px;
            margin-right: 10px;
            border-radius: 20px;
            border: none;
            font-size: small;
            font-family: "Faculty Glyphic", serif;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 8px 16px var(--shadow1);
        }
        .profile .b6:hover{
            background-color: var(--red-color-opacity);
            transform: scale(1.1);
            box-shadow: 0 8px 16px var(--shadow2);
            animation: signin_vibration 1s normal;
        }
        /* Keyframes for horizontal sliding */
        @keyframes signin_vibration {
            0% {
                transform: rotate(10deg) scale(1.1);
            }
            25% {
                transform: rotate(-10deg) scale(1.1);
            }
            50% {
                transform: rotate(10deg) scale(1.1);
            }
            75% {
                transform: rotate(-10deg) scale(1.1);
            }
            100% {
                transform: rotate(0deg) scale(1.1);
            }
        }
    </style>
    
</head>
<body>
    <!-- Sidebar -->
    <div id="sidebar" style="background-color: var(--color-white);">
        <div class="text-center">
            <img src="images/web_logo2.png" alt="Profile" width="160">
            <h5>Welcome <?=isset($_SESSION['user_id']) ? $_SESSION['firstname'] : ''?>!</h5>
        </div>
        <ul>
            <li id="logoutSidebar"><a href="logout.php">Logout</a></li>
            <li id="profileSidebar"><a href="#">Profile</a></li>
            <li><a href="#" onclick="showStatistics()">Dashboard</a></li>
            <li><a href="#" onclick="showUsers()">Users</a></li>
            <ul id="users">
                <li><a href="#" onclick="showAdmins()">Admins</a></li>
                <li><a href="#" onclick="showCustomers()">Customers</a></li>
            </ul>
            <li><a href="#" onclick="showProductManagment()">Product Managment</a></li>
            <ul id="product-managment">
                <li><a href="#" onclick="showProducts()">Products</a></li>
                <li><a href="#" onclick="showOrders()">Orders</a></li>
            </ul>
            <li><a href="#" onclick="showCategories()">Categories</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="profile">
            <a href="logout.php"><button class="b6">Logout</button></a>
            <button><img src="images/profile-user.png"></button>
        </div>
        <div class="header">
            <h4>Admin Dashboard</h4>
            <!-- Sidebar Toggle (for small screens) -->
            <button id="sidebarToggle">&#9776;</button>
        </div>

        <!-- Main Page -->
        <div class="row" id="row">
            <!-- User Management -->
            <div class="col">
                <div class="card">
                    <h3>User Management</h3>
                    <div class="inside-card">
                        <div id="admins" onmouseover="changeAdminsIcon()" onmouseout="changeAdminsIcon2()" onclick="showAdmins()">
                            <figure>
                                <img src="images/user.png">
                            </figure>
                            <figcaption><p>Admins</p></figcaption>
                        </div>
                        <div id="customers" onmouseover="changeCustomersIcon()" onmouseout="changeCustomersIcon2()" onclick="showCustomers()">
                            <figure>
                                <img src="images/customer.png">
                            </figure>
                            <figcaption><p>Customers</p></figcaption>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Management -->
            <div class="col">
                <div class="card">
                    <h3>Product Management</h3>
                    <div class="inside-card">
                        <div id="products" onmouseover="changeProductsIcon()" onmouseout="changeProductsIcon2()" onclick="showProducts()">
                            <figure>
                                <img src="images/product.png">
                            </figure>
                            <figcaption><p>Products</p></figcaption>
                        </div>
                        <div id="orders" onmouseover="changeOrdersIcon()" onmouseout="changeOrdersIcon2()" onclick="showOrders()">
                            <figure>
                                <img src="images/orders.png">
                            </figure>
                            <figcaption><p>Orders</p></figcaption>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Brand Management -->
            <div class="col">
                <div class="card">
                    <h3>Dashboard</h3>
                    <div class="inside-card">
                        <div id="statistics" onmouseover="changeStatisticsIcon()" onmouseout="changeStatisticsIcon2()" onclick="showStatistics()">
                            <figure>
                                <img src="images/trend.png">
                            </figure>
                            <figcaption><p>Statistics</p></figcaption>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Management -->
            <div class="col">
                <div class="card">
                    <h3>Categories Management</h3>
                    <div class="inside-card">
                        <div id="categories" onmouseover="changeCategoriesIcon()" onmouseout="changeCategoriesIcon2()" onclick="showCategories()">
                            <figure>
                                <img src="images/categories.png">
                            </figure>
                            <figcaption><p>Categories</p></figcaption>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div id="stat-sec">
            <div class="chart-container">
                <div class="chart-title">MostPurchased Items</div>
                <!-- Year Selector -->
                <div class="stat-date">
                    <div class="header">
                        <div class="yearSelector-wrapper">
                            <select id="yearSelector">
                              <option value="2024">2024</option>
                              <option value="2025">2025</option>
                              <option value="2026">2026</option>
                              <option value="2027">2027</option>
                            </select>
                            <div id="yearSelector-dropdown">
                              <option value="2024">2024</option>
                              <option value="2025">2025</option>
                              <option value="2026">2026</option>
                              <option value="2027">2027</option>
                            </div>
                          </div>
                    </div>
                    
                    <!-- Month Navigation -->
                    <div class="month-navigation">
                        <button id="prevMonth" class="arrow-btn">←</button>
                        <h3 id="monthTitle" style="text-align: center;">January 2024</h3>
                        <button id="nextMonth" class="arrow-btn">→</button>
                    </div>
                </div>

                <!-- Chart Area -->
                <div id="chartArea"></div>
              
                <!-- Summary Section -->
                <div class="summary">
                    <div class="summ1">
                        <h3><div id="totalItems">0</div></h3>
                        <p>Total Sold Items</p>
                    </div>
                    <div class="summ2">
                        <h3>$<div id="totalRevenue">0</div></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>
            <div class="stat-row2">
                <!-- Last Activities -->
                <div class="act-management" id="act-management">
                    <div class="header">
                        <h2>Last Activities</h2>
                    </div>
                    <div class="table-container">
                        <table class="act-table">
                            <thead>
                                <tr>
                                    <th>
                                        Timestamp
                                        <button onclick="sortTable(0, 'asc', 'act')" title="asc">↑</button>
                                        <button onclick="sortTable(0, 'desc', 'act')" title="desc">↓</button>
                                    </th>
                                    <th>
                                        Role
                                        <button onclick="sortTable(1, 'asc', 'act')" title="asc">↑</button>
                                        <button onclick="sortTable(1, 'desc', 'act')" title="desc">↓</button>
                                    </th>
                                    <th>
                                        Name
                                        <button onclick="sortTable(2, 'asc', 'act')" title="asc">↑</button>
                                        <button onclick="sortTable(2, 'desc', 'act')" title="desc">↓</button>
                                    </th>
                                    <th>
                                        Type of Activity
                                        <button onclick="sortTable(3, 'asc', 'act')" title="asc">↑</button>
                                        <button onclick="sortTable(3, 'desc', 'act')" title="desc">↓</button>
                                    </th>
                                    <th>
                                        Item/Service
                                        <button onclick="sortTable(4, 'asc', 'act')" title="asc">↑</button>
                                        <button onclick="sortTable(4, 'desc', 'act')" title="desc">↓</button>
                                    </th>
                                    <th>
                                        Amount Spent
                                        <button onclick="sortTable(5, 'asc', 'act')" title="asc">↑</button>
                                        <button onclick="sortTable(5, 'desc', 'admin')" title="desc">↓</button>
                                    </th>
                                    <th>
                                        Status
                                        <button onclick="sortTable(6, 'asc', 'act')" title="asc">↑</button>
                                        <button onclick="sortTable(6, 'desc', 'act')" title="desc">↓</button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="act-table-body">
                                <!-- Repeat rows as necessary -->
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination3">
                        <div id="entry-info3">Showing 1 to 10 of X entries</div>
                        <div class="page-controls3">
                            <button id="prev-page3" disabled>&lt;</button>
                            <span id="page-numbers3"></span>
                            <button id="next-page3">&gt;</button>
                        </div>
                    </div>
                </div> 
                <div id="topCustomersContainer">
                    <h3>Top 5 Customers</h3>
                    <ul id="topCustomersList">
                        <!-- top customers will be added here -->
                    </ul>
                </div>   
            </div>       
        </div>

         <!-- Products -->
        <div class="main-container" id="main-container">
            <!-- Filters -->
            <div class="filter-buttons">
                <button data-category="all" class="all active">SHOW ALL</button>
                <?php foreach ($categories2 as $category3) {
                $catg2 = "cat" . $category3['id'];         
                ?>
                    <button data-category="<?= $catg2 ?>" class="<?= $catg2 ?>"><?= $category3['category'] ?></button>
                <?php
                }
                ?>
            </div>

            <!-- Cards Grid -->
            <div id="card-container">
                <!-- New Card -->
                <div class="new-card" id="new-card">
                    <img src="images/add-post.png">
                </div>
                <?php foreach ($products as $product) {
                $cat = "cat" . $product['catid'];    
                ?>
                    <div class="card <?=$cat?>">
                        <img src="get_image.php?id=<?= $product['id'] ?>&column=prodimg&table=products&mime=mime_type" alt="Product Image"> 
                        <div 
                            class="price-badge remove" 
                            data-id="<?= $product['id'] ?>" 
                            onclick="removeProd(this)" 
                            style="width: 32px;height: 32px;border-radius: 50%;padding: 0px;border-width: 1px;border-color: var(--color-white);background-color: var(--color-white);">
                                <img src="images/remove.png" style="width: 32px;height: 32px;">
                        </div>
                        <div class="card-content">
                            <h4><?=$product['name']?></h4>
                        </div>
                        <button 
                            class="action-button" 
                            data-id="<?= $product['id'] ?>"
                            data-name="<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>"
                            data-category="<?= $product['catid'] ?>"
                            data-cost="<?= $product['cost'] ?>"
                            data-quantity="<?= $product['quantity'] ?>"
                            data-image="get_image.php?id=<?= $product['id'] ?>&column=prodimg&table=products&mime=mime_type"
                            onclick="editProduct(this)" 
                            style="width: 45px;height: 45px;padding: 0px;border-width: 1px;border-color: var(--color-white);background-color: var(--primary-color);box-shadow: 1px 1px 2px 2px var(--shadow1);"><img src="images/edit.png" 
                            style="width: 32px;height: 32px;">
                        </button>
                    </div>
                <?php
                }
                ?>
            </div>

            <!-- Pagination -->
            <div id="pagination">
                <button id="prev-page" disabled>«</button>
                <span id="page-numbers" class="active"></span>
                <button id="next-page">»</button>
            </div>
        </div>

        <!-- Orders Management -->
        <div class="order-management" id="order-management">
            <div class="header">
                <h2>Orders Management</h2>
            </div>
            <div class="table-container">
                <table class="order-table">
                    <thead>
                        <tr>
                            <th>
                                id
                                <button onclick="sortTable(0, 'asc', 'order')" title="asc">↑</button>
                                <button onclick="sortTable(0, 'desc', 'order')" title="desc">↓</button>
                            </th>
                            <th>
                                Name
                                <button onclick="sortTable(1, 'asc', 'order')" title="asc">↑</button>
                                <button onclick="sortTable(1, 'desc', 'order')" title="desc">↓</button>
                            </th>
                            <th>
                                Contact
                                <button onclick="sortTable(2, 'asc', 'order')" title="asc">↑</button>
                                <button onclick="sortTable(2, 'desc', 'order')" title="desc">↓</button>
                            </th>
                            <th>
                                Address
                                <button onclick="sortTable(3, 'asc', 'order')" title="asc">↑</button>
                                <button onclick="sortTable(3, 'desc', 'order')" title="desc">↓</button>
                            </th>
                            <th>
                                Date
                                <button onclick="sortTable(4, 'asc', 'order')" title="asc">↑</button>
                                <button onclick="sortTable(4, 'desc', 'order')" title="desc">↓</button>
                            </th>
                            <th>
                                Cost
                                <button onclick="sortTable(5, 'asc', 'order')" title="asc">↑</button>
                                <button onclick="sortTable(5, 'desc', 'order')" title="desc">↓</button>
                            </th>
                            <th>
                                Status
                                <button onclick="sortTable(6, 'asc', 'order')" title="asc">↑</button>
                                <button onclick="sortTable(6, 'desc', 'order')" title="desc">↓</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="order-table-body">
                        <!-- Repeat rows as necessary -->
                    </tbody>
                </table>
            </div>
            <div class="pagination4">
                <div id="entry-info4">Showing 1 to 10 of X entries</div>
                <div class="page-controls4">
                    <button id="prev-page4" disabled>&lt;</button>
                    <span id="page-numbers4"></span>
                    <button id="next-page4">&gt;</button>
                </div>
            </div>
        </div> 

        <!-- Categories -->
        <div id="cat-sec">
            <div class="add-cat" id="add-cat">
                <img src="images/add-post.png">
            </div>
            <?php foreach ($categories2 as $category2) {
                $catg = preg_replace('/\s+/', '_', $category2['category']);  
                $name_array = explode(" ", $category2['category']);       
            ?>
                    <div class="<?=$catg?>-cat cdiv">
                        <img src="get_image.php?id=<?= $category2['id'] ?>&column=catimg&table=categories&mime=mime_type" alt="<?= $category2['category'] ?>" id="<?= strtolower($catg) ?>Image"> 
                        <h2>
                            <?php
                                $catcount = 0; 
                                foreach ($name_array as $cn) {
                                    $catcount++;
                            ?>
                            <?=$cn?>
                                <?php
                                if ($catcount >= count($name_array)) {
                                ?>
                                <span class="cedit"
                                data-id="<?= $category2['id'] ?>"
                                data-name="<?= htmlspecialchars($category2['category'], ENT_QUOTES) ?>"
                                data-image1="get_image.php?id=<?= $category2['id'] ?>&column=catimg&table=categories&mime=mime_type"
                                data-image2="get_image.php?id=<?= $category2['id'] ?>&column=catimg2&table=categories&mime=mime_type2"
                                onclick="editCategory(this)">
                                    <img src="images/pencil.png">
                                </span>
                                <?php
                                }
                                ?>
                            <br>
                            <?php
                                }
                            ?>
                        </h2>
                        <button 
                        class="remove-btn" 
                        data-id="<?= $category2['id'] ?>" 
                        onclick="removeCat(this)">
                            &#x2716;
                        </button>
                    </div>
            <?php
                }
            ?>
        </div>
        
        <!-- Customer Management -->
        <div class="customer-management" id="customer-management">
            <div class="header">
                <h2>Manage Client</h2>
            </div>
            <div class="table-container">
                <table class="client-table">
                    <thead>
                        <tr>
                            <th>
                                Name
                                <button onclick="sortTable(0, 'asc', 'client')" title="asc">↑</button>
                                <button onclick="sortTable(0, 'desc', 'client')" title="desc">↓</button>
                            </th>
                            <th>
                                User Name
                                <button onclick="sortTable(1, 'asc', 'client')" title="asc">↑</button>
                                <button onclick="sortTable(1, 'desc', 'client')" title="desc">↓</button>
                            </th>
                            <th>
                                Contact
                                <button onclick="sortTable(2, 'asc', 'client')" title="asc">↑</button>
                                <button onclick="sortTable(2, 'desc', 'client')" title="desc">↓</button>
                            </th>
                            <th>
                                Gender
                                <button onclick="sortTable(3, 'asc', 'client')" title="asc">↑</button>
                                <button onclick="sortTable(3, 'desc', 'client')" title="desc">↓</button>
                            </th>
                            <th>
                                Location
                                <button onclick="sortTable(4, 'asc', 'client')" title="asc">↑</button>
                                <button onclick="sortTable(4, 'desc', 'client')" title="desc">↓</button>
                            </th>
                            <th>
                                Status
                                <button onclick="sortTable(5, 'asc', 'client')" title="asc">↑</button>
                                <button onclick="sortTable(5, 'desc', 'client')" title="desc">↓</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="client-table-body">
                        <!-- Repeat rows as necessary -->
                    </tbody>
                </table>
            </div>
            <div class="pagination1">
                <div id="entry-info1">Showing 1 to 10 of X entries</div>
                <div class="page-controls1">
                    <button id="prev-page1" disabled>&lt;</button>
                    <span id="page-numbers1"></span>
                    <button id="next-page1">&gt;</button>
                </div>
            </div>
        </div> 
        
        <!-- Admin Management -->
        <div class="admin-management" id="admin-management">
            <div class="header">
                <h2>Manage Employees</h2>
                <button class="add-emp-btn">Add Employee</button>
            </div>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>
                                ID
                                <button onclick="sortTable(0, 'asc', 'admin')" title="asc">↑</button>
                                <button onclick="sortTable(0, 'desc', 'admin')" title="desc">↓</button>
                            </th>
                            <th>
                                Name
                                <button onclick="sortTable(1, 'asc', 'admin')" title="asc">↑</button>
                                <button onclick="sortTable(1, 'desc', 'admin')" title="desc">↓</button>
                            </th>
                            <th>
                                Department
                                <button onclick="sortTable(2, 'asc', 'admin')" title="asc">↑</button>
                                <button onclick="sortTable(2, 'desc', 'admin')" title="desc">↓</button>
                            </th>
                            <th>
                                Email Address
                                <button onclick="sortTable(3, 'asc', 'admin')" title="asc">↑</button>
                                <button onclick="sortTable(3, 'desc', 'admin')" title="desc">↓</button>
                            </th>
                            <th>
                                Contant Number
                                <button onclick="sortTable(4, 'asc', 'admin')" title="asc">↑</button>
                                <button onclick="sortTable(4, 'desc', 'admin')" title="desc">↓</button>
                            </th>
                            <th>
                                Gender
                                <button onclick="sortTable(5, 'asc', 'admin')" title="asc">↑</button>
                                <button onclick="sortTable(5, 'desc', 'admin')" title="desc">↓</button>
                            </th>
                            <th>
                                Location
                                <button onclick="sortTable(6, 'asc', 'admin')" title="asc">↑</button>
                                <button onclick="sortTable(6, 'desc', 'admin')" title="desc">↓</button>
                            </th>
                            <th>
                                Status
                                <button onclick="sortTable(7, 'asc', 'admin')" title="asc">↑</button>
                                <button onclick="sortTable(7, 'desc', 'admin')" title="desc">↓</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="admin-table-body">
                        <!-- Repeat rows as necessary -->
                    </tbody>
                </table>
            </div>
            <div class="pagination2">
                <div id="entry-info2">Showing 1 to 10 of X entries</div>
                <div class="page-controls2">
                    <button id="prev-page2" disabled>&lt;</button>
                    <span id="page-numbers2"></span>
                    <button id="next-page2">&gt;</button>
                </div>
            </div>
        </div>  

        <!-- Add Product -->
        <div id="add-prod">
            <!-- Modal Structure -->
            <div class="overlay" id="productModal">
              <div class="modal">
                <button class="close-modal" onclick="closeModal()">&times;</button>
                <h2>Add New Product</h2>
                <form id="productForm" method="post" enctype="multipart/form-data" action="admin.php">

                  <input type="hidden" name="operation" value="<?= htmlspecialchars("addproduct", ENT_QUOTES) ?>">

                  <!-- Name Field -->
                  <input type="text" name="name" placeholder="Product Name" maxlength="20" required>
        
                  <!-- Category Selection -->
                  <div class="category-select">
                    <button class="cat-sel" type="button" onclick="toggleCategoryOptions()">Select Category</button>
                        <div class="category-options" id="categoryOptions">
                        <?php 
                            foreach ($categories2 as $category4) { 
                        ?>
                            <label><input type="radio" name="category" value="<?= $category4['category'] ?>"><?= $category4['category'] ?></label>
                        <?php
                            }
                        ?>
                    </div>
                  </div>
        
                  <!-- Cost Field -->
                  <input type="number" name="cost" placeholder="Product Cost" min="1" required>
        
                  <!-- Quantity Field -->
                  <input type="number" name="quantity" placeholder="Product Quantity" min="1" required>
        
                  <!-- Image Field -->
                  <input type="file" name="image" accept="image/*" required>
        
                  <!-- Submit Button -->
                  <button class="submit-prod" type="submit">Add Product</button>
                </form>
              </div>
            </div>
        </div>

        <!-- Edit Product -->
        <div id="add-prod2">
            <!-- Modal Structure -->
            <div class="overlay" id="productModal2">
              <div class="modal">
                <button class="close-modal" onclick="closeModal3()">&times;</button>
                <h2>Edit Product</h2>
                <form id="productForm2" method="post" enctype="multipart/form-data" action="admin.php">

                  <input type="hidden" name="operation" value="<?= htmlspecialchars("editproduct", ENT_QUOTES) ?>">

                  <!-- Name Field -->
                  <input type="text" name="name" placeholder="Product Name" maxlength="20" required>
        
                  <!-- Category Selection -->
                  <div class="category-select">
                    <button class="cat-sel" type="button" onclick="toggleCategoryOptions2()">Select Category</button>
                        <div class="category-options" id="categoryOptions2">
                        <?php 
                            foreach ($categories2 as $category4) { 
                        ?>
                            <label><input type="radio" name="category" value="<?= $category4['category'] ?>"><?= $category4['category'] ?></label>
                        <?php
                            }
                        ?>
                    </div>
                  </div>
        
                  <!-- Cost Field -->
                  <input type="number" name="cost" placeholder="Product Cost" min="1" required>
        
                  <!-- Quantity Field -->
                  <input type="number" name="quantity" placeholder="Product Quantity" min="1" required>

                  <!-- Current Product Image -->
                  <div class="image-preview">
                      <img id="currentProductImage" src="" alt="Current Product Image" style="max-width: 100px; max-height: 100px; object-fit: contain; border-radius: 10px;">
                  </div>
        
                  <!-- Image Field -->
                  <input type="file" id="prodImageInput" name="image" accept="image/*" required>
        
                  <!-- Submit Button -->
                  <button class="submit-prod" onclick="<?php $operation = 'editproduct' ?>" type="submit">Edit Product</button>
                </form>
              </div>
            </div>
        </div>

        <!-- Add Category -->
        <div id="add-catg">
            <!-- Modal Structure -->
            <div class="overlay" id="categoryModal">
              <div class="modal">
                <button class="close-modal" onclick="closeModal2()">&times;</button>
                <h2>Add New Category</h2>
                <form id="categoryForm" method="post" enctype="multipart/form-data" action="admin.php">

                  <input type="hidden" name="operation" value="<?= htmlspecialchars("addcategory", ENT_QUOTES) ?>">

                  <!-- Name Field -->
                  <input type="text" name="catname" placeholder="Category Name" maxlength="20" required>
        
                  <label for="CatImage1">Image 1</label>
                  <!-- CatImage1 Field -->
                  <input type="file" name="catimage" id="CatImage1" accept="image/*" required>

                  <label for="CatImage2">Image 2</label>
                  <!-- CatImage2 Field -->
                  <input type="file" name="catimage2" id="CatImage2" accept="image/*" required>

                  <!-- Submit Button -->
                  <button class="submit-cat" onclick="<?php $operation = 'addcategory' ?>" type="submit">Add Category</button>
                </form>
              </div>
            </div>
        </div>

        <!-- Edit Category -->
        <div id="add-catg2">
            <!-- Modal Structure -->
            <div class="overlay" id="categoryModal2">
              <div class="modal">
                <button class="close-modal" onclick="closeModal4()">&times;</button>
                <h2>Edit Category</h2>
                <form id="categoryForm2" method="post" enctype="multipart/form-data" action="admin.php">

                  <input type="hidden" name="operation" value="<?= htmlspecialchars("editcategory", ENT_QUOTES) ?>">

                  <!-- Name Field -->
                  <input type="text" name="catname" placeholder="Category Name" maxlength="20" required>
        
                  <label for="EditCatImage1">Image 1</label>
                  <!-- Current Cat1 Image -->
                  <div class="image-preview">
                      <img id="currentCatImage1" src="" alt="Current Category Image 1" style="max-width: 100px; max-height: 100px; object-fit: contain; border-radius: 10px;">
                  </div>
                  <!-- CatImage1 Field -->
                  <input type="file" name="catimage" id="EditCatImage1" accept="image/*" required>

                  <label for="EditCatImage2">Image 2</label>
                  <!-- Current Cat2 Image -->
                  <div class="image-preview">
                      <img id="currentCatImage2" src="" alt="Current Category Image 2" style="max-width: 100px; max-height: 100px; object-fit: contain; border-radius: 10px;">
                  </div>
                  <!-- CatImage2 Field -->
                  <input type="file" name="catimage2" id="EditCatImage2" accept="image/*" required>

                  <!-- Submit Button -->
                  <button class="submit-cat" onclick="<?php $operation = 'editcategory' ?>" type="submit">Edit Category</button>
                </form>
              </div>
            </div>
        </div>

        <footer>
            <h4>Made by Omar Jarbou & Karam Zaidan</h4>
        </footer>
    </div>

    <!-- Floating Action Buttons -->
    <div class="fab2" onclick="showMain()">
        <img src="images/home.png">
    </div>
    <div class="fab">
        <button class="fabs">
            <span class="tooltip">Add a note</span>
            ✏️
        </button>
        <button class="fabs">
            <span class="tooltip">Add a photo</span>
            📷
        </button>
        <button class="fabs">
            <span class="tooltip">Set an alarm</span>
            ⏰
        </button>
        <button class="fabs">
            <span class="tooltip">Add a note</span>
            🔑
        </button>
        <button class="show" onclick="showHideFabs()">
            <p style="font-size: x-large;">&plus;</p>
        </button>
    </div>

    <script src="shop_products.js"></script>
    <script>
        // Sidebar toggle functionality for small screens
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            document.querySelector('.main').classList.toggle('fullscreen');
        });

        let fabsOpacity = 0;
        function showHideFabs(){
            let arr = document.getElementsByClassName('fabs');
            let translateY;
            if (fabsOpacity == 0) {
                fabsOpacity = 1;
            }
            else if (fabsOpacity == 1) {
                fabsOpacity = 0;
            }  
            for(let i = 0; i < arr.length; i++){
                    translateY = (i+1) * 55;
                    arr[i].style.opacity = fabsOpacity;
                    arr[i].style.transform = "translateY(-" + translateY + "px)";
                }
        }

        function changeAdminsIcon(){
            let adminsIcon = document.getElementById('admins')
                                    .getElementsByTagName('img')[0]
                                    .setAttribute("src","images/user2.png");
        }
        function changeAdminsIcon2(){
            let adminsIcon = document.getElementById('admins')
                                    .getElementsByTagName('img')[0]
                                    .setAttribute("src","images/user.png");
        }
        function changeCustomersIcon(){
            let adminsIcon = document.getElementById('customers')
                                    .getElementsByTagName('img')[0]
                                    .setAttribute("src","images/customer2.png");
        }
        function changeCustomersIcon2(){
            let adminsIcon = document.getElementById('customers')
                                    .getElementsByTagName('img')[0]
                                    .setAttribute("src","images/customer.png");
        }
        function changeProductsIcon(){
            let adminsIcon = document.getElementById('products')
                                    .getElementsByTagName('img')[0]
                                    .setAttribute("src","images/product2.png");
        }
        function changeProductsIcon2(){
            let adminsIcon = document.getElementById('products')
                                    .getElementsByTagName('img')[0]
                                    .setAttribute("src","images/product.png");
        }
        function changeOrdersIcon(){
            let adminsIcon = document.getElementById('orders')
                                    .getElementsByTagName('img')[0]
                                    .setAttribute("src","images/orders2.png");
        }
        function changeOrdersIcon2(){
            let adminsIcon = document.getElementById('orders')
                                    .getElementsByTagName('img')[0]
                                    .setAttribute("src","images/orders.png");
        }
        function changeStatisticsIcon(){
            let adminsIcon = document.getElementById('statistics')
                                    .getElementsByTagName('img')[0]
                                    .setAttribute("src","images/trend2.png");
        }
        function changeStatisticsIcon2(){
            let adminsIcon = document.getElementById('statistics')
                                    .getElementsByTagName('img')[0]
                                    .setAttribute("src","images/trend.png");
        }
        function changeCategoriesIcon(){
            let adminsIcon = document.getElementById('categories')
                                    .getElementsByTagName('img')[0]
                                    .setAttribute("src","images/categories2.png");
        }
        function changeCategoriesIcon2(){
            let adminsIcon = document.getElementById('categories')
                                    .getElementsByTagName('img')[0]
                                    .setAttribute("src","images/categories.png");
        }

        let usersDisplay = 'none';
        function showUsers(){
            usersDisplay = (usersDisplay=='none')? 'block': 'none';
            document.getElementById('users').style.display = usersDisplay;
        }
        let productManagmentDisplay = 'none';
        function showProductManagment(){
            productManagmentDisplay = (productManagmentDisplay=='none')? 'block': 'none';
            document.getElementById('product-managment').style.display = productManagmentDisplay;
        }

        function showMain(){
            hideCategories();
            hideProducts();
            hideCustomers();
            hideAdmins();
            hideStatistics();
            hideOrders();
            document.getElementById('row').style.display = 'flex';
            document.querySelector('#sidebar > ul > li:nth-child(2)').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(1) > li:first-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(1) > li:last-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(2) > li:first-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(2) > li:last-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > li:last-child').style = 'background-color: var(--body-color);';
        }
        function hideMain(){
            document.getElementById('row').style.display = 'none';
        }

        function showProducts(){
            hideMain();
            hideCategories();
            hideCustomers();
            hideAdmins();
            hideStatistics();
            hideOrders();
            document.getElementById('main-container').style.display = 'block';
            document.querySelector('#sidebar > ul > li:nth-child(2)').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(1) > li:first-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(1) > li:last-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(2) > li:first-child').style = 'background-color: var(--secondary-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(2) > li:last-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > li:last-child').style = 'background-color: var(--body-color);';
        }
        function hideProducts(){
            document.getElementById('main-container').style.display = 'none';
        }

        function showCategories(){
            hideMain();
            hideProducts();
            hideCustomers();
            hideAdmins();
            hideStatistics();
            hideOrders();
            document.getElementById('cat-sec').style.display = 'flex';
            document.querySelector('#sidebar > ul > li:nth-child(2)').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(1) > li:first-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(1) > li:last-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(2) > li:first-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(2) > li:last-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > li:last-child').style = 'background-color: var(--secondary-color);';
        }
        function hideCategories(){
            document.getElementById('cat-sec').style.display = 'none';
        }

        function showCustomers(){
            hideMain();
            hideProducts();
            hideCategories();
            hideAdmins();
            hideStatistics();
            hideOrders();
            document.getElementById('customer-management').style.display = 'block';
            document.querySelector('#sidebar > ul > li:nth-child(2)').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(1) > li:first-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(1) > li:last-child').style = 'background-color: var(--secondary-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(2) > li:first-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(2) > li:last-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > li:last-child').style = 'background-color: var(--body-color);';
        }
        function hideCustomers(){
            document.getElementById('customer-management').style.display = 'none';
        }

        function showAdmins(){
            hideMain();
            hideProducts();
            hideCategories();
            hideCustomers();
            hideStatistics();
            hideOrders();
            document.getElementById('admin-management').style.display = 'block';
            document.querySelector('#sidebar > ul > li:nth-child(2)').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(1) > li:first-child').style = 'background-color: var(--secondary-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(1) > li:last-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(2) > li:first-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(2) > li:last-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > li:last-child').style = 'background-color: var(--body-color);';
        }
        function hideAdmins(){
            document.getElementById('admin-management').style.display = 'none';
        }

        function showStatistics(){
            hideMain();
            hideProducts();
            hideCategories();
            hideCustomers();
            hideAdmins();
            hideOrders();
            document.getElementById('stat-sec').style.display = 'block';
            document.querySelector('#sidebar > ul > li:nth-child(2)').style = 'background-color: var(--secondary-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(1) > li:first-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(1) > li:last-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(2) > li:first-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(2) > li:last-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > li:last-child').style = 'background-color: var(--body-color);';
        }
        function hideStatistics(){
            document.getElementById('stat-sec').style.display = 'none';
        }

        function showOrders(){
            hideMain();
            hideProducts();
            hideCategories();
            hideCustomers();
            hideAdmins();
            hideStatistics();
            document.getElementById('order-management').style.display = 'block';
            document.querySelector('#sidebar > ul > li:nth-child(2)').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(1) > li:first-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(1) > li:last-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(2) > li:first-child').style = 'background-color: var(--body-color);';
            document.querySelector('#sidebar > ul > ul:nth-of-type(2) > li:last-child').style = 'background-color: var(--secondary-color);';
            document.querySelector('#sidebar > ul > li:last-child').style = 'background-color: var(--body-color);';
        }
        function hideOrders(){
            document.getElementById('order-management').style.display = 'none';
        }

        //customer table control
        document.addEventListener('DOMContentLoaded', () => {
            let data = []; // To store admin data fetched from the server
            let activeRowId = null; // Track the currently active row by its ID

            console.log('before');

            // Fetch admin data from the server every 1 second
                const fetchClientData = async () => {
                    try {
                        const response = await fetch('get_clients.php');
                        const result = await response.json();

                        if (result.success) {
                            data = result.data;
                            renderTable(currentPage);
                            attachRowListeners(); // Re-attach row event listeners after re-rendering
                            renderPagination();
                        } else {
                            console.error('Failed to fetch client data:', result.message);
                        }
                    } catch (error) {
                        console.error('Error fetching client data:', error);
                    }
                };
            fetchClientData();
            setInterval(() => {
                fetchClientData();
            }, 60000); // Refresh every 1 minute

            const rowsPerPage = 10;
            const paginationContainer = document.getElementById('page-numbers1');
            const tableBody = document.getElementById('client-table-body');
            const prevPageBtn = document.getElementById('prev-page1');
            const nextPageBtn = document.getElementById('next-page1');
            const entryInfo = document.getElementById('entry-info1');

            let currentPage = 1;
            let currentPageGroup = 1;

            const renderTable = (page) => {
                tableBody.innerHTML = '';
                const startIndex = (page - 1) * rowsPerPage;
                const endIndex = Math.min(startIndex + rowsPerPage, data.length);

                data.slice(startIndex, endIndex).forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>
                            <div class="client-info">
                                <div>
                                    <p class="client-name">${row.name}</p>
                                    <span>${row.username}</span>
                                </div>
                            </div>
                        </td>
                        <td>${row.username}</td>
                        <td>${row.contact}</td>
                        <td>${row.gender}</td>
                        <td>${row.location}</td>
                        <td><span class="status ${row.status.toLowerCase()}">${row.status}</span></td>
                    `;
                    tableBody.appendChild(tr);
                });

                entryInfo.textContent = `Showing ${startIndex + 1} to ${endIndex} of ${data.length} entries`;
            };

            const attachRowListeners = () => {
                const tableRows = document.querySelectorAll("#customer-management .client-table tbody tr");

                tableRows.forEach((row) => {
                    // Create the sliding block container dynamically
                    const slideBlockContainer = document.createElement("div");
                    slideBlockContainer.classList.add("slide-block-container");

                    // Create the "Block" sliding block
                    const blockButton = document.createElement("div");
                    blockButton.classList.add("slide-blockA");
                    blockButton.textContent = "Block";
                    blockButton.style.backgroundColor = "red";

                    // Create the "Hire" sliding block
                    const hireButton = document.createElement("div");
                    hireButton.classList.add("slide-blockB");
                    hireButton.textContent = "Hire";
                    hireButton.style.backgroundColor = "green";

                    // Append buttons to the sliding block container
                    slideBlockContainer.appendChild(blockButton);
                    slideBlockContainer.appendChild(hireButton);

                    // Attach the container to the row
                    row.style.position = "relative"; // Ensure each row is a positioning container
                    row.appendChild(slideBlockContainer);

                    // Handle row click event
                    row.addEventListener("click", () => {
                        // Toggle the 'row-active' class to show/hide the sliding block
                        if (row.classList.contains("row-active")) {
                            row.classList.remove("row-active");
                        } else {
                            // Remove 'row-active' from other rows to close their blocks
                            tableRows.forEach((r) => r.classList.remove("row-active"));
                            row.classList.add("row-active");
                        }
                    });

                    // Block Button Event
                    blockButton.addEventListener("click", (event) => {
                        event.stopPropagation(); // Prevent triggering the row click event
                        const clientId = parseInt(row.getAttribute("data-id"), 10);

                        if (confirm(`Are you sure you want to delete client ID ${clientId}?`)) {
                            fetch("delete_client.php", {
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify({ clientId }),
                            })
                                .then((response) => response.json())
                                .then((result) => {
                                    if (result.success) {
                                        alert(result.message);
                                        row.remove(); // Remove the row from the table
                                    } else {
                                        alert("Error: " + result.message);
                                    }
                                })
                                .catch((error) => console.error("Error:", error));
                        }
                    });

                    // Hire Button Event
                    hireButton.addEventListener("click", (event) => {
                        event.stopPropagation(); // Prevent triggering the row click event
                        const clientId = parseInt(row.getAttribute("data-id"), 10);

                        if (confirm(`Are you sure you want to hire client ID ${clientId} as an admin?`)) {
                            fetch("hire_client.php", {
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify({ clientId }),
                            })
                                .then((response) => response.json())
                                .then((result) => {
                                    if (result.success) {
                                        alert(result.message);
                                        row.remove(); // Remove the row from the table or update its status
                                    } else {
                                        alert("Error: " + result.message);
                                    }
                                })
                                .catch((error) => console.error("Error:", error));
                        }
                    });
                });
            };


            const renderPagination = () => {
                const totalPages = Math.ceil(data.length / rowsPerPage);
                const pageNumbers = Array.from({ length: totalPages }, (_, i) => i + 1);

                // Group pages into chunks of 3 for display
                const pageGroups = [];
                for (let i = 0; i < pageNumbers.length; i += 3) {
                    pageGroups.push(pageNumbers.slice(i, i + 3));
                }

                // Render only the current group
                paginationContainer.innerHTML = '';
                const currentGroupPages = pageGroups[currentPageGroup - 1] || [];
                currentGroupPages.forEach(page => {
                    const button = document.createElement('button');
                    button.textContent = page;
                    button.classList.add('page-btn');
                    if (page === currentPage) button.classList.add('active');
                    button.addEventListener('click', () => {
                        currentPage = page;
                        renderTable(currentPage);
                        renderPagination();
                    });
                    paginationContainer.appendChild(button);
                });

                // Enable/Disable navigation buttons
                prevPageBtn.disabled = currentPageGroup === 1;
                nextPageBtn.disabled = currentPageGroup === pageGroups.length;
            };

            // Navigation controls
            prevPageBtn.addEventListener('click', () => {
                if (currentPageGroup > 1) {
                    currentPageGroup--;
                    currentPage = (currentPageGroup - 1) * 3 + 1; // Move to the first page in the new group
                    renderTable(currentPage);
                    renderPagination();
                }
            });

            nextPageBtn.addEventListener('click', () => {
                const totalPages = Math.ceil(data.length / rowsPerPage);
                if (currentPageGroup < Math.ceil(totalPages / 3)) {
                    currentPageGroup++;
                    currentPage = (currentPageGroup - 1) * 3 + 1; // Move to the first page in the new group
                    renderTable(currentPage);
                    renderPagination();
                }
            });

            // Initialize table and pagination
            renderTable(currentPage);
            attachRowListeners();
            renderPagination();
         });

        //admin table control
        document.addEventListener('DOMContentLoaded', () => {
            let data = []; // To store admin data fetched from the server
            let activeRowId = null; // Track the currently active row by its ID

            console.log('before');

            // Fetch admin data from the server every 1 second
                const fetchAdminData = async () => {
                    try {
                        const response = await fetch('get_admins.php');
                        const result = await response.json();

                        if (result.success) {
                            data = result.data;
                            renderTable(currentPage);
                            attachRowListeners(); // Re-attach row event listeners after re-rendering
                            renderPagination();
                        } else {
                            console.error('Failed to fetch admin data:', result.message);
                        }
                    } catch (error) {
                        console.error('Error fetching admin data:', error);
                    }
                };
            fetchAdminData();    
            setInterval(() => {
                fetchAdminData();
            }, 60000); // Refresh every 1 minute

            const rowsPerPage = 10;
            const paginationContainer = document.getElementById('page-numbers2');
            const tableBody = document.getElementById('admin-table-body');
            const prevPageBtn = document.getElementById('prev-page2');
            const nextPageBtn = document.getElementById('next-page2');
            const entryInfo = document.getElementById('entry-info2');

            let currentPage = 1;
            let currentPageGroup = 1;

            const renderTable = (page) => {
                tableBody.innerHTML = '';
                const startIndex = (page - 1) * rowsPerPage;
                const endIndex = Math.min(startIndex + rowsPerPage, data.length);

                data.slice(startIndex, endIndex).forEach(row => {
                    const tr = document.createElement('tr');
                    tr.setAttribute('data-id', row.id); // Set row ID for tracking
                    tr.innerHTML = `
                        <td>${row.id}</td>
                        <td>${row.name}</td>
                        <td>${row.department}</td>
                        <td>${row.email}</td>
                        <td>${row.contact}</td>
                        <td>${row.gender}</td>
                        <td>${row.location}</td>
                        <td><span class="status ${row.status.toLowerCase()}">${row.status}</span></td>
                    `;

                    // Reapply the active class if this is the active row
                    if (row.id === activeRowId) {
                        tr.classList.add('row-active');
                    }

                    tableBody.appendChild(tr);
                });

                entryInfo.textContent = `Showing ${startIndex + 1} to ${endIndex} of ${data.length} entries`;
            };

            const attachRowListeners = () => {
                const tableRows = document.querySelectorAll("#admin-management .admin-table tbody tr");

                tableRows.forEach((row) => {
                    // Remove existing sliding blocks to prevent duplicates
                    const existingBlock = row.querySelector(".slide-block");
                    if (existingBlock) existingBlock.remove();

                    // Create the sliding block dynamically
                    const slideBlock = document.createElement("div");
                    slideBlock.classList.add("slide-block");
                    slideBlock.textContent = "Fire"; // Text inside the block
                    row.style.position = "relative"; // Ensure each row is a positioning container
                    row.appendChild(slideBlock);

                    // Handle row click event
                    row.addEventListener("click", () => {
                        // Toggle the 'row-active' class to show/hide the sliding block
                        if (row.classList.contains("row-active")) {
                            row.classList.remove("row-active");
                        } else {
                            // Remove 'row-active' from other rows to close their blocks
                            tableRows.forEach((r) => r.classList.remove("row-active"));
                            row.classList.add("row-active");
                        }
                    });

                    // Add click listener to the "Fire" sliding block
                    slideBlock.addEventListener("click", (event) => {
                        event.stopPropagation(); // Prevent triggering the row click event

                        const adminId = parseInt(row.getAttribute('data-id'), 10);
                        if (confirm(`Are you sure you want to convert admin ID ${adminId} to a client?`)) {
                            fetch('fire_admin.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ adminId })
                            })
                                .then(response => response.json())
                                .then(result => {
                                    if (result.success) {
                                        alert(result.message);
                                        // Optionally remove the row or update its status
                                        row.remove();
                                    } else {
                                        alert("Error: " + result.message);
                                    }
                                })
                                .catch(error => console.error("Error:", error));
                        }
                    });
                });
            }

            const renderPagination = () => {
                const totalPages = Math.ceil(data.length / rowsPerPage);
                const pageNumbers = Array.from({ length: totalPages }, (_, i) => i + 1);

                // Group pages into chunks of 3 for display
                const pageGroups = [];
                for (let i = 0; i < pageNumbers.length; i += 3) {
                    pageGroups.push(pageNumbers.slice(i, i + 3));
                }

                // Render only the current group
                paginationContainer.innerHTML = '';
                const currentGroupPages = pageGroups[currentPageGroup - 1] || [];
                currentGroupPages.forEach(page => {
                    const button = document.createElement('button');
                    button.textContent = page;
                    button.classList.add('page-btn');
                    if (page === currentPage) button.classList.add('active');
                    button.addEventListener('click', () => {
                        currentPage = page;
                        renderTable(currentPage);
                        attachRowListeners(); // Re-attach row event listeners after re-rendering
                        renderPagination();
                    });
                    paginationContainer.appendChild(button);
                });

                // Enable/Disable navigation buttons
                prevPageBtn.disabled = currentPageGroup === 1;
                nextPageBtn.disabled = currentPageGroup === pageGroups.length;
            };

            // Navigation controls
            prevPageBtn.addEventListener('click', () => {
                if (currentPageGroup > 1) {
                    currentPageGroup--;
                    currentPage = (currentPageGroup - 1) * 3 + 1; // Move to the first page in the new group
                    renderTable(currentPage);
                    attachRowListeners(); // Re-attach row event listeners after re-rendering
                    renderPagination();
                }
            });

            nextPageBtn.addEventListener('click', () => {
                const totalPages = Math.ceil(data.length / rowsPerPage);
                if (currentPageGroup < Math.ceil(totalPages / 3)) {
                    currentPageGroup++;
                    currentPage = (currentPageGroup - 1) * 3 + 1; // Move to the first page in the new group
                    renderTable(currentPage);
                    attachRowListeners(); // Re-attach row event listeners after re-rendering
                    renderPagination();
                }
            });

            // Initialize table and pagination
            renderTable(currentPage);
            attachRowListeners(); // Attach row event listeners for the first render
            renderPagination();
        });
        function sortTable(columnIndex, order, table) {
            const tableBody = document.getElementById(`${table}-table-body`);
            const rows = Array.from(tableBody.rows); // Convert rows to an array for sorting

            rows.sort((a, b) => {
                const cellA = a.cells[columnIndex].innerText.trim();
                const cellB = b.cells[columnIndex].innerText.trim();

                // Handle numeric sorting
                if (!isNaN(cellA) && !isNaN(cellB)) {
                return order === 'asc' ? cellA - cellB : cellB - cellA;
                }

                // Handle string sorting
                return order === 'asc'
                ? cellA.localeCompare(cellB)
                : cellB.localeCompare(cellA);
            });

            // Append sorted rows back to the table body
            tableBody.innerHTML = '';
            rows.forEach(row => tableBody.appendChild(row));
        }

    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const data = {
            2024: {
            January: { items: ["Item 1", "Item 2", "Item 3","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [120, 95, 80, 54, 53, 32, 24, 12, 11, 4], totalItems: 485, totalRevenue: 4500 },
            February: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 85, 10, 10, 10, 10, 10, 10, 10], totalItems: 345, totalRevenue: 4000 },
            March: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 85, 20, 10, 10, 10, 10, 10, 10], totalItems: 355, totalRevenue: 4050 },
            April: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 85, 30, 10, 10, 10, 10, 10, 10], totalItems: 365, totalRevenue: 4100 },
            May: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 85, 10, 10, 10, 4, 3, 2, 1], totalItems: 315, totalRevenue: 3800 },
            June: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 85, 10, 10, 10, 10, 10, 10, 10], totalItems: 345, totalRevenue: 4000 },
            July: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 85, 10, 10, 10, 10, 10, 10, 10], totalItems: 345, totalRevenue: 4000 },
            Augest: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 85, 10, 10, 10, 10, 10, 10, 10], totalItems: 345, totalRevenue: 4000 },
            Septemper: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 70, 10, 10, 10, 10, 10, 10, 10], totalItems: 330, totalRevenue: 3900 },
            October: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 85, 10, 10, 10, 10, 10, 10, 10], totalItems: 345, totalRevenue: 4000 },
            November: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 85, 10, 10, 10, 10, 10, 10, 10], totalItems: 345, totalRevenue: 4000 },
            December: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 85, 10, 10, 10, 10, 10, 10, 10], totalItems: 345, totalRevenue: 4000 }
            },
            2025: {
                January: { items: ["Item 1", "Item 2", "Item 3","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 95, 80, 54, 53, 32, 24, 12, 11, 4], totalItems: 465, totalRevenue: 4400 },
            February: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 85, 10, 10, 10, 10, 10, 10, 10], totalItems: 345, totalRevenue: 4000 },
            March: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 85, 20, 10, 10, 10, 10, 10, 10], totalItems: 355, totalRevenue: 4050 },
            April: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 85, 30, 10, 10, 10, 10, 10, 10], totalItems: 365, totalRevenue: 4100 },
            May: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 85, 10, 10, 10, 4, 3, 2, 1], totalItems: 315, totalRevenue: 3800 },
            June: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 40, 10, 10, 10, 10, 10, 10, 10], totalItems: 300, totalRevenue: 3600 },
            July: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 40, 10, 10, 10, 10, 10, 10, 10], totalItems: 300, totalRevenue: 3600 },
            Augest: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 40, 10, 10, 10, 10, 10, 10, 10], totalItems: 300, totalRevenue: 3600 },
            Septemper: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 70, 10, 10, 10, 10, 10, 10, 10], totalItems: 330, totalRevenue: 3900 },
            October: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 40, 10, 10, 10, 10, 10, 10, 10], totalItems: 300, totalRevenue: 3600 },
            November: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 40, 10, 10, 10, 10, 10, 10, 10], totalItems: 300, totalRevenue: 3600 },
            December: { items: ["Item 4", "Item 5", "Item 6","Item 4", "Item 5", "Item 6","Item 7", "Item 8", "Item 9","item 10"], purchases: [100, 90, 40, 10, 10, 10, 10, 10, 10, 10], totalItems: 300, totalRevenue: 3600 }
            },
        };
        
        let currentYear = "2024";
        let currentMonthIndex = 0;
        const months = Object.keys(data[currentYear]);
        
        const yearSelector = document.getElementById("yearSelector");
        const monthTitle = document.getElementById("monthTitle");
        const totalItems = document.getElementById("totalItems");
        const totalRevenue = document.getElementById("totalRevenue");
        const chartArea = document.getElementById("chartArea");
        let chartInstance = null;
        
        function updateChart() {
            const month = months[currentMonthIndex];
            const chartData = data[currentYear][month];
        
            // Update month title
            monthTitle.textContent = `${month} ${currentYear}`;
        
            // Update summary
            totalItems.textContent = chartData.totalItems;
            totalRevenue.textContent = chartData.totalRevenue;
        
            // Destroy old chart if it exists
            if (chartInstance) chartInstance.destroy();
        
            // Create new chart
            const ctx = document.createElement("canvas");
            chartArea.innerHTML = ""; // Clear previous chart
            chartArea.appendChild(ctx);
        
            chartInstance = new Chart(ctx, {
            type: "bar",
            data: {
                labels: chartData.items,
                datasets: [
                {
                    label: "Number of Purchases",
                    data: chartData.purchases,
                    backgroundColor: "rgba(75, 192, 192, 0.2)",
                    borderColor: "rgba(75, 192, 192, 1)",
                    borderWidth: 1,
                },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                y: {
                    beginAtZero: true,
                },
                },
            },
            });
        }
        
        document.getElementById("prevMonth").addEventListener("click", () => {
            currentMonthIndex = (currentMonthIndex - 1 + months.length) % months.length;
            updateChart();
        });
        
        document.getElementById("nextMonth").addEventListener("click", () => {
            currentMonthIndex = (currentMonthIndex + 1) % months.length;
            updateChart();
        });
        
        yearSelector.addEventListener("change", (e) => {
            currentYear = e.target.value;
            months.length = 0;
            months.push(...Object.keys(data[currentYear]));
            currentMonthIndex = 0;
            updateChart();
        });
        
        // Initialize chart
        updateChart();

        /*top customers*/
        const topCustomers = [
        {
            name: "John Doe",
            purchases: 150,
            image: "https://randomuser.me/api/portraits/men/1.jpg",
        },
        {
            name: "Jane Smith",
            purchases: 130,
            image: "https://randomuser.me/api/portraits/women/2.jpg",
        },
        {
            name: "Emily Johnson",
            purchases: 120,
            image: "https://randomuser.me/api/portraits/women/3.jpg",
        },
        {
            name: "Michael Brown",
            purchases: 110,
            image: "https://randomuser.me/api/portraits/men/4.jpg",
        },
        {
            name: "Sophia Wilson",
            purchases: 100,
            image: "https://randomuser.me/api/portraits/women/5.jpg",
        },
        ];

        const topCustomersList = document.getElementById("topCustomersList");
        topCustomersList.innerHTML = topCustomers
        .map(
            (customer, index) => `
        <li>
            <div class="rank rank${index+1}">${index + 1}</div>
            <div class="customer-info">
            <img src="${customer.image}" alt="${customer.name}" class="profile-pic">
            <div class="name">${customer.name}</div>
            <div class="purchases">${customer.purchases} purchases</div>
            </div>
            <div class="slide-block2">Show Info.</div>
        </li>
        `
        )
        .join("");

        // Add click event listeners to toggle sliding blocks
        const customerItems = document.querySelectorAll("#topCustomersList li");

        customerItems.forEach((item) => {
        item.addEventListener("click", () => {
            // Toggle the 'row-active' class to show/hide the sliding block
            if (item.classList.contains("row-active2")) {
                        item.classList.remove("row-active2");
                    } else {
                    // Remove 'row-active' from other rows to close their blocks
                    customerItems.forEach((i) => i.classList.remove("row-active2"));
                        item.classList.add("row-active2");
                    }
        });
        });


        //last activities table control
        document.addEventListener('DOMContentLoaded', () => {
            const data = Array(20).fill().map((_, i) => ({
                time: `2024-12-29, ${20-i}:30`,
                role: i % 2 === 0 ? 'Client' : 'Admin',
                name: i % 2 === 0 ? `user${i + 1}` : `emp${i + 1}`,
                type: i % 2 === 0 ? 'Placed an Order' : 'Add item',
                item: i % 2 === 0 ? 'Painting' : 'N/A',
                spent: i % 2 === 0 ? '$400' : 'N/A',
                status: i % 2 === 0 ? 'Pending' : 'Completed',
                status_desc: i % 2 === 0 ? 'InActive' : 'Active'
            }));

            const rowsPerPage = 10;
            const paginationContainer = document.getElementById('page-numbers3');
            const tableBody = document.getElementById('act-table-body');
            const prevPageBtn = document.getElementById('prev-page3');
            const nextPageBtn = document.getElementById('next-page3');
            const entryInfo = document.getElementById('entry-info3');

            let currentPage = 1;
            let currentPageGroup = 1;

            const renderTable = (page) => {
                tableBody.innerHTML = '';
                const startIndex = (page - 1) * rowsPerPage;
                const endIndex = Math.min(startIndex + rowsPerPage, data.length);

                data.slice(startIndex, endIndex).forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${row.time}</td>
                        <td>${row.role}</td>
                        <td>${row.name}</td>
                        <td>${row.type}</td>
                        <td>${row.item}</td>
                        <td>${row.spent}</td>
                        <td><span class="status ${row.status_desc.toLowerCase()}">${row.status}</span></td>
                    `;
                    tableBody.appendChild(tr);
                });

                entryInfo.textContent = `Showing ${startIndex + 1} to ${endIndex} of ${data.length} entries`;
            };

            const renderPagination = () => {
                const totalPages = Math.ceil(data.length / rowsPerPage);
                const pageNumbers = Array.from({ length: totalPages }, (_, i) => i + 1);

                // Group pages into chunks of 3 for display
                const pageGroups = [];
                for (let i = 0; i < pageNumbers.length; i += 3) {
                    pageGroups.push(pageNumbers.slice(i, i + 3));
                }

                // Render only the current group
                paginationContainer.innerHTML = '';
                const currentGroupPages = pageGroups[currentPageGroup - 1] || [];
                currentGroupPages.forEach(page => {
                    const button = document.createElement('button');
                    button.textContent = page;
                    button.classList.add('page-btn');
                    if (page === currentPage) button.classList.add('active');
                    button.addEventListener('click', () => {
                        currentPage = page;
                        renderTable(currentPage);
                        renderPagination();
                    });
                    paginationContainer.appendChild(button);
                });

                // Enable/Disable navigation buttons
                prevPageBtn.disabled = currentPageGroup === 1;
                nextPageBtn.disabled = currentPageGroup === pageGroups.length;
            };

            // Navigation controls
            prevPageBtn.addEventListener('click', () => {
                if (currentPageGroup > 1) {
                    currentPageGroup--;
                    currentPage = (currentPageGroup - 1) * 3 + 1; // Move to the first page in the new group
                    renderTable(currentPage);
                    renderPagination();
                }
            });

            nextPageBtn.addEventListener('click', () => {
                const totalPages = Math.ceil(data.length / rowsPerPage);
                if (currentPageGroup < Math.ceil(totalPages / 3)) {
                    currentPageGroup++;
                    currentPage = (currentPageGroup - 1) * 3 + 1; // Move to the first page in the new group
                    renderTable(currentPage);
                    renderPagination();
                }
            });

            // Initialize table and pagination
            renderTable(currentPage);
            renderPagination();

            // Select all rows in the table
            const tableRows = document.querySelectorAll("#act-management .act-table tbody tr");

            // Add click event listeners to rows
            tableRows.forEach((row) => {
                // Create the sliding block dynamically
                const slideBlock = document.createElement("div");
                slideBlock.classList.add("slide-block");
                slideBlock.textContent = "Info."; // Text inside the block
                row.style.position = "relative"; // Ensure each row is a positioning container
                row.appendChild(slideBlock);

                // Handle row click event
                row.addEventListener("click", () => {
                    // Toggle the 'row-active' class to show/hide the sliding block
                    if (row.classList.contains("row-active")) {
                    row.classList.remove("row-active");
                    } else {
                    // Remove 'row-active' from other rows to close their blocks
                    tableRows.forEach((r) => r.classList.remove("row-active"));
                    row.classList.add("row-active");
                    }
                });
            });
         });
         
         // Orders table control
        document.addEventListener('DOMContentLoaded', () => {
            let data = []; // To store admin data fetched from the server
            let activeRowId = null; // Track the currently active row by its ID

            console.log('before');

            // Fetch admin data from the server every 1 second
                const fetchOrderData = async () => {
                    try {
                        const response = await fetch('get_orders.php');
                        const result = await response.json();

                        if (result.success) {
                            data = result.data;
                            renderTable(currentPage);
                            renderPagination();
                        } else {
                            console.error('Failed to fetch order data:', result.message);
                        }
                    } catch (error) {
                        console.error('Error fetching order data:', error);
                    }
                };
                fetchOrderData();

            const rowsPerPage = 10;
            const paginationContainer = document.getElementById('page-numbers4');
            const tableBody = document.getElementById('order-table-body');
            const prevPageBtn = document.getElementById('prev-page4');
            const nextPageBtn = document.getElementById('next-page4');
            const entryInfo = document.getElementById('entry-info4');

            let currentPage = 1;
            let currentPageGroup = 1;

            // Function to render the table rows
            const renderTable = (page) => {
                tableBody.innerHTML = '';
                const startIndex = (page - 1) * rowsPerPage;
                const endIndex = Math.min(startIndex + rowsPerPage, data.length);

                data.slice(startIndex, endIndex).forEach((row, index) => {
                    const status_descr = row.order_status != "Completed" ? 'InActive' : 'Active';
                    console.log(status_descr);
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${row.order_id}</td>
                        <td>${row.username}</td>
                        <td>${row.contact}</td>
                        <td>${row.order_address}</td>
                        <td>${row.order_date}</td>
                        <td>${row.order_cost}</td>
                        <td><span class="status ${status_descr.toLowerCase()}">${row.order_status}</span></td>
                    `;
                    tableBody.appendChild(tr);

                    // Add sliding block functionality to the row
                    addSlidingBlock(tr, row);
                });

                entryInfo.textContent = `Showing ${startIndex + 1} to ${endIndex} of ${data.length} entries`;
            };

            // Function to add sliding blocks to rows
            const addSlidingBlock = (row, orderData) => {
                row.style.position = "relative"; // Ensure each row is a positioning container

                // Create the sliding block
                const slideBlock = document.createElement("div");
                slideBlock.classList.add("slide-block3");
                slideBlock.innerHTML = `
                    <div class="options">
                        <button class="show-btn">Show Order</button>
                        ${
                            orderData.order_status === "Pending"
                                ? `
                                    <button class="accept-btn">Accept</button>
                                    <button class="reject-btn">Reject</button>
                                `
                                : `
                                    <button class="delete-btn">Delete</button>
                                `
                        }
                    </div>
                `;
                row.appendChild(slideBlock);

                // Handle row click event to toggle sliding block
                row.addEventListener("click", () => {
                    const isActive = row.classList.contains("row-active3");
                    document.querySelectorAll("#order-table-body tr").forEach((r) => r.classList.remove("row-active3"));
                    if (!isActive) row.classList.add("row-active3");
                });

                // Add event listeners for buttons
                slideBlock.querySelectorAll("button").forEach((button) => {
                    button.addEventListener("click", (e) => {
                        e.stopPropagation(); // Prevent the row click event
                        if (button.classList.contains("accept-btn")) {
                            alert(`Order ${orderData.id} Accepted`);
                        } else if (button.classList.contains("reject-btn")) {
                            alert(`Order ${orderData.id} Rejected`);
                        } else if (button.classList.contains("delete-btn")) {
                            alert(`Order ${orderData.id} Deleted`);
                        }
                    });
                });
            };

            // Function to render pagination controls
            const renderPagination = () => {
                const totalPages = Math.ceil(data.length / rowsPerPage);
                const pageNumbers = Array.from({ length: totalPages }, (_, i) => i + 1);

                // Group pages into chunks of 3 for display
                const pageGroups = [];
                for (let i = 0; i < pageNumbers.length; i += 3) {
                    pageGroups.push(pageNumbers.slice(i, i + 3));
                }

                // Render only the current group
                paginationContainer.innerHTML = '';
                const currentGroupPages = pageGroups[currentPageGroup - 1] || [];
                currentGroupPages.forEach((page) => {
                    const button = document.createElement('button');
                    button.textContent = page;
                    button.classList.add('page-btn');
                    if (page === currentPage) button.classList.add('active');
                    button.addEventListener('click', () => {
                        currentPage = page;
                        renderTable(currentPage);
                        renderPagination();
                    });
                    paginationContainer.appendChild(button);
                });

                // Enable/Disable navigation buttons
                prevPageBtn.disabled = currentPageGroup === 1;
                nextPageBtn.disabled = currentPageGroup === pageGroups.length;
            };

            // Event listeners for pagination controls
            prevPageBtn.addEventListener('click', () => {
                if (currentPageGroup > 1) {
                    currentPageGroup--;
                    currentPage = (currentPageGroup - 1) * 3 + 1; // Move to the first page in the new group
                    renderTable(currentPage);
                    renderPagination();
                }
            });

            nextPageBtn.addEventListener('click', () => {
                const totalPages = Math.ceil(data.length / rowsPerPage);
                if (currentPageGroup < Math.ceil(totalPages / 3)) {
                    currentPageGroup++;
                    currentPage = (currentPageGroup - 1) * 3 + 1; // Move to the first page in the new group
                    renderTable(currentPage);
                    renderPagination();
                }
            });

            // Initialize the table and pagination
            renderTable(currentPage);
            renderPagination();
        });

    </script>
    
    <!-- Add product -->
    <script>
        const modal = document.getElementById('productModal');
        const categoryOptions = document.getElementById('categoryOptions');
    
        document.getElementById('new-card').addEventListener('click', () => {
          modal.style.display = 'flex';
        });
    
        function closeModal() {
          modal.style.display = 'none';
        }
    
        function toggleCategoryOptions() {
          categoryOptions.style.display = categoryOptions.style.display === 'block' ? 'none' : 'block';
        }
    
        window.onclick = function (event) {
          if (event.target === modal) {
            closeModal();
          }
        };
    </script>

    <!-- Add Category -->
    <script>
        const modal2 = document.getElementById('categoryModal');
    
        document.getElementById('add-cat').addEventListener('click', () => {
          modal2.style.display = 'flex';
        });
    
        function closeModal2() {
          modal2.style.display = 'none';
        }
    
        window.onclick = function (event) {
          if (event.target === modal2) {
            closeModal2();
          }
        };
    </script>

    <!-- Edit product -->
    <script>
        const categoryOptions2 = document.getElementById('categoryOptions2');
        const categoriesMap = <?= json_encode(array_column($categories2, 'category', 'id')); ?>;
        const modal3 = document.getElementById('productModal2');

        function toggleCategoryOptions2() {
          categoryOptions2.style.display = categoryOptions2.style.display === 'block' ? 'none' : 'block';
        }

        // Edit Product
        function editProduct(button) {
            // Show the modal
            modal3.style.display = 'flex';

            // Get product data from the button's data attributes
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const categoryId = button.getAttribute('data-category'); // This is the category ID
            const cost = button.getAttribute('data-cost');
            const quantity = button.getAttribute('data-quantity');
            const imageUrl = button.getAttribute('data-image'); // Product image URL

            // Get the category name using the category ID
            const categoryName = categoriesMap[categoryId];

            // Populate form fields
            document.querySelector('#productForm2 input[name="name"]').value = name;
            document.querySelector('#productForm2 input[name="cost"]').value = cost;
            document.querySelector('#productForm2 input[name="quantity"]').value = quantity;

            // Pre-select the category
            const categoryOptions = document.querySelectorAll('#productForm2 input[name="category"]');
            categoryOptions.forEach(option => {
                option.checked = option.value === categoryName;
            });

            // Set the current product image
            const imageElement = document.getElementById('currentProductImage');
            imageElement.src = imageUrl;

            // Add a hidden field to track the product ID for editing
            let hiddenField = document.querySelector('#productForm2 input[name="product_id"]');
            if (!hiddenField) {
                hiddenField = document.createElement('input');
                hiddenField.type = 'hidden';
                hiddenField.name = 'product_id';
                document.getElementById('productForm2').appendChild(hiddenField);
            }
            hiddenField.value = id;
        }

        function closeModal3() {
          modal3.style.display = 'none';
        }
    
        window.onclick = function (event) {
          if (event.target === modal3) {
            closeModal3();
          }
        };

        // Handle image input change event
        document.getElementById('prodImageInput').addEventListener('change', function(event) {
            const file = event.target.files[0]; // Get the selected file
            if (file) {
                const preview = document.getElementById('currentProductImage'); // Get the image element
                preview.src = URL.createObjectURL(file); // Set the src to the selected file
                preview.onload = () => URL.revokeObjectURL(preview.src); // Revoke the object URL after the image loads
            }
        });
    </script>

    <!-- Delete Product -->
    <script>
        function removeProd(to_remove_prod) {
            const id = to_remove_prod.getAttribute('data-id');

            if (confirm("Are you sure you want to delete this product?")) {
                fetch('delete_product.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ delete_product_id: id })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Network response was not ok");
                    }
                    return response.text(); // Temporarily parse as text
                })
                .then(text => {
                    try {
                        // Attempt to parse JSON
                        const data = JSON.parse(text);
                        if (data.success) {
                            alert(data.message);
                            location.reload();
                        } else {
                            alert("Error: " + data.message);
                        }
                    } catch (err) {
                        // Handle non-JSON response
                        console.error("Invalid JSON response:", text);
                        alert("An unexpected error occurred.");
                    }
                })
                .catch(error => console.error("Error:", error));
            }
        }

    </script>

    <!-- Edit Category -->
    <script>
        const modal4 = document.getElementById('categoryModal2');

        // Edit Product
        function editCategory(button) {
            // Show the modal
            modal4.style.display = 'flex';

            // Get product data from the button's data attributes
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const imageUrl = button.getAttribute('data-image1'); // Product image URL
            const imageUrl2 = button.getAttribute('data-image2'); // Product image URL

            // Populate form fields
            document.querySelector('#categoryForm2 input[name="catname"]').value = name;

            // Set the current category1 image
            const imageElement = document.getElementById('currentCatImage1');
            imageElement.src = imageUrl;

            // Set the current category1 image
            const imageElement2 = document.getElementById('currentCatImage2');
            imageElement2.src = imageUrl2;

            // Add a hidden field to track the product ID for editing
            let hiddenField = document.querySelector('#categoryForm2 input[name="category_id"]');
            if (!hiddenField) {
                hiddenField = document.createElement('input');
                hiddenField.type = 'hidden';
                hiddenField.name = 'category_id';
                document.getElementById('categoryForm2').appendChild(hiddenField);
            }
            hiddenField.value = id;
        }

        function closeModal4() {
          modal4.style.display = 'none';
        }
    
        window.onclick = function (event) {
          if (event.target === modal3) {
            closeModal4();
          }
        };

        // Handle image1 input change event
        document.getElementById('EditCatImage1').addEventListener('change', function(event) {
            const file = event.target.files[0]; // Get the selected file
            if (file) {
                const preview = document.getElementById('currentCatImage1'); // Get the image element
                preview.src = URL.createObjectURL(file); // Set the src to the selected file
                preview.onload = () => URL.revokeObjectURL(preview.src); // Revoke the object URL after the image loads
            }
        });

        // Handle image2 input change event
        document.getElementById('EditCatImage2').addEventListener('change', function(event) {
            const file = event.target.files[0]; // Get the selected file
            if (file) {
                const preview = document.getElementById('currentCatImage2'); // Get the image element
                preview.src = URL.createObjectURL(file); // Set the src to the selected file
                preview.onload = () => URL.revokeObjectURL(preview.src); // Revoke the object URL after the image loads
            }
        });
    </script>

    <!-- Delete Category -->
    <script>
        function removeCat(to_remove_cat) {
            const id = to_remove_cat.getAttribute('data-id');

            if (confirm("Are you sure you want to delete this category?")) {
                fetch('delete_category.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ delete_category_id: id })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Network response was not ok");
                    }
                    return response.text(); // Temporarily parse as text
                })
                .then(text => {
                    try {
                        // Attempt to parse JSON
                        const data = JSON.parse(text);
                        if (data.success) {
                            alert(data.message);
                            location.reload();
                        } else {
                            alert("Error: " + data.message);
                        }
                    } catch (err) {
                        // Handle non-JSON response
                        console.error("Invalid JSON response:", text);
                        alert("An unexpected error occurred.");
                    }
                })
                .catch(error => console.error("Error:", error));
            }
        }

    </script>

    <!-- Update Activity -->
    <script>
        setInterval(() => {
            fetch('update_activity.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ userId: 1 }) // Pass the logged-in user's ID (loggedInUserId: after you implement login)
            })
            .catch(error => console.error('Error updating activity:', error));
        }, 5000); // Update every 5 seconds
    </script>
</body>
</html>
