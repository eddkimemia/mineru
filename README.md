# CryptoMiner ERP - Modern PHP Refactor

This is a production-ready, modern PHP refactor of the CryptoMiner ERP system. It follows PSR-12 coding standards, implements the MVC pattern, and includes robust security features.

## Key Features

- **Architecture:** Full MVC pattern implementation.
- **Security:** CSRF protection, secure session handling, PDO prepared statements (SQL injection protection), XSS protection, and password hashing (`password_hash()`).
- **Frontend:** Responsive, mobile-first design using TailwindCSS and Twig template engine.
- **Authentication:** Secure user and admin login with email verification for new users.
- **Dashboard:** Comprehensive user dashboard with mining stats and earnings charts.
- **Wallet:** Deposit and withdrawal system with transaction tracking.
- **Admin Panel:** Full administrative control over users, mining packages, and transactions.

## Technologies Used

- PHP 7.4+
- MySQL
- TailwindCSS
- Twig (Template Engine)
- Composer (Dependency Management)
- PHPMailer (Email)
- Monolog (Logging)
- PHP Dotenv (Configuration)

## Setup Instructions

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer

### Installation

1. **Clone the repository:**
   ```bash
   git clone <repo-url>
   cd <repo-name>
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Configure environment variables:**
   Copy `.env.example` to `.env` and update your database credentials and SMTP settings.
   ```bash
   cp .env.example .env
   ```

4. **Database Setup:**
   Import the schema and run seeders:
   ```bash
   mysql -u your_user -p your_db < database/database.sql
   php database/seeders.php
   ```

5. **Run the application:**
   Using PHP built-in server:
   ```bash
   php -S localhost:8000 -t public
   ```
   Or use Docker:
   ```bash
   docker build -t cryptominer-erp .
   docker run -p 8000:80 cryptominer-erp
   ```

## Admin Access

- **URL:** `/admin/login`
- **Default Username:** `admin`
- **Default Password:** `admin123` (Please change this immediately in production)

## Security Note

Ensure the `logs/` directory is writable by the web server and the `.env` file is never publicly accessible.
