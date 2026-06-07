<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Helpers\Utils;

class ProfileController extends Controller
{
    private $userModel;

    public function __construct()
    {
        if (!Session::has('user_id')) {
            $this->redirect('/login');
        }
        $this->userModel = new User();
    }

    public function index()
    {
        $userId = Session::get('user_id');
        $user = $this->userModel->findById($userId);
        $this->view('profile', [
            'user' => $user,
            'csrf_token' => Session::generateCsrfToken()
        ]);
    }

    public function update()
    {
        $userId = Session::get('user_id');
        $data = [
            'full_name' => Utils::sanitize($_POST['full_name'] ?? ''),
            'phone_number' => Utils::sanitize($_POST['phone_number'] ?? '')
        ];

        if (!empty($data['full_name'])) {
            $this->userModel->update($userId, $data);
            Session::set('full_name', $data['full_name']);
            $this->redirect('/profile?updated=1');
        } else {
            $user = $this->userModel->findById($userId);
            $this->view('profile', [
                'user' => $user,
                'error' => 'Full name is required.',
                'csrf_token' => Session::generateCsrfToken()
            ]);
        }
    }
}
