<?php
// --- dangky_them.php ---
require 'config.php';

// Authentication Check
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Phiên đăng nhập hết hạn hoặc không hợp lệ.'];
    header('Location: login.php');
    exit();
}

$maHP = $_GET['id'] ?? null;
$redirect_page = 'hocphan.php'; // Default redirect back to list

if ($maHP) {
    // --- Check course existence and capacity ---
    $soLuongDuKien = null;
    $daDangKy = 0;
    $course_exists = false;

    $stmt_check = $conn->prepare("SELECT SoLuongDuKien FROM hocphan WHERE MaHP = ?");
    if ($stmt_check) {
        $stmt_check->bind_param("s", $maHP);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($row_check = $result_check->fetch_assoc()) {
            $course_exists = true;
            $soLuongDuKien = $row_check['SoLuongDuKien'];
        }
        $stmt_check->close();
    } else {
         $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lỗi kiểm tra thông tin học phần.'];
         header('Location: ' . $redirect_page); exit();
    }

    if (!$course_exists) {
        $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Mã học phần không hợp lệ.'];
    } else {
        // Only check current count if there is a limit
        if ($soLuongDuKien !== null) {
            $stmt_count = $conn->prepare("SELECT COUNT(*) as DaDangKy FROM dangkyhocphan WHERE MaHP = ?");
            if($stmt_count){
                 $stmt_count->bind_param("s", $maHP);
                 $stmt_count->execute();
                 $result_count = $stmt_count->get_result();
                 if($row_c = $result_count->fetch_assoc()){
                     $daDangKy = $row_c['DaDangKy'];
                 }
                 $stmt_count->close();
            } else {
                 $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lỗi kiểm tra số lượng đã đăng ký.'];
                 header('Location: ' . $redirect_page); exit();
            }

            // Check if full BEFORE adding to session
            if ($daDangKy >= $soLuongDuKien) {
                 $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Học phần "' . htmlspecialchars($maHP) . '" đã đủ số lượng đăng ký.'];
                 header('Location: ' . $redirect_page);
                 exit();
            }
        }

        // --- Add to session if not full ---
        if (!isset($_SESSION['dangky'])) {
            $_SESSION['dangky'] = [];
        }

        if (!isset($_SESSION['dangky'][$maHP])) {
             $_SESSION['dangky'][$maHP] = 1;
             $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Đã thêm học phần "' . htmlspecialchars($maHP) . '" vào danh sách đăng ký.'];
             $redirect_page = 'dangky_xem.php'; // Redirect to cart view after successful add
        } else {
             $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'Học phần "' . htmlspecialchars($maHP) . '" đã có trong danh sách đăng ký.'];
             $redirect_page = 'dangky_xem.php'; // Redirect to cart view even if already exists
        }
    }
    $conn->close();

} else {
    $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Không có mã học phần được cung cấp.'];
}

header('Location: ' . $redirect_page);
exit();
?>