<?php
/**
 * Application Configuration
 * إعدادات التطبيق - المتجر الإلكتروني / منصة الدورات
 * 
 * ملف موحد للإعدادات - تم إصلاح التعارضات
 */

// =============================================
// Database Configuration - إعدادات قاعدة البيانات
// =============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce_store');  // اسم موحد لقاعدة البيانات
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// =============================================
// JWT Configuration - إعدادات المصادقة
// =============================================
define('JWT_SECRET', 'your-super-secret-key-change-in-production-123456789');
define('JWT_EXPIRY', 86400 * 7); // 7 days

// =============================================
// App Configuration - إعدادات التطبيق
// =============================================
define('APP_NAME', 'المتجر الإلكتروني');
define('APP_URL', 'http://localhost/ecommerce');
define('API_URL', APP_URL . '/api');
define('DEFAULT_PAGE_SIZE', 12);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// =============================================
// Pricing Configuration - إعدادات الأسعار
// =============================================
define('CURRENCY', 'SAR');
define('CURRENCY_SYMBOL', 'ر.س');
define('TAX_RATE', 0.15); // 15% VAT
define('SHIPPING_COST', 25.00);
define('FREE_SHIPPING_THRESHOLD', 500.00);

// =============================================
// Security Configuration - إعدادات الأمان
// =============================================
define('CORS_ALLOWED_ORIGINS', '*'); // في الإنتاج، حدد النطاقات المسموحة
define('PASSWORD_MIN_LENGTH', 6);
define('SESSION_LIFETIME', 3600 * 24 * 7); // 7 days

// =============================================
// File Upload Configuration - إعدادات رفع الملفات
// =============================================
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024); // 5MB
define('THUMBNAIL_WIDTH', 300);
define('THUMBNAIL_HEIGHT', 300);

// =============================================
// Messages - الرسائل
// =============================================
define('MESSAGES', [
    'LOGIN_SUCCESS' => 'تم تسجيل الدخول بنجاح',
    'LOGIN_ERROR' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة',
    'REGISTER_SUCCESS' => 'تم إنشاء الحساب بنجاح',
    'LOGOUT_SUCCESS' => 'تم تسجيل الخروج بنجاح',
    'CART_ADD_SUCCESS' => 'تمت إضافة المنتج إلى السلة',
    'CART_REMOVE_SUCCESS' => 'تمت إزالة المنتج من السلة',
    'ORDER_SUCCESS' => 'تم إنشاء الطلب بنجاح',
    'ERROR_GENERAL' => 'حدث خطأ، يرجى المحاولة مرة أخرى',
    'UNAUTHORIZED' => 'يرجى تسجيل الدخول أولاً',
    'FORBIDDEN' => 'ليس لديك صلاحية للوصول',
    'NOT_FOUND' => 'العنصر غير موجود',
    'VALIDATION_ERROR' => 'يرجى التحقق من البيانات المدخلة',
]);

// =============================================
// Database Connection - الاتصال بقاعدة البيانات
// =============================================
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // في بيئة التطوير
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        die(json_encode([
            'success' => false, 
            'message' => 'Database connection failed: ' . $e->getMessage()
        ]));
    }
    // في بيئة الإنتاج
    die(json_encode(['success' => false, 'message' => 'خطأ في الاتصال بقاعدة البيانات']));
}

// =============================================
// Helper Functions - دوال مساعدة
// =============================================

/**
 * Get database connection
 */
function getDB() {
    global $pdo;
    return $pdo;
}

/**
 * Generate random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Format price
 */
function formatPrice($price) {
    return number_format($price, 2) . ' ' . CURRENCY_SYMBOL;
}

/**
 * Calculate order total with tax and shipping
 */
function calculateOrderTotal($subtotal) {
    $tax = $subtotal * TAX_RATE;
    $shipping = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
    return [
        'subtotal' => $subtotal,
        'tax' => $tax,
        'shipping' => $shipping,
        'total' => $subtotal + $tax + $shipping,
    ];
}
