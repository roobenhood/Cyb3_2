    <?php
    if (!defined('BASEPATH')) {
        define('BASEPATH', dirname(__DIR__));
    }

    define('DB_HOST', 'localhost');
    define('DB_NAME', 'swiftcart'); 
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');


    define('UPLOAD_DIR', BASEPATH . '/uploads/');
    define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    define('TAX_RATE', 0.15);               // نسبة الضريبة (15%)
    define('SHIPPING_COST', 25.00);         // تكلفة الشحن الثابتة
    define('FREE_SHIPPING_THRESHOLD', 500); // حد الشحن المجاني
    


    define('CURRENCY', 'SAR');
    date_default_timezone_set('Asia/Riyadh');

    define('JWT_SECRET', 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2');

    define('JWT_EXPIRY', 604800);

    error_reporting(E_ALL);
    ini_set('display_errors', 1);


    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    ?>
    