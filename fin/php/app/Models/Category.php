<?php

namespace App\Models;

use App\Database\Connection;

class Category
{
    private string $table = 'categories';
    private \PDO $db;
    
    public function __construct() { $this->db = Connection::getInstance()->getConnection(); }
    
    public function findById(int $id): ?array { $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?"); $stmt->execute([$id]); return $stmt->fetch() ?: null; }
    
    public function getAll(): array { return $this->db->query("SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll(); }
    
    public function getWithProductCount(): array { return $this->db->query("SELECT c.*, COUNT(p.id) as product_count FROM {$this->table} c LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1 WHERE c.is_active = 1 GROUP BY c.id ORDER BY c.sort_order ASC")->fetchAll(); }
}
