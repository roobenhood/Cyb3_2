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

        $total = $stats['total'] ?: 1; // Avoid division by zero

        return [
            'total' => (int) $stats['total'],
            'average' => round($stats['average'] ?? 0, 1),
            'distribution' => [
                5 => ['count' => (int) $stats['five_star'], 'percentage' => round(($stats['five_star'] / $total) * 100)],
                4 => ['count' => (int) $stats['four_star'], 'percentage' => round(($stats['four_star'] / $total) * 100)],
                3 => ['count' => (int) $stats['three_star'], 'percentage' => round(($stats['three_star'] / $total) * 100)],
                2 => ['count' => (int) $stats['two_star'], 'percentage' => round(($stats['two_star'] / $total) * 100)],
                1 => ['count' => (int) $stats['one_star'], 'percentage' => round(($stats['one_star'] / $total) * 100)],
            ]
        ];
    }

    /**
     * Create review
     */
    public function create($userId, $productId, $data) {
        // Check if user already reviewed this product
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'لقد قمت بتقييم هذا المنتج مسبقاً'];
        }

        // Check if user purchased this product
        $purchaseCheck = $this->db->prepare(
            "SELECT 1 FROM orders o 
             JOIN order_items oi ON o.id = oi.order_id 
             WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'delivered'"
        );
        $purchaseCheck->execute([$userId, $productId]);
        $isVerified = $purchaseCheck->fetch() ? 1 : 0;

        $images = isset($data['images']) ? json_encode($data['images']) : null;

        $sql = "INSERT INTO {$this->table} (user_id, product_id, rating, title, comment, images, is_verified_purchase, is_approved)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $productId,
            $data['rating'],
            $data['title'] ?? null,
            $data['comment'] ?? null,
            $images,
            $isVerified,
            0 // Not approved by default
        ]);

        // Update product rating
        $productModel = new Product();
        $productModel->updateRating($productId);

        return ['success' => true, 'message' => 'تم إرسال التقييم للمراجعة'];
    }

    /**
     * Update review
     */
    public function update($id, $userId, $data) {
        $fields = [];
        $values = [];

        if (isset($data['rating'])) {
            $fields[] = "rating = ?";
            $values[] = $data['rating'];
        }

        if (isset($data['title'])) {
            $fields[] = "title = ?";
            $values[] = $data['title'];
        }

        if (isset($data['comment'])) {
            $fields[] = "comment = ?";
            $values[] = $data['comment'];
        }

        if (isset($data['images'])) {
            $fields[] = "images = ?";
            $values[] = json_encode($data['images']);
        }

        if (empty($fields)) {
            return ['success' => false, 'message' => 'لا توجد بيانات للتحديث'];
        }

        // Reset approval status
        $fields[] = "is_approved = 0";

        $values[] = $id;
        $values[] = $userId;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'لم يتم العثور على التقييم'];
        }

        return ['success' => true, 'message' => 'تم تحديث التقييم'];
    }

    /**
     * Delete review
     */
    public function delete($id, $userId = null) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $params = [$id];

        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        // Get product ID first
        $stmt = $this->db->prepare("SELECT product_id FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $review = $stmt->fetch();

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);

        // Update product rating
        if ($review) {
            $productModel = new Product();
            $productModel->updateRating($review['product_id']);
        }

        return $result;
    }

    /**
     * Approve review (admin)
     */
    public function approve($id) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET is_approved = 1 WHERE id = ?");
        $stmt->execute([$id]);

        // Update product rating
        $review = $this->db->prepare("SELECT product_id FROM {$this->table} WHERE id = ?")->execute([$id])->fetch();
        if ($review) {
            $productModel = new Product();
            $productModel->updateRating($review['product_id']);
        }

        return true;
    }

    /**
     * Get pending reviews (admin)
     */
    public function getPending($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE is_approved = 0");
        $total = $countStmt->fetchColumn();

        $sql = "SELECT r.*, u.name as user_name, p.name as product_name
                FROM {$this->table} r
                JOIN users u ON r.user_id = u.id
                JOIN products p ON r.product_id = p.id
                WHERE r.is_approved = 0
                ORDER BY r.created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$perPage, $offset]);
        $reviews = $stmt->fetchAll();

        return [
            'reviews' => $reviews,
            'total' => $total
        ];
    }

    /**
     * Mark review as helpful
     */
    public function markHelpful($id) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET helpful_count = helpful_count + 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
