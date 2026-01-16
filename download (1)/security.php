<?php
/**
 * ملف الأمان المتقدم
 * Advanced Security Functions
 * AlwaniCTF Security Module
 */

if (!defined('CYBERCTF')) {
    die('Access denied');
}

// ============================================
// حماية ضد هجمات Brute Force
// ============================================

/**
 * التحقق من محاولات الدخول الفاشلة
 * يعتمد على IP فقط لتجنب حساب المحاولات الخاطئة
 */
function checkLoginAttempts($identifier) {
    global $pdo;
    
    // التحقق من أن MAX_LOGIN_ATTEMPTS أكبر من 0
    if (MAX_LOGIN_ATTEMPTS <= 0) {
        return false; // تعطيل الحماية
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $lockout_time = LOGIN_LOCKOUT_TIME > 0 ? LOGIN_LOCKOUT_TIME : 900;
    
    try {
        // التحقق من وجود جدول activity_log
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'activity_log'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            return false; // الجدول غير موجود
        }
        
        // حساب المحاولات الفاشلة لهذا الـ IP فقط خلال فترة القفل
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM activity_log 
            WHERE action = 'failed_login' 
            AND ip_address = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$ip, $lockout_time]);
        $attempts = $stmt->fetchColumn();
        
        return $attempts >= MAX_LOGIN_ATTEMPTS;
    } catch (Exception $e) {
        // في حالة الخطأ، السماح بالمحاولة
        return false;
    }
}

/**
 * الحصول على وقت الانتظار المتبقي
 */
function getRemainingLockoutTime($identifier) {
    global $pdo;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    try {
        $stmt = $pdo->prepare("
            SELECT created_at FROM activity_log 
            WHERE action = 'failed_login' 
            AND ip_address = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$ip]);
        $last_attempt = $stmt->fetch();
        
        if ($last_attempt) {
            $last_time = strtotime($last_attempt['created_at']);
            $unlock_time = $last_time + LOGIN_LOCKOUT_TIME;
            $remaining = $unlock_time - time();
            return max(0, $remaining);
        }
    } catch (Exception $e) {
        return 0;
    }
    
    return 0;
}

/**
 * مسح محاولات الدخول الفاشلة بعد تسجيل دخول ناجح
 */
function clearLoginAttempts() {
    global $pdo;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    try {
        $stmt = $pdo->prepare("
            DELETE FROM activity_log 
            WHERE action = 'failed_login' 
            AND ip_address = ?
        ");
        $stmt->execute([$ip]);
    } catch (Exception $e) {
        // تجاهل الأخطاء
    }
}

// ============================================
// حماية ضد هجمات XSS و CSRF
// ============================================

/**
 * تنظيف عميق للمدخلات
 */
function deepSanitize($input, $type = 'string') {
    if (is_array($input)) {
        return array_map(function($item) use ($type) {
            return deepSanitize($item, $type);
        }, $input);
    }
    
    // إزالة المسافات الزائدة
    $input = trim($input);
    
    switch ($type) {
        case 'int':
            return intval($input);
        case 'float':
            return floatval($input);
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        case 'html':
            return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        default:
            return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * التحقق من صحة البريد الإلكتروني
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * التحقق من قوة كلمة المرور
 */
function isStrongPassword($password) {
    // الحد الأدنى 8 أحرف
    if (strlen($password) < 8) return false;
    
    // يجب أن تحتوي على حرف كبير
    if (!preg_match('/[A-Z]/', $password)) return false;
    
    // يجب أن تحتوي على حرف صغير
    if (!preg_match('/[a-z]/', $password)) return false;
    
    // يجب أن تحتوي على رقم
    if (!preg_match('/[0-9]/', $password)) return false;
    
    return true;
}

// ============================================
// إدارة الجلسات الآمنة
// ============================================

/**
 * تجديد معرف الجلسة
 */
function regenerateSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * التحقق من سرقة الجلسة
 */
function validateSession() {
    $current_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $current_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $current_agent) {
        // User Agent تغير - قد تكون سرقة جلسة
        session_destroy();
        return false;
    }
    
    $_SESSION['user_agent'] = $current_agent;
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * التحقق من انتهاء الجلسة
 */
function checkSessionTimeout() {
    $timeout = SESSION_LIFETIME;
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

// ============================================
// حماية ضد SQL Injection
// ============================================

/**
 * التحقق من أن القيمة رقم صحيح
 */
function validateInt($value) {
    return filter_var($value, FILTER_VALIDATE_INT) !== false;
}

/**
 * التحقق من اسم الجدول/العمود (لمنع SQL Injection)
 */
function validateIdentifier($name, $allowed = []) {
    if (!empty($allowed) && !in_array($name, $allowed)) {
        return false;
    }
    return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name);
}

// ============================================
// Rate Limiting
// ============================================

/**
 * التحقق من معدل الطلبات
 */
function checkRateLimit($action, $limit = 60, $period = 60) {
    global $pdo;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_id = $_SESSION['user_id'] ?? null;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM activity_log 
        WHERE action = ? 
        AND (ip_address = ? OR user_id = ?)
        AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $stmt->execute([$action, $ip, $user_id, $period]);
    $count = $stmt->fetchColumn();
    
    return $count < $limit;
}

// ============================================
// أمان رفع الملفات
// ============================================

/**
 * التحقق من نوع الملف
 */
function validateFileUpload($file, $allowed_types = null, $max_size = null) {
    if ($allowed_types === null) {
        $allowed_types = ALLOWED_EXTENSIONS;
    }
    if ($max_size === null) {
        $max_size = MAX_FILE_SIZE;
    }
    
    // التحقق من الأخطاء
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'Upload error'];
    }
    
    // التحقق من الحجم
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'error' => 'File too large'];
    }
    
    // التحقق من الامتداد
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_types)) {
        return ['valid' => false, 'error' => 'Invalid file type'];
    }
    
    // التحقق من MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $dangerous_mimes = ['application/x-httpd-php', 'text/x-php'];
    if (in_array($mime_type, $dangerous_mimes)) {
        return ['valid' => false, 'error' => 'Dangerous file type'];
    }
    
    return ['valid' => true, 'extension' => $extension, 'mime' => $mime_type];
}

