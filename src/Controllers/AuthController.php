<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Core\Session;
use App\Helpers\Security;
use App\Helpers\Utils;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AuthController extends Controller
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function showLogin()
    {
        if (Session::has('user_id')) {
            $this->redirect('/dashboard');
        }
        $this->view('login', ['csrf_token' => Session::generateCsrfToken()]);
    }

    public function login()
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = $this->userModel->findByEmail($email);

        if ($user && Security::verifyPassword($password, $user['password_hash'])) {
            if ($user['account_status'] === 'pending') {
                Session::set('verify_email', $email);
                $this->redirect('/verify');
            } elseif ($user['account_status'] === 'suspended') {
                $this->view('login', ['error' => 'Your account has been suspended.', 'csrf_token' => Session::generateCsrfToken()]);
            } else {
                Session::set('user_id', $user['id']);
                Session::set('username', $user['username']);
                Session::set('full_name', $user['full_name']);
                $this->userModel->updateLoginTimestamp($user['id']);
                $this->redirect('/dashboard');
            }
        } else {
            $this->view('login', ['error' => 'Invalid email or password.', 'csrf_token' => Session::generateCsrfToken()]);
        }
    }

    public function showRegister()
    {
        $this->view('register', ['csrf_token' => Session::generateCsrfToken()]);
    }

    public function register()
    {
        $data = [
            'full_name' => Utils::sanitize($_POST['full_name'] ?? ''),
            'email' => Utils::sanitize($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            'referral_code' => Utils::sanitize($_POST['referral_code'] ?? '')
        ];

        if ($data['password'] !== $data['confirm_password']) {
            return $this->view('register', ['error' => 'Passwords do not match.', 'csrf_token' => Session::generateCsrfToken()]);
        }

        if ($this->userModel->findByEmail($data['email'])) {
            return $this->view('register', ['error' => 'Email already exists.', 'csrf_token' => Session::generateCsrfToken()]);
        }

        $username = strtolower(str_replace(' ', '_', $data['full_name'])) . '_' . rand(100, 999);
        $referralCode = substr(md5($username), 0, 8);
        $verificationCode = Security::generateRandomCode(6);

        $referredBy = null;
        if (!empty($data['referral_code'])) {
            $referrer = $this->userModel->findByReferralCode($data['referral_code']);
            if ($referrer) {
                $referredBy = $referrer['id'];
            }
        }

        $userData = [
            'username' => $username,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'password_hash' => Security::hashPassword($data['password']),
            'referral_code' => $referralCode,
            'referred_by' => $referredBy,
            'account_status' => 'pending',
            'verification_code' => $verificationCode,
            'verification_expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ];

        $this->userModel->create($userData);
        $this->sendVerificationEmail($data['email'], $verificationCode);

        Session::set('verify_email', $data['email']);
        $this->redirect('/verify');
    }

    public function showVerify()
    {
        if (!Session::has('verify_email')) {
            $this->redirect('/login');
        }
        $this->view('verify', ['csrf_token' => Session::generateCsrfToken()]);
    }

    public function verify()
    {
        $email = Session::get('verify_email');
        $code = $_POST['code'] ?? '';

        if ($this->userModel->verify($email, $code)) {
            Session::remove('verify_email');
            $this->redirect('/login?verified=1');
        } else {
            $this->view('verify', ['error' => 'Invalid or expired verification code.', 'csrf_token' => Session::generateCsrfToken()]);
        }
    }

    public function logout()
    {
        Session::destroy();
        $this->redirect('/login');
    }

    private function sendVerificationEmail($email, $code)
    {
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.example.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'] ?? 'user@example.com';
            $mail->Password   = $_ENV['SMTP_PASS'] ?? 'password';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $_ENV['SMTP_PORT'] ?? 587;

            // Recipients
            $mail->setFrom($_ENV['MAIL_FROM'] ?? 'no-reply@cryptominer.com', 'CryptoMiner ERP');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Verify your account';
            $mail->Body    = "Your verification code is: <b>$code</b>";

            $mail->send();
        } catch (Exception $e) {
            Utils::log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}", 'error');
        }
    }
}
