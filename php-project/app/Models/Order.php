<?php

namespace App\Models;

use App\Database\Connection;

class Order
{
    private string $table = 'orders';
    private \PDO $db;
    
    public function __construct() { $this->db = Connection::getInstance()->getConnection(); }
    
    public function getByUser(int $userId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE user_id = ?"); $countStmt->execute([$userId]);
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?"); $stmt->execute([$userId, $perPage, $offset]);
        return ['orders' => $stmt->fetchAll(), 'total' => $countStmt->fetchColumn()];
    }
    
    public function findById(int $id): ?array { $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?"); $stmt->execute([$id]); $order = $stmt->fetch(); if ($order) { $order['items'] = $this->getItems($id); } return $order ?: null; }
    
    public function getItems(int $orderId): array { $stmt = $this->db->prepare("SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?"); $stmt->execute([$orderId]); return $stmt->fetchAll(); }
    
    public function create(int $userId, array $data): array
    {
        $cart = (new Cart())->getByUser($userId);
        if (empty($cart['items'])) return ['success' => false, 'message' => 'السلة فارغة'];
        $totals = (new Cart())->calculateTotals($userId, $data['coupon_code'] ?? null);
        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $this->db->prepare("INSERT INTO {$this->table} (user_id, order_number, subtotal, discount, tax, shipping, total, status, shipping_address, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)")->execute([$userId, $orderNumber, $totals['subtotal'], $totals['discount'], $totals['tax'], $totals['shipping'], $totals['total'], json_encode($data['shipping_address'] ?? []), $data['payment_method'] ?? 'cod']);
        $orderId = (int) $this->db->lastInsertId();
        foreach ($cart['items'] as $item) { $this->db->prepare("INSERT INTO order_items (order_id, product_id, variant_id, quantity, price, total) VALUES (?, ?, ?, ?, ?, ?)")->execute([$orderId, $item['product_id'], $item['variant_id'] ?? null, $item['quantity'], $item['unit_price'], $item['total']]); }
        (new Cart())->clear($userId);
        return ['success' => true, 'order' => $this->findById($orderId)];
    }
    
    public function cancel(int $id, int $userId): array
    {
        $order = $this->findById($id);
        if (!$order || $order['user_id'] != $userId) return ['success' => false, 'message' => 'الطلب غير موجود'];
        if (!in_array($order['status'], ['pending', 'processing'])) return ['success' => false, 'message' => 'لا يمكن إلغاء هذا الطلب'];
        $this->db->prepare("UPDATE {$this->table} SET status = 'cancelled' WHERE id = ?")->execute([$id]);
        return ['success' => true, 'order' => $this->findById($id)];
    }
}
