<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Session;
use App\Helpers\Validator;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;

class WebAuthController extends Controller
{
    /**
     * عرض صفحة تسجيل الدخول
     */
    public function showLogin(): void
    {
        AuthMiddleware::guest();
        $this->view('auth/login');
    }

    /**
     * معالجة تسجيل الدخول
     */
    public function login(): void
    {
        CsrfMiddleware::handle();
        
        $email = Validator::sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $this->view('auth/login', [
                'error' => 'يرجى ملء جميع الحقول',
                'old' => ['email' => $email]
            ]);
            return;
        }
        
        $userModel = $this->model('User');
        $user = $userModel->findByEmail($email);
        
        if (!$user || !password_verify($password, $user['password'])) {
            $this->view('auth/login', [
                'error' => 'بيانات الدخول غير صحيحة',
                'old' => ['email' => $email]
            ]);
            return;
        }
        
        // تسجيل الدخول
        Session::login($user);
        Session::flash('success', 'مرحباً بك ' . $user['name']);
        
        // إعادة التوجيه
        $redirect = $user['role'] === 'admin' ? '/dashboard' : '/';
        header('Location: ' . $redirect);
        exit;
    }

    /**
     * عرض صفحة التسجيل
     */
    public function showRegister(): void
    {
        AuthMiddleware::guest();
        $this->view('auth/register');
    }

    /**
     * معالجة التسجيل
     */
    public function register(): void
    {
        CsrfMiddleware::handle();
        
        $data = [
            'name' => Validator::sanitize($_POST['name'] ?? ''),
            'email' => Validator::sanitize($_POST['email'] ?? ''),
            'phone' => Validator::sanitize($_POST['phone'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirmation' => $_POST['password_confirmation'] ?? ''
        ];
        
        $errors = [];
        
        // التحقق من البيانات
        if (empty($data['name'])) {
            $errors[] = 'الاسم مطلوب';
        }
        
        if (!Validator::isEmail($data['email'])) {
            $errors[] = 'البريد الإلكتروني غير صالح';
        }
        
        if (!Validator::isPhone($data['phone'])) {
            $errors[] = 'رقم الهاتف غير صالح';
        }
        
        if (strlen($data['password']) < 8) {
            $errors[] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
        }
        
        if ($data['password'] !== $data['password_confirmation']) {
            $errors[] = 'كلمتا المرور غير متطابقتين';
        }
        
        // التحقق من وجود البريد
        $userModel = $this->model('User');
        if ($userModel->findByEmail($data['email'])) {
            $errors[] = 'البريد الإلكتروني مستخدم مسبقاً';
        }
        
        if (!empty($errors)) {
            $this->view('auth/register', [
                'errors' => $errors,
                'old' => $data
            ]);
            return;
        }
        
        // إنشاء الحساب
        $result = $userModel->create($data);
        
        if ($result['success']) {
            Session::flash('success', 'تم إنشاء الحساب بنجاح. يمكنك الآن تسجيل الدخول.');
            header('Location: /login');
        } else {
            $this->view('auth/register', [
                'errors' => [$result['message']],
                'old' => $data
            ]);
        }
    }

    /**
     * تسجيل الخروج
     */
    public function logout(): void
    {
        CsrfMiddleware::handle();
        Session::logout();
        Session::flash('success', 'تم تسجيل الخروج بنجاح');
        header('Location: /login');
        exit;
    }
}
