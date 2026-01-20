<?php

namespace App\Middleware;

use App\Helpers\Session;

class AuthMiddleware
{
    /**
     * التحقق من تسجيل الدخول للويب
     */
    public static function handle(): bool
    {
        Session::start();
        
        if (!Session::isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        
        return true;
    }

    /**
     * التحقق من أن المستخدم غير مسجل (للصفحات العامة)
     */
    public static function guest(): bool
    {
        Session::start();
        
        if (Session::isLoggedIn()) {
            header('Location: /dashboard');
            exit;
        }
        
        return true;
    }

    /**
     * التحقق من صلاحيات الأدمن
     */
    public static function admin(): bool
    {
        self::handle();
        
        $user = Session::user();
        if (!$user || ($user['role'] ?? 'user') !== 'admin') {
            header('Location: /unauthorized');
            exit;
        }
        
        return true;
    }
}
