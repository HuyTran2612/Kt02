<?php
require 'config.php'; // Connects and starts session

// --- Authentication Check ---
 // Use the function from config.php
// --- End Authentication Check ---

$page_title = "DANH SÁCH HỌC PHẦN";
require 'header.php'; // Include header (assumed to exist)

// Display any flash messages


// Fetch available courses from 'hocphan' table
$hocphans = [];
// Select course details from the 'hocphan' table
// REMOVED SoLuongDuKien as it's not in the DB schema
$result = $conn->query("SELECT MaHP, TenHP, SoTinChi FROM HocPhan ORDER BY MaHP");

if (!$result) {
    // Display error if the query for course list failed
    echo "<div class='alert alert-danger'>Lỗi truy vấn danh sách học phần: " . $conn->error . "</div>";
} else {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $hocphans[] = $row; // Add course data to the array
        }
    }
    $result->free(); // Free the result set memory
}

// --- Get current registration counts ---
// We count from ChiTietDangKy table now
$registered_counts = []; // Initialize array to store counts {MaHP => count}
$all_mahp = array_column($hocphans, 'MaHP'); // Get all MaHP from the fetched list

if (!empty($all_mahp)) {
    $placeholders = implode(',', array_fill(0, count($all_mahp), '?'));
    $types = str_repeat('s', count($all_mahp));

    // Count registrations for all listed courses from ChiTietDangKy
    $sql_count = "SELECT MaHP, COUNT(*) as DaDangKy
                  FROM ChiTietDangKy
                  WHERE MaHP IN ($placeholders)
                  GROUP BY MaHP";

    $stmt_count = $conn->prepare($sql_count);
    if ($stmt_count) {
        $stmt_count->bind_param($types, ...$all_mahp);
        if ($stmt_count->execute()) {
             $result_count = $stmt_count->get_result();
             while ($row_count = $result_count->fetch_assoc()) {
                 $registered_counts[$row_count['MaHP']] = $row_count['DaDangKy'];
             }
             $result_count->free();
        } else {
            echo "<div class='alert alert-warning'>Lỗi khi thực thi đếm số lượng: " . $stmt_count->error . "</div>";
        }
        $stmt_count->close();
    } else {
        echo "<div class='alert alert-warning'>Lỗi khi chuẩn bị câu lệnh đếm số lượng: " . $conn->error . "</div>";
    }
}

?>

<!-- HTML Structure -->
<h3 class="text-dark mb-4"><?php echo htmlspecialchars($page_title); ?></h3>

<?php if (!empty($hocphans)): ?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr class="table-light">
                    <th scope="col">Mã HP</th>
                    <th scope="col">Tên Học Phần</th>
                    <th scope="col" class="text-center">Số Tín Chỉ</th>
                    <th scope="col" class="text-center">Đã ĐK</th> <!-- Show count instead of capacity -->
                    <th scope="col" class="text-center">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hocphans as $hp):
                    $maHP = $hp['MaHP'];
                    $daDangKy = $registered_counts[$maHP] ?? 0; // Get count, default 0

                    // Check if selected in current session's temporary list
                    $is_in_session = isset($_SESSION['dangky'][$maHP]);

                    // Determine button state (only disable if already in session cart)
                    $button_disabled = $is_in_session;
                    $button_text = "Chọn ĐK"; // Changed text slightly
                    $button_class = "btn-primary";
                    $button_icon = "fa-plus";

                    if ($is_in_session) {
                        $button_text = "Đã chọn";
                        $button_class = "btn-secondary";
                        $button_icon = "fa-check";
                    }
                    // REMOVED: Capacity checking logic ($is_full)
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($maHP); ?></td>
                    <td><?php echo htmlspecialchars($hp['TenHP']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($hp['SoTinChi']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($daDangKy); ?></td> <!-- Display actual registered count -->
                    <td class="text-center">
                        <?php if ($button_disabled): ?>
                            <button class="btn <?php echo $button_class; ?> btn-sm" disabled>
                                <i class="fas <?php echo $button_icon; ?> me-1"></i> <?php echo $button_text; ?>
                            </button>
                        <?php else: ?>
                             <!-- Link to add course to session cart -->
                            <a href="dangky_them.php?id=<?php echo urlencode($maHP); ?>" class="btn <?php echo $button_class; ?> btn-sm">
                                <i class="fas <?php echo $button_icon; ?> me-1"></i> <?php echo $button_text; ?>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
    <div class="alert alert-info">Hiện chưa có học phần nào trong danh sách.</div>
<?php endif; ?>

<?php
$conn->close();
require 'footer.php'; // Include footer file (assumed to exist)
?>