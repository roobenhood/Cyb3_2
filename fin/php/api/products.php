<?php
/**
 * Products API Endpoints
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../models/Product.php';

$action = $_GET['action'] ?? 'list';
$productModel = new Product();

switch ($action) {
    case 'list':
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 12);
        $filters = [
            'category_id' => $_GET['category_id'] ?? null,
            'search' => $_GET['search'] ?? null,
            'min_price' => $_GET['min_price'] ?? null,
            'max_price' => $_GET['max_price'] ?? null,
            'sort' => $_GET['sort'] ?? null,
            'in_stock' => $_GET['in_stock'] ?? null,
        ];
        $result = $productModel->getAll($page, $perPage, $filters);
        Response::paginate($result['products'], $result['total'], $page, $perPage);
        break;

    case 'featured':
        $products = $productModel->getFeatured((int)($_GET['limit'] ?? 8));
        Response::success($products);
        break;

    case 'get':
        $id = $_GET['id'] ?? null;
        if (!$id) Response::error('معرف المنتج مطلوب', [], 400);
        $product = $productModel->findById($id);
        if (!$product) Response::notFound('المنتج غير موجود');
        $productModel->incrementViews($id);
        $product['related'] = $productModel->getRelated($id, $product['category_id']);
        Response::success($product);
        break;

    default:
        Response::error('إجراء غير صالح', [], 400);
}
