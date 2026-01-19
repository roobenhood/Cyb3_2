<?php
/**
 * AlwaniCTF Configuration File
 * ملف إعدادات المنصة
 */

// منع الوصول المباشر
if (!defined('CYBERCTF')) {
    define('CYBERCTF', true);
}

// تحميل ملف البيئة إن وجد
if (file_exists(__DIR__ . '/env.php')) {
    require_once __DIR__ . '/env.php';
}

// إعدادات قاعدة البيانات - يجب تخزينها في ملف env أو خارج الكود
// ⚠️ تحذير: لا تضع بيانات الدخول مباشرة في الكود في بيئة الإنتاج
define('DB_HOST', 'sql100.infinityfree.com');
define('DB_NAME', 'if0_40590141_myproject');
define('DB_USER', 'if0_40590141');
define('DB_PASS', 'ouTwARRb4kSuLTF');
define('DB_CHARSET', 'utf8mb4');

// إعدادات الموقع
define('SITE_NAME', 'AlwaniCTF');
define('SITE_URL', getenv('SITE_URL') ?: 'https://alwnai.page.gd');
define('SITE_VERSION', '2.0.0');

// المسارات الأساسية
define('ROOT_PATH', dirname(__FILE__) . '/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('LANGUAGES_PATH', ROOT_PATH . 'languages/');
define('CHALLENGES_PATH', ROOT_PATH . 'challenges_data/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');

// إعدادات الجلسة والأمان
define('SESSION_NAME', 'CYBERCTF_SESSION');
define('SESSION_LIFETIME', 7200);
define('CSRF_TOKEN_NAME', 'csrf_token');

// إعدادات الأمان
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);
define('HASH_COST', 12);

// إعدادات رفع الملفات
define('MAX_FILE_SIZE', 50 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['zip', 'tar', 'gz', 'txt', 'pdf', 'png', 'jpg', 'jpeg', 'py', 'c', 'cpp', 'elf', 'exe']);

// بدء الجلسة بإعدادات آمنة
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_name(SESSION_NAME);
    session_start();
}

// تعيين المنطقة الزمنية
date_default_timezone_set('Asia/Riyadh');

// الاتصال بقاعدة البيانات
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die(' الخدمه متوقفه مؤقتا   ');
}

// تحميل ملف الأمان
if (file_exists(__DIR__ . '/security.php')) {
    require_once __DIR__ . '/security.php';
    
    // تشغيل فحوصات الأمان التلقائية
    runSecurityChecks();
}

// معالجة تبديل اللغة عبر الـ GET
if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'en'])) {
    $_SESSION['language'] = $_GET['lang'];
    setcookie('language', $_GET['lang'], time() + (365 * 24 * 60 * 60), '/');

    // إعادة توجيه لنفس الصفحة بدون ?lang لضمان تطبيق اللغة ومنع مشاكل الكاش
    $query = $_GET;
    unset($query['lang']);

    $redirect = $_SERVER['PHP_SELF'];
    if (!empty($query)) {
        $redirect .= '?' . http_build_query($query);
    }

    header('Location: ' . $redirect);
    exit;
}

// تحميل ملف اللغة
function loadLanguage($lang = null) {
    if ($lang === null) {
        if (isset($_SESSION['language'])) {
            $lang = $_SESSION['language'];
        } elseif (isset($_COOKIE['language'])) {
            $lang = $_COOKIE['language'];
        } else {
            $lang = 'ar';
        }
    }
    
    $lang = in_array($lang, ['ar', 'en']) ? $lang : 'ar';
    $langFile = LANGUAGES_PATH . $lang . '.php';
    
    if (file_exists($langFile)) {
        return include $langFile;
    }
    
    return include LANGUAGES_PATH . 'ar.php';
}

// الحصول على النص المترجم
function __($key, $lang = null) {
    static $strings = null;
    
    if ($strings === null) {
        $strings = loadLanguage($lang);
    }
    
    return $strings[$key] ?? $key;
}

// تبديل اللغة
function setLanguage($lang) {
    $lang = in_array($lang, ['ar', 'en']) ? $lang : 'ar';
    $_SESSION['language'] = $lang;
    setcookie('language', $lang, time() + (365 * 24 * 60 * 60), '/');
    
    if (isLoggedIn()) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE users SET language = ? WHERE id = ?");
        $stmt->execute([$lang, $_SESSION['user_id']]);
    }
}

