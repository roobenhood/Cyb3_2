<?php
/**
 * Application Configuration
 * إعدادات التطبيق - المتجر الإلكتروني
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce_store');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// JWT Configuration
define('JWT_SECRET', 'your-secret-key-change-in-production');
define('JWT_EXPIRY', 86400 * 7); // 7 days

// App Configuration
define('DEFAULT_PAGE_SIZE', 12);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Pricing Configuration
define('CURRENCY', 'SAR');
define('TAX_RATE', 0.15); // 15% VAT
define('SHIPPING_COST', 25.00);
define('FREE_SHIPPING_THRESHOLD', 500.00);

// Database Connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}
