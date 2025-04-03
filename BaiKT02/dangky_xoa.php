<?php
require 'config.php'; // Connects, starts session, defines helpers

// --- Authentication Check ---
require_login();
// --- End Authentication Check ---

$maHP = trim($_GET['id'] ?? ''); // Trim whitespace

if (!empty($maHP)) {
    // Check if session cart exists and the specific key exists before unsetting
    if (isset($_SESSION['dangky']) && is_array($_SESSION['dangky']) && isset($_SESSION['dangky'][$maHP])) {
        // Remove the course from the temporary cart
        unset($_SESSION['dangky'][$maHP]);
        $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'Đã xóa học phần "' . htmlspecialchars($maHP) . '" khỏi danh sách chờ đăng ký.'];

        // Optional: If the cart becomes empty after deletion, unset the main session key
        // This can help differentiate between an empty cart and no cart ever started.
        if (empty($_SESSION['dangky'])) {
            unset($_SESSION['dangky']);
            $_SESSION['flash_message']['message'] .= ' Danh sách chờ hiện đang trống.';
        }

    } else {
         // Course wasn't in the session cart or cart didn't exist
         $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Học phần "' . htmlspecialchars($maHP) . '" không có trong danh sách chờ đăng ký.'];
    }
} else {
    $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Mã học phần không hợp lệ để xóa khỏi danh sách chờ.'];
}

// --- Close DB connection (optional here, but good practice if opened) ---
// $conn->close(); // Uncomment if config.php doesn't auto-close or if needed

// --- Redirect back to the registration view/cart page ---
header('Location: dangky_xem.php');
exit(); // Stop script execution
?>