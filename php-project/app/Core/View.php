<?php

namespace App\Core;

class View
{
    private static string $viewsPath = __DIR__ . '/../Views/';

    /**
     * عرض صفحة
     */
    public static function render(string $view, array $data = []): void
    {
        extract($data);
        
        $viewFile = self::$viewsPath . str_replace('.', '/', $view) . '.php';
        
        if (!file_exists($viewFile)) {
            self::renderError(404);
            return;
        }
        
        require $viewFile;
    }

    /**
     * عرض صفحة خطأ
     */
    public static function renderError(int $code): void
    {
        http_response_code($code);
        
        $errorFile = self::$viewsPath . "errors/{$code}.php";
        
        if (file_exists($errorFile)) {
            require $errorFile;
        } else {
            echo "Error {$code}";
        }
    }

    /**
     * إعادة التوجيه
     */
    public static function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * إعادة التوجيه مع رسالة
     */
    public static function redirectWith(string $url, string $type, string $message): void
    {
        \App\Helpers\Session::flash($type, $message);
        self::redirect($url);
    }
}
