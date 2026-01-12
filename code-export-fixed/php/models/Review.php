<?php
/**
 * Review Model
 * نموذج التقييم
 */

require_once __DIR__ . '/../config/database.php';

class Review {
    private $conn;
    private $table = 'reviews';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($userId, $courseId, $rating, $comment = null) {
        // Check if user already reviewed
        if ($this->hasReviewed($userId, $courseId)) {
            return false;
        }

        $query = "INSERT INTO " . $this->table . " (user_id, course_id, rating, comment) 
                  VALUES (:user_id, :course_id, :rating, :comment)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':course_id', $courseId);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':comment', $comment);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function hasReviewed($userId, $courseId) {
        $query = "SELECT id FROM " . $this->table . " WHERE user_id = :user_id AND course_id = :course_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':course_id', $courseId);
        $stmt->execute();

        return $stmt->fetch() !== false;
    }

    public function getByCourse($courseId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;

        // Get total
        $countQuery = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE course_id = :course_id";
        $countStmt = $this->conn->prepare($countQuery);
        $countStmt->bindParam(':course_id', $courseId);
        $countStmt->execute();
        $total = $countStmt->fetch()['total'];

        // Get reviews
        $query = "SELECT r.*, u.name as user_name, u.avatar as user_avatar
                  FROM " . $this->table . " r
                  INNER JOIN users u ON r.user_id = u.id
                  WHERE r.course_id = :course_id
                  ORDER BY r.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':course_id', $courseId);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total
        ];
    }

    public function getAverageRating($courseId) {
        $query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                  FROM " . $this->table . " WHERE course_id = :course_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':course_id', $courseId);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function getRatingDistribution($courseId) {
        $query = "SELECT rating, COUNT(*) as count 
                  FROM " . $this->table . " 
                  WHERE course_id = :course_id 
                  GROUP BY rating 
                  ORDER BY rating DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':course_id', $courseId);
        $stmt->execute();

        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $distribution[$i] = 0;
        }

        foreach ($stmt->fetchAll() as $row) {
            $distribution[(int)$row['rating']] = (int)$row['count'];
        }

        return $distribution;
    }

    public function update($id, $userId, $rating, $comment = null) {
        $query = "UPDATE " . $this->table . " 
                  SET rating = :rating, comment = :comment 
                  WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':comment', $comment);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);

        return $stmt->execute();
    }

    public function delete($id, $userId) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);

        return $stmt->execute();
    }
}
