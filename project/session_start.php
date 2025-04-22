<?php
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session cookie parameters before starting the session
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']), // Only send over HTTPS
        'httponly' => true, // Inaccessible via JavaScript
        'samesite' => 'Strict'
    ]);

    session_start();

    // Regenerate session ID after login for security
    if (!isset($_SESSION['initialized'])) {
        session_regenerate_id(true);
        $_SESSION['initialized'] = true;
    }
}
?>
