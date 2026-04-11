<?php
include_once __DIR__ . "/../includes/connect-db.php";
include_once __DIR__ . "/../models/models.php";

class TrainingPointRepository
{
    private function prepareOrFail($sql)
    {
        global $conn;
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("SQL prepare failed: " . $conn->error);
        }

        return $stmt;
    }

    public function getSemesterId($userId, $semesterName)
    {
        $stmt = $this->prepareOrFail("SELECT id FROM semesters WHERE user_id = ? AND semester_name = ? LIMIT 1");
        $stmt->bind_param("is", $userId, $semesterName);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result ? $result['id'] : null;
    }

    // [CẬP NHẬT]: Lấy thêm max_score từ bảng criterions
    public function getEvidenceBySemesterId($userId, $semesterId)
    {
        $sql = "SELECT e.id, e.criterion_id, e.score_value, e.event_date, 
                       (CASE WHEN e.content IS NOT NULL AND LENGTH(e.content) > 0 THEN 1 ELSE 0 END) as has_content,
                       c.max_score
                FROM evidences e
                JOIN criterions c ON e.criterion_id = c.id
                WHERE e.user_id = ? AND e.semester_id = ?";

        $stmt = $this->prepareOrFail($sql);
        $stmt->bind_param("ii", $userId, $semesterId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }

    public function saveEvidence($userId, $semesterId, $critId, $score, $date, $fileData = null)
    {
        $sql = "INSERT INTO evidences (content, score_value, event_date, criterion_id, user_id, semester_id) 
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->prepareOrFail($sql);
        $null = NULL;
        $stmt->bind_param("bdssii", $null, $score, $date, $critId, $userId, $semesterId);

        if ($fileData !== null) {
            $stmt->send_long_data(0, $fileData);
        }

        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function updateEvidence($userId, $evidenceId, $score, $date, $fileData = null, $replaceFile = false)
    {
        if ($replaceFile) {
            $sql = "UPDATE evidences 
                    SET content = ?, score_value = ?, event_date = ? 
                    WHERE id = ? AND user_id = ? 
                    LIMIT 1";
            $stmt = $this->prepareOrFail($sql);
            $stmt->bind_param("sdsii", $fileData, $score, $date, $evidenceId, $userId);
        } else {
            $sql = "UPDATE evidences 
                    SET score_value = ?, event_date = ? 
                    WHERE id = ? AND user_id = ? 
                    LIMIT 1";
            $stmt = $this->prepareOrFail($sql);
            $stmt->bind_param("dsii", $score, $date, $evidenceId, $userId);
        }

        if (!$stmt->execute()) {
            throw new RuntimeException("SQL execute failed: " . $stmt->error);
        }
        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        return $affectedRows >= 0;
    }

    public function deleteEvidence($userId, $evidenceId)
    {
        $stmt = $this->prepareOrFail("DELETE FROM evidences WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->bind_param("ii", $evidenceId, $userId);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        return $affectedRows > 0;
    }

    // Các hàm dưới đây giữ nguyên (dù không còn dùng để chặn lỗi cứng, nhưng có thể cần thiết về sau)
    public function getCriterionMaxScore($critId)
    {
        $stmt = $this->prepareOrFail("SELECT max_score FROM criterions WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $critId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result ? (float) $result['max_score'] : 0;
    }
    // Get maximum points based on existing evidence.

    public function getMaxScoreByEvidenceId($evidenceId, $userId)
    {
        $sql = "SELECT c.max_score 
                FROM evidences e 
                JOIN criterions c ON e.criterion_id = c.id 
                WHERE e.id = ? AND e.user_id = ? LIMIT 1";
        $stmt = $this->prepareOrFail($sql);
        $stmt->bind_param("ii", $evidenceId, $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result ? (float) $result['max_score'] : 0;
    }
}
?>