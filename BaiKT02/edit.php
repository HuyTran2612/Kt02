<?php
require 'config.php'; // Connects, starts session, defines helpers, defines UPLOAD_DIR/BASE_URL

// --- Authentication & Authorization (Example) ---
require_login();
// Optional: Add role check if only admins can edit
// if ($_SESSION['role'] !== 'admin') {
//    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Bạn không có quyền thực hiện hành động này.'];
//    header('Location: index.php'); exit();
// }
// --- End Auth ---


$masv_to_edit = $_GET['id'] ?? null;
$student = null;
$errors = [];
$old_image_filename = ''; // Store the filename currently in DB
$nganhHocList = []; // To store list of majors for dropdown

// --- Validate Input ID ---
if (empty($masv_to_edit)) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Mã sinh viên không được cung cấp hoặc không hợp lệ.'];
    header("Location: index.php"); // Redirect to a safe page
    exit();
}

// --- Fetch existing student data ---
$stmt_select = $conn->prepare("SELECT MaSV, HoTen, GioiTinh, NgaySinh, Hinh, MaNganh FROM SinhVien WHERE MaSV = ?");
if (!$stmt_select) {
     error_log("Edit Student - Prepare failed for SELECT: (" . $conn->errno . ") " . $conn->error);
     $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lỗi hệ thống khi truy vấn dữ liệu sinh viên.'];
     header("Location: index.php");
     exit();
}

$stmt_select->bind_param("s", $masv_to_edit);
if (!$stmt_select->execute()) {
    error_log("Edit Student - Execute failed for SELECT: (" . $stmt_select->errno . ") " . $stmt_select->error);
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lỗi hệ thống khi truy vấn dữ liệu sinh viên (execute).'];
    $stmt_select->close();
    header("Location: index.php");
    exit();
}

$result = $stmt_select->get_result();
if ($result->num_rows === 1) {
    $student = $result->fetch_assoc();
    $old_image_filename = $student['Hinh']; // Store the existing image filename
} else {
    // Student with the given MaSV was not found
    $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Không tìm thấy sinh viên với mã số ' . htmlspecialchars($masv_to_edit) . '.'];
    $stmt_select->close();
    header("Location: index.php");
    exit();
}
$stmt_select->close();
// --- End Fetch Student Data ---


// --- Fetch NganhHoc list for dropdown ---
$result_nganh = $conn->query("SELECT MaNganh, TenNganh FROM NganhHoc ORDER BY TenNganh");
if ($result_nganh) {
    while ($row_nganh = $result_nganh->fetch_assoc()) {
        $nganhHocList[] = $row_nganh;
    }
    $result_nganh->free();
} else {
    error_log("Edit Student - Failed to fetch NganhHoc list: (" . $conn->errno . ") " . $conn->error);
    // Decide if this is fatal. Maybe allow editing without dropdown?
    $errors['nganh_fetch'] = "Không thể tải danh sách ngành học.";
}
// --- End Fetch NganhHoc List ---


