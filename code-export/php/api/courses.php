<?php
/**
 * API جلب الكورسات
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // الفلترة والترتيب
    $category = $_GET['category'] ?? null;
    $search = $_GET['search'] ?? null;
    $sort = $_GET['sort'] ?? 'created_at';
    $order = $_GET['order'] ?? 'DESC';
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 10);
    $offset = ($page - 1) * $limit;
    
    // بناء الاستعلام
    $query = "SELECT c.*, 
              u.name as instructor_name,
              (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as students_count,
              (SELECT AVG(rating) FROM reviews WHERE course_id = c.id) as avg_rating
              FROM courses c
              LEFT JOIN users u ON c.instructor_id = u.id
              WHERE c.status = 'published'";
    
    $params = [];
    
    if ($category) {
        $query .= " AND c.category = :category";
        $params[':category'] = $category;
    }
    
    if ($search) {
        $query .= " AND (c.title LIKE :search OR c.description LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // التحقق من صحة الترتيب
    $allowedSorts = ['created_at', 'price', 'title', 'rating'];
    $sort = in_array($sort, $allowedSorts) ? $sort : 'created_at';
    $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
    
    $query .= " ORDER BY c.$sort $order LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $courses = $stmt->fetchAll();
    
    // جلب عدد الكورسات الكلي للـ pagination
    $countQuery = "SELECT COUNT(*) as total FROM courses c WHERE c.status = 'published'";
    if ($category) {
        $countQuery .= " AND c.category = :category";
    }
    if ($search) {
        $countQuery .= " AND (c.title LIKE :search OR c.description LIKE :search)";
    }
    
    $countStmt = $db->prepare($countQuery);
    if ($category) {
        $countStmt->bindValue(':category', $category);
    }
    if ($search) {
        $countStmt->bindValue(':search', "%$search%");
    }
    $countStmt->execute();
    $total = $countStmt->fetch()['total'];
    
    echo json_encode([
        "success" => true,
        "courses" => $courses,
        "pagination" => [
            "page" => $page,
            "limit" => $limit,
            "total" => (int)$total,
            "total_pages" => ceil($total / $limit)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "حدث خطأ: " . $e->getMessage()
    ]);
}
?>
