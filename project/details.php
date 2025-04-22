<?php
require 'session_start.php';
require 'auth_helper.php';

// // Ensure the user is logged in
// redirectIfNotLoggedIn();


require 'db.php'; // Include the database connection

// Check if product ID is set in POST
if (isset($_POST['product_id']) || isset($_SESSION['return_from_cart_for_error'])) {
    if(isset($_POST['product_id'])){
        $product_Id = intval($_POST['product_id']); // Get the product ID from the POST data
    }
    else if(isset($_SESSION['return_from_cart_for_error'])){
        $product_Id = intval($_SESSION['return_from_cart_for_error']);
        unset($_SESSION['return_from_cart_for_error']);
    }

    $errors = [];

    // Fetch product details from the database
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute(['id' => $product_Id]);
    $product = $stmt->fetch();

    if (!$product) {
        // Handle case where product is not found
        $errors[] = 'Product not found';
        $_SESSION['errors'] = $errors;
        header("Location: index.php");
        exit;
    }
} else {
    // Handle case where no product ID is provided
    $errors[] = 'Product ID is required';
    $_SESSION['errors'] = $errors;
    header("Location: index.php");
    exit;
}

// Fetch all categories securely
$categories = [];
$categoryQuery = "SELECT id, category FROM categories";
$result = $pdo->query($categoryQuery);
if ($result) {
    while ($row = $result->fetch()) {
        $categories[$row['id']] = $row['category'];
    }
} else {
    $dialogDisplay = "block";
    displayDialog("Unable to fetch categories. Please try again later.", "error", $dialogDisplay);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"> -->
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
            --red-color-light:rgba(255, 59, 62, 0.5);

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
            justify-content: center;
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
            color: var(--secondary-color);
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
            background-color: var(--secondary-color);
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .add-to-cart button:hover {
            background-color:var(--secondary-color-strong);
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

        #warning {
            color: darkred;
            background-color: var(--red-color-light);
            border-width: 2px;
            border-color: darkred;
            border-radius: 5px;
            padding-left: 10px;
            padding-right: 10px;
            padding-top: 5px;
            padding-bottom: 5px;
            display: none;
        }
        #warning p {
            margin: 0px;
        }

        .alert {
            position: fixed;
            top: 0;
            left: 0;
            border-radius: 5px;
            padding: 20px;
            z-index: 1001;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <?php
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success" onclick="hideSuccess(this)">' . $_SESSION['success'] . '<br>TAB HERE TO HIDE' . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger" onclick="hideFail(this)">' . $_SESSION['error'] . '<br>TAB HERE TO HIDE' . '</div>';
            unset($_SESSION['error']);
        }
    ?>
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
            <div class="container">
                <div class="image-container">
                    <img src="get_image.php?id=<?= $product_Id ?>&column=prodimg&table=products&mime=mime_type" alt="<?= htmlspecialchars($product['name']) ?>">
                </div>
                <div class="details-container">
                    <h1><?= htmlspecialchars($product['name']) ?></h1>
                    <p class="price">
                        <p><strong>Price:</strong> <span class="discount-price">$<?= htmlspecialchars($product['cost']) ?></span>
                    </p>
                    <p class="quantity">
                        <p><strong>Quantity:</strong> <span class="discount-price"><?= htmlspecialchars($product['quantity']) ?></span>
                    </p>
                    <p class="description">
                    Explore our unique collection of handcrafted products, each made by skilled artisans. Enjoy one-of-a-kind designs crafted from quality materials, perfect for enhancing your home or as thoughtful gifts. Elevate your everyday life with Qashan's exquisite items!
                    </p>
                    <div class="game-info">
                        <p><strong>Product ID:</strong> <a href="#"><?= htmlspecialchars($product['id']) ?></p></a>
                        <p><strong>Category:</strong> <a href="shop.php"><?= htmlspecialchars($categories[$product['catid']]) ?></p></a>
                    </div>
                    <div class="add-to-cart">
                        <form action="cart.php" method="post">
                            <input type="hidden" name="product_added_to_cart" value="true">
                            <input type="hidden" name="to_cart_product_id" value="<?= $product['id'] ?>">
                            <input type="number" name="wanted_quantity" value="1" min="1">
                            <button 
                            type="submit"
                            <?php
                                if(!isset($_SESSION['user_id'])) {
                            ?>
                                    onmouseover="document.getElementById('warning').style.display = 'flex';"
                                    onmouseleave="document.getElementById('warning').style.display = 'none';";
                            <?php
                                }
                            ?>
                            >
                                Add to Cart
                            </button>
                        </form>
                        <div id="warning"><p>You Cant Access Cart While You Are Not Logged IN!</p></div>
                    </div>
                </div>
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

    <script>
        const plateImage = document.getElementById('plateImage');
        const planterImage = document.getElementById('planterImage');
        const burnerImage = document.getElementById('burnerImage');
        const othersImage = document.getElementById('othersImage');
        const trendingElement1 = document.getElementById('tr');
        const shoppingImage1 = document.getElementById('shoppingImage');
        const trendingElement2 = document.getElementById('tr2');
        const shoppingImage2 = document.getElementById('shoppingImage2');
        const trendingElement3 = document.getElementById('tr3');
        const shoppingImage3 = document.getElementById('shoppingImage3');
        const trendingElement4 = document.getElementById('tr4');
        const shoppingImage4 = document.getElementById('shoppingImage4');
    
        // Change the `src` on hover
        plateImage.addEventListener('mouseenter', () => {
          plateImage.src = 'plate2.png'; // Replace with your hover image path
        });
    
        // Restore the original `src` when the mouse leaves
        plateImage.addEventListener('mouseleave', () => {
          plateImage.src = 'plate.png'; // Replace with your original image path
        });

        // Change the `src` on hover
        planterImage.addEventListener('mouseenter', () => {
            planterImage.src = 'planter2.png'; // Replace with your hover image path
        });
    
        // Restore the original `src` when the mouse leaves
        planterImage.addEventListener('mouseleave', () => {
            planterImage.src = 'planter.png'; // Replace with your original image path
        });

        // Change the `src` on hover
        paintingImage.addEventListener('mouseenter', () => {
            paintingImage.src = 'painting2.png'; // Replace with your hover image path
        });
    
        // Restore the original `src` when the mouse leaves
        paintingImage.addEventListener('mouseleave', () => {
            paintingImage.src = 'painting.png'; // Replace with your original image path
        });

        // Change the `src` on hover
        burnerImage.addEventListener('mouseenter', () => {
            burnerImage.src = 'incense-burner2.png'; // Replace with your hover image path
        });
    
        // Restore the original `src` when the mouse leaves
        burnerImage.addEventListener('mouseleave', () => {
            burnerImage.src = 'incense-burner.png'; // Replace with your original image path
        });

        // Change the `src` on hover
        othersImage.addEventListener('mouseenter', () => {
            othersImage.src = 'others2.png'; // Replace with your hover image path
        });
    
        // Restore the original `src` when the mouse leaves
        othersImage.addEventListener('mouseleave', () => {
            othersImage.src = 'others.png'; // Replace with your original image path
        });

        // Change the `src` on hover
        trendingElement1.addEventListener('mouseenter', () => {
            shoppingImage1.src = 'shopping-bag2.png'; // Replace with your hover image path
        });
    
        // Restore the original `src` when the mouse leaves
        trendingElement1.addEventListener('mouseleave', () => {
            shoppingImage1.src = 'shopping-bag.png'; // Replace with your original image path
        });

        // Change the `src` on hover
        trendingElement2.addEventListener('mouseenter', () => {
            shoppingImage2.src = 'shopping-bag2.png'; // Replace with your hover image path
        });
        // Restore the original `src` when the mouse leaves
        trendingElement2.addEventListener('mouseleave', () => {
            shoppingImage2.src = 'shopping-bag.png'; // Replace with your original image path
        });

        // Change the `src` on hover
        trendingElement3.addEventListener('mouseenter', () => {
            shoppingImage3.src = 'shopping-bag2.png'; // Replace with your hover image path
        });
    
        // Restore the original `src` when the mouse leaves
        trendingElement3.addEventListener('mouseleave', () => {
            shoppingImage3.src = 'shopping-bag.png'; // Replace with your original image path
        });

        // Change the `src` on hover
        trendingElement4.addEventListener('mouseenter', () => {
            shoppingImage4.src = 'shopping-bag2.png'; // Replace with your hover image path
        });
        // Restore the original `src` when the mouse leaves
        trendingElement4.addEventListener('mouseleave', () => {
            shoppingImage4.src = 'shopping-bag.png'; // Replace with your original image path
        });
    </script>

    <script>
        function hideSuccess(suc) {
            suc.style.display = 'none';
        }
        function hideFail(fail) {
            fail.style.display = 'none';
        }
    </script>
</body>
</html>