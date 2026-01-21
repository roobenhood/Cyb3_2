<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\ApiResponse;
class CategoryController extends Controller { public function handle(): void { $action = $this->getAction(); match($action) { 'list' => ApiResponse::success($this->model('Category')->getWithProductCount()), 'get' => $this->get(), default => ApiResponse::error('إجراء غير صالح', [], 400) }; } private function get(): void { $id = $this->getParam('id'); if (!$id) ApiResponse::error('معرف التصنيف مطلوب', [], 400); $cat = $this->model('Category')->findById($id); if (!$cat) ApiResponse::notFound('التصنيف غير موجود'); ApiResponse::success($cat); } }
