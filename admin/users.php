<?php
// admin/users.php
require_once 'header.php';

// Handle user status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    verify_csrf_token();
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $new_status = $_POST['status'] ?? '';

    if ($user_id && in_array($new_status, ['pending', 'active', 'suspended'])) {
        $stmt = $pdo->prepare("UPDATE users SET account_status = ? WHERE id = ?");
        $stmt->execute([$new_status, $user_id]);
        echo "<div class='bg-green-50 text-green-600 p-3 rounded mb-4'>User status updated successfully.</div>";
    }
}

try {
    $users = $pdo->query("SELECT u.*, b.available_balance FROM users u LEFT JOIN user_balances b ON u.id = b.user_id ORDER BY u.created_at DESC")->fetchAll();
} catch (PDOException $e) {
    echo "<div class='bg-red-50 text-red-600 p-4 rounded'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">User Management</h1>
    <p class="text-gray-600">View and manage platform users.</p>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">User Details</th>
                    <th class="px-4 py-3">Balance</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Joined</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($users as $u): ?>
                <tr>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-800"><?php echo htmlspecialchars($u['full_name']); ?></div>
                        <div class="text-gray-500 text-xs">@<?php echo htmlspecialchars($u['username']); ?> • <?php echo htmlspecialchars($u['email']); ?></div>
                    </td>
                    <td class="px-4 py-3 text-gray-800 font-medium">
                        $<?php echo number_format($u['available_balance'] ?: 0, 2); ?>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs <?php echo $u['account_status'] === 'active' ? 'bg-green-100 text-green-700' : ($u['account_status'] === 'suspended' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'); ?>">
                            <?php echo ucfirst($u['account_status']); ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">
                        <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                    </td>
                    <td class="px-4 py-3">
                        <form method="POST" action="" class="flex items-center space-x-2">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <select name="status" class="text-xs border border-gray-300 rounded px-2 py-1 outline-none">
                                <option value="active" <?php echo $u['account_status'] === 'active' ? 'selected' : ''; ?>>Activate</option>
                                <option value="suspended" <?php echo $u['account_status'] === 'suspended' ? 'selected' : ''; ?>>Suspend</option>
                                <option value="pending" <?php echo $u['account_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            </select>
                            <button type="submit" name="update_status" class="text-blue-600 hover:text-blue-800 text-xs font-bold uppercase tracking-tight">Update</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>