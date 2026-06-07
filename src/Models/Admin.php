<?php

namespace App\Models;

use App\Core\Model;

class Admin extends Model
{
    public function findByEmail($email)
    {
        return $this->db->query("SELECT * FROM admins WHERE email = ?", [$email])->fetch();
    }

    public function findByUsername($username)
    {
        return $this->db->query("SELECT * FROM admins WHERE username = ?", [$username])->fetch();
    }
}
