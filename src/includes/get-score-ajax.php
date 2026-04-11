<?php
session_start();
require_once __DIR__ . '/../services/ScoreService.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$scoreService = new ScoreService();
//lấy dữ liệu điểm của người dùng từ csdl, dùng bằng user_id
$data = $scoreService->getScorePageData($_SESSION['user_id'], $_GET);

// Trả về JSON để JavaScript xử lý -> đang xài aja"x" dựa trên json thay vì xml
header('Content-Type: application/json');
echo json_encode($data);