<?php
require_once __DIR__ . '/../repositories/TrainingPointRepository.php';

class TrainingPointService
{
    private $tpRepo;

    public function __construct()
    {
        $this->tpRepo = new TrainingPointRepository();
    }

    public function handleRequest($userId)
    {
        if (isset($_POST['save_tpoint'])) {
            $semesterName = $_POST['semester_name'];

            // 1. Lấy hoặc tạo Semester ID
            $semesterId = $this->tpRepo->getSemesterId($userId, $semesterName);
            if (!$semesterId) {
                global $conn;
                $stmt = $conn->prepare("INSERT INTO semesters (semester_name, user_id) VALUES (?, ?)");
                $stmt->bind_param("si", $semesterName, $userId);
                $stmt->execute();
                $semesterId = $conn->insert_id;
            }

            // 2. Xử lý danh sách minh chứng từ Form (ĐÃ SỬA TÊN BIẾN THEO JS)
            if (isset($_POST['crit_id'])) {
                foreach ($_POST['crit_id'] as $i => $critId) {
                    // Lấy đúng tên mảng từ JavaScript: event_scores và event_dates
                    $score = isset($_POST['event_scores'][$i]) ? floatval($_POST['event_scores'][$i]) : 0;
                    $date = isset($_POST['event_dates'][$i]) ? $_POST['event_dates'][$i] : date('Y-m-d');

                    // Xử lý file nếu có (Tên input file trong JS là evidences[])
                    $fileData = null;
                    if (isset($_FILES['evidences']['tmp_name'][$i]) && is_uploaded_file($_FILES['evidences']['tmp_name'][$i])) {
                        $fileData = file_get_contents($_FILES['evidences']['tmp_name'][$i]);
                    }

                    // Lưu vào Repo
                    $this->tpRepo->saveEvidence($userId, $semesterId, $critId, $score, $date, $fileData);
                }
            }

            header("Location: ../tpoint-page.php?semester=" . urlencode($semesterName) . "&msg=saved");
            exit();
        }
    }

    public function getPageData($userId, $semesterName)
    {
        $semesterId = $this->tpRepo->getSemesterId($userId, $semesterName);
        if (!$semesterId) {
            return $this->getEmptyData($semesterName);
        }

        $evidence_data = $this->tpRepo->getEvidenceBySemesterId($userId, $semesterId);

        // Mảng lưu trữ điểm thô của 5 mục lớn
        $section_scores = [
            'I' => 0,
            'II' => 0,
            'III' => 0,
            'IV' => 0,
            'V' => 0
        ];

        foreach ($evidence_data as $row) {
            $critId = (int) $row['criterion_id'];
            $val = (float) $row['score_value'];

            // Phân loại crit_id vào các mục lớn (Dựa trên CSDL của bạn)
            if ($critId >= 1 && $critId <= 4)
                $section_scores['I'] += $val;
            elseif ($critId >= 5 && $critId <= 8)
                $section_scores['II'] += $val;
            elseif ($critId >= 9 && $critId <= 11)
                $section_scores['III'] += $val;
            elseif ($critId >= 12 && $critId <= 14)
                $section_scores['IV'] += $val;
            elseif ($critId >= 15 && $critId <= 17)
                $section_scores['V'] += $val;
        }

        // Áp dụng giới hạn theo yêu cầu của bạn
        $final_scores = [
            1 => min($section_scores['I'], 20),
            2 => min($section_scores['II'], 25),
            3 => min($section_scores['III'], 20),
            4 => min($section_scores['IV'], 25),
            5 => min($section_scores['V'], 10)
        ];

        $total_sum = array_sum($final_scores);

        return [
            'semester' => $semesterName,
            'evidence_data' => $evidence_data,
            'scores' => $final_scores,
            'final_score' => $total_sum,
            'classification' => $this->calculateClassification($total_sum)
        ];
    }

    private function calculateClassification($score)
    {
        if ($score >= 90)
            return "Xuất sắc";
        if ($score >= 80)
            return "Tốt";
        if ($score >= 65)
            return "Khá";
        if ($score >= 50)
            return "Trung bình";
        return "Yếu/Kém";
    }

    private function getEmptyData($semesterName)
    {
        return [
            'semester' => $semesterName,
            'evidence_data' => [],
            'scores' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
            'final_score' => 0,
            'classification' => 'N/A'
        ];
    }
}
// Khởi chạy hệ thống điều hướng tự động
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $tpService = new TrainingPointService();
    $tpService->handleRequest($_SESSION['user_id']);
}