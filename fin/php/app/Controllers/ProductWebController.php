<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * ProductWebController - عرض صفحات المنتجات على الويب
 */
class ProductWebController extends Controller
{
    /**
     * صفحة قائمة المنتجات
     */
    public function index(): void
    {
        $this->view('products/index');
    }
    
    /**
     * صفحة تفاصيل المنتج
     */
    public function show(string $id): void
    {
        $this->view('products/show', ['product_id' => $id]);
    }
}
