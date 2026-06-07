<?php

namespace App\Helpers;

class Security
{
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    public static function generateRandomCode($length = 6)
    {
        return sprintf("%0{$length}d", mt_rand(0, pow(10, $length) - 1));
    }
}
