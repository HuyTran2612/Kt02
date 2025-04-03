<?php
// --- login.php ---
require 'config.php'; // Connects, starts session, defines helpers

$errors = [];
$masv_input = ''; // Renamed from username for clarity

// If user is already logged in, redirect them to the index page
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit(); // Stop script execution
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get input (use 'username' from form, but treat it as MaSV)
    $masv_input = trim($_POST['username'] ?? '');
    $password_input = $_POST['password'] ?? '';

    // --- Basic Validation ---
    if (empty($masv_input)) {
        $errors['username'] = 'Mã sinh viên là bắt buộc.';
    }
    if (empty($password_input)) {
        $errors['password'] = 'Mật khẩu là bắt buộc.';
    }

    // --- Proceed if basic validation passes ---
    if (empty($errors)) {

        // Prepare SQL to fetch student by MaSV including the password hash
        // IMPORTANT: Assumes you added the 'PasswordHash' column!
        $sql = "SELECT MaSV, HoTen, PasswordHash FROM SinhVien WHERE MaSV = ?";
        $stmt = $conn->prepare($sql);

        if($stmt) {
            $stmt->bind_param("s", $masv_input);
            if ($stmt->execute()) {
                $result = $stmt->get_result();

                if ($student = $result->fetch_assoc()) {
                    // Student found, now verify the password
                    // Check if PasswordHash is set and verify against input
                    if ($student['PasswordHash'] !== null && password_verify($password_input, $student['PasswordHash'])) {
                        // Password is correct!

                        // Regenerate session ID for security
                        session_regenerate_id(true);

                        // Store essential user info in the session
                        $_SESSION['user_id'] = $student['MaSV'];  // Use MaSV as the unique identifier
                        $_SESSION['username'] = $student['MaSV']; // Store MaSV as the 'username' reference
                        $_SESSION['fullname'] = $student['HoTen']; // Store the student's full name

                        // Redirect to the main page after successful login
                        header("Location: index.php");
                        exit(); // Stop script execution

                    } else {
                        // Invalid password or password not set for this user
                        $errors['login'] = 'Mã sinh viên hoặc mật khẩu không đúng.';
                    }
                } else {
                    // No student found with the provided MaSV
                    $errors['login'] = 'Mã sinh viên hoặc mật khẩu không đúng.';
                }
                $result->free(); // Free result set memory
            } else {
                // Error executing the statement
                 error_log("Login query execution failed: " . $stmt->error);
                 $errors['db'] = 'Lỗi hệ thống khi đăng nhập. Vui lòng thử lại.';
            }
            $stmt->close(); // Close the statement
        } else {
             // Error preparing the statement
             error_log("Login query prepare failed: " . $conn->error);
             $errors['db'] = 'Lỗi hệ thống khi đăng nhập. Vui lòng thử lại.';
        }
    }
     // Close connection only if it wasn't closed in footer.php
     // $conn->close(); // Usually closed in footer
}

// --- Prepare for Page Display ---
$page_title = "Đăng Nhập Hệ Thống";
require 'header.php'; // Include header (should display flash messages if any)
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="text-center mb-0"><?php echo htmlspecialchars($page_title); ?></h3>
                </div>
                <div class="card-body p-4">
                    <?php display_flash_message(); // Display any session flash messages ?>

                    <?php // Display login-specific errors ?>
                    <?php if (!empty($errors['login'])): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($errors['login']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($errors['db'])): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($errors['db']); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="login.php" novalidate>
                        <div class="mb-3">
                            <label for="username" class="form-label">Mã sinh viên <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>"
                                   id="username"
                                   name="username"
                                   value="<?php echo htmlspecialchars($masv_input); ?>"
                                   required
                                   aria-describedby="usernameHelp">
                             <div id="usernameHelp" class="form-text">Nhập Mã Số Sinh Viên của bạn.</div>
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['username']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                            <input type="password"
                                   class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                   id="password"
                                   name="password"
                                   required>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['password']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-1"></i> Đăng Nhập
                            </button>
                        </div>

                        <?php /* // Uncomment or adapt if you implement registration
                        <div class="text-center">
                            <p>Chưa có tài khoản? <a href="register.php">Đăng ký tại đây</a></p>
                        </div>
                        */ ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// $conn->close(); // Close connection here if not done in footer
require 'footer.php';
?>