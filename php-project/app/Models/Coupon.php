<?php

namespace App\Models;

use App\Database\Connection;
use App\Interfaces\ModelInterface;

class Coupon implements ModelInterface
{
    private string $table = 'coupons';
    private \PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getAll(): array
    {
        return $this->db->query("SELECT * FROM {$this->table} ORDER BY created_at DESC")->fetchAll();
    }

    /**
     * البحث بالكود
     */
    public function findByCode(string $code): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE code = ? AND is_active = 1");
        $stmt->execute([strtoupper($code)]);
        return $stmt->fetch() ?: null;
    }

    /**
     * التحقق من صلاحية الكوبون
     */
    public function validate(string $code, float $cartTotal, int $userId = null): array
    {
        $coupon = $this->findByCode($code);

        if (!$coupon) {
            return ['valid' => false, 'message' => 'كوبون غير صالح'];
        }

        // التحقق من التاريخ
        $now = date('Y-m-d');
        if ($coupon['start_date'] && $now < $coupon['start_date']) {
            return ['valid' => false, 'message' => 'الكوبون لم يبدأ بعد'];
        }
        if ($coupon['end_date'] && $now > $coupon['end_date']) {
            return ['valid' => false, 'message' => 'الكوبون منتهي الصلاحية'];
        }

        // التحقق من عدد الاستخدامات
        if ($coupon['max_uses'] && $coupon['used_count'] >= $coupon['max_uses']) {
            return ['valid' => false, 'message' => 'تم استنفاد الكوبون'];
        }

        // التحقق من الحد الأدنى للطلب
        if ($coupon['min_order_amount'] && $cartTotal < $coupon['min_order_amount']) {
            return [
                'valid' => false,
                'message' => 'الحد الأدنى للطلب ' . $coupon['min_order_amount'] . ' ر.س'
            ];
        }

        // حساب الخصم
        $discount = 0;
        if ($coupon['type'] === 'percentage') {
            $discount = ($cartTotal * $coupon['value']) / 100;
            if ($coupon['max_discount']) {
                $discount = min($discount, $coupon['max_discount']);
            }
        } else {
            $discount = min($coupon['value'], $cartTotal);
        }

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount' => round($discount, 2),
            'message' => 'تم تطبيق الكوبون'
        ];
    }

    /**
     * تسجيل استخدام الكوبون
     */
    public function recordUsage(int $couponId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET used_count = used_count + 1 WHERE id = ?"
        );
        $stmt->execute([$couponId]);
    }

    /**
     * إنشاء كوبون جديد
     */
    public function create(array $data): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO {$this->table} (code, type, value, min_order_amount, max_discount, 
                    max_uses, start_date, end_date, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");

            $stmt->execute([
                strtoupper($data['code']),
                $data['type'] ?? 'fixed',
                $data['value'],
                $data['min_order_amount'] ?? null,
                $data['max_discount'] ?? null,
                $data['max_uses'] ?? null,
                $data['start_date'] ?? null,
                $data['end_date'] ?? null
            ]);

            return ['success' => true, 'id' => $this->db->lastInsertId()];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'فشل في إنشاء الكوبون'];
        }
    }
}
