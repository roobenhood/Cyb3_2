<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\ApiResponse; use App\Models\Product;

class ProductController extends Controller {
    public function handle(): void {
        $action = $this->getAction();
        match($action) { 'list' => $this->list(), 'featured' => $this->featured(), 'get' => $this->get(), default => ApiResponse::error('إجراء غير صالح', [], 400) };
    }
    private function list(): void { $page = (int)$this->getParam('page', 1); $perPage = (int)$this->getParam('per_page', 12); $filters = ['category_id' => $this->getParam('category_id'), 'search' => $this->getParam('search'), 'min_price' => $this->getParam('min_price'), 'max_price' => $this->getParam('max_price'), 'sort' => $this->getParam('sort')]; $result = $this->model('Product')->getAll($page, $perPage, $filters); ApiResponse::paginate($result['products'], $result['total'], $page, $perPage); }
    private function featured(): void { ApiResponse::success($this->model('Product')->getFeatured((int)$this->getParam('limit', 8))); }
    private function get(): void { $id = $this->getParam('id'); if (!$id) ApiResponse::error('معرف المنتج مطلوب', [], 400); $product = $this->model('Product')->findById($id); if (!$product) ApiResponse::notFound('المنتج غير موجود'); $this->model('Product')->incrementViews($id); $product['related'] = $this->model('Product')->getRelated($id, $product['category_id']); ApiResponse::success($product); }
}
