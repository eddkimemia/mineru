<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class User extends Model
{
    public function findById($id)
    {
        return $this->db->query("SELECT * FROM users WHERE id = ?", [$id])->fetch();
    }

    public function getAll($limit = 50, $offset = 0)
    {
        return $this->db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT $limit OFFSET $offset")->fetchAll();
    }

    public function findByEmail($email)
    {
        return $this->db->query("SELECT * FROM users WHERE email = ?", [$email])->fetch();
    }

    public function findByUsername($username)
    {
        return $this->db->query("SELECT * FROM users WHERE username = ?", [$username])->fetch();
    }

    public function findByReferralCode($code)
    {
        return $this->db->query("SELECT * FROM users WHERE referral_code = ?", [$code])->fetch();
    }

    public function create($data)
    {
        $this->db->beginTransaction();
        try {
            $sql = "INSERT INTO users (username, full_name, email, password_hash, referral_code, referred_by, account_status, verification_code, verification_expires_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $this->db->query($sql, [
                $data['username'],
                $data['full_name'],
                $data['email'],
                $data['password_hash'],
                $data['referral_code'],
                $data['referred_by'],
                $data['account_status'],
                $data['verification_code'],
                $data['verification_expires_at']
            ]);

            $userId = $this->db->lastInsertId();

            // Record referral if applicable
            if (!empty($data['referred_by'])) {
                $this->db->query("INSERT INTO referrals (referrer_id, referred_user_id) VALUES (?, ?)", [$data['referred_by'], $userId]);
            }

            // Initialize balance
            $this->db->query("INSERT INTO user_balances (user_id) VALUES (?)", [$userId]);

            // Initialize security settings
            $this->db->query("INSERT INTO security_settings (user_id) VALUES (?)", [$userId]);

            $this->db->commit();
            return $userId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function verify($email, $code)
    {
        $user = $this->db->query("SELECT * FROM users WHERE email = ? AND verification_code = ? AND verification_expires_at > NOW()", [$email, $code])->fetch();
        if ($user) {
            $this->db->query("UPDATE users SET account_status = 'active', verification_code = NULL, verification_expires_at = NULL WHERE id = ?", [$user['id']]);
            return true;
        }
        return false;
    }

    public function update($id, $data)
    {
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $params[] = $value;
        }
        $params[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        return $this->db->query($sql, $params);
    }

    public function updateLoginTimestamp($userId)
    {
        $this->db->query("UPDATE users SET updated_at = NOW() WHERE id = ?", [$userId]);
    }

    public function getCount()
    {
        return $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }
}
