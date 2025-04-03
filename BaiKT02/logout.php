<?php
// --- logout.php ---
require 'config.php'; // Must include to start the session before destroying it

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie (optional but good practice)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Set a flash message for the login page (optional)
session_start(); // Need to start a *new* session to store the flash message
$_SESSION['flash_message'] = ['type' => 'info', 'message' => 'Bạn đã đăng xuất thành công.'];

// Redirect to login page
header("Location: login.php");
exit();
?>