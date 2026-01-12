<?php
/**
 * Orders API Endpoints
 * نقاط نهاية API للطلبات - المتجر الإلكتروني
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
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Cart.php';

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        getOrders();
        break;
    case 'get':
        getOrder();
        break;
    case 'create':
        createOrder();
        break;
    case 'cancel':
        cancelOrder();
        break;
    case 'update-status':
        updateOrderStatus();
        break;
    default:
        Response::error('إجراء غير صالح', [], 400);
}

/**
 * Get user orders
 */
function getOrders() {
    $user = Auth::requireAuth();
    if (!$user) return;

    try {
        $orderModel = new Order();
        $orders = $orderModel->getByUserId($user['id']);
        
        Response::success('تم جلب الطلبات', $orders);
    } catch (Exception $e) {
        Response::error('فشل في جلب الطلبات', [], 500);
    }
}

/**
 * Get single order
 */
function getOrder() {
    $user = Auth::requireAuth();
    if (!$user) return;

    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        Response::error('معرف الطلب مطلوب', [], 400);
        return;
    }

    try {
        $orderModel = new Order();
        $order = $orderModel->getById($id);

        if (!$order) {
            Response::error('الطلب غير موجود', [], 404);
            return;
        }

        // Check if order belongs to user
        if ($order['user_id'] !== $user['id'] && $user['role'] !== 'admin') {
            Response::error('غير مصرح', [], 403);
            return;
        }

        Response::success('تم جلب الطلب', $order);
    } catch (Exception $e) {
        Response::error('فشل في جلب الطلب', [], 500);
    }
}

/**
 * Create new order
 */
function createOrder() {
    $user = Auth::requireAuth();
    if (!$user) return;

    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $validator->required('shipping_address', 'عنوان الشحن مطلوب');
    $validator->required('payment_method', 'طريقة الدفع مطلوبة');

    if (!$validator->isValid()) {
        Response::error('بيانات غير صالحة', $validator->getErrors(), 422);
        return;
    }

    try {
        // Get cart items
        $cartModel = new Cart();
        $cart = $cartModel->getByUserId($user['id']);

        if (empty($cart['items'])) {
            Response::error('السلة فارغة', [], 400);
            return;
        }

        // Create order
        $orderModel = new Order();
        $orderData = [
            'user_id' => $user['id'],
            'items' => $cart['items'],
            'subtotal' => $cart['subtotal'],
            'shipping' => $cart['subtotal'] >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST,
            'tax' => $cart['subtotal'] * TAX_RATE,
            'shipping_address' => $data['shipping_address'],
            'billing_address' => $data['billing_address'] ?? $data['shipping_address'],
            'payment_method' => $data['payment_method'],
            'notes' => $data['notes'] ?? null
        ];

        $orderData['total'] = $orderData['subtotal'] + $orderData['shipping'] + $orderData['tax'];

        $orderId = $orderModel->create($orderData);
        $order = $orderModel->getById($orderId);

        // Clear cart after successful order
        $cartModel->clear($user['id']);

        Response::success('تم إنشاء الطلب بنجاح', $order, [], 201);
    } catch (Exception $e) {
        Response::error('فشل في إنشاء الطلب', [], 500);
    }
}

/**
 * Cancel order
 */
function cancelOrder() {
    $user = Auth::requireAuth();
    if (!$user) return;

    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        Response::error('معرف الطلب مطلوب', [], 400);
        return;
    }

    try {
        $orderModel = new Order();
        $order = $orderModel->getById($id);

        if (!$order) {
            Response::error('الطلب غير موجود', [], 404);
            return;
        }

        // Check if order belongs to user
        if ($order['user_id'] !== $user['id'] && $user['role'] !== 'admin') {
            Response::error('غير مصرح', [], 403);
            return;
        }

        // Check if order can be cancelled
        if (!in_array($order['status'], ['pending', 'processing'])) {
            Response::error('لا يمكن إلغاء هذا الطلب', [], 400);
            return;
        }

        $orderModel->updateStatus($id, 'cancelled');
        $order = $orderModel->getById($id);

        Response::success('تم إلغاء الطلب', $order);
    } catch (Exception $e) {
        Response::error('فشل في إلغاء الطلب', [], 500);
    }
}

/**
 * Update order status (Admin only)
 */
function updateOrderStatus() {
    $user = Auth::requireAuth();
    if (!$user) return;

    if ($user['role'] !== 'admin') {
        Response::error('غير مصرح', [], 403);
        return;
    }

    $id = (int)($_GET['id'] ?? 0);
    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $validator->required('status', 'الحالة مطلوبة');
    $validator->in('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'], 'حالة غير صالحة');

    if (!$validator->isValid()) {
        Response::error('بيانات غير صالحة', $validator->getErrors(), 422);
        return;
    }

    try {
        $orderModel = new Order();
        $orderModel->updateStatus($id, $data['status']);
        $order = $orderModel->getById($id);

        Response::success('تم تحديث حالة الطلب', $order);
    } catch (Exception $e) {
        Response::error('فشل في تحديث الحالة', [], 500);
    }
}
