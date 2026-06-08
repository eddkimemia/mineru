<?php
require_once 'config.php';
verify_csrf_token();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_id = $_SESSION['user_id'];
$error = ''; $success = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['submit_deposit'])) {
            $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
            if ($amount >= 10) {
                $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, method, status, transaction_hash) VALUES (?, 'deposit', ?, 'MPESA', 'pending', ?)");
                $stmt->execute([$user_id, $amount, 'TX_DEP_' . bin2hex(random_bytes(4))]);
                $success = "Deposit request submitted!";
            } else { $error = "Minimum $10."; }
        } elseif (isset($_POST['submit_withdrawal'])) {
            $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_id = ? AND status = 'active'");
            $stmt->execute([$user_id]);
            $ref_count = $stmt->fetchColumn();
            $stmt = $pdo->prepare("SELECT available_balance FROM user_balances WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $bal = $stmt->fetchColumn();
            if ($ref_count >= 3 && $amount >= 10 && $bal >= $amount) {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, method, status, transaction_hash) VALUES (?, 'withdrawal', ?, 'MPESA', 'pending', ?)");
                $stmt->execute([$user_id, -$amount, 'TX_WIT_' . bin2hex(random_bytes(4))]);
                $stmt = $pdo->prepare("UPDATE user_balances SET available_balance = available_balance - ?, pending_balance = pending_balance + ? WHERE user_id = ?");
                $stmt->execute([$amount, $amount, $user_id]);
                $pdo->commit();
                $success = "Withdrawal request submitted!";
            } else { $error = "Requirements not met (3 referrals, min $10, sufficient balance)."; }
        }
    }
    $stmt = $pdo->prepare("SELECT * FROM user_balances WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $balance = $stmt->fetch();
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll();
} catch (Exception $e) { error_log($e->getMessage()); }
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Wallet - CryptoMiner ERP</title>
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include 'header.php'; ?>
    <main class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Wallet</h1>
        <?php if($success): ?><div class="mb-4 p-3 bg-green-50 text-green-600 rounded"><?php echo $success; ?></div><?php endif; ?>
        <?php if($error): ?><div class="mb-4 p-3 bg-red-50 text-red-600 rounded"><?php echo $error; ?></div><?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div class="bg-white p-6 rounded shadow border">
                <h2 class="font-bold mb-4">Deposit (MPESA)</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="number" name="amount" min="10" step="0.01" required class="w-full border p-2 rounded mb-4" placeholder="Amount ($)">
                    <button type="submit" name="submit_deposit" class="w-full bg-green-600 text-white py-2 rounded">Deposit</button>
                </form>
            </div>
            <div class="bg-white p-6 rounded shadow border">
                <h2 class="font-bold mb-4">Withdraw (MPESA)</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="number" name="amount" min="10" step="0.01" required class="w-full border p-2 rounded mb-4" placeholder="Amount ($)">
                    <button type="submit" name="submit_withdrawal" class="w-full bg-red-600 text-white py-2 rounded">Withdraw</button>
                </form>
            </div>
        </div>

        <h2 class="text-xl font-bold mb-4">Transactions</h2>
        <div class="bg-white rounded shadow border overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50">
                    <tr><th class="p-4">Type</th><th class="p-4">Amount</th><th class="p-4">Status</th><th class="p-4">Date</th></tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach($transactions as $tx): ?>
                    <tr>
                        <td class="p-4 uppercase text-sm"><?php echo $tx['type']; ?></td>
                        <td class="p-4 font-bold">$<?php echo number_format(abs($tx['amount']), 2); ?></td>
                        <td class="p-4"><span class="px-2 py-1 rounded-full text-xs bg-gray-100"><?php echo $tx['status']; ?></span></td>
                        <td class="p-4 text-gray-500 text-sm"><?php echo $tx['created_at']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>