<?php
/**
 * Order Model
 * نموذج الطلب
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Cart.php';
require_once __DIR__ . '/Product.php';

class Order {
    private $table = 'orders';
    private $db;

    public function __construct() {
        $this->db = db();
    }

    /**
     * Find order by ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if ($order) {
            $order['items'] = $this->getItems($id);
        }

        return $order;
    }

    /**
     * Find order by order number
     */
    public function findByOrderNumber($orderNumber) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE order_number = ?");
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch();

        if ($order) {
            $order['items'] = $this->getItems($order['id']);
        }

        return $order;
    }

    /**
     * Get order items
     */
    public function getItems($orderId) {
        $sql = "SELECT oi.*, p.image, p.slug
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    /**
     * Get user orders
     */
    public function getByUser($userId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;

        // Count total
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE user_id = ?");
        $countStmt->execute([$userId]);
        $total = $countStmt->fetchColumn();

        // Get orders
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $perPage, $offset]);
        $orders = $stmt->fetchAll();

        foreach ($orders as &$order) {
            $order['items'] = $this->getItems($order['id']);
        }

        return [
            'orders' => $orders,
            'total' => $total
        ];
    }

    /**
     * Create order from cart
     */
    public function create($userId, $data) {
        $cartModel = new Cart();
        $productModel = new Product();

        // Validate cart
        $validation = $cartModel->validateCart($userId);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }

        // Calculate totals
        $totals = $cartModel->calculateTotals($userId, $data['coupon_code'] ?? null);

        if ($totals['total'] < MIN_ORDER_VALUE) {
            return ['success' => false, 'message' => 'الحد الأدنى للطلب ' . MIN_ORDER_VALUE . ' ' . CURRENCY_SYMBOL];
        }

        try {
            $this->db->beginTransaction();

            // Generate order number
            $orderNumber = $this->generateOrderNumber();

            // Create order
            $sql = "INSERT INTO {$this->table} 
                    (order_number, user_id, status, payment_status, payment_method,
                     subtotal, discount, tax, shipping_cost, total, coupon_code,
                     shipping_address_id, shipping_name, shipping_phone, shipping_city, shipping_address, notes)
                    VALUES (?, ?, 'pending', 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $orderNumber,
                $userId,
                $data['payment_method'] ?? 'cash',
                $totals['subtotal'],
                $totals['discount'],
                $totals['tax'],
                $totals['shipping'],
                $totals['total'],
                $data['coupon_code'] ?? null,
                $data['address_id'] ?? null,
                $data['shipping_name'] ?? null,
                $data['shipping_phone'] ?? null,
                $data['shipping_city'] ?? null,
                $data['shipping_address'] ?? null,
                $data['notes'] ?? null
            ]);

            $orderId = $this->db->lastInsertId();

            // Create order items
            foreach ($totals['items'] as $item) {
                $sql = "INSERT INTO order_items (order_id, product_id, variant_id, product_name, variant_name, price, quantity, total)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['variant_id'],
                    $item['name'],
                    $item['variant_name'],
                    $item['unit_price'],
                    $item['quantity'],
                    $item['total']
                ]);

                // Update stock
                $productModel->updateStock($item['product_id'], $item['quantity'], 'subtract');
            }

            // Update coupon usage
            if (!empty($data['coupon_code'])) {
                $this->db->prepare("UPDATE coupons SET usage_count = usage_count + 1 WHERE code = ?")
                    ->execute([$data['coupon_code']]);
            }

            // Clear cart
            $cartModel->clear($userId);

            $this->db->commit();

            return ['success' => true, 'order' => $this->findById($orderId)];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'فشل في إنشاء الطلب'];
        }
    }

    /**
     * Update order status
     */
    public function updateStatus($id, $status) {
        $allowedStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        
        if (!in_array($status, $allowedStatuses)) {
            return false;
        }

        $additionalFields = '';
        $params = [$status];

        if ($status === 'shipped') {
            $additionalFields = ', shipped_at = NOW()';
        } elseif ($status === 'delivered') {
            $additionalFields = ', delivered_at = NOW()';
        }

        $params[] = $id;

        $sql = "UPDATE {$this->table} SET status = ?{$additionalFields} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->findById($id);
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($id, $status) {
        $allowedStatuses = ['pending', 'paid', 'failed', 'refunded'];
        
        if (!in_array($status, $allowedStatuses)) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE {$this->table} SET payment_status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);

        return $this->findById($id);
    }

    /**
     * Cancel order
     */
    public function cancel($id, $userId = null) {
        $order = $this->findById($id);
        
        if (!$order) {
            return ['success' => false, 'message' => 'الطلب غير موجود'];
        }

        if ($userId && $order['user_id'] != $userId) {
            return ['success' => false, 'message' => 'غير مصرح'];
        }

        if (!in_array($order['status'], ['pending', 'confirmed'])) {
            return ['success' => false, 'message' => 'لا يمكن إلغاء الطلب في هذه المرحلة'];
        }

        try {
            $this->db->beginTransaction();

            // Restore stock
            $productModel = new Product();
            foreach ($order['items'] as $item) {
                $productModel->updateStock($item['product_id'], $item['quantity'], 'add');
            }

            // Update order status
            $stmt = $this->db->prepare("UPDATE {$this->table} SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$id]);

            $this->db->commit();

            return ['success' => true, 'order' => $this->findById($id)];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'فشل في إلغاء الطلب'];
        }
    }

    /**
     * Get all orders (admin)
     */
    public function getAll($page = 1, $perPage = 10, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "o.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['payment_status'])) {
            $where[] = "o.payment_status = ?";
            $params[] = $filters['payment_status'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        if (!empty($filters['date_from'])) {
            $where[] = "DATE(o.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "DATE(o.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = implode(' AND ', $where);

        // Count total
        $countSql = "SELECT COUNT(*) FROM {$this->table} o 
                     LEFT JOIN users u ON o.user_id = u.id 
                     WHERE {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Get orders
        $params[] = $perPage;
        $params[] = $offset;

        $sql = "SELECT o.*, u.name as customer_name, u.email as customer_email
                FROM {$this->table} o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE {$whereClause}
                ORDER BY o.created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        return [
            'orders' => $orders,
            'total' => $total
        ];
    }

    /**
     * Generate order number
     */
    private function generateOrderNumber() {
        $prefix = 'ORD';
        $date = date('Ymd');
        $random = strtoupper(bin2hex(random_bytes(3)));
        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Get order statistics
     */
    public function getStatistics($period = 'month') {
        $dateCondition = '';
        switch ($period) {
            case 'today':
                $dateCondition = "DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $dateCondition = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $dateCondition = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'year':
                $dateCondition = "created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
        }

        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(total) as total_revenue,
                    AVG(total) as average_order,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
                FROM {$this->table}
                WHERE {$dateCondition}";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetch();
    }
}
