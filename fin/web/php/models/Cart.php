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
            return $this->updateQuantity($userId, $existing['id'], $newQuantity);
        }

        // Add new item
        $sql = "INSERT INTO {$this->table} (user_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $productId, $variantId, $quantity]);

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

        return ['items' => [], 'subtotal' => 0, 'item_count' => 0];
    }

    /**
     * Get cart count
     */
    public function getCount($userId) {
        $stmt = $this->db->prepare("SELECT SUM(quantity) FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Check product availability in cart
     */
    public function validateCart($userId) {
        $cart = $this->getByUser($userId);
        $errors = [];

        foreach ($cart['items'] as $item) {
            $stock = $item['variant_stock'] ?? $item['stock'];
            if ($item['quantity'] > $stock) {
                $errors[] = [
                    'product_id' => $item['product_id'],
                    'name' => $item['name'],
                    'requested' => $item['quantity'],
                    'available' => $stock,
                    'message' => "الكمية المتوفرة من {$item['name']} هي {$stock} فقط"
                ];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'cart' => $cart
        ];
    }

    /**
     * Calculate cart totals
     */
    public function calculateTotals($userId, $couponCode = null) {
        $cart = $this->getByUser($userId);
        
        $subtotal = $cart['subtotal'];
        $discount = 0;
        $coupon = null;

        // Apply coupon if provided
        if ($couponCode) {
            $couponResult = $this->applyCoupon($couponCode, $subtotal);
            if ($couponResult['valid']) {
                $discount = $couponResult['discount'];
                $coupon = $couponResult['coupon'];
            }
        }

        $afterDiscount = $subtotal - $discount;
        $tax = $afterDiscount * TAX_RATE;
        $shipping = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $total = $afterDiscount + $tax + $shipping;

        return [
            'items' => $cart['items'],
            'item_count' => $cart['item_count'],
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'coupon' => $coupon,
            'tax' => round($tax, 2),
            'tax_rate' => TAX_RATE * 100,
            'shipping' => round($shipping, 2),
            'free_shipping_threshold' => FREE_SHIPPING_THRESHOLD,
            'total' => round($total, 2)
        ];
    }

    /**
     * Apply coupon
     */
    private function applyCoupon($code, $subtotal) {
        $stmt = $this->db->prepare(
            "SELECT * FROM coupons 
             WHERE code = ? AND is_active = 1 
             AND (start_date IS NULL OR start_date <= NOW())
             AND (end_date IS NULL OR end_date >= NOW())
             AND (usage_limit IS NULL OR usage_count < usage_limit)"
        );
        $stmt->execute([$code]);
        $coupon = $stmt->fetch();

        if (!$coupon) {
            return ['valid' => false, 'message' => 'كوبون غير صالح'];
        }

        if ($subtotal < $coupon['min_order_value']) {
            return ['valid' => false, 'message' => "الحد الأدنى للطلب {$coupon['min_order_value']} " . CURRENCY_SYMBOL];
        }

        $discount = 0;
        if ($coupon['type'] === 'percentage') {
            $discount = $subtotal * ($coupon['value'] / 100);
            if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
                $discount = $coupon['max_discount'];
            }
        } else {
            $discount = $coupon['value'];
        }

        return [
            'valid' => true,
            'discount' => $discount,
            'coupon' => [
                'code' => $coupon['code'],
                'type' => $coupon['type'],
                'value' => $coupon['value']
            ]
        ];
    }
}
