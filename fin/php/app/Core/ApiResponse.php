<?php

namespace App\Core;

/**
 * فئة ApiResponse - توحيد استجابات API
 */
class ApiResponse
{
    /**
     * إرسال استجابة ناجحة
     */
    public static function success($data = null, string $message = 'تم بنجاح', int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * إرسال استجابة خطأ
     */
    public static function error(string $message = 'حدث خطأ', array $errors = [], int $statusCode = 400): void
    {
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * إرسال استجابة مع تصفح الصفحات
     */
    public static function paginate($data, int $total, int $page, int $perPage, string $message = 'تم بنجاح'): void
    {
        $lastPage = ceil($total / $perPage);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'total' => (int)$total,
                'per_page' => (int)$perPage,
                'current_page' => (int)$page,
                'last_page' => (int)$lastPage,
                'has_more' => $page < $lastPage
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * إرسال استجابة إنشاء ناجح
     */
    public static function created($data = null, string $message = 'تم الإنشاء بنجاح'): void
    {
        self::success($data, $message, 201);
    }
    
    /**
     * إرسال استجابة غير موجود
     */
    public static function notFound(string $message = 'غير موجود'): void
    {
        self::error($message, [], 404);
    }
    
    /**
     * إرسال استجابة غير مصرح
     */
    public static function unauthorized(string $message = 'غير مصرح'): void
    {
        self::error($message, [], 401);
    }
    
    /**
     * إرسال استجابة ممنوع
     */
    public static function forbidden(string $message = 'غير مسموح'): void
    {
        self::error($message, [], 403);
    }
    
    /**
     * إرسال استجابة خطأ التحقق
     */
    public static function validationError(string $message = 'بيانات غير صالحة', array $errors = []): void
    {
        self::error($message, $errors, 422);
    }
    
    /**
     * إرسال استجابة خطأ الخادم
     */
    public static function serverError(string $message = 'خطأ في الخادم'): void
    {
        self::error($message, [], 500);
    }
}
