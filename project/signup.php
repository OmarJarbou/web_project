<?php
require 'session_start.php';
require 'auth_helper.php';
require 'db.php'; // Include the database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect and sanitize input data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    $address = trim($_POST['address']);
    $gender = trim($_POST['gender']);
    $password = trim($_POST['password']);
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $bdate = trim($_POST['birthdate']);

    // Validate input data
    $errors = [];

    // Username validation
    if (empty($username) || strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username must be at least 3 characters long and can only contain letters, numbers, and underscores.";
    }

    // Email validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Check if the email is disposable (optional)
    $disposableDomains = ['tempmail.com', '10minutemail.com', 'mailinator.com']; // Add more disposable domains as needed
    $emailDomain = substr(strrchr($email, "@"), 1);
    if (in_array($emailDomain, $disposableDomains)) {
        $errors[] = "Disposable email addresses are not allowed.";
    }

    // Contact number validation
    if (empty($contact) || !preg_match('/^[0-9]{10,15}$/', $contact)) {
        $errors[] = "Contact number must be between 10 to 15 digits.";
    }

    // Address validation
    if (empty($address)) {
        $errors[] = "Address is required.";
    }

    // Gender validation
    if (empty($gender)) {
        $errors[] = "Gender is required.";
    }

    // Password validation
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one numeric digit.";
    } elseif (!preg_match('/[\W_]/', $password)) {
        $errors[] = "Password must contain at least one special character (e.g., !@#$%^&*()).";
    }

    // First Name validation
    if (empty($fname) || !preg_match('/^[a-zA-Z]+$/', $fname)) {
        $errors[] = "First Name is required and can only contain letters.";
    }

    // Last Name validation
    if (empty($lname) || !preg_match('/^[a-zA-Z]+$/', $lname)) {
        $errors[] = "Last Name is required and can only contain letters.";
    }

    // Birthdate validation
    if (empty($bdate)) {
        $errors[] = "Birthdate is required.";
    } else {
        $birthdate = DateTime::createFromFormat('Y-m-d', $bdate);
        if (!$birthdate || $birthdate > new DateTime()) {
            $errors[] = "Birthdate must be a valid date and cannot be in the future.";
        }
    }

    // Check if username or email already exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
            $stmt->execute(['username' => $username, 'email' => $email]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Username or Email or Contact already exists.";
            }
        } catch (PDOException $e) {
            $errors[] = "Error retrieving info: " . $e->getMessage();
            header("Location: index.php");
        }
    }

    // If no errors, insert the user into the database
    if (empty($errors)) {
        try { 
            $role = "Client";
            $status = "INACTIVE";
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, contact, gender, location, password, firstname, lastname, role, status, birthdate) VALUES (:username, :email, :contact, :gender, :address, :password, :fname , :lname, :role, :status, :birthdate)");
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'contact' => $contact,
                'gender' => $gender,
                'address' => $address,
                'password' => $hashedPassword,
                'fname' => $fname,
                'lname' => $lname,
                'role' => $role,
                'status' => $status,
                'birthdate' => $bdate
            ]);

            $_SESSION['success'] = "Signup successful! You can now log in.";
            header("Location: index.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error creating user: " . $e->getMessage();
            $_SESSION['errors'] = $errors;
            header("Location: index.php");
        }
    } else {
        // Store errors in session to display later
        $_SESSION['errors'] = $errors;
        header("Location: index.php"); // Redirect back to index.php
        exit();
    }
}
?>