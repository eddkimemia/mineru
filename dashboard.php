<?php
require_once 'config.php';
verify_csrf_token();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_id = $_SESSION['user_id'];
$error = ''; $success = '';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase_miner'])) {
        $package_id = filter_input(INPUT_POST, 'package_id', FILTER_VALIDATE_INT);
        $stmt = $pdo->prepare("SELECT * FROM mining_packages WHERE id = ?");
        $stmt->execute([$package_id]);
        $package = $stmt->fetch();
        $stmt = $pdo->prepare("SELECT * FROM user_balances WHERE user_id = ? FOR UPDATE");
        $stmt->execute([$user_id]);
        $balance = $stmt->fetch();
        if ($package && $balance && $balance['available_balance'] >= $package['price']) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE user_balances SET available_balance = available_balance - ? WHERE user_id = ?");
            $stmt->execute([$package['price'], $user_id]);
            $stmt = $pdo->prepare("INSERT INTO user_miners (user_id, package_id, status, days_remaining) VALUES (?, ?, 'active', ?)");
            $stmt->execute([$user_id, $package_id, $package['duration_days']]);
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, method, status, transaction_hash) VALUES (?, 'purchase', ?, 'wallet', 'completed', ?)");
            $stmt->execute([$user_id, -$package['price'], 'TX_PUR_' . bin2hex(random_bytes(4))]);
            $pdo->commit();
            $success = "Purchased!";
        } else { $error = "Insufficient balance or invalid package."; }
    }
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $stmt = $pdo->prepare("SELECT * FROM user_balances WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $balance = $stmt->fetch() ?: ['available_balance' => 0];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_miners WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$user_id]);
    $active_miners = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_id = ?");
    $stmt->execute([$user_id]);
    $referral_count = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM mining_packages WHERE is_active = 1");
    $stmt->execute();
    $mining_packages = $stmt->fetchAll();
} catch (Exception $e) { error_log($e->getMessage()); }
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard - CryptoMiner ERP</title>
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include 'header.php'; ?>
    <main class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Dashboard</h1>
        <?php if($success): ?><div class="mb-4 p-3 bg-green-50 text-green-600 rounded"><?php echo $success; ?></div><?php endif; ?>
        <?php if($error): ?><div class="mb-4 p-3 bg-red-50 text-red-600 rounded"><?php echo $error; ?></div><?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded shadow-sm border">
                <p class="text-gray-500 text-sm">Balance</p>
                <p class="text-2xl font-bold">$<?php echo number_format($balance['available_balance'], 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded shadow-sm border">
                <p class="text-gray-500 text-sm">Active Miners</p>
                <p class="text-2xl font-bold"><?php echo $active_miners; ?></p>
            </div>
            <div class="bg-white p-6 rounded shadow-sm border">
                <p class="text-gray-500 text-sm">Referrals</p>
                <p class="text-2xl font-bold"><?php echo $referral_count; ?>/3</p>
            </div>
        </div>

        <h2 class="text-xl font-bold mb-4">Mining Packages</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach($mining_packages as $p): ?>
            <div class="bg-white p-6 rounded shadow-sm border">
                <h3 class="font-bold text-lg"><?php echo htmlspecialchars($p['name']); ?></h3>
                <p class="text-blue-600 font-bold">$<?php echo number_format($p['price'], 2); ?></p>
                <p class="text-sm text-gray-500"><?php echo $p['duration_days']; ?> days</p>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="package_id" value="<?php echo $p['id']; ?>">
                    <button type="submit" name="purchase_miner" class="mt-4 w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">Purchase</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>