<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<!doctype html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <title>Quên mật khẩu - Personal Student Tracker</title>
  <link rel="stylesheet" href="./css/auth-style.css" />
</head>

<body>
  <div class="flex-container">
    <div class="authentication-container">
      <?php if (!isset($_GET['is_sent'])): ?>
        <div class="hero">
          <h2>Quên Mật Khẩu</h2>
          <p>Password Recovery</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
          <div class="status-msg error-msg">Hệ thống gửi mail đang gặp sự cố.</div>
        <?php endif; ?>

        <form method="POST" action="./services/UserService.php">
          <div class="input-box">
            <label>Email của bạn:</label>
            <input type="email" name="email" placeholder="ngoctrinh@example.com" required />
          </div>
          <button type="submit" name="forgot_password_submit">Gửi yêu cầu đặt lại</button>
        </form>
        <a href="index.php" class="back-link">← Quay lại đăng nhập</a>

      <?php else: ?>
        <div class="success-step" style="text-align: center;">
          <div class="hero">
            <h2>Kiểm tra Email</h2>
            <p>Liên kết đã được gửi</p>
          </div>
          <p>Chúng tôi đã gửi một liên kết bảo mật vào hộp thư của bạn.</p>
          <a href="index.php" class="submit-btn-link">Quay lại đăng nhập</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>

</html>
