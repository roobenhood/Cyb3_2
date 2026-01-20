<?php
/**
 * نظام التحميل التلقائي للكلاسات
 * Autoloader for SwiftCart MVC
 */

spl_autoload_register(function ($className) {
    // تحويل namespace إلى مسار ملف
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    
    // البحث عن الملف في المجلدات المختلفة
    $directories = [
        __DIR__ . '/app/',
        __DIR__ . '/',
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
