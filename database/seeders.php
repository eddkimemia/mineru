<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

$db = Database::getInstance();

echo "Seeding mining packages...\n";
$packages = [
    ['Starter Miner', 10.00, 0.90, 9.00, 20, true, false],
    ['Basic Miner', 25.00, 2.25, 9.00, 20, true, false],
    ['Standard Miner', 50.00, 4.50, 9.00, 20, true, true],
    ['Advanced Miner', 75.00, 6.75, 9.00, 20, true, false],
    ['Premium Miner', 120.00, 10.80, 9.00, 20, true, false],
];

foreach ($packages as $p) {
    $db->query(
        "INSERT INTO mining_packages (name, price, daily_profit, daily_return_percentage, duration_days, is_active, is_popular)
         VALUES (?, ?, ?, ?, ?, ?, ?)",
        $p
    );
}

echo "Seeding admin account...\n";
$admin_user = 'admin';
$admin_email = 'admin@cryptominer.com';
$admin_pass = password_hash('admin123', PASSWORD_BCRYPT);

$db->query(
    "INSERT INTO admins (username, email, password_hash) VALUES (?, ?, ?)",
    [$admin_user, $admin_email, $admin_pass]
);

echo "Done seeding.\n";
