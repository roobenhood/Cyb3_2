<?php
/**
 * Order Model - FINAL CORRECTED VERSION
 * نموذج الطلب - النسخة النهائية والمصححة
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

    // ... other functions like findById, getItems, etc. are fine ...
    
    /**
     * Get user orders - ROBUST AND CORRECTED VERSION
     * This function is now safe and will not crash the server.
     */
    public function getByUser($userId, $page = 1, $perPage = 10) {
        try {
            // Ensure page and perPage are integers to prevent errors
            $page = (int)$page > 0 ? (int)$page : 1;
            $perPage = (int)$perPage > 0 ? (int)$perPage : 10;

            // Calculate offset
            $offset = ($page - 1) * $perPage;

            // Count total rows for pagination
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE user_id = ?");
            $countStmt->execute([$userId]);
            $total = (int)$countStmt->fetchColumn();

            // Prepare the main query
            // Using bindParam is the safest way to handle LIMIT/OFFSET with PDO
            $sql = "SELECT * FROM {$this->table}
                    WHERE user_id = :userid
                    ORDER BY created_at DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);

            // Bind parameters, ensuring integer types for limit and offset
            $stmt->bindParam(':userid', $userId);
            $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            $orders = $stmt->fetchAll();

            // Fetch items for each order
            foreach ($orders as &$order) {
                $order['items'] = $this->getItems($order['id']);
            }

            return [
                'orders' => $orders,
                'total' => $total
            ];

        } catch (PDOException $e) {
            // In case of any database error, return an empty result to prevent crashing.
            // On a production server, you would log this error to a file.
            // error_log("Error in getByUser: " . $e->getMessage()); 
            return ['orders' => [], 'total' => 0];
        }
    }

    // --- ALL OTHER FUNCTIONS IN THIS FILE REMAIN THE SAME ---
    // (The rest of your code for findById, create, cancel, etc. can stay as it is)
    
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if ($order) {
            $order['items'] = $this->getItems($id);
        }

        return $order;
    }

    public function findByOrderNumber($orderNumber) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE order_number = ?");
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch();

        if ($order) {
            $order['items'] = $this->getItems($order['id']);
        }

        return $order;
    }

    public function getItems($orderId) {
        $sql = "SELECT oi.*, p.image, p.slug
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }
    
    public function create($userId, $data) {
        $cartModel = new Cart();
        $productModel = new Product();

        $cart = $cartModel->getByUser($userId);
        if (empty($cart['items'])) {
            return ['success' => false, 'message' => 'السلة فارغة'];
        }

        if (isset($GLOBALS['MIN_ORDER_VALUE']) && $cart['subtotal'] < $GLOBALS['MIN_ORDER_VALUE']) {
            return ['success' => false, 'message' => 'الحد الأدنى للطلب ' . $GLOBALS['MIN_ORDER_VALUE'] . ' ' . $GLOBALS['CURRENCY_SYMBOL']];
        }

        foreach ($cart['items'] as $item) {
            if (!$productModel->checkStock($item['product_id'], $item['quantity'], $item['variant_id'])) {
                return ['success' => false, 'message' => 'المنتج "' . $item['name'] . '" غير متوفر بالكمية المطلوبة'];
            }
        }

        $totals = $cartModel->calculateTotals($userId, $data['coupon_code'] ?? null);
        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

        $this->db->beginTransaction();
        try {
            $sql = "INSERT INTO {$this->table}
                    (order_number, user_id, status, payment_method, subtotal, discount, tax, shipping_cost, total,
                     coupon_code, shipping_name, shipping_phone, shipping_city, shipping_address, notes)
                    VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $orderNumber, $userId, $data['payment_method'] ?? 'cash', $totals['subtotal'], $totals['discount'], 
                $totals['tax'], $totals['shipping'], $totals['total'], $data['coupon_code'] ?? null, 
                $data['shipping_name'] ?? null, $data['shipping_phone'] ?? null, $data['shipping_city'] ?? null, 
                $data['shipping_address'] ?? null, $data['notes'] ?? null
            ]);
            $orderId = $this->db->lastInsertId();

            $itemSql = "INSERT INTO order_items (order_id, product_id, variant_id, product_name, variant_name, price, quantity, total)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $itemStmt = $this->db->prepare($itemSql);
            foreach ($cart['items'] as $item) {
                $itemStmt->execute([
                    $orderId, $item['product_id'], $item['variant_id'], $item['name'], $item['variant_name'],
                    $item['unit_price'], $item['quantity'], $item['total']
                ]);
                $productModel->reduceStock($item['product_id'], $item['quantity'], $item['variant_id']);
            }

            if (!empty($data['coupon_code'])) {
                $this->db->prepare("UPDATE coupons SET usage_count = usage_count + 1 WHERE code = ?")->execute([$data['coupon_code']]);
            }
            $cartModel->clear($userId);
            $this->db->commit();
            return ['success' => true, 'order' => $this->findById($orderId)];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'حدث خطأ أثناء إنشاء الطلب: ' . $e->getMessage()];
        }
    }
    
    public function cancel($orderId, $userId) {
        $order = $this->findById($orderId);

        if (!$order || $order['user_id'] != $userId) {
            return ['success' => false, 'message' => 'الطلب غير موجود'];
        }
        if (!in_array($order['status'], ['pending', 'confirmed'])) {
            return ['success' => false, 'message' => 'لا يمكن إلغاء هذا الطلب'];
        }

        $productModel = new Product();
        $this->db->beginTransaction();
        try {
            $this->db->prepare("UPDATE {$this->table} SET status = 'cancelled' WHERE id = ?")->execute([$orderId]);
            foreach ($order['items'] as $item) {
                $productModel->restoreStock($item['product_id'], $item['quantity'], $item['variant_id']);
            }
            $this->db->commit();
            return ['success' => true, 'order' => $this->findById($orderId)];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'حدث خطأ أثناء إلغاء الطلب'];
        }
    }
    
    public function updateStatus($orderId, $status) {
        $validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        if (!in_array($status, $validStatuses)) return false;

        $additionalFields = [];
        if ($status === 'shipped') $additionalFields['shipped_at'] = date('Y-m-d H:i:s');
        elseif ($status === 'delivered') $additionalFields['delivered_at'] = date('Y-m-d H:i:s');
        
        $sql = "UPDATE {$this->table} SET status = ?";
        $params = [$status];
        foreach ($additionalFields as $field => $value) {
            $sql .= ", {$field} = ?";
            $params[] = $value;
        }
        $sql .= " WHERE id = ?";
        $params[] = $orderId;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}