<?php

namespace App\Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class View
{
    private static $twig = null;

    public static function init()
    {
        if (self::$twig === null) {
            $loader = new FilesystemLoader(__DIR__ . '/../../views');
            self::$twig = new Environment($loader, [
                'cache' => false, // Set to __DIR__ . '/../../cache' in production
                'debug' => true,
            ]);

            // Add global variables
            self::$twig->addGlobal('session', $_SESSION);
        }
    }

    public static function render($template, $data = [])
    {
        self::init();
        try {
            echo self::$twig->render($template . '.twig', $data);
        } catch (\Exception $e) {
            die("View error: " . $e->getMessage());
        }
    }
}
