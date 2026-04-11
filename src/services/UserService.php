<?php
require_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/../includes/connect-db.php';
include_once __DIR__ . '/../models/models.php';
include_once __DIR__ . '/../repositories/UserRepository.php';
use Dotenv\Dotenv;

class UserService
{
    private $userRepo;
    private $resend;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();
        $apiKey = $_ENV['RESEND_API_KEY'];
        $this->resend = Resend::client($apiKey);
    }
//hàm này sẽ hoạt động giống hứng request
    public function handleRequest()
    {
        if (isset($_POST['login_submit'])) {
            $this->login();
        } elseif (isset($_POST['signup_submit'])) {
            $this->register();
        } elseif (isset($_POST['forgot_password_submit'])) {
            $this->forgotPassword();
        } elseif (isset($_POST['reset_password_submit'])) {
            $this->resetPassword();
        } else {
            header("Location: ../index.php");
            exit();
        }
    }
//xử lý đky
    private function register()
    {
        $username = $_POST['username_signup'] ?? '';
        $password = $_POST['signup_password'] ?? '';
        $confirm_password = $_POST['confirm_signup_password'] ?? '';
        $email = $_POST['email-input'] ?? '';
        $full_name = $_POST['fullname'] ?? '';
        $student_id = $_POST['studentid'] ?? '';


        if ($password !== $confirm_password) {
            header("Location: ../index.php?error=password_mismatch");
            exit();
        }

        //email hoac username co nguoi dung
        if ($this->userRepo->isExisted(null, $email) || $this->userRepo->getByUsername($username)) {
            header("Location: ../index.php?error=user_exists");
            exit();
        }

        $user = new User();
        $user->username = $username;
        $user->password = $password;
        $user->email = $email;
        $user->full_name = $full_name;
        $user->student_id = $student_id;

        if ($this->userRepo->save($user)) {
            header("Location: ../index.php?success=registered");
        } else {
            header("Location: ../index.php?error=failed");
        }
        exit();
    }
//xử lý đăng nhập
    private function login()
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['login_password'] ?? '';

        $user = $this->userRepo->getByUsername($username);

        if ($user && $user->password === $password) {
            session_start();
            //may cai nay duoc luu o sv, cai luu o user la session id, dua tren cai sesison id ma server biet la cai nao
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            $_SESSION["full_name"] = $user->full_name;
            $_SESSION["student_id"] = $user->student_id;

            //vao trang hub
            header("Location: ../hub-page.php");
        } else {
            //sai mat khau 
            header("Location: ../index.php?error=invalid_credentials");
        }
        exit();
    }
    //xử lý quên mk
    private function forgotPassword()
    {
        $email = $_POST['email'] ?? '';
        $user = $this->userRepo->getByEmail($email);

        if ($user) {
            //tao token ngau nhien
            $token = bin2hex(random_bytes(32));
            $expire = date("Y-m-d H:i:s", strtotime("+1 hour"));

            //luu token vao db
            $this->userRepo->updateResetToken($email, $token, $expire);

            //gui email chua token
            $resetLink = "http://localhost:8080/reset-password.php?token=" . $token;

            try {
                $this->resend->emails->send([
                    'from' => 'reset-password@nhhun2005.id.vn',
                    'to' => $email,
                    'subject' => 'Khôi phục mật khẩu - Personal Student Tracker',
                    'html' => "
                        <h3>Yêu cầu khôi phục mật khẩu</h3>
                        <p>Chào {$user->full_name},</p>
                        <p>Có vẻ bạn đã quên đi mật khẩu của mình, quá gà, hãy nhấn vào link bên dưới để đặt lại mật khẩu của mình nhé!</p>
                        <p><a href='{$resetLink}' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Đổi mật khẩu ngay</a></p>
                        <p>Link này sẽ hết hạn trong 1 giờ.</p>
                    ",
                ]);
                header("Location: ../forgot-password-page.php?is_sent=1");
            } catch (\Exception $e) {
                header("Location: ../forgot-password-page.php?error=send_failed");
            }
        } else {
            header("Location: ../forgot-password-page.php?is_sent=1"); //van chuyen trang de tranh do email
        }
        exit();
    }
    //đổi mk qua tính năng quên mk
    private function resetPassword()
    {
        $token = $_POST['token'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if ($new_pass !== $confirm_pass) {
            header("Location: ../reset-password.php?token=$token&error=password_mismatch");
            exit();
        }

        $user = $this->userRepo->getUserByValidToken($token);

        if ($user) {
            $success = $this->userRepo->updatePasswordAndClearToken($user['id'], $new_pass);

            if ($success) {
                header("Location: ../reset-password.php?success=1");
            } else {
                header("Location: ../reset-password.php?token=$token&error=update_failed");
            }
        } else {
            header("Location: ../reset-password.php?error=invalid_token");
        }
        exit();
    }
    //kiểm chứng token coi đúng ko mới cho đổi mk
    public function validateToken($token)
    {
        if (empty($token)) {
            return "Mã xác thực không hợp lệ.";
        }

        $user = $this->userRepo->getUserByValidToken($token);

        if (!$user) {
            return "Liên kết không hợp lệ hoặc đã hết hạn.";
        }

        return null;
    }
}
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    (isset($_POST['login_submit']) ||
        isset($_POST['signup_submit']) ||
        isset($_POST['forgot_password_submit']) ||
        isset($_POST['reset_password_submit']))
) {
    $userService = new UserService();
    $userService->handleRequest();
}