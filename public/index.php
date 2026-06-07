<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Bramus\Router\Router;
use App\Core\View;
use App\Core\Session;
use App\Middleware\CsrfMiddleware;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Start session
Session::init();

// Initialize View
View::init();

// CSRF Protection
CsrfMiddleware::handle();

// Create Router
$router = new Router();

// Define routes
require_once __DIR__ . '/../config/routes.php';

// Run it!
$router->run();
