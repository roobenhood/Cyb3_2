<?php

namespace App\Models;

use App\Database\Connection;

/**
 * Product Model - نموذج المنتج
 */
class Product
{
    private string $table = 'products';
    private \PDO $db;
    
    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }
    
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT p.*, c.name as category_name FROM {$this->table} p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    
    public function getAll(int $page = 1, int $perPage = 12, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $where = ["p.is_active = 1"];
        $params = [];
        
        if (!empty($filters['category_id'])) { $where[] = "p.category_id = ?"; $params[] = $filters['category_id']; }
        if (!empty($filters['search'])) { $where[] = "(p.name LIKE ? OR p.description LIKE ?)"; $params[] = "%{$filters['search']}%"; $params[] = "%{$filters['search']}%"; }
        if (!empty($filters['min_price'])) { $where[] = "COALESCE(p.discount_price, p.price) >= ?"; $params[] = $filters['min_price']; }
        if (!empty($filters['max_price'])) { $where[] = "COALESCE(p.discount_price, p.price) <= ?"; $params[] = $filters['max_price']; }
        
        $whereClause = implode(' AND ', $where);
        $orderBy = match($filters['sort'] ?? null) { 'price_asc' => 'COALESCE(p.discount_price, p.price) ASC', 'price_desc' => 'COALESCE(p.discount_price, p.price) DESC', 'newest' => 'p.created_at DESC', default => 'p.created_at DESC' };
        
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} p WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        $sql = "SELECT p.*, c.name as category_name FROM {$this->table} p LEFT JOIN categories c ON p.category_id = c.id WHERE {$whereClause} ORDER BY {$orderBy} LIMIT ? OFFSET ?";
        $params[] = $perPage; $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return ['products' => $stmt->fetchAll(), 'total' => $total];
    }
    
    public function getFeatured(int $limit = 8): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE is_active = 1 AND is_featured = 1 ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function incrementViews(int $id): void
    {
        $this->db->prepare("UPDATE {$this->table} SET views = views + 1 WHERE id = ?")->execute([$id]);
    }
    
    public function getRelated(int $id, int $categoryId, int $limit = 4): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id != ? AND category_id = ? AND is_active = 1 LIMIT ?");
        $stmt->execute([$id, $categoryId, $limit]);
        return $stmt->fetchAll();
    }
    
    public function updateRating(int $id): void
    {
        $sql = "UPDATE {$this->table} SET rating = (SELECT AVG(rating) FROM reviews WHERE product_id = ? AND is_approved = 1), reviews_count = (SELECT COUNT(*) FROM reviews WHERE product_id = ? AND is_approved = 1) WHERE id = ?";
        $this->db->prepare($sql)->execute([$id, $id, $id]);
    }
}
