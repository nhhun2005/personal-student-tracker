<?php
session_start();
require_once './connect-db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    //signup logic
    if (isset($_POST['signup_submit'])) {
        $full_name = trim($_POST['fullname']);
        $student_id = trim($_POST['studentid']);
        $email = trim($_POST['email-input']);
        $username = trim($_POST['username_signup']);
        $password = $_POST['signup_password'];
        $confirm = $_POST['confirm_signup_password'];

        if (empty($full_name) || empty($student_id) || empty($email) || empty($username) || empty($password)) {
            $error = "Vui lòng điền đầy đủ các trường bắt buộc.";
        } elseif ($password !== $confirm) {
            $error = "Mật khẩu xác nhận không khớp.";
        } else {
            //existed?
            $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR student_id = ? OR email = ?");
            $check->bind_param("sss", $username, $student_id, $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $error = "Tên đăng nhập, MSSV hoặc Email đã tồn tại.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT); //using Bcrypt
                $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, student_id, email) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $username, $hashed_password, $full_name, $student_id, $email);

                if ($stmt->execute()) {
                    // Đăng ký xong nên chuyển hướng hoặc báo thành công
                    header("Location: ../index.php?msg=signup_success");
                    exit();
                } else {
                    error_log("Signup Error: " . $stmt->error);
                    $error = "Lỗi hệ thống, thử lại sau.";
                }
            }
        }
    }

    //sign in logic
    if (isset($_POST['login_submit'])) {
        $username = trim($_POST['username']);
        $password = $_POST['login_password'];

        if (!empty($username) && !empty($password)) {
            $stmt = $conn->prepare("SELECT id, password, full_name FROM users WHERE username = ? LIMIT 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    session_regenerate_id(true);
                    header("Location: ../hub-page.php");
                    exit();
                }
            }
            $error = "Tên đăng nhập hoặc mật khẩu không chính xác.";
        }
    }
}

if (!empty($error)) {
    $_SESSION['auth_error'] = $error;
    header("Location: ../index.php");
    exit();
}
?>