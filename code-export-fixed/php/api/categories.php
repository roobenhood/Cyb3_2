<?php
/**
 * Categories API Endpoints
 * نقاط نهاية API للفئات - المتجر الإلكتروني
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../models/Category.php';

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        getCategories();
        break;
    case 'get':
        getCategory();
        break;
    case 'create':
        createCategory();
        break;
    case 'update':
        updateCategory();
        break;
    case 'delete':
        deleteCategory();
        break;
    default:
        Response::error('إجراء غير صالح', [], 400);
}

/**
 * Get all categories
 */
function getCategories() {
    try {
        $categoryModel = new Category();
        $categories = $categoryModel->getAll();
        
        Response::success('تم جلب الفئات بنجاح', $categories);
    } catch (Exception $e) {
        Response::error('فشل في جلب الفئات', [], 500);
    }
}

/**
 * Get single category
 */
function getCategory() {
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        Response::error('معرف الفئة مطلوب', [], 400);
        return;
    }

    try {
        $categoryModel = new Category();
        $category = $categoryModel->getById($id);

        if (!$category) {
            Response::error('الفئة غير موجودة', [], 404);
            return;
        }

        Response::success('تم جلب الفئة', $category);
    } catch (Exception $e) {
        Response::error('فشل في جلب الفئة', [], 500);
    }
}

/**
 * Create category (Admin only)
 */
function createCategory() {
    $user = Auth::requireAuth();
    if (!$user) return;

    if ($user['role'] !== 'admin') {
        Response::error('غير مصرح', [], 403);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $validator->required('name', 'اسم الفئة مطلوب');

    if (!$validator->isValid()) {
        Response::error('بيانات غير صالحة', $validator->getErrors(), 422);
        return;
    }

    try {
        $categoryModel = new Category();
        $categoryId = $categoryModel->create($data);
        $category = $categoryModel->getById($categoryId);

        Response::success('تم إنشاء الفئة بنجاح', $category, [], 201);
    } catch (Exception $e) {
        Response::error('فشل في إنشاء الفئة', [], 500);
    }
}

/**
 * Update category (Admin only)
 */
function updateCategory() {
    $user = Auth::requireAuth();
    if (!$user) return;

    if ($user['role'] !== 'admin') {
        Response::error('غير مصرح', [], 403);
        return;
    }

    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        Response::error('معرف الفئة مطلوب', [], 400);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    try {
        $categoryModel = new Category();
        $categoryModel->update($id, $data);
        $category = $categoryModel->getById($id);

        Response::success('تم تحديث الفئة بنجاح', $category);
    } catch (Exception $e) {
        Response::error('فشل في تحديث الفئة', [], 500);
    }
}

/**
 * Delete category (Admin only)
 */
function deleteCategory() {
    $user = Auth::requireAuth();
    if (!$user) return;

    if ($user['role'] !== 'admin') {
        Response::error('غير مصرح', [], 403);
        return;
    }

    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        Response::error('معرف الفئة مطلوب', [], 400);
        return;
    }

    try {
        $categoryModel = new Category();
        $categoryModel->delete($id);

        Response::success('تم حذف الفئة بنجاح');
    } catch (Exception $e) {
        Response::error('فشل في حذف الفئة', [], 500);
    }
}
