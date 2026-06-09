<?php
require_once 'functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
$mining_config = get_settings('mining_config');

// Calculate daily reward
$daily_reward = calculateMiningReward($user['mining_hashrate']);

// Fetch referrals
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE referred_by = ?");
$stmt->execute([$user_id]);
$referral_count = $stmt->fetchColumn();

// Fetch recent transactions
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll();

// Handle "Start Mining" simulation
$mining_status = 'Idle';
$stmt = $pdo->prepare("SELECT * FROM mining_sessions WHERE user_id = ? AND status = 'active'");
$stmt->execute([$user_id]);
$active_session = $stmt->fetch();
if ($active_session) {
    $mining_status = 'Mining...';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_mining'])) {
    verify_csrf();
    if (!$active_session) {
        $stmt = $pdo->prepare("INSERT INTO mining_sessions (user_id, hashrate) VALUES (?, ?)");
        $stmt->execute([$user_id, $user['mining_hashrate']]);
        redirect('dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CryptoERP Miner</title>
    <script src="https://cdn.tailwindcss.com/"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .gradient-text {
            background: linear-gradient(to right, #10b981, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 min-h-screen">
    <!-- Sidebar / Nav -->
    <nav class="fixed top-0 left-0 h-full w-20 md:w-64 bg-slate-900 border-r border-slate-800 flex flex-col items-center py-8 z-50">
        <h1 class="hidden md:block text-2xl font-bold gradient-text mb-12">CryptoERP</h1>
        <div class="flex flex-col space-y-8 flex-grow">
            <a href="dashboard.php" class="flex items-center space-x-4 text-emerald-500">
                <i class="ri-dashboard-fill text-2xl"></i>
                <span class="hidden md:block font-medium">Dashboard</span>
            </a>
            <a href="deposit.php" class="flex items-center space-x-4 hover:text-emerald-500 transition">
                <i class="ri-wallet-3-line text-2xl"></i>
                <span class="hidden md:block font-medium">Deposit</span>
            </a>
            <a href="withdraw.php" class="flex items-center space-x-4 hover:text-emerald-500 transition">
                <i class="ri-bank-card-line text-2xl"></i>
                <span class="hidden md:block font-medium">Withdraw</span>
            </a>
            <?php if (is_admin()): ?>
            <a href="admin.php" class="flex items-center space-x-4 hover:text-emerald-500 transition">
                <i class="ri-admin-line text-2xl"></i>
                <span class="hidden md:block font-medium">Admin</span>
            </a>
            <?php endif; ?>
        </div>
        <a href="logout.php" class="flex items-center space-x-4 hover:text-red-500 transition">
            <i class="ri-logout-box-line text-2xl"></i>
            <span class="hidden md:block font-medium">Logout</span>
        </a>
    </nav>

    <!-- Main Content -->
    <main class="ml-20 md:ml-64 p-4 md:p-8 pt-20">
        <header class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <h2 class="text-3xl font-bold text-white">Hello, <?php echo htmlspecialchars($user['fullname']); ?>!</h2>
                <p class="text-slate-400">Welcome to your mining command center.</p>
            </div>
            <div class="glass-card px-6 py-3 rounded-2xl flex items-center space-x-4">
                <div class="text-right">
                    <p class="text-xs text-slate-400 uppercase tracking-wider">Wallet Balance</p>
                    <p class="text-xl font-bold text-white">KES <?php echo number_format($user['wallet_balance'], 2); ?></p>
                </div>
                <div class="w-10 h-10 bg-emerald-500/20 rounded-full flex items-center justify-center text-emerald-500">
                    <i class="ri-wallet-line text-xl"></i>
                </div>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="glass-card p-6 rounded-2xl">
                <p class="text-slate-400 text-sm mb-1">Current Hash Rate</p>
                <h3 class="text-2xl font-bold text-white"><?php echo number_format($user['mining_hashrate'], 2); ?> KH/s</h3>
                <div class="mt-4 flex items-center text-xs text-emerald-500">
                    <i class="ri-arrow-up-line"></i>
                    <span>Base 10 KH/s + Referral Bonus</span>
                </div>
            </div>
            <div class="glass-card p-6 rounded-2xl border-l-4 border-emerald-500">
                <p class="text-slate-400 text-sm mb-1">Daily Mining Reward</p>
                <h3 class="text-2xl font-bold text-white">KES <?php echo number_format($daily_reward, 2); ?></h3>
                <p class="mt-4 text-xs text-slate-500">Auto-credited every 24h</p>
            </div>
            <div class="glass-card p-6 rounded-2xl">
                <p class="text-slate-400 text-sm mb-1">Total Mined All Time</p>
                <h3 class="text-2xl font-bold text-white">KES <?php echo number_format($user['total_mined'], 2); ?></h3>
                <div class="mt-4 h-1.5 bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500 w-2/3"></div>
                </div>
            </div>
        </div>

        <!-- Mining Simulation & Referrals -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="glass-card p-8 rounded-3xl relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/10 blur-3xl group-hover:bg-emerald-500/20 transition-all"></div>
                <h3 class="text-xl font-bold mb-6 flex items-center">
                    <i class="ri-cpu-line mr-2 text-emerald-500"></i> Mining Session
                </h3>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-400 text-sm">Status</p>
                        <p class="text-lg font-medium <?php echo $active_session ? 'text-emerald-400 animate-pulse' : 'text-slate-500'; ?>">
                            <?php echo $mining_status; ?>
                        </p>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <button type="submit" name="start_mining" <?php echo $active_session ? 'disabled' : ''; ?>
                                class="bg-gradient-to-r from-emerald-500 to-teal-600 px-8 py-3 rounded-xl font-bold text-white shadow-lg hover:shadow-emerald-500/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <?php echo $active_session ? 'Mining Active' : 'Start Mining'; ?>
                        </button>
                    </form>
                </div>
                <div class="mt-8 grid grid-cols-3 gap-4 text-center">
                    <div class="p-3 bg-slate-900/50 rounded-xl">
                        <p class="text-[10px] text-slate-500 uppercase">GPU Temp</p>
                        <p class="text-sm font-bold">54°C</p>
                    </div>
                    <div class="p-3 bg-slate-900/50 rounded-xl">
                        <p class="text-[10px] text-slate-500 uppercase">Load</p>
                        <p class="text-sm font-bold">88%</p>
                    </div>
                    <div class="p-3 bg-slate-900/50 rounded-xl">
                        <p class="text-[10px] text-slate-500 uppercase">Uptime</p>
                        <p class="text-sm font-bold">12h 4m</p>
                    </div>
                </div>
            </div>

            <div class="glass-card p-8 rounded-3xl">
                <h3 class="text-xl font-bold mb-6 flex items-center">
                    <i class="ri-share-line mr-2 text-blue-500"></i> Referral Program
                </h3>
                <div class="bg-slate-900/50 p-4 rounded-xl mb-6">
                    <p class="text-xs text-slate-400 mb-2 uppercase tracking-tighter">Your Referral Link</p>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="<?php echo BASE_URL . '/register.php?ref=' . $user['referral_code']; ?>"
                               id="refLink" class="bg-transparent border-none outline-none text-sm w-full text-blue-400 font-medium">
                        <button onclick="copyRef()" class="p-2 hover:bg-slate-800 rounded-lg text-slate-400 transition">
                            <i class="ri-file-copy-line"></i>
                        </button>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-400 text-sm">Total Referrals</p>
                        <p class="text-2xl font-bold text-white"><?php echo $referral_count; ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-slate-400 text-sm">Total Bonus</p>
                        <p class="text-2xl font-bold text-blue-400">KES <?php echo number_format($user['total_referral_bonus'], 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="glass-card rounded-3xl overflow-hidden">
            <div class="p-6 border-b border-slate-800 flex justify-between items-center">
                <h3 class="font-bold text-lg">Recent Transactions</h3>
                <a href="#" class="text-sm text-emerald-500 hover:underline">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-900/50 text-slate-500 text-xs uppercase">
                        <tr>
                            <th class="px-6 py-4">Type</th>
                            <th class="px-6 py-4">Amount</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <?php foreach ($transactions as $tx): ?>
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <?php
                                    $icon = 'ri-exchange-line';
                                    $color = 'text-slate-400';
                                    if($tx['type'] == 'deposit') { $icon = 'ri-arrow-down-circle-line'; $color = 'text-emerald-500'; }
                                    if($tx['type'] == 'withdraw') { $icon = 'ri-arrow-up-circle-line'; $color = 'text-red-500'; }
                                    if($tx['type'] == 'mining_reward') { $icon = 'ri-cpu-line'; $color = 'text-blue-500'; }
                                    ?>
                                    <i class="<?php echo $icon . ' ' . $color; ?> mr-3 text-xl"></i>
                                    <span class="capitalize"><?php echo str_replace('_', ' ', $tx['type']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-bold <?php echo $tx['amount'] >= 0 ? 'text-emerald-400' : 'text-red-400'; ?>">
                                <?php echo ($tx['amount'] >= 0 ? '+' : '') . number_format($tx['amount'], 2); ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase <?php
                                    echo $tx['status'] == 'completed' ? 'bg-emerald-500/10 text-emerald-500' :
                                        ($tx['status'] == 'pending' ? 'bg-yellow-500/10 text-yellow-500' : 'bg-red-500/10 text-red-500');
                                ?>">
                                    <?php echo $tx['status']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500">
                                <?php echo date('M d, H:i', strtotime($tx['created_at'])); ?>
                            </td>
                        </tr>
                        <?php endforeach; if(empty($transactions)): ?>
                        <tr><td colspan="4" class="px-6 py-12 text-center text-slate-500">No transactions yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function copyRef() {
            const copyText = document.getElementById("refLink");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            document.execCommand("copy");
            alert("Referral link copied!");
        }
    </script>
</body>
</html>
