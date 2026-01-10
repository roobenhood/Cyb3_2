<?php
/**
 * API تسجيل الدخول
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/database.php';

// التعامل مع طلبات OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// التحقق من نوع الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "طريقة الطلب غير مسموحة"]);
    exit;
}

// قراءة البيانات المرسلة
$data = json_decode(file_get_contents("php://input"));

// التحقق من وجود البيانات
if (empty($data->email) || empty($data->password)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "البريد الإلكتروني وكلمة المرور مطلوبان"
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // البحث عن المستخدم
    $query = "SELECT id, name, email, password, avatar, created_at FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $data->email);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "البريد الإلكتروني أو كلمة المرور غير صحيحة"
        ]);
        exit;
    }
    
    $user = $stmt->fetch();
    
    // التحقق من كلمة المرور
    if (!password_verify($data->password, $user['password'])) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "البريد الإلكتروني أو كلمة المرور غير صحيحة"
        ]);
        exit;
    }
    
    // حذف التوكنات القديمة
    $deleteQuery = "DELETE FROM user_tokens WHERE user_id = :user_id";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(":user_id", $user['id']);
    $deleteStmt->execute();
    
    // إنشاء توكن جديد
    $token = bin2hex(random_bytes(32));
    
    // حفظ التوكن
    $tokenQuery = "INSERT INTO user_tokens (user_id, token, expires_at) VALUES (:user_id, :token, DATE_ADD(NOW(), INTERVAL 30 DAY))";
    $tokenStmt = $db->prepare($tokenQuery);
    $tokenStmt->bindParam(":user_id", $user['id']);
    $tokenStmt->bindParam(":token", $token);
    $tokenStmt->execute();
    
    // تحديث آخر تسجيل دخول
    $updateQuery = "UPDATE users SET last_login = NOW() WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(":id", $user['id']);
    $updateStmt->execute();
    
    // حذف كلمة المرور من الاستجابة
    unset($user['password']);
    
    echo json_encode([
        "success" => true,
        "message" => "تم تسجيل الدخول بنجاح",
        "user" => $user,
        "token" => $token
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "حدث خطأ: " . $e->getMessage()
    ]);
}
?>
