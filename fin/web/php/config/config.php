<?php
/**
 * Application Configuration
 * إعدادات التطبيق - المتجر الإلكتروني
 */

// Prevent direct access
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__));
}

// Environment
define('ENVIRONMENT', 'development'); // development, production

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce_store');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// JWT Configuration
define('JWT_SECRET', 'your-super-secret-key-change-in-production-2026');
define('JWT_EXPIRY', 86400 * 7); // 7 days

// App Configuration
define('APP_NAME', 'المتجر الإلكتروني');
define('APP_URL', 'http://localhost/ecommerce-store');
define('API_URL', APP_URL . '/api');
define('DEFAULT_PAGE_SIZE', 12);

// Upload Configuration
define('UPLOAD_DIR', BASEPATH . '/uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf']);

// Pricing Configuration
define('CURRENCY', 'SAR');
define('CURRENCY_SYMBOL', 'ر.س');
define('TAX_RATE', 0.15); // 15% VAT
define('SHIPPING_COST', 25.00);
define('FREE_SHIPPING_THRESHOLD', 500.00);
define('MIN_ORDER_VALUE', 50.00);

// Email Configuration
define('MAIL_HOST', 'smtp.example.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'noreply@example.com');
define('MAIL_PASSWORD', 'your-email-password');
define('MAIL_FROM_ADDRESS', 'noreply@example.com');
define('MAIL_FROM_NAME', APP_NAME);

// Error handling based on environment
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('Asia/Riyadh');

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
