<?php
// admin/transactions.php
require_once 'header.php';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_token();
    $tx_id = filter_input(INPUT_POST, 'tx_id', FILTER_VALIDATE_INT);
    $action = $_POST['action']; // 'approve' or 'reject'

    if ($tx_id) {
        try {
            $pdo->beginTransaction();

            // Fetch transaction details
            $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$tx_id]);
            $tx = $stmt->fetch();

            if ($tx && $tx['status'] === 'pending') {
                $new_status = ($action === 'approve') ? 'completed' : 'failed';

                // Update transaction status
                $stmt = $pdo->prepare("UPDATE transactions SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $tx_id]);

                if ($action === 'approve') {
                    if ($tx['type'] === 'deposit') {
                        // Add to user balance
                        $stmt = $pdo->prepare("UPDATE user_balances SET available_balance = available_balance + ? WHERE user_id = ?");
                        $stmt->execute([$tx['amount'], $tx['user_id']]);
                    } elseif ($tx['type'] === 'withdrawal') {
                        // Subtract from pending_balance and add to total_withdrawn
                        $amount = abs($tx['amount']);
                        $stmt = $pdo->prepare("UPDATE user_balances SET pending_balance = pending_balance - ?, total_withdrawn = total_withdrawn + ? WHERE user_id = ?");
                        $stmt->execute([$amount, $amount, $tx['user_id']]);
                    }
                } elseif ($action === 'reject' && $tx['type'] === 'withdrawal') {
                    // Refund available_balance and subtract from pending_balance
                    $amount = abs($tx['amount']);
                    $stmt = $pdo->prepare("UPDATE user_balances SET available_balance = available_balance + ?, pending_balance = pending_balance - ? WHERE user_id = ?");
                    $stmt->execute([$amount, $amount, $tx['user_id']]);
                }

                $pdo->commit();
                echo "<div class='bg-green-50 text-green-600 p-3 rounded mb-4'>Transaction " . ucfirst($new_status) . ".</div>";
            } else {
                $pdo->rollBack();
                echo "<div class='bg-red-50 text-red-600 p-3 rounded mb-4'>Invalid transaction or already processed.</div>";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<div class='bg-red-50 text-red-600 p-3 rounded mb-4'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

try {
    $transactions = $pdo->query("SELECT t.*, u.username, u.email FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 100")->fetchAll();
} catch (PDOException $e) {
    echo "<div class='bg-red-50 text-red-600 p-4 rounded'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Transaction Logs</h1>
    <p class="text-gray-600">Review and approve platform financial activity.</p>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Method</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($transactions as $tx): ?>
                <tr>
                    <td class="px-4 py-3">
                        <span class="font-medium text-gray-800 uppercase text-xs"><?php echo $tx['type']; ?></span>
                        <div class="text-gray-400 text-[10px] font-mono"><?php echo htmlspecialchars($tx['transaction_hash']); ?></div>
                    </td>
                    <td class="px-4 py-3 text-gray-600">
                        <?php echo htmlspecialchars($tx['username']); ?>
                        <div class="text-gray-400 text-xs"><?php echo htmlspecialchars($tx['email']); ?></div>
                    </td>
                    <td class="px-4 py-3 font-bold <?php echo $tx['amount'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo ($tx['amount'] >= 0 ? '+' : '-') . '$' . number_format(abs($tx['amount']), 2); ?>
                    </td>
                    <td class="px-4 py-3 text-gray-500"><?php echo htmlspecialchars($tx['method']); ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs <?php echo $tx['status'] === 'completed' ? 'bg-green-100 text-green-700' : ($tx['status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'); ?>">
                            <?php echo ucfirst($tx['status']); ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">
                        <?php echo date('M d, Y H:i', strtotime($tx['created_at'])); ?>
                    </td>
                    <td class="px-4 py-3">
                        <?php if ($tx['status'] === 'pending' && ($tx['type'] === 'deposit' || $tx['type'] === 'withdrawal')): ?>
                        <div class="flex space-x-2">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="tx_id" value="<?php echo $tx['id']; ?>">
                                <button type="submit" name="action" value="approve" class="bg-green-600 text-white px-2 py-1 rounded text-xs font-bold hover:bg-green-700 transition">Approve</button>
                            </form>
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="tx_id" value="<?php echo $tx['id']; ?>">
                                <button type="submit" name="action" value="reject" class="bg-red-600 text-white px-2 py-1 rounded text-xs font-bold hover:bg-red-700 transition">Reject</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>