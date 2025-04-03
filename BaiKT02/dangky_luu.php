<?php
require 'config.php'; // Connects, starts session, defines helpers

// --- Authentication Check ---
require_login();
// --- End Authentication Check ---

/* Basic CSRF Check - Recommended for production
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lỗi bảo mật (CSRF token không hợp lệ). Vui lòng thử lại.'];
        // Unset token to force regeneration on next page load
        unset($_SESSION['csrf_token']);
        header('Location: dangky_xem.php');
        exit();
    }
    // CSRF token is valid, remove it after use
    unset($_SESSION['csrf_token']);
} else {
    // Allow GET only for specific purposes if needed, otherwise block
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Phương thức truy cập không hợp lệ.'];
    header('Location: dangky_xem.php');
    exit();
}
*/


// --- Check Session Cart ---
if (!isset($_SESSION['dangky']) || !is_array($_SESSION['dangky']) || empty($_SESSION['dangky'])) {
    $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Không có học phần nào trong danh sách chờ để lưu.'];
    header('Location: hocphan.php');
    exit();
}

// --- Get Student ID ---
$current_masv = $_SESSION['username'] ?? null;
if (!$current_masv) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lỗi xác định Mã Sinh Viên. Không thể lưu. Vui lòng đăng nhập lại.'];
    header('Location: login.php');
    exit();
}

// --- Get List of Courses to Register ---
$mahp_list_to_register = array_keys($_SESSION['dangky']);
$current_date = date('Y-m-d'); // SQL DATE format

// --- Database Transaction ---
$conn->begin_transaction();

$new_madk = null; // To store the ID generated from DangKy table
$stmt_insert_dk = null; // Define statement variables outside try for finally block
$stmt_insert_ctdk = null;

try {
    // --- Step 1: Insert into DangKy table ---
    $sql_insert_dk = "INSERT INTO DangKy (NgayDK, MaSV) VALUES (?, ?)";
    $stmt_insert_dk = $conn->prepare($sql_insert_dk);
    if (!$stmt_insert_dk) {
        throw new Exception("Lỗi chuẩn bị lệnh INSERT vào DangKy: " . $conn->error);
    }

    $stmt_insert_dk->bind_param("ss", $current_date, $current_masv);

    if (!$stmt_insert_dk->execute()) {
        // Check for foreign key constraint failure on MaSV
        if ($conn->errno == 1452) {
             throw new Exception("Lỗi thực thi INSERT vào DangKy: Mã Sinh Viên '{$current_masv}' không hợp lệ hoặc không tồn tại.");
        } else {
             throw new Exception("Lỗi thực thi INSERT vào DangKy: (" . $conn->errno . ") " . $stmt_insert_dk->error);
        }
    }

    // Get the newly generated MaDK (Auto Increment ID)
    $new_madk = $conn->insert_id;
    if (!$new_madk || $new_madk <= 0) { // Check if ID is valid
         throw new Exception("Không lấy được Mã Đăng Ký (MaDK) hợp lệ sau khi insert vào DangKy.");
    }
    $stmt_insert_dk->close(); // Close statement for Step 1

    // --- Step 2: Insert into ChiTietDangKy table (Loop) ---
    $sql_insert_ctdk = "INSERT INTO ChiTietDangKy (MaDK, MaHP) VALUES (?, ?)";
    $stmt_insert_ctdk = $conn->prepare($sql_insert_ctdk);
    if (!$stmt_insert_ctdk) {
        throw new Exception("Lỗi chuẩn bị lệnh INSERT vào ChiTietDangKy: " . $conn->error);
    }

    $errors_details = []; // Collect errors during detail insertion
    foreach ($mahp_list_to_register as $mahp) {
        // MaDK is INT (i), MaHP is CHAR/VARCHAR (s)
        $stmt_insert_ctdk->bind_param("is", $new_madk, $mahp);

        if (!$stmt_insert_ctdk->execute()) {
            // Collect errors instead of throwing immediately to try inserting others
            if ($conn->errno == 1062) { // Duplicate primary key (MaDK, MaHP) - should be rare here
                 $errors_details[] = "Lỗi trùng lặp khi đăng ký chi tiết '$mahp'.";
                 error_log("Duplicate entry during save: MaDK=$new_madk, MaHP=$mahp");
            } elseif ($conn->errno == 1452) { // Foreign key constraint fails (invalid MaHP or MaDK)
                 $errors_details[] = "Mã học phần '$mahp' không hợp lệ hoặc Mã đăng ký ($new_madk) có vấn đề.";
                 error_log("FK constraint fail during save: MaDK=$new_madk, MaHP=$mahp");
            } else {
                 $errors_details[] = "Lỗi INSERT vào ChiTietDangKy cho '$mahp': (" . $conn->errno . ") " . $stmt_insert_ctdk->error;
                 error_log("INSERT error ChiTietDangKy: MaDK=$new_madk, MaHP=$mahp - (" . $conn->errno . ") " . $stmt_insert_ctdk->error);
            }
        }
    }
    $stmt_insert_ctdk->close(); // Close statement for Step 2 after the loop

    // --- Check if any detail errors occurred ---
    if (!empty($errors_details)) {
        // If any detail failed, throw an exception to trigger rollback
        throw new Exception("Có lỗi xảy ra khi lưu chi tiết đăng ký: " . implode("; ", $errors_details));
    }

    // --- Commit Transaction ---
    $conn->commit();

    // --- Success ---
    unset($_SESSION['dangky']); // Clear the temporary cart session
    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Đăng ký học phần thành công! (Mã ĐK: ' . $new_madk . ')'];
    header('Location: dangky_thanhcong.php');
    exit();

} catch (Exception $e) {
    // --- Rollback Transaction on Error ---
    $conn->rollback();

    // Log the detailed error for administrators
    error_log("Registration save FAILED: MaSV=$current_masv. Error: " . $e->getMessage());

    // Provide a user-friendly error message
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'Lưu đăng ký thất bại. Lý do: ' . htmlspecialchars($e->getMessage()) . ' Vui lòng kiểm tra lại danh sách chọn hoặc liên hệ quản trị viên.'
    ];

    // Redirect back to the confirmation/view page so user can see the error and maybe retry
    header('Location: dangky_xem.php');
    exit();

} finally {
    // Ensure statements are closed if they were successfully prepared, even on error
     if (isset($stmt_insert_dk) && $stmt_insert_dk instanceof mysqli_stmt) { $stmt_insert_dk->close();}
     if (isset($stmt_insert_ctdk) && $stmt_insert_ctdk instanceof mysqli_stmt) { $stmt_insert_ctdk->close();}
    // Ensure connection is closed
    $conn->close();
}
?>