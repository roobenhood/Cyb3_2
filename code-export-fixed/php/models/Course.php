<?php
/**
 * Course Model
 * نموذج الدورة
 */

require_once __DIR__ . '/../config/database.php';

class Course {
    private $conn;
    private $table = 'courses';

    public $id;
    public $title;
    public $description;
    public $instructor_id;
    public $category_id;
    public $thumbnail;
    public $price;
    public $discount_price;
    public $level;
    public $duration;
    public $is_featured;
    public $is_published;
    public $created_at;
    public $updated_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (title, description, instructor_id, category_id, thumbnail, price, discount_price, level, duration, is_featured, is_published) 
                  VALUES (:title, :description, :instructor_id, :category_id, :thumbnail, :price, :discount_price, :level, :duration, :is_featured, :is_published)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':instructor_id', $this->instructor_id);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':thumbnail', $this->thumbnail);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':discount_price', $this->discount_price);
        $stmt->bindParam(':level', $this->level);
        $stmt->bindParam(':duration', $this->duration);
        $stmt->bindParam(':is_featured', $this->is_featured);
        $stmt->bindParam(':is_published', $this->is_published);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    public function findById($id) {
        $query = "SELECT c.*, 
                         u.name as instructor_name,
                         cat.name as category_name,
                         (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as lessons_count,
                         (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as students_count,
                         (SELECT AVG(rating) FROM reviews WHERE course_id = c.id) as avg_rating
                  FROM " . $this->table . " c
                  LEFT JOIN users u ON c.instructor_id = u.id
                  LEFT JOIN categories cat ON c.category_id = cat.id
                  WHERE c.id = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function getAll($filters = []) {
        $page = $filters['page'] ?? 1;
        $perPage = min($filters['per_page'] ?? DEFAULT_PAGE_SIZE, MAX_PAGE_SIZE);
        $offset = ($page - 1) * $perPage;

        $whereConditions = ['c.is_published = 1'];
        $params = [];

        if (!empty($filters['category_id'])) {
            $whereConditions[] = 'c.category_id = :category_id';
            $params[':category_id'] = $filters['category_id'];
        }

        if (!empty($filters['level'])) {
            $whereConditions[] = 'c.level = :level';
            $params[':level'] = $filters['level'];
        }

        if (!empty($filters['search'])) {
            $whereConditions[] = '(c.title LIKE :search OR c.description LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (isset($filters['is_featured']) && $filters['is_featured']) {
            $whereConditions[] = 'c.is_featured = 1';
        }

        if (!empty($filters['min_price'])) {
            $whereConditions[] = 'COALESCE(c.discount_price, c.price) >= :min_price';
            $params[':min_price'] = $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $whereConditions[] = 'COALESCE(c.discount_price, c.price) <= :max_price';
            $params[':max_price'] = $filters['max_price'];
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Sorting
        $orderBy = 'c.created_at DESC';
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price_asc':
                    $orderBy = 'COALESCE(c.discount_price, c.price) ASC';
                    break;
                case 'price_desc':
                    $orderBy = 'COALESCE(c.discount_price, c.price) DESC';
                    break;
                case 'rating':
                    $orderBy = 'avg_rating DESC';
                    break;
                case 'popular':
                    $orderBy = 'students_count DESC';
                    break;
                case 'newest':
                    $orderBy = 'c.created_at DESC';
                    break;
            }
        }

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM " . $this->table . " c WHERE $whereClause";
        $countStmt = $this->conn->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Get paginated results
        $query = "SELECT c.*, 
                         u.name as instructor_name,
                         cat.name as category_name,
                         (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as lessons_count,
                         (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as students_count,
                         (SELECT AVG(rating) FROM reviews WHERE course_id = c.id) as avg_rating
                  FROM " . $this->table . " c
                  LEFT JOIN users u ON c.instructor_id = u.id
                  LEFT JOIN categories cat ON c.category_id = cat.id
                  WHERE $whereClause
                  ORDER BY $orderBy
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage
        ];
    }

    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = ['title', 'description', 'category_id', 'thumbnail', 'price', 'discount_price', 'level', 'duration', 'is_featured', 'is_published'];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $query = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        return $stmt->execute($params);
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    public function getLessons($courseId) {
        $query = "SELECT * FROM lessons WHERE course_id = :course_id ORDER BY order_num ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':course_id', $courseId);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getReviews($courseId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;

        $query = "SELECT r.*, u.name as user_name, u.avatar as user_avatar
                  FROM reviews r
                  INNER JOIN users u ON r.user_id = u.id
                  WHERE r.course_id = :course_id
                  ORDER BY r.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':course_id', $courseId);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getFeatured($limit = 6) {
        $query = "SELECT c.*, 
                         u.name as instructor_name,
                         (SELECT AVG(rating) FROM reviews WHERE course_id = c.id) as avg_rating
                  FROM " . $this->table . " c
                  LEFT JOIN users u ON c.instructor_id = u.id
                  WHERE c.is_featured = 1 AND c.is_published = 1
                  ORDER BY c.created_at DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getByInstructor($instructorId) {
        $query = "SELECT c.*,
                         (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as students_count,
                         (SELECT AVG(rating) FROM reviews WHERE course_id = c.id) as avg_rating
                  FROM " . $this->table . " c
                  WHERE c.instructor_id = :instructor_id
                  ORDER BY c.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':instructor_id', $instructorId);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
