<?php
require 'config.php'; // Connects, starts session, defines helpers

// --- Authentication Check ---
require_login();
// --- End Authentication Check ---

// --- Fetch Logged-in Student Info ---
$student_info = null;
$current_masv = $_SESSION['username'] ?? null; // Assuming username IS MaSV

if (!$current_masv) {
    // This should ideally be caught by require_login if username setup is correct
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Không tìm thấy Mã Sinh Viên. Vui lòng đăng nhập lại.'];
    header('Location: login.php');
    exit();
}

// Fetch student details including NganhHoc name using JOIN
$sql_sv = "SELECT sv.MaSV, sv.HoTen, sv.GioiTinh, sv.NgaySinh, sv.MaNganh, nh.TenNganh
           FROM SinhVien sv
           LEFT JOIN NganhHoc nh ON sv.MaNganh = nh.MaNganh
           WHERE sv.MaSV = ?";
$stmt_sv = $conn->prepare($sql_sv);

if (!$stmt_sv) {
    error_log("Prepare failed for student info query: " . $conn->error);
    echo "<div class='alert alert-danger'>Lỗi hệ thống khi truy vấn thông tin sinh viên.</div>";
    // Consider exiting or preventing confirmation if student info fails
} else {
    $stmt_sv->bind_param("s", $current_masv);
    if ($stmt_sv->execute()) {
        $result_sv = $stmt_sv->get_result();
        if ($result_sv->num_rows === 1) {
            $student_info = $result_sv->fetch_assoc();
        } else {
            // User's MaSV from session doesn't exist in SinhVien table! Critical error.
            error_log("Critical: MaSV '{$current_masv}' from session not found in SinhVien table.");
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Thông tin sinh viên không hợp lệ. Vui lòng liên hệ quản trị viên.'];
            // Optional: Log out the user
            // session_destroy();
            // header('Location: login.php');
            // exit();
        }
        $result_sv->free();
    } else {
        error_log("Execute failed for student info query: " . $stmt_sv->error);
        echo "<div class='alert alert-danger'>Lỗi hệ thống khi truy vấn thông tin sinh viên (execute).</div>";
    }
    $stmt_sv->close();
}
// --- End Fetch Student Info ---

$page_title = "Xác Nhận Đăng Ký Học Phần";
require 'header.php'; // Includes display_flash_message()

$selected_courses_details = [];
$total_credits = 0;
$total_courses = 0;
$mahp_list_from_session = [];

// --- Process Session Cart ---
if (isset($_SESSION['dangky']) && is_array($_SESSION['dangky']) && !empty($_SESSION['dangky'])) {
    // Get the MaHP keys from the session array
    $mahp_list_from_session = array_keys($_SESSION['dangky']);
    $total_courses = count($mahp_list_from_session);

    // Prepare placeholders for the IN clause (e.g., ?,?,?)
    $placeholders = implode(',', array_fill(0, $total_courses, '?'));
    // Prepare type string for bind_param (e.g., 'sss')
    $types = str_repeat('s', $total_courses);

    // Fetch details for the selected courses from HocPhan table
    $sql_hp = "SELECT MaHP, TenHP, SoTinChi FROM HocPhan WHERE MaHP IN ($placeholders)";
    $stmt_hp = $conn->prepare($sql_hp);

    if (!$stmt_hp) {
         error_log("Prepare failed for course details query: " . $conn->error);
         echo "<div class='alert alert-danger'>Lỗi hệ thống khi lấy chi tiết học phần.</div>";
    } else {
        // Use argument unpacking (...) to bind the array of MaHPs
        $stmt_hp->bind_param($types, ...$mahp_list_from_session);

        if ($stmt_hp->execute()) {
             $result_hp = $stmt_hp->get_result();
             while ($row = $result_hp->fetch_assoc()) {
                 // Store details using MaHP as key for easy lookup
                 $selected_courses_details[$row['MaHP']] = $row;
                 // Add credits, ensuring it's treated as a number
                 $total_credits += (int)$row['SoTinChi'];
             }
             $result_hp->free();
        } else {
            error_log("Execute failed for course details query: " . $stmt_hp->error);
            echo "<div class='alert alert-danger'>Lỗi hệ thống khi lấy chi tiết học phần (execute).</div>";
        }
        $stmt_hp->close();
    }
}
// --- End Process Session Cart ---

?>

<h3 class="text-dark mb-4"><?php echo htmlspecialchars($page_title); ?></h3>

