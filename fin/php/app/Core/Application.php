<?php

namespace App\Core;

/**
 * فئة Application - نقطة الدخول الرئيسية للتطبيق
 */
class Application
{
    protected Kernel $kernel;
    
    public function __construct()
    {
        $this->bootstrap();
        $this->kernel = new Kernel();
    }
    
    /**
     * تهيئة وإعداد المكونات الأساسية للتطبيق
     */
    protected function bootstrap(): void
    {
        // تحميل الإعدادات
        Config::load();
        
        // تسجيل معالج الأخطاء
        ErrorHandler::register();
    }
    
    /**
     * تشغيل التطبيق
     */
    public function run(): void
    {
        $this->kernel->handle();
    }
}
