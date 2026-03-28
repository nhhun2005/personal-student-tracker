<?php
include_once __DIR__ . "/../includes/connect-db.php";
include_once __DIR__ . "/../models/models.php";

class UserRepository
{

    public function isExisted($userId, $email)
    {
        global $conn;
        $sql = "SELECT EXISTS(SELECT 1 FROM users WHERE id= ? OR email=?) as existed";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $userId, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return (bool) $user['existed'];
    }
    public function save(User $user)
    {
        global $conn;
        $sql = "INSERT INTO users (username, password, full_name, student_id, email) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssss",
            $user->username,
            $user->password,
            $user->full_name,
            $user->student_id,
            $user->email
        );
        //ham nay tra ve so row bi tac dong nen coi nhu bool cung dc
        $success = $stmt->execute();
        //huu duyen dung toi userid trong tuong lai
        if ($success) {
            $user->id = $conn->insert_id;
        }
        $stmt->close();
        return $success;
    }
    public function getById($userId)
    {
        global $conn;
        $sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row) {
            $user = new User();
            $user->id = $row['id'];
            $user->username = $row['username'];
            $user->password = $row['password'];
            $user->full_name = $row['full_name'];
            $user->student_id = $row['student_id'];
            $user->email = $row['email'];
            $user->reset_token = $row['reset_token'];
            $user->reset_expire = $row['reset_expire'];
            return $user;
        }

        return null;
    }
    public function getByUsername($username)
    {
        global $conn;
        $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row) {
            $user = new User();
            $user->id = $row['id'];
            $user->username = $row['username'];
            $user->password = $row['password'];
            $user->full_name = $row['full_name'];
            $user->student_id = $row['student_id'];
            $user->email = $row['email'];
            $user->reset_token = $row['reset_token'];
            $user->reset_expire = $row['reset_expire'];
            return $user;
        }

        return null;
    }

    public function getByEmail($email)
    {
        global $conn;
        $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row) {
            $user = new User();
            $user->id = $row['id'];
            $user->username = $row['username'];
            $user->password = $row['password'];
            $user->full_name = $row['full_name'];
            $user->student_id = $row['student_id'];
            $user->email = $row['email'];
            $user->reset_token = $row['reset_token'];
            $user->reset_expire = $row['reset_expire'];
            return $user;
        }

        return null;
    }

    public function updateResetToken($email, $token, $expire)
    {
        global $conn;
        $sql = "UPDATE users SET reset_token = ?, reset_expire = ? WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $token, $expire, $email);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    public function getUserByValidToken($token)
    {
        global $conn;
        $sql = "SELECT id FROM users WHERE reset_token = ? AND reset_expire > NOW() LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }
    public function updatePasswordAndClearToken($userId, $newPassword)
    {
        global $conn;
        $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_expire = NULL WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $newPassword, $userId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

}



?>