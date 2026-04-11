<?php
// process-hub.php: Xử lý logic liên quan đến hub page, bao gồm xác thực người dùng và tải dữ liệu cần thiết
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
// Tải thông tin người dùng từ session
$user = [
    'full_name' => $_SESSION['full_name'] ?? 'N/A',
    'student_id' => $_SESSION['student_id'] ?? 'N/A'
];

