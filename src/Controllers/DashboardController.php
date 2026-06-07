<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Models\Balance;
use App\Models\Miner;
use App\Models\Transaction;

class DashboardController extends Controller
{
    private $userModel;
    private $balanceModel;
    private $minerModel;
    private $transactionModel;

    public function __construct()
    {
        if (!Session::has('user_id')) {
            $this->redirect('/login');
        }
        $this->userModel = new User();
        $this->balanceModel = new Balance();
        $this->minerModel = new Miner();
        $this->transactionModel = new Transaction();
    }

    public function index()
    {
        $userId = Session::get('user_id');
        $user = $this->userModel->findById($userId);
        $data = [
            'user' => $user,
            'balance' => $this->balanceModel->getByUserId($userId),
            'active_miners_count' => count($this->minerModel->getActiveByUserId($userId)),
            'recent_transactions' => $this->transactionModel->getRecentByUserId($userId)
        ];
        $this->view('dashboard', $data);
    }
}
