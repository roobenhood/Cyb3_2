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

        // إعداد رؤوس CORS (مسموحة للـ API فقط)
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

        // معالجة طلبات Preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        // استخراج الـ URI
        $uri = $_GET['url'] ?? '';
        $uri = trim(filter_var($uri, FILTER_SANITIZE_URL), '/');

        // تحديد هل الطلب API أم صفحة ويب
        $isApi = str_starts_with($uri, 'api/');

        // اختيار ملف المسارات المناسب
        if ($isApi) {
            header('Content-Type: application/json; charset=utf-8');
            $router = require Path::routes('api.php');
        } else {
            // ❌ لا تضع Content-Type هنا
            $router = require Path::routes('web.php');
        }

        // تنفيذ التوجيه
        $response = $router->direct($uri, $_SERVER['REQUEST_METHOD']);

        // إخراج الاستجابة
        if ($isApi && is_array($response)) {
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } else {
            echo $response;
        }
    }
}
