<?php
// --- register.php ---
require 'config.php';

$errors = [];
$username = $fullname = $masv_check = ''; // username is MaSV

if (isset($_SESSION['user_id'])) {
    header('Location: index.php'); exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? ''); // This IS MaSV
    $fullname = trim($_POST['fullname'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // --- Validation ---
    if (empty($username)) $errors['username'] = 'Mã sinh viên (Tên đăng nhập) là bắt buộc.';
    else {
        // 1. Check if MaSV exists in SinhVien table first!
        $stmt_sv_check = $conn->prepare("SELECT MaSV FROM SinhVien WHERE MaSV = ?");
        if($stmt_sv_check) {
             $stmt_sv_check->bind_param("s", $username);
             $stmt_sv_check->execute();
             $stmt_sv_check->store_result();
             if($stmt_sv_check->num_rows === 0) {
                 $errors['username'] = 'Mã sinh viên này không tồn tại trong hệ thống.';
             }
             $stmt_sv_check->close();
        } else {
             $errors['db'] = 'Lỗi kiểm tra mã sinh viên.';
        }

        // 2. If MaSV exists, check if it's already registered in users table
        if(!isset($errors['username']) && !isset($errors['db'])) {
            $stmt_user_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            if($stmt_user_check){
                $stmt_user_check->bind_param("s", $username);
                $stmt_user_check->execute();
                $stmt_user_check->store_result();
                if ($stmt_user_check->num_rows > 0) {
                    $errors['username'] = 'Mã sinh viên này đã được đăng ký tài khoản.';
                }
                $stmt_user_check->close();
            } else {
                $errors['db'] = 'Lỗi kiểm tra tài khoản người dùng.';
            }
        }
    }

    if (empty($fullname)) $errors['fullname'] = 'Họ và Tên là bắt buộc.'; // Make fullname required
    if (empty($password)) $errors['password'] = 'Mật khẩu là bắt buộc.';
    elseif (strlen($password) < 6) $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    if ($password !== $password_confirm) $errors['password_confirm'] = 'Xác nhận mật khẩu không khớp.';

    // --- Process Registration ---
    if (empty($errors)) {
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt_insert = $conn->prepare("INSERT INTO users (username, password, fullname) VALUES (?, ?, ?)");
        if ($stmt_insert) {
            $stmt_insert->bind_param("sss", $username, $password_hashed, $fullname);
            if ($stmt_insert->execute()) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Đăng ký thành công! Vui lòng đăng nhập bằng Mã Sinh Viên và mật khẩu vừa tạo.'];
                header("Location: login.php"); exit();
            } else {
                $errors['db'] = 'Lỗi khi đăng ký người dùng: ' . $conn->error; // Show specific error in dev
            }
            $stmt_insert->close();
        } else {
             $errors['db'] = 'Lỗi chuẩn bị truy vấn đăng ký.';
        }
    }
    $conn->close();
}

$page_title = "Đăng Ký Tài Khoản";
require 'header.php';
?>
<div class="form-container" style="max-width: 500px;">
    <h2 class="text-center mb-4"><?php echo htmlspecialchars($page_title); ?></h2>
    <?php if (!empty($errors['db'])): ?> <div class="alert alert-danger"><?php echo htmlspecialchars($errors['db']); ?></div> <?php endif; ?>
    <form method="POST" action="register.php" novalidate>
        <div class="mb-3">
            <label for="username" class="form-label">Mã sinh viên (Dùng làm tên đăng nhập) <span class="text-danger">*</span></label>
            <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            <?php if (isset($errors['username'])): ?> <div class="invalid-feedback"><?php echo htmlspecialchars($errors['username']); ?></div> <?php endif; ?>
        </div>
        <div class="mb-3">
            <label for="fullname" class="form-label">Họ và Tên <span class="text-danger">*</span></label>
            <input type="text" class="form-control <?php echo isset($errors['fullname']) ? 'is-invalid' : ''; ?>" id="fullname" name="fullname" value="<?php echo htmlspecialchars($fullname); ?>" required>
             <?php if (isset($errors['fullname'])): ?> <div class="invalid-feedback"><?php echo htmlspecialchars($errors['fullname']); ?></div> <?php endif; ?>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
            <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" required>
            <?php if (isset($errors['password'])): ?> <div class="invalid-feedback"><?php echo htmlspecialchars($errors['password']); ?></div> <?php endif; ?>
        </div>
        <div class="mb-3">
            <label for="password_confirm" class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
            <input type="password" class="form-control <?php echo isset($errors['password_confirm']) ? 'is-invalid' : ''; ?>" id="password_confirm" name="password_confirm" required>
            <?php if (isset($errors['password_confirm'])): ?> <div class="invalid-feedback"><?php echo htmlspecialchars($errors['password_confirm']); ?></div> <?php endif; ?>
        </div>
        <div class="d-grid gap-2 mb-3"> <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus me-1"></i> Đăng Ký</button> </div>
        <div class="text-center"> <p>Đã có tài khoản? <a href="login.php">Đăng nhập tại đây</a></p> </div>
    </form>
</div>
<?php require 'footer.php'; ?>