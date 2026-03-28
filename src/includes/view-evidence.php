<?php
session_start();
include_once __DIR__ . "/connect-db.php";

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    die("Truy cập bị từ chối.");
}

$evidence_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Chỉ cho phép sinh viên xem minh chứng của chính mình
$stmt = $conn->prepare("SELECT content FROM evidences WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $evidence_id, $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($content);
    $stmt->fetch();

    if (!empty($content)) {
        // Tự động nhận diện định dạng cơ bản (giả định là ảnh hoặc PDF)
        // Nếu bạn có lưu mime_type trong DB thì dùng cái đó sẽ chuẩn hơn
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=minh-chung-" . $evidence_id);
        echo $content;
    } else {
        echo "Không có dữ liệu file cho minh chứng này.";
    }
} else {
    echo "Không tìm thấy minh chứng hoặc bạn không có quyền xem.";
}
$stmt->close();