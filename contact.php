<?php
// contact.php
require_once 'config.php';
verify_csrf_token();

// Initialize data
$user_id = $_SESSION['user_id'] ?? null;
$user = null;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}

$error = '';
$success = '';
$form_data = [
    'name' => $user ? $user['full_name'] : '',
    'email' => $user ? $user['email'] : '',
    'subject' => '',
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $form_data['name'] = htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $form_data['email'] = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $form_data['subject'] = htmlspecialchars($_POST['subject'] ?? '', ENT_QUOTES, 'UTF-8');
    $form_data['message'] = htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8');

    if (empty($form_data['name']) || empty($form_data['email']) || empty($form_data['subject']) || empty($form_data['message'])) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (user_id, name, email, subject, message, status, created_at) VALUES (?, ?, ?, ?, ?, 'new', NOW())");
            $stmt->execute([$user_id, $form_data['name'], $form_data['email'], $form_data['subject'], $form_data['message']]);
            $success = 'Your message has been sent successfully!';
            $form_data['subject'] = '';
            $form_data['message'] = '';
        } catch (Exception $e) {
            $error = 'Failed to send message.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Us - CryptoMiner ERP</title>
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include 'header.php'; ?>
    <main class="flex-grow container mx-auto px-4 py-12">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-sm p-8">
            <h1 class="text-2xl font-semibold mb-6 text-center">Contact Us</h1>
            <?php if ($error): ?><div class="mb-4 p-3 bg-red-50 text-red-600 rounded"><?php echo $error; ?></div><?php endif; ?>
            <?php if ($success): ?><div class="mb-4 p-3 bg-green-50 text-green-600 rounded"><?php echo $success; ?></div><?php endif; ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Name</label>
                    <input type="text" name="name" required class="w-full border rounded px-3 py-2" value="<?php echo htmlspecialchars($form_data['name']); ?>">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input type="email" name="email" required class="w-full border rounded px-3 py-2" value="<?php echo htmlspecialchars($form_data['email']); ?>">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Subject</label>
                    <input type="text" name="subject" required class="w-full border rounded px-3 py-2" value="<?php echo htmlspecialchars($form_data['subject']); ?>">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-1">Message</label>
                    <textarea name="message" required class="w-full border rounded px-3 py-2 h-32"><?php echo htmlspecialchars($form_data['message']); ?></textarea>
                </div>
                <button type="submit" name="submit_contact" class="w-full bg-primary text-white py-2 rounded">Send Message</button>
            </form>
        </div>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>
