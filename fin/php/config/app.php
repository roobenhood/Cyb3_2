<?php
/**
 * SwiftCart - Application Configuration
 * إعدادات التطبيق العامة
 */

use App\Core\Path;

return [
    // معلومات التطبيق
    'name' => 'SwiftCart',
    'debug' => true,
    'charset' => 'UTF-8',
    'timezone' => 'Asia/Riyadh',
    
    // مسارات التطبيق
    'paths' => [
        'views' => Path::views(),
        'uploads' => Path::root() . '/uploads',
    ],
    
    // إعدادات الرفع
    'upload' => [
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'max_size' => 5 * 1024 * 1024, // 5MB
    ],
    
    // إعدادات المتجر
    'store' => [
        'tax_rate' => 0.15,           // نسبة الضريبة 15%
        'shipping_cost' => 25.00,      // تكلفة الشحن الثابتة
        'free_shipping_threshold' => 500, // حد الشحن المجاني
        'currency' => 'SAR',
    ],
    
    // إعدادات JWT
    'jwt' => [
        'secret' => 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2',
        'expiry' => 604800, // 7 أيام بالثواني
    ],
];
