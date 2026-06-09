<?php
require_once 'functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $fullname = sanitize($_POST['fullname']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $ref_code = sanitize($_POST['referral_code'] ?? '');

    if (empty($fullname) || empty($email) || empty($phone) || empty($password)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
        $stmt->execute([$email, $phone]);
        if ($stmt->fetch()) {
            $error = 'Email or Phone already registered.';
        } else {
            // Process referral
            $referred_by = null;
            if (!empty($ref_code)) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
                $stmt->execute([$ref_code]);
                $referrer = $stmt->fetch();
                if ($referrer) {
                    $referred_by = $referrer['id'];
                }
            }

            // Generate referral code
            $new_ref_code = 'ERP' . strtoupper(bin2hex(random_bytes(3)));
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            try {
                $stmt = $pdo->prepare("INSERT INTO users (fullname, email, phone, password_hash, referral_code, referred_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$fullname, $email, $phone, $password_hash, $new_ref_code, $referred_by]);

                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['role'] = 'user';
                $_SESSION['fullname'] = $fullname;

                redirect('dashboard.php');
            } catch (PDOException $e) {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CryptoERP Miner</title>
    <script src="https://cdn.tailwindcss.com/"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
    <style>
        .glass {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="bg-slate-900 text-white min-h-screen flex items-center justify-center p-4">
    <div class="glass p-8 rounded-2xl w-full max-w-md shadow-2xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-emerald-400 to-teal-500 bg-clip-text text-transparent">CryptoERP Miner</h1>
            <p class="text-slate-400 mt-2">Create your mining account</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/20 border border-red-500/50 text-red-200 p-3 rounded-lg mb-6 text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div>
                <label class="block text-sm font-medium mb-1">Full Name</label>
                <div class="relative">
                    <i class="ri-user-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="fullname" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg py-2.5 pl-10 pr-4 focus:ring-2 focus:ring-emerald-500 outline-none transition" placeholder="John Doe">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Email Address</label>
                <div class="relative">
                    <i class="ri-mail-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="email" name="email" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg py-2.5 pl-10 pr-4 focus:ring-2 focus:ring-emerald-500 outline-none transition" placeholder="john@example.com">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Phone Number (M-Pesa)</label>
                <div class="relative">
                    <i class="ri-phone-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="phone" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg py-2.5 pl-10 pr-4 focus:ring-2 focus:ring-emerald-500 outline-none transition" placeholder="254712345678">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Password</label>
                    <input type="password" name="password" id="password" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-emerald-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Confirm</label>
                    <input type="password" name="confirm_password" id="confirm_password" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-emerald-500 outline-none transition">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Referral Code (Optional)</label>
                <input type="text" name="referral_code" class="w-full bg-slate-800/50 border border-slate-700 rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-emerald-500 outline-none transition" placeholder="ERPXXXXXX">
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-emerald-500 to-teal-600 hover:scale-[1.02] active:scale-95 text-white font-bold py-3 rounded-lg transition-all shadow-lg mt-4">
                Create Account
            </button>
        </form>

        <p class="text-center text-slate-400 mt-6 text-sm">
            Already have an account? <a href="login.php" class="text-emerald-400 hover:underline">Login here</a>
        </p>
    </div>
</body>
</html>
