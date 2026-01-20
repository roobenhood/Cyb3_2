<?php
/**
 * Cart API
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../models/Cart.php';

$action = $_GET['action'] ?? 'list';
$user = Auth::requireAuth();
$cartModel = new Cart();

switch ($action) {
    case 'list':
        $cart = $cartModel->getByUser($user['id']);
        Response::success($cart);
        break;
    case 'add':
        $data = json_decode(file_get_contents('php://input'), true);
        $cart = $cartModel->addItem($user['id'], $data['product_id'], $data['quantity'] ?? 1, $data['variant_id'] ?? null);
        Response::success($cart, 'تمت الإضافة للسلة');
        break;
    case 'update':
        $data = json_decode(file_get_contents('php://input'), true);
        $cart = $cartModel->updateQuantity($user['id'], $data['item_id'], $data['quantity']);
        Response::success($cart);
        break;
    case 'remove':
        $itemId = $_GET['item_id'] ?? null;
        $cart = $cartModel->removeItem($user['id'], $itemId);
        Response::success($cart, 'تمت الإزالة من السلة');
        break;
    case 'clear':
        $cart = $cartModel->clear($user['id']);
        Response::success($cart, 'تم تفريغ السلة');
        break;
    case 'totals':
        $coupon = $_GET['coupon'] ?? null;
        $totals = $cartModel->calculateTotals($user['id'], $coupon);
        Response::success($totals);
        break;
    default:
        Response::error('إجراء غير صالح', [], 400);
}
