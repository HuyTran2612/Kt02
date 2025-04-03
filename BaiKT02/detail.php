<?php
// --- detail.php ---
require 'config.php';

$id = $_GET['id'] ?? null;
$student = null;

if (!$id) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Mã sinh viên không hợp lệ.'];
    header("Location: index.php");
    exit();
}

// --- Fetch student data ---
// Consider joining with Nganh table if you have one:
// SELECT sv.*, n.TenNganh FROM SinhVien sv LEFT JOIN Nganh n ON sv.MaNganh = n.MaNganh WHERE sv.MaSV = ?
$stmt = $conn->prepare("SELECT MaSV, HoTen, GioiTinh, NgaySinh, Hinh, MaNganh FROM SinhVien WHERE MaSV = ?");
if (!$stmt) {
     // Log: error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
     $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lỗi truy vấn cơ sở dữ liệu.'];
     header("Location: index.php");
     exit();
}

$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $student = $result->fetch_assoc();
} else {
    $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Không tìm thấy sinh viên với mã cung cấp (ID: '.htmlspecialchars($id).').'];
    header("Location: index.php");
    exit();
}
$stmt->close();


$page_title = "Chi Tiết Sinh Viên - " . htmlspecialchars($student['HoTen']);
require 'header.php';
?>

<h2 class="text-center mb-4">CHI TIẾT SINH VIÊN</h2>

<div class="card student-detail-card shadow-sm">
    <div class="row g-0">
        <div class="col-md-4 d-flex justify-content-center align-items-center p-3 bg-light">
             <?php if (!empty($student['Hinh']) && file_exists(UPLOAD_DIR . $student['Hinh'])): ?>
                <img src="<?php echo UPLOAD_DIR . htmlspecialchars($student['Hinh']); ?>"
                     class="img-fluid rounded detail-avatar"
                     alt="Ảnh <?php echo htmlspecialchars($student['HoTen']); ?>">
            <?php else: ?>
                 <i class="fas fa-user-tie fa-10x text-secondary opacity-50"></i> <!-- Placeholder icon -->
            <?php endif; ?>
        </div>
        <div class="col-md-8">
            <div class="card-body p-4">
                <h3 class="card-title mb-3 border-bottom pb-2"><?php echo htmlspecialchars($student['HoTen']); ?></h3>
                <dl class="row">
                    <dt class="col-sm-4">Mã Sinh Viên:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($student['MaSV']); ?></dd>

                    <dt class="col-sm-4">Giới Tính:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($student['GioiTinh']); ?></dd>

                    <dt class="col-sm-4">Ngày Sinh:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars(date("d/m/Y", strtotime($student['NgaySinh']))); ?></dd>

                    <dt class="col-sm-4">Mã Ngành:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($student['MaNganh']); ?></dd>
                    <!-- Add TenNganh here if you join tables -->
                    <!--
                    <dt class="col-sm-4">Tên Ngành:</dt>
                    <dd class="col-sm-8"><?php // echo isset($student['TenNganh']) ? htmlspecialchars($student['TenNganh']) : 'N/A'; ?></dd>
                     -->
                </dl>
                 <div class="mt-4 pt-3 border-top">
                     <a href="index.php" class="btn btn-secondary">
                         <i class="fas fa-arrow-left me-1"></i> Quay Lại Danh Sách
                     </a>
                    <a href="edit.php?id=<?php echo urlencode($student['MaSV']); ?>" class="btn btn-warning ms-2">
                        <i class="fas fa-edit me-1"></i> Chỉnh Sửa
                    </a>
                 </div>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
require 'footer.php';
?>