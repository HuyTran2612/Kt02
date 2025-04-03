<?php
// --- dangky_thanhcong.php ---
require 'config.php';

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not authenticated, although they shouldn't reach here without saving first
    header('Location: login.php');
    exit();
}
// --- End Authentication Check ---

$page_title = "Đăng Ký Thành Công";
require 'header.php'; // Header will display the flash message set by dangky_luu.php
?>

<div class="container mt-5">
    <div class="card shadow-sm text-center" style="max-width: 600px; margin: auto;">
        <div class="card-header bg-success text-white">
           <h4><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($page_title); ?></h4>
        </div>
        <div class="card-body">
            <p class="lead">Thông tin đăng ký học phần của bạn đã được lưu thành công.</p>
            <hr>
            <p>Bạn có thể xem lại lịch sử đăng ký (chức năng này chưa được xây dựng) hoặc quay về trang chính.</p>
            <a href="index.php" class="btn btn-primary mt-2">
                <i class="fas fa-home me-1"></i> Về trang Sinh Viên
            </a>
            <a href="hocphan.php" class="btn btn-secondary mt-2">
                <i class="fas fa-book me-1"></i> Xem danh sách học phần
            </a>
        </div>
    </div>
</div>

<?php
// Connection likely closed in header/footer includes if setup that way
// otherwise, ensure $conn->close(); if needed.
require 'footer.php';
?>