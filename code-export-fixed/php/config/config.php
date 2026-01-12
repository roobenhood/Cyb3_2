<?php
/**
 * Application Configuration
 * إعدادات التطبيق
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

// Timezone
date_default_timezone_set('Asia/Baghdad');

// Application settings
define('APP_NAME', 'منصة الدورات التعليمية');
define('APP_URL', 'http://localhost/courses-platform');
define('APP_VERSION', '1.0.0');

// Upload settings
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/ogg']);

// JWT settings
define('JWT_SECRET', 'your-secret-key-change-in-production');
define('JWT_EXPIRY', 86400 * 7); // 7 days

// API settings
define('API_RATE_LIMIT', 100); // requests per minute
define('API_VERSION', 'v1');

// Pagination
define('DEFAULT_PAGE_SIZE', 10);
define('MAX_PAGE_SIZE', 100);
