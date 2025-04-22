<?php
require 'session_start.php';
require 'auth_helper.php';


// Database connection
$servername = "localhost";
$username = "root"; 
$password = "";     
$dbname = "qashan_db";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve all products
$products = [];

$stmt = $conn->prepare("SELECT id, name, catid, cost, quantity FROM products");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    $stmt->close();
} else {
    die("Failed to prepare the SQL statement: " . $conn->error);
}

// Retrieve all categories
$categories = [];

$stmt2 = $conn->prepare("SELECT id, category FROM categories");
if ($stmt2) {
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    
    while ($row2 = $result2->fetch_assoc()) {
        $categories[] = $row2;
    }

    $stmt2->close();
} else {
    die("Failed to prepare the SQL statement: " . $conn->error);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!--<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>-->
    <style>
        /*----------------------------------shop----------------------------------------*/
            :root{
            --body-color: #dcdcdc;
            --color-white: rgb(250, 250, 250);

            --primary-color: #201a56;/*#007d46*/
            --primary-color-light: #403a7abb;
            --opacity-color: #322e5a;
            --secondary-color: #eb765a;
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
            "headert"
            "headert"
            "headert"
            "headert"
            "mainc"
            "mainc"
            "mainc"
            "mainc"
            "mainc"
            "footer"
            ;
            /*. means empty area*/
        }

        .header_top{
            height: 500px;
            display: flex;
            flex-direction: column;
            grid-area: headert;

            background-image: url("images/shop_top.png");
            background-size: cover;
            border-bottom-left-radius: 20vw;
            border-bottom-right-radius: 20vw;
        }
        .header{
            height: 90px;
            position: fixed;
            top: 0px;
            left: 0px;
        }
        .shop_top{
            height: 100%;
            grid-area: shoptop;
            

            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .shop_top h1{
            font-weight: bold;
        }
        .shop_top h1,.shop_top h3{
            color: var(--body-color);
        }
        /* Floating Action Button */
        .fabutton {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background-color: var(--secondary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease;
            text-decoration: none;
        }

        .fabutton:hover {
            cursor: pointer;
        }

        .fabutton i {
            font-size: 24px;
        }
    </style>
    <link rel="stylesheet" href="shop_products.css">
</head>
<body>
    <?php
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success" onclick="hideSignupSuccess(this)">' . $_SESSION['success'] . '<br>TAB HERE TO HIDE' . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])) {
            foreach ($_SESSION['errors'] as $error) {
                echo '<div class="alert alert-danger" onclick="hideSignupFail(this)">' . $error . '<br>TAB HERE TO HIDE' . '</div>';
            }
            unset($_SESSION['errors']);
        }
    ?>
    <div class="parent">
        <div class="header_top">
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
            <div class="shop_top">
                <h1>OUR SHOP</h1>
                <h3>Qashan: Q Means Quality</h3>
            </div>
        </div>
        <nav class="mobile_menu">
            <a href="index.php">Home</a>
            <a href="shop.php">Our Shop</a>
            <a href="contact.php">Contact Us</a>
        </nav>
        
        <!-- Main Container -->
        <div class="main-container">
            <!-- Filters -->
            <div class="filter-buttons">
                <button data-category="all" class="all active">SHOW ALL</button>
                <?php foreach ($categories as $category) {
                $cat2 = "cat" . $category['id'];         
                ?>
                    <button data-category="<?= $cat2 ?>" class="<?= $cat2 ?>"><?= $category['category'] ?></button>
                <?php
                }
                ?>
            </div>

            <!-- Cards Grid -->
            <div id="card-container">
                <?php foreach ($products as $product) {
                $cat = "cat" . $product['catid'];         
                ?>
                    <div class="card <?=$cat?>">
                        <form action="details.php" method="POST">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <!-- <input type="hidden" name="user_id" value="<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '' ?>"> -->
                            <button type="submit" style="border: none; background-color: white;">
                                <img src="get_image.php?id=<?= $product['id'] ?>&column=prodimg&table=products&mime=mime_type" alt="Product Image"> 
                            </button>
                        </form>
                        <div class="price-badge">$<?=$product['cost']?></div>
                        <div class="card-content">
                            <h4><?=$product['name']?></h4>
                        </div>
                        <button class="action-button">&minus;</button>
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
                  <li><a href="#">Home</a></li>
                  <li><a href="#">Our Shop</a></li>
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

        <?php
            if(isset($_SESSION['user_id'])) {
        ?>
            <a href="cart.php" class="fabutton">
                <i class="bi bi-cart"></i>
            </a>
        <?php
            }
        ?>

    </div>
    <script src="shop_products.js" type="text/javascript"></script>
</body>
</html>