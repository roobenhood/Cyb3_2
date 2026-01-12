<?php
/**
 * Categories API Endpoints
 * نقاط نهاية API للتصنيفات
 */

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

function getCategories() {
    $category = new Category();
    $categories = $category->getAll();
    Response::success($categories);
}

function getCategory() {
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        Response::error('معرف التصنيف مطلوب', [], 400);
    }

    $category = new Category();
    $categoryData = $category->findById($id);

    if (!$categoryData) {
        Response::notFound('التصنيف غير موجود');
    }

    Response::success($categoryData);
}

function createCategory() {
    Auth::requireAuth();

    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $isValid = $validator->validate([
        'name' => 'required|min:2|max:100'
    ]);

    if (!$isValid) {
        Response::validationError($validator->getErrors());
    }

    $category = new Category();
    $id = $category->create(
        Validator::sanitize($data['name']),
        Validator::sanitize($data['description'] ?? ''),
        $data['icon'] ?? null
    );

    if ($id) {
        Response::success(['id' => $id], 'تم إنشاء التصنيف بنجاح', 201);
    } else {
        Response::serverError('فشل إنشاء التصنيف');
    }
}

function updateCategory() {
    Auth::requireAuth();

    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        Response::error('معرف التصنيف مطلوب', [], 400);
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $isValid = $validator->validate([
        'name' => 'min:2|max:100'
    ]);

    if (!$isValid) {
        Response::validationError($validator->getErrors());
    }

    $category = new Category();
    
    if (!$category->findById($id)) {
        Response::notFound('التصنيف غير موجود');
    }

    $updateData = [];
    if (isset($data['name'])) $updateData['name'] = Validator::sanitize($data['name']);
    if (isset($data['description'])) $updateData['description'] = Validator::sanitize($data['description']);
    if (isset($data['icon'])) $updateData['icon'] = $data['icon'];

    if ($category->update($id, $updateData)) {
        Response::success(null, 'تم تحديث التصنيف بنجاح');
    } else {
        Response::serverError('فشل تحديث التصنيف');
    }
}

function deleteCategory() {
    Auth::requireAuth();

    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        Response::error('معرف التصنيف مطلوب', [], 400);
    }

    $category = new Category();
    
    if (!$category->findById($id)) {
        Response::notFound('التصنيف غير موجود');
    }

    if ($category->delete($id)) {
        Response::success(null, 'تم حذف التصنيف بنجاح');
    } else {
        Response::serverError('فشل حذف التصنيف');
    }
}
