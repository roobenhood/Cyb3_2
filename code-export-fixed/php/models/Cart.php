<?php
/**
 * Cart Model
 * نموذج سلة التسوق
 */

require_once __DIR__ . '/../config/database.php';

class Cart {
    private $conn;
    private $table = 'cart';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function add($userId, $courseId) {
        // Check if already in cart
        if ($this->isInCart($userId, $courseId)) {
            return false;
        }

        $query = "INSERT INTO " . $this->table . " (user_id, course_id) VALUES (:user_id, :course_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':course_id', $courseId);

        return $stmt->execute();
    }

    public function isInCart($userId, $courseId) {
        $query = "SELECT id FROM " . $this->table . " WHERE user_id = :user_id AND course_id = :course_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':course_id', $courseId);
        $stmt->execute();

        return $stmt->fetch() !== false;
    }

    public function getByUser($userId) {
        $query = "SELECT c.*, 
                         cr.title, cr.thumbnail, cr.price, cr.discount_price,
                         u.name as instructor_name
                  FROM " . $this->table . " c
                  INNER JOIN courses cr ON c.course_id = cr.id
                  LEFT JOIN users u ON cr.instructor_id = u.id
                  WHERE c.user_id = :user_id
                  ORDER BY c.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function remove($userId, $courseId) {
        $query = "DELETE FROM " . $this->table . " WHERE user_id = :user_id AND course_id = :course_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':course_id', $courseId);

        return $stmt->execute();
    }

    public function clear($userId) {
        $query = "DELETE FROM " . $this->table . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);

        return $stmt->execute();
    }

    public function getTotal($userId) {
        $query = "SELECT SUM(COALESCE(cr.discount_price, cr.price)) as total
                  FROM " . $this->table . " c
                  INNER JOIN courses cr ON c.course_id = cr.id
                  WHERE c.user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $result = $stmt->fetch();

        return $result['total'] ?? 0;
    }

    public function getCount($userId) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $result = $stmt->fetch();

        return $result['count'] ?? 0;
    }

    public function checkout($userId) {
        $this->conn->beginTransaction();

        try {
            // Get cart items
            $items = $this->getByUser($userId);

            // Create enrollments for each course
            $enrollmentModel = new Enrollment();
            foreach ($items as $item) {
                $enrollmentModel->create($userId, $item['course_id']);
            }

            // Clear cart
            $this->clear($userId);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}

require_once __DIR__ . '/Enrollment.php';
