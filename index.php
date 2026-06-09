<?php
require_once 'functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CryptoERP Miner - Next Gen Crypto Mining Platform</title>
    <script src="https://cdn.tailwindcss.com/"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-950 text-slate-200 overflow-x-hidden">
    <!-- Hero Background Effects -->
    <div class="fixed top-0 left-0 w-full h-full -z-10 overflow-hidden">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-emerald-500/10 blur-[120px] rounded-full animate-float"></div>
        <div class="absolute bottom-[10%] right-[-5%] w-[30%] h-[30%] bg-blue-600/10 blur-[100px] rounded-full animate-float" style="animation-delay: 2s;"></div>
    </div>

    <!-- Header -->
    <header class="container mx-auto px-6 py-8 flex justify-between items-center relative z-10">
        <div class="flex items-center space-x-2">
            <div class="w-10 h-10 bg-gradient-to-tr from-emerald-400 to-teal-600 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/20">
                <i class="ri-cpu-line text-white text-xl"></i>
            </div>
            <span class="text-2xl font-extrabold bg-gradient-to-r from-white to-slate-400 bg-clip-text text-transparent tracking-tighter">CryptoERP</span>
        </div>
        <div class="hidden md:flex items-center space-x-8 text-sm font-medium text-slate-400">
            <a href="#how" class="hover:text-emerald-400 transition">How it Works</a>
            <a href="#features" class="hover:text-emerald-400 transition">Features</a>
            <a href="#referral" class="hover:text-emerald-400 transition">Referral</a>
            <a href="login.php" class="text-white hover:text-emerald-400 transition">Login</a>
            <a href="register.php" class="bg-white text-slate-950 px-6 py-2.5 rounded-full font-bold hover:bg-emerald-400 transition shadow-lg">Get Started</a>
        </div>
        <button class="md:hidden text-white text-2xl"><i class="ri-menu-4-line"></i></button>
    </header>

    <!-- Hero Section -->
    <section class="container mx-auto px-6 pt-20 pb-32 relative z-10">
        <div class="max-w-4xl mx-auto text-center">
            <div class="inline-flex items-center space-x-2 bg-emerald-500/10 border border-emerald-500/20 px-4 py-2 rounded-full text-emerald-400 text-xs font-bold uppercase tracking-widest mb-8">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span>Now Live in Kenya</span>
            </div>
            <h1 class="text-5xl md:text-7xl font-extrabold text-white mb-8 tracking-tight leading-[1.1]">
                Mining Simplified. <br>
                <span class="bg-gradient-to-r from-emerald-400 to-teal-500 bg-clip-text text-transparent">Profit Multiplied.</span>
            </h1>
            <p class="text-lg md:text-xl text-slate-400 mb-12 max-w-2xl mx-auto leading-relaxed">
                Join Kenya's premier mining ERP system. Deposit KES via M-Pesa, activate your hashrate, and watch your earnings grow daily. No hardware required.
            </p>
            <div class="flex flex-col sm:flex-row justify-center items-center gap-6">
                <a href="register.php" class="w-full sm:w-auto bg-gradient-to-r from-emerald-500 to-teal-600 text-white px-10 py-5 rounded-2xl font-bold text-lg hover:scale-105 transition shadow-2xl shadow-emerald-500/20">
                    Start Mining Today
                </a>
                <a href="#how" class="w-full sm:w-auto px-10 py-5 rounded-2xl font-bold text-lg border border-slate-800 hover:bg-slate-900 transition">
                    Learn More
                </a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="container mx-auto px-6 mb-32 relative z-10">
        <div class="glass-morphism rounded-[40px] p-8 md:p-12 grid grid-cols-1 md:grid-cols-3 gap-12 text-center">
            <div>
                <h4 class="text-4xl font-extrabold text-white mb-2">12.5M+</h4>
                <p class="text-slate-500 uppercase tracking-widest text-xs font-bold">Total KES Paid</p>
            </div>
            <div class="border-y md:border-y-0 md:border-x border-slate-800 py-8 md:py-0">
                <h4 class="text-4xl font-extrabold text-white mb-2">15K+</h4>
                <p class="text-slate-500 uppercase tracking-widest text-xs font-bold">Active Miners</p>
            </div>
            <div>
                <h4 class="text-4xl font-extrabold text-white mb-2">99.9%</h4>
                <p class="text-slate-500 uppercase tracking-widest text-xs font-bold">Uptime Guaranteed</p>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how" class="container mx-auto px-6 py-20 mb-32">
        <div class="text-center mb-20">
            <h2 class="text-4xl font-bold text-white mb-4">How to Start</h2>
            <p class="text-slate-400">Three simple steps to financial freedom.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
            <div class="group">
                <div class="w-16 h-16 bg-slate-900 border border-slate-800 rounded-2xl flex items-center justify-center mb-8 group-hover:border-emerald-500/50 transition">
                    <i class="ri-user-add-line text-2xl text-emerald-500"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-4">1. Quick Registration</h3>
                <p class="text-slate-500 leading-relaxed">Create your account in 30 seconds. All you need is your name, email, and M-Pesa phone number.</p>
            </div>
            <div class="group">
                <div class="w-16 h-16 bg-slate-900 border border-slate-800 rounded-2xl flex items-center justify-center mb-8 group-hover:border-blue-500/50 transition">
                    <i class="ri-wallet-3-line text-2xl text-blue-500"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-4">2. Fund via M-Pesa</h3>
                <p class="text-slate-500 leading-relaxed">Deposit KES instantly using STK Push. Your balance reflects immediately in your wallet.</p>
            </div>
            <div class="group">
                <div class="w-16 h-16 bg-slate-900 border border-slate-800 rounded-2xl flex items-center justify-center mb-8 group-hover:border-teal-500/50 transition">
                    <i class="ri-pulse-line text-2xl text-teal-500"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-4">3. Start Mining</h3>
                <p class="text-slate-500 leading-relaxed">Activate your mining session. Rewards accrue daily based on your current hashrate.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-slate-900 py-12">
        <div class="container mx-auto px-6 text-center">
            <p class="text-slate-500 text-sm">&copy; 2024 CryptoERP Miner. All rights reserved. Registered in Kenya.</p>
            <div class="mt-6 flex justify-center space-x-6 text-slate-600">
                <a href="#" class="hover:text-white transition"><i class="ri-twitter-x-line text-xl"></i></a>
                <a href="#" class="hover:text-white transition"><i class="ri-telegram-line text-xl"></i></a>
                <a href="#" class="hover:text-white transition"><i class="ri-whatsapp-line text-xl"></i></a>
            </div>
        </div>
    </footer>
</body>
</html>
