<?php
require 'session_start.php';
require 'auth_helper.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect and sanitize input data
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate input data
    $errors = [];

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    // Check for errors
    if (empty($errors)) {
        // Prepare and execute the query to find the user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // Verify the password
        if ($user && password_verify($password, $user['password'])) {
            // Start the session and save user data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['lastname'] = $user['lastname'];
            $_SESSION['role'] = $user['role']; // Store user role

            // Redirect based on user role
            if ($user['role'] === 'Admin') {
                $_SESSION['success'] = 'You Are Logged In Our Website Now, Welcome ' . $user['firstname'] . '!';
                header("Location: admin.php");
            } else {
                //add a new uncompleted order that keep up with user until he log out or end his session
                $stmt2 = $pdo->prepare('INSERT INTO uncompleted_orders (userid, totalcost) VALUES (' . $user['id'] . ', 0)');
                try {
                    $stmt2->execute();
                } catch (PDOException $e) {
                    $errors[] = 'Error While Login.';
                    $_SESSION['errors'] = $errors;
                    header("Location: index.php"); // Redirect back to index.php
                    unset($_SESSION['user_id']);
                    unset($_SESSION['username']);
                    unset($_SESSION['firstname']);
                    unset($_SESSION['lastname']);
                    unset($_SESSION['role']);
                    exit();
                }
                $_SESSION['uc_order_id'] = $pdo->lastInsertId();
                $_SESSION['success'] = 'You Are Logged In Our Website Now, Welcome ' . $user['firstname'] . '!';
                header("Location: index.php");
            }
            exit();
        } else {
          $errors[] = 'Invalid email or password.';
        }
    }

    // Store errors in session to display later
    $_SESSION['errors'] = $errors;
    header("Location: index.php"); // Redirect back to index.php
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="login.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
    />
  <title>SignUp</title>
  <style>
        .login_container{
            animation: fadeIn 0.3s ease-in-out;
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
  </style>
</head>
<body>
            <div class="login_container">
                <input type="checkbox" id="flip" />
                <div class="cover">
                    <div class="front">
                        <img src="images/plate1.jpeg" alt="" style="position: relative; background: red;" />
                    </div>
                    <div class="back">
                        <img class="backImg" src="images/planter1.jpeg" alt="" />
                    </div>
                </div>
                <div class="forms">
                    <div class="form-content">
                        <div class="login-form">
                            <div class="title">Login</div>
                            <form action="login.php" method="POST">
                                <div class="input-boxes">
                                    <div class="input-box">
                                        <i class="fas fa-envelope"></i>
                                        <input type="text" name="email" placeholder="Enter your email" required />
                                    </div>
                                    <div class="input-box">
                                        <i class="fas fa-lock"></i>
                                        <input type="password" name="password" placeholder="Enter your password" required />
                                    </div>
                                    <div class="text"><a href="#">Forgot password?</a></div>
                                    <div class="button input-box">
                                        <input type="submit" value="Submit" />
                                    </div>
                                    <div class="text sign-up-text">
                                        Don't have an account? <label for="flip">Signup now</label>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="signup-form">
                            <div class="title">Signup</div>
                            <form action="signup.php" method="POST">
                                <div class="input-boxes">
                                    <div class="scrollable-inputs">
                                        <div class="input-box">
                                            <i class="fas fa-user"></i>
                                            <input type="text" name="fname" placeholder="Enter your fist name" required />
                                        </div>
                                        <div class="input-box">
                                            <i class="fas fa-user"></i>
                                            <input type="text" name="lname" placeholder="Enter your last name" required />
                                        </div>
                                        <div class="input-box">
                                            <i class="fas fa-user-circle"></i>
                                            <input type="text" name="username" placeholder="Enter a unique username" required />
                                        </div>
                                        <div class="input-box">
                                            <i class="fas fa-envelope"></i>
                                            <input type="text" name="email" placeholder="Enter your email" required />
                                        </div>
                                        <div class="input-box">
                                            <i class="fas fa-phone"></i>
                                            <input type="text" name="contact" placeholder="Enter your contact number" required />
                                        </div>
                                        <div class="input-box">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <input type="text" name="address" placeholder="Enter your address" required />
                                        </div>
                                        <div class="input-box">
                                            <i class="fas fa-venus-mars"></i>
                                            <select name="gender" required>
                                                <option value="" disabled selected>Select your gender</option>
                                                <option value="M">Male</option>
                                                <option value="F">Female</option>
                                            </select>
                                        </div>
                                        <label for="birthdaytime">Birthdate</label>
                                        <div class="input-box">
                                            <i class="fas fa-calendar-alt"></i>
                                            <!-- block forward days in date input html? -->
                                            <input id="birthdaytime" type="date" name="birthdate" required />
                                        </div>
                                        <div class="input-box">
                                            <i class="fas fa-lock"></i>
                                            <input type="password" name="password" placeholder="Enter your password" required />
                                        </div>
                                    </div>
                                    <div class="button input-box">
                                        <input type="submit" value="Submit" />
                                    </div>
                                    <div class="text sign-up-text">
                                        Already have an account? <label for="flip">Login now</label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
</body>
</html>