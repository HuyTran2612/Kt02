<?php
// --- dangky_xoa.php ---
require 'config.php';

// Authentication Check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$maHP = $_GET['id'] ?? null;

if ($maHP) {
    // Check if session and key exist before unsetting
    if (isset($_SESSION['dangky']) && isset($_SESSION['dangky'][$maHP])) {
        unset($_SESSION['dangky'][$maHP]);
        $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'Đã xóa học phần "' . htmlspecialchars($maHP) . '" khỏi danh sách đăng ký.'];

        // Optional: If the array becomes empty after deletion, unset the main key too
        if (empty($_SESSION['dangky'])) {
            unset($_SESSION['dangky']);
        }

    } else {
         $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Học phần không có trong danh sách đăng ký hoặc lỗi.'];
    }
} else {
    $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Mã học phần không hợp lệ để xóa.'];
}

// Redirect back to the registration view page
header('Location: dangky_xem.php');
exit();
?>