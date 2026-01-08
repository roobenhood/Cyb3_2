<?php
class User
{
    private PDO $db;
    private string $table = 'users';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(array $data): int|false
    {
        $sql = "INSERT INTO {$this->table} (name, email, password, role, is_active, created_at)
                VALUES (:name, :email, :password, :role, :is_active, NOW())";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'name' => Security::sanitize($data['name']),
            'email' => strtolower(trim($data['email'])),
            'password' => Security::hashPassword($data['password']),
            'role' => $data['role'] ?? ROLE_USER,
            'is_active' => $data['is_active'] ?? USER_ACTIVE
        ]);

        return $result ? (int)$this->db->lastInsertId() : false;
    }


    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => strtolower(trim($email))]);
        $user = $stmt->fetch();

        return $user ?: null;
    }


    public function findById(int $id): ?array
    {
        $sql = "SELECT id, name, email, role, is_active, login_attempts, locked_until, last_login_at, created_at
                FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function authenticate(string $email, string $password): array
    {
        $user = $this->findByEmail($email);

        if (!$user) {
            return ['success' => false, 'error' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة'];
        }

        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $remainingTime = ceil((strtotime($user['locked_until']) - time()) / 60);
            return [
                'success' => false,
                'error' => "الحساب مقفل. حاول مرة أخرى بعد {$remainingTime} دقيقة"
            ];
        }


        if ($user['is_active'] == USER_INACTIVE) {
            return ['success' => false, 'error' => 'الحساب معطل. تواصل مع الإدارة'];
        }

      
        if (!Security::verifyPassword($password, $user['password'])) {
            $this->incrementLoginAttempts($user['id']);
            return ['success' => false, 'error' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة'];
        }

     
        $this->resetLoginAttempts($user['id']);
        $this->updateLastLogin($user['id']);

        unset($user['password'], $user['login_attempts'], $user['locked_until']);

        return ['success' => true, 'user' => $user];
    }

    private function incrementLoginAttempts(int $userId): void
    {
        $sql = "UPDATE {$this->table} SET login_attempts = login_attempts + 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $userId]);

        $sql = "SELECT login_attempts FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $userId]);
        $result = $stmt->fetch();

        if ($result && $result['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
            $lockUntil = date('Y-m-d H:i:s', time() + LOCKOUT_TIME);
            $sql = "UPDATE {$this->table} SET locked_until = :locked_until WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $userId, 'locked_until' => $lockUntil]);
        }
    }

    private function resetLoginAttempts(int $userId): void
    {
        $sql = "UPDATE {$this->table} SET login_attempts = 0, locked_until = NULL WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $userId]);
    }

    private function updateLastLogin(int $userId): void
    {
        $sql = "UPDATE {$this->table} SET last_login_at = NOW(), last_login_ip = :ip WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $userId,
            'ip' => Session::getClientIP()
        ]);
    }

    public function getAll(): array
    {
        $sql = "SELECT id, name, email, role, is_active, last_login_at, created_at
                FROM {$this->table} ORDER BY created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['name', 'email', 'password', 'role', 'is_active'];

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowedFields)) {
                continue;
            }

            if ($key === 'password' && !empty($value)) {
                $fields[] = "password = :password";
                $params['password'] = Security::hashPassword($value);
            } elseif ($key === 'email') {
                $fields[] = "email = :email";
                $params['email'] = strtolower(trim($value));
            } elseif ($key === 'name') {
                $fields[] = "name = :name";
                $params['name'] = Security::sanitize($value);
            } elseif ($key !== 'password') {
                $fields[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        if ($id === 1) return false;

        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        return (int)$result['total'];
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE email = :email";
        $params = ['email' => strtolower(trim($email))];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return $result['total'] > 0;
    }

    public function toggleStatus(int $id): bool
    {
        if ($id === 1) return false;

        $user = $this->findById($id);
        if (!$user) return false;

        $newStatus = $user['is_active'] == USER_ACTIVE ? USER_INACTIVE : USER_ACTIVE;
        return $this->update($id, ['is_active' => $newStatus]);
    }

  
    public function changeRole(int $id, string $role): bool
    {
        if ($id === 1) return false;

        if (!in_array($role, [ROLE_ADMIN, ROLE_USER])) {
            return false;
        }
        return $this->update($id, ['role' => $role]);
    }
}
