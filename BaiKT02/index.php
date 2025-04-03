<?php
// --- index.php ---
require 'config.php'; // Use require to stop if config fails
$page_title = "Danh Sách Sinh Viên";
require 'header.php';

// --- Pagination Logic ---
$limit = 5; // Records per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // Ensure page is positive int
$start = ($page - 1) * $limit;

// --- Get Total Records ---
$total_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM SinhVien");
$total_records = 0;
if ($total_stmt) {
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    if($total_row = $total_result->fetch_assoc()) {
        $total_records = $total_row['total'];
    }
    $total_stmt->close();
}
$totalPages = ceil($total_records / $limit);

// --- Fetch Student Data ---
$students = []; // Initialize array
// Added ORDER BY HoTen ASC for consistent results
$stmt = $conn->prepare("SELECT MaSV, HoTen, GioiTinh, NgaySinh, Hinh FROM SinhVien ORDER BY HoTen ASC LIMIT ?, ?");
if ($stmt) {
    $stmt->bind_param("ii", $start, $limit); // 'ii' means two integers
    $stmt->execute();
    $result = $stmt->get_result(); // Get result set
    while ($row = $result->fetch_assoc()) {
        $students[] = $row; // Add student to array
    }
    $stmt->close(); // Close the prepared statement
} else {
    // Handle error if statement preparation failed
     $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lỗi truy vấn cơ sở dữ liệu.'];
     // Optionally log: error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}

?>

<h2 class="text-center mb-4">DANH SÁCH SINH VIÊN</h2>

<div class="d-flex justify-content-end mb-3">
    <a href="create.php" class="btn btn-success">
        <i class="fas fa-plus me-1"></i> Thêm Sinh Viên Mới
    </a>
</div>

<?php if (!empty($students)): ?>
<div class="table-responsive shadow-sm">
    <table class="table table-bordered table-striped table-hover align-middle mb-0">
        <thead class="table-primary">
            <tr>
                <th scope="col">Mã SV</th>
                <th scope="col">Họ Tên</th>
                <th scope="col">Giới Tính</th>
                <th scope="col">Ngày Sinh</th>
                <th scope="col" class="text-center">Hình Ảnh</th>
                <th scope="col" class="text-center">Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
            <tr>
                <td><?php echo htmlspecialchars($student['MaSV']); ?></td>
                <td><?php echo htmlspecialchars($student['HoTen']); ?></td>
                <td><?php echo htmlspecialchars($student['GioiTinh']); ?></td>
                <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($student['NgaySinh']))); ?></td>
                <td class="text-center">
                    <?php if (!empty($student['Hinh']) && file_exists(UPLOAD_DIR . $student['Hinh'])): ?>
                        <img src="<?php echo UPLOAD_DIR . htmlspecialchars($student['Hinh']); ?>"
                             alt="Ảnh <?php echo htmlspecialchars($student['HoTen']); ?>"
                             class="student-avatar img-thumbnail">
                    <?php else: ?>
                        <i class="fas fa-user-circle text-secondary fa-2x" title="Không có ảnh"></i>
                    <?php endif; ?>
                </td>
                <td class="text-center action-buttons">
                    <a class="btn btn-info btn-sm" href="detail.php?id=<?php echo urlencode($student['MaSV']); ?>" title="Xem Chi Tiết">
                        <i class="fas fa-eye"></i> <span class="d-none d-md-inline">Xem</span>
                    </a>
                    <a class="btn btn-warning btn-sm" href="edit.php?id=<?php echo urlencode($student['MaSV']); ?>" title="Chỉnh Sửa">
                        <i class="fas fa-edit"></i> <span class="d-none d-md-inline">Sửa</span>
                    </a>
                    <a class="btn btn-danger btn-sm delete-btn"
                       href="delete.php?id=<?php echo urlencode($student['MaSV']); ?>"
                       title="Xóa"
                       data-student-name="<?php echo htmlspecialchars($student['HoTen']); ?>">
                        <i class="fas fa-trash-alt"></i> <span class="d-none d-md-inline">Xóa</span>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

    <?php // --- Pagination Links ---
    if ($totalPages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4 d-flex justify-content-center">
        <ul class="pagination shadow-sm">
            <!-- Previous Page Link -->
            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>

            <!-- Page Number Links -->
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>" <?php echo ($page == $i) ? 'aria-current="page"' : ''; ?>>
                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>

            <!-- Next Page Link -->
            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

<?php elseif ($total_records === 0): ?>
    <div class="alert alert-info text-center mt-4" role="alert">
        Không tìm thấy sinh viên nào trong cơ sở dữ liệu.
        <a href="create.php" class="alert-link">Thêm sinh viên mới?</a>
    </div>
<?php endif; // End check for empty $students ?>

<?php
$conn->close(); // Close the database connection
require 'footer.php';
?>