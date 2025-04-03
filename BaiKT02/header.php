<?php
// --- header.php ---
// Session started by config.php
$dangky_count = isset($_SESSION['dangky']) ? count($_SESSION['dangky']) : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Quản Lý Sinh Viên'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body { background-color: #f8f9fa; }
        .navbar { margin-bottom: 1rem; } /* Reduced bottom margin */
        .table img.student-avatar { max-height: 60px; width: 60px; object-fit: cover; border-radius: 5px; }
        .form-container { background-color: #ffffff; padding: 2rem; border-radius: 0.5rem; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); max-width: 700px; margin: 1rem auto; }
        .action-buttons a, .action-buttons button { margin-right: 5px; margin-bottom: 5px; }
        .pagination .page-link { margin-left: 2px; margin-right: 2px;}
        .student-detail-card { margin: 1rem auto; max-width: 800px; }
        .student-detail-card img.detail-avatar { max-width: 200px; width: 100%; height: auto; border-radius: 0.375rem; object-fit: cover; }
        .footer { background-color: #e9ecef; font-size: 0.9em;}
         /* Style for table header like images */
        thead.table-light th, tr.table-light td { background-color: #f8f9fa !important; /* Override default light */}
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="index.php">Test1</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php if (isset($_SESSION['user_id'])): // Logged in ?>
            <li class="nav-item">
              <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                 <i class="fas fa-user-graduate me-1"></i> Sinh Viên
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'hocphan.php' ? 'active' : ''; ?>" href="hocphan.php">
                <i class="fas fa-book me-1"></i> Học Phần
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo strpos(basename($_SERVER['PHP_SELF']), 'dangky_') === 0 ? 'active' : ''; ?>" href="dangky_xem.php">
                <i class="fas fa-edit me-1"></i> Đăng Ký <?php if($dangky_count > 0) echo "<span class='badge bg-danger ms-1'>$dangky_count</span>"; ?>
              </a>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?> (Đăng Nhập)
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Đăng Xuất</a></li>
              </ul>
            </li>
        <?php else: // Not logged in ?>
             <li class="nav-item">
               <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>" href="login.php">
                 <i class="fas fa-sign-in-alt me-1"></i> Đăng Nhập
               </a>
             </li>
              <li class="nav-item">
               <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : ''; ?>" href="register.php">
                 <i class="fas fa-user-plus me-1"></i> Đăng Ký Tài Khoản
               </a>
             </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="container flex-grow-1 my-3"> <!-- Adjusted margin -->
    <?php
    if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['flash_message']['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php unset($_SESSION['flash_message']); endif; ?>
<!-- Content starts here -->