// --- Handle Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- CSRF Protection (Basic Example - Implement fully if needed) ---
    // if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    //     $errors['csrf'] = "Lỗi bảo mật hoặc phiên làm việc đã hết hạn. Vui lòng thử lại.";
    // }
    // --- End CSRF ---


    // Get submitted data (use existing student data as default for repopulation)
    // Trim whitespace from text inputs
    $hoTen = trim($_POST['HoTen'] ?? $student['HoTen']);
    $gioiTinh = $_POST['GioiTinh'] ?? $student['GioiTinh']; // No trim needed for select
    $ngaySinh = $_POST['NgaySinh'] ?? $student['NgaySinh'];   // No trim needed for date
    $maNganh = $_POST['MaNganh'] ?? $student['MaNganh'];     // No trim needed for select
    $hinh_db_value = $old_image_filename; // Assume we keep the old image unless upload succeeds

    // --- Validation ---
    if (empty($hoTen)) $errors['HoTen'] = "Họ Tên là bắt buộc.";
    if (empty($gioiTinh)) $errors['GioiTinh'] = "Vui lòng chọn Giới Tính.";
    if (empty($ngaySinh)) {
        $errors['NgaySinh'] = "Ngày Sinh là bắt buộc.";
    } elseif (strtotime($ngaySinh) === false) {
        $errors['NgaySinh'] = "Ngày Sinh không hợp lệ.";
    }
    if (empty($maNganh)) $errors['MaNganh'] = "Vui lòng chọn Ngành Học.";
    // Optional: Validate if the selected MaNganh actually exists in NganhHoc table
    $valid_nganh = false;
    foreach ($nganhHocList as $nganh) { if ($nganh['MaNganh'] == $maNganh) { $valid_nganh = true; break; } }
    if (!$valid_nganh && !empty($maNganh)) $errors['MaNganh'] = "Ngành Học được chọn không hợp lệ.";


    // --- File Upload Handling (Only if a new file is submitted) ---
    $new_image_uploaded = false;
    $new_hinh_filename_temp = ''; // Temporary holder for new filename

    if (isset($_FILES['Hinh']) && $_FILES['Hinh']['error'] === UPLOAD_ERR_OK) {
         $file_tmp_name = $_FILES['Hinh']['tmp_name'];
         $file_size = $_FILES['Hinh']['size'];
         $file_error = $_FILES['Hinh']['error'];
         $original_filename = $_FILES['Hinh']['name'];

         // Define upload constraints
         $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
         $max_size_bytes = 2 * 1024 * 1024; // 2MB

         // Get file extension
         $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));

         // Validate extension
         if (!in_array($file_extension, $allowed_extensions)) {
             $errors['Hinh'] = "Định dạng file không hợp lệ. Chỉ chấp nhận JPG, PNG, GIF.";
         }
         // Validate size
         elseif ($file_size > $max_size_bytes) {
             $errors['Hinh'] = "Kích thước file quá lớn. Tối đa là 2MB.";
         }
         // More robust MIME type check
         else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_tmp_name);
            finfo_close($finfo);
            $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($mime_type, $allowed_mime_types)) {
                $errors['Hinh'] = "Loại file không hợp lệ (dựa trên nội dung).";
            }
         }

         // If validation passes so far for the file
         if (!isset($errors['Hinh'])) {
             // Generate a unique filename to prevent overwrites and issues with special chars
             // Format: sv_MASV_timestamp.extension
             $new_hinh_filename_temp = 'sv_' . preg_replace('/[^a-zA-Z0-9]/', '_', $masv_to_edit) . '_' . time() . '.' . $file_extension;
             $upload_target_path = UPLOAD_DIR . $new_hinh_filename_temp;

             // Check if UPLOAD_DIR is writable (optional but good)
             if (!is_dir(UPLOAD_DIR) || !is_writable(UPLOAD_DIR)) {
                  $errors['Hinh'] = "Lỗi hệ thống: Thư mục tải lên không tồn tại hoặc không có quyền ghi.";
                  error_log("Upload directory error: " . UPLOAD_DIR . " does not exist or is not writable.");
             } else {
                 // Attempt to move the uploaded file
                 if (move_uploaded_file($file_tmp_name, $upload_target_path)) {
                      $new_image_uploaded = true; // Mark that upload succeeded
                      $hinh_db_value = $new_hinh_filename_temp; // Set the filename to be saved in DB
                 } else {
                     $errors['Hinh'] = "Đã xảy ra lỗi khi lưu file ảnh tải lên.";
                     error_log("Failed to move uploaded file from $file_tmp_name to $upload_target_path");
                 }
             }
         }
    }
    // Handle other upload errors (e.g., file too large based on php.ini, partial upload)
    elseif (isset($_FILES['Hinh']) && $_FILES['Hinh']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Map error codes to user-friendly messages
        switch ($_FILES['Hinh']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors['Hinh'] = "Kích thước file quá lớn."; break;
            case UPLOAD_ERR_PARTIAL:
                $errors['Hinh'] = "File chỉ được tải lên một phần."; break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errors['Hinh'] = "Lỗi máy chủ: Thiếu thư mục tạm."; break;
            case UPLOAD_ERR_CANT_WRITE:
                $errors['Hinh'] = "Lỗi máy chủ: Không thể ghi file."; break;
            case UPLOAD_ERR_EXTENSION:
                $errors['Hinh'] = "Lỗi máy chủ: Extension PHP đã chặn việc tải file."; break;
            default:
                $errors['Hinh'] = "Đã xảy ra lỗi không xác định khi tải file lên (Mã: " . $_FILES['Hinh']['error'] . ")."; break;
        }
        error_log("File upload error code: " . $_FILES['Hinh']['error'] . " for MaSV: " . $masv_to_edit);
    }
    // --- End File Upload Handling ---


    // --- Update Database if no validation errors ---
    if (empty($errors)) {
        $stmt_update = $conn->prepare("UPDATE SinhVien SET HoTen=?, GioiTinh=?, NgaySinh=?, Hinh=?, MaNganh=? WHERE MaSV=?");
        if (!$stmt_update) {
              error_log("Edit Student - SQL Prepare Error for UPDATE: [" . $conn->errno . "] " . $conn->error);
              $errors['db'] = "Lỗi hệ thống khi chuẩn bị cập nhật.";
        } else {
             // Bind parameters: s (HoTen), s (GioiTinh), s (NgaySinh DATE), s (Hinh filename), s (MaNganh), s (MaSV WHERE)
             $stmt_update->bind_param("ssssss", $hoTen, $gioiTinh, $ngaySinh, $hinh_db_value, $maNganh, $masv_to_edit);

             if ($stmt_update->execute()) {
                  // SUCCESS!
                  // Delete the old image ONLY if a new image was successfully uploaded AND it's different from old one
                  if ($new_image_uploaded && !empty($old_image_filename) && $old_image_filename !== $hinh_db_value) {
                      $old_image_path = UPLOAD_DIR . $old_image_filename;
                      if (file_exists($old_image_path)) {
                          if (!@unlink($old_image_path)) {
                              // Log error if deletion failed, but don't stop the success flow
                              error_log("Edit Student - Failed to delete old image: " . $old_image_path);
                          }
                      }
                  }

                  $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Cập nhật thông tin sinh viên "' . htmlspecialchars($hoTen) . '" thành công!'];
                  // Regenerate CSRF token after successful action
                  // unset($_SESSION['csrf_token']);
                  header("Location: index.php"); // Redirect to prevent form resubmission
                  exit();

             } else {
                  // Database update failed
                  error_log("Edit Student - SQL Update Execute Error: [" . $stmt_update->errno . "] " . $stmt_update->error);
                  $errors['db'] = "Lỗi cơ sở dữ liệu khi cập nhật thông tin sinh viên.";

                  // IMPORTANT: If DB update fails, and we *did* upload a new image, we must delete the newly uploaded file
                  // to keep filesystem consistent with the database state.
                  if ($new_image_uploaded) {
                      $uploaded_file_path = UPLOAD_DIR . $hinh_db_value; // $hinh_db_value holds the new filename here
                      if (file_exists($uploaded_file_path)) {
                          @unlink($uploaded_file_path); // Attempt to delete the orphaned new file
                          error_log("Edit Student - Rolled back new image upload due to DB error: " . $uploaded_file_path);
                      }
                  }
             }
             $stmt_update->close();
        }
    } // End if(empty($errors))

    // --- Repopulate $student array with submitted data for form redisplay on error ---
    // This ensures the user sees their attempted values and the errors.
    $student['HoTen'] = $hoTen;       // Use the submitted value
    $student['GioiTinh'] = $gioiTinh;   // Use the submitted value
    $student['NgaySinh'] = $ngaySinh;   // Use the submitted value
    $student['MaNganh'] = $maNganh;     // Use the submitted value
    // $student['Hinh'] remains the *original* filename for displaying the current image.
    // $hinh_db_value contains the filename *intended* for the DB (might be new or old).

} // End if ($_SERVER["REQUEST_METHOD"] == "POST")

