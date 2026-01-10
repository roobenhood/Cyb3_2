<?php
/**
 * Middleware للتحقق من المصادقة
 */

require_once __DIR__ . '/../config/database.php';

function authenticate() {
    // جلب التوكن من الهيدر
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader)) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "التوكن مطلوب"
        ]);
        return null;
    }
    
    // استخراج التوكن
    $token = str_replace('Bearer ', '', $authHeader);
    
    if (empty($token)) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "التوكن غير صالح"
        ]);
        return null;
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // التحقق من التوكن
        $query = "SELECT t.user_id, t.expires_at, u.id, u.name, u.email, u.avatar 
                  FROM user_tokens t 
                  JOIN users u ON t.user_id = u.id 
                  WHERE t.token = :token";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            http_response_code(401);
            echo json_encode([
                "success" => false,
                "message" => "التوكن غير صالح"
            ]);
            return null;
        }
        
        $data = $stmt->fetch();
        
        // التحقق من انتهاء الصلاحية
        if (strtotime($data['expires_at']) < time()) {
            // حذف التوكن المنتهي
            $deleteQuery = "DELETE FROM user_tokens WHERE token = :token";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->bindParam(":token", $token);
            $deleteStmt->execute();
            
            http_response_code(401);
            echo json_encode([
                "success" => false,
                "message" => "انتهت صلاحية التوكن"
            ]);
            return null;
        }
        
        return [
            'id' => $data['id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'avatar' => $data['avatar']
        ];
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "خطأ في التحقق: " . $e->getMessage()
        ]);
        return null;
    }
}

/**
 * التحقق من أن المستخدم مدرب
 */
function authenticateInstructor() {
    $user = authenticate();
    if (!$user) {
        return null;
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT role FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $user['id']);
        $stmt->execute();
        $data = $stmt->fetch();
        
        if ($data['role'] !== 'instructor' && $data['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode([
                "success" => false,
                "message" => "غير مصرح لك بهذا الإجراء"
            ]);
            return null;
        }
        
        $user['role'] = $data['role'];
        return $user;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "خطأ: " . $e->getMessage()
        ]);
        return null;
    }
}
?>
