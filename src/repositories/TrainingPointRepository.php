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

    /**
     * Lấy ID học kỳ của một User cụ thể
     */
    public function getSemesterId($userId, $semesterName)
    {
        $stmt = $this->prepareOrFail("SELECT id FROM semesters WHERE user_id = ? AND semester_name = ? LIMIT 1");
        $stmt->bind_param("is", $userId, $semesterName);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result ? $result['id'] : null;
    }

    /**
     * Lấy danh sách minh chứng dựa trên UserId và SemesterId
     * Đảm bảo lấy đủ các cột để Service tính điểm và JS render giao diện
     */
    public function getEvidenceBySemesterId($userId, $semesterId)
    {
        // Sử dụng LENGTH(content) để biết có file hay không mà không cần load toàn bộ BLOB vào RAM ở bước này
        $sql = "SELECT id, criterion_id, score_value, event_date, 
                       (CASE WHEN content IS NOT NULL AND LENGTH(content) > 0 THEN 1 ELSE 0 END) as has_content 
                FROM evidences 
                WHERE user_id = ? AND semester_id = ?";

        $stmt = $this->prepareOrFail($sql);
        $stmt->bind_param("ii", $userId, $semesterId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }

    /**
     * Lưu minh chứng mới vào Database
     */
    public function saveEvidence($userId, $semesterId, $critId, $score, $date, $fileData = null)
    {
        $sql = "INSERT INTO evidences (content, score_value, event_date, criterion_id, user_id, semester_id) 
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->prepareOrFail($sql);

        // Chuẩn bị dữ liệu: content là kiểu blob (b) nên truyền NULL trước
        $null = NULL;
        $stmt->bind_param("bdssii", $null, $score, $date, $critId, $userId, $semesterId);

        // Nếu có dữ liệu file, gửi dữ liệu dài (long data) vào vị trí tham số 0 (cột content)
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
}
