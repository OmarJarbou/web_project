<?php
function redirectIfNotLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit;
    }
}

function redirectIfNotAdmin() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
        header("Location: index.php");
        exit;
    }
}

function redirectIfAdmin() {
    if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
        header("Location: admin.php");
        exit;
    }
}

function redirectIfClient() {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'Client') {
        header("Location: index.php");
        exit;
    }
}
?>
