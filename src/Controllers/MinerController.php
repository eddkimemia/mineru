<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Miner;
use App\Models\Balance;
use App\Models\Transaction;

class MinerController extends Controller
{
    private $minerModel;
    private $balanceModel;
    private $transactionModel;

    public function __construct()
    {
        if (!Session::has('user_id')) {
            $this->redirect('/login');
        }
        $this->minerModel = new Miner();
        $this->balanceModel = new Balance();
        $this->transactionModel = new Transaction();
    }

    public function index()
    {
        $userId = Session::get('user_id');
        $data = [
            'packages' => $this->minerModel->getAllPackages(),
            'user_miners' => $this->minerModel->getActiveByUserId($userId),
            'csrf_token' => Session::generateCsrfToken()
        ];
        $this->view('miners/index', $data);
    }

    public function purchase()
    {
        $userId = Session::get('user_id');
        $packageId = $_POST['package_id'] ?? 0;

        $package = $this->minerModel->getPackageById($packageId);
        if (!$package) {
            $this->redirect('/miners?error=invalid_package');
        }

        $balance = $this->balanceModel->getByUserId($userId);
        if ($balance['available_balance'] < $package['price']) {
            $this->redirect('/miners?error=insufficient_balance');
        }

        $this->db->beginTransaction();
        try {
            // Deduct balance
            $this->balanceModel->update($userId, [
                'available_balance' => $balance['available_balance'] - $package['price']
            ]);

            // Create user miner
            $this->minerModel->createPurchase($userId, $packageId, $package['duration_days']);

            // Record transaction
            $this->transactionModel->create([
                'user_id' => $userId,
                'type' => 'purchase',
                'amount' => -$package['price'],
                'method' => 'wallet',
                'status' => 'completed',
                'transaction_hash' => 'TX_PUR_' . bin2hex(random_bytes(8))
            ]);

            $this->db->commit();
            $this->redirect('/miners?success=purchase_complete');
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->redirect('/miners?error=purchase_failed');
        }
    }
}
