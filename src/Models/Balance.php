<?php

namespace App\Models;

use App\Core\Model;

class Balance extends Model
{
    public function getByUserId($userId)
    {
        return $this->db->query("SELECT * FROM user_balances WHERE user_id = ?", [$userId])->fetch();
    }

    public function update($userId, $data)
    {
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $params[] = $value;
        }
        $params[] = $userId;
        $sql = "UPDATE user_balances SET " . implode(', ', $fields) . " WHERE user_id = ?";
        return $this->db->query($sql, $params);
    }
}
