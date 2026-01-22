<?php

namespace App\Core;

/**
 * فئة Router - نظام التوجيه
 */
class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'OPTIONS' => [],
    ];
    
    /**
     * إضافة مسار GET
     */
    public function get(string $uri, string $controller): void
    {
        $this->routes['GET'][$uri] = $controller;
    }
    
    /**
     * إضافة مسار POST
     */
    public function post(string $uri, string $controller): void
    {
        $this->routes['POST'][$uri] = $controller;
    }
    
    /**
     * إضافة مسار PUT
     */
    public function put(string $uri, string $controller): void
    {
        $this->routes['PUT'][$uri] = $controller;
    }
    
    /**
     * إضافة مسار DELETE
     */
    public function delete(string $uri, string $controller): void
    {
        $this->routes['DELETE'][$uri] = $controller;
    }
    
    /**
     * تحميل ملف المسارات
     */
    public static function load(string $file): Router
    {
        $router = new static();
        require $file;
        return $router;
    }
    
    /**
     * توجيه الطلب
     */
    public function direct(string $uri, string $requestType)
    {
        $uri = explode('?', $uri)[0];
        $uri = trim($uri, '/');
        
        if ($uri === '') {
            $uri = '/';
        }
        
        $requestType = strtoupper($requestType);
        
        if (!isset($this->routes[$requestType])) {
            $this->notFound("نوع الطلب غير مدعوم: {$requestType}");
        }
        
        // مسار ثابت
        if (array_key_exists($uri, $this->routes[$requestType])) {
            return $this->callAction(
                ...explode('@', $this->routes[$requestType][$uri])
            );
        }
        
        // مسارات ديناميكية {id}
        foreach ($this->routes[$requestType] as $route => $controllerAction) {
            $pattern = preg_replace('/\{[a-zA-Z_]+\}/', '([^/]+)', $route);
            $pattern = "#^{$pattern}$#";
            
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                return $this->callAction(
                    ...array_merge(
                        explode('@', $controllerAction),
                        $matches
                    )
                );
            }
        }
        
        $this->notFound();
    }
    
    /**
     * استدعاء الدالة في المتحكم
     */
    protected function callAction(string $controller, string $action, ...$params)
    {
        $controllerClass = "App\\Controllers\\{$controller}";
        
        if (!class_exists($controllerClass)) {
            $this->notFound("المتحكم '{$controllerClass}' غير موجود");
        }
        
        $instance = new $controllerClass();
        
        if (!method_exists($instance, $action)) {
            $this->notFound("الدالة '{$action}' غير موجودة في المتحكم");
        }
        
        return $instance->$action(...$params);
    }
    
    /**
     * صفحة 404
     */
    protected function notFound(string $message = 'الصفحة غير موجودة'): void
    {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        
        echo json_encode([
            'success' => false,
            'status' => 404,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        
        exit;
    }
    
    /**
     * جلب جميع المسارات (Debug)
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
