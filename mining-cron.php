<?php
/**
 * mining-cron.php - Process daily mining rewards
 * Run this daily via cron: php mining-cron.php
 */

require_once 'functions.php';

echo "Starting daily mining reward distribution...\n";

try {
    // 1. Get all active mining sessions
    $stmt = $pdo->prepare("SELECT * FROM mining_sessions WHERE status = 'active'");
    $stmt->execute();
    $sessions = $stmt->fetchAll();

    $processed_count = 0;
    $total_rewarded = 0;

    $today = date('Y-m-d');

    foreach ($sessions as $session) {
        $user_id = $session['user_id'];
        $hashrate = $session['hashrate'];

        // Check if reward already paid today
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND type = 'mining_reward' AND date(created_at) = ?");
        $stmt->execute([$user_id, $today]);
        if ($stmt->fetchColumn() > 0) {
            echo "Skipping User $user_id: Reward already paid today.\n";
            continue;
        }

        // Calculate reward
        $reward = calculateMiningReward($hashrate);

        if ($reward > 0) {
            $pdo->beginTransaction();

            // Update user balance and total mined
            $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ?, total_mined = total_mined + ? WHERE id = ?");
            $stmt->execute([$reward, $reward, $user_id]);

            // Record reward transaction
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status, notes) VALUES (?, 'mining_reward', ?, 'completed', ?)");
            $stmt->execute([$user_id, $reward, "Daily mining reward for " . $hashrate . " KH/s"]);

            // Mark session as completed (optional: sessions could stay active for days)
            // But per requirements, rewards accrue daily. We can either keep session active or close and restart.
            // Let's keep it active but record the reward.
            $stmt = $pdo->prepare("UPDATE mining_sessions SET reward_earned = reward_earned + ? WHERE id = ?");
            $stmt->execute([$reward, $session['id']]);

            $pdo->commit();

            $processed_count++;
            $total_rewarded += $reward;
        }
    }

    echo "Processed " . $processed_count . " mining sessions.\n";
    echo "Total rewards distributed: KES " . number_format($total_rewarded, 2) . "\n";

} catch (Exception $e) {
    echo "Error during mining cron: " . $e->getMessage() . "\n";
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
