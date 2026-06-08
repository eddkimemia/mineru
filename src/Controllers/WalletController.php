<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Balance;
use App\Models\Transaction;
use App\Helpers\Utils;

class WalletController extends Controller
{
    private $balanceModel;
    private $transactionModel;

    protected $db;

    public function __construct()
    {
        if (!Session::has('user_id')) {
            $this->redirect('/login');
        }
        $this->db = \App\Core\Database::getInstance();
        $this->balanceModel = new Balance();
        $this->transactionModel = new Transaction();
    }

    public function index()
    {
        $userId = Session::get('user_id');
        $data = [
            'balance' => $this->balanceModel->getByUserId($userId),
            'transactions' => $this->transactionModel->getRecentByUserId($userId, 10)
        ];
        $this->view('wallet/index', $data);
    }

    public function showDeposit()
    {
        $this->view('wallet/deposit', ['csrf_token' => Session::generateCsrfToken()]);
    }

    public function deposit()
    {
        $userId = Session::get('user_id');
        $amount = $_POST['amount'] ?? 0;
        $method = $_POST['method'] ?? '';

        if ($amount < 10) {
            return $this->view('wallet/deposit', ['error' => 'Minimum deposit is $10.', 'csrf_token' => Session::generateCsrfToken()]);
        }

        $this->transactionModel->create([
            'user_id' => $userId,
            'type' => 'deposit',
            'amount' => $amount,
            'method' => $method,
            'status' => 'pending',
            'transaction_hash' => 'TX_DEP_' . bin2hex(random_bytes(8))
        ]);

        $this->redirect('/wallet?success=deposit_submitted');
    }

    public function showWithdraw()
    {
        $userId = Session::get('user_id');
        $this->view('wallet/withdraw', [
            'balance' => $this->balanceModel->getByUserId($userId),
            'csrf_token' => Session::generateCsrfToken()
        ]);
    }

    public function withdraw()
    {
        $userId = Session::get('user_id');
        $amount = $_POST['amount'] ?? 0;
        $balance = $this->balanceModel->getByUserId($userId);

        if ($amount < 10) {
            return $this->view('wallet/withdraw', ['error' => 'Minimum withdrawal is $10.', 'csrf_token' => Session::generateCsrfToken()]);
        }

        if ($amount > $balance['available_balance']) {
            return $this->view('wallet/withdraw', ['error' => 'Insufficient balance.', 'csrf_token' => Session::generateCsrfToken()]);
        }

        // Check referrals (to be implemented)

        $this->db->beginTransaction();
        try {
            $this->transactionModel->create([
                'user_id' => $userId,
                'type' => 'withdrawal',
                'amount' => -$amount,
                'method' => 'MPESA',
                'status' => 'pending',
                'transaction_hash' => 'TX_WIT_' . bin2hex(random_bytes(8))
            ]);

            $this->balanceModel->update($userId, [
                'available_balance' => $balance['available_balance'] - $amount,
                'pending_balance' => $balance['pending_balance'] + $amount
            ]);

            $this->db->commit();
            $this->redirect('/wallet?success=withdrawal_submitted');
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->view('wallet/withdraw', ['error' => 'Withdrawal failed.', 'csrf_token' => Session::generateCsrfToken()]);
        }
    }
}
