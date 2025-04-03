<?php
// --- hocphan.php ---
require 'config.php';

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Vui lòng đăng nhập để truy cập trang này.'];
    header('Location: login.php');
    exit();
}
// --- End Authentication Check ---

$page_title = "DANH SÁCH HỌC PHẦN";
require 'header.php';

// Fetch available courses including the limit
$hocphans = [];
// === THAY ĐỔI Ở ĐÂY ===
$result = $conn->query("SELECT MaHP, TenHP, SoChiChi, SoLuongDuKien FROM hocphan ORDER BY MaHP");
$mahp_list_for_count = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $hocphans[] = $row;
        if ($row['SoLuongDuKien'] !== null) {
            $mahp_list_for_count[] = $row['MaHP'];
        }
    }
    $result->free();
} else {
    echo "<div class='alert alert-danger'>Lỗi truy vấn danh sách học phần.</div>";
}

// --- Get current registration counts ---
$registered_counts = [];
if (!empty($mahp_list_for_count)) {
    $placeholders = implode(',', array_fill(0, count($mahp_list_for_count), '?'));
    $types = str_repeat('s', count($mahp_list_for_count));
    $sql_count = "SELECT MaHP, COUNT(*) as DaDangKy FROM dangkyhocphan WHERE MaHP IN ($placeholders) GROUP BY MaHP";
    $stmt_count = $conn->prepare($sql_count);
    if ($stmt_count) {
        $stmt_count->bind_param($types, ...$mahp_list_for_count);
        $stmt_count->execute();
        $result_count = $stmt_count->get_result();
        while ($row_count = $result_count->fetch_assoc()) {
            $registered_counts[$row_count['MaHP']] = $row_count['DaDangKy'];
        }
        $stmt_count->close();
    } else {
        echo "<div class='alert alert-warning'>Lỗi khi lấy số lượng đã đăng ký.</div>";
    }
}

?>

<h3 class="text-dark mb-4"><?php echo htmlspecialchars($page_title); ?></h3>

<?php if (!empty($hocphans)): ?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr class="table-light">
                    <th scope="col">Mã Học Phần</th>
                    <th scope="col">Tên Học Phần</th> <!-- Giữ nguyên tiêu đề bảng -->
                    <th scope="col" class="text-center">Số Tín Chỉ</th>
                    <th scope="col" class="text-center">Số lượng dự kiến</th>
                    <th scope="col" class="text-center">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hocphans as $hp):
                    $maHP = $hp['MaHP'];
                    $soLuongDuKien = $hp['SoLuongDuKien'];
                    $daDangKy = $registered_counts[$maHP] ?? 0;
                    $is_full = ($soLuongDuKien !== null && $daDangKy >= $soLuongDuKien);
                    $is_in_session = isset($_SESSION['dangky'][$maHP]);
                    $button_disabled = $is_full || $is_in_session;
                    $button_text = "Đăng Ký";
                    $button_class = "btn-success";
                    $button_icon = "fa-plus";

                    if ($is_in_session) { $button_text = "Đã chọn"; $button_class = "btn-secondary"; $button_icon = "fa-check"; }
                    elseif ($is_full) { $button_text = "Đã đầy"; $button_class = "btn-danger"; $button_icon = "fa-ban"; }
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($maHP); ?></td>
                    <!-- === THAY ĐỔI Ở ĐÂY === -->
                    <td><?php echo htmlspecialchars($hp['TenHP']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($hp['SoChiChi']); ?></td>
                    <td class="text-center"><?php echo ($soLuongDuKien !== null) ? htmlspecialchars($soLuongDuKien) : '-'; ?></td>
                    <td class="text-center">
                        <?php if ($button_disabled): ?>
                            <button class="btn <?php echo $button_class; ?> btn-sm" disabled><i class="fas <?php echo $button_icon; ?> me-1"></i> <?php echo $button_text; ?></button>
                        <?php else: ?>
                            <a href="dangky_them.php?id=<?php echo urlencode($maHP); ?>" class="btn <?php echo $button_class; ?> btn-sm"><i class="fas <?php echo $button_icon; ?> me-1"></i> <?php echo $button_text; ?></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
    <div class="alert alert-info">Hiện chưa có học phần nào.</div>
<?php endif; ?>

<?php
$conn->close();
require 'footer.php';
?>