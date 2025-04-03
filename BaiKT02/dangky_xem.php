<?php
// --- dangky_xem.php ---
require 'config.php';

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Vui lòng đăng nhập để xem danh sách đăng ký.'];
    header('Location: login.php');
    exit();
}
// --- End Authentication Check ---

// --- Fetch Logged-in Student Info ---
$student_info = null;
$current_masv = $_SESSION['username'] ?? null; // Assuming username IS MaSV

if ($current_masv) {
    $stmt_sv = $conn->prepare("SELECT MaSV, HoTen, NgaySinh, MaNganh FROM SinhVien WHERE MaSV = ?");
    if ($stmt_sv) {
        $stmt_sv->bind_param("s", $current_masv);
        $stmt_sv->execute();
        $result_sv = $stmt_sv->get_result();
        if ($result_sv->num_rows === 1) {
            $student_info = $result_sv->fetch_assoc();
        }
        $stmt_sv->close();
    } else {
         echo "<div class='alert alert-danger'>Lỗi khi chuẩn bị truy vấn thông tin sinh viên.</div>";
    }
} else {
     echo "<div class='alert alert-warning'>Không tìm thấy thông tin Mã Sinh Viên trong phiên làm việc. Không thể lưu đăng ký.</div>";
}


$page_title = "Đăng Kí Học Phần";
require 'header.php';

$registered_courses = [];
$total_credits = 0;
$total_courses = 0;
$mahp_list = [];

if (isset($_SESSION['dangky']) && !empty($_SESSION['dangky'])) {
    $mahp_list = array_keys($_SESSION['dangky']);
    $total_courses = count($mahp_list);

    $placeholders = implode(',', array_fill(0, $total_courses, '?'));
    $types = str_repeat('s', $total_courses);

    // === THAY ĐỔI Ở ĐÂY ===
    $sql = "SELECT MaHP, TenHP, SoChiChi FROM hocphan WHERE MaHP IN ($placeholders)";
    $stmt_hp = $conn->prepare($sql);

    if ($stmt_hp) {
        $stmt_hp->bind_param($types, ...$mahp_list);
        $stmt_hp->execute();
        $result_hp = $stmt_hp->get_result();

        while ($row = $result_hp->fetch_assoc()) {
            $registered_courses[$row['MaHP']] = $row;
            $total_credits += $row['SoChiChi'];
        }
        $stmt_hp->close();
    } else {
         echo "<div class='alert alert-danger'>Lỗi chuẩn bị truy vấn lấy chi tiết học phần.</div>";
    }
}

?>

<h3 class="text-dark mb-4"><?php echo htmlspecialchars($page_title); ?></h3>

<div class="card shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr class="table-light">
                    <th scope="col">MãHP</th>
                    <th scope="col">Tên Học Phần</th> <!-- Giữ nguyên tiêu đề bảng -->
                    <th scope="col" class="text-end">Số Chỉ Chi</th>
                    <th scope="col" class="text-center">Xóa</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($registered_courses)): ?>
                    <?php foreach ($mahp_list as $mahp): ?>
                        <?php if (isset($registered_courses[$mahp])):
                            $course = $registered_courses[$mahp];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['MaHP']); ?></td>
                            <!-- === THAY ĐỔI Ở ĐÂY === -->
                            <td><?php echo htmlspecialchars($course['TenHP']); ?></td>
                            <td class="text-end"><?php echo htmlspecialchars($course['SoChiChi']); ?></td>
                            <td class="text-center">
                                <a href="dangky_xoa.php?id=<?php echo urlencode($course['MaHP']); ?>" class="text-danger" title="Xóa học phần này">Xóa</a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                     <tr class="fw-bold table-light">
                         <td colspan="2" class="text-danger">Số lượng học phần: <?php echo $total_courses; ?></td>
                         <td class="text-danger text-end">Tổng số tín chỉ: <?php echo $total_credits; ?></td>
                         <td class="text-center"><a href="hocphan.php" class="text-primary small">Trở về giỏ hàng</a></td>
                     </tr>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center text-muted p-4"><i>Chưa có học phần nào được đăng ký.</i> <br><a href="hocphan.php" class="btn btn-sm btn-outline-primary mt-2">Xem danh sách học phần</a></td></tr>
                    <tr class="fw-bold table-light"><td colspan="2" class="text-danger">Số lượng học phần: 0</td><td class="text-danger text-end">Tổng số tín chỉ: 0</td><td class="text-center"><a href="hocphan.php" class="text-primary small">Trở về giỏ hàng</a></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- THÔNG TIN ĐĂNG KÍ -->
<?php if ($total_courses > 0 && $student_info): ?>
<hr>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">Thông tin Đăng kí</div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Mã số sinh Viên:</dt><dd class="col-sm-8"><?php echo htmlspecialchars($student_info['MaSV']); ?></dd>
            <dt class="col-sm-4">Họ Tên Sinh Viên:</dt><dd class="col-sm-8"><?php echo htmlspecialchars($student_info['HoTen']); ?></dd>
            <dt class="col-sm-4">Ngày Sinh:</dt><dd class="col-sm-8"><?php echo htmlspecialchars(date("d/m/Y", strtotime($student_info['NgaySinh']))); ?></dd>
            <dt class="col-sm-4">Ngành Học:</dt><dd class="col-sm-8"><?php echo htmlspecialchars($student_info['MaNganh']); ?></dd>
            <dt class="col-sm-4">Ngày Đăng Kí:</dt><dd class="col-sm-8"><?php echo date("d/m/Y"); ?></dd>
        </dl>
        <div class="text-end mt-3">
            <form action="dangky_luu.php" method="POST" style="display: inline;">
                <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i> Xác Nhận</button>
            </form>
             <a href="dangky_xoahet.php" class="btn btn-outline-danger ms-2"><i class="fas fa-times me-1"></i> Hủy Đăng Ký</a>
        </div>
    </div>
</div>
<?php elseif ($total_courses > 0 && !$student_info): ?>
    <div class="alert alert-danger">Không thể hiển thị thông tin sinh viên để xác nhận đăng ký.</div>
<?php endif; ?>
<!-- KẾT THÚC THÔNG TIN ĐĂNG KÍ -->

<?php
$conn->close();
require 'footer.php';
?>