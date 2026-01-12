<?php
/** Orders API */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../models/Order.php';

$action = $_GET['action'] ?? 'list';
$user = Auth::requireAuth();
$orderModel = new Order();

switch ($action) {
    case 'list':
        $page = (int)($_GET['page'] ?? 1);
        $result = $orderModel->getByUser($user['id'], $page);
        Response::paginate($result['orders'], $result['total'], $page, 10);
        break;
    case 'get':
        $id = $_GET['id'] ?? null;
        $order = $orderModel->findById($id);
        if (!$order || $order['user_id'] != $user['id']) Response::notFound('الطلب غير موجود');
        Response::success($order);
        break;
    case 'create':
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $orderModel->create($user['id'], $data);
        if (!$result['success']) Response::error($result['message'] ?? 'فشل إنشاء الطلب', $result['errors'] ?? []);
        Response::created($result['order'], 'تم إنشاء الطلب بنجاح');
        break;
    case 'cancel':
        $id = $_GET['id'] ?? null;
        $result = $orderModel->cancel($id, $user['id']);
        if (!$result['success']) Response::error($result['message']);
        Response::success($result['order'], 'تم إلغاء الطلب');
        break;
    default:
        Response::error('إجراء غير صالح', [], 400);
}
