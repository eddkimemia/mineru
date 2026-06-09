-- CryptoERP Miner Database Schema (SQLite)

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    fullname TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    phone TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    referral_code TEXT UNIQUE NOT NULL,
    referred_by INTEGER DEFAULT NULL,
    role TEXT CHECK(role IN ('user', 'admin')) DEFAULT 'user',
    wallet_balance REAL DEFAULT 0.00,
    mining_hashrate REAL DEFAULT 10.00,
    total_mined REAL DEFAULT 0.00,
    total_referral_bonus REAL DEFAULT 0.00,
    is_banned INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS mining_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    hashrate REAL NOT NULL,
    start_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    end_time DATETIME NULL,
    reward_earned REAL DEFAULT 0.00,
    status TEXT CHECK(status IN ('active', 'completed')) DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type TEXT CHECK(type IN ('deposit', 'withdraw', 'mining_reward', 'referral_bonus')) NOT NULL,
    amount REAL NOT NULL,
    status TEXT CHECK(status IN ('pending', 'completed', 'failed')) DEFAULT 'pending',
    mpesa_receipt TEXT DEFAULT NULL,
    checkout_request_id TEXT DEFAULT NULL,
    reference TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS withdrawals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    phone_number TEXT NOT NULL,
    status TEXT CHECK(status IN ('pending', 'processed', 'failed')) DEFAULT 'pending',
    admin_notes TEXT DEFAULT NULL,
    processed_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS mpesa_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    transaction_type TEXT CHECK(transaction_type IN ('stkpush', 'b2c', 'callback')) NOT NULL,
    request_id TEXT DEFAULT NULL,
    raw_request TEXT DEFAULT NULL,
    raw_response TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS site_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Seed initial settings
INSERT INTO site_settings (setting_key, setting_value) VALUES
('mining_config', '{"mining_rate_per_hash": 0.05, "min_withdraw": 100, "referral_percent": 10, "min_deposit": 10}');

-- Seed initial admin (Password: admin123)
INSERT INTO users (fullname, email, phone, password_hash, referral_code, role) VALUES
('System Admin', 'admin@cryptoerp.com', '254700000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN001', 'admin');
