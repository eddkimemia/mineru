<?php
// admin/messages.php
require_once 'header.php';

// Handle message status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    verify_csrf_token();
    $msg_id = filter_input(INPUT_POST, 'msg_id', FILTER_VALIDATE_INT);
    $new_status = $_POST['status'] ?? '';
    $reply = filter_input(INPUT_POST, 'admin_reply', FILTER_SANITIZE_STRING);

    if ($msg_id && in_array($new_status, ['new', 'read', 'replied', 'closed'])) {
        $stmt = $pdo->prepare("UPDATE contact_messages SET status = ?, admin_reply = ? WHERE id = ?");
        $stmt->execute([$new_status, $reply, $msg_id]);
        echo "<div class='bg-green-50 text-green-600 p-3 rounded mb-4'>Message updated.</div>";
    }
}

try {
    $messages = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    echo "<div class='bg-red-50 text-red-600 p-4 rounded'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Support Inquiries</h1>
    <p class="text-gray-600">Respond to user messages and questions.</p>
</div>

<div class="space-y-6">
    <?php if (empty($messages)): ?>
    <div class="bg-white p-8 rounded-lg text-center text-gray-500 border border-gray-100">
        No messages found.
    </div>
    <?php endif; ?>

    <?php foreach ($messages as $m): ?>
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
            <div>
                <span class="font-bold text-gray-800"><?php echo htmlspecialchars($m['name']); ?></span>
                <span class="text-gray-500 text-sm ml-2">&lt;<?php echo htmlspecialchars($m['email']); ?>&gt;</span>
            </div>
            <span class="px-2 py-0.5 rounded-full text-xs <?php
                echo $m['status'] === 'new' ? 'bg-blue-100 text-blue-700' :
                    ($m['status'] === 'replied' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700');
            ?>">
                <?php echo ucfirst($m['status']); ?>
            </span>
        </div>

        <div class="p-6">
            <h3 class="font-bold text-lg text-gray-800 mb-2"><?php echo htmlspecialchars($m['subject']); ?></h3>
            <p class="text-gray-600 whitespace-pre-wrap mb-6"><?php echo htmlspecialchars($m['message']); ?></p>

            <form method="POST" action="" class="bg-gray-50 p-4 rounded-lg">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="msg_id" value="<?php echo $m['id']; ?>">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Admin Reply</label>
                    <textarea name="admin_reply" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-blue-500 outline-none h-24" placeholder="Type your response here..."><?php echo htmlspecialchars($m['admin_reply'] ?? ''); ?></textarea>
                </div>

                <div class="flex items-center justify-between">
                    <select name="status" class="text-sm border border-gray-300 rounded px-3 py-1.5 outline-none">
                        <option value="new" <?php echo $m['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                        <option value="read" <?php echo $m['status'] === 'read' ? 'selected' : ''; ?>>Read</option>
                        <option value="replied" <?php echo $m['status'] === 'replied' ? 'selected' : ''; ?>>Replied</option>
                        <option value="closed" <?php echo $m['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                    <button type="submit" name="update_status" class="bg-gray-800 text-white px-4 py-1.5 rounded text-sm font-semibold hover:bg-black transition">Save Changes</button>
                </div>
            </form>
        </div>
        <div class="px-6 py-3 bg-gray-50 text-xs text-gray-400 italic">
            Received on: <?php echo date('F j, Y, g:i a', strtotime($m['created_at'])); ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once 'footer.php'; ?>