<?php
// --- create.php ---
require 'config.php';

$errors = [];
$maSV = $hoTen = $gioiTinh = $ngaySinh = $maNganh = ''; // Initialize variables

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and Validate Inputs
    $maSV = trim($_POST['MaSV'] ?? '');
    $hoTen = trim($_POST['HoTen'] ?? '');
    $gioiTinh = trim($_POST['GioiTinh'] ?? '');
    $ngaySinh = trim($_POST['NgaySinh'] ?? '');
    $maNganh = trim($_POST['MaNganh'] ?? '');
    $hinh = ''; // Initialize image variable

    if (empty($maSV)) $errors['MaSV'] = "Mã Sinh Viên là bắt buộc.";
    // Optional: Check if MaSV already exists
    // $check_stmt = $conn->prepare("SELECT MaSV FROM SinhVien WHERE MaSV = ?");
    // $check_stmt->bind_param("s", $maSV);
    // $check_stmt->execute();
    // $check_result = $check_stmt->get_result();
    // if ($check_result->num_rows > 0) $errors['MaSV'] = "Mã Sinh Viên đã tồn tại.";
    // $check_stmt->close();

    if (empty($hoTen)) $errors['HoTen'] = "Họ Tên là bắt buộc.";
    if (empty($gioiTinh)) $errors['GioiTinh'] = "Giới Tính là bắt buộc.";
    if (empty($ngaySinh)) $errors['NgaySinh'] = "Ngày Sinh là bắt buộc.";
    // Optional: Validate date format more strictly if needed
    if (empty($maNganh)) $errors['MaNganh'] = "Mã Ngành là bắt buộc.";

    // --- File Upload Handling ---
    if (isset($_FILES['Hinh']) && $_FILES['Hinh']['error'] == UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_info = finfo_open(FILEINFO_MIME_TYPE); // Use finfo for reliable type check
        $file_type = finfo_file($file_info, $_FILES['Hinh']['tmp_name']);
        finfo_close($file_info);

        $file_size = $_FILES['Hinh']['size'];
        $max_size = 2 * 1024 * 1024; // 2MB limit

        if (!in_array($file_type, $allowed_types)) {
            $errors['Hinh'] = "Chỉ chấp nhận file định dạng JPG, PNG, GIF.";
        } elseif ($file_size > $max_size) {
            $errors['Hinh'] = "Kích thước file không được vượt quá 2MB.";
        } else {
            // Generate a unique filename
            $file_extension = strtolower(pathinfo($_FILES['Hinh']['name'], PATHINFO_EXTENSION));
            $hinh = 'sv_' . $maSV . '_' . uniqid() . '.' . $file_extension; // Include MaSV for easier identification
            $upload_path = UPLOAD_DIR . $hinh;

            if (!move_uploaded_file($_FILES['Hinh']['tmp_name'], $upload_path)) {
                $errors['Hinh'] = "Lỗi khi tải lên hình ảnh. Vui lòng thử lại.";
                // Log the specific error: error_log("File upload failed for: " . $upload_path);
                $hinh = ''; // Reset filename if upload failed
            }
        }
    } elseif (isset($_FILES['Hinh']) && $_FILES['Hinh']['error'] != UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors (e.g., partial upload, system errors)
        $errors['Hinh'] = "Có lỗi xảy ra trong quá trình tải file (Mã lỗi: " . $_FILES['Hinh']['error'] . ").";
    }
    // else: No file uploaded, which might be acceptable


    // --- Insert into Database if no errors ---
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO SinhVien (MaSV, HoTen, GioiTinh, NgaySinh, Hinh, MaNganh) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            // 'ssssss' means six string parameters
            $stmt->bind_param("ssssss", $maSV, $hoTen, $gioiTinh, $ngaySinh, $hinh, $maNganh);

            if ($stmt->execute()) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Thêm sinh viên thành công!'];
                header("Location: index.php");
                exit();
            } else {
                // Log detailed error: error_log("SQL Insert Error: [" . $stmt->errno . "] " . $stmt->error);
                $errors['db'] = "Lỗi cơ sở dữ liệu khi thêm sinh viên. Vui lòng thử lại.";
                 // If insert fails, delete the potentially uploaded file
                if (!empty($hinh) && file_exists(UPLOAD_DIR . $hinh)) {
                    unlink(UPLOAD_DIR . $hinh);
                }
            }
            $stmt->close();
        } else {
             // Log detailed error: error_log("SQL Prepare Error: [" . $conn->errno . "] " . $conn->error);
             $errors['db'] = "Lỗi chuẩn bị truy vấn cơ sở dữ liệu.";
        }
    }
    // If validation failed or DB error, the script continues to display the form with errors
}

