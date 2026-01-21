<?php
/**
 * SwiftCart - نقطة الدخول الرئيسية
 * Single Entry Point for the MVC Application
 */

require_once dirname(__DIR__) . '/autoload.php';

use App\Core\Application;

// نقطة دخول واحدة فقط
$app = new Application();
$app->run();
