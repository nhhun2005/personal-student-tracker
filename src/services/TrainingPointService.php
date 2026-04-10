<?php
require_once __DIR__ . '/../repositories/TrainingPointRepository.php';

class TrainingPointService
{
    private $tpRepo;

    public function __construct()
    {
        $this->tpRepo = new TrainingPointRepository();
    }

    public function getPageData($userId, $semesterName)
    {
        $semesterId = $this->tpRepo->getSemesterId($userId, $semesterName);
        if (!$semesterId) {
            return $this->getEmptyData($semesterName);
        }

        $evidenceData = $this->tpRepo->getEvidenceBySemesterId($userId, $semesterId);
        $sectionScores = [
            'I' => 0,
            'II' => 0,
            'III' => 0,
            'IV' => 0,
            'V' => 0
        ];

        foreach ($evidenceData as $row) {
            $critId = (int) $row['criterion_id'];
            $scoreValue = (float) $row['score_value'];

            if ($critId >= 1 && $critId <= 5) {
                $sectionScores['I'] += $scoreValue;
            } elseif ($critId >= 6 && $critId <= 7) {
                $sectionScores['II'] += $scoreValue;
            } elseif ($critId >= 8 && $critId <= 10) {
                $sectionScores['III'] += $scoreValue;
            } elseif ($critId >= 11 && $critId <= 13) {
                $sectionScores['IV'] += $scoreValue;
            } elseif ($critId >= 14 && $critId <= 17) {
                $sectionScores['V'] += $scoreValue;
            }
        }

        $finalScores = [
            1 => min($sectionScores['I'], 20),
            2 => min($sectionScores['II'], 25),
            3 => min($sectionScores['III'], 20),
            4 => min($sectionScores['IV'], 25),
            5 => min($sectionScores['V'], 10)
        ];

        $totalSum = array_sum($finalScores);

        return [
            'semester' => $semesterName,
            'evidence_data' => $evidenceData,
            'scores' => $finalScores,
            'final_score' => $totalSum,
            'classification' => $this->calculateClassification($totalSum)
        ];
    }

    public function handleApiRequest($userId)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $action = $_GET['action'] ?? '';
            if ($action !== 'fetch_tpoint_data') {
                throw new RuntimeException('Action GET không hợp lệ.');
            }

            $semesterName = $_GET['semester'] ?? '';
            if ($semesterName === '') {
                throw new RuntimeException('Thiếu học kỳ để tải dữ liệu.');
            }

