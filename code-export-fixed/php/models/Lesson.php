<?php
/**
 * Lesson Model
 * نموذج الدرس
 */

require_once __DIR__ . '/../config/database.php';

class Lesson {
    private $conn;
    private $table = 'lessons';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (course_id, title, description, video_url, duration, order_num, is_free) 
                  VALUES (:course_id, :title, :description, :video_url, :duration, :order_num, :is_free)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':course_id', $data['course_id']);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':video_url', $data['video_url']);
        $stmt->bindParam(':duration', $data['duration']);
        $stmt->bindParam(':order_num', $data['order_num']);
        $stmt->bindParam(':is_free', $data['is_free']);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function findById($id) {
        $query = "SELECT l.*, c.title as course_title 
                  FROM " . $this->table . " l
                  INNER JOIN courses c ON l.course_id = c.id
                  WHERE l.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function getByCourse($courseId) {
        $query = "SELECT * FROM " . $this->table . " WHERE course_id = :course_id ORDER BY order_num ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':course_id', $courseId);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = ['title', 'description', 'video_url', 'duration', 'order_num', 'is_free'];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $query = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        return $stmt->execute($params);
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    public function reorder($courseId, $orderedIds) {
        $this->conn->beginTransaction();

        try {
            foreach ($orderedIds as $order => $lessonId) {
                $query = "UPDATE " . $this->table . " SET order_num = :order WHERE id = :id AND course_id = :course_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindValue(':order', $order + 1);
                $stmt->bindValue(':id', $lessonId);
                $stmt->bindValue(':course_id', $courseId);
                $stmt->execute();
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getNextOrder($courseId) {
        $query = "SELECT MAX(order_num) as max_order FROM " . $this->table . " WHERE course_id = :course_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':course_id', $courseId);
        $stmt->execute();
        $result = $stmt->fetch();

        return ($result['max_order'] ?? 0) + 1;
    }
}
