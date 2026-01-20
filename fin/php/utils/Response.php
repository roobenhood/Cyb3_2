<?php
/**
 * Response Helper
 * مساعد الاستجابة
 */

class Response {
    /**
     * Send success response
     */
    public static function success($data = null, $message = 'تم بنجاح', $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send error response
     */
    public static function error($message = 'حدث خطأ', $errors = [], $statusCode = 400) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send paginated response
     */
    public static function paginate($data, $total, $page, $perPage, $message = 'تم بنجاح') {
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
     * Send created response
     */
    public static function created($data = null, $message = 'تم الإنشاء بنجاح') {
        self::success($data, $message, 201);
    }

    /**
     * Send not found response
     */
    public static function notFound($message = 'غير موجود') {
        self::error($message, [], 404);
    }

    /**
     * Send unauthorized response
     */
    public static function unauthorized($message = 'غير مصرح') {
        self::error($message, [], 401);
    }

    /**
     * Send forbidden response
     */
    public static function forbidden($message = 'غير مسموح') {
        self::error($message, [], 403);
    }

    /**
     * Send validation error response
     */
    public static function validationError($message = 'بيانات غير صالحة', $errors = []) {
        self::error($message, $errors, 422);
    }

    /**
     * Send server error response
     */
    public static function serverError($message = 'خطأ في الخادم') {
        self::error($message, [], 500);
    }

    /**
     * Send raw JSON response
     */
    public static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
