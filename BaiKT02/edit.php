<?php
// --- edit.php ---
require 'config.php';

$id = $_GET['id'] ?? null;
$student = null;
$errors = [];
$old_image = '';

if (!$id) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Mã sinh viên không hợp lệ.'];
    header("Location: index.php");
    exit();
}

// --- Fetch existing student data ---
$stmt_select = $conn->prepare("SELECT MaSV, HoTen, GioiTinh, NgaySinh, Hinh, MaNganh FROM SinhVien WHERE MaSV = ?");
if (!$stmt_select) {
     // Log: error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
     $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lỗi truy vấn cơ sở dữ liệu.'];
     header("Location: index.php");
     exit();
}
$stmt_select->bind_param("s", $id);
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result->num_rows === 1) {
    $student = $result->fetch_assoc();
    $old_image = $student['Hinh']; // Store the old image filename
} else {
    $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Không tìm thấy sinh viên với mã cung cấp.'];
    header("Location: index.php");
    exit();
}
$stmt_select->close();


// --- Handle Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get submitted data (use existing student data as default)
    $hoTen = trim($_POST['HoTen'] ?? $student['HoTen']);
    $gioiTinh = trim($_POST['GioiTinh'] ?? $student['GioiTinh']);
    $ngaySinh = trim($_POST['NgaySinh'] ?? $student['NgaySinh']);
    $maNganh = trim($_POST['MaNganh'] ?? $student['MaNganh']);
    $hinh = $old_image; // Start assuming we keep the old image

    // Validation
    if (empty($hoTen)) $errors['HoTen'] = "Họ Tên là bắt buộc.";
    if (empty($gioiTinh)) $errors['GioiTinh'] = "Giới Tính là bắt buộc.";
    if (empty($ngaySinh)) $errors['NgaySinh'] = "Ngày Sinh là bắt buộc.";
    if (empty($maNganh)) $errors['MaNganh'] = "Mã Ngành là bắt buộc.";

    // --- File Upload Handling (only if a new file is uploaded) ---
    if (isset($_FILES['Hinh']) && $_FILES['Hinh']['error'] == UPLOAD_ERR_OK) {
         $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
         $file_info = finfo_open(FILEINFO_MIME_TYPE);
         $file_type = finfo_file($file_info, $_FILES['Hinh']['tmp_name']);
         finfo_close($file_info);
         $file_size = $_FILES['Hinh']['size'];
         $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($file_type, $allowed_types)) {
            $errors['Hinh'] = "Chỉ chấp nhận file JPG, PNG, GIF.";
        } elseif ($file_size > $max_size) {
            $errors['Hinh'] = "Kích thước file không được vượt quá 2MB.";
        } else {
            // Generate unique filename
            $file_extension = strtolower(pathinfo($_FILES['Hinh']['name'], PATHINFO_EXTENSION));
            $new_hinh_filename = 'sv_' . $id . '_' . uniqid() . '.' . $file_extension;
            $upload_path = UPLOAD_DIR . $new_hinh_filename;

            if (move_uploaded_file($_FILES['Hinh']['tmp_name'], $upload_path)) {
                 // Delete the old image *after* successful upload
                 if (!empty($old_image) && $old_image != $new_hinh_filename && file_exists(UPLOAD_DIR . $old_image)) {
                    @unlink(UPLOAD_DIR . $old_image); // Use @ to suppress errors if file doesn't exist
                 }
                 $hinh = $new_hinh_filename; // Set filename for DB update
            } else {
                $errors['Hinh'] = "Lỗi khi tải lên hình ảnh mới.";
                // Log: error_log("File upload failed for: " . $upload_path);
                $hinh = $old_image; // Keep old image if upload failed
            }
        }
    } elseif (isset($_FILES['Hinh']) && $_FILES['Hinh']['error'] != UPLOAD_ERR_NO_FILE) {
        $errors['Hinh'] = "Có lỗi xảy ra trong quá trình tải file (Mã lỗi: " . $_FILES['Hinh']['error'] . ").";
    }
    // else: No new file uploaded, $hinh remains $old_image

    // --- Update Database if no validation errors ---
    if (empty($errors)) {
        $stmt_update = $conn->prepare("UPDATE SinhVien SET HoTen=?, GioiTinh=?, NgaySinh=?, Hinh=?, MaNganh=? WHERE MaSV=?");
        if ($stmt_update) {
             $stmt_update->bind_param("ssssss", $hoTen, $gioiTinh, $ngaySinh, $hinh, $maNganh, $id);

             if ($stmt_update->execute()) {
                  $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Cập nhật thông tin sinh viên thành công!'];
                 header("Location: index.php");
                 exit();
             } else {
                 // Log: error_log("SQL Update Error: [" . $stmt_update->errno . "] " . $stmt_update->error);
                  $errors['db'] = "Lỗi cơ sở dữ liệu khi cập nhật sinh viên.";
                  // If update fails and a *new* image was uploaded, delete the new image
                  if ($hinh !== $old_image && file_exists(UPLOAD_DIR . $hinh)) {
                      @unlink(UPLOAD_DIR . $hinh);
                  }
             }
             $stmt_update->close();
        } else {
              // Log: error_log("SQL Prepare Error: [" . $conn->errno . "] " . $conn->error);
              $errors['db'] = "Lỗi chuẩn bị truy vấn cập nhật.";
        }
    }

    // IMPORTANT: If validation or DB update failed, update the $student array
    // with the *submitted* data so the form shows the latest attempt.
    $student['HoTen'] = $hoTen;
    $student['GioiTinh'] = $gioiTinh;
    $student['NgaySinh'] = $ngaySinh;
    $student['MaNganh'] = $maNganh;
    // Note: $student['Hinh'] still holds the *original* image path for display
    // $hinh holds the filename intended for the database (which might be new or old)
}

