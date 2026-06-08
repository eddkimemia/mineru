<?php
// admin/header.php
require_once 'auth.php';
check_admin_auth();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - CryptoMiner ERP</title>
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Navigation -->
    <nav class="bg-gray-800 text-white shadow-md">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center">
                <a href="index.php" class="text-xl font-bold text-blue-400 mr-8">Admin Console</a>
                <div class="hidden md:flex space-x-6 text-sm font-medium">
                    <a href="index.php" class="hover:text-blue-400 transition">Dashboard</a>
                    <a href="users.php" class="hover:text-blue-400 transition">Users</a>
                    <a href="packages.php" class="hover:text-blue-400 transition">Packages</a>
                    <a href="transactions.php" class="hover:text-blue-400 transition">Transactions</a>
                    <a href="messages.php" class="hover:text-blue-400 transition">Messages</a>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <span class="hidden sm:inline text-xs text-gray-400">Signed in as: <span class="text-white"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span></span>
                <a href="logout.php" class="text-gray-400 hover:text-red-400 transition text-lg" title="Sign Out">
                    <i class="ri-logout-box-r-line"></i>
                </a>
                <!-- Mobile menu button -->
                <button class="md:hidden text-2xl text-gray-400" onclick="document.getElementById('adminMobileMenu').classList.toggle('hidden')">
                    <i class="ri-menu-line"></i>
                </button>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="adminMobileMenu" class="md:hidden bg-gray-700 hidden border-t border-gray-600">
            <div class="px-4 py-3 space-y-3">
                <a href="index.php" class="block hover:text-blue-400">Dashboard</a>
                <a href="users.php" class="block hover:text-blue-400">Users</a>
                <a href="packages.php" class="block hover:text-blue-400">Packages</a>
                <a href="transactions.php" class="block hover:text-blue-400">Transactions</a>
                <a href="messages.php" class="block hover:text-blue-400">Messages</a>
                <a href="logout.php" class="block text-red-400">Sign Out</a>
            </div>
        </div>
    </nav>
    <main class="flex-grow container mx-auto px-4 py-8">