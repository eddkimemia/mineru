<?php
/**
 * config.php - Central configuration for CryptoERP Miner
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_PATH', __DIR__ . '/database.db');

// Daraja M-Pesa API Credentials
define('CONSUMER_KEY', 'your_actual_consumer_key');
define('CONSUMER_SECRET', 'your_actual_consumer_secret');
define('PASSKEY', 'your_actual_passkey');
define('SHORTCODE', '174379'); // Sandbox Shortcode
define('B2C_SHORTCODE', '600000'); // Sandbox B2C Shortcode
define('CALLBACK_URL', 'https://yourdomain.com/mpesa-callback.php');

// Application Settings
define('APP_NAME', 'CryptoERP Miner');
define('BASE_URL', 'http://localhost:8000'); // Update for production

// Security
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * PDO Database Connection
 */
try {
    $pdo = new PDO("sqlite:" . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