$page_title = "Chỉnh Sửa Sinh Viên";
require 'header.php';
?>

<div class="form-container">
    <h2 class="text-center mb-4">HIỆU CHỈNH THÔNG TIN SINH VIÊN</h2>

     <?php if (!empty($errors['db'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errors['db']); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" novalidate>
        <div class="mb-3">
            <label for="MaSV" class="form-label">Mã Sinh Viên:</label>
            <input type="text" class="form-control" id="MaSV" value="<?php echo htmlspecialchars($student['MaSV']); ?>" readonly disabled>
            <!-- We don't need to submit MaSV, it's in the URL ($id) -->
        </div>

        <div class="mb-3">
            <label for="HoTen" class="form-label">Họ Tên <span class="text-danger">*</span></label>
            <input type="text" class="form-control <?php echo isset($errors['HoTen']) ? 'is-invalid' : ''; ?>"
                   id="HoTen" name="HoTen" value="<?php echo htmlspecialchars($student['HoTen']); ?>" required>
             <?php if (isset($errors['HoTen'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['HoTen']); ?></div>
            <?php endif; ?>
        </div>

         <div class="mb-3">
            <label for="GioiTinh" class="form-label">Giới Tính <span class="text-danger">*</span></label>
            <select class="form-select <?php echo isset($errors['GioiTinh']) ? 'is-invalid' : ''; ?>"
                    id="GioiTinh" name="GioiTinh" required>
                <option value="" disabled>-- Chọn giới tính --</option>
                <option value="Nam" <?php echo ($student['GioiTinh'] == 'Nam') ? 'selected' : ''; ?>>Nam</option>
                <option value="Nữ" <?php echo ($student['GioiTinh'] == 'Nữ') ? 'selected' : ''; ?>>Nữ</option>
                 <option value="Khác" <?php echo ($student['GioiTinh'] == 'Khác') ? 'selected' : ''; ?>>Khác</option>
            </select>
             <?php if (isset($errors['GioiTinh'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['GioiTinh']); ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="NgaySinh" class="form-label">Ngày Sinh <span class="text-danger">*</span></label>
            <input type="date" class="form-control <?php echo isset($errors['NgaySinh']) ? 'is-invalid' : ''; ?>"
                   id="NgaySinh" name="NgaySinh" value="<?php echo htmlspecialchars($student['NgaySinh']); ?>" required>
             <?php if (isset($errors['NgaySinh'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['NgaySinh']); ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label class="form-label">Hình Ảnh Hiện Tại:</label>
            <div>
            <?php if (!empty($old_image) && file_exists(UPLOAD_DIR . $old_image)): ?>
                <img src="<?php echo UPLOAD_DIR . htmlspecialchars($old_image); ?>"
                     alt="Ảnh hiện tại của <?php echo htmlspecialchars($student['HoTen']); ?>"
                     class="img-thumbnail mb-2" style="max-height: 100px; width: auto;">
            <?php else: ?>
                <p class="text-muted fst-italic">Không có ảnh.</p>
            <?php endif; ?>
            </div>
            <label for="Hinh" class="form-label">Tải Lên Ảnh Mới (Để trống nếu không muốn thay đổi):</label>
            <input type="file" class="form-control <?php echo isset($errors['Hinh']) ? 'is-invalid' : ''; ?>"
                   id="Hinh" name="Hinh" accept="image/png, image/jpeg, image/gif">
             <small class="form-text text-muted">Định dạng JPG, PNG, GIF. Tối đa 2MB.</small>
             <?php if (isset($errors['Hinh'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['Hinh']); ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="MaNganh" class="form-label">Mã Ngành <span class="text-danger">*</span></label>
            <input type="text" class="form-control <?php echo isset($errors['MaNganh']) ? 'is-invalid' : ''; ?>"
                   id="MaNganh" name="MaNganh" value="<?php echo htmlspecialchars($student['MaNganh']); ?>" required>
             <?php if (isset($errors['MaNganh'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['MaNganh']); ?></div>
            <?php endif; ?>
        </div>

        <hr>
        <div class="d-flex justify-content-end gap-2">
             <a href="index.php" class="btn btn-secondary">
                 <i class="fas fa-arrow-left me-1"></i> Hủy
             </a>
             <button type="submit" class="btn btn-primary">
                 <i class="fas fa-save me-1"></i> Lưu Thay Đổi
             </button>
        </div>
    </form>
</div>

<?php
$conn->close();
require 'footer.php';
?>