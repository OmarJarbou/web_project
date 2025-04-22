<?php
require 'session_start.php';
require 'auth_helper.php';

// // Allow only unauthenticated users to see this page
// if (isset($_SESSION['user_id'])) {
//     redirectIfAdmin();
//     redirectIfClient();
// }
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

// Retrieve all categories
$categories = [];

$stmt = $conn->prepare("SELECT id, category FROM categories ORDER BY id");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }

    $stmt->close();
} else {
    die("Failed to prepare the SQL statement: " . $conn->error);
}

// Most Purchased
// SQL query to retrieve the most purchased 4 products for Completed orders
$sql = "
    SELECT 
        p.id AS product_id,
        p.name AS product_name,
        p.catid AS category_id,
        p.prodimg AS product_image,
        p.mime_type AS mime_type,
        p.cost AS product_cost,
        p.quantity AS available_quantity,
        SUM(od.prodcount) AS total_purchased
    FROM 
        products p
    INNER JOIN 
        order_details od ON p.id = od.prodid
    INNER JOIN 
        orders o ON od.orderid = o.id
    WHERE 
        o.status = 'Completed'
    GROUP BY 
        p.id
    ORDER BY 
        total_purchased DESC
    LIMIT 4
";

$result2 = $conn->query($sql);

$mostPurchasedProducts = [];
if ($result2->num_rows > 0) {
    while ($row2 = $result2->fetch_assoc()) {
        $mostPurchasedProducts[] = [
            'id' => $row2['product_id'],
            'name' => $row2['product_name'],
            'category_id' => $row2['category_id'],
            'image' => $row2['product_image'], // This will be binary data
            'mime_type' => $row2['mime_type'],
            'cost' => $row2['product_cost'],
            'available_quantity' => $row2['available_quantity'],
            'total_purchased' => $row2['total_purchased'],
        ];
    }
}

// Trending
// Define the time frame for trending products (e.g., last 30 days)
$timeFrame = date('Y-m-d', strtotime('-30 days'));

// SQL query to retrieve the trending 4 products
$sql2 = "
    SELECT 
        p.id AS product_id,
        p.name AS product_name,
        p.catid AS category_id,
        p.prodimg AS product_image,
        p.mime_type AS mime_type,
        p.cost AS product_cost,
        p.quantity AS available_quantity,
        SUM(od.prodcount) AS total_purchased
    FROM 
        products p
    INNER JOIN 
        order_details od ON p.id = od.prodid
    INNER JOIN 
        orders o ON od.orderid = o.id
    WHERE 
        o.status = 'Completed' AND o.date >= ?
    GROUP BY 
        p.id
    ORDER BY 
        total_purchased DESC
    LIMIT 4
";

