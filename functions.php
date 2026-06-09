<?php
/**
 * functions.php - Core helper functions
 */

require_once 'config.php';

/**
 * CSRF Validation
 */
function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('CSRF token validation failed.');
        }
    }
}

/**
 * Get site settings
 */
function get_settings($key) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? json_decode($result['setting_value'], true) : null;
}

/**
 * User Authentication
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * User Management
 */
function getUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getUserByEmailOrPhone($identifier) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
    $stmt->execute([$identifier, $identifier]);
    return $stmt->fetch();
}

/**
 * Wallet and Transactions
 */
function updateWallet($user_id, $amount, $type, $notes = '') {
    global $pdo;
    try {
        $pdo->beginTransaction();

        // Update user balance
        if ($type === 'deposit' || $type === 'mining_reward' || $type === 'referral_bonus') {
            $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
        } else if ($type === 'withdraw') {
            $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
        }
        $stmt->execute([$amount, $user_id]);

        // Record transaction
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status, notes) VALUES (?, ?, ?, 'completed', ?)");
        $stmt->execute([$user_id, $type, $amount, $notes]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Wallet update failed: " . $e->getMessage());
        return false;
    }
}

/**
 * M-Pesa Integration (STK Push)
 */
function get_mpesa_access_token() {
    $credentials = base64_encode(CONSUMER_KEY . ':' . CONSUMER_SECRET);
    $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $credentials));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($curl);
    $result = json_decode($response);
    return $result->access_token ?? null;
}

function mpesa_stk_push($phone, $amount, $reference) {
    $token = get_mpesa_access_token();
    if (!$token) return false;

    $timestamp = date('YmdHis');
    $password = base64_encode(SHORTCODE . PASSKEY . $timestamp);

    $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/query'; // Error in plan? Should be /mpesa/stkpush/v1/processrequest
    $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

    $curl_post_data = array(
        'BusinessShortCode' => SHORTCODE,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phone,
        'PartyB' => SHORTCODE,
        'PhoneNumber' => $phone,
        'CallBackURL' => CALLBACK_URL,
        'AccountReference' => $reference,
        'TransactionDesc' => 'Deposit to CryptoERP'
    );

    $data_string = json_encode($curl_post_data);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer ' . $token));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

    $response = curl_exec($curl);
    return json_decode($response, true);
}

/**
 * Mining Logic
 */
function calculateMiningReward($hashrate) {
    $settings = get_settings('mining_config');
    $rate = $settings['mining_rate_per_hash'] ?? 0.05;
    return $hashrate * $rate;
}

/**
 * Sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
