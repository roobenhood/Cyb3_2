<?php
/**
 * API Response Helper
 * مساعد استجابة API
 */

class Response {
    public static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function success($data = null, $message = 'تمت العملية بنجاح', $statusCode = 200) {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    public static function error($message = 'حدث خطأ', $errors = [], $statusCode = 400) {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }

    public static function notFound($message = 'العنصر غير موجود') {
        self::error($message, [], 404);
    }

    public static function unauthorized($message = 'غير مصرح') {
        self::error($message, [], 401);
    }

    public static function forbidden($message = 'ممنوع الوصول') {
        self::error($message, [], 403);
    }

    public static function validationError($errors) {
        self::error('خطأ في البيانات المدخلة', $errors, 422);
    }

    public static function serverError($message = 'خطأ في الخادم') {
        self::error($message, [], 500);
    }

    public static function paginated($data, $total, $page, $perPage) {
        $totalPages = ceil($total / $perPage);
        
        self::json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ]
        ]);
    }
}
