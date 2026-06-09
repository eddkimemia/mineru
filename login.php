<?php
require_once 'functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $identifier = sanitize($_POST['identifier']); // email or phone
    $password = $_POST['password'];

    if (empty($identifier) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $user = getUserByEmailOrPhone($identifier);

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['is_banned']) {
                $error = 'Your account has been suspended.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['fullname'] = $user['fullname'];

                if ($user['role'] === 'admin') {
                    redirect('admin.php');
                } else {
                    redirect('dashboard.php');
                }
            }
        } else {
            $error = 'Invalid email/phone or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CryptoERP Miner</title>
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
            <p class="text-slate-400 mt-2">Welcome back! Please login</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/20 border border-red-500/50 text-red-200 p-3 rounded-lg mb-6 text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div>
                <label class="block text-sm font-medium mb-1">Email or Phone</label>
                <div class="relative">
                    <i class="ri-user-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="identifier" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg py-2.5 pl-10 pr-4 focus:ring-2 focus:ring-emerald-500 outline-none transition" placeholder="Email or 254...">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Password</label>
                <div class="relative">
                    <i class="ri-lock-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="password" name="password" id="password" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg py-2.5 pl-10 pr-10 focus:ring-2 focus:ring-emerald-500 outline-none transition">
                    <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white">
                        <i id="toggleIcon" class="ri-eye-line"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-emerald-500 to-teal-600 hover:scale-[1.02] active:scale-95 text-white font-bold py-3 rounded-lg transition-all shadow-lg">
                Login
            </button>
        </form>

        <p class="text-center text-slate-400 mt-8 text-sm">
            Don't have an account? <a href="register.php" class="text-emerald-400 hover:underline">Register now</a>
        </p>
    </div>

    <script>
        function togglePassword() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.className = 'ri-eye-off-line';
            } else {
                pwd.type = 'password';
                icon.className = 'ri-eye-line';
            }
        }
    </script>
</body>
</html>
