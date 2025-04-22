<?php
require 'session_start.php';
require 'auth_helper.php';

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
$userId = 1; // Replace '1' with the actual user ID (e.g., from a session or URL parameter)
$stmt = $conn->prepare("SELECT username, email, contact, gender, location, birthdate, picture, firstname, lastname, bio, position, social_facebook, social_twitter, social_instagram FROM users WHERE id = ?");
$stmt->bind_param("i", $userId); // Bind the user ID to the query
$stmt->execute(); // Execute the query
$result = $stmt->get_result(); // Get the result
$user = $result->fetch_assoc(); // Fetch the data as an associative array

// Check if the user exists
if (!$user) {
    die("User not found!");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile Page</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
            href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap"
            rel="stylesheet"
    />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"/>
    <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
            rel="stylesheet"
            integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
            crossorigin="anonymous"
    />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="contact.css" />
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
            "header"
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
        <img src="images/web_logo2.png">
        <div class="header_btns">
          <a href="index.php"><button class="b1">Home</button></a>
          <a href="shop.php"><button class="b2">Our Shop</button></a>
          <a href="contact.php"><button class="b4">Contact Us</button></a>
        </div>
        <div class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
    <nav class="mobile_menu">
      <a href="index.php">Home</a>
      <a href="shop.php">Our Shop</a>
      <a href="contact.php">Contact Us</a>
    </nav>

    <div class="mainc">

                <h1 class="brand"><span style="color: #C3C9DD; font-size: larger; background-color: #485F73; border-radius: 20px;">Contact Us</span></h1>

                <div class="wrapper">
                    <div class="company-info">
                        <h3>Qashan Ceramice</h3>

                        <ul>
                            <li><i class="fa fa-road"></i>Tulkarem - Nablus Street</li>
                            <li><i class="fa fa-phone"></i> 0598520282</li>
                            <li><i class="fa fa-envelope"></i>kamalsaff@yahoo.com</li>
                        </ul>

                        <br>
                        <br>

                        Social Media Accounts :
                        <a href="https://www.instagram.com/qashan_ceramics/"><i class="fa-brands fa-instagram" ></i></a>

                        <a href="https://www.facebook.com/share/1DN5iUaeWC/"><i class="fa-brands fa-facebook"></i></a>
                    </div>

                    <div class="contact">
                        <h3>Email Us</h3>

                        <form id="contact-form">
                            <p>
                                <label>Name</label>
                                <input type="text" name="name" id="name" required />
                            </p>

                            <p>
                                <label>E-mail Address</label>
                                <input type="email" name="email" id="email" required />
                            </p>

                            <p>
                                <label>Phone Number</label>
                                <input type="text" name="phone" id="phone" />
                            </p>

                            <p class="full">
                                <label>Message</label>
                                <textarea name="message" rows="5" id="message"></textarea>
                            </p>

                            <p class="full">
                                <button type="submit">Submit</button>
                            </p>
                        </form>
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
</body>
</html>