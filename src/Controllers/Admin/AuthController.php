<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Admin;
use App\Core\Session;
use App\Helpers\Security;

class AuthController extends Controller
{
    private $adminModel;

    public function __construct()
    {
        $this->adminModel = new Admin();
    }

    public function showLogin()
    {
        if (Session::has('admin_id')) {
            $this->redirect('/admin/dashboard');
        }
        $this->view('admin/login', ['csrf_token' => Session::generateCsrfToken()]);
    }

    public function login()
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $admin = $this->adminModel->findByUsername($username);

        if ($admin && Security::verifyPassword($password, $admin['password_hash'])) {
            Session::set('admin_id', $admin['id']);
            Session::set('admin_username', $admin['username']);
            $this->redirect('/admin/dashboard');
        } else {
            $this->view('admin/login', ['error' => 'Invalid credentials.', 'csrf_token' => Session::generateCsrfToken()]);
        }
    }

    public function logout()
    {
        Session::remove('admin_id');
        Session::remove('admin_username');
        $this->redirect('/admin/login');
    }
}
