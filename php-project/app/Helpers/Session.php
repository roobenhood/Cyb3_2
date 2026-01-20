<?php

namespace App\Helpers;

class Session
{
    /**
     * بدء الجلسة
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * تعيين قيمة في الجلسة
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * الحصول على قيمة من الجلسة
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * التحقق من وجود مفتاح
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * حذف مفتاح من الجلسة
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * مسح الجلسة بالكامل
     */
    public static function destroy(): void
    {
        self::start();
        session_unset();
        session_destroy();
    }

    /**
     * تعيين رسالة flash
     */
    public static function flash(string $key, string $message): void
    {
        self::set('flash_' . $key, $message);
    }

    /**
     * الحصول على رسالة flash وحذفها
     */
    public static function getFlash(string $key): ?string
    {
        $message = self::get('flash_' . $key);
        self::remove('flash_' . $key);
        return $message;
    }

    /**
     * تسجيل دخول المستخدم
     */
    public static function login(array $user): void
    {
        self::set('user_id', $user['id']);
        self::set('user', $user);
        self::set('logged_in', true);
    }

    /**
     * تسجيل خروج المستخدم
     */
    public static function logout(): void
    {
        self::remove('user_id');
        self::remove('user');
        self::remove('logged_in');
        self::destroy();
    }

    /**
     * التحقق من تسجيل الدخول
     */
    public static function isLoggedIn(): bool
    {
        return self::get('logged_in', false) === true;
    }

    /**
     * الحصول على المستخدم الحالي
     */
    public static function user(): ?array
    {
        return self::get('user');
    }

    /**
     * الحصول على معرف المستخدم الحالي
     */
    public static function userId(): ?int
    {
        return self::get('user_id');
    }

    /**
     * إنشاء CSRF token
     */
    public static function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        self::set('csrf_token', $token);
        return $token;
    }

    /**
     * التحقق من CSRF token
     */
    public static function verifyCsrfToken(string $token): bool
    {
        return hash_equals(self::get('csrf_token', ''), $token);
    }
}
