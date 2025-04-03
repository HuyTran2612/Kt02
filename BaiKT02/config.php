<?php
// --- config.php ---

// --- Error Reporting (Development Only - Remove/comment out for Production) ---
error_reporting(E_ALL);
ini_set('display_errors', 1); // SET TO 0 OR REMOVE FOR PRODUCTION

// --- Define Constants and Check Directories FIRST ---
define('UPLOAD_DIR', 'uploads/'); // Relative path

if (!is_dir(UPLOAD_DIR)) {
    if (!mkdir(UPLOAD_DIR, 0755, true) && !is_dir(UPLOAD_DIR)) {
         die('Lỗi nghiêm trọng: Không thể tạo thư mục '. UPLOAD_DIR .'. Vui lòng kiểm tra quyền.');
    }
}
elseif (!is_writable(UPLOAD_DIR)) {
    die('Lỗi cấu hình: Thư mục '. UPLOAD_DIR .' không có quyền ghi. Vui lòng kiểm tra quyền.');
}

// --- Database Configuration ---
$servername = "localhost";
$username = "root"; // Use a less privileged user in production
$password = ""; // Use a strong password in production
$dbname = "test1"; // Make sure this database exists

// --- Database Connection ---
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    // error_log("Database connection failed: [" . $conn->connect_errno . "] " . $conn->connect_error);
    die("Kết nối cơ sở dữ liệu thất bại. Vui lòng thử lại sau hoặc liên hệ quản trị viên.");
}

if (!$conn->set_charset("utf8mb4")) {
     // error_log("Error loading character set utf8mb4: %s\n", $conn->error);
}

// --- Session Management ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>