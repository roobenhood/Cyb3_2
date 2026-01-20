<?php
/**
 * User Model - FINAL CORRECTED VERSION
 * نموذج المستخدم - النسخة النهائية والمصححة
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private $table = 'users';
    private $db;

    public function __construct() {
        $this->db = db();
    }

    /**
     * Find user by ID
     */
    public function findById($id) {
        if (empty($id)) {
            return false;
        }
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        // Never return the password hash
        if ($user) {
            unset($user['password']);
        }

        return $user;
    }

    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * DYNAMIC AND ROBUST create new user method.
     * This is the final fix for the server crash.
     */
    public function create($data) {
        try {
            $fields = [];
            $placeholders = [];
            $values = [];

            // Required fields
            if (isset($data['name'])) {
                $fields[] = 'name';
                $placeholders[] = '?';
                $values[] = $data['name'];
            }
            if (isset($data['email'])) {
                $fields[] = 'email';
                $placeholders[] = '?';
                $values[] = $data['email'];
            }
            if (isset($data['password'])) {
                $fields[] = 'password';
                $placeholders[] = '?';
                $values[] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            }

            // Optional fields from Flutter app (or other sources)
            if (isset($data['phone'])) {
                $fields[] = 'phone';
                $placeholders[] = '?';
                $values[] = $data['phone'];
            }
             if (isset($data['avatar'])) {
                $fields[] = 'avatar';
                $placeholders[] = '?';
                $values[] = $data['avatar'];
            }
            
            // Set default role if not provided
            $fields[] = 'role';
            $placeholders[] = '?';
            $values[] = $data['role'] ?? 'customer';


            if (empty($fields)) {
                return false; // Cannot create a user with no data
            }

            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $this->table,
                implode(', ', $fields),
                implode(', ', $placeholders)
            );

            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);

            $lastId = $this->db->lastInsertId();
            return $this->findById($lastId);

        } catch (PDOException $e) {
            return false; 
        }
    }

    public function update($id, $data) {
        $fields = [];
        $values = [];
        $allowedFields = ['name', 'phone', 'avatar'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return $this->findById($id);
        }

        $values[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);

        return $this->findById($id);
    }

    /**
     * Update password
     */
    public function updatePassword($id, $newPassword) {
        $sql = "UPDATE {$this->table} SET password = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]),
            $id
        ]);
        return true;
    }

    /**
     * Verify password
     */
    public function verifyPassword($user, $password) {
        // Ensure $user and password field exist to prevent errors
        if (!$user || !isset($user['password'])) {
            return false;
        }
        return password_verify($password, $user['password']);
    }

    // ... The rest of the functions (addresses, notifications) remain the same ...
    // ... (Your existing functions for addresses and notifications are fine) ...
    public function getAddresses($userId) {
        $stmt = $this->db->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function addAddress($userId, $data) {
        if (!empty($data['is_default'])) {
            $this->db->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
        }

        $sql = "INSERT INTO addresses (user_id, name, phone, country, city, district, street, building_number, postal_code, is_default)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $data['name'],
            $data['phone'],
            $data['country'] ?? 'اليمن',
            $data['city'],
            $data['district'] ?? null,
            $data['street'],
            $data['building_number'] ?? null,
            $data['postal_code'] ?? null,
            $data['is_default'] ?? 0
        ]);

        return $this->db->lastInsertId();
    }
    
    public function updateAddress($userId, $addressId, $data) {
        $stmt = $this->db->prepare("SELECT id FROM addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$addressId, $userId]);
        if (!$stmt->fetch()) {
            return false;
        }
        if (!empty($data['is_default'])) {
            $this->db->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
        }
        $fields = [];
        $values = [];
        $allowedFields = ['name', 'phone', 'country', 'city', 'district', 'street', 'building_number', 'postal_code', 'is_default'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        if (empty($fields)) {
            return true;
        }
        $values[] = $addressId;
        $sql = "UPDATE addresses SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        return true;
    }
    
    public function deleteAddress($userId, $addressId) {
        $stmt = $this->db->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$addressId, $userId]);
        return $stmt->rowCount() > 0;
    }
    
    public function getDefaultAddress($userId) {
        $stmt = $this->db->prepare("SELECT * FROM addresses WHERE user_id = ? AND is_default = 1 LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function getNotifications($userId, $limit = 20) {
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    public function markNotificationRead($userId, $notificationId) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notificationId, $userId]);
        return $stmt->rowCount() > 0;
    }
    
    public function getUnreadNotificationCount($userId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
}