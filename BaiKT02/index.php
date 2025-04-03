<?php
require 'config.php'; // Connects, starts session, defines helpers, defines UPLOAD_DIR/BASE_URL

// --- Optional: Authentication ---
// require_login(); // Uncomment if this page requires login

$page_title = "Danh Sách Sinh Viên";
require 'header.php'; // Include header (should display flash messages)

// --- Pagination Configuration ---
$limit = 5; // Number of records to display per page

// --- Calculate Current Page ---
// Ensure page number is a positive integer, default to 1
$page = isset($_GET['page']) && filter_var($_GET['page'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
        ? (int)$_GET['page']
        : 1;
$start_offset = ($page - 1) * $limit; // Calculate the starting record index for SQL

// --- Get Total Number of Records ---
$total_records = 0;
$total_pages = 0;
$sql_total = "SELECT COUNT(*) AS total FROM SinhVien";
$result_total = $conn->query($sql_total); // Simple query, prepared statement optional for COUNT(*)

if ($result_total && $row_total = $result_total->fetch_assoc()) {
    $total_records = (int)$row_total['total'];
    $total_pages = ceil($total_records / $limit); // Calculate total pages
} else {
    // Log error if count query fails, inform user
    error_log("Index - Failed to execute total count query: (" . $conn->errno . ") " . $conn->error);
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lỗi khi đếm số lượng sinh viên.'];
    // Optionally redirect or display error prominently if count is crucial
}
if ($result_total) $result_total->free(); // Free result set memory

// --- Fetch Student Data for the Current Page ---
$students = []; // Initialize array to hold student data
// Added MaNganh to SELECT if needed later, ordered by HoTen
$sql_fetch = "SELECT MaSV, HoTen, GioiTinh, NgaySinh, Hinh, MaNganh FROM SinhVien ORDER BY HoTen ASC LIMIT ?, ?";
$stmt_fetch = $conn->prepare($sql_fetch);

if (!$stmt_fetch) {
    error_log("Index - Prepare failed for student fetch: (" . $conn->errno . ") " . $conn->error);
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lỗi hệ thống khi chuẩn bị truy vấn danh sách sinh viên.'];
    // Prevent displaying table if prepare fails
    $total_records = -1; // Use a flag to indicate error state
} else {
    // Bind parameters: i (integer) for start_offset, i (integer) for limit
    $stmt_fetch->bind_param("ii", $start_offset, $limit);

    if (!$stmt_fetch->execute()) {
        error_log("Index - Execute failed for student fetch: (" . $stmt_fetch->errno . ") " . $stmt_fetch->error);
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lỗi hệ thống khi truy vấn danh sách sinh viên (execute).'];
        $total_records = -1; // Flag error state
    } else {
        $result_fetch = $stmt_fetch->get_result(); // Get result set
        while ($row = $result_fetch->fetch_assoc()) {
            $students[] = $row; // Add student data to the array
        }
        $result_fetch->free(); // Free result set memory
    }
    $stmt_fetch->close(); // Close the prepared statement
}
// --- End Fetching Data ---

?>

<div class="container mt-4">
    <h2 class="text-center mb-4"><?php echo htmlspecialchars($page_title); ?></h2>

    <?php display_flash_message(); // Display any flash messages set earlier ?>

    <div class="d-flex justify-content-end mb-3">
        <a href="create.php" class="btn btn-success shadow-sm">
            <i class="fas fa-user-plus me-1"></i> Thêm Sinh Viên Mới
        </a>
    </div>

    <?php // --- Display Table only if there are records AND no critical errors happened --- ?>
    <?php if ($total_records > 0): ?>
        <div class="table-responsive shadow-sm rounded">
            <table class="table table-bordered table-striped table-hover align-middle mb-0">
                <thead class="table-primary">
                    <tr>
                        <th scope="col">Mã SV</th>
                        <th scope="col">Họ Tên</th>
                        <th scope="col">Giới Tính</th>
                        <th scope="col">Ngày Sinh</th>
                        <th scope="col" class="text-center">Hình Ảnh</th>
                        <th scope="col" class="text-center" style="min-width: 180px;">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student):
                        // Construct image URL safely
                        $image_url = '';
                        if (!empty($student['Hinh']) && defined('UPLOAD_DIR') && defined('BASE_URL')) {
                            $image_file_path = UPLOAD_DIR . $student['Hinh'];
                            if (file_exists($image_file_path)) {
                                $image_url = BASE_URL . UPLOAD_DIR . rawurlencode($student['Hinh']);
                            }
                        }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['MaSV']); ?></td>
                        <td><?php echo htmlspecialchars($student['HoTen']); ?></td>
                        <td><?php echo htmlspecialchars($student['GioiTinh']); ?></td>
                        <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($student['NgaySinh']))); ?></td>
                        <td class="text-center">
                            <?php if ($image_url): ?>
                                <img src="<?php echo htmlspecialchars($image_url); ?>"
                                     alt="Ảnh SV <?php echo htmlspecialchars($student['HoTen']); ?>"
                                     class="student-avatar img-thumbnail"
                                     style="max-height: 50px; width: auto;">
                            <?php else: ?>
                                <!-- Placeholder Icon -->
                                <i class="fas fa-user-circle text-secondary fa-2x" title="Không có ảnh"></i>
                            <?php endif; ?>
                        </td>
                        <td class="text-center action-buttons">
                            <a class="btn btn-info btn-sm me-1" href="detail.php?id=<?php echo urlencode($student['MaSV']); ?>" title="Xem Chi Tiết">
                                <i class="fas fa-eye"></i><span class="d-none d-lg-inline ms-1">Xem</span>
                            </a>
                            <a class="btn btn-warning btn-sm me-1" href="edit.php?id=<?php echo urlencode($student['MaSV']); ?>" title="Chỉnh Sửa">
                                <i class="fas fa-edit"></i><span class="d-none d-lg-inline ms-1">Sửa</span>
                            </a>
                            <a class="btn btn-danger btn-sm delete-btn"
                               href="delete.php?id=<?php echo urlencode($student['MaSV']); ?>"
                               title="Xóa Sinh Viên"
                               data-student-id="<?php echo htmlspecialchars($student['MaSV']); ?>"
                               data-student-name="<?php echo htmlspecialchars($student['HoTen']); ?>">
                                <i class="fas fa-trash-alt"></i><span class="d-none d-lg-inline ms-1">Xóa</span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php // --- Pagination Links --- ?>
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Student list navigation" class="mt-4 d-flex justify-content-center">
            <ul class="pagination shadow-sm">
                <?php // Previous Page Link ?>
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Trang trước">
                        <span aria-hidden="true">«</span>
                    </a>
                </li>

                <?php // Page Number Links (Consider limiting shown pages for large numbers) ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>" <?php echo ($page == $i) ? 'aria-current="page"' : ''; ?>>
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>

                <?php // Next Page Link ?>
                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Trang sau">
                        <span aria-hidden="true">»</span>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="text-center text-muted mt-2">
             Trang <?php echo $page; ?> / <?php echo $total_pages; ?> (Tổng số <?php echo $total_records; ?> sinh viên)
        </div>
        <?php endif; ?>

    <?php // --- Handle Case: No Records Found --- ?>
    <?php elseif ($total_records === 0): ?>
        <div class="alert alert-info text-center mt-4" role="alert">
            Hiện tại chưa có sinh viên nào trong danh sách.
            <a href="create.php" class="alert-link">Thêm sinh viên mới?</a>
        </div>
    <?php // --- Handle Case: Error During Fetch (total_records flagged as -1) --- ?>
    <?php elseif ($total_records === -1): ?>
         <div class="alert alert-danger text-center mt-4" role="alert">
            Đã có lỗi xảy ra khi tải danh sách sinh viên. Vui lòng thử lại sau.
        </div>
    <?php endif; ?>

</div> <?php // End container ?>

<?php
$conn->close(); // Close the database connection
require 'footer.php'; // Include footer
?>

<?php // --- Add this JavaScript (ideally in a separate JS file included in footer.php) --- ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const deleteButtons = document.querySelectorAll('.delete-btn');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault(); // Stop the link from navigating immediately

            const studentName = this.getAttribute('data-student-name');
            const studentId = this.getAttribute('data-student-id'); // Optional, but good for confirmation text
            const deleteUrl = this.href;

            // Use a simple confirm dialog (consider using Bootstrap modals for better UI)
            const confirmed = confirm(`Bạn có chắc chắn muốn xóa sinh viên "${studentName}" (Mã SV: ${studentId})?\n\nHành động này không thể hoàn tác!`);

            if (confirmed) {
                // If confirmed, navigate to the delete URL
                window.location.href = deleteUrl;
            }
            // If not confirmed, do nothing (link navigation was already prevented)
        });
    });
});
</script>