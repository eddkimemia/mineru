<?php
require_once 'config.php';
verify_csrf_token();
if (isset($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $terms = isset($_POST['terms']);
    if (empty($full_name) || empty($email) || empty($password) || !$terms) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email.';
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Email already exists.';
        } else {
            $username = strtolower(str_replace(' ', '_', $full_name)) . '_' . rand(100, 999);
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $token = bin2hex(random_bytes(16));
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, referral_code, account_status, verification_token) VALUES (?, ?, ?, ?, ?, 'pending', ?)");
                $stmt->execute([$username, $email, $password_hash, $full_name, 'REF' . rand(1000, 9999), $token]);
                $user_id = $pdo->lastInsertId();
                $pdo->prepare("INSERT INTO user_balances (user_id) VALUES (?)")->execute([$user_id]);
                $pdo->commit();
                $link = "http://" . $_SERVER['HTTP_HOST'] . "/verify_email.php?token=$token";
                $success = "Registered! Click <a href='$link' class='underline font-bold'>here</a> to verify (Dev Mode).";
            } catch (Exception $e) { $pdo->rollBack(); $error = 'Registration failed.'; }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Register - CryptoMiner ERP</title>
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include 'header.php'; ?>
    <main class="container mx-auto px-4 py-12 max-w-md">
        <div class="bg-white p-8 rounded shadow border">
            <h1 class="text-2xl font-bold mb-6 text-center">Create Account</h1>
            <?php if($error): ?><div class="mb-4 p-3 bg-red-50 text-red-600 rounded"><?php echo $error; ?></div><?php endif; ?>
            <?php if($success): ?><div class="mb-4 p-3 bg-green-50 text-green-600 rounded"><?php echo $success; ?></div><?php endif; ?>
            <?php if(!$success): ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Full Name</label>
                    <input type="text" name="full_name" required class="w-full border rounded px-3 py-2">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input type="email" name="email" required class="w-full border rounded px-3 py-2">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Password</label>
                    <input type="password" name="password" required class="w-full border rounded px-3 py-2">
                </div>
                <div class="mb-6">
                    <label class="flex items-center text-sm">
                        <input type="checkbox" name="terms" required class="mr-2"> I agree to terms
                    </label>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded font-bold hover:bg-blue-700 transition">Sign Up</button>
            </form>
            <?php endif; ?>
            <p class="mt-4 text-center text-sm">Already have an account? <a href="login.php" class="text-blue-600">Login</a></p>
        </div>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>