$page_title = "Thêm Sinh Viên Mới";
require 'header.php';
?>

<div class="form-container">
    <h2 class="text-center mb-4">THÊM SINH VIÊN MỚI</h2>

    <?php if (!empty($errors['db'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errors['db']); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" novalidate>
        <div class="mb-3">
            <label for="MaSV" class="form-label">Mã Sinh Viên <span class="text-danger">*</span></label>
            <input type="text" class="form-control <?php echo isset($errors['MaSV']) ? 'is-invalid' : ''; ?>"
                   id="MaSV" name="MaSV" value="<?php echo htmlspecialchars($maSV); ?>" required>
            <?php if (isset($errors['MaSV'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['MaSV']); ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="HoTen" class="form-label">Họ Tên <span class="text-danger">*</span></label>
            <input type="text" class="form-control <?php echo isset($errors['HoTen']) ? 'is-invalid' : ''; ?>"
                   id="HoTen" name="HoTen" value="<?php echo htmlspecialchars($hoTen); ?>" required>
             <?php if (isset($errors['HoTen'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['HoTen']); ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="GioiTinh" class="form-label">Giới Tính <span class="text-danger">*</span></label>
            <select class="form-select <?php echo isset($errors['GioiTinh']) ? 'is-invalid' : ''; ?>"
                    id="GioiTinh" name="GioiTinh" required>
                <option value="" disabled <?php echo empty($gioiTinh) ? 'selected' : ''; ?>>-- Chọn giới tính --</option>
                <option value="Nam" <?php echo ($gioiTinh == 'Nam') ? 'selected' : ''; ?>>Nam</option>
                <option value="Nữ" <?php echo ($gioiTinh == 'Nữ') ? 'selected' : ''; ?>>Nữ</option>
                <option value="Khác" <?php echo ($gioiTinh == 'Khác') ? 'selected' : ''; ?>>Khác</option>
            </select>
             <?php if (isset($errors['GioiTinh'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['GioiTinh']); ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="NgaySinh" class="form-label">Ngày Sinh <span class="text-danger">*</span></label>
            <input type="date" class="form-control <?php echo isset($errors['NgaySinh']) ? 'is-invalid' : ''; ?>"
                   id="NgaySinh" name="NgaySinh" value="<?php echo htmlspecialchars($ngaySinh); ?>" required>
             <?php if (isset($errors['NgaySinh'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['NgaySinh']); ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="Hinh" class="form-label">Hình Ảnh</label>
            <input type="file" class="form-control <?php echo isset($errors['Hinh']) ? 'is-invalid' : ''; ?>"
                   id="Hinh" name="Hinh" accept="image/png, image/jpeg, image/gif">
            <small class="form-text text-muted">Định dạng JPG, PNG, GIF. Tối đa 2MB.</small>
             <?php if (isset($errors['Hinh'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['Hinh']); ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="MaNganh" class="form-label">Mã Ngành <span class="text-danger">*</span></label>
            <!-- Consider using a dropdown (<select>) if you have a Nganh table -->
            <input type="text" class="form-control <?php echo isset($errors['MaNganh']) ? 'is-invalid' : ''; ?>"
                   id="MaNganh" name="MaNganh" value="<?php echo htmlspecialchars($maNganh); ?>" required>
             <?php if (isset($errors['MaNganh'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['MaNganh']); ?></div>
            <?php endif; ?>
        </div>

        <hr>
        <div class="d-flex justify-content-end gap-2">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-times me-1"></i> Hủy
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Thêm Sinh Viên
             </button>
        </div>
    </form>
</div>

<?php
$conn->close();
require 'footer.php';
?>