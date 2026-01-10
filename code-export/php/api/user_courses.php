<?php
/**
 * API جلب كورسات المستخدم
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../config/database.php';
require_once '../middleware/auth.php';

// التحقق من المصادقة
$user = authenticate();
if (!$user) {
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // جلب الكورسات المسجل بها المستخدم
    $query = "SELECT c.*, 
              e.enrolled_at,
              e.progress,
              e.completed_at,
              u.name as instructor_name,
              (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons,
              (SELECT COUNT(*) FROM lesson_progress lp 
               JOIN lessons l ON lp.lesson_id = l.id 
               WHERE l.course_id = c.id AND lp.user_id = :user_id AND lp.completed = 1) as completed_lessons
              FROM enrollments e
              JOIN courses c ON e.course_id = c.id
              LEFT JOIN users u ON c.instructor_id = u.id
              WHERE e.user_id = :user_id
              ORDER BY e.enrolled_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user['id']);
    $stmt->execute();
    
    $courses = $stmt->fetchAll();
    
    // حساب نسبة التقدم لكل كورس
    foreach ($courses as &$course) {
        $totalLessons = (int)$course['total_lessons'];
        $completedLessons = (int)$course['completed_lessons'];
        $course['progress_percentage'] = $totalLessons > 0 
            ? round(($completedLessons / $totalLessons) * 100) 
            : 0;
    }
    
    echo json_encode([
        "success" => true,
        "courses" => $courses
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "حدث خطأ: " . $e->getMessage()
    ]);
}
?>
