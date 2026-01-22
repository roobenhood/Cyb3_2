<?php
/**
 * SwiftCart - Web Routes
 * مسارات الويب للمتجر
 */

use App\Core\Router;

$router = new Router();

// الصفحة الرئيسية
$router->get('/', 'HomeController@index');
$router->get('', 'HomeController@index');

// المنتجات
$router->get('products', 'ProductWebController@index');
$router->get('products/{id}', 'ProductWebController@show');

// السلة
$router->get('cart', 'CartWebController@index');
$router->get('checkout', 'CartWebController@checkout');

// المصادقة
$router->get('login', 'WebAuthController@loginForm');
$router->post('login', 'WebAuthController@login');
$router->get('register', 'WebAuthController@registerForm');
$router->post('register', 'WebAuthController@register');
$router->get('logout', 'WebAuthController@logout');

// لوحة التحكم
$router->get('dashboard', 'DashboardController@index');
$router->get('dashboard/orders', 'DashboardController@orders');
$router->get('dashboard/products', 'DashboardController@products');
$router->get('dashboard/categories', 'DashboardController@categories');
$router->get('dashboard/users', 'DashboardController@users');
$router->get('dashboard/settings', 'DashboardController@settings');

// صفحات ثابتة
$router->get('about', 'HomeController@about');
$router->get('contact', 'HomeController@contact');
$router->get('terms', 'HomeController@terms');
$router->get('privacy', 'HomeController@privacy');

return $router;