            return $this->buildPayload($userId, $semesterName, '');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new RuntimeException('Method không được hỗ trợ.');
        }

        if (isset($_POST['create_tpoint_evidence'])) {
            return $this->handleCreateEvidence($userId);
        }

        if (isset($_POST['update_tpoint_evidence'])) {
            return $this->handleUpdateEvidence($userId);
        }

        if (isset($_POST['delete_tpoint_evidence'])) {
            return $this->handleDeleteEvidence($userId);
        }

        throw new RuntimeException('Request không hợp lệ hoặc thiếu action submit.');
    }

    private function handleCreateEvidence($userId)
    {
        $semesterName = $this->requireSemesterName($_POST['semester_name'] ?? '');
        $criterionId = isset($_POST['criterion_id']) ? (int) $_POST['criterion_id'] : 0;
        $score = $this->requireScore($_POST['event_score'] ?? null, 'Thiếu điểm khi lưu minh chứng.');
        $date = $this->requireValidDate($_POST['event_date'] ?? '');

        if ($criterionId <= 0) {
            throw new RuntimeException('Thiếu tiêu chí khi lưu minh chứng.');
        }

        // VALIDATION: Kiểm tra điểm vượt rào bằng Server-side
        $maxScore = $this->tpRepo->getCriterionMaxScore($criterionId);
        if ($score > $maxScore) {
            throw new RuntimeException("Điểm bị từ chối: $score lớn hơn điểm tối đa ($maxScore) của mục này.");
        }

        $semesterId = $this->ensureSemesterId($userId, $semesterName);
        $fileData = $this->readUploadedFile('evidence');
        $saved = $this->tpRepo->saveEvidence($userId, $semesterId, $criterionId, $score, $date, $fileData);

        if (!$saved) {
            error_log("TrainingPoint create failed for user_id={$userId}, semester={$semesterName}, criterion_id={$criterionId}");
            throw new RuntimeException('Không thể lưu minh chứng mới.');
        }

        return $this->buildPayload($userId, $semesterName, 'Lưu minh chứng thành công!');
    }

    private function handleUpdateEvidence($userId)
    {
        $semesterName = $this->requireSemesterName($_POST['semester_name'] ?? '');
        $evidenceId = isset($_POST['evidence_id']) ? (int) $_POST['evidence_id'] : 0;
        $score = $this->requireScore($_POST['update_event_score'] ?? null, 'Thiếu điểm khi cập nhật minh chứng.');
        $date = $this->requireValidDate($_POST['update_event_date'] ?? '');

        if ($evidenceId <= 0) {
            throw new RuntimeException('Thiếu ID minh chứng cần cập nhật.');
        }

        // VALIDATION: Kiểm tra điểm vượt rào bằng Server-side khi Cập nhật
        $maxScore = $this->tpRepo->getMaxScoreByEvidenceId($evidenceId, $userId);
        if ($score > $maxScore) {
            throw new RuntimeException("Điểm bị từ chối: $score lớn hơn điểm tối đa ($maxScore) của mục này.");
        }

        $fileData = $this->readUploadedFile('update_evidence');
        $replaceFile = $fileData !== null;
        $updated = $this->tpRepo->updateEvidence($userId, $evidenceId, $score, $date, $fileData, $replaceFile);

        if (!$updated) {
            error_log("TrainingPoint update failed for user_id={$userId}, evidence_id={$evidenceId}");
            throw new RuntimeException('Không thể cập nhật minh chứng.');
        }

        return $this->buildPayload($userId, $semesterName, 'Cập nhật minh chứng thành công!');
    }

    private function handleDeleteEvidence($userId)
    {
        $semesterName = $this->requireSemesterName($_POST['semester_name'] ?? '');
        $evidenceId = isset($_POST['evidence_id']) ? (int) $_POST['evidence_id'] : 0;

        if ($evidenceId <= 0) {
            throw new RuntimeException('Thiếu ID minh chứng cần xóa.');
        }

        $deleted = $this->tpRepo->deleteEvidence($userId, $evidenceId);
        if (!$deleted) {
            error_log("TrainingPoint delete failed for user_id={$userId}, evidence_id={$evidenceId}");
            throw new RuntimeException('Không thể xóa minh chứng hoặc minh chứng không tồn tại.');
        }

        return $this->buildPayload($userId, $semesterName, 'Xóa minh chứng thành công!');
    }

    private function buildPayload($userId, $semesterName, $message)
    {
        $pageData = $this->getPageData($userId, $semesterName);

        return [
            'ok' => true,
            'message' => $message,
            'data' => $pageData
        ];
    }

    private function ensureSemesterId($userId, $semesterName)
    {
        $semesterId = $this->tpRepo->getSemesterId($userId, $semesterName);
        if ($semesterId) {
            return $semesterId;
        }

        global $conn;
        $stmt = $conn->prepare("INSERT INTO semesters (semester_name, user_id) VALUES (?, ?)");
        if (!$stmt) {
            throw new RuntimeException('Không thể tạo học kỳ mới: ' . $conn->error);
        }

        $stmt->bind_param("si", $semesterName, $userId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Không thể tạo học kỳ mới: ' . $stmt->error);
        }

        $semesterId = $conn->insert_id;
        $stmt->close();

        return $semesterId;
    }

    private function requireSemesterName($semesterName)
    {
        if ($semesterName === '') {
            throw new RuntimeException('Thiếu thông lưu học kỳ.');
        }

        return $semesterName;
    }

    private function requireScore($scoreRaw, $message)
    {
        if ($scoreRaw === null || $scoreRaw === '') {
            throw new RuntimeException($message);
        }

        return (float) $scoreRaw;
    }

    private function requireValidDate($date)
    {
        if (!$this->isValidDate($date)) {
            throw new RuntimeException('Ngày minh chứng không hợp lệ.');
        }

        return $date;
    }

    private function readUploadedFile($fieldName)
    {
        if (!isset($_FILES[$fieldName]['tmp_name']) || !is_uploaded_file($_FILES[$fieldName]['tmp_name'])) {
            return null;
        }

        return file_get_contents($_FILES[$fieldName]['tmp_name']);
    }

    private function calculateClassification($score)
    {
        if ($score >= 90) {
            return "Xuất sắc";
        }
        if ($score >= 80) {
            return "Tốt";
        }
        if ($score >= 65) {
            return "Khá";
        }
        if ($score >= 50) {
            return "Trung bình";
        }
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

    private function isValidDate($date)
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date;
    }

    public static function sendJsonResponse($payload, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit();
    }

    public static function renderErrorPage($message, $details = '')
    {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');

        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $safeDetails = htmlspecialchars($details, ENT_QUOTES, 'UTF-8');

        echo "<!DOCTYPE html><html lang='vi'><head><meta charset='UTF-8'><title>Lỗi Training Point</title>";
        echo "<style>body{font-family:Arial,sans-serif;background:#f8fafc;padding:32px;color:#0f172a}.box{max-width:760px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;box-shadow:0 8px 24px rgba(15,23,42,.08)}h1{margin-top:0;color:#b91c1c}pre{white-space:pre-wrap;background:#f8fafc;border:1px solid #e2e8f0;padding:12px;border-radius:8px;overflow:auto}</style>";
        echo "</head><body><div class='box'><h1>Có lỗi khi xử lý minh chứng</h1><p>{$safeMessage}</p>";
        if ($safeDetails !== '') {
            echo "<pre>{$safeDetails}</pre>";
        }
        echo "<p>Quay lại <a href='../tpoint-page.php'>trang điểm rèn luyện</a> để thử lại.</p></div></body></html>";
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    try {
        if (!isset($_SESSION['user_id'])) {
            throw new RuntimeException('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
        }

        $tpService = new TrainingPointService();
        $payload = $tpService->handleApiRequest($_SESSION['user_id']);
        TrainingPointService::sendJsonResponse($payload);
    } catch (Throwable $e) {
        error_log('TrainingPointService error: ' . $e->getMessage());

        $wantsJson = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');
        if ($wantsJson || isset($_GET['action']) || isset($_POST['create_tpoint_evidence']) || isset($_POST['update_tpoint_evidence']) || isset($_POST['delete_tpoint_evidence'])) {
            TrainingPointService::sendJsonResponse([
                'ok' => false,
                'message' => $e->getMessage()
            ], 500);
        }

        TrainingPointService::renderErrorPage($e->getMessage(), $e->getTraceAsString());
    }
}