// الحصول على اللغة الحالية
function getCurrentLanguage() {
    if (isset($_SESSION['language'])) {
        return $_SESSION['language'];
    }
    if (isset($_COOKIE['language'])) {
        return $_COOKIE['language'];
    }
    return 'ar';
}

// توليد CSRF Token
function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// التحقق من CSRF Token
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// تنظيف المدخلات
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// التحقق من تسجيل الدخول
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// التحقق من صلاحيات الأدمن - يتحقق من قاعدة البيانات
function isAdmin() {
    if (!isLoggedIn()) {
        return false;
    }
    
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user && in_array($user['role'], ['admin', 'super_admin'])) {
            return true;
        }
    } catch (Exception $e) {
        return false;
    }
    
    return false;
}

// تطلب تسجيل الدخول
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
    
    // التحقق من تأكيد البريد إذا كان الخيار مفعلاً
    $emailVerificationRequired = getSetting('email_verification_required', '0') === '1';
    $forceLogoutUnverified = getSetting('force_logout_unverified', '0') === '1';
    
    if ($emailVerificationRequired && $forceLogoutUnverified) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT email, email_verified FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user && !$user['email_verified']) {
            // حفظ بيانات التحقق وتسجيل الخروج
            $_SESSION['pending_verification_user_id'] = $_SESSION['user_id'];
            $_SESSION['pending_verification_email'] = $user['email'];
            
            // مسح جلسة الدخول
            unset($_SESSION['user_id']);
            unset($_SESSION['username']);
            unset($_SESSION['role']);
            unset($_SESSION['logged_in']);
            unset($_SESSION['is_admin']);
            
            flashMessage('warning', __('email_not_verified'));
            header('Location: ' . SITE_URL . '/verify_email.php');
            exit;
        }
    }
}

// تطلب صلاحيات الأدمن
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('HTTP/1.1 403 Forbidden');
        die(__('access_denied'));
    }
}

// الحصول على معلومات المستخدم الحالي
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// تسجيل النشاط
function logActivity($action, $details = '', $userId = null) {
    global $pdo;
    
    if ($userId === null && isLoggedIn()) {
        $userId = $_SESSION['user_id'];
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $details, $ip]);
}

// إنشاء مجلد التحدي
function createChallengeFolder($folderName) {
    $path = CHALLENGES_PATH . $folderName;
    
    if (!file_exists(CHALLENGES_PATH)) {
        mkdir(CHALLENGES_PATH, 0755, true);
    }
    
    if (!file_exists($path)) {
        mkdir($path, 0755, true);
        mkdir($path . '/files', 0755, true);
        file_put_contents($path . '/.htaccess', "Options -Indexes\n");
        return true;
    }
    
    return false;
}

// حذف مجلد التحدي
function deleteChallengeFolder($folderName) {
    $path = CHALLENGES_PATH . $folderName;
    
    if (file_exists($path)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        
        rmdir($path);
        return true;
    }
    
    return false;
}

// الحصول على ملفات التحدي
function getChallengeFiles($folderName) {
    $path = CHALLENGES_PATH . $folderName . '/files';
    $files = [];
    
    if (file_exists($path)) {
        $items = scandir($path);
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..' && $item !== '.htaccess') {
                $files[] = [
                    'name' => $item,
                    'size' => filesize($path . '/' . $item),
                    'path' => $path . '/' . $item
                ];
            }
        }
    }
    
    return $files;
}

// تنسيق حجم الملف
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// الحصول على إعداد معين
function getSetting($key, $default = '') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}

// تحديث إعداد
function updateSetting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                          ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$key, $value, $value]);
}

