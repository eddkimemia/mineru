<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Referral;
use App\Models\User;

class ReferralController extends Controller
{
    private $referralModel;
    private $userModel;

    public function __construct()
    {
        if (!Session::has('user_id')) {
            $this->redirect('/login');
        }
        $this->referralModel = new Referral();
        $this->userModel = new User();
    }

    public function index()
    {
        $userId = Session::get('user_id');
        $user = $this->userModel->findById($userId);
        $data = [
            'user' => $user,
            'referrals' => $this->referralModel->getByReferrerId($userId),
            'referral_count' => $this->referralModel->getCountByReferrerId($userId),
        ];
        $this->view('referrals/index', $data);
    }
}
