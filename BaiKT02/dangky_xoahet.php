<?php
require 'config.php'; // Connects, starts session, defines helpers

// --- Authentication Check ---
require_login();
// --- End Authentication Check ---

// Check if session cart exists and has items before unsetting
if (isset($_SESSION['dangky']) && is_array($_SESSION['dangky']) && !empty($_SESSION['dangky'])) {
    // Clear the entire temporary cart
    unset($_SESSION['dangky']);
    $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'Đã xóa toàn bộ danh sách học phần đang chờ đăng ký.'];
} else {
     // Cart was already empty or didn't exist
     $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'Không có học phần nào trong danh sách chờ để xóa.'];
}

// --- Close DB connection (optional here) ---
// $conn->close();

// --- Redirect back to the registration view/cart page ---
header('Location: dangky_xem.php');
exit(); // Stop script execution
?>