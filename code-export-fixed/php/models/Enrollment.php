<?php
/**
 * Enrollment Model
 * نموذج التسجيل
 */

require_once __DIR__ . '/../config/database.php';

class Enrollment {
    private $conn;
    private $table = 'enrollments';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($userId, $courseId) {
        // Check if already enrolled
        if ($this->isEnrolled($userId, $courseId)) {
            return false;
        }

        $query = "INSERT INTO " . $this->table . " (user_id, course_id) VALUES (:user_id, :course_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':course_id', $courseId);

        return $stmt->execute();
    }

    public function isEnrolled($userId, $courseId) {
        $query = "SELECT id FROM " . $this->table . " WHERE user_id = :user_id AND course_id = :course_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':course_id', $courseId);
        $stmt->execute();

        return $stmt->fetch() !== false;
    }

    public function getByUser($userId) {
        $query = "SELECT e.*, c.title, c.thumbnail, c.duration,
                         (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons
                  FROM " . $this->table . " e
                  INNER JOIN courses c ON e.course_id = c.id
                  WHERE e.user_id = :user_id
                  ORDER BY e.enrolled_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function updateProgress($userId, $courseId, $progress, $completedLessons = null) {
        $query = "UPDATE " . $this->table . " 
                  SET progress = :progress";
        
        $params = [
            ':progress' => $progress,
            ':user_id' => $userId,
            ':course_id' => $courseId
        ];

        if ($completedLessons !== null) {
            $query .= ", completed_lessons = :completed_lessons";
            $params[':completed_lessons'] = is_array($completedLessons) ? implode(',', $completedLessons) : $completedLessons;
        }

        if ($progress >= 100) {
            $query .= ", is_completed = 1, completed_at = NOW()";
        }

        $query .= " WHERE user_id = :user_id AND course_id = :course_id";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }

    public function markLessonComplete($userId, $courseId, $lessonId) {
        // Get current completed lessons
        $query = "SELECT completed_lessons FROM " . $this->table . " WHERE user_id = :user_id AND course_id = :course_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':course_id', $courseId);
        $stmt->execute();
        $enrollment = $stmt->fetch();

        if (!$enrollment) {
            return false;
        }

        $completedLessons = !empty($enrollment['completed_lessons']) 
            ? explode(',', $enrollment['completed_lessons']) 
            : [];

        if (!in_array($lessonId, $completedLessons)) {
            $completedLessons[] = $lessonId;
        }

        // Calculate progress
        $countQuery = "SELECT COUNT(*) as total FROM lessons WHERE course_id = :course_id";
        $countStmt = $this->conn->prepare($countQuery);
        $countStmt->bindParam(':course_id', $courseId);
        $countStmt->execute();
        $totalLessons = $countStmt->fetch()['total'];

        $progress = ($totalLessons > 0) ? (count($completedLessons) / $totalLessons) * 100 : 0;

        return $this->updateProgress($userId, $courseId, $progress, $completedLessons);
    }

    public function getProgress($userId, $courseId) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id AND course_id = :course_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':course_id', $courseId);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function delete($userId, $courseId) {
        $query = "DELETE FROM " . $this->table . " WHERE user_id = :user_id AND course_id = :course_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':course_id', $courseId);

        return $stmt->execute();
    }
}
