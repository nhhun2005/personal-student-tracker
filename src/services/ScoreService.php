<?php
require_once __DIR__ . '/../repositories/ScoreRepository.php';

class ScoreService
{
    private $scoreRepo;

    public function __construct()
    {
        $this->scoreRepo = new ScoreRepository();
    }
    public function handleRequest($userId)
    {
        if (isset($_POST['save_score'])) {
            $this->handleScoreSave($userId);
        }
    }

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

    public function handleScoreSave($userId)
    {
        if (isset($_POST['save_score'])) {
            $semester = $_POST['semester_name'] ?? $_GET['semester'] ?? 'HK2 2025-2026';
            $names = $_POST['c_name'] ?? [];
            $credits = $_POST['c_credit'] ?? [];
            $scores = $_POST['c_score'] ?? [];

            $this->scoreRepo->updateCourseScores($userId, $semester, $names, $credits, $scores);
            header("Location: ../score-page.php?semester=" . urlencode($semester) . "&success=1");
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