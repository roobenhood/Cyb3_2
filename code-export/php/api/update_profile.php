<?php
/**
 * API تحديث الملف الشخصي
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../config/database.php';
require_once '../middleware/auth.php';

// التحقق من المصادقة
$user = authenticate();
if (!$user) {
    exit;
}

$data = json_decode(file_get_contents("php://input"));

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // بناء استعلام التحديث
    $updates = [];
    $params = [':id' => $user['id']];
    
    if (!empty($data->name)) {
        $updates[] = "name = :name";
        $params[':name'] = $data->name;
    }
    
    if (!empty($data->phone)) {
        $updates[] = "phone = :phone";
        $params[':phone'] = $data->phone;
    }
    
    if (!empty($data->bio)) {
        $updates[] = "bio = :bio";
        $params[':bio'] = $data->bio;
    }
    
    if (!empty($data->avatar)) {
        $updates[] = "avatar = :avatar";
        $params[':avatar'] = $data->avatar;
    }
    
    // تحديث كلمة المرور
    if (!empty($data->new_password)) {
        if (empty($data->current_password)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "كلمة المرور الحالية مطلوبة"
            ]);
            exit;
        }
        
        // التحقق من كلمة المرور الحالية
        $passQuery = "SELECT password FROM users WHERE id = :id";
        $passStmt = $db->prepare($passQuery);
        $passStmt->bindParam(":id", $user['id']);
        $passStmt->execute();
        $currentUser = $passStmt->fetch();
        
        if (!password_verify($data->current_password, $currentUser['password'])) {
            http_response_code(401);
            echo json_encode([
                "success" => false,
                "message" => "كلمة المرور الحالية غير صحيحة"
            ]);
            exit;
        }
        
        if (strlen($data->new_password) < 6) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل"
            ]);
            exit;
        }
        
        $updates[] = "password = :password";
        $params[':password'] = password_hash($data->new_password, PASSWORD_DEFAULT);
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "لا توجد بيانات للتحديث"
        ]);
        exit;
    }
    
    $query = "UPDATE users SET " . implode(", ", $updates) . ", updated_at = NOW() WHERE id = :id";
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    
    // جلب البيانات المحدثة
    $selectQuery = "SELECT id, name, email, phone, bio, avatar, created_at FROM users WHERE id = :id";
    $selectStmt = $db->prepare($selectQuery);
    $selectStmt->bindParam(":id", $user['id']);
    $selectStmt->execute();
    $updatedUser = $selectStmt->fetch();
    
    echo json_encode([
        "success" => true,
        "message" => "تم تحديث الملف الشخصي بنجاح",
        "user" => $updatedUser
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "حدث خطأ: " . $e->getMessage()
    ]);
}
?>
