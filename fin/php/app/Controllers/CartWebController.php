<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * CartWebController - صفحة سلة التسوق على الويب
 */
class CartWebController extends Controller
{
    /**
     * صفحة سلة التسوق
     */
    public function index(): void
    {
        $this->view('cart/index');
    }
    
    /**
     * صفحة إتمام الطلب
     */
    public function checkout(): void
    {
        $this->view('cart/checkout');
    }
}
