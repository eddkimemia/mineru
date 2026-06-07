<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Miner;
use App\Models\Balance;

class AdminController extends Controller
{
    private $userModel;
    private $transactionModel;
    private $minerModel;
    private $balanceModel;

    public function __construct()
    {
        if (!Session::has('admin_id')) {
            $this->redirect('/admin/login');
        }
        $this->userModel = new User();
        $this->transactionModel = new Transaction();
        $this->minerModel = new Miner();
        $this->balanceModel = new Balance();
    }

    public function dashboard()
    {
        $data = [
            'total_users' => $this->userModel->getCount(),
            'total_deposits' => $this->transactionModel->getTotalByType('deposit'),
            'total_withdrawals' => abs($this->transactionModel->getTotalByType('withdrawal')),
            'pending_transactions' => 0 // Can implement a specific count method
        ];
        $this->view('admin/dashboard', $data);
    }

    public function users()
    {
        $data = [
            'users' => $this->userModel->getAll()
        ];
        $this->view('admin/users', $data);
    }

    public function packages()
    {
        $data = [
            'packages' => $this->minerModel->getAllPackages()
        ];
        $this->view('admin/packages', $data);
    }

    public function transactions()
    {
        $data = [
            'transactions' => $this->transactionModel->getAll()
        ];
        $this->view('admin/transactions', $data);
    }

    public function approveTransaction()
    {
        $id = $_POST['id'] ?? null;
        $tx = $this->transactionModel->findById($id);
        if ($tx && $tx['status'] === 'pending') {
            $this->db->beginTransaction();
            try {
                $this->transactionModel->updateStatus($id, 'completed');

                if ($tx['type'] === 'deposit') {
                    $balance = $this->balanceModel->getByUserId($tx['user_id']);
                    $this->balanceModel->update($tx['user_id'], [
                        'available_balance' => $balance['available_balance'] + $tx['amount']
                    ]);
                } elseif ($tx['type'] === 'withdrawal') {
                    $balance = $this->balanceModel->getByUserId($tx['user_id']);
                    $this->balanceModel->update($tx['user_id'], [
                        'pending_balance' => $balance['pending_balance'] - abs($tx['amount']),
                        'total_withdrawn' => $balance['total_withdrawn'] + abs($tx['amount'])
                    ]);
                }

                $this->db->commit();
                $this->redirect('/admin/transactions?success=1');
            } catch (\Exception $e) {
                $this->db->rollBack();
                $this->redirect('/admin/transactions?error=1');
            }
        }
    }
}
