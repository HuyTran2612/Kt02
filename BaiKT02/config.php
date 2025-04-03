<?php
// MUST BE THE VERY FIRST THING IN THE FILE
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Start session handling if not already started
}

// --- Database Credentials ---
$servername = "localhost"; // Usually 'localhost' or '127.0.0.1' for local
$username   = "root";      // Default XAMPP username (change for production)
$password   = "";          // Default XAMPP password (change for production)
$dbname     = "Test1";     // Your specific database name

// --- Create Connection (MySQLi) ---
$conn = new mysqli($servername, $username, $password, $dbname);

// --- Check Connection ---
if ($conn->connect_error) {
    error_log("Database Connection failed: (" . $conn->connect_errno . ") " . $conn->connect_error);
    // Display generic error and stop execution if DB is essential
    die("Không thể kết nối đến cơ sở dữ liệu. Vui lòng thử lại sau hoặc liên hệ quản trị viên.");
}

// --- Set Character Set for UTF-8 (Vietnamese) ---
if (!$conn->set_charset("utf8mb4")) {
    error_log("Error loading character set utf8mb4: " . $conn->error);
    // Consider if this is fatal or not
}

// --- File Upload Configuration ---
/**
 * IMPORTANT: Directory path for storing uploaded files.
 * This path is RELATIVE to the location of your PHP scripts (like index.php, edit.php).
 * Example: If scripts are in C:\xampp\htdocs\Project\ and you want uploads in C:\xampp\htdocs\Project\uploads\
 * then use 'uploads/'.
 *
 * *** YOU MUST CREATE THIS DIRECTORY MANUALLY! ***
 * *** Ensure your web server (Apache in XAMPP) has WRITE PERMISSIONS for this directory. ***
 * (Usually not an issue on Windows XAMPP, critical on Linux).
 */
define('UPLOAD_DIR', 'uploads/');

/**
 * IMPORTANT: Base URL path for accessing the uploads directory FROM THE WEB BROWSER.
 * This MUST match how your project is accessed in the browser's address bar.
 *
 * Based on your error message path: C:\xampp\htdocs\TranHuynhGiaHuy_2180608521\BaiKT02\
 * Assuming 'htdocs' is your web root, the URL path is likely:
 * http://localhost/TranHuynhGiaHuy_2180608521/BaiKT02/
 *
 * *** ADJUST THIS PATH IF YOUR URL IS DIFFERENT! *** Make sure it ENDS with a slash '/'.
 */
define('BASE_URL', '/TranHuynhGiaHuy_2180608521/BaiKT02/');

// --- End File Upload Configuration ---


// --- Error Reporting (Development vs Production) ---
// For Development: Show all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
// For Production: Log errors, don't display them
// error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED); // Report critical errors
// ini_set('display_errors', 0);                      // Hide errors from users
// ini_set('log_errors', 1);                          // Enable logging
// ini_set('error_log', '/path/outside/webroot/php-error.log'); // Set secure log path

// --- Helper Functions ---

/**
 * Displays and clears flash messages stored in the session.
 * Assumes Bootstrap alert structure.
 */
function display_flash_message() {
    if (isset($_SESSION['flash_message']) && is_array($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_message']['type'] ?? 'info';
        $message = $_SESSION['flash_message']['message'] ?? 'Thông báo.';
        $safe_type = htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
        $safe_message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        echo "<div class='alert alert-{$safe_type} alert-dismissible fade show' role='alert'>";
        echo $safe_message;
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo "</div>";
        unset($_SESSION['flash_message']);
    }
}

/**
 * Checks if the user is logged in (based on $_SESSION['user_id']).
 * Redirects to the specified login page if not logged in.
 *
 * @param string $redirect_url The URL to redirect to. Defaults to 'login.php'.
 */
function require_login($redirect_url = 'login.php') {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) { // Check if user_id is set and not empty
         $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Vui lòng đăng nhập để truy cập trang này.'];
         header('Location: ' . $redirect_url);
         exit(); // Stop script execution after redirect
    }
}

?>