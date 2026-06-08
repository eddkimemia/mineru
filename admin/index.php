<?php
// admin/index.php
require_once 'header.php';

try {
    // Stats
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $active_miners = $pdo->query("SELECT COUNT(*) FROM user_miners WHERE status = 'active'")->fetchColumn();
    $total_deposits = $pdo->query("SELECT SUM(amount) FROM transactions WHERE type = 'deposit' AND status = 'completed'")->fetchColumn() ?: 0;
    $pending_withdrawals_count = $pdo->query("SELECT COUNT(*) FROM transactions WHERE type = 'withdrawal' AND status = 'pending'")->fetchColumn();

    // Recent Users
    $recent_users = $pdo->query("SELECT id, username, full_name, email, account_status, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

    // Recent Transactions
    $recent_transactions = $pdo->query("SELECT t.*, u.username FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 5")->fetchAll();

} catch (PDOException $e) {
    echo "<div class='bg-red-50 text-red-600 p-4 rounded'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
    <p class="text-gray-600">Platform overview and key metrics.</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-gray-500 uppercase">Total Users</h3>
            <i class="ri-user-line text-blue-500 text-xl"></i>
        </div>
        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($total_users); ?></p>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-gray-500 uppercase">Active Miners</h3>
            <i class="ri-cpu-line text-green-500 text-xl"></i>
        </div>
        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($active_miners); ?></p>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-gray-500 uppercase">Total Deposits</h3>
            <i class="ri-money-dollar-circle-line text-yellow-500 text-xl"></i>
        </div>
        <p class="text-2xl font-bold text-gray-800">$<?php echo number_format($total_deposits, 2); ?></p>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-gray-500 uppercase">Pending Withdrawals</h3>
            <i class="ri-time-line text-red-500 text-xl"></i>
        </div>
        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($pending_withdrawals_count); ?></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Recent Users -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
            <h2 class="font-bold text-gray-800">Recently Joined Users</h2>
            <a href="users.php" class="text-blue-600 text-sm hover:underline">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Joined</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($recent_users as $u): ?>
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800"><?php echo htmlspecialchars($u['full_name']); ?></div>
                            <div class="text-gray-500 text-xs"><?php echo htmlspecialchars($u['email']); ?></div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs <?php echo $u['account_status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'; ?>">
                                <?php echo ucfirst($u['account_status']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">
                            <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
            <h2 class="font-bold text-gray-800">Recent Transactions</h2>
            <a href="transactions.php" class="text-blue-600 text-sm hover:underline">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($recent_transactions as $tx): ?>
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-800"><?php echo ucfirst($tx['type']); ?></td>
                        <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($tx['username']); ?></td>
                        <td class="px-4 py-3 font-bold <?php echo $tx['amount'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo ($tx['amount'] >= 0 ? '+' : '-') . '$' . number_format(abs($tx['amount']), 2); ?>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs <?php echo $tx['status'] === 'completed' ? 'bg-green-100 text-green-700' : ($tx['status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'); ?>">
                                <?php echo ucfirst($tx['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>