<?php
/**
 * User Model
 * نموذج المستخدم
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
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
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
     * Create new user
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (name, email, password, phone, avatar, role) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            $data['phone'] ?? null,
            $data['avatar'] ?? null,
            $data['role'] ?? 'customer'
        ]);

        return $this->findById($this->db->lastInsertId());
    }

    /**
     * Update user
     */
    public function update($id, $data) {
        $fields = [];
        $values = [];

        $allowedFields = ['name', 'phone', 'avatar'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = ?";
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
    public function updatePassword($id, $password) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET password = ? WHERE id = ?");
        $stmt->execute([
            password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            $id
        ]);
        return true;
    }

    /**
     * Verify password
     */
    public function verifyPassword($user, $password) {
        return password_verify($password, $user['password']);
    }

    /**
     * Get all users (admin)
     */
    public function getAll($page = 1, $perPage = 10, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['role'])) {
            $where[] = "role = ?";
            $params[] = $filters['role'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(name LIKE ? OR email LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        if (isset($filters['is_active'])) {
            $where[] = "is_active = ?";
            $params[] = $filters['is_active'];
        }

        $whereClause = implode(' AND ', $where);

        // Count total
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Get users
        $params[] = $perPage;
        $params[] = $offset;
        
        $sql = "SELECT id, name, email, phone, avatar, role, is_active, created_at 
                FROM {$this->table} 
                WHERE {$whereClause} 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        return [
            'users' => $users,
            'total' => $total
        ];
    }

    /**
     * Delete user
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Toggle user active status
     */
    public function toggleActive($id) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        return $this->findById($id);
    }

    /**
     * Get user addresses
     */
    public function getAddresses($userId) {
        $stmt = $this->db->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Add address
     */
    public function addAddress($userId, $data) {
        // If this is default, unset other defaults
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
            $data['country'] ?? 'السعودية',
            $data['city'],
            $data['district'] ?? null,
            $data['street'],
            $data['building_number'] ?? null,
            $data['postal_code'] ?? null,
            $data['is_default'] ?? 0
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Update address
     */
    public function updateAddress($userId, $addressId, $data) {
        // If this is default, unset other defaults
        if (!empty($data['is_default'])) {
            $this->db->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
        }

        $sql = "UPDATE addresses SET 
                name = ?, phone = ?, country = ?, city = ?, district = ?, 
                street = ?, building_number = ?, postal_code = ?, is_default = ?
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['phone'],
            $data['country'] ?? 'السعودية',
            $data['city'],
            $data['district'] ?? null,
            $data['street'],
            $data['building_number'] ?? null,
            $data['postal_code'] ?? null,
            $data['is_default'] ?? 0,
            $addressId,
            $userId
        ]);
    }

    /**
     * Delete address
     */
    public function deleteAddress($userId, $addressId) {
        $stmt = $this->db->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
        return $stmt->execute([$addressId, $userId]);
    }
}
