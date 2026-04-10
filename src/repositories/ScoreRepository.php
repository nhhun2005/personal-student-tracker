<?php
include_once __DIR__ . "/../includes/connect-db.php";
include_once __DIR__ . "/../models/models.php";

class ScoreRepository
{
    public function getCourses($userId, $semester, $searchName, $searchCredit, $sortBy, $sortOrder, $limit, $offset)
    {
        global $conn;
        $where = ["s.user_id = ?"];
        $params = [$userId];
        $types = "i";

        if ($semester !== 'Tất cả') {
            $where[] = "s.semester_name = ?";
            $params[] = $semester;
            $types .= "s";
        }

        if (!empty($searchName)) {
            $where[] = "c.course_name LIKE ?";
            $params[] = "%$searchName%";
            $types .= "s";
        }

        if ($searchCredit !== null) {
            $where[] = "c.credits = ?";
            $params[] = $searchCredit;
            $types .= "i";
        }

        $whereSql = implode(" AND ", $where);
        $sql = "SELECT c.*, s.semester_name 
                FROM courses c 
                JOIN semesters s ON c.semester_id = s.id 
                WHERE $whereSql 
                ORDER BY $sortBy $sortOrder 
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function countCourses($userId, $semester, $searchName, $searchCredit)
    {
        global $conn;
        $where = ["s.user_id = ?"];
        $params = [$userId];
        $types = "i";

        if ($semester !== 'Tất cả') {
            $where[] = "s.semester_name = ?";
            $params[] = $semester;
            $types .= "s";
        }

        if (!empty($searchName)) {
            $where[] = "c.course_name LIKE ?";
            $params[] = "%$searchName%";
            $types .= "s";
        }

        if ($searchCredit !== null) {
            $where[] = "c.credits = ?";
            $params[] = $searchCredit;
            $types .= "i";
        }

        $whereSql = implode(" AND ", $where);
        $sql = "SELECT COUNT(*) as total FROM courses c JOIN semesters s ON c.semester_id = s.id WHERE $whereSql";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'];
    }

    public function updateCourseScores($userId, $semesterName, $courseNames, $credits, $scores)
    {
        global $conn;

        // 1. Lấy hoặc tạo Semester ID
        $stmt = $conn->prepare("SELECT id FROM semesters WHERE user_id = ? AND semester_name = ? LIMIT 1");
        $stmt->bind_param("is", $userId, $semesterName);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if ($res) {
            $semesterId = $res['id'];
        } else {
            $stmt = $conn->prepare("INSERT INTO semesters (user_id, semester_name) VALUES (?, ?)");
            $stmt->bind_param("is", $userId, $semesterName);
            $stmt->execute();
            $semesterId = $conn->insert_id;
        }

        // 2. Xóa môn cũ của kỳ đó để ghi đè
        $stmt = $conn->prepare("DELETE FROM courses WHERE semester_id = ?");
        $stmt->bind_param("i", $semesterId);
        $stmt->execute();

        // 3. Chèn dữ liệu mới
        $stmt = $conn->prepare("INSERT INTO courses (course_name, credits, score, semester_id) VALUES (?, ?, ?, ?)");
        foreach ($courseNames as $i => $name) {
            if (empty($name))
                continue;
            $stmt->bind_param("sidi", $name, $credits[$i], $scores[$i], $semesterId);
            $stmt->execute();
        }
        return true;
    }
    public function deleteCourse($courseId, $userId)
    {
        global $conn;
        // Xóa thông qua JOIN để đảm bảo user chỉ có thể xóa môn của chính mình
        $sql = "DELETE c FROM courses c
                JOIN semesters s ON c.semester_id = s.id
                WHERE c.id = ? AND s.user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $courseId, $userId);
        return $stmt->execute();
    }
}