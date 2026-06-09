<?php
require_once 'functions.php';

// Log the raw request
$callbackJSONData = file_get_contents('php://input');
$stmt = $pdo->prepare("INSERT INTO mpesa_logs (transaction_type, raw_request) VALUES ('callback', ?)");
$stmt->execute([$callbackJSONData]);

$callbackData = json_decode($callbackJSONData, true);

if (isset($callbackData['Body']['stkCallback'])) {
    $stkCallback = $callbackData['Body']['stkCallback'];
    $resultCode = $stkCallback['ResultCode'];
    $checkoutRequestID = $stkCallback['CheckoutRequestID'];

    if ($resultCode == 0) {
        // Success
        $callbackMetadata = $stkCallback['CallbackMetadata']['Item'];
        $amount = 0;
        $mpesaReceiptNumber = '';

        foreach ($callbackMetadata as $item) {
            if ($item['Name'] === 'Amount') {
                $amount = $item['Value'];
            } elseif ($item['Name'] === 'MpesaReceiptNumber') {
                $mpesaReceiptNumber = $item['Value'];
            }
        }

        // Find the transaction
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE checkout_request_id = ? AND status = 'pending'");
        $stmt->execute([$checkoutRequestID]);
        $transaction = $stmt->fetch();

        if ($transaction) {
            $user_id = $transaction['user_id'];

            try {
                $pdo->beginTransaction();

                // Update transaction
                $stmt = $pdo->prepare("UPDATE transactions SET status = 'completed', mpesa_receipt = ?, notes = 'M-Pesa payment successful' WHERE id = ?");
                $stmt->execute([$mpesaReceiptNumber, $transaction['id']]);

                // Update user balance
                $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $stmt->execute([$amount, $user_id]);

                // Check if this was their first deposit to give referral bonus
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND type = 'deposit' AND status = 'completed' AND id != ?");
                $stmt->execute([$user_id, $transaction['id']]);
                $previous_deposits = $stmt->fetchColumn();

                if ($previous_deposits == 0) {
                    // Give referral bonus to referrer
                    $stmt = $pdo->prepare("SELECT referred_by FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $referred_by = $stmt->fetchColumn();

                    if ($referred_by) {
                        $referral_percent = get_settings('mining_config')['referral_percent'] ?? 10;
                        $bonus_amount = $amount * ($referral_percent / 100);

                        // Update referrer balance and hashrate (5% increase)
                        $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ?, total_referral_bonus = total_referral_bonus + ?, mining_hashrate = mining_hashrate * 1.05 WHERE id = ?");
                        $stmt->execute([$bonus_amount, $bonus_amount, $referred_by]);

                        // Record bonus transaction
                        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status, notes) VALUES (?, 'referral_bonus', ?, 'completed', ?)");
                        $stmt->execute([$referred_by, $bonus_amount, "Bonus from user ID " . $user_id . " first deposit"]);
                    }
                }

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("M-Pesa callback processing failed: " . $e->getMessage());
            }
        }
    } else {
        // Failed
        $stmt = $pdo->prepare("UPDATE transactions SET status = 'failed', notes = ? WHERE checkout_request_id = ?");
        $stmt->execute([$stkCallback['ResultDesc'], $checkoutRequestID]);
    }
}

// B2C Callback (Withdrawal)
if (isset($callbackData['Result'])) {
    $result = $callbackData['Result'];
    $resultCode = $result['ResultCode'];
    $originatorConversationID = $result['OriginatorConversationID'];

    if ($resultCode == 0) {
        // Success
        $stmt = $pdo->prepare("UPDATE withdrawals SET status = 'processed', processed_at = CURRENT_TIMESTAMP WHERE id IN (SELECT id FROM withdrawals WHERE admin_notes LIKE ?)");
        // In a real scenario, we would use the OriginatorConversationID to map it.
        // For simplicity here, we'll assume we stored it in admin_notes or a new column.
        // Let's just log it for now.
    } else {
        // Failed
    }
}

echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
