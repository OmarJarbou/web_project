<?php
require 'session_start.php';
require 'auth_helper.php';

// Ensure the user is logged in
redirectIfNotLoggedIn();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "qashan_db";

// Create a MySQLi connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch profile data
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, contact, gender, location, birthdate, picture, firstname, lastname, bio, position, social_facebook, social_twitter, social_instagram FROM users WHERE id = ?");
$stmt->bind_param("i", $userId); // Bind the user ID to the query
$stmt->execute(); // Execute the query
$result = $stmt->get_result(); // Get the result
$user = $result->fetch_assoc(); // Fetch the data as an associative array

// Check if the user exists
if (!$user) {
    die("User not found!");
}

// Fetch payment history
$paymentStmt = $conn->prepare("SELECT * FROM orders WHERE userid = ?");
$paymentStmt->bind_param("i", $userId); // Bind the user ID to the query
$paymentStmt->execute(); // Execute the query
$paymentResult = $paymentStmt->get_result(); // Get the result
$payments = $paymentResult->fetch_all(MYSQLI_ASSOC); // Fetch all rows as an associative array

// Close the database connection
$stmt->close();
$paymentStmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile Page</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        /* styles.css */
        :root{
            --body-color: #dcdcdc;
            --color-white: rgb(250, 250, 250);

            --primary-color: #201a56;/*#007d46*/
            --primary-color-light: #403a7abb;
            --opacity-color: #322e5a;
            --secondary-color: #eb765a;
            --secondary-color-strong: #ee603c;
            --secondary-color-light: #e08169;
            --secondary-color-double-light: #da8d7a;
            --third-color: #9A98A6;

            --header-color: #201A56;

            --red-color: rgb(209, 0, 0);
            --red-color-opacity: rgba(209, 0, 0, 0.65);

            --dark-gray: #333;

            --shadow1: rgb(0,0,0,0.1);
            --shadow2: rgb(0,0,0,0.2);
        }

        .parent{
            height:auto;
            width: auto;
            display: grid;
            row-gap: 45px;
            column-gap: 45px;
            grid-template-areas:
            "header"
            "header"
            "mainc"
            "mainc"
            "mainc"
            "footer"
            "footer"
        ;
            /*. means empty area*/
        }

        /* styles.css */
        /* styles.css */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }

        .header .b6{
            background-color: var(--red-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 8px 16px var(--shadow1);
        }
        .header .b6:hover{
            background-color: var(--red-color-opacity);
            transform: scale(1.1);
            box-shadow: 0 8px 16px var(--shadow2);
            animation: signin_vibration 1s normal;
        }

        .mainc{
            padding: 20px;
        }

        .container {
            display: flex;
            flex-wrap: wrap; /* Allows wrapping on smaller screens */
            justify-content: left;
            align-items: center;
            gap: 20px;
            max-width: 1200px;
            height: auto;
            margin: 40px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .image-container {
            flex: 1 1 300px;
            max-width: 300px;
        }

        .image-container img {
            width: 100%;
            height: auto;
            border-radius: 10px;
        }

        .details-container {
            flex: 2 1 600px;
            padding: 20px;
            height: auto;
        }

        .details-container h1 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }

        .price {
            margin: 10px 0;
            font-size: 18px;
            font-weight: bold;
        }

        .original-price {
            text-decoration: line-through;
            color: #aaa;
            margin-right: 10px;
        }

        .discount-price {
            color: #e74c3c;
        }

        .description {
            font-size: 14px;
            color: #555;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .game-info p {
            font-size: 14px;
            margin: 5px 0;
        }

        .game-info a {
            text-decoration: none;
            color: #3498db;
        }

        .add-to-cart {
            display: flex;
            align-items: center;
            margin-top: 20px;
            gap: 10px;
        }

        .add-to-cart input {
            width: 60px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .add-to-cart button {
            padding: 10px 20px;
            background-color: #e74c3c;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .add-to-cart button:hover {
            background-color: #c0392b;
        }

        /* Media Queries for Responsiveness */
        @media (max-width: 976px) {
            .container {
                flex-direction: column; /* Stack containers vertically */
                align-items: center;
            }

            .details-container {
                flex: 2 1 auto;
            }

            .details-container {
                text-align: center; /* Center text for smaller devices */
                padding: 10px;
            }

            .add-to-cart {
                flex-direction: column; /* Stack input and button vertically */
            }

            .add-to-cart input {
                width: 100%;
            }

            .add-to-cart button {
                width: 100%;
            }

        }

        .profile-container {
            width: 350px;
            padding: 20px;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            font-family: Arial, sans-serif;
        }

        /* Profile Header */
        .profile-header {
            text-align: center;
        }

        .profile-header img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .profile-header h2 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }

        /* Profile Details */
        .profile-details {
            margin-top: 15px;
            line-height: 1.6;
        }

        .profile-details .label {
            font-weight: bold;
            color: #555;
        }

        /* Social Icons */
        .social-icons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
        }

        .social-icons a {
            display: inline-block;
            width: 30px;
            height: 30px;
            background-color: #ddd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #fff;
        }

        .social-icons a:hover {
            background-color: #bbb;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .action-buttons button {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
            font-size: 14px;
        }

        .delete-btn {
            background-color: #f44336;
        }

        .delete-btn:hover {
            background-color: #d32f2f;
        }

        .edit-btn {
            background-color: #2196f3;
        }

        .edit-btn:hover {
            background-color: #1976d2;
        }

        .payment-history-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .payment-history-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .payment-history-header h2 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }

        .payment-history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .payment-history-table th, .payment-history-table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #e0e0e0;
        }

        .payment-history-table th {
            background-color: #f9f9f9;
            font-weight: bold;
        }

        .status {
            padding: 5px 10px;
            border-radius: 4px;
            color: #fff;
            font-size: 12px;
            text-align: center;
        }

        .status.inprogress {
            background-color: #2196f3;
        }

        .status.completed {
            background-color: #4caf50;
        }

        .status.pending {
            background-color: #ff9800;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination button {
            padding: 8px 12px;
            margin: 0 5px;
            border: none;
            border-radius: 4px;
            background-color: #2196f3;
            color: #fff;
            cursor: pointer;
        }

        .pagination button:hover {
            background-color: #1976d2;
        }

        .pagination button.disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }



    </style>
