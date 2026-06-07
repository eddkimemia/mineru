<?php

namespace App\Models;

use App\Core\Model;

class Referral extends Model
{
    public function getByReferrerId($referrerId)
    {
        $sql = "SELECT r.*, u.username, u.full_name, u.created_at as joined_at
                FROM referrals r
                JOIN users u ON r.referred_user_id = u.id
                WHERE r.referrer_id = ?";
        return $this->db->query($sql, [$referrerId])->fetchAll();
    }

    public function getCountByReferrerId($referrerId)
    {
        return $this->db->query("SELECT COUNT(*) FROM referrals WHERE referrer_id = ?", [$referrerId])->fetchColumn();
    }
}
