<?php
/**
 * SwiftCart - Web Routes
 * مسارات الويب (إذا لزم الأمر في المستقبل)
 */

use App\Core\Router;

$router = new Router();

// الصفحة الرئيسية
$router->get('/', 'HomeController@index');
$router->get('', 'HomeController@index');

return $router;
