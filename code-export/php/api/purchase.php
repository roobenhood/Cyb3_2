<?php
/**
 * API شراء كورس
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../config/database.php';
require_once '../middleware/auth.php';

// التحقق من المصادقة
$user = authenticate();
if (!$user) {
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->course_id)) {
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
    
    // التحقق من وجود الكورس
    $courseQuery = "SELECT id, title, price FROM courses WHERE id = :id AND status = 'published'";
    $courseStmt = $db->prepare($courseQuery);
    $courseStmt->bindParam(":id", $data->course_id);
    $courseStmt->execute();
    
    if ($courseStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "الكورس غير موجود"
        ]);
        exit;
    }
    
    $course = $courseStmt->fetch();
    
    // التحقق من عدم التسجيل المسبق
    $checkQuery = "SELECT id FROM enrollments WHERE user_id = :user_id AND course_id = :course_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(":user_id", $user['id']);
    $checkStmt->bindParam(":course_id", $data->course_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "أنت مسجل بالفعل في هذا الكورس"
        ]);
        exit;
    }
    
    // بدء المعاملة
    $db->beginTransaction();
    
    // إنشاء سجل الدفع
    $paymentQuery = "INSERT INTO payments (user_id, course_id, amount, status, payment_method, created_at) 
                     VALUES (:user_id, :course_id, :amount, 'completed', :payment_method, NOW())";
    $paymentStmt = $db->prepare($paymentQuery);
    $paymentStmt->bindParam(":user_id", $user['id']);
    $paymentStmt->bindParam(":course_id", $data->course_id);
    $paymentStmt->bindParam(":amount", $course['price']);
    $paymentMethod = $data->payment_method ?? 'card';
    $paymentStmt->bindParam(":payment_method", $paymentMethod);
    $paymentStmt->execute();
    
    $paymentId = $db->lastInsertId();
    
    // تسجيل المستخدم في الكورس
    $enrollQuery = "INSERT INTO enrollments (user_id, course_id, payment_id, enrolled_at) 
                    VALUES (:user_id, :course_id, :payment_id, NOW())";
    $enrollStmt = $db->prepare($enrollQuery);
    $enrollStmt->bindParam(":user_id", $user['id']);
    $enrollStmt->bindParam(":course_id", $data->course_id);
    $enrollStmt->bindParam(":payment_id", $paymentId);
    $enrollStmt->execute();
    
    $db->commit();
    
    echo json_encode([
        "success" => true,
        "message" => "تم شراء الكورس بنجاح",
        "course" => [
            "id" => $course['id'],
            "title" => $course['title']
        ]
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "حدث خطأ: " . $e->getMessage()
    ]);
}
?>
