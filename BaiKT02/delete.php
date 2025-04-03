<?php
// --- delete.php ---
require 'config.php'; // Use require

$id = $_GET['id'] ?? null;

if (!$id) {
     $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Mã sinh viên không hợp lệ để xóa.'];
    header("Location: index.php");
    exit();
}

// --- Get the image filename BEFORE deleting the record ---
$hinh = '';
$stmt_select = $conn->prepare("SELECT Hinh FROM SinhVien WHERE MaSV = ?");
if($stmt_select){
    $stmt_select->bind_param("s", $id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    if($row = $result_select->fetch_assoc()){
        $hinh = $row['Hinh'];
    }
    $stmt_select->close();
} // No need to die if select fails, delete might still work, but file won't be removed

// --- Delete the record ---
$stmt_delete = $conn->prepare("DELETE FROM SinhVien WHERE MaSV = ?");
if (!$stmt_delete) {
     // Log: error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
     $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lỗi chuẩn bị truy vấn xóa.'];
} else {
    $stmt_delete->bind_param("s", $id);

    if ($stmt_delete->execute()) {
        // Check if a row was actually deleted
        if ($stmt_delete->affected_rows > 0) {
             $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Xóa sinh viên thành công!'];
            // Delete the associated image file if it exists and filename was retrieved
            if (!empty($hinh) && file_exists(UPLOAD_DIR . $hinh)) {
                @unlink(UPLOAD_DIR . $hinh); // Suppress error if file removal fails
            }
        } else {
             $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Không tìm thấy sinh viên để xóa (ID: '.htmlspecialchars($id).') hoặc đã được xóa.'];
        }
    } else {
        // Log: error_log("SQL Delete Error: [" . $stmt_delete->errno . "] " . $stmt_delete->error);
         $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lỗi cơ sở dữ liệu khi xóa sinh viên.'];
    }
    $stmt_delete->close();
}

$conn->close();

header("Location: index.php");
exit();
?>