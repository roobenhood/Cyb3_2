<?php
/**
 * Favorites API Endpoints
 * نقاط نهاية API للمفضلة - المتجر الإلكتروني
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../models/Favorite.php';

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        getFavorites();
        break;
    case 'add':
        addToFavorites();
        break;
    case 'remove':
        removeFromFavorites();
        break;
    case 'check':
        checkFavorite();
        break;
    default:
        Response::error('إجراء غير صالح', [], 400);
}

/**
 * Get user favorites
 */
function getFavorites() {
    $user = Auth::requireAuth();
    if (!$user) return;

    try {
        $favoriteModel = new Favorite();
        $favorites = $favoriteModel->getByUserId($user['id']);
        
        Response::success('تم جلب المفضلة', $favorites);
    } catch (Exception $e) {
        Response::error('فشل في جلب المفضلة', [], 500);
    }
}

/**
 * Add product to favorites
 */
function addToFavorites() {
    $user = Auth::requireAuth();
    if (!$user) return;

    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $validator->required('product_id', 'معرف المنتج مطلوب');

    if (!$validator->isValid()) {
        Response::error('بيانات غير صالحة', $validator->getErrors(), 422);
        return;
    }

    try {
        $favoriteModel = new Favorite();
        
        // Check if already in favorites
        if ($favoriteModel->exists($user['id'], $data['product_id'])) {
            Response::error('المنتج موجود في المفضلة بالفعل', [], 400);
            return;
        }

        $favoriteModel->add($user['id'], $data['product_id']);
        $favorites = $favoriteModel->getByUserId($user['id']);

        Response::success('تمت إضافة المنتج إلى المفضلة', $favorites, [], 201);
    } catch (Exception $e) {
        Response::error('فشل في إضافة المنتج للمفضلة', [], 500);
    }
}

/**
 * Remove product from favorites
 */
function removeFromFavorites() {
    $user = Auth::requireAuth();
    if (!$user) return;

    $productId = (int)($_GET['product_id'] ?? 0);

    if (!$productId) {
        Response::error('معرف المنتج مطلوب', [], 400);
        return;
    }

    try {
        $favoriteModel = new Favorite();
        $favoriteModel->remove($user['id'], $productId);
        $favorites = $favoriteModel->getByUserId($user['id']);

        Response::success('تمت إزالة المنتج من المفضلة', $favorites);
    } catch (Exception $e) {
        Response::error('فشل في إزالة المنتج', [], 500);
    }
}

/**
 * Check if product is in favorites
 */
function checkFavorite() {
    $user = Auth::requireAuth();
    if (!$user) return;

    $productId = (int)($_GET['product_id'] ?? 0);

    if (!$productId) {
        Response::error('معرف المنتج مطلوب', [], 400);
        return;
    }

    try {
        $favoriteModel = new Favorite();
        $exists = $favoriteModel->exists($user['id'], $productId);

        Response::success('تم التحقق', ['is_favorite' => $exists]);
    } catch (Exception $e) {
        Response::error('فشل في التحقق', [], 500);
    }
}
