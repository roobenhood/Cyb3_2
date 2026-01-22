<?php
namespace App\Models;

use App\Database\Connection;

/**
 * User Model - Final and Correct Version
 * This version is designed to work with Firebase authentication in an MVC structure.
 */
class User
{
    private string $table = 'users';
    private \PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    /**
     * Finds a user by their primary ID.
     */
    public function findById(int $id): ?array
    {
        // Select all relevant user data, excluding the password hash.
        $stmt = $this->db->prepare("SELECT id, name, email, phone, avatar, firebase_uid, is_active, created_at FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Finds a user by their email address.
     * Important: This selects the full record including the password for verification purposes.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Finds a user by their unique Firebase UID.
     * This is the crucial function for Firebase sync.
     */
    public function findByFirebaseUid(string $firebase_uid): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE firebase_uid = ?");
        $stmt->execute([$firebase_uid]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Creates a new user in the database.
     * This single, smart function handles all registration types (email/password and social).
     */
    public function create(array $data): ?array
    {
        $sql = "INSERT INTO {$this->table} (name, email, firebase_uid, password, avatar, is_active) 
                VALUES (:name, :email, :firebase_uid, :password, :avatar, 1)";

        // Hash the password only if it's provided (for email/password registration)
        $passwordHash = isset($data['password']) && !empty($data['password'])
            ? password_hash($data['password'], PASSWORD_DEFAULT)
            : null;

        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            ':name'         => $data['name'] ?? 'New User',
            ':email'        => $data['email'],
            ':firebase_uid' => $data['firebase_uid'] ?? null,
            ':password'     => $passwordHash,
            ':avatar'       => $data['avatar'] ?? null
        ]);

        if ($success) {
            $id = (int) $this->db->lastInsertId();
            return $this->findById($id); // Return the newly created user data
        }

        return null; // Return null on failure
    }

    /**
     * Updates an existing user's data.
     * Can update profile info or add a firebase_uid to an existing account.
     */
    public function update(int $id, array $data): ?array
    {
        if (empty($data)) {
            return $this->findById($id);
        }

        $allowedFields = ['name', 'phone', 'avatar', 'firebase_uid'];
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "{$key} = ?";
                $values[] = $value;
            }
        }

        if (empty($fields)) {
            return $this->findById($id); // Nothing to update
        }

        $values[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";

        if ($this->db->prepare($sql)->execute($values)) {
            return $this->findById($id); // Return the updated user data
        }

        return null; // Return null on failure
    }

    /**
     * Verifies a user's current password.
     * Useful for 'change password' functionality for users who have a password.
     */
    public function verifyPassword(int $id, string $password): bool
    {
        $stmt = $this->db->prepare("SELECT password FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        // A user registered via social login might not have a password
        if (!$user || empty($user['password'])) {
            return false;
        }

        return password_verify($password, $user['password']);
    }

    /**
     * Updates only the user's password.
     */
    public function updatePassword(int $id, string $newPassword): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET password = ? WHERE id = ?");
        return $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $id]);
    }
}