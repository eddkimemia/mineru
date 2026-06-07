<?php

namespace App\Middleware;

use App\Core\Session;

class CsrfMiddleware
{
    public static function handle()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!Session::verifyCsrfToken($token)) {
                http_response_code(403);
                die('CSRF token validation failed.');
            }
        }
    }
}
