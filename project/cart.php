<?php
require 'session_start.php';
require 'auth_helper.php';

// Ensure the user is logged in
redirectIfNotLoggedIn();


require 'db.php'; // Include the database connection
$errors = [];

try {
        // Query to get all products for the uncompleted order
        $stmt = $pdo->prepare("
        SELECT 
            p.id AS id, 
            p.name AS name, 
            p.catid AS catid,
            p.prodimg AS prodimg, 
            p.mime_type AS mime_type, 
            p.cost AS cost, 
            p.quantity AS stock_quantity, 
            uod.prodid, 
            uod.prodcount AS wanted_quantity
        FROM 
            uncompleted_order_details uod
        INNER JOIN 
            products p 
        ON 
            uod.prodid = p.id
        WHERE 
            uod.orderid = :orderid
    ");
    $stmt->execute(['orderid' => $_SESSION['uc_order_id']]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = 'Error while fetching products.';
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
    $errors[] = 'Unable to fetch categories. Please try again later.';
    $_SESSION['errors'] = $errors;
    header("Location: index.php");
    exit;
}

//check if there is product comming
if (isset($_POST['product_added_to_cart'])) {
    // Check if product ID & quantity are set in POST
    if (isset($_POST['to_cart_product_id']) && isset($_POST['wanted_quantity'])) {
        $product_Id = intval($_POST['to_cart_product_id']); // Get the product ID from the POST data
        $wanted_quantity = intval($_POST['wanted_quantity']);

        foreach ($products as $prd){
            if ($prd['id'] == $product_Id) {
                $_SESSION['return_from_cart_for_error'] = $product_Id;
                $_SESSION['error'] = 'The product "' . $prd['name'] . '" is already added to cart, you can edit the quantity there.';
                header("Location: details.php");
                exit;
            }
        }

        // Fetch product details from the database
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute(['id' => $product_Id]);
        $product = $stmt->fetch();

        if (!$product) {
            // Handle case where product is not found
            $_SESSION['return_from_cart_for_error'] = $product_Id;
            $_SESSION['error'] = 'Product not found!';
            header("Location: details.php");
            exit;
        }
        if($wanted_quantity < 1){
            $_SESSION['return_from_cart_for_error'] = $product_Id;
            $_SESSION['error'] = 'Wanted quantity should be 1 or more.';
            header("Location: details.php");
            exit;
        }
        else if($wanted_quantity > $product['quantity']){
            $_SESSION['return_from_cart_for_error'] = $product_Id;
            $_SESSION['error'] = 'There is only ' . $product['quantity'] . ' of this product!';
            header("Location: details.php");
            exit;
        }

        //add product to uncompleted_orders
        $stmt2 = $pdo->prepare("INSERT INTO uncompleted_order_details (orderid, prodid, prodcount, totalcount) VALUES (:order_id, :product_id, :count, :quantity)");
        $stmt2->execute(['order_id' => $_SESSION['uc_order_id'], 'product_id' => $product_Id, 'count' => $wanted_quantity, 'quantity' => $product['quantity']]);

        //update totalcost in uncompleted_orders:
        $stmt3 = $pdo->prepare("UPDATE uncompleted_orders SET totalcost= (totalcost + ". (intval($product['cost'])*$wanted_quantity) .") WHERE id = ". $_SESSION['uc_order_id'] .";");
        $stmt3->execute();
    } 
    else {
        $_SESSION['error'] = 'Product ID or Quantity should be provided.';
        header("Location: details.php");
        exit;
    }

try {
    // Query to get all products for the uncompleted order again because a product might be added
    $stmt = $pdo->prepare("
        SELECT 
            p.id AS id, 
            p.name AS name, 
            p.catid AS catid,
            p.prodimg AS prodimg, 
            p.mime_type AS mime_type, 
            p.cost AS cost, 
            p.quantity AS stock_quantity, 
            uod.prodid, 
            uod.prodcount AS wanted_quantity
        FROM 
            uncompleted_order_details uod
        INNER JOIN 
            products p 
        ON 
            uod.prodid = p.id
        WHERE 
            uod.orderid = :orderid
    ");
    $stmt->execute(['orderid' => $_SESSION['uc_order_id']]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = 'Error while fetching products.';
    $_SESSION['errors'] = $errors;
    header("Location: index.php");
    exit;
}
}

// Handle form submission for placing an order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // Validate address fields
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $postcode = trim($_POST['postcode']);
    $country = trim($_POST['country']);
    $state = trim($_POST['state']);
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $totcost = intval(trim($_POST['total_cost']));

    if (empty($fname) || empty($lname)) {
        $errors[] = 'Please fill in your name.';
    }
    if (empty($address) || empty($city) || empty($postcode) || empty($country)) {
        $errors[] = 'Please fill in all address fields.';
    }

    if (empty($errors)) {
        try {
            // Begin transaction
            $pdo->beginTransaction();

            // Insert into orders table
            $stmt = $pdo->prepare("
                INSERT INTO orders (userid, date, totalcost, status, address, city, postal, state, country)
                VALUES (:userid, NOW(), :totalcost, 'Pending', :address, :city, :postal, :state, :country)
            ");
            $stmt->execute([
                'userid' => $_SESSION['user_id'],
                'totalcost' => $totcost,
                'address' => $address,
                'city' => $city,
                'postal' => $postcode,
                'state' => $state,
                'country' => $country
            ]);
            $orderId = $pdo->lastInsertId();

            // Insert into order_details table
            foreach ($products as $product) {
                $stmt = $pdo->prepare("
                    INSERT INTO order_details (orderid, prodid, prodcount)
                    VALUES (:orderid, :prodid, :prodcount)
                ");
                $stmt->execute([
                    'orderid' => $orderId,
                    'prodid' => $product['id'],
                    'prodcount' => $product['wanted_quantity']
                ]);

                // Update product quantity
                $stmt = $pdo->prepare("
                    UPDATE products SET quantity = quantity - :prodcount WHERE id = :prodid
                ");
                $stmt->execute([
                    'prodcount' => $product['wanted_quantity'],
                    'prodid' => $product['id']
                ]);
            }

            // Delete from uncompleted_orders and uncompleted_order_details
            $stmt = $pdo->prepare("DELETE FROM uncompleted_order_details WHERE orderid = :orderid");
            $stmt->execute(['orderid' => $_SESSION['uc_order_id']]);

            $stmt = $pdo->prepare("DELETE FROM uncompleted_orders WHERE id = :orderid");
            $stmt->execute(['orderid' => $_SESSION['uc_order_id']]);

            // Fetch the maximum id from uncompleted_orders table
            $maxIdStmt = $pdo->query("SELECT MAX(id) AS max_id FROM uncompleted_orders");
            $maxId = $maxIdStmt->fetch(PDO::FETCH_ASSOC)['max_id'];

            // Reset AUTO_INCREMENT to max_id + 1
            if ($maxId !== null) {
                $resetIdSql = "ALTER TABLE uncompleted_orders AUTO_INCREMENT = " . ($maxId + 1);
                $pdo->exec($resetIdSql); // Directly execute the query without prepared statements
            }

            //add a new uncompleted order that keep up with user until he log out or end his session
            $stmt2 = $pdo->prepare('INSERT INTO uncompleted_orders (userid, totalcost) VALUES (' . $_SESSION['user_id'] . ', 0)');
            $stmt2->execute();
            $_SESSION['uc_order_id'] = $pdo->lastInsertId();

            // Commit transaction
            $pdo->commit();

            // Set success message and redirect
            $_SESSION['success'] = 'Order placed successfully!';
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $errors[] = 'Error placing order: ' . $e->getMessage();
            $_SESSION['errors'] = $errors;
        }
    } else {
        $_SESSION['errors'] = $errors;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Shopping Cart</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
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
            "container"
            "container"
            "footer"
            "footer"
            ;
            /*. means empty area*/
        }
        .container {
            grid-area: container;
            max-width: 950px;
            margin: 40px auto;
            background: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333333;
            font-weight: 700;
        }
        .summary-item {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 1px solid #eaeaea;
            padding-bottom: 15px;
        }
        .summary-item img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            margin-right: 20px;
            border-radius: 5px;
        }
        .summary-item h6 {
            font-size: 16px;
            font-weight: 600;
            color: #555555;
        }
        .summary-item p {
            margin: 5px 0;
            color: #777777;
        }
        .summary-item button {
            font-size: 14px;
        }
        .btn-primary.active {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary.active:hover {
            background-color: royalblue;
            border-color: royalblue;
        }
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-outline-primary:hover {
            background-color: royalblue;
            border-color: royalblue;
        }
        .order-summary {
            border-top: 1px solid #eaeaea;
            padding-top: 15px;
        }
        .order-summary p {
            font-size: 14px;
            color: #555555;
        }
        .order-summary h5 {
            font-size: 18px;
            font-weight: 700;
            color: #333333;
        }
        label {
            font-size: 14px;
            font-weight: 600;
            color: #555555;
        }
        input.form-control {
            border: 1px solid #cccccc;
            border-radius: 5px;
            font-size: 14px;
        }
        input.form-control:focus {
            border-color: var(--primary-color-light);
            box-shadow: 0 0 5px var(--primary-color);
        }
        button.btn-success {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            font-weight: 600;
        }
        button.btn-success:hover {
            background-color: var(--secondary-color-strong);
            border-color: var(--secondary-color-strong);
        }
        .prods_col{
            max-height: 350px;
            overflow: auto;
        }
        .alert {
            position: fixed;
            top: 0;
            left: 0;
            border-radius: 5px;
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
        if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])) {
            foreach ($_SESSION['errors'] as $error) {
                echo '<div class="alert alert-danger" onclick="hideFail(this)">' . $error . '<br>TAB HERE TO HIDE' . '</div>';
            }
            unset($_SESSION['errors']);
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
        <div class="container">
            <h1 class="text-center mb-4">My Shopping Cart</h1>
            <div class="row">
                <!-- Cart Summary -->
                <div class="col-md-6">
                    <h4 class="mb-3">Summary</h4>
                    <div class="prods_col">
                        <?php
                            foreach ($products as $prod) {
                        ?>
                                <div class="summary-item" data-product-id="<?= $prod['id'] ?>">
                                    <img src="get_image.php?id=<?= $prod['id'] ?>&column=prodimg&table=products&mime=mime_type" alt="<?= $prod['name'] ?>">
                                    <div>
                                        <h6><?= $prod['name'] ?></h6>
                                        <p>Category: <?= $categories[$prod['catid']] ?> - $<?= intval($prod['cost']*$prod['wanted_quantity']) ?></p>
                                        <button class="btn btn-sm btn-outline-secondary btn-decrease" <?= $prod['wanted_quantity'] <= 1 ? 'disabled' : '' ?>>-</button>
                                        <span class="mx-2 quantity-display"><?= $prod['wanted_quantity'] ?></span>
                                        <button class="btn btn-sm btn-outline-secondary btn-increase" <?= $prod['wanted_quantity'] >= $prod['stock_quantity'] ? 'disabled' : '' ?>>+</button>
                                    </div>
                                </div>
                        <?php
                            }
                        ?>
                        <?php
                            $total = 0;
                            foreach ($products as $prod) {
                                $total += $prod['cost'] * $prod['wanted_quantity'];
                            }
                        ?>
                    </div>
                    <div class="order-summary">
                        <p>Subtotal: <span class="float-end">$<?= $total ?></span></p>
                        <p>Delivery: <span class="float-end">Defined Later</span></p>
                        <h5>Total: <span class="float-end">$<?= $total ?></span></h5>
                    </div>
                </div>
                <!-- Address Form -->
                <div class="col-md-6">
                    <h4 class="mb-3">How Would You Like to Receive Your Order</h4>
                    <div class="mb-4">
                        <button class="btn btn-outline-primary">Delivery</button>
                        <button class="btn btn-primary active">Cash On Delivery</button>
                    </div>
                    <h4 class="mb-3">Enter Your Name and Address</h4>
                    <form method="POST" action="cart.php">
                        <input type="hidden" name="total_cost" value="<?= $total ?>">
                        <div class="row mb-3">
                            <div class="col">
                                <label for="firstName" class="form-label">First Name</label>
                                <input type="text" name="fname" class="form-control" id="firstName" placeholder="John" value="<?= $_SESSION['firstname'] ?>" readonly>
                            </div>
                            <div class="col">
                                <label for="lastName" class="form-label">Last Name</label>
                                <input type="text" name="lname" class="form-control" id="lastName" placeholder="Smith" value="<?= $_SESSION['lastname'] ?>" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" id="address" placeholder="Askar Camp, Near Al-Muhajereen Mosque">
                        </div>
                        <div class="row mb-3">
                            <div class="col">
                                <label for="city" class="form-label">City</label>
                                <input type="text" name="city" class="form-control" id="city" placeholder="Nablus">
                            </div>
                            <div class="col">
                                <label for="postcode" class="form-label">Post Code</label>
                                <input type="text" name="postcode" class="form-control" id="postcode" placeholder="4270458">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col">
                                <label for="state" class="form-label">State</label>
                                <input type="text" name="state" class="form-control" id="state" placeholder="You Can Ignore It">
                            </div>
                            <div class="col">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" name="country" class="form-control" id="country" placeholder="Palestine">
                            </div>
                        </div>
                        <button type="submit" name="place_order" class="btn btn-success w-100">Place Order</button>
                    </form>
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

        
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const decreaseButtons = document.querySelectorAll('.btn-decrease');
            const increaseButtons = document.querySelectorAll('.btn-increase');

            decreaseButtons.forEach(button => {
                button.addEventListener('click', () => updateQuantity(button, 'decrease'));
            });

            increaseButtons.forEach(button => {
                button.addEventListener('click', () => updateQuantity(button, 'increase'));
            });

            function updateQuantity(button, action) {
                const item = button.closest('.summary-item');
                const productId = item.getAttribute('data-product-id');
                const quantityDisplay = item.querySelector('.quantity-display');
                const currentQuantity = parseInt(quantityDisplay.textContent);

                fetch('update_quantity.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        productId: productId,
                        action: action
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        quantityDisplay.textContent = data.newQuantity;
                        button.disabled = (data.newQuantity <= 1 && action === 'decrease') || (data.newQuantity >= data.totalCount && action === 'increase');
                        window.location.reload();
                    } else {
                        alert(data.error);
                    }
                })
                .catch(err => console.error('Error:', err));
            }
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
