# CryptoERP Miner

A modern, production-ready crypto mining ERP platform built with PHP and Tailwind CSS.

## Features

- **Modern UI**: Clean, responsive dashboard with glassmorphism effects.
- **M-Pesa Integration**: Supports Safaricom Daraja API for STK Push deposits and B2C withdrawals.
- **Mining Simulation**: Daily accrual of rewards based on hashrate.
- **Referral System**: Earn bonuses from referred users' first deposits and increase hashrate.
- **Admin Panel**: Manage users, approve withdrawals, and configure platform settings.
- **Secure**: CSRF protection, password hashing, and input sanitization.

## File Structure

- `index.php`: Landing page.
- `dashboard.php`: User dashboard and mining command center.
- `admin.php`: Administrative control panel.
- `login.php` / `register.php`: Authentication pages.
- `deposit.php` / `withdraw.php`: Wallet operations.
- `mpesa-callback.php`: Daraja API callback endpoint.
- `mining-cron.php`: Script to process daily rewards.
- `config.php`: Central configuration and database connection.
- `functions.php`: Reusable helper functions.
- `database.db`: SQLite database.
- `database.sql`: Database schema definition.

## Setup Instructions

### 1. Database Setup
The system uses SQLite. The `database.db` is automatically created if it doesn't exist when you run the schema.
To re-initialize:
```bash
sqlite3 database.db < database.sql
```

### 2. Configuration
Update `config.php` with your actual Daraja API credentials:
- `CONSUMER_KEY`
- `CONSUMER_SECRET`
- `PASSKEY`
- `CALLBACK_URL`

### 3. Cron Job
Set up a daily cron job to distribute mining rewards:
```bash
0 0 * * * php /path/to/mining-cron.php
```

### 4. Admin Access
- **Default Admin Email**: `admin@cryptoerp.com`
- **Default Password**: `admin123`

## Testing Locally
You can use `ngrok` to test M-Pesa callbacks locally:
```bash
ngrok http 8000
```
Then update `CALLBACK_URL` in `config.php` with your ngrok URL.
