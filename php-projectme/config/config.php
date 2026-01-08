<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
define('SITE_NAME', 'منصة الكورسات التعليمية');
define('SITE_URL', 'http://localhost/php-projectme');

define('SESSION_LIFETIME', 1800);      
define('SESSION_REGENERATE_TIME', 300);  
define('SESSION_NAME', 'COURSE_SID');

define('HASH_COST', 12);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900);  


define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');

define('COURSE_PUBLISHED', 1);
define('COURSE_DRAFT', 0);
define('USER_ACTIVE', 1);
define('USER_INACTIVE', 0);

define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

date_default_timezone_set('Asia/Riyadh');


class Database
{
    private static ?PDO $instance = null;
    private const HOST = 'localhost';
    private const DB_NAME = 'courses';
    private const USERNAME = 'root';
    private const PASSWORD = '';
    private const CHARSET = 'utf8mb4';

    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=%s',
                    self::HOST,
                    self::DB_NAME,
                    self::CHARSET
                );

                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];

                self::$instance = new PDO($dsn, self::USERNAME, self::PASSWORD, $options);

            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    throw new PDOException('خطأ في الاتصال: ' . $e->getMessage());
                }
                throw new PDOException('خطأ في الاتصال بقاعدة البيانات');
            }
        }

        return self::$instance;
    }

    
    public static function closeConnection(): void
    {
        self::$instance = null;
    }
}

spl_autoload_register(function ($class) {
    $paths = [
        BASE_PATH . '/models/',
        BASE_PATH . '/controllers/',
        BASE_PATH . '/core/',
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
