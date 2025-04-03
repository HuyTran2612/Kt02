<?php
// --- dangky_xoahet.php ---
require 'config.php';

// Authentication Check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if session exists before unsetting
if (isset($_SESSION['dangky'])) {
    unset($_SESSION['dangky']);
    $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'Đã xóa toàn bộ danh sách học phần đăng ký.'];
} else {
     $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'Không có học phần nào trong danh sách để xóa.'];
}

// Redirect back to the registration view page
header('Location: dangky_xem.php');
exit();
?>