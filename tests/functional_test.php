<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Models\User;
use App\Models\Admin;
use App\Helpers\Security;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

function test($name, $callback) {
    echo "Testing $name... ";
    try {
        $callback();
        echo "\033[32mPASSED\033[0m\n";
    } catch (\Exception $e) {
        echo "\033[31mFAILED\033[0m: " . $e->getMessage() . "\n";
    }
}

test("Database Connection", function() {
    $db = Database::getInstance();
    if (!$db->getConnection()) throw new Exception("Could not connect to DB");
});

test("User Model Creation", function() {
    $userModel = new User();
    $username = 'testuser_' . time();
    $email = $username . '@example.com';

    $userId = $userModel->create([
        'username' => $username,
        'full_name' => 'Test User',
        'email' => $email,
        'password_hash' => Security::hashPassword('password123'),
        'referral_code' => 'TEST_REF',
        'referred_by' => null,
        'account_status' => 'pending',
        'verification_code' => '123456',
        'verification_expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
    ]);

    if (!$userId) throw new Exception("User creation failed");

    $user = $userModel->findByEmail($email);
    if ($user['username'] !== $username) throw new Exception("Username mismatch");
});

test("Admin Authentication", function() {
    $adminModel = new Admin();
    $admin = $adminModel->findByUsername('admin');
    if (!$admin) throw new Exception("Admin not found (did you run seeders?)");

    // Default password from seeder is 'admin123'
    if (!Security::verifyPassword('admin123', $admin['password_hash'])) {
        throw new Exception("Admin password verification failed");
    }
});

echo "\nAll tests completed.\n";
