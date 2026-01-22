<?php

namespace App\Models;

use App\Database\Connection;
use App\Interfaces\ModelInterface;

class Address implements ModelInterface
{
    private string $table = 'addresses';
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
        return $this->db->query("SELECT * FROM {$this->table}")->fetchAll();
    }

    /**
     * الحصول على عناوين المستخدم
     */
    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY is_default DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * الحصول على العنوان الافتراضي
     */
    public function getDefault(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ? AND is_default = 1");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * إضافة عنوان جديد
     */
    public function create(int $userId, array $data): array
    {
        try {
            // إذا كان العنوان افتراضي، إلغاء الافتراضي من الآخرين
            if ($data['is_default'] ?? false) {
                $this->clearDefault($userId);
            }

            $stmt = $this->db->prepare("
                INSERT INTO {$this->table} (user_id, name, phone, city, district, street, 
                    building, floor, apartment, notes, is_default, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $userId,
                $data['name'],
                $data['phone'],
                $data['city'],
                $data['district'] ?? null,
                $data['street'],
                $data['building'] ?? null,
                $data['floor'] ?? null,
                $data['apartment'] ?? null,
                $data['notes'] ?? null,
                $data['is_default'] ?? 0
            ]);

            return [
                'success' => true,
                'id' => $this->db->lastInsertId(),
                'message' => 'تم إضافة العنوان'
            ];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'فشل في إضافة العنوان'];
        }
    }

    /**
     * تحديث عنوان
     */
    public function update(int $id, int $userId, array $data): array
    {
        $address = $this->findById($id);
        if (!$address || $address['user_id'] != $userId) {
            return ['success' => false, 'message' => 'العنوان غير موجود'];
        }

        try {
            if ($data['is_default'] ?? false) {
                $this->clearDefault($userId);
            }

            $stmt = $this->db->prepare("
                UPDATE {$this->table} SET 
                    name = ?, phone = ?, city = ?, district = ?, street = ?,
                    building = ?, floor = ?, apartment = ?, notes = ?, is_default = ?
                WHERE id = ? AND user_id = ?
            ");

            $stmt->execute([
                $data['name'],
                $data['phone'],
                $data['city'],
                $data['district'] ?? null,
                $data['street'],
                $data['building'] ?? null,
                $data['floor'] ?? null,
                $data['apartment'] ?? null,
                $data['notes'] ?? null,
                $data['is_default'] ?? 0,
                $id,
                $userId
            ]);

            return ['success' => true, 'message' => 'تم تحديث العنوان'];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'فشل في تحديث العنوان'];
        }
    }

    /**
     * حذف عنوان
     */
    public function delete(int $id, int $userId): array
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);

        return [
            'success' => $stmt->rowCount() > 0,
            'message' => $stmt->rowCount() > 0 ? 'تم حذف العنوان' : 'العنوان غير موجود'
        ];
    }

    /**
     * إلغاء العنوان الافتراضي
     */
    private function clearDefault(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET is_default = 0 WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    /**
     * تعيين كعنوان افتراضي
     */
    public function setDefault(int $id, int $userId): array
    {
        $this->clearDefault($userId);

        $stmt = $this->db->prepare("UPDATE {$this->table} SET is_default = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);

        return [
            'success' => $stmt->rowCount() > 0,
            'message' => 'تم تعيين العنوان كافتراضي'
        ];
    }
}