<!-- Display Selected Courses -->
<div class="card shadow-sm mb-4">
     <div class="card-header bg-light fw-bold">Danh sách học phần chờ đăng ký</div>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th scope="col">Mã HP</th>
                    <th scope="col">Tên Học Phần</th>
                    <th scope="col" class="text-center">Số Tín Chỉ</th>
                    <th scope="col" class="text-center">Hủy Chọn</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_courses > 0 && !empty($selected_courses_details)): ?>
                    <?php foreach ($mahp_list_from_session as $mahp): ?>
                        <?php if (isset($selected_courses_details[$mahp])):
                            $course = $selected_courses_details[$mahp];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['MaHP']); ?></td>
                            <td><?php echo htmlspecialchars($course['TenHP']); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($course['SoTinChi']); ?></td>
                            <td class="text-center">
                                <a href="dangky_xoa.php?id=<?php echo urlencode($course['MaHP']); ?>" class="btn btn-sm btn-outline-danger" title="Xóa học phần này khỏi danh sách chờ">
                                   <i class="fas fa-trash-alt"></i><span class="visually-hidden">Xóa</span>
                                </a>
                            </td>
                        </tr>
                        <?php else: ?>
                         <!-- This row indicates data inconsistency (in session but not in DB) -->
                         <tr>
                             <td class="text-danger"><?php echo htmlspecialchars($mahp); ?></td>
                             <td colspan="3" class="text-danger fst-italic">Lỗi: Học phần này có trong danh sách chờ nhưng không tìm thấy thông tin chi tiết.</td>
                         </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                     <tr class="table-light fw-bold">
                         <td colspan="2">Tổng cộng: <?php echo $total_courses; ?> học phần</td>
                         <td class="text-center"><?php echo $total_credits; ?> tín chỉ</td>
                         <td class="text-center">
                              <a href="dangky_xoahet.php" class="btn btn-sm btn-warning" title="Xóa hết danh sách chờ" onclick="return confirm('Bạn có chắc chắn muốn xóa TẤT CẢ học phần đã chọn khỏi danh sách chờ đăng ký?');">
                                   <i class="fas fa-times-circle me-1"></i> Xóa hết
                              </a>
                         </td>
                     </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted p-4">
                            <i>Chưa có học phần nào được chọn.</i><br>
                            <a href="hocphan.php" class="btn btn-sm btn-outline-primary mt-2">
                               <i class="fas fa-book me-1"></i> Xem danh sách học phần
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Display Student Info and Confirmation Buttons -->
<?php if ($total_courses > 0 && $student_info): // Only show if courses are selected AND student info is available ?>
<hr>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white fw-bold">Xác nhận Thông tin Đăng ký</div>
    <div class="card-body">
        <h5 class="card-title mb-3">Thông tin Sinh viên</h5>
        <dl class="row">
            <dt class="col-sm-3">Mã Sinh Viên:</dt><dd class="col-sm-9"><?php echo htmlspecialchars($student_info['MaSV']); ?></dd>
            <dt class="col-sm-3">Họ Tên:</dt><dd class="col-sm-9 fw-bold"><?php echo htmlspecialchars($student_info['HoTen']); ?></dd>
            <dt class="col-sm-3">Ngày Sinh:</dt><dd class="col-sm-9"><?php echo htmlspecialchars(date("d/m/Y", strtotime($student_info['NgaySinh']))); ?></dd>
            <dt class="col-sm-3">Giới Tính:</dt><dd class="col-sm-9"><?php echo htmlspecialchars($student_info['GioiTinh']); ?></dd>
            <dt class="col-sm-3">Ngành Học:</dt><dd class="col-sm-9"><?php echo htmlspecialchars($student_info['TenNganh'] ?? $student_info['MaNganh']); // Show TenNganh if available ?></dd>
            <dt class="col-sm-3">Ngày Đăng Ký:</dt><dd class="col-sm-9"><?php echo date("d/m/Y H:i"); ?> (Thời điểm xác nhận)</dd>
        </dl>
        <hr>
        <div class="text-center mt-3">
            <p class="mb-3">Vui lòng kiểm tra kỹ thông tin và danh sách học phần trước khi xác nhận đăng ký chính thức.</p>
             <!-- Form to submit the registration -->
            <form action="dangky_luu.php" method="POST" onsubmit="return confirm('Xác nhận đăng ký chính thức các học phần đã chọn? Hành động này không thể hoàn tác.');" style="display: inline-block;">
                 <?php /* Basic CSRF token - recommended for production
                 if (empty($_SESSION['csrf_token'])) {
                     $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                 }
                 ?>
                 <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                 */ ?>
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-check-circle me-2"></i> Lưu Đăng Ký Chính Thức
                </button>
            </form>
             <a href="hocphan.php" class="btn btn-secondary ms-2">
                 <i class="fas fa-arrow-left me-1"></i> Quay lại chọn thêm
             </a>
        </div>
    </div>
</div>
<?php elseif ($total_courses > 0 && !$student_info): ?>
    <div class="alert alert-danger">Không thể hiển thị thông tin sinh viên. Không thể xác nhận đăng ký. Vui lòng liên hệ quản trị viên.</div>
<?php endif; ?>
<!-- End Confirmation Section -->

<?php
$conn->close();
require 'footer.php';
?>