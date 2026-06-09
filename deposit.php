<?php
require_once 'functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$error = '';
$success = '';
$user = getUserById($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $phone = sanitize($_POST['phone']);
    $min_deposit = get_settings('mining_config')['min_deposit'] ?? 10;

    if (!$amount || $amount < $min_deposit) {
        $error = "Minimum deposit is KES " . $min_deposit;
    } elseif (empty($phone)) {
        $error = "Phone number is required.";
    } else {
        // Trigger STK Push
        $response = mpesa_stk_push($phone, $amount, 'DEP' . time());

        if ($response && isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
            $checkout_id = $response['CheckoutRequestID'];

            // Record pending transaction
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status, checkout_request_id, notes) VALUES (?, 'deposit', ?, 'pending', ?, ?)");
            $stmt->execute([$user['id'], $amount, $checkout_id, "STK Push initiated to " . $phone]);

            $success = "STK Push sent! Please check your phone to enter M-Pesa PIN.";
        } else {
            $error = "M-Pesa STK Push failed: " . ($response['errorMessage'] ?? 'Unknown error');

            // Log failed request
            $stmt = $pdo->prepare("INSERT INTO mpesa_logs (transaction_type, raw_response) VALUES ('stkpush', ?)");
            $stmt->execute([json_encode($response)]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit - CryptoERP Miner</title>
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
    <!-- Same Sidebar as dashboard.php -->
    <nav class="fixed top-0 left-0 h-full w-20 md:w-64 bg-slate-900 border-r border-slate-800 flex flex-col items-center py-8 z-50">
        <h1 class="hidden md:block text-2xl font-bold bg-gradient-to-r from-emerald-400 to-teal-500 bg-clip-text text-transparent mb-12">CryptoERP</h1>
        <div class="flex flex-col space-y-8 flex-grow">
            <a href="dashboard.php" class="flex items-center space-x-4 hover:text-emerald-500 transition">
                <i class="ri-dashboard-line text-2xl"></i>
                <span class="hidden md:block font-medium">Dashboard</span>
            </a>
            <a href="deposit.php" class="flex items-center space-x-4 text-emerald-500">
                <i class="ri-wallet-3-fill text-2xl"></i>
                <span class="hidden md:block font-medium">Deposit</span>
            </a>
            <a href="withdraw.php" class="flex items-center space-x-4 hover:text-emerald-500 transition">
                <i class="ri-bank-card-line text-2xl"></i>
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
                <h2 class="text-3xl font-bold text-white">Deposit Funds</h2>
                <p class="text-slate-400">Instantly fund your wallet via M-Pesa STK Push.</p>
            </header>

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
                        <label class="block text-sm font-medium mb-2 text-slate-300">Amount (KES)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 font-bold">KES</span>
                            <input type="number" name="amount" min="10" step="1" required
                                   class="w-full bg-slate-900/50 border border-slate-700 rounded-xl py-3 pl-14 pr-4 focus:ring-2 focus:ring-emerald-500 outline-none transition text-white text-lg"
                                   placeholder="0.00">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2 text-slate-300">M-Pesa Phone Number</label>
                        <div class="relative">
                            <i class="ri-smartphone-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required
                                   class="w-full bg-slate-900/50 border border-slate-700 rounded-xl py-3 pl-12 pr-4 focus:ring-2 focus:ring-emerald-500 outline-none transition text-white"
                                   placeholder="2547XXXXXXXX">
                        </div>
                        <p class="text-[10px] text-slate-500 mt-2">Ensure the number is in the format 2547XXXXXXXX</p>
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-r from-emerald-500 to-teal-600 hover:scale-[1.02] active:scale-95 text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-emerald-500/20 flex items-center justify-center">
                        <i class="ri-safe-2-line mr-2"></i>
                        Initiate STK Push
                    </button>
                </form>

                <div class="mt-8 pt-8 border-t border-slate-800">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Instructions</h4>
                    <ul class="text-sm text-slate-400 space-y-3">
                        <li class="flex items-start">
                            <span class="w-5 h-5 bg-slate-800 rounded-full flex items-center justify-center text-[10px] mr-3 shrink-0">1</span>
                            Enter the amount you wish to deposit.
                        </li>
                        <li class="flex items-start">
                            <span class="w-5 h-5 bg-slate-800 rounded-full flex items-center justify-center text-[10px] mr-3 shrink-0">2</span>
                            Ensure your phone is unlocked and near you.
                        </li>
                        <li class="flex items-start">
                            <span class="w-5 h-5 bg-slate-800 rounded-full flex items-center justify-center text-[10px] mr-3 shrink-0">3</span>
                            Enter your M-Pesa PIN when prompted on your phone.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
