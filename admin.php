<?php
require_once 'functions.php';

if (!is_admin()) {
    redirect('login.php');
}

$success = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // Withdrawal Approval
    if (isset($_POST['approve_withdrawal'])) {
        $w_id = filter_input(INPUT_POST, 'withdrawal_id', FILTER_VALIDATE_INT);
        $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ? AND status = 'pending'");
        $stmt->execute([$w_id]);
        $withdrawal = $stmt->fetch();

        if ($withdrawal) {
            // Trigger M-Pesa B2C (Simulation)
            // In a real scenario, you'd call a function here
            $stmt = $pdo->prepare("UPDATE withdrawals SET status = 'processed', processed_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$w_id]);

            $stmt = $pdo->prepare("UPDATE transactions SET status = 'completed' WHERE user_id = ? AND type = 'withdraw' AND status = 'pending' AND amount = ?");
            $stmt->execute([$withdrawal['user_id'], -$withdrawal['amount']]);

            $success = "Withdrawal approved and processed!";
        }
    }

    // User Management
    if (isset($_POST['update_user'])) {
        $u_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $hashrate = filter_input(INPUT_POST, 'hashrate', FILTER_VALIDATE_FLOAT);
        $is_banned = isset($_POST['is_banned']) ? 1 : 0;

        $stmt = $pdo->prepare("UPDATE users SET mining_hashrate = ?, is_banned = ? WHERE id = ?");
        $stmt->execute([$hashrate, $is_banned, $u_id]);
        $success = "User updated successfully!";
    }

    // Settings Management
    if (isset($_POST['update_settings'])) {
        $settings = [
            'mining_rate_per_hash' => filter_input(INPUT_POST, 'mining_rate', FILTER_VALIDATE_FLOAT),
            'min_withdraw' => filter_input(INPUT_POST, 'min_withdraw', FILTER_VALIDATE_FLOAT),
            'referral_percent' => filter_input(INPUT_POST, 'referral_percent', FILTER_VALIDATE_FLOAT),
            'min_deposit' => filter_input(INPUT_POST, 'min_deposit', FILTER_VALIDATE_FLOAT),
        ];

        $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'mining_config'");
        $stmt->execute([json_encode($settings)]);
        $success = "Settings updated successfully!";
    }
}

// Fetch stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_deposits = $pdo->query("SELECT SUM(amount) FROM transactions WHERE type = 'deposit' AND status = 'completed'")->fetchColumn() ?: 0;
$total_withdrawals = $pdo->query("SELECT SUM(amount) FROM transactions WHERE type = 'withdraw' AND status = 'completed'")->fetchColumn() ?: 0;
$pending_withdrawals_count = $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn();

// Fetch users
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

// Fetch pending withdrawals
$pending_withdrawals = $pdo->query("SELECT w.*, u.fullname FROM withdrawals w JOIN users u ON w.user_id = u.id WHERE w.status = 'pending' ORDER BY w.created_at DESC")->fetchAll();

// Fetch logs
$mpesa_logs = $pdo->query("SELECT * FROM mpesa_logs ORDER BY created_at DESC LIMIT 10")->fetchAll();

$mining_config = get_settings('mining_config');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - CryptoERP Miner</title>
    <script src="https://cdn.tailwindcss.com/"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
