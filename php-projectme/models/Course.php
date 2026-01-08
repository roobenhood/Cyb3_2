<?php

class Course
{
    private PDO $db;
    private string $table = 'courses';
    private string $enrollmentsTable = 'enrollments';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(array $data, int $createdBy): int|false
    {
        $sql = "INSERT INTO {$this->table}
                (title, description, instructor, duration, is_published, created_by, created_at)
                VALUES (:title, :description, :instructor, :duration, :is_published, :created_by, NOW())";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'title' => Security::sanitize($data['title']),
            'description' => Security::sanitize($data['description'] ?? ''),
            'instructor' => Security::sanitize($data['instructor'] ?? ''),
            'duration' => max(0, (int)($data['duration'] ?? 0)),
            'is_published' => isset($data['is_published']) && $data['is_published'] ? COURSE_PUBLISHED : COURSE_DRAFT,
            'created_by' => $createdBy
        ]);

        return $result ? (int)$this->db->lastInsertId() : false;
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT c.*, u.name as creator_name
                FROM {$this->table} c
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $course = $stmt->fetch();

        return $course ?: null;
    }

    public function getAll(bool $publishedOnly = false): array
    {
        $sql = "SELECT c.*, u.name as creator_name
                FROM {$this->table} c
                LEFT JOIN users u ON c.created_by = u.id";

        if ($publishedOnly) {
            $sql .= " WHERE c.is_published = " . COURSE_PUBLISHED;
        }

        $sql .= " ORDER BY c.created_at DESC";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll();
    }

    
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['title', 'description', 'instructor', 'duration', 'is_published'];

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowedFields)) {
                continue;
            }

            if (in_array($key, ['title', 'description', 'instructor'])) {
                $fields[] = "{$key} = :{$key}";
                $params[$key] = Security::sanitize($value);
            } elseif ($key === 'duration') {
                $fields[] = "duration = :duration";
                $params['duration'] = max(0, (int)$value);
            } elseif ($key === 'is_published') {
                $fields[] = "is_published = :is_published";
                $params['is_published'] = $value ? COURSE_PUBLISHED : COURSE_DRAFT;
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
        $sql = "DELETE FROM {$this->enrollmentsTable} WHERE course_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);

        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function count(bool $publishedOnly = false): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";

        if ($publishedOnly) {
            $sql .= " WHERE is_published = " . COURSE_PUBLISHED;
        }

        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();

        return (int)$result['total'];
    }

    public function togglePublish(int $id): bool
    {
        $course = $this->findById($id);
        if (!$course) return false;

        $newStatus = $course['is_published'] == COURSE_PUBLISHED ? COURSE_DRAFT : COURSE_PUBLISHED;
        return $this->update($id, ['is_published' => $newStatus]);
    }

    public function enroll(int $userId, int $courseId): bool
    {
        if ($this->isEnrolled($userId, $courseId)) {
            return false;
        }

        $sql = "INSERT INTO {$this->enrollmentsTable} (user_id, course_id, enrolled_at)
                VALUES (:user_id, :course_id, NOW())";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'user_id' => $userId,
            'course_id' => $courseId
        ]);
    }


    public function unenroll(int $userId, int $courseId): bool
    {
        $sql = "DELETE FROM {$this->enrollmentsTable}
                WHERE user_id = :user_id AND course_id = :course_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'user_id' => $userId,
            'course_id' => $courseId
        ]);
    }

   
    public function isEnrolled(int $userId, int $courseId): bool
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->enrollmentsTable}
                WHERE user_id = :user_id AND course_id = :course_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'course_id' => $courseId
        ]);
        $result = $stmt->fetch();

        return $result['total'] > 0;
    }

    public function getUserCourses(int $userId): array
    {
        $sql = "SELECT c.*, e.enrolled_at
                FROM {$this->table} c
                INNER JOIN {$this->enrollmentsTable} e ON c.id = e.course_id
                WHERE e.user_id = :user_id
                ORDER BY e.enrolled_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function getEnrollmentCount(int $courseId): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->enrollmentsTable} WHERE course_id = :course_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['course_id' => $courseId]);
        $result = $stmt->fetch();

        return (int)$result['total'];
    }
}
