<?php

namespace App\Middleware;

use App\Helpers\Session;

class RateLimitMiddleware
{
    private static int $maxAttempts = 60;
    private static int $decayMinutes = 1;

    /**
     * التحقق من معدل الطلبات
     */
    public static function handle(): bool
    {
        $key = 'rate_limit_' . self::getClientIp();
        $attempts = Session::get($key, ['count' => 0, 'reset_at' => time()]);
        
        // إعادة تعيين العداد إذا انتهت الفترة
        if (time() > $attempts['reset_at']) {
            $attempts = [
                'count' => 0,
                'reset_at' => time() + (self::$decayMinutes * 60)
            ];
        }
        
        $attempts['count']++;
        Session::set($key, $attempts);
        
        if ($attempts['count'] > self::$maxAttempts) {
            http_response_code(429);
            header('Retry-After: ' . (self::$decayMinutes * 60));
            die(json_encode([
                'success' => false,
                'message' => 'تم تجاوز الحد الأقصى للطلبات. حاول مجدداً لاحقاً.'
            ]));
        }
        
        return true;
    }

    /**
     * الحصول على IP العميل
     */
    private static function getClientIp(): string
    {
        $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                return $_SERVER[$header];
            }
        }
        
        return '0.0.0.0';
    }
}
