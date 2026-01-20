<?php
/**
 * Review Model
 * نموذج التقييم
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Product.php';

class Review {
    private $table = 'reviews';
    private $db;

    public function __construct() {
        $this->db = db();
    }

    /**
     * Get product reviews
     */
    public function getByProduct($productId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;

        // Count total
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE product_id = ? AND is_approved = 1");
        $countStmt->execute([$productId]);
        $total = $countStmt->fetchColumn();

        // Get reviews
        $sql = "SELECT r.*, u.name as user_name, u.avatar as user_avatar
                FROM {$this->table} r
                JOIN users u ON r.user_id = u.id
                WHERE r.product_id = ? AND r.is_approved = 1
                ORDER BY r.created_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId, $perPage, $offset]);
        $reviews = $stmt->fetchAll();

        foreach ($reviews as &$review) {
            $review['images'] = $review['images'] ? json_decode($review['images'], true) : [];
        }

        return [
            'reviews' => $reviews,
            'total' => $total
        ];
    }

    /**
     * Get review statistics for product
     */
    public function getStats($productId) {
        $sql = "SELECT
                    COUNT(*) as total,
                    AVG(rating) as average,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                FROM {$this->table}
                WHERE product_id = ? AND is_approved = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        $stats = $stmt->fetch();

        $total = $stats['total'] ?: 1;
        return [
            'total' => (int)$stats['total'],
            'average' => round($stats['average'] ?? 0, 1),
            'distribution' => [
                5 => round(($stats['five_star'] / $total) * 100),
                4 => round(($stats['four_star'] / $total) * 100),
                3 => round(($stats['three_star'] / $total) * 100),
                2 => round(($stats['two_star'] / $total) * 100),
                1 => round(($stats['one_star'] / $total) * 100)
            ]
        ];
    }

    /**
     * Create review
     */
    public function create($userId, $productId, $data) {
        // Check if already reviewed
        $checkStmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE user_id = ? AND product_id = ?");
        $checkStmt->execute([$userId, $productId]);
        if ($checkStmt->fetch()) {
            return ['success' => false, 'message' => 'لقد قمت بتقييم هذا المنتج مسبقاً'];
        }

        // Check if user purchased the product
        $purchaseCheck = $this->db->prepare("
            SELECT o.id FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'delivered'
        ");
        $purchaseCheck->execute([$userId, $productId]);
        $isVerifiedPurchase = $purchaseCheck->fetch() ? 1 : 0;

        $sql = "INSERT INTO {$this->table}
                (user_id, product_id, rating, title, comment, images, is_verified_purchase, is_approved)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $productId,
            $data['rating'],
            $data['title'] ?? null,
            $data['comment'] ?? null,
            !empty($data['images']) ? json_encode($data['images']) : null,
            $isVerifiedPurchase,
            1 // Auto-approve for now
        ]);

        // Update product rating
        $productModel = new Product();
        $productModel->updateRating($productId);

        return ['success' => true, 'message' => 'تم إضافة التقييم بنجاح'];
    }

    /**
     * Mark review as helpful
     */
    public function markHelpful($reviewId, $userId) {
        // Simple increment for now
        $stmt = $this->db->prepare("UPDATE {$this->table} SET helpful_count = helpful_count + 1 WHERE id = ?");
        $stmt->execute([$reviewId]);
        return true;
    }

    /**
     * Delete review (user can only delete their own)
     */
    public function delete($reviewId, $userId) {
        $stmt = $this->db->prepare("SELECT product_id FROM {$this->table} WHERE id = ? AND user_id = ?");
        $stmt->execute([$reviewId, $userId]);
        $review = $stmt->fetch();

        if (!$review) {
            return false;
        }

        $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?")->execute([$reviewId]);

        // Update product rating
        $productModel = new Product();
        $productModel->updateRating($review['product_id']);

        return true;
    }
}
