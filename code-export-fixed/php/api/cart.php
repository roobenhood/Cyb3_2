<?php
/**
 * Cart API Endpoints
 * نقاط نهاية API لسلة التسوق
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/config.php';
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
    case 'remove':
        removeFromCart();
        break;
    case 'clear':
        clearCart();
        break;
    case 'checkout':
        checkout();
        break;
    default:
        Response::error('إجراء غير صالح', [], 400);
}

function getCart() {
    $userId = Auth::requireAuth();

    $cart = new Cart();
    $items = $cart->getByUser($userId);
    $total = $cart->getTotal($userId);
    $count = $cart->getCount($userId);

    Response::success([
        'items' => $items,
        'total' => (float)$total,
        'count' => (int)$count
    ]);
}

function addToCart() {
    $userId = Auth::requireAuth();
    $data = json_decode(file_get_contents('php://input'), true);

    $courseId = (int)($data['course_id'] ?? 0);
    
    if (!$courseId) {
        Response::error('معرف الدورة مطلوب', [], 400);
    }

    $cart = new Cart();

    // Check if already in cart
    if ($cart->isInCart($userId, $courseId)) {
        Response::error('الدورة موجودة بالفعل في السلة', [], 400);
    }

    // Check if already enrolled
    require_once __DIR__ . '/../models/Enrollment.php';
    $enrollment = new Enrollment();
    if ($enrollment->isEnrolled($userId, $courseId)) {
        Response::error('أنت مسجل بالفعل في هذه الدورة', [], 400);
    }

    if ($cart->add($userId, $courseId)) {
        Response::success([
            'count' => $cart->getCount($userId)
        ], 'تمت إضافة الدورة إلى السلة');
    } else {
        Response::serverError('فشل إضافة الدورة إلى السلة');
    }
}

function removeFromCart() {
    $userId = Auth::requireAuth();
    $courseId = (int)($_GET['course_id'] ?? 0);
    
    if (!$courseId) {
        Response::error('معرف الدورة مطلوب', [], 400);
    }

    $cart = new Cart();

    if ($cart->remove($userId, $courseId)) {
        Response::success([
            'count' => $cart->getCount($userId)
        ], 'تمت إزالة الدورة من السلة');
    } else {
        Response::serverError('فشل إزالة الدورة من السلة');
    }
}

function clearCart() {
    $userId = Auth::requireAuth();

    $cart = new Cart();

    if ($cart->clear($userId)) {
        Response::success(null, 'تم تفريغ السلة');
    } else {
        Response::serverError('فشل تفريغ السلة');
    }
}

function checkout() {
    $userId = Auth::requireAuth();

    $cart = new Cart();
    $items = $cart->getByUser($userId);

    if (empty($items)) {
        Response::error('السلة فارغة', [], 400);
    }

    // Here you would integrate with payment gateway
    // For now, we'll just create enrollments

    if ($cart->checkout($userId)) {
        Response::success(null, 'تمت عملية الشراء بنجاح');
    } else {
        Response::serverError('فشلت عملية الشراء');
    }
}