// --- Generate CSRF token for the form (Basic Example) ---
// if (empty($_SESSION['csrf_token'])) {
//    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
// }
// $csrf_token = $_SESSION['csrf_token'];
// --- End CSRF ---


// --- Prepare for Page Display ---
$page_title = "Chỉnh Sửa Thông Tin Sinh Viên";
require 'header.php'; // Include header (should display flash messages)
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card shadow-sm">
                 <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Hiệu Chỉnh Thông Tin Sinh Viên</h3>
                </div>
                <div class="card-body p-4">

                     <?php // Display general DB or CSRF errors at the top ?>
                     <?php if (!empty($errors['db'])): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($errors['db']); ?></div>
                    <?php endif; ?>
                     <?php if (!empty($errors['csrf'])): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($errors['csrf']); ?></div>
                    <?php endif; ?>
                     <?php if (!empty($errors['nganh_fetch'])): ?>
                        <div class="alert alert-warning"><?php echo htmlspecialchars($errors['nganh_fetch']); ?></div>
                    <?php endif; ?>


                    <form method="POST" action="edit.php?id=<?php echo htmlspecialchars($masv_to_edit); ?>" enctype="multipart/form-data" novalidate>
                         <?php /* CSRF Token Input
                         <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                         */ ?>

                        <div class="mb-3">
                            <label for="MaSVDisplay" class="form-label">Mã Sinh Viên:</label>
                            <input type="text" class="form-control" id="MaSVDisplay" value="<?php echo htmlspecialchars($student['MaSV']); ?>" readonly disabled>
                            <small class="form-text text-muted">Mã sinh viên không thể thay đổi.</small>
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
                                <option value="" <?php echo empty($student['GioiTinh']) ? 'selected' : ''; ?> disabled>-- Chọn giới tính --</option>
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
                                   id="NgaySinh" name="NgaySinh" value="<?php echo htmlspecialchars($student['NgaySinh']); ?>" required max="<?php echo date('Y-m-d'); // Prevent future dates ?>">
                             <?php if (isset($errors['NgaySinh'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['NgaySinh']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Hình Ảnh Hiện Tại:</label>
                            <div class="mb-2">
                                <?php
                                // Construct the URL path to the image
                                $image_path_for_display = '';
                                if (!empty($old_image_filename)) {
                                    // Check if UPLOAD_DIR is defined and the file exists
                                    if (defined('UPLOAD_DIR') && file_exists(UPLOAD_DIR . $old_image_filename)) {
                                         // Combine BASE_URL and filename for web access
                                         $image_path_for_display = (defined('BASE_URL') ? BASE_URL : '') . UPLOAD_DIR . rawurlencode($old_image_filename);
                                    } else {
                                         // Log if file is missing but filename exists in DB
                                         error_log("Image file not found: " . (defined('UPLOAD_DIR') ? UPLOAD_DIR : 'UNDEF_UPLOAD_DIR') . $old_image_filename . " for MaSV: " . $masv_to_edit);
                                    }
                                }
                                ?>
                                <?php if (!empty($image_path_for_display)): ?>
                                    <img src="<?php echo htmlspecialchars($image_path_for_display); ?>"
                                         alt="Ảnh SV <?php echo htmlspecialchars($student['HoTen']); ?>"
                                         class="img-thumbnail" style="max-height: 120px; width: auto; display: block;">
                                <?php else: ?>
                                    <p class="text-muted fst-italic">Không có ảnh.</p>
                                <?php endif; ?>
                            </div>
                            <label for="Hinh" class="form-label">Tải Lên Ảnh Mới (Tùy chọn):</label>
                            <input type="file" class="form-control <?php echo isset($errors['Hinh']) ? 'is-invalid' : ''; ?>"
                                   id="Hinh" name="Hinh" accept="image/png, image/jpeg, image/gif">
                             <small class="form-text text-muted">Để trống nếu không muốn thay đổi ảnh. Định dạng JPG, PNG, GIF. Tối đa 2MB.</small>
                             <?php if (isset($errors['Hinh'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['Hinh']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="MaNganh" class="form-label">Ngành Học <span class="text-danger">*</span></label>
                             <select class="form-select <?php echo isset($errors['MaNganh']) ? 'is-invalid' : ''; ?>"
                                    id="MaNganh" name="MaNganh" required>
                                <option value="" <?php echo empty($student['MaNganh']) ? 'selected' : ''; ?> disabled>-- Chọn Ngành Học --</option>
                                <?php if (!empty($nganhHocList)): ?>
                                    <?php foreach ($nganhHocList as $nganh): ?>
                                        <option value="<?php echo htmlspecialchars($nganh['MaNganh']); ?>"
                                                <?php echo ($student['MaNganh'] == $nganh['MaNganh']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($nganh['TenNganh']) . ' (' . htmlspecialchars($nganh['MaNganh']) . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Không tải được danh sách ngành</option>
                                <?php endif; ?>
                            </select>
                             <?php if (isset($errors['MaNganh'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['MaNganh']); ?></div>
                            <?php endif; ?>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                             <a href="index.php" class="btn btn-secondary">
                                 <i class="fas fa-times me-1"></i> Hủy Bỏ
                             </a>
                             <button type="submit" class="btn btn-primary">
                                 <i class="fas fa-save me-1"></i> Lưu Thay Đổi
                             </button>
                        </div>
                    </form>
                </div><!-- /card-body -->
            </div><!-- /card -->
        </div><!-- /col -->
    </div><!-- /row -->
</div><!-- /container -->

<?php
$conn->close(); // Close connection
require 'footer.php'; // Include footer
?>