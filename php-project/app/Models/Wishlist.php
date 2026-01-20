<?php

namespace App\Models;

use App\Database\Connection;

class Wishlist
{
    private string $table = 'wishlists';
    private \PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    /**
     * الحصول على قائمة الأمنيات للمستخدم
     */
    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT w.*, p.name, p.price, p.sale_price, p.image, p.stock_quantity,
                   c.name as category_name
            FROM {$this->table} w
            JOIN products p ON w.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE w.user_id = ? AND p.is_active = 1
            ORDER BY w.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * إضافة منتج للأمنيات
     */
    public function add(int $userId, int $productId): array
    {
        // التحقق من عدم وجوده مسبقاً
        if ($this->exists($userId, $productId)) {
            return ['success' => false, 'message' => 'المنتج موجود في المفضلة'];
        }

        try {
            $stmt = $this->db->prepare("INSERT INTO {$this->table} (user_id, product_id, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$userId, $productId]);

            return ['success' => true, 'message' => 'تمت الإضافة للمفضلة'];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'فشل في الإضافة'];
        }
    }

    /**
     * إزالة منتج من الأمنيات
     */
    public function remove(int $userId, int $productId): array
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);

        return [
            'success' => $stmt->rowCount() > 0,
            'message' => 'تمت الإزالة من المفضلة'
        ];
    }

    /**
     * التحقق من وجود منتج في الأمنيات
     */
    public function exists(int $userId, int $productId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM {$this->table} WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        return (bool)$stmt->fetch();
    }

    /**
     * مسح قائمة الأمنيات
     */
    public function clear(int $userId): array
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([$userId]);

        return ['success' => true, 'message' => 'تم مسح المفضلة'];
    }

    /**
     * عدد المنتجات في المفضلة
     */
    public function count(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * نقل للسلة
     */
    public function moveToCart(int $userId, int $productId): array
    {
        $cart = new Cart();
        $result = $cart->addItem($userId, $productId, 1);

        if ($result) {
            $this->remove($userId, $productId);
            return ['success' => true, 'message' => 'تم النقل للسلة'];
        }

        return ['success' => false, 'message' => 'فشل في النقل'];
    }
}
