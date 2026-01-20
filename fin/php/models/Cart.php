<?php
/**
 * Cart Model
 * نموذج السلة
 */

require_once __DIR__ . '/../config/database.php';

class Cart {
    private $table = 'cart';
    private $db;

    public function __construct() {
        $this->db = db();
    }

    /**
     * Get user cart
     */
    public function getByUser($userId) {
        $sql = "SELECT c.id, c.quantity, c.product_id, c.variant_id,
                       p.name, p.slug, p.price, p.discount_price, p.image, p.stock,
                       pv.name as variant_name, pv.price as variant_price, pv.stock as variant_stock
                FROM {$this->table} c
                JOIN products p ON c.product_id = p.id
                LEFT JOIN product_variants pv ON c.variant_id = pv.id
                WHERE c.user_id = ? AND p.is_active = 1
                ORDER BY c.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll();

        $subtotal = 0;
        foreach ($items as &$item) {
            $price = $item['variant_price'] ?? ($item['discount_price'] ?? $item['price']);
            $item['unit_price'] = $price;
            $item['total'] = $price * $item['quantity'];
            $subtotal += $item['total'];
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'item_count' => count($items)
        ];
    }

    /**
     * Add item to cart
     */
    public function addItem($userId, $productId, $quantity = 1, $variantId = null) {
        // Check if item already exists
        $sql = "SELECT id, quantity FROM {$this->table}
                WHERE user_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $productId, $variantId, $variantId]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update quantity
            $newQuantity = $existing['quantity'] + $quantity;
            $updateStmt = $this->db->prepare("UPDATE {$this->table} SET quantity = ? WHERE id = ?");
            $updateStmt->execute([$newQuantity, $existing['id']]);
        } else {
            // Insert new item
            $sql = "INSERT INTO {$this->table} (user_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $productId, $variantId, $quantity]);
        }

        return $this->getByUser($userId);
    }

    /**
     * Update item quantity
     */
    public function updateQuantity($userId, $itemId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeItem($userId, $itemId);
        }

        $stmt = $this->db->prepare("UPDATE {$this->table} SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$quantity, $itemId, $userId]);

        return $this->getByUser($userId);
    }

    /**
     * Remove item from cart
     */
    public function removeItem($userId, $itemId) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ? AND user_id = ?");
        $stmt->execute([$itemId, $userId]);

        return $this->getByUser($userId);
    }

    /**
     * Clear cart
     */
    public function clear($userId) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([$userId]);

        return $this->getByUser($userId);
    }

    /**
     * Calculate totals
     */
    public function calculateTotals($userId, $couponCode = null) {
        $cart = $this->getByUser($userId);
        $subtotal = $cart['subtotal'];
        $discount = 0;
        $coupon = null;

        // Apply coupon
        if ($couponCode) {
            $coupon = $this->validateCoupon($couponCode, $subtotal);
            if ($coupon) {
                $discount = $this->calculateDiscount($coupon, $subtotal);
            }
        }

        // Calculate tax
        $taxableAmount = $subtotal - $discount;
        $tax = $taxableAmount * TAX_RATE;

        // Shipping
        $shipping = ($subtotal - $discount) >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;

        $total = $subtotal - $discount + $tax + $shipping;

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => round($tax, 2),
            'shipping' => $shipping,
            'total' => round($total, 2),
            'coupon' => $coupon ? [
                'code' => $coupon['code'],
                'description' => $coupon['description']
            ] : null,
            'item_count' => $cart['item_count']
        ];
    }

    /**
     * Validate coupon
     */
    private function validateCoupon($code, $orderValue) {
        $stmt = $this->db->prepare("
            SELECT * FROM coupons
            WHERE code = ?
            AND is_active = 1
            AND (start_date IS NULL OR start_date <= NOW())
            AND (end_date IS NULL OR end_date >= NOW())
            AND (usage_limit IS NULL OR usage_count < usage_limit)
        ");
        $stmt->execute([$code]);
        $coupon = $stmt->fetch();

        if (!$coupon) {
            return null;
        }

        if ($orderValue < $coupon['min_order_value']) {
            return null;
        }

        return $coupon;
    }

    /**
     * Calculate discount
     */
    private function calculateDiscount($coupon, $subtotal) {
        if ($coupon['type'] === 'percentage') {
            $discount = $subtotal * ($coupon['value'] / 100);
            if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
                $discount = $coupon['max_discount'];
            }
        } else {
            $discount = $coupon['value'];
        }

        return min($discount, $subtotal);
    }
}
