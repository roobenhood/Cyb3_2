<?php

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    /**
     * عرض الصفحة الرئيسية
     */
    public function index(): void
    {
        $this->view('home/index');
    }

    /**
     * صفحة من نحن
     */
    public function about(): void
    {
        $this->view('pages/about');
    }

    /**
     * صفحة اتصل بنا
     */
    public function contact(): void
    {
        $this->view('pages/contact');
    }

    /**
     * صفحة الشروط والأحكام
     */
    public function terms(): void
    {
        $this->view('pages/terms');
    }

    /**
     * صفحة سياسة الخصوصية
     */
    public function privacy(): void
    {
        $this->view('pages/privacy');
    }
}
