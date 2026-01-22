<?php

namespace App\Models;

use App\Database\Connection;
use App\Core\Config;

class Cart
{
    private string $table = 'cart';
    private \PDO $db;
    
    public function __construct() { $this->db = Connection::getInstance()->getConnection(); }
    
    public function getByUser(int $userId): array
    {
        $sql = "SELECT c.id, c.quantity, c.product_id, c.variant_id, p.name, p.slug, p.price, p.discount_price, p.image, p.stock FROM {$this->table} c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? AND p.is_active = 1";
        $stmt = $this->db->prepare($sql); $stmt->execute([$userId]); $items = $stmt->fetchAll();
        $subtotal = 0;
        foreach ($items as &$item) { $price = $item['discount_price'] ?? $item['price']; $item['unit_price'] = $price; $item['total'] = $price * $item['quantity']; $subtotal += $item['total']; }
        return ['items' => $items, 'subtotal' => $subtotal, 'item_count' => count($items)];
    }
    
    public function addItem(int $userId, int $productId, int $quantity = 1, ?int $variantId = null): array
    {
        $stmt = $this->db->prepare("SELECT id, quantity FROM {$this->table} WHERE user_id = ? AND product_id = ?"); $stmt->execute([$userId, $productId]); $existing = $stmt->fetch();
        if ($existing) { $this->db->prepare("UPDATE {$this->table} SET quantity = ? WHERE id = ?")->execute([$existing['quantity'] + $quantity, $existing['id']]); }
        else { $this->db->prepare("INSERT INTO {$this->table} (user_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)")->execute([$userId, $productId, $variantId, $quantity]); }
        return $this->getByUser($userId);
    }
    
    public function updateQuantity(int $userId, int $itemId, int $quantity): array { if ($quantity <= 0) return $this->removeItem($userId, $itemId); $this->db->prepare("UPDATE {$this->table} SET quantity = ? WHERE id = ? AND user_id = ?")->execute([$quantity, $itemId, $userId]); return $this->getByUser($userId); }
    public function removeItem(int $userId, int $itemId): array { $this->db->prepare("DELETE FROM {$this->table} WHERE id = ? AND user_id = ?")->execute([$itemId, $userId]); return $this->getByUser($userId); }
    public function clear(int $userId): array { $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = ?")->execute([$userId]); return $this->getByUser($userId); }
    
    public function calculateTotals(int $userId, ?string $couponCode = null): array
    {
        $cart = $this->getByUser($userId); $subtotal = $cart['subtotal']; $discount = 0;
        $taxRate = Config::get('app.store.tax_rate', 0.15); $tax = ($subtotal - $discount) * $taxRate;
        $shipping = ($subtotal - $discount) >= Config::get('app.store.free_shipping_threshold', 500) ? 0 : Config::get('app.store.shipping_cost', 25);
        return ['subtotal' => $subtotal, 'discount' => $discount, 'tax' => round($tax, 2), 'shipping' => $shipping, 'total' => round($subtotal - $discount + $tax + $shipping, 2), 'coupon' => null];
    }
}
