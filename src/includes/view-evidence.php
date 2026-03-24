<?php
// TẮT TẤT CẢ THÔNG BÁO LỖI để tránh làm hỏng dữ liệu ảnh nhị phân
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once './connect-db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Truy cập bị từ chối.");
}

$evidence_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Truy vấn lấy dữ liệu
$stmt = $conn->prepare("SELECT content FROM evidences WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $evidence_id, $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($content);
    $stmt->fetch();

    // Xóa bộ đệm nếu có
    if (ob_get_length())
        ob_end_clean();

    // Tự động đoán MIME type từ nội dung file
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->buffer($content);

    // Gửi Header chuẩn
    header("Content-Type: " . $mime_type);
    header("Content-Length: " . strlen($content));
    header("Content-Disposition: inline; filename='evidence_" . $evidence_id . "'");
    header("Cache-Control: public, max-age=86400"); // Lưu cache 1 ngày cho nhanh

    echo $content;
    exit();
} else {
    header("HTTP/1.1 404 Not Found");
    exit("Không tìm thấy minh chứng.");
}