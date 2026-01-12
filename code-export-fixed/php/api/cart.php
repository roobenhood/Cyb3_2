<?php
/**
 * Cart API Endpoints
 * نقاط نهاية API للسلة - المتجر الإلكتروني
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
require_once __DIR__ . '/../models/Cart.php';

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        getCart();
        break;
    case 'add':
        addToCart();
        break;
    case 'update':
        updateCartItem();
        break;
    case 'remove':
        removeFromCart();
        break;
    case 'clear':
        clearCart();
        break;
    default:
        Response::error('إجراء غير صالح', [], 400);
}

/**
 * Get cart items
 */
function getCart() {
    $user = Auth::requireAuth();
    if (!$user) return;

    try {
        $cartModel = new Cart();
        $cart = $cartModel->getByUserId($user['id']);
        
        Response::success('تم جلب السلة', $cart);
    } catch (Exception $e) {
        Response::error('فشل في جلب السلة', [], 500);
    }
}

/**
 * Add item to cart
 */
function addToCart() {
    $user = Auth::requireAuth();
    if (!$user) return;

    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $validator->required('product_id', 'معرف المنتج مطلوب');

    if (!$validator->isValid()) {
        Response::error('بيانات غير صالحة', $validator->getErrors(), 422);
        return;
    }

    $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;

    try {
        $cartModel = new Cart();
        
        // Check if product is in stock
        if (!$cartModel->checkStock($data['product_id'], $quantity)) {
            Response::error('الكمية المطلوبة غير متوفرة', [], 400);
            return;
        }

        $cartModel->addItem($user['id'], $data['product_id'], $quantity);
        $cart = $cartModel->getByUserId($user['id']);

        Response::success('تمت إضافة المنتج إلى السلة', $cart, [], 201);
    } catch (Exception $e) {
        Response::error('فشل في إضافة المنتج', [], 500);
    }
}

/**
 * Update cart item quantity
 */
function updateCartItem() {
    $user = Auth::requireAuth();
    if (!$user) return;

    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $validator->required('product_id', 'معرف المنتج مطلوب');
    $validator->required('quantity', 'الكمية مطلوبة');
    $validator->numeric('quantity', 'الكمية يجب أن تكون رقماً');
    $validator->min('quantity', 1, 'الكمية يجب أن تكون 1 على الأقل');

    if (!$validator->isValid()) {
        Response::error('بيانات غير صالحة', $validator->getErrors(), 422);
        return;
    }

    try {
        $cartModel = new Cart();
        
        // Check if product is in stock
        if (!$cartModel->checkStock($data['product_id'], $data['quantity'])) {
            Response::error('الكمية المطلوبة غير متوفرة', [], 400);
            return;
        }

        $cartModel->updateQuantity($user['id'], $data['product_id'], $data['quantity']);
        $cart = $cartModel->getByUserId($user['id']);

        Response::success('تم تحديث الكمية', $cart);
    } catch (Exception $e) {
        Response::error('فشل في تحديث الكمية', [], 500);
    }
}

/**
 * Remove item from cart
 */
function removeFromCart() {
    $user = Auth::requireAuth();
    if (!$user) return;

    $productId = (int)($_GET['product_id'] ?? 0);

    if (!$productId) {
        Response::error('معرف المنتج مطلوب', [], 400);
        return;
    }

    try {
        $cartModel = new Cart();
        $cartModel->removeItem($user['id'], $productId);
        $cart = $cartModel->getByUserId($user['id']);

        Response::success('تمت إزالة المنتج من السلة', $cart);
    } catch (Exception $e) {
        Response::error('فشل في إزالة المنتج', [], 500);
    }
}

/**
 * Clear cart
 */
function clearCart() {
    $user = Auth::requireAuth();
    if (!$user) return;

    try {
        $cartModel = new Cart();
        $cartModel->clear($user['id']);

        Response::success('تم إفراغ السلة');
    } catch (Exception $e) {
        Response::error('فشل في إفراغ السلة', [], 500);
    }
}