</head>
<body class="bg-slate-50 min-h-screen font-sans">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-900 min-h-screen text-slate-300 p-6">
            <h1 class="text-2xl font-bold text-white mb-10">Admin Center</h1>
            <nav class="space-y-4">
                <a href="#stats" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-slate-800 transition">
                    <i class="ri-pie-chart-line"></i> <span>Overview</span>
                </a>
                <a href="#users" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-slate-800 transition">
                    <i class="ri-group-line"></i> <span>Users</span>
                </a>
                <a href="#withdrawals" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-slate-800 transition">
                    <i class="ri-bank-card-line"></i> <span>Withdrawals</span>
                </a>
                <a href="#settings" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-slate-800 transition">
                    <i class="ri-settings-4-line"></i> <span>Settings</span>
                </a>
                <a href="dashboard.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-slate-800 transition mt-10">
                    <i class="ri-arrow-left-line"></i> <span>Back to Site</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 overflow-y-auto h-screen">
            <header class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-slate-800">System Dashboard</h2>
                <?php if ($success): ?><div class="bg-green-100 text-green-700 px-4 py-2 rounded-lg border border-green-200"><?php echo $success; ?></div><?php endif; ?>
            </header>

            <!-- Stats -->
            <div id="stats" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                    <p class="text-slate-500 text-sm">Total Users</p>
                    <p class="text-2xl font-bold text-slate-800"><?php echo $total_users; ?></p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                    <p class="text-slate-500 text-sm">Total Deposits</p>
                    <p class="text-2xl font-bold text-emerald-600">KES <?php echo number_format($total_deposits, 2); ?></p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                    <p class="text-slate-500 text-sm">Total Withdrawals</p>
                    <p class="text-2xl font-bold text-red-600">KES <?php echo number_format(abs($total_withdrawals), 2); ?></p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                    <p class="text-slate-500 text-sm">Pending Withdrawals</p>
                    <p class="text-2xl font-bold text-blue-600"><?php echo $pending_withdrawals_count; ?></p>
                </div>
            </div>

            <!-- Pending Withdrawals -->
            <section id="withdrawals" class="bg-white rounded-xl shadow-sm border border-slate-100 mb-10 overflow-hidden">
                <div class="p-6 border-b border-slate-50 flex justify-between items-center">
                    <h3 class="font-bold text-lg">Withdrawal Requests</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                            <tr>
                                <th class="px-6 py-4">User</th>
                                <th class="px-6 py-4">Phone</th>
                                <th class="px-6 py-4">Amount</th>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach ($pending_withdrawals as $pw): ?>
                            <tr>
                                <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($pw['fullname']); ?></td>
                                <td class="px-6 py-4 text-slate-600"><?php echo $pw['phone_number']; ?></td>
                                <td class="px-6 py-4 font-bold">KES <?php echo number_format($pw['amount'], 2); ?></td>
                                <td class="px-6 py-4 text-sm text-slate-400"><?php echo date('M d, H:i', strtotime($pw['created_at'])); ?></td>
                                <td class="px-6 py-4">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="withdrawal_id" value="<?php echo $pw['id']; ?>">
                                        <button type="submit" name="approve_withdrawal" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-1.5 rounded text-sm font-bold transition">Approve</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; if(empty($pending_withdrawals)): ?>
                            <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400">No pending requests.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- User Management -->
            <section id="users" class="bg-white rounded-xl shadow-sm border border-slate-100 mb-10 overflow-hidden">
                <div class="p-6 border-b border-slate-50"><h3 class="font-bold text-lg">Manage Users</h3></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                            <tr>
                                <th class="px-6 py-4">User</th>
                                <th class="px-6 py-4">Email/Phone</th>
                                <th class="px-6 py-4">Hash Rate</th>
                                <th class="px-6 py-4">Balance</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach ($users as $u): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-800"><?php echo htmlspecialchars($u['fullname']); ?></div>
                                    <div class="text-[10px] text-slate-400 uppercase tracking-tighter">Code: <?php echo $u['referral_code']; ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <div><?php echo $u['email']; ?></div>
                                    <div><?php echo $u['phone']; ?></div>
                                </td>
                                <td class="px-6 py-4 font-medium text-blue-600"><?php echo $u['mining_hashrate']; ?> KH/s</td>
                                <td class="px-6 py-4 font-bold">KES <?php echo number_format($u['wallet_balance'], 2); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?php echo $u['is_banned'] ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'; ?>">
                                        <?php echo $u['is_banned'] ? 'Banned' : 'Active'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <button onclick='openEditModal(<?php echo json_encode($u); ?>)' class="text-blue-500 hover:underline text-sm font-bold">Edit</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Settings -->
            <section id="settings" class="bg-white rounded-xl shadow-sm border border-slate-100 mb-10 overflow-hidden">
                <div class="p-6 border-b border-slate-50"><h3 class="font-bold text-lg">System Settings</h3></div>
                <form method="POST" class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-2">Mining Rate (KES per KH/s per Day)</label>
                        <input type="number" step="0.01" name="mining_rate" value="<?php echo $mining_config['mining_rate_per_hash']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-3">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-2">Min Withdrawal (KES)</label>
                        <input type="number" name="min_withdraw" value="<?php echo $mining_config['min_withdraw']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-3">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-2">Referral Bonus (%)</label>
                        <input type="number" name="referral_percent" value="<?php echo $mining_config['referral_percent']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-3">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-2">Min Deposit (KES)</label>
                        <input type="number" name="min_deposit" value="<?php echo $mining_config['min_deposit']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-3">
                    </div>
                    <div class="md:col-span-2 text-right">
                        <button type="submit" name="update_settings" class="bg-slate-800 text-white px-8 py-3 rounded-lg font-bold hover:bg-slate-700 transition">Save Settings</button>
                    </div>
                </form>
            </section>

            <!-- Logs -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100 mb-10 overflow-hidden">
                <div class="p-6 border-b border-slate-50"><h3 class="font-bold text-lg">Recent M-Pesa Logs</h3></div>
                <div class="p-6 space-y-4">
                    <?php foreach ($mpesa_logs as $log): ?>
                    <div class="text-xs font-mono bg-slate-900 text-slate-400 p-4 rounded-lg overflow-x-auto">
                        <span class="text-emerald-500 font-bold uppercase">[<?php echo $log['transaction_type']; ?>]</span>
                        <span class="text-slate-500 ml-2"><?php echo $log['created_at']; ?></span>
                        <div class="mt-2 text-slate-300"><?php echo htmlspecialchars($log['raw_request'] ?: $log['raw_response']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4 z-[100]">
        <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden">
            <div class="p-6 border-b flex justify-between items-center">
                <h3 class="font-bold text-xl">Edit User</h3>
                <button onclick="closeEditModal()" class="text-slate-400"><i class="ri-close-line text-2xl"></i></button>
            </div>
            <form method="POST" class="p-6 space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="user_id" id="modalUserId">
                <div>
                    <label class="block text-sm font-medium mb-1 text-slate-600">Full Name</label>
                    <input type="text" id="modalUserName" readonly class="w-full bg-slate-50 border border-slate-100 rounded-lg p-3 text-slate-400">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-slate-600">Mining Hash Rate (KH/s)</label>
                    <input type="number" step="0.1" name="hashrate" id="modalUserHash" class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="is_banned" id="modalUserBanned" class="w-5 h-5 mr-3">
                    <label class="text-sm font-medium text-red-600">Ban Account</label>
                </div>
                <button type="submit" name="update_user" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition">Update User</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(user) {
            document.getElementById('modalUserId').value = user.id;
            document.getElementById('modalUserName').value = user.fullname;
            document.getElementById('modalUserHash').value = user.mining_hashrate;
            document.getElementById('modalUserBanned').checked = user.is_banned == 1;
            document.getElementById('editModal').classList.remove('hidden');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
    </script>
</body>
</html>
