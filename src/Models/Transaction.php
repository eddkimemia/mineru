<?php

namespace App\Models;

use App\Core\Model;

class Transaction extends Model
{
    public function getRecentByUserId($userId, $limit = 5)
    {
        return $this->db->query("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ?", [$userId, (int)$limit])->fetchAll();
    }

    public function getAll($limit = 50, $offset = 0)
    {
        return $this->db->query("SELECT t.*, u.username FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT $limit OFFSET $offset")->fetchAll();
    }

    public function create($data)
    {
        $sql = "INSERT INTO transactions (user_id, type, amount, method, status, transaction_hash) VALUES (?, ?, ?, ?, ?, ?)";
        return $this->db->query($sql, [
            $data['user_id'],
            $data['type'],
            $data['amount'],
            $data['method'],
            $data['status'],
            $data['transaction_hash']
        ]);
    }

    public function updateStatus($id, $status)
    {
        return $this->db->query("UPDATE transactions SET status = ? WHERE id = ?", [$status, $id]);
    }

    public function findById($id)
    {
        return $this->db->query("SELECT * FROM transactions WHERE id = ?", [$id])->fetch();
    }

    public function getTotalByType($type, $status = 'completed')
    {
        return $this->db->query("SELECT SUM(amount) FROM transactions WHERE type = ? AND status = ?", [$type, $status])->fetchColumn() ?: 0;
    }
}
