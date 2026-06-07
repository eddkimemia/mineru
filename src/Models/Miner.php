<?php

namespace App\Models;

use App\Core\Model;

class Miner extends Model
{
    public function getAllPackages()
    {
        return $this->db->query("SELECT * FROM mining_packages WHERE is_active = TRUE ORDER BY price ASC")->fetchAll();
    }

    public function getPackageById($id)
    {
        return $this->db->query("SELECT * FROM mining_packages WHERE id = ?", [$id])->fetch();
    }

    public function getActiveByUserId($userId)
    {
        $sql = "SELECT um.*, mp.name, mp.daily_profit
                FROM user_miners um
                JOIN mining_packages mp ON um.package_id = mp.id
                WHERE um.user_id = ? AND um.status = 'active'";
        return $this->db->query($sql, [$userId])->fetchAll();
    }

    public function createPurchase($userId, $packageId, $days)
    {
        $sql = "INSERT INTO user_miners (user_id, package_id, days_remaining) VALUES (?, ?, ?)";
        return $this->db->query($sql, [$userId, $packageId, $days]);
    }
}
