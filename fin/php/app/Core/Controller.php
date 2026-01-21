<?php

namespace App\Core;

/**
 * فئة Controller الأساسية
 * توفر وظائف مشتركة لجميع controllers
 */
class Controller
{
    /**
     * تحميل model
     */
    protected function model(string $model)
    {
        $modelClass = "App\\Models\\{$model}";
        
        if (class_exists($modelClass)) {
            return new $modelClass();
        }
        
        throw new \Exception("النموذج '{$model}' غير موجود");
    }
    
    /**
     * تحميل View
     */
    protected function view(string $view, array $data = []): void
    {
        // تحويل عناصر المصفوفة إلى متغيرات
        extract($data);

        $viewPath = __DIR__ . "/../Views/{$view}.php";

        if (!file_exists($viewPath)) {
            throw new \Exception("ملف العرض '{$view}.php' غير موجود");
        }

        require $viewPath;
    }
    
    /**
     * الحصول على بيانات الطلب JSON
     */
    protected function getRequestData(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);
        return $data ?? [];
    }
    
    /**
     * الحصول على معامل GET
     */
    protected function getParam(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }
    
    /**
     * الحصول على الـ action من الطلب
     */
    protected function getAction(): string
    {
        return $_GET['action'] ?? 'list';
    }
}
