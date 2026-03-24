<?php
session_start();
require_once './connect-db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $semester_name = $_POST['semester_name'];
    $today = date('Y-m-d');

    // Lấy ID học kỳ
    $stmt = $conn->prepare("SELECT id FROM semesters WHERE semester_name = ? AND user_id = ?");
    $stmt->bind_param("si", $semester_name, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sem_data = $result->fetch_assoc();

    if (!$sem_data) {
        // Nếu học kỳ chưa tồn tại trong bảng semesters của user này, tạo mới
        $stmt_add = $conn->prepare("INSERT INTO semesters (semester_name, user_id) VALUES (?, ?)");
        $stmt_add->bind_param("si", $semester_name, $user_id);
        $stmt_add->execute();
        $sem_id = $conn->insert_id;
    } else {
        $sem_id = $sem_data['id'];
    }

    if (isset($_POST['crit_id'])) {
        foreach ($_POST['crit_id'] as $i => $crit_id) {
            $score = floatval($_POST['event_scores'][$i]);
            $date = $_POST['event_dates'][$i];

            // 1. Server-side Validation: Ngày không được là tương lai
            if ($date > $today)
                continue;

            // 2. Server-side Validation: Tổng điểm
            $stmt_max = $conn->prepare("SELECT max_score FROM criterions WHERE id = ?");
            $stmt_max->bind_param("i", $crit_id);
            $stmt_max->execute();
            $max_allowed = $stmt_max->get_result()->fetch_assoc()['max_score'];

            $stmt_sum = $conn->prepare("SELECT SUM(score_value) as current_sum FROM evidences WHERE user_id = ? AND semester_id = ? AND criterion_id = ?");
            $stmt_sum->bind_param("iii", $user_id, $sem_id, $crit_id);
            $stmt_sum->execute();
            $current_sum = $stmt_sum->get_result()->fetch_assoc()['current_sum'] ?? 0;

            if (($current_sum + $score) > $max_allowed)
                continue;

            // 3. Xử lý file (Minh chứng là optional)
            $file_data = null;
            $has_file = false;
            if (isset($_FILES['evidences']['tmp_name'][$i]) && is_uploaded_file($_FILES['evidences']['tmp_name'][$i])) {
                $file_data = file_get_contents($_FILES['evidences']['tmp_name'][$i]);
                $has_file = true;
            }

            // 4. Insert vào DB
            $sql = "INSERT INTO evidences (content, score_value, event_date, criterion_id, user_id, semester_id) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_ins = $conn->prepare($sql);

            $null = NULL;
            // "b" cho blob, "d" cho double (score), "s" cho date, "i" cho id
            $stmt_ins->bind_param("bdssii", $null, $score, $date, $crit_id, $user_id, $sem_id);

            if ($has_file) {
                $stmt_ins->send_long_data(0, $file_data);
            }

            $stmt_ins->execute();
        }
    }
    header("Location: ../tpoint-page.php?semester=" . urlencode($semester_name) . "&msg=saved");
    exit();
}