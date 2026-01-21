<?php
/**
 * SwiftCart - API Routes
 * مسارات API - متوافقة مع تطبيق Flutter
 * 
 * ملاحظة: جميع المسارات تحافظ على التوافق مع الـ endpoints الأصلية
 */

use App\Core\Router;

$router = new Router();

/**
 * Auth Routes - مسارات المصادقة
 * الملف الأصلي: api/auth.php
 */
$router->post('api/auth.php', 'AuthController@handle');
$router->get('api/auth.php', 'AuthController@handle');

/**
 * Products Routes - مسارات المنتجات
 * الملف الأصلي: api/products.php
 */
$router->get('api/products.php', 'ProductController@handle');
$router->post('api/products.php', 'ProductController@handle');

/**
 * Categories Routes - مسارات التصنيفات
 * الملف الأصلي: api/categories.php
 */
$router->get('api/categories.php', 'CategoryController@handle');

/**
 * Cart Routes - مسارات السلة
 * الملف الأصلي: api/cart.php
 */
$router->get('api/cart.php', 'CartController@handle');
$router->post('api/cart.php', 'CartController@handle');

/**
 * Orders Routes - مسارات الطلبات
 * الملف الأصلي: api/orders.php
 */
$router->get('api/orders.php', 'OrderController@handle');
$router->post('api/orders.php', 'OrderController@handle');

/**
 * Reviews Routes - مسارات التقييمات
 * الملف الأصلي: api/reviews.php
 */
$router->get('api/reviews.php', 'ReviewController@handle');
$router->post('api/reviews.php', 'ReviewController@handle');

return $router;