</head>
<body>

<div class="parent">
    <div class="header">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php"><div id="profile"><button class="b7"><img src="images/profile-user.png"></button></div></a>
            <?php endif; ?>
            <img src="images/web_logo2.png">
            <div class="header_btns">
                <a href="index.php"><button class="b1">Home</button></a>
                <a href="shop.php"><button class="b2">Our Shop</button></a>
                <a href="contact.php"><button class="b4">Contact Us</button></a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="logout.php"><button class="b6">Logout</button></a>
                <?php else: ?>
                    <a href="login.php"><button class="b5" id="b5">SIGN IN</button></a>
                <?php endif; ?>
                <!-- <button class="b5" id="b5">SIGN IN</button> -->
            </div>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
    </div>
    <nav class="mobile_menu">
            <a href="index.html">Home</a>
            <a href="shop.html">Our Shop</a>
            <a href="contact.php">Contact Us</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php" id="A1">SIGN IN</a>
            <?php endif; ?>
    </nav>

    <div class="mainc">
        <div class="container">
            <!-- Profile Section -->
            <div class="profile-container">
                <div class="profile-header">
                    <img src="<?php echo $user['picture']; ?>" alt="Profile Picture">
                    <h2><?php echo $user['firstname'] . ' ' . $user['lastname']; ?></h2>
                </div>
                <div class="profile-details">
                    <p><span class="label">Email:</span> <?php echo $user['email']; ?></p>
                    <p><span class="label">Phone:</span> <?php echo $user['contact']; ?></p>
                    <p><span class="label">Location:</span> <?php echo $user['location']; ?></p>
                    <p><span class="label">Date of Birth:</span> <?php echo $user['birthdate']; ?></p>
                    <p><span class="label">Position:</span> <?php echo $user['position']; ?></p>
                    <p><span class="label">Bio:</span> <?php echo $user['bio']; ?></p>
                </div>
                <div class="social-icons">
                    <a href="<?php echo $user['social_facebook']; ?>"><img src="images/facebook-icon.png" alt="Facebook"></a>
                    <a href="<?php echo $user['social_twitter']; ?>"><img src="images/twitter-icon.png" alt="Twitter"></a>
                    <a href="<?php echo $user['social_instagram']; ?>"><img src="images/instagram-icon.png" alt="Instagram"></a>
                </div>
                <div class="action-buttons">
                    <button class="delete-btn">Delete</button>
                    <button class="edit-btn">Edit</button>
                </div>
            </div>

            <!-- Payment History Section -->
            <div class="payment-history-container">
                <div class="payment-history-header">
                    <h2>Payment History</h2>
                </div>
                <table class="payment-history-table">
                    <thead>
                    <tr>
                        <th>ORDER ID</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Address</th>
                        <th>Total Cost</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo $payment['id']; ?></td>
                            <td><?php echo $payment['date']; ?></td>
                            <td><span class="status <?php echo strtolower($payment['status']); ?>"><?php echo $payment['status']; ?></span></td>
                            <td><?php echo $payment['address']; ?></td>
                            <td><?php echo $payment['totalcost']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="pagination">
                    <button class="disabled">Previous</button>
                    <button>1</button>
                    <button>2</button>
                    <button>Next</button>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
            <div class="footer-container">
              <!-- About Section -->
              <div class="footer-about">
                <h3>About QASHAN</h3>
                <p>Qashan brings you beautiful handcrafted items from around the world, blending culture and modernity.</p>
              </div>
              
              <!-- Quick Links Section -->
              <div class="footer-links">
                <h3>Quick Links</h3>
                <ul>
                  <li><a href="index.php">Home</a></li>
                  <li><a href="shop.php">Our Shop</a></li>
                  <li><a href="#">Product Details</a></li>
                  <li><a href="#">Contact Us</a></li>
                </ul>
              </div>
              
              <!-- Contact Section -->
              <div class="footer-contact">
                <h3>Contact Us</h3>
                <p>Email: support@qashan.com</p>
                <p>Phone: +123 456 789</p>
                <div class="social-icons">
                  <a href="#"><img src="images/facebook-icon.png" alt="Facebook"></a>
                  <a href="#"><img src="images/twitter-icon.png" alt="Twitter"></a>
                  <a href="#"><img src="images/instagram-icon.png" alt="Instagram"></a>
                </div>
              </div>
            </div>
            <div class="footer-bottom">
              <p>&copy; 2024 Qashan. All Rights Reserved.</p>
            </div>
        </div>

<script>
    // JavaScript for interactivity
    const deleteBtn = document.querySelector('.delete-btn');
    const editBtn = document.querySelector('.edit-btn');

    deleteBtn.addEventListener('click', () => {
        alert('Delete button clicked!');
    });

    editBtn.addEventListener('click', () => {
        alert('Edit button clicked!');
    });
</script>

</body>
</html>