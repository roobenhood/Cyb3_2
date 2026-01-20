<?php

namespace App\Core;

/**
 * فئة Kernel - النواة الرئيسية للتطبيق
 * مسؤولة عن معالجة جميع الطلبات الواردة وتوجيهها
 */
class Kernel
{
    /**
     * معالجة الطلب الوارد
     */
    public function handle(): void
    {
        // تعيين المنطقة الزمنية
        date_default_timezone_set(Config::get('app.timezone', 'Asia/Riyadh'));
        
        // إعداد رؤوس CORS
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        header('Content-Type: application/json; charset=utf-8');
        
        // معالجة طلبات Preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        // استخراج الـ URI من الطلب
        $uri = $_GET['url'] ?? '';
        $uri = trim(filter_var($uri, FILTER_SANITIZE_URL), '/');
        
        // توجيه الطلب إلى الراوتر المناسب
        if (str_starts_with($uri, 'api/') || str_starts_with($uri, 'api\\')) {
            $router = require Path::routes('api.php');
        } else {
            $router = require Path::routes('web.php');
        }
        
        // توجيه الطلب
        $router->direct($uri, $_SERVER['REQUEST_METHOD']);
    }
}
