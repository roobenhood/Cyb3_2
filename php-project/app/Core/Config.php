<?php

namespace App\Core;

/**
 * فئة Config - مسؤولة عن تحميل وإدارة إعدادات التطبيق
 */
class Config
{
    /**
     * مصفوفة تخزن جميع عناصر الإعدادات
     */
    private static array $items = [];
    
    /**
     * تحميل ملفات الإعدادات من مجلد config/
     */
    public static function load(): void
    {
        self::$items['app'] = require Path::config('app.php');
        self::$items['database'] = require Path::config('database.php');
    }
    
    /**
     * استرجاع قيمة من ملفات الإعدادات باستخدام الترميز النقطي
     */
    public static function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = self::$items;
        
        foreach ($keys as $segment) {
            if (!isset($value[$segment])) {
                return $default;
            }
            $value = $value[$segment];
        }
        
        return $value;
    }
    
    /**
     * تعيين قيمة في الإعدادات
     */
    public static function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $config = &self::$items;
        
        foreach ($keys as $i => $segment) {
            if ($i === count($keys) - 1) {
                $config[$segment] = $value;
            } else {
                if (!isset($config[$segment]) || !is_array($config[$segment])) {
                    $config[$segment] = [];
                }
                $config = &$config[$segment];
            }
        }
    }
}