// رسالة فلاش
function flashMessage($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ============================================
// وظائف نظام التحقق و OTP
// ============================================

// توليد رمز OTP
function generateOTP($length = 6) {
    return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

// إنشاء وحفظ رمز OTP
function createOTP($userId, $type = 'email_verify') {
    global $pdo;
    
    // حذف الرموز القديمة من نفس النوع
    $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE user_id = ? AND type = ?");
    $stmt->execute([$userId, $type]);
    
    // إنشاء رمز جديد
    $code = generateOTP();
    $expiryMinutes = (int)getSetting('otp_expiry_minutes', 10);
    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryMinutes} minutes"));
    
    $stmt = $pdo->prepare("INSERT INTO otp_codes (user_id, code, type, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $code, $type, $expiresAt]);
    
    return $code;
}

// التحقق من رمز OTP
function verifyOTP($userId, $code, $type = 'email_verify') {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM otp_codes 
        WHERE user_id = ? AND code = ? AND type = ? 
        AND expires_at > NOW() AND is_used = 0
    ");
    $stmt->execute([$userId, $code, $type]);
    $otp = $stmt->fetch();
    
    if ($otp) {
        // تحديث الرمز كمستخدم
        $stmt = $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE id = ?");
        $stmt->execute([$otp['id']]);
        return true;
    }
    
    return false;
}

// الحصول على هاش الجهاز
function getDeviceHash() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // نستخدم الـ IP + User Agent لإنشاء هاش فريد للجهاز
    return hash('sha256', $ip . $userAgent . $acceptLang);
}

// التحقق من الجهاز الموثوق
function isTrustedDevice($userId) {
    global $pdo;
    
    $deviceHash = getDeviceHash();
    
    $stmt = $pdo->prepare("
        SELECT * FROM trusted_devices 
        WHERE user_id = ? AND device_hash = ?
    ");
    $stmt->execute([$userId, $deviceHash]);
    
    return $stmt->fetch() !== false;
}

// إضافة جهاز موثوق
function addTrustedDevice($userId) {
    global $pdo;
    
    $deviceHash = getDeviceHash();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // تحديد اسم الجهاز من User Agent
    $deviceName = 'Unknown Device';
    if (preg_match('/Windows/i', $userAgent)) {
        $deviceName = 'Windows PC';
    } elseif (preg_match('/Mac/i', $userAgent)) {
        $deviceName = 'Mac';
    } elseif (preg_match('/Linux/i', $userAgent)) {
        $deviceName = 'Linux';
    } elseif (preg_match('/Android/i', $userAgent)) {
        $deviceName = 'Android Device';
    } elseif (preg_match('/iPhone|iPad/i', $userAgent)) {
        $deviceName = 'iOS Device';
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO trusted_devices (user_id, device_hash, ip_address, user_agent, device_name) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE last_used = NOW(), ip_address = ?
    ");
    $stmt->execute([$userId, $deviceHash, $ipAddress, $userAgent, $deviceName, $ipAddress]);
}

// إرسال بريد إلكتروني
function sendEmail($to, $subject, $body) {
    $smtpHost = getSetting('smtp_host', '');
    $smtpPort = getSetting('smtp_port', '587');
    $smtpUser = getSetting('smtp_username', '');
    $smtpPass = getSetting('smtp_password', '');
    $fromEmail = getSetting('smtp_from_email', '');
    $fromName = getSetting('smtp_from_name', 'AlwaniCTF');
    
    // إذا لم يتم إعداد SMTP، استخدم mail() العادية
    if (empty($smtpHost) || empty($smtpUser)) {
        $headers = "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "Reply-To: {$fromEmail}\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        
        return @mail($to, $subject, $body, $headers);
    }
    
    // استخدام SMTP
    try {
        $socket = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 30);
        if (!$socket) {
            error_log("SMTP Connection failed: $errstr ($errno)");
            return false;
        }
        
        // قراءة الرد الأولي
        fgets($socket);
        
        // EHLO
        fputs($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        fgets($socket);
        
        // STARTTLS إذا كان المنفذ 587
        if ($smtpPort == 587) {
            fputs($socket, "STARTTLS\r\n");
            fgets($socket);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            fputs($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
            fgets($socket);
        }
        
        // المصادقة
        fputs($socket, "AUTH LOGIN\r\n");
        fgets($socket);
        fputs($socket, base64_encode($smtpUser) . "\r\n");
        fgets($socket);
        fputs($socket, base64_encode($smtpPass) . "\r\n");
        fgets($socket);
        
        // MAIL FROM
        fputs($socket, "MAIL FROM:<{$fromEmail}>\r\n");
        fgets($socket);
        
        // RCPT TO
        fputs($socket, "RCPT TO:<{$to}>\r\n");
        fgets($socket);
        
        // DATA
        fputs($socket, "DATA\r\n");
        fgets($socket);
        
        // محتوى الرسالة
        $message = "From: {$fromName} <{$fromEmail}>\r\n";
        $message .= "To: {$to}\r\n";
        $message .= "Subject: {$subject}\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "\r\n";
        $message .= $body;
        $message .= "\r\n.\r\n";
        
        fputs($socket, $message);
        fgets($socket);
        
        // QUIT
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        return true;
    } catch (Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        return false;
    }
}

// إرسال رمز OTP بالبريد
function sendOTPEmail($email, $code, $type = 'email_verify') {
    $lang = getCurrentLanguage();
    
    if ($type === 'email_verify') {
        $subject = $lang === 'ar' ? 'تأكيد بريدك الإلكتروني - AlwaniCTF' : 'Verify Your Email - AlwaniCTF';
        $title = $lang === 'ar' ? 'مرحباً بك في AlwaniCTF!' : 'Welcome to AlwaniCTF!';
        $message = $lang === 'ar' 
            ? 'استخدم الرمز التالي لتأكيد بريدك الإلكتروني:' 
            : 'Use the following code to verify your email:';
    } elseif ($type === 'login_verify') {
        $subject = $lang === 'ar' ? 'رمز التحقق للدخول - AlwaniCTF' : 'Login Verification Code - AlwaniCTF';
        $title = $lang === 'ar' ? 'تسجيل دخول من جهاز جديد' : 'Login from New Device';
        $message = $lang === 'ar' 
            ? 'اكتشفنا محاولة تسجيل دخول من جهاز جديد. استخدم الرمز التالي للتحقق:' 
            : 'We detected a login attempt from a new device. Use this code to verify:';
    } else {
        $subject = $lang === 'ar' ? 'إعادة تعيين كلمة المرور - AlwaniCTF' : 'Password Reset - AlwaniCTF';
        $title = $lang === 'ar' ? 'إعادة تعيين كلمة المرور' : 'Password Reset';
        $message = $lang === 'ar' 
            ? 'استخدم الرمز التالي لإعادة تعيين كلمة المرور:' 
            : 'Use the following code to reset your password:';
    }
    
    $expiryText = $lang === 'ar' ? 'هذا الرمز صالح لمدة 10 دقائق' : 'This code is valid for 10 minutes';
    $ignoreText = $lang === 'ar' 
        ? 'إذا لم تطلب هذا الرمز، يرجى تجاهل هذا البريد.' 
        : 'If you did not request this code, please ignore this email.';
    
    $body = "
    <!DOCTYPE html>
    <html dir='" . ($lang === 'ar' ? 'rtl' : 'ltr') . "'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; background: #0a0a0f; color: #e0e0e0; margin: 0; padding: 20px; }
            .container { max-width: 500px; margin: 0 auto; background: linear-gradient(145deg, #1a1a2e, #16213e); border-radius: 15px; padding: 30px; border: 1px solid #00ff88; }
            .logo { text-align: center; font-size: 28px; font-weight: bold; color: #00ff88; margin-bottom: 20px; }
            .title { font-size: 22px; color: #fff; margin-bottom: 15px; text-align: center; }
            .message { color: #b0b0b0; margin-bottom: 25px; text-align: center; }
            .otp-box { background: #0d0d1a; border: 2px dashed #00ff88; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0; }
            .otp-code { font-size: 36px; font-weight: bold; color: #00ff88; letter-spacing: 8px; font-family: monospace; }
            .expiry { color: #ff6b6b; font-size: 14px; text-align: center; margin: 15px 0; }
            .ignore { color: #666; font-size: 12px; text-align: center; margin-top: 20px; }
            .footer { text-align: center; margin-top: 25px; padding-top: 15px; border-top: 1px solid #333; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='logo'>🛡️ AlwaniCTF</div>
            <div class='title'>{$title}</div>
            <div class='message'>{$message}</div>
            <div class='otp-box'>
                <div class='otp-code'>{$code}</div>
            </div>
            <div class='expiry'>⏱️ {$expiryText}</div>
            <div class='ignore'>{$ignoreText}</div>
            <div class='footer'>© " . date('Y') . " AlwaniCTF - Capture The Flag Platform</div>
        </div>
    </body>
    </html>";
    
    return sendEmail($email, $subject, $body);
}

// البحث عن مستخدم بالاسم أو البريد
function findUserByIdentifier($identifier) {
    global $pdo;
    
    // التحقق إذا كان بريد إلكتروني
    if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    }
    
    $stmt->execute([$identifier]);
    return $stmt->fetch();
}

// تحقق من البريد الإلكتروني
function markEmailAsVerified($userId) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, email_verified_at = NOW() WHERE id = ?");
    return $stmt->execute([$userId]);
}

// التحقق مما إذا كان البريد مؤكد
function isEmailVerified($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT email_verified FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result && $result['email_verified'] == 1;
}
