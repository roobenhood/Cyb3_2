<?php
/**
 * API جلب تفاصيل كورس
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../config/database.php';

$courseId = $_GET['id'] ?? null;

if (!$courseId) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "معرف الكورس مطلوب"
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // جلب بيانات الكورس
    $query = "SELECT c.*, 
              u.name as instructor_name,
              u.avatar as instructor_avatar,
              u.bio as instructor_bio,
              (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as students_count,
              (SELECT AVG(rating) FROM reviews WHERE course_id = c.id) as avg_rating,
              (SELECT COUNT(*) FROM reviews WHERE course_id = c.id) as reviews_count
              FROM courses c
              LEFT JOIN users u ON c.instructor_id = u.id
              WHERE c.id = :id AND c.status = 'published'";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $courseId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "الكورس غير موجود"
        ]);
        exit;
    }
    
    $course = $stmt->fetch();
    
    // جلب الدروس
    $lessonsQuery = "SELECT id, title, duration, video_url, is_preview, sort_order 
                     FROM lessons 
                     WHERE course_id = :course_id 
                     ORDER BY sort_order ASC";
    $lessonsStmt = $db->prepare($lessonsQuery);
    $lessonsStmt->bindParam(":course_id", $courseId);
    $lessonsStmt->execute();
    $lessons = $lessonsStmt->fetchAll();
    
    // إخفاء روابط الفيديو للدروس غير المجانية
    foreach ($lessons as &$lesson) {
        if (!$lesson['is_preview']) {
            $lesson['video_url'] = null;
        }
    }
    
    // جلب التقييمات
    $reviewsQuery = "SELECT r.*, u.name as user_name, u.avatar as user_avatar 
                     FROM reviews r 
                     LEFT JOIN users u ON r.user_id = u.id 
                     WHERE r.course_id = :course_id 
                     ORDER BY r.created_at DESC 
                     LIMIT 10";
    $reviewsStmt = $db->prepare($reviewsQuery);
    $reviewsStmt->bindParam(":course_id", $courseId);
    $reviewsStmt->execute();
    $reviews = $reviewsStmt->fetchAll();
    
    $course['lessons'] = $lessons;
    $course['reviews'] = $reviews;
    
    echo json_encode([
        "success" => true,
        "course" => $course
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "حدث خطأ: " . $e->getMessage()
    ]);
}
?>
