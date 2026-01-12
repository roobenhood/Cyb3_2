<?php
/** Categories API */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../models/Category.php';

$action = $_GET['action'] ?? 'list';
$categoryModel = new Category();

switch ($action) {
    case 'list':
        $categories = $categoryModel->getWithProductCount();
        Response::success($categories);
        break;
    case 'get':
        $id = $_GET['id'] ?? null;
        if (!$id) Response::error('معرف التصنيف مطلوب', [], 400);
        $category = $categoryModel->findById($id);
        if (!$category) Response::notFound('التصنيف غير موجود');
        Response::success($category);
        break;
    default:
        Response::error('إجراء غير صالح', [], 400);
}