// ============================================
// تشفير وفك تشفير البيانات
// ============================================

/**
 * تشفير بيانات حساسة
 */
function encryptData($data, $key = null) {
    if ($key === null) {
        $key = getenv('APP_SECRET_KEY') ?: 'default-key-change-me';
    }
    
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    
    return base64_encode($iv . $encrypted);
}

/**
 * فك تشفير البيانات
 */
function decryptData($data, $key = null) {
    if ($key === null) {
        $key = getenv('APP_SECRET_KEY') ?: 'default-key-change-me';
    }
    
    $data = base64_decode($data);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
}

// ============================================
// سجل الأمان
// ============================================

/**
 * تسجيل حدث أمني
 */
function logSecurityEvent($event_type, $details = '', $severity = 'info') {
    global $pdo;
    
    $user_id = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $full_details = json_encode([
        'event' => $event_type,
        'details' => $details,
        'severity' => $severity,
        'user_agent' => $user_agent,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, 'security_' . $event_type, $full_details, $ip]);
}

// ============================================
// حماية متقدمة للموقع
// ============================================

/**
 * التحقق من الهجمات المعروفة
 */
function detectAttack($input) {
    $patterns = [
        // SQL Injection
        '/(\bunion\b.*\bselect\b)|(\bselect\b.*\bfrom\b)|(\binsert\b.*\binto\b)|(\bdelete\b.*\bfrom\b)|(\bupdate\b.*\bset\b)/i',
        // XSS
        '/<script[^>]*>|<\/script>|javascript:|on\w+\s*=/i',
        // Path Traversal
        '/\.\.\//i',
        // Command Injection
        '/[;&|`$]|\b(cat|ls|wget|curl|bash|sh|nc|netcat)\b/i',
        // LFI/RFI
        '/(file|php|data|expect|input|filter):\/\//i',
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $input)) {
            return true;
        }
    }
    return false;
}

/**
 * التحقق من جميع المدخلات
 */
function validateAllInputs() {
    $all_inputs = array_merge($_GET, $_POST);
    
    foreach ($all_inputs as $key => $value) {
        if (is_string($value) && detectAttack($value)) {
            // تسجيل الهجوم
            logSecurityEvent('attack_detected', json_encode([
                'type' => 'input_attack',
                'parameter' => $key,
                'value' => substr($value, 0, 200),
                'method' => $_SERVER['REQUEST_METHOD']
            ]), 'critical');
            
            return false;
        }
    }
    return true;
}

/**
 * حماية الهيدرات
 */
function setSecurityHeaders() {
    if (!headers_sent()) {
        // منع Clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // منع XSS
        header('X-XSS-Protection: 1; mode=block');
        
        // منع MIME sniffing
        header('X-Content-Type-Options: nosniff');
        
        // سياسة الإحالة
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // CSP (سياسة أمان المحتوى)
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.gstatic.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'");
    }
}

/**
 * التحقق من IP المحظور
 */
function isIPBlocked($ip = null) {
    global $pdo;
    
    if ($ip === null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    try {
        // التحقق من جدول الحظر إن وجد
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'blocked_ips'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            return false;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM blocked_ips WHERE ip_address = ? AND (expires_at IS NULL OR expires_at > NOW())");
        $stmt->execute([$ip]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * حظر IP
 */
function blockIP($ip, $reason = '', $duration = 86400) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'blocked_ips'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            // إنشاء الجدول إذا لم يكن موجوداً
            $pdo->exec("CREATE TABLE IF NOT EXISTS blocked_ips (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                reason TEXT,
                blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL,
                UNIQUE KEY (ip_address)
            )");
        }
        
        $expires = $duration > 0 ? date('Y-m-d H:i:s', time() + $duration) : null;
        $stmt = $pdo->prepare("INSERT INTO blocked_ips (ip_address, reason, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE reason = ?, expires_at = ?");
        $stmt->execute([$ip, $reason, $expires, $reason, $expires]);
        
        logSecurityEvent('ip_blocked', "IP: $ip, Reason: $reason", 'warning');
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * التحقق الشامل من الأمان
 */
function runSecurityChecks() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // التحقق من الحظر
    if (isIPBlocked($ip)) {
        http_response_code(403);
        die('Access Denied');
    }
    
    // تعيين الهيدرات الأمنية
    setSecurityHeaders();
    
    // التحقق من صحة الجلسة
    if (session_status() === PHP_SESSION_ACTIVE) {
        validateSession();
        checkSessionTimeout();
    }
}

/**
 * تنظيف مخرجات HTML
 */
function cleanOutput($output) {
    return htmlspecialchars($output, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
