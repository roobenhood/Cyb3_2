<?php

namespace App\Middleware;

use App\Helpers\Session;

class CsrfMiddleware
{
    /**
     * التحقق من CSRF token
     */
    public static function handle(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            
            if (!Session::verifyCsrfToken($token)) {
                http_response_code(403);
                die('CSRF token mismatch');
            }
        }
        
        return true;
    }

    /**
     * إنشاء حقل hidden للـ CSRF
     */
    public static function field(): string
    {
        $token = Session::generateCsrfToken();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token) . '">';
    }
}
