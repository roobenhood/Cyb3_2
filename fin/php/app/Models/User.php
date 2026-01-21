<?php

namespace App\Models;

use App\Database\Connection;

/**
 * User Model - نموذج المستخدم
 */
class User
{
    private string $table = 'users';
    private \PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, name, email, phone, avatar, is_active, social_provider, created_at FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    /**
     * إنشاء مستخدم جديد (تسجيل عادي بكلمة مرور)
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table} (name, email, password, phone) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['phone'] ?? null
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * إنشاء مستخدم عبر Social Login (بدون كلمة مرور)
     */
    public function createSocialUser(array $data): int
    {
        $sql = "INSERT INTO {$this->table} (name, email, social_provider, firebase_uid) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['provider'],
            $data['firebase_uid'] ?? null
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * تحديث معلومات المزود الاجتماعي
     */
    public function updateSocialProvider(int $id, string $provider, ?string $firebaseUid = null): bool
    {
        $sql = "UPDATE {$this->table} SET social_provider = ?, firebase_uid = ?, last_login = NOW() WHERE id = ?";
        return $this->db->prepare($sql)->execute([$provider, $firebaseUid, $id]);
    }

    public function update(int $id, array $data): bool
    {
        if (empty($data)) return true;
        
        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            // منع تحديث الحقول الحساسة
            if (in_array($key, ['id', 'email', 'password', 'role'])) continue;
            $fields[] = "{$key} = ?";
            $values[] = $value;
        }
        
        if (empty($fields)) return true;
        
        $values[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        return $this->db->prepare($sql)->execute($values);
    }

    public function verifyPassword(int $id, string $password): bool
    {
        $stmt = $this->db->prepare("SELECT password FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        // للمستخدمين عبر Social Login بدون كلمة مرور
        if (!$user || empty($user['password'])) {
            return false;
        }
        
        return password_verify($password, $user['password']);
    }

    public function updatePassword(int $id, string $password): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET password = ? WHERE id = ?");
        return $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }
}
