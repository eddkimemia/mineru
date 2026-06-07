<?php

/** @var Bramus\Router\Router $router */

$router->get('/', 'App\Controllers\HomeController@index');

// Auth routes
$router->get('/login', 'App\Controllers\AuthController@showLogin');
$router->post('/login', 'App\Controllers\AuthController@login');
$router->get('/register', 'App\Controllers\AuthController@showRegister');
$router->post('/register', 'App\Controllers\AuthController@register');
$router->get('/verify', 'App\Controllers\AuthController@showVerify');
$router->post('/verify', 'App\Controllers\AuthController@verify');
$router->get('/logout', 'App\Controllers\AuthController@logout');

// User routes
$router->mount('/dashboard', function() use ($router) {
    $router->get('/', 'App\Controllers\DashboardController@index');
});

$router->mount('/profile', function() use ($router) {
    $router->get('/', 'App\Controllers\ProfileController@index');
    $router->post('/update', 'App\Controllers\ProfileController@update');
});

$router->mount('/wallet', function() use ($router) {
    $router->get('/', 'App\Controllers\WalletController@index');
    $router->get('/deposit', 'App\Controllers\WalletController@showDeposit');
    $router->post('/deposit', 'App\Controllers\WalletController@deposit');
    $router->get('/withdraw', 'App\Controllers\WalletController@showWithdraw');
    $router->post('/withdraw', 'App\Controllers\WalletController@withdraw');
});

$router->mount('/miners', function() use ($router) {
    $router->get('/', 'App\Controllers\MinerController@index');
    $router->post('/purchase', 'App\Controllers\MinerController@purchase');
});

$router->get('/referrals', 'App\Controllers\ReferralController@index');

// Admin routes
$router->mount('/admin', function() use ($router) {
    $router->get('/login', 'App\Controllers\Admin\AuthController@showLogin');
    $router->post('/login', 'App\Controllers\Admin\AuthController@login');
    $router->get('/logout', 'App\Controllers\Admin\AuthController@logout');

    $router->get('/dashboard', 'App\Controllers\Admin\AdminController@dashboard');
    $router->get('/users', 'App\Controllers\Admin\AdminController@users');
    $router->get('/packages', 'App\Controllers\Admin\AdminController@packages');
    $router->get('/transactions', 'App\Controllers\Admin\AdminController@transactions');
    $router->post('/transactions/approve', 'App\Controllers\Admin\AdminController@approveTransaction');
});

// Error handling
$router->set404(function() {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    echo '404 - Page not found';
});
