<?php
/**
 * API تسجيل مستخدم جديد
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

// التحقق من وجود البيانات المطلوبة
if (empty($data->name) || empty($data->email) || empty($data->password)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "جميع الحقول مطلوبة"
    ]);
    exit;
}

// التحقق من صحة البريد الإلكتروني
if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "البريد الإلكتروني غير صالح"
    ]);
    exit;
}

// التحقق من طول كلمة المرور
if (strlen($data->password) < 6) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "كلمة المرور يجب أن تكون 6 أحرف على الأقل"
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // التحقق من عدم وجود المستخدم
    $checkQuery = "SELECT id FROM users WHERE email = :email";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(":email", $data->email);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "البريد الإلكتروني مستخدم بالفعل"
        ]);
        exit;
    }
    
    // تشفير كلمة المرور
    $hashedPassword = password_hash($data->password, PASSWORD_DEFAULT);
    
    // إدراج المستخدم الجديد
    $query = "INSERT INTO users (name, email, password, created_at) VALUES (:name, :email, :password, NOW())";
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(":name", $data->name);
    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":password", $hashedPassword);
    
    if ($stmt->execute()) {
        $userId = $db->lastInsertId();
        
        // إنشاء توكن
        $token = bin2hex(random_bytes(32));
        
        // حفظ التوكن
        $tokenQuery = "INSERT INTO user_tokens (user_id, token, expires_at) VALUES (:user_id, :token, DATE_ADD(NOW(), INTERVAL 30 DAY))";
        $tokenStmt = $db->prepare($tokenQuery);
        $tokenStmt->bindParam(":user_id", $userId);
        $tokenStmt->bindParam(":token", $token);
        $tokenStmt->execute();
        
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "تم إنشاء الحساب بنجاح",
            "user" => [
                "id" => $userId,
                "name" => $data->name,
                "email" => $data->email
            ],
            "token" => $token
        ]);
    } else {
        throw new Exception("فشل في إنشاء الحساب");
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "حدث خطأ: " . $e->getMessage()
    ]);
}
?>
