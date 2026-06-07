<?php

namespace App\Core;

abstract class Controller
{
    protected function view($template, $data = [])
    {
        return View::render($template, $data);
    }

    protected function redirect($url)
    {
        header("Location: $url");
        exit;
    }

    protected function json($data, $status = 200)
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}
