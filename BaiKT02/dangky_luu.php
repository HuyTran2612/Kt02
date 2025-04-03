<?php
// --- dangky_luu.php ---
require 'config.php';

// Authentication Check... (Giữ nguyên)
if (!isset($_SESSION['user_id'])) { /* ... */ }

// Check session exists... (Giữ nguyên)
if (!isset($_SESSION['dangky']) || empty($_SESSION['dangky'])) { /* ... */ }

// Get student ID... (Giữ nguyên)
$current_masv = $_SESSION['username'] ?? null;
if (!$current_masv) { /* ... */ }

$mahp_list = array_keys($_SESSION['dangky']);
$success = true;

// --- Database Transaction ---
$conn->begin_transaction();

try {
    // Prepare statement ONCE outside the loop
    $stmt_insert = $conn->prepare("INSERT INTO dangkyhocphan (MaSV, MaHP) VALUES (?, ?)");
    // Prepare statement for checking count INSIDE loop (using FOR UPDATE)
    $stmt_check_count = $conn->prepare("SELECT COUNT(*) as DaDangKy FROM dangkyhocphan WHERE MaHP = ? FOR UPDATE"); // Lock rows for this MaHP
    // Prepare statement for getting limit
    $stmt_get_limit = $conn->prepare("SELECT SoLuongDuKien FROM hocphan WHERE MaHP = ?");


    if (!$stmt_insert || !$stmt_check_count || !$stmt_get_limit) {
        throw new Exception("Lỗi chuẩn bị câu lệnh: " . $conn->error);
    }

    foreach ($mahp_list as $mahp) {
        // 1. Get the limit for this course
        $soLuongDuKien = null;
        $stmt_get_limit->bind_param("s", $mahp);
        $stmt_get_limit->execute();
        $result_limit = $stmt_get_limit->get_result();
        if ($row_limit = $result_limit->fetch_assoc()) {
            $soLuongDuKien = $row_limit['SoLuongDuKien'];
        }
        $result_limit->free(); // free result

        // 2. Check current count WITH LOCK if limit exists
        if ($soLuongDuKien !== null) {
            $daDangKy = 0;
            $stmt_check_count->bind_param("s", $mahp);
            $stmt_check_count->execute();
            $result_count = $stmt_check_count->get_result();
             if ($row_c = $result_count->fetch_assoc()) {
                $daDangKy = $row_c['DaDangKy'];
             }
             $result_count->free(); // free result

             // 3. Verify capacity
             if ($daDangKy >= $soLuongDuKien) {
                 throw new Exception("Học phần '$mahp' đã hết chỗ ngay trước khi bạn lưu.");
             }
        }

        // 4. Insert if capacity is okay
        $stmt_insert->bind_param("ss", $current_masv, $mahp);
        if (!$stmt_insert->execute()) {
            if ($conn->errno == 1062) { // Duplicate entry
                 // Ignore duplicate for this specific user/course combo if needed
                 error_log("Duplicate entry ignored during save: MaSV=$current_masv, MaHP=$mahp");
                 // Or treat as failure:
                 // throw new Exception("Lỗi khi đăng ký '$mahp': Bạn đã đăng ký học phần này rồi.");
            } else {
                 throw new Exception("Lỗi INSERT cho '$mahp': " . $stmt_insert->error);
            }
        }
    }

    // Close statements after loop
    $stmt_insert->close();
    $stmt_check_count->close();
    $stmt_get_limit->close();

    // --- Commit or Rollback ---
    $conn->commit();
    unset($_SESSION['dangky']);
    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Đăng ký học phần thành công!'];
    header('Location: dangky_thanhcong.php');
    exit();

} catch (Exception $e) {
    $conn->rollback();
    error_log("Registration save failed inside transaction: " . $e->getMessage());
    // Provide a more specific error if possible
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lưu đăng ký thất bại: ' . htmlspecialchars($e->getMessage())];
    header('Location: dangky_xem.php');
    exit();

} finally {
    // Close statements if they were opened, even on error before loop finishes
     if (isset($stmt_insert) && $stmt_insert instanceof mysqli_stmt && $stmt_insert->sqlstate) { $stmt_insert->close();}
     if (isset($stmt_check_count) && $stmt_check_count instanceof mysqli_stmt && $stmt_check_count->sqlstate) { $stmt_check_count->close();}
     if (isset($stmt_get_limit) && $stmt_get_limit instanceof mysqli_stmt && $stmt_get_limit->sqlstate) { $stmt_get_limit->close();}
    $conn->close();
}
?>