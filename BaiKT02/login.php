<?php
// --- login.php ---
require 'config.php';

$errors = [];
$username = ''; // This should be MaSV based on our assumption

if (isset($_SESSION['user_id'])) {
    header('Location: index.php'); exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? ''); // Expecting MaSV here
    $password = $_POST['password'] ?? '';

    if (empty($username)) $errors['username'] = 'Mã sinh viên (Tên đăng nhập) là bắt buộc.';
    if (empty($password)) $errors['password'] = 'Mật khẩu là bắt buộc.';

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, username, password, fullname FROM users WHERE username = ?");
        if($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) { // Fetch user if exists
                if (password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username']; // This is MaSV
                    $_SESSION['fullname'] = $user['fullname'];
                    header("Location: index.php"); exit();
                } else {
                    $errors['login'] = 'Mã sinh viên hoặc mật khẩu không đúng.';
                }
            } else {
                $errors['login'] = 'Mã sinh viên hoặc mật khẩu không đúng.';
            }
            $stmt->close();
        } else {
             $errors['db'] = 'Lỗi truy vấn cơ sở dữ liệu.';
        }
    }
     $conn->close();
}

$page_title = "Đăng Nhập";
require 'header.php';
?>
<div class="form-container" style="max-width: 500px;">
    <h2 class="text-center mb-4"><?php echo htmlspecialchars($page_title); ?></h2>
    <?php if (isset($errors['login'])): ?> <div class="alert alert-danger"><?php echo htmlspecialchars($errors['login']); ?></div> <?php endif; ?>
    <?php if (isset($errors['db'])): ?> <div class="alert alert-danger"><?php echo htmlspecialchars($errors['db']); ?></div> <?php endif; ?>
    <form method="POST" action="login.php" novalidate>
        <div class="mb-3">
            <label for="username" class="form-label">Mã sinh viên (Tên đăng nhập) <span class="text-danger">*</span></label>
            <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            <?php if (isset($errors['username'])): ?> <div class="invalid-feedback"><?php echo htmlspecialchars($errors['username']); ?></div> <?php endif; ?>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
            <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" required>
            <?php if (isset($errors['password'])): ?> <div class="invalid-feedback"><?php echo htmlspecialchars($errors['password']); ?></div> <?php endif; ?>
        </div>
        <div class="d-grid gap-2 mb-3"> <button type="submit" class="btn btn-primary"><i class="fas fa-sign-in-alt me-1"></i> Đăng Nhập</button> </div>
        <div class="text-center"> <p>Chưa có tài khoản? <a href="register.php">Đăng ký tại đây</a></p> </div>
    </form>
</div>
<?php require 'footer.php'; ?>