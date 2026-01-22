<?php

namespace App\Core;

/**
 * فئة ErrorHandler - معالج الأخطاء والاستثناءات
 */
class ErrorHandler
{
    /**
     * تسجيل معالج الأخطاء
     */
    public static function register(): void
    {
        if (Config::get('app.debug')) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(0);
            ini_set('display_errors', 0);
        }
        
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
    }
    
    /**
     * معالجة الاستثناءات
     */
    public static function handleException(\Throwable $e): void
    {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        
        if (Config::get('app.debug')) {
            echo json_encode([
                'success' => false,
                'message' => 'خطأ في الخادم',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'خطأ في الخادم'
            ], JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }
    
    /**
     * معالجة الأخطاء
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
}
