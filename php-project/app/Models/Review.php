<?php

namespace App\Models;

use App\Database\Connection;

class Review
{
    private string $table = 'reviews';
    private \PDO $db;
    
    public function __construct() { $this->db = Connection::getInstance()->getConnection(); }
    
    public function getByProduct(int $productId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE product_id = ? AND is_approved = 1"); $countStmt->execute([$productId]);
        $stmt = $this->db->prepare("SELECT r.*, u.name as user_name, u.avatar as user_avatar FROM {$this->table} r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? AND r.is_approved = 1 ORDER BY r.created_at DESC LIMIT ? OFFSET ?"); $stmt->execute([$productId, $perPage, $offset]);
        $reviews = $stmt->fetchAll(); foreach ($reviews as &$r) { $r['images'] = $r['images'] ? json_decode($r['images'], true) : []; }
        return ['reviews' => $reviews, 'total' => $countStmt->fetchColumn()];
    }
    
    public function getStats(int $productId): array
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total, AVG(rating) as average, SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star, SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star, SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star, SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star, SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star FROM {$this->table} WHERE product_id = ? AND is_approved = 1");
        $stmt->execute([$productId]); $stats = $stmt->fetch(); $total = $stats['total'] ?: 1;
        return ['total' => (int)$stats['total'], 'average' => round($stats['average'] ?? 0, 1), 'distribution' => [5 => round(($stats['five_star'] / $total) * 100), 4 => round(($stats['four_star'] / $total) * 100), 3 => round(($stats['three_star'] / $total) * 100), 2 => round(($stats['two_star'] / $total) * 100), 1 => round(($stats['one_star'] / $total) * 100)]];
    }
    
    public function create(int $userId, int $productId, array $data): array
    {
        $check = $this->db->prepare("SELECT id FROM {$this->table} WHERE user_id = ? AND product_id = ?"); $check->execute([$userId, $productId]);
        if ($check->fetch()) return ['success' => false, 'message' => 'لقد قمت بتقييم هذا المنتج مسبقاً'];
        $this->db->prepare("INSERT INTO {$this->table} (user_id, product_id, rating, title, comment, is_approved) VALUES (?, ?, ?, ?, ?, 1)")->execute([$userId, $productId, $data['rating'], $data['title'] ?? null, $data['comment'] ?? null]);
        (new Product())->updateRating($productId);
        return ['success' => true, 'message' => 'تم إضافة التقييم بنجاح'];
    }
}
