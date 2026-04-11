<?php
require_once __DIR__ . '/../repositories/ScoreRepository.php';

class ScoreService
{
    private $scoreRepo;

    public function __construct()
    {
        $this->scoreRepo = new ScoreRepository();
    }
    //tương tự usẻ, cái này là hứng request
    public function handleRequest($userId)
    {
        if (isset($_POST['save_score'])) {
            $this->handleScoreSave($userId);
        } elseif (isset($_POST['delete_course'])) {
            $this->handleDeleteCourse($userId); 
        }
    }
//cái này là lấy dữ liệu trang để render
    public function getScorePageData($userId, $options)
    {
        $semester = $options['semester'] ?? 'HK2 2025-2026';
        $searchName = $options['q'] ?? '';
        $searchCredit = (isset($options['c']) && $options['c'] !== '') ? intval($options['c']) : null;
        $sortBy = in_array($options['sort'] ?? '', ['score', 'credits']) ? $options['sort'] : 'c.id';
        $sortOrder = strtoupper($options['order'] ?? '') === 'ASC' ? 'ASC' : 'DESC';
        $limit = 5;
        $page = max(1, intval($options['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $courses = $this->scoreRepo->getCourses($userId, $semester, $searchName, $searchCredit, $sortBy, $sortOrder, $limit, $offset);
        $totalItems = $this->scoreRepo->countCourses($userId, $semester, $searchName, $searchCredit);

        // Tính GPA trung bình
        $totalWeight = 0;
        $totalCredits = 0;
        foreach ($courses as $c) {
            $totalWeight += ($c['score'] * $c['credits']);
            $totalCredits += $c['credits'];
        }
        $gpa = ($totalCredits > 0) ? round($totalWeight / $totalCredits, 2) : 0;

        return [
            'courses' => $courses,
            'total_pages' => ceil($totalItems / $limit),
            'current_page' => $page,
            'gpa' => $gpa,
            'semester' => $semester
        ];
    }
//lưu điểm
    public function handleScoreSave($userId)
    {
        if (isset($_POST['save_score'])) {
            $semester = $_POST['semester_name'] ?? $_GET['semester'] ?? 'HK2 2025-2026';
            $names = $_POST['c_name'] ?? [];
            $raw_credits = $_POST['c_credit'] ?? [];
            $raw_scores = $_POST['c_score'] ?? [];

            // Backend Validation: Chặn triệt để dữ liệu sai lệch
            $credits = [];
            $scores = [];
            
            foreach ($raw_credits as $c) {
                // Tín chỉ phải >= 0
                $credits[] = max(0, intval($c)); 
            }
            
            foreach ($raw_scores as $s) {
                // Điểm phải >= 0 và <= 4.0
                $scores[] = max(0, min(4.0, floatval($s))); 
            }

            $this->scoreRepo->updateCourseScores($userId, $semester, $names, $credits, $scores);
            header("Location: ../score-page.php?semester=" . urlencode($semester) . "&success=1");
            exit();
        }
    }
//xóa môn
    public function handleDeleteCourse($userId)
    {
        if (isset($_POST['delete_course'])) {
            $courseId = intval($_POST['delete_course']);
            $semester = $_POST['semester_name'] ?? $_GET['semester'] ?? 'HK2 2025-2026';

            $this->scoreRepo->deleteCourse($courseId, $userId);
            header("Location: ../score-page.php?semester=" . urlencode($semester) . "&success=deleted");
            exit();
        }
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $scoreService = new ScoreService();
    $scoreService->handleRequest($_SESSION['user_id']);
}
?>