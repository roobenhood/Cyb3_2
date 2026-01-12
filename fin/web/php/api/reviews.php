<?php
/** Reviews API */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../models/Review.php';

$action = $_GET['action'] ?? 'list';
$reviewModel = new Review();

switch ($action) {
    case 'list':
        $productId = $_GET['product_id'] ?? null;
        if (!$productId) Response::error('معرف المنتج مطلوب');
        $result = $reviewModel->getByProduct($productId, (int)($_GET['page'] ?? 1));
        Response::paginate($result['reviews'], $result['total'], 1, 10);
        break;
    case 'stats':
        $productId = $_GET['product_id'] ?? null;
        if (!$productId) Response::error('معرف المنتج مطلوب');
        Response::success($reviewModel->getStats($productId));
        break;
    case 'create':
        $user = Auth::requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $reviewModel->create($user['id'], $data['product_id'], $data);
        if (!$result['success']) Response::error($result['message']);
        Response::created(null, $result['message']);
        break;
    default:
        Response::error('إجراء غير صالح', [], 400);
}
