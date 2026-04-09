<?php
/**
 * Logout Logic - Enterprise Standard
 * Đảm bảo xóa sạch Session ở cả Server và Client (Cookie)
 */

session_start();

// 1. Xóa tất cả các biến lưu trong mảng $_SESSION
$_SESSION = array();

// 2. Nếu muốn xóa sạch Cookie Session ở trình duyệt (PHPSESSID)
// Đây là bước quan trọng để bảo mật tuyệt đối
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Hủy bỏ phiên làm việc trên Server
session_destroy();

// 4. Chuyển hướng người dùng về trang đăng nhập
// Sử dụng đường dẫn tuyệt đối hoặc tương đối tùy cấu trúc thư mục của bạn
header("Location: ../index.php");
exit();
