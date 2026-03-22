<?php
session_start();
require_once __DIR__ . '/includes/connect-db.php';

// --- 1. KHỞI TẠO BIẾN MẶC ĐỊNH (FIX LỖI UNDEFINED) ---
$error = "";
$success = false;
$token = $_GET['token'] ?? ''; // Lấy token từ URL (?token=...)

// --- 2. KIỂM TRA TOKEN BAN ĐẦU ---
if (empty($token)) {
    $error = "Liên kết không hợp lệ hoặc thiếu mã xác thực.";
} else {
    // Truy vấn kiểm tra token trong Database
    $stmt = $conn->prepare("SELECT id, reset_expire FROM users WHERE reset_token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        $error = "Liên kết không hợp lệ hoặc đã được sử dụng trước đó.";
    } elseif (strtotime($user['reset_expire']) < time()) {
        $error = "Liên kết này đã hết hạn (hiệu lực 15 phút). Vui lòng yêu cầu lại.";
    }
}

// --- 3. XỬ LÝ CẬP NHẬT MẬT KHẨU (KHI SUBMIT FORM) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_password']) && empty($error)) {
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // Validation cơ bản
    if (strlen($new_pass) < 6) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự để đảm bảo bảo mật.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "Mật khẩu xác nhận không khớp. Vui lòng kiểm tra lại.";
    } else {
        // Hash mật khẩu tiêu chuẩn BCrypt
        $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);

        // Cập nhật mật khẩu và VÔ HIỆU HÓA TOKEN (đặt về NULL)
        $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expire = NULL WHERE id = ?");
        $update->bind_param("si", $hashed_password, $user['id']);

        if ($update->execute()) {
            $success = true;
        } else {
            error_log("Reset Password DB Error: " . $update->error);
            $error = "Lỗi hệ thống không thể cập nhật mật khẩu. Thử lại sau.";
        }
    }
}
?>

<!doctype html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <title>Đặt lại mật khẩu - Personal Student Tracker</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Readex+Pro:wght@160..700&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="./css/auth-style.css" />
</head>

<body>
    <div class="flex-container">
        <div class="authentication-container">

            <svg id="app-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path
                    d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z">
                </path>
                <path d="M22 10v6"></path>
                <path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"></path>
            </svg>

            <?php if (!$success): ?>
                <div class="hero">
                    <h2>Mật Khẩu Mới</h2>
                    <p>Thiết lập lại mật khẩu truy cập</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="status-msg error-msg"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (empty($error) || (!empty($error) && strpos($error, 'khớp') !== false || strpos($error, 'ký tự') !== false)): ?>
                    <form method="POST" action="">
                        <div class="input-box">
                            <label>Mật khẩu mới:</label>
                            <input type="password" name="new_password" placeholder="Tối thiểu 6 ký tự" required />
                        </div>
                        <div class="input-box">
                            <label>Xác nhận mật khẩu:</label>
                            <input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu mới" required />
                        </div>
                        <button type="submit">Cập nhật mật khẩu</button>
                    </form>
                <?php else: ?>
                    <a href="forgot-password.php" class="back-link">← Yêu cầu lại liên kết mới</a>
                <?php endif; ?>

                <a href="index.php" class="back-link" style="margin-top: 10px;">Quay lại đăng nhập</a>

            <?php else: ?>
                <div class="success-step" style="text-align: center;">
                    <div class="success-icon-container" style="color: #22c55e; margin-bottom: 1.5rem;">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                            stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <div class="hero">
                        <h2>Hoàn Tất!</h2>
                        <p>Mật khẩu của bạn đã được thay đổi thành công.</p>
                    </div>
                    <p style="color: #64748b; margin-bottom: 25px; font-size: 0.9rem;">
                        Vui lòng sử dụng mật khẩu mới để đăng nhập vào hệ thống.
                    </p>
                    <a href="index.php" class="submit-btn-link">ĐĂNG NHẬP NGAY</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</body>

</html>