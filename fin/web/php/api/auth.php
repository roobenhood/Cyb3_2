<?php
/**
 * Authentication API Endpoints
 * نقاط نهاية API للمصادقة
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../models/User.php';

$action = $_GET['action'] ?? 'login';

switch ($action) {
    case 'login': login(); break;
    case 'register': register(); break;
    case 'profile': getProfile(); break;
    case 'update-profile': updateProfile(); break;
    case 'change-password': changePassword(); break;
    default: Response::error('إجراء غير صالح', [], 400);
}

function login() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $validator = new Validator($data);
    $validator->required('email', 'البريد الإلكتروني مطلوب')
              ->email('email', 'البريد الإلكتروني غير صالح')
              ->required('password', 'كلمة المرور مطلوبة');
    $validator->validate();

    $userModel = new User();
    $user = $userModel->findByEmail($data['email']);

    if (!$user || !$userModel->verifyPassword($user, $data['password'])) {
        Response::error('البريد الإلكتروني أو كلمة المرور غير صحيحة', [], 401);
    }

    if (!$user['is_active']) {
        Response::error('الحساب غير مفعل', [], 403);
    }

    $token = Auth::generateToken($user['id']);
    unset($user['password']);

    Response::success(['token' => $token, 'user' => $user], 'تم تسجيل الدخول بنجاح');
}

function register() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $validator = new Validator($data);
    $validator->required('name', 'الاسم مطلوب')
              ->min('name', 2, 'الاسم يجب أن يكون حرفين على الأقل')
              ->required('email', 'البريد الإلكتروني مطلوب')
              ->email('email', 'البريد الإلكتروني غير صالح')
              ->unique('email', 'users', 'email', null, 'البريد الإلكتروني مستخدم مسبقاً')
              ->required('password', 'كلمة المرور مطلوبة')
              ->min('password', 6, 'كلمة المرور يجب أن تكون 6 أحرف على الأقل')
              ->matches('password', 'password_confirmation', 'كلمة المرور غير متطابقة');
    $validator->validate();

    $userModel = new User();
    $user = $userModel->create($data);
    $token = Auth::generateToken($user['id']);

    Response::created(['token' => $token, 'user' => $user], 'تم إنشاء الحساب بنجاح');
}

function getProfile() {
    $user = Auth::requireAuth();
    Response::success($user);
}

function updateProfile() {
    $user = Auth::requireAuth();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $userModel = new User();
    $updated = $userModel->update($user['id'], $data);
    
    Response::success($updated, 'تم تحديث الملف الشخصي');
}

function changePassword() {
    $user = Auth::requireAuth();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $validator = new Validator($data);
    $validator->required('current_password', 'كلمة المرور الحالية مطلوبة')
              ->required('new_password', 'كلمة المرور الجديدة مطلوبة')
              ->min('new_password', 6, 'كلمة المرور يجب أن تكون 6 أحرف على الأقل');
    $validator->validate();

    $userModel = new User();
    $fullUser = $userModel->findByEmail($user['email']);
    
    if (!$userModel->verifyPassword($fullUser, $data['current_password'])) {
        Response::error('كلمة المرور الحالية غير صحيحة', [], 400);
    }

    $userModel->updatePassword($user['id'], $data['new_password']);
    Response::success(null, 'تم تغيير كلمة المرور بنجاح');
}
