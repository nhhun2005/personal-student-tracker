<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/connect-db.php';
require_once __DIR__ . '/includes/env-loader.php';

$error = "";
$is_sent = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
  $email = trim($_POST['email']);

  // 1. Kiểm tra User
  $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ? LIMIT 1");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();

  if ($user) {
    // 2. Tạo Token (15 phút)
    $token = bin2hex(random_bytes(32));
    $expire = date("Y-m-d H:i:s", strtotime("+15 minutes"));

    // 3. Cập nhật DB
    $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_expire = ? WHERE email = ?");
    $update->bind_param("sss", $token, $expire, $email);
    $update->execute();

    // 4. Gửi Mail qua Resend SDK
    $apiKey = env('RESEND_API_KEY');
    $resend = Resend::client($apiKey);
    $resetLink = "http://localhost:8080/reset-password.php?token=" . $token;

    try {
      $resend->emails->send([
        'from' => 'Student Tracker <onboarding@resend.dev>',
        'to' => [$email],
        'subject' => 'Khôi phục mật khẩu - Student Tracker',
        'html' => "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e1e1e1; border-radius: 10px;'>
            <h2 style='color: #1d71bb; text-align: center;'>Khôi phục mật khẩu</h2>
            <p>Chào <strong>{$user['full_name']}</strong>,</p>
            <p>Bạn đã yêu cầu đặt lại mật khẩu cho hệ thống Student Tracker. Vui lòng nhấn vào nút bên dưới để tiếp tục:</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$resetLink}' 
                   style='background-color: #1d71bb; 
                          color: #ffffff; 
                          padding: 15px 25px; 
                          text-decoration: none; 
                          border-radius: 8px; 
                          font-weight: bold; 
                          display: inline-block;'>
                   ĐẶT LẠI MẬT KHẨU NGAY
                </a>
            </div>

            <p style='color: #666; font-size: 12px;'>Nếu nút trên không hoạt động, bạn có thể copy link này dán vào trình duyệt:</p>
            <p style='color: #1d71bb; font-size: 11px; word-break: break-all;'>{$resetLink}</p>
            
            <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
            <p style='font-size: 11px; color: #999;'>Link này sẽ hết hạn sau 15 phút. Nếu bạn không yêu cầu, vui lòng bỏ qua email này.</p>
        </div>
    ",
      ]);
      $is_sent = true;
    } catch (\Exception $e) {
      error_log("Mail Error: " . $e->getMessage());
      $error = "Hệ thống gửi mail đang gặp sự cố.";
    }
  } else {
    // Bảo mật: Vẫn báo thành công để tránh dò email
    $is_sent = true;
  }
}
?>

<!doctype html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <title>Quên mật khẩu - Personal Student Tracker</title>
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

      <?php if (!$is_sent): ?>
        <div class="hero">
          <h2>Quên Mật Khẩu</h2>
          <p>Password Recovery</p>
        </div>

        <?php if ($error): ?>
          <div class="status-msg error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <div style="text-align: center; color: #64748b; font-size: 0.9rem; margin-bottom: 20px;">
          Nhập email của bạn để lấy lại mật khẩu.
        </div>

        <form method="POST" action="">
          <div class="input-box">
            <label>Email của bạn:</label>
            <input type="email" name="email" placeholder="ngoctrinh@example.com" required />
          </div>
          <button type="submit">Gửi yêu cầu đặt lại</button>
        </form>

        <a href="index.php" class="back-link">← Quay lại đăng nhập</a>

      <?php else: ?>
        <div class="success-step">
          <div class="success-icon-container">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
              stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
          </div>
          <div class="hero">
            <h2>Kiểm tra Email</h2>
            <p>Liên kết đã được gửi</p>
          </div>
          <div style="text-align: center; color: #64748b; margin-bottom: 20px;">
            Chúng tôi đã gửi một liên kết bảo mật vào hộp thư của bạn. Vui lòng nhấn vào đó để tạo mật khẩu mới.
          </div>
          <a href="index.php" class="submit-btn-link">Quay lại đăng nhập</a>

          <p style="margin-top: 20px; font-size: 0.85rem; color: #94a3b8;">
            Không nhận được email? <a href="forgot-password-page.php" style="color: #1d71bb; font-weight: bold;">Gửi
              lại</a>
          </p>
        </div>
      <?php endif; ?>

    </div>
  </div>
</body>

</html>