<?php
require_once 'functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$error = '';
$success = '';
$user = getUserById($_SESSION['user_id']);
$min_withdraw = get_settings('mining_config')['min_withdraw'] ?? 100;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $phone = sanitize($_POST['phone']);

    if (!$amount || $amount < $min_withdraw) {
        $error = "Minimum withdrawal is KES " . $min_withdraw;
    } elseif ($amount > $user['wallet_balance']) {
        $error = "Insufficient wallet balance.";
    } elseif (empty($phone)) {
        $error = "Phone number is required.";
    } else {
        try {
            $pdo->beginTransaction();

            // Deduct balance immediately (mark as pending)
            $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
            $stmt->execute([$amount, $user['id']]);

            // Record withdrawal request
            $stmt = $pdo->prepare("INSERT INTO withdrawals (user_id, amount, phone_number, status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$user['id'], $amount, $phone]);

            // Record transaction
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status, notes) VALUES (?, 'withdraw', ?, 'pending', ?)");
            $stmt->execute([$user['id'], -$amount, "Withdrawal to " . $phone]);

            $pdo->commit();
            $success = "Withdrawal request submitted! It will be processed after admin approval.";
            $user = getUserById($user['id']); // Refresh user data
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to submit withdrawal request.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw - CryptoERP Miner</title>
    <script src="https://cdn.tailwindcss.com/"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 min-h-screen">
    <nav class="fixed top-0 left-0 h-full w-20 md:w-64 bg-slate-900 border-r border-slate-800 flex flex-col items-center py-8 z-50">
        <h1 class="hidden md:block text-2xl font-bold bg-gradient-to-r from-emerald-400 to-teal-500 bg-clip-text text-transparent mb-12">CryptoERP</h1>
        <div class="flex flex-col space-y-8 flex-grow">
            <a href="dashboard.php" class="flex items-center space-x-4 hover:text-emerald-500 transition">
                <i class="ri-dashboard-line text-2xl"></i>
                <span class="hidden md:block font-medium">Dashboard</span>
            </a>
            <a href="deposit.php" class="flex items-center space-x-4 hover:text-emerald-500 transition">
                <i class="ri-wallet-3-line text-2xl"></i>
                <span class="hidden md:block font-medium">Deposit</span>
            </a>
            <a href="withdraw.php" class="flex items-center space-x-4 text-emerald-500">
                <i class="ri-bank-card-fill text-2xl"></i>
                <span class="hidden md:block font-medium">Withdraw</span>
            </a>
        </div>
        <a href="logout.php" class="flex items-center space-x-4 hover:text-red-500 transition">
            <i class="ri-logout-box-line text-2xl"></i>
            <span class="hidden md:block font-medium">Logout</span>
        </a>
    </nav>

    <main class="ml-20 md:ml-64 p-4 md:p-8 pt-20 flex justify-center">
        <div class="w-full max-w-lg">
            <header class="mb-8">
                <h2 class="text-3xl font-bold text-white">Withdraw Funds</h2>
                <p class="text-slate-400">Withdraw your earnings to your M-Pesa number.</p>
            </header>

            <div class="glass-card px-6 py-4 rounded-2xl mb-8 flex justify-between items-center border-l-4 border-blue-500">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-widest">Withdrawable Balance</p>
                    <p class="text-2xl font-bold text-white">KES <?php echo number_format($user['wallet_balance'], 2); ?></p>
                </div>
                <i class="ri-money-dollar-circle-line text-4xl text-blue-500/20"></i>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-500/20 border border-red-500/50 text-red-200 p-4 rounded-xl mb-6 flex items-center">
                    <i class="ri-error-warning-line mr-3 text-xl"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-emerald-500/20 border border-emerald-500/50 text-emerald-200 p-4 rounded-xl mb-6 flex items-center">
                    <i class="ri-checkbox-circle-line mr-3 text-xl"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="glass-card p-8 rounded-3xl">
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div>
                        <label class="block text-sm font-medium mb-2 text-slate-300">Amount to Withdraw</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 font-bold">KES</span>
                            <input type="number" name="amount" min="<?php echo $min_withdraw; ?>" max="<?php echo $user['wallet_balance']; ?>" step="1" required
                                   class="w-full bg-slate-900/50 border border-slate-700 rounded-xl py-3 pl-14 pr-4 focus:ring-2 focus:ring-emerald-500 outline-none transition text-white text-lg"
                                   placeholder="0.00">
                        </div>
                        <p class="text-[10px] text-slate-500 mt-2">Minimum withdrawal: KES <?php echo $min_withdraw; ?></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2 text-slate-300">Recipient M-Pesa Number</label>
                        <div class="relative">
                            <i class="ri-smartphone-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required
                                   class="w-full bg-slate-900/50 border border-slate-700 rounded-xl py-3 pl-12 pr-4 focus:ring-2 focus:ring-emerald-500 outline-none transition text-white"
                                   placeholder="2547XXXXXXXX">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 hover:scale-[1.02] active:scale-95 text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-blue-500/20 flex items-center justify-center">
                        <i class="ri-send-plane-fill mr-2"></i>
                        Submit Withdrawal
                    </button>
                </form>

                <div class="mt-8 pt-8 border-t border-slate-800">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Note</h4>
                    <p class="text-sm text-slate-400">
                        Withdrawals are processed manually within 24 hours. You will receive an M-Pesa notification once approved.
                    </p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
