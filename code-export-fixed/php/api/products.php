<?php
/**
 * Products API Endpoints
 * نقاط نهاية API للمنتجات - المتجر الإلكتروني
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
require_once __DIR__ . '/../models/Product.php';

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        getProducts();
        break;
    case 'featured':
        getFeaturedProducts();
        break;
    case 'new':
        getNewArrivals();
        break;
    case 'get':
        getProduct();
        break;
    case 'search':
        searchProducts();
        break;
    case 'reviews':
        getProductReviews();
        break;
    case 'add-review':
        addProductReview();
        break;
    case 'create':
        createProduct();
        break;
    case 'update':
        updateProduct();
        break;
    case 'delete':
        deleteProduct();
        break;
    default:
        Response::error('إجراء غير صالح', [], 400);
}

/**
 * Get all products with filters
 */
function getProducts() {
    $filters = [
        'page' => (int)($_GET['page'] ?? 1),
        'per_page' => (int)($_GET['per_page'] ?? DEFAULT_PAGE_SIZE),
        'category_id' => $_GET['category_id'] ?? null,
        'min_price' => $_GET['min_price'] ?? null,
        'max_price' => $_GET['max_price'] ?? null,
        'search' => $_GET['search'] ?? null,
        'sort' => $_GET['sort'] ?? 'newest',
        'featured' => $_GET['featured'] ?? null,
        'in_stock' => $_GET['in_stock'] ?? null,
    ];

    try {
        $productModel = new Product();
        $result = $productModel->getAll($filters);
        
        Response::success('تم جلب المنتجات بنجاح', $result['products'], [
            'pagination' => $result['pagination']
        ]);
    } catch (Exception $e) {
        Response::error('فشل في جلب المنتجات', [], 500);
    }
}

/**
 * Get featured products
 */
function getFeaturedProducts() {
    $limit = (int)($_GET['limit'] ?? 8);

    try {
        $productModel = new Product();
        $products = $productModel->getFeatured($limit);
        
        Response::success('تم جلب المنتجات المميزة', $products);
    } catch (Exception $e) {
        Response::error('فشل في جلب المنتجات', [], 500);
    }
}

/**
 * Get new arrivals
 */
function getNewArrivals() {
    $limit = (int)($_GET['limit'] ?? 8);

    try {
        $productModel = new Product();
        $products = $productModel->getNewArrivals($limit);
        
        Response::success('تم جلب المنتجات الجديدة', $products);
    } catch (Exception $e) {
        Response::error('فشل في جلب المنتجات', [], 500);
    }
}

/**
 * Get single product
 */
function getProduct() {
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        Response::error('معرف المنتج مطلوب', [], 400);
        return;
    }

    try {
        $productModel = new Product();
        $product = $productModel->getById($id);

        if (!$product) {
            Response::error('المنتج غير موجود', [], 404);
            return;
        }

        Response::success('تم جلب المنتج', $product);
    } catch (Exception $e) {
        Response::error('فشل في جلب المنتج', [], 500);
    }
}

/**
 * Search products
 */
function searchProducts() {
    $query = $_GET['q'] ?? '';

    if (strlen($query) < 2) {
        Response::error('يجب أن يكون البحث حرفين على الأقل', [], 400);
        return;
    }

    try {
        $productModel = new Product();
        $products = $productModel->search($query);
        
        Response::success('نتائج البحث', $products);
    } catch (Exception $e) {
        Response::error('فشل في البحث', [], 500);
    }
}

/**
 * Get product reviews
 */
function getProductReviews() {
    $productId = (int)($_GET['id'] ?? 0);

    if (!$productId) {
        Response::error('معرف المنتج مطلوب', [], 400);
        return;
    }

    try {
        $productModel = new Product();
        $reviews = $productModel->getReviews($productId);
        
        Response::success('تم جلب التقييمات', $reviews);
    } catch (Exception $e) {
        Response::error('فشل في جلب التقييمات', [], 500);
    }
}

/**
 * Add product review
 */
function addProductReview() {
    $user = Auth::requireAuth();
    if (!$user) return;

    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $validator->required('product_id', 'معرف المنتج مطلوب');
    $validator->required('rating', 'التقييم مطلوب');
    $validator->between('rating', 1, 5, 'التقييم يجب أن يكون بين 1 و 5');

    if (!$validator->isValid()) {
        Response::error('بيانات غير صالحة', $validator->getErrors(), 422);
        return;
    }

    try {
        $productModel = new Product();
        
        // Check if user already reviewed this product
        if ($productModel->hasReviewed($user['id'], $data['product_id'])) {
            Response::error('لقد قمت بتقييم هذا المنتج مسبقاً', [], 400);
            return;
        }

        $reviewId = $productModel->addReview(
            $user['id'],
            $data['product_id'],
            $data['rating'],
            $data['comment'] ?? ''
        );

        Response::success('تم إضافة التقييم بنجاح', ['id' => $reviewId], [], 201);
    } catch (Exception $e) {
        Response::error('فشل في إضافة التقييم', [], 500);
    }
}

/**
 * Create product (Admin only)
 */
function createProduct() {
    $user = Auth::requireAuth();
    if (!$user) return;

    if ($user['role'] !== 'admin') {
        Response::error('غير مصرح', [], 403);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $validator->required('name', 'اسم المنتج مطلوب');
    $validator->required('description', 'وصف المنتج مطلوب');
    $validator->required('category_id', 'الفئة مطلوبة');
    $validator->required('price', 'السعر مطلوب');
    $validator->numeric('price', 'السعر يجب أن يكون رقماً');

    if (!$validator->isValid()) {
        Response::error('بيانات غير صالحة', $validator->getErrors(), 422);
        return;
    }

    try {
        $productModel = new Product();
        $productId = $productModel->create($data);
        $product = $productModel->getById($productId);

        Response::success('تم إنشاء المنتج بنجاح', $product, [], 201);
    } catch (Exception $e) {
        Response::error('فشل في إنشاء المنتج', [], 500);
    }
}

/**
 * Update product (Admin only)
 */
function updateProduct() {
    $user = Auth::requireAuth();
    if (!$user) return;

    if ($user['role'] !== 'admin') {
        Response::error('غير مصرح', [], 403);
        return;
    }

    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        Response::error('معرف المنتج مطلوب', [], 400);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    try {
        $productModel = new Product();
        $productModel->update($id, $data);
        $product = $productModel->getById($id);

        Response::success('تم تحديث المنتج بنجاح', $product);
    } catch (Exception $e) {
        Response::error('فشل في تحديث المنتج', [], 500);
    }
}

/**
 * Delete product (Admin only)
 */
function deleteProduct() {
    $user = Auth::requireAuth();
    if (!$user) return;

    if ($user['role'] !== 'admin') {
        Response::error('غير مصرح', [], 403);
        return;
    }

    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        Response::error('معرف المنتج مطلوب', [], 400);
        return;
    }

    try {
        $productModel = new Product();
        $productModel->delete($id);

        Response::success('تم حذف المنتج بنجاح');
    } catch (Exception $e) {
        Response::error('فشل في حذف المنتج', [], 500);
    }
}
