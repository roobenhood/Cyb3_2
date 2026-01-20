<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;

class DashboardController extends Controller
{
    public function __construct()
    {
        AuthMiddleware::admin();
    }

    /**
     * لوحة التحكم الرئيسية
     */
    public function index(): void
    {
        $this->view('dashboard/index', [
            'currentPage' => 'dashboard'
        ]);
    }

    /**
     * إدارة الطلبات
     */
    public function orders(): void
    {
        $this->view('dashboard/orders', [
            'currentPage' => 'orders'
        ]);
    }

    /**
     * إدارة المنتجات
     */
    public function products(): void
    {
        $this->view('dashboard/products', [
            'currentPage' => 'products'
        ]);
    }

    /**
     * إدارة التصنيفات
     */
    public function categories(): void
    {
        $this->view('dashboard/categories', [
            'currentPage' => 'categories'
        ]);
    }

    /**
     * إدارة المستخدمين
     */
    public function users(): void
    {
        $this->view('dashboard/users', [
            'currentPage' => 'users'
        ]);
    }

    /**
     * الإعدادات
     */
    public function settings(): void
    {
        $this->view('dashboard/settings', [
            'currentPage' => 'settings'
        ]);
    }

    /**
     * التقارير
     */
    public function reports(): void
    {
        $this->view('dashboard/reports', [
            'currentPage' => 'reports'
        ]);
    }
}
