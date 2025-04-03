<?php
require 'config.php'; // Connects, starts session, defines helpers

// --- Authentication Check ---
require_login();
// --- End Authentication Check ---

$page_title = "Đăng Ký Thành Công";
require 'header.php'; // Includes display_flash_message()

?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm text-center border-success">
                <div class="card-header bg-success text-white">
                   <h4><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($page_title); ?></h4>
                </div>
                <div class="card-body p-4">
                     <?php /* Display flash message specific to this page if needed,
                                header already displayed the one from dangky_luu */
                           // display_flash_message(); ?>

                    <p class="lead text-success fw-bold">Thông tin đăng ký học phần của bạn đã được lưu vào hệ thống thành công!</p>
                    <hr>
                    <p>Bạn có thể thực hiện các thao tác tiếp theo:</p>
                     <!-- Link to view registration history (needs a new page/feature) -->
                    <!--
                    <a href="lichsu_dangky.php" class="btn btn-info mt-2 mb-2">
                        <i class="fas fa-history me-1"></i> Xem Lịch Sử Đăng Ký
                    </a>
                    -->
                    <a href="index.php" class="btn btn-primary mt-2 mb-2">
                        <i class="fas fa-home me-1"></i> Về trang chủ
                    </a>
                    <a href="hocphan.php" class="btn btn-secondary mt-2 mb-2">
                        <i class="fas fa-book me-1"></i> Xem lại danh sách học phần
                    </a>
                </div>
                <div class="card-footer text-muted small">
                    Chúc bạn học tập tốt!
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// $conn->close(); // Close connection if not done in footer
require 'footer.php';
?>