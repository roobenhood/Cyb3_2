<?php

namespace App\Core;

/**
 * فئة Path - مسؤولة عن إدارة المسارات والروابط في المشروع
 */
class Path
{
    /**
     * الحصول على المسار الجذري للمشروع
     */
    public static function root(): string
    {
        return dirname(__DIR__, 2);
    }
    
    /**
     * الحصول على مسار مجلد الإعدادات (config)
     */
    public static function config(string $file = ''): string
    {
        return self::root() . '/config' . ($file ? '/' . $file : '');
    }
    
    /**
     * الحصول على مسار مجلد العروض (views)
     */
    public static function views(string $file = ''): string
    {
        return self::root() . '/app/Views' . ($file ? '/' . $file : '');
    }
    
    /**
     * الحصول على مسار المجلد العام (public)
     */
    public static function public(string $file = ''): string
    {
        return self::root() . '/public' . ($file ? '/' . $file : '');
    }
    
    /**
     * الحصول على مسار مجلد المسارات (routes)
     */
    public static function routes(string $file = ''): string
    {
        return self::root() . '/routes' . ($file ? '/' . $file : '');
    }
    
    /**
     * الحصول على مسار مجلد الرفع (uploads)
     */
    public static function uploads(string $file = ''): string
    {
        return self::root() . '/uploads' . ($file ? '/' . $file : '');
    }
    
    /**
     * إنشاء رابط المشروع الأساسي (Base URL) ديناميكياً
     */
    public static function baseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = dirname($scriptName);
        
        $projectFolder = preg_replace('#/public$#', '', $scriptDir);
        
        if ($projectFolder === '/' || $projectFolder === '\\') {
            $projectFolder = '';
        }
        
        return rtrim($protocol . '://' . $host . $projectFolder, '/');
    }
    
    /**
     * توليد رابط كامل لأي مسار داخل المشروع
     */
    public static function url(string $path = ''): string
    {
        $baseUrl = self::baseUrl();
        $path = ltrim($path, '/');
        return $baseUrl . ($path ? '/' . $path : '');
    }
}
