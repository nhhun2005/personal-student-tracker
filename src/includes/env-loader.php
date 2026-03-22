<?php
// includes/env-loader.php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

try {
    // __DIR__ là /var/www/html/includes
    // __DIR__ . '/..' sẽ trỏ về /var/www/html (nơi có file .env mới chuyển vào)
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (Exception $e) {
    // Đừng lo, lệnh này giúp bạn biết chính xác nó đang tìm ở đâu nếu vẫn lỗi
    die("Lỗi: Không tìm thấy file .env tại " . realpath(__DIR__ . '/../'));
}

if (!function_exists('env')) {
    function env($key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }
}