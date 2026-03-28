<?php
session_start();
require_once __DIR__ . '/../services/ScoreService.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$scoreService = new ScoreService();
// Lấy dữ liệu dựa trên các tham số từ AJAX gửi lên (GET)
$data = $scoreService->getScorePageData($_SESSION['user_id'], $_GET);

// Trả về JSON để JavaScript xử lý
header('Content-Type: application/json');
echo json_encode($data);