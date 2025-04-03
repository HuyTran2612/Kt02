<?php
require 'config.php'; // Connects, STARTS SESSION, defines helpers

// --- Authentication Check ---
require_login();
// --- End Authentication Check ---

$maHP = trim($_GET['id'] ?? ''); // Trim whitespace
$redirect_page = 'hocphan.php'; // Default redirect back to list

// Validate that MaHP is provided and looks reasonable (basic check)
if (empty($maHP)) {
    $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Mã học phần không được cung cấp.'];
    header('Location: ' . $redirect_page);
    exit();
}

// --- Check if the course actually exists in the database ---
$course_exists = false;
$stmt_check = $conn->prepare("SELECT MaHP FROM HocPhan WHERE MaHP = ?");

if (!$stmt_check) {
    error_log("Prepare failed for course check: " . $conn->error);
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lỗi hệ thống khi kiểm tra học phần.'];
    header('Location: ' . $redirect_page);
    exit();
}

$stmt_check->bind_param("s", $maHP);
if (!$stmt_check->execute()) {
    error_log("Execute failed for course check: " . $stmt_check->error);
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lỗi hệ thống khi kiểm tra học phần (execute).'];
    $stmt_check->close();
    header('Location: ' . $redirect_page);
    exit();
}

$result_check = $stmt_check->get_result();
if ($result_check->num_rows > 0) {
    $course_exists = true;
}
$stmt_check->close();
// --- End Course Existence Check ---


if (!$course_exists) {
    $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Mã học phần "' . htmlspecialchars($maHP) . '" không hợp lệ hoặc không tồn tại.'];
    // Redirect back to course list if invalid MaHP provided
    header('Location: ' . $redirect_page);
    exit();
} else {
    // --- Course exists, add to session cart ---

    // Initialize the session cart if it doesn't exist
    if (!isset($_SESSION['dangky'])) {
        $_SESSION['dangky'] = [];
    }

    // Check if already in the cart
    if (!isset($_SESSION['dangky'][$maHP])) {
         // Add MaHP as key. Value can be simple (true/1) or details if needed.
         // Using 'true' is simple and efficient for just tracking selection.
         $_SESSION['dangky'][$maHP] = true;
         $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Đã thêm học phần "' . htmlspecialchars($maHP) . '" vào danh sách chờ đăng ký.'];
    } else {
         // Already in the cart, just inform the user
         $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'Học phần "' . htmlspecialchars($maHP) . '" đã có trong danh sách chờ đăng ký.'];
    }

    // Redirect to the cart view page after adding or if it already exists
    $redirect_page = 'dangky_xem.php';
}

// --- Close DB connection and redirect ---
$conn->close();
header('Location: ' . $redirect_page);
exit(); // IMPORTANT: Stop script execution after header redirect
?>