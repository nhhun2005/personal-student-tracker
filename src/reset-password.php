<?php
header('Content-Type: text/html; charset=UTF-8');

require_once './services/UserService.php';
$userService = new UserService();

$token = $_GET['token'] ?? '';
$success = isset($_GET['success']);
$error_param = $_GET['error'] ?? '';

$error_msg = $userService->validateToken($token);

if (!$error_msg && $error_param == 'password_mismatch') {
    $error_msg = "Mật khẩu không khớp.";
}
?>

<!doctype html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <title>Đặt lại mật khẩu - Student Tracker</title>
    <link rel="stylesheet" href="./css/auth-style.css" />
</head>

<body>
    <div class="flex-container">
        <div class="authentication-container">
            <?php if (!$success): ?>
                <div class="hero">
                    <h2>Mật Khẩu Mới</h2>
                    <p>Thiết lập lại mật khẩu</p>
                </div>

                <?php if ($error_msg): ?>
                    <div class="status-msg error-msg"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <!-- Chỉ hiện form nếu token hợp lệ (không có lỗi nghiêm trọng) -->
                <?php if ($error_msg == null || $error_param == 'password_mismatch'): ?>
                    <form method="POST" action="./services/UserService.php">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <div class="input-box">
                            <label>Mật khẩu mới:</label>
                            <input type="password" name="new_password" required />
                        </div>
                        <div class="input-box">
                            <label>Xác nhận mật khẩu:</label>
                            <input type="password" name="confirm_password" required />
                        </div>
                        <button type="submit" name="reset_password_submit">Cập nhật mật khẩu</button>
                    </form>
                <?php else: ?>
                    <a href="forgot-password-page.php" class="back-link"
                        style="display:block; text-align:center; margin-top:20px;">
                        ← Yêu cầu link mới
                    </a>
                <?php endif; ?>

            <?php else: ?>
                <div class="hero">
                    <h2>Hoàn Tất!</h2>
                    <p>Mật khẩu đã được thay đổi.</p>
                </div>
                <a href="index.php" class="submit-btn-link">ĐĂNG NHẬP NGAY</a>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
