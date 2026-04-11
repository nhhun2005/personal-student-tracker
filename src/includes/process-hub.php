<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user = [
    'full_name' => $_SESSION['full_name'] ?? 'N/A',
    'student_id' => $_SESSION['student_id'] ?? 'N/A'
];