$stmt3 = $conn->prepare($sql2);
if ($stmt3) {
    $stmt3->bind_param("s", $timeFrame);
    $stmt3->execute();
    $result3 = $stmt3->get_result();

    $trendingProducts = [];
    while ($row3 = $result3->fetch_assoc()) {
        $trendingProducts[] = [
            'id' => $row3['product_id'],
            'name' => $row3['product_name'],
            'category_id' => $row3['category_id'],
            'image' => $row3['product_image'], // Binary data
            'mime_type' => $row3['mime_type'],
            'cost' => $row3['product_cost'],
            'available_quantity' => $row3['available_quantity'],
            'total_purchased' => $row3['total_purchased'],
        ];
    }

    $stmt3->close();
} else {
    die("Query preparation failed: " . $conn->error);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
    />
    <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>

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

        #profile{
            padding: 0px;
        }
        #profile button{
            width: 44px;
            height: 44px;
            border-radius: 100%;
            padding: 0px;
            background-color: none;
            border: none;
            transition-duration: 0.3s;
        }
        #profile button:hover{
            cursor: pointer;
            transform: scale(1.1);
        }
        #profile button img{
            padding: 0px;
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
        <div class="header">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="prof.php"><div id="profile"><button class="b7"><img src="images/profile-user.png"></button></div></a>
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
        <div class="main">
            <div class="welcome">
                <div class="first">
                    <p><b>Discover Handcrafted Artistry</b></p>
                </div>
                <div class="second">
                    <h1 style="text-align: center;">WELCOME TO QASHAN OFFICIAL PAGE!</h1>
                </div>
                <div class="third">
                    <p style="text-align: center;">Where creativity meets quality! Explore our unique collections and let us bring a touch of inspiration to your world.</p>
                </div>
                <div class="fourth">
                    <button>Explore Now</button>
                </div>
            </div>
            <div class="nav">
               <!--  -->
               <div class="circle-container">
                    <div class="circle large-circle">
                        <section class="slider_container">
                            <section class="slider">
                            <div class="slide one">
                                <img src="images/plate1.jpeg"/>
                            </div>
                            <div class="slide two">
                                <img src="images/plate_4.jpeg"/>
                            </div>
                            <div class="slide three">
                                <img src="images/plate_5.jpeg"/>
                            </div>
                            <div class="slide four">
                                <img src="images/plate_6.jpeg"/>
                            </div>
                            <div class="slide four">
                                <img src="images/burner2.jpeg"/>
                            </div>
                            </section>
                        </section>
                    </div>
                    <div class="circle medium-circle">
                        <section class="slider_container">
                            <section class="slider">
                                <div class="slide one">
                                    <img src="images/burner1.jpeg"/>
                                </div>
                                <div class="slide two">
                                    <img src="images/planter1.jpeg"/>
                                </div>
                                <div class="slide three">
                                    <img src="images/planter_2.jpeg"/>
                                </div>
                                <div class="slide four">
                                    <img src="images/plate_3.jpeg"/>
                                </div>
                                <div class="slide four">
                                    <img src="images/sculpture1.jpg"/>
                                </div>
                                </section>
                        </section>
                    </div>
                    <div class="purple-circle small purple1"></div>
                    <div class="purple-circle medium purple2"></div>
                    <div class="purple-circle large purple3"></div>
                </div>
            </div>
        </div>
        
        <div class="sections">
            <?php foreach ($categories as $category) {
                $cat = preg_replace('/\s+/', '_', $category['category']);  
                $name_array = explode(" ", $category['category']);       
            ?>
                    <div class="<?=$cat?>">
                        <a href="shop.php">
                            <img src="get_image.php?id=<?= $category['id'] ?>&column=catimg&table=categories&mime=mime_type" alt="<?= $category['category'] ?>" id="<?= strtolower($cat) ?>Image"> 
                        </a>
                        <h2>
                            <?php
                                foreach ($name_array as $cn) {
                            ?>
                            <?=$cn?>
                            <br>
                            <?php
                                }
                            ?>
                        </h2>
                    </div>  
            <?php
                }
            ?>
        </div>

        <div class="trending">
            <div class="top">
                <div class="first">
                    <p><b>TRENDING</b></p>
                </div>
                <div class="horizontal">
                    <h1 class="label">Trending Items</h1>
                </div>
            </div>
            <div class="content">
                <?php $q = 0; ?>
                <?php foreach ($trendingProducts as $product) {
                    $q++;  
                    $tr = ($q === 1)? "" : $q;
                ?>
                        <div class="tr1_card" id="tr<?= $tr ?>">
                            <div class="tr1_image">
                                <form action="details.php" method="POST">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <!-- <input type="hidden" name="user_id" value="<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '' ?>"> -->
                                    <button type="submit" style="border: none; margin: 0px; padding: 0px;">
                                        <img src="get_image.php?id=<?= $product['id'] ?>&column=prodimg&table=products&mime=mime_type" alt="TR1 Image">
                                    </button>
                                </form>
                                <div class="price_tag">$<?= $product['cost'] ?></div>
                            </div>
                            <div class="item_info">
                                <p class="category"><?= $categories[$product['category_id'] - 1]['category'] ?></p>
                                <h2 class="img_title"><b><?= $product['name'] ?></b></h2>
                                <img class="shop_icon" src="images/shopping-bag.png" alt="shopping" id="shoppingImage<?= $tr ?>">
                            </div>
                        </div>
                <?php
                    }
                ?>
            </div>
        </div>

        <div class="most_purchased">
            <div class="top">
                <div class="horizontal1">
                    <div class="first">
                        <p><b>TOP PRODUCT</b></p>
                    </div>
                    <div class="second">
                        <h1 class="label">Most Purchased</h1>
                    </div>
                </div>
                <div class="horizontal2">
                    <button class="view_all">VIEW ALL</button>
                </div>
            </div>
            <div class="content">
                <?php $k = 0;?>
                <?php foreach ($mostPurchasedProducts as $product) {
                    $k++;  
                    $mp = ($k === 1)? "" : $k;
                ?>
                        <div class="mp_card" id="mp<?= $mp ?>">
                            <div class="mp_image">
                                <img src="get_image.php?id=<?= $product['id'] ?>&column=prodimg&table=products&mime=mime_type">
                            </div>
                            <div class="item_info">
                                <p class="category"><?= $categories[$product['category_id'] - 1]['category'] ?></p>
                                <h2 class="img_title"><b><?= $product['name'] ?></b></h2>
                            </div>
                            <div class="explore-button">
                                <form action="details.php" method="POST">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <input type="hidden" name="user_id" value="<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '' ?>">
                                    <button type="submit">EXPLORE</button>
                                </form>
                            </div>
                        </div>
                <?php
                    }
                ?>
            </div>
        </div>

        <div class="last_section">
            <div class="shop">
                <div class="first">
                    <p><b>OUR SHOP</b></p>
                </div>
                <div class="second">
                    <p style="text-align: center;"><b>Extraordinary Prices & Best Offers For You!</b></p>
                </div>
                <div class="third">
                    <p style="text-align: center;"><b>See our latest products and get to know to our categories.</b></p>
                </div>
                <div class="fourth">
                    <button>SHOP NOW</button>
                </div>
            </div>
            <div class="joinus">
                <div class="first">
                    <p><b>SIGN IN</b></p>
                </div>
                <div class="second">
                    <p style="text-align: center;"><b>Sign In And Join Our Beautiful Family!</b></p>
                </div>
                <div class="third">
                    <button>JOIN US</button>
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

        <?php
            if(isset($_SESSION['user_id'])) {
        ?>
            <a href="cart.php" class="fabutton">
                <i class="bi bi-cart"></i>
            </a>
        <?php
            }
        ?>
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

        const hamburger = document.querySelector('.hamburger');
        const mobileMenu = document.querySelector('.mobile_menu');

        hamburger.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');
        });

        <?php
            $y = 0;
        ?>
        <?php foreach ($categories as $category) {
                $y++;
                $cat2 = preg_replace('/\s+/', '_', $category['category']);  
                $catImgID = strtolower($cat2) . "Image";    
            ?>
                const catImage<?=$y?> = document.getElementById("<?= $catImgID ?>");
                // Change the `src` on hover
                catImage<?=$y?>.addEventListener('mouseenter', () => {
                catImage<?=$y?>.src = 'get_image.php?id=<?= $category['id'] ?>&column=catimg2&table=categories&mime=mime_type2'; // Replace with your hover image path
                });
            
                // Restore the original `src` when the mouse leaves
                catImage<?=$y?>.addEventListener('mouseleave', () => {
                catImage<?=$y?>.src = 'get_image.php?id=<?= $category['id'] ?>&column=catimg&table=categories&mime=mime_type'; // Replace with your original image path
                });
            <?php
                }
            ?>

        // Change the `src` on hover
        trendingElement1.addEventListener('mouseenter', () => {
            shoppingImage1.src = 'images/shopping-bag2.png'; // Replace with your hover image path
        });
    
        // Restore the original `src` when the mouse leaves
        trendingElement1.addEventListener('mouseleave', () => {
            shoppingImage1.src = 'images/shopping-bag.png'; // Replace with your original image path
        });

        // Change the `src` on hover
        trendingElement2.addEventListener('mouseenter', () => {
            shoppingImage2.src = 'images/shopping-bag2.png'; // Replace with your hover image path
        });
        // Restore the original `src` when the mouse leaves
        trendingElement2.addEventListener('mouseleave', () => {
            shoppingImage2.src = 'images/shopping-bag.png'; // Replace with your original image path
        });

        // Change the `src` on hover
        trendingElement3.addEventListener('mouseenter', () => {
            shoppingImage3.src = 'images/shopping-bag2.png'; // Replace with your hover image path
        });
    
        // Restore the original `src` when the mouse leaves
        trendingElement3.addEventListener('mouseleave', () => {
            shoppingImage3.src = 'images/shopping-bag.png'; // Replace with your original image path
        });

        // Change the `src` on hover
        trendingElement4.addEventListener('mouseenter', () => {
            shoppingImage4.src = 'images/shopping-bag2.png'; // Replace with your hover image path
        });
        // Restore the original `src` when the mouse leaves
        trendingElement4.addEventListener('mouseleave', () => {
            shoppingImage4.src = 'images/shopping-bag.png'; // Replace with your original image path
        });
    </script>
    <script>
        function hideSignupSuccess(suc) {
            suc.style.display = 'none';
        }
        function hideSignupFail(fail) {
            fail.style.display = 'none';
        }
    </script>
</body>
</html>