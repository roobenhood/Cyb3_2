<?php
/**
 * Authentication API Endpoints
 * نقاط نهاية API للمصادقة - المتجر الإلكتروني
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../models/User.php';

$action = $_GET['action'] ?? 'login';

switch ($action) {
    case 'login':
        login();
        break;
    case 'register':
        register();
        break;
    case 'profile':
        getProfile();
        break;
    case 'update-profile':
        updateProfile();
        break;
    case 'change-password':
        changePassword();
        break;
    case 'forgot-password':
        forgotPassword();
        break;
    case 'reset-password':
        resetPassword();
        break;
    default:
        Response::error('إجراء غير صالح', [], 400);
}

/**
 * Login user
 */
function login() {
    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $validator->required('email', 'البريد الإلكتروني مطلوب');
    $validator->email('email', 'البريد الإلكتروني غير صالح');
    $validator->required('password', 'كلمة المرور مطلوبة');

    if (!$validator->isValid()) {
        Response::error('بيانات غير صالحة', $validator->getErrors(), 422);
        return;
    }

    try {
        $userModel = new User();
        $user = $userModel->findByEmail($data['email']);

        if (!$user || !password_verify($data['password'], $user['password'])) {
            Response::error('البريد الإلكتروني أو كلمة المرور غير صحيحة', [], 401);
            return;
        }

        if (!$user['is_active']) {
            Response::error('الحساب معطل', [], 403);
            return;
        }

        // Generate JWT token
        $token = Auth::generateToken($user);

        // Remove password from response
        unset($user['password']);

        Response::success('تم تسجيل الدخول بنجاح', [
            'user' => $user,
            'token' => $token
        ]);
    } catch (Exception $e) {
        Response::error('فشل في تسجيل الدخول', [], 500);
    }
}

/**
 * Register new user
 */
function register() {
    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $validator->required('name', 'الاسم مطلوب');
    $validator->minLength('name', 2, 'الاسم يجب أن يكون حرفين على الأقل');
    $validator->required('email', 'البريد الإلكتروني مطلوب');
    $validator->email('email', 'البريد الإلكتروني غير صالح');
    $validator->required('password', 'كلمة المرور مطلوبة');
    $validator->minLength('password', 6, 'كلمة المرور يجب أن تكون 6 أحرف على الأقل');
    $validator->required('password_confirmation', 'تأكيد كلمة المرور مطلوب');
    $validator->matches('password_confirmation', $data['password'] ?? '', 'كلمة المرور غير متطابقة');

    if (!$validator->isValid()) {
        Response::error('بيانات غير صالحة', $validator->getErrors(), 422);
        return;
    }

    try {
        $userModel = new User();

        // Check if email exists
        if ($userModel->findByEmail($data['email'])) {
            Response::error('البريد الإلكتروني مستخدم بالفعل', ['email' => 'البريد الإلكتروني مستخدم'], 422);
            return;
        }

        // Create user
        $userId = $userModel->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'phone' => $data['phone'] ?? null,
            'role' => 'customer'
        ]);

        $user = $userModel->findById($userId);

        // Generate JWT token
        $token = Auth::generateToken($user);

        // Remove password from response
        unset($user['password']);

        Response::success('تم إنشاء الحساب بنجاح', [
            'user' => $user,
            'token' => $token
        ], [], 201);
    } catch (Exception $e) {
        Response::error('فشل في إنشاء الحساب', [], 500);
    }
}

/**
 * Get user profile
 */
function getProfile() {
    $user = Auth::requireAuth();
    if (!$user) return;

    try {
        $userModel = new User();
        $profile = $userModel->findById($user['id']);

        if (!$profile) {
            Response::error('المستخدم غير موجود', [], 404);
            return;
        }

        unset($profile['password']);

        Response::success('تم جلب الملف الشخصي', $profile);
    } catch (Exception $e) {
        Response::error('فشل في جلب الملف الشخصي', [], 500);
    }
}

/**
 * Update user profile
 */
function updateProfile() {
    $user = Auth::requireAuth();
    if (!$user) return;

    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    if (isset($data['name'])) {
        $validator->minLength('name', 2, 'الاسم يجب أن يكون حرفين على الأقل');
    }
    if (isset($data['email'])) {
        $validator->email('email', 'البريد الإلكتروني غير صالح');
    }
    if (isset($data['phone'])) {
        $validator->phone('phone', 'رقم الهاتف غير صالح');
    }

    if (!$validator->isValid()) {
        Response::error('بيانات غير صالحة', $validator->getErrors(), 422);
        return;
    }

    try {
        $userModel = new User();

        // Check if new email exists
        if (isset($data['email']) && $data['email'] !== $user['email']) {
            if ($userModel->findByEmail($data['email'])) {
                Response::error('البريد الإلكتروني مستخدم بالفعل', ['email' => 'البريد الإلكتروني مستخدم'], 422);
                return;
            }
        }

        $updateData = [];
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['email'])) $updateData['email'] = $data['email'];
        if (isset($data['phone'])) $updateData['phone'] = $data['phone'];
        if (isset($data['avatar'])) $updateData['avatar'] = $data['avatar'];

        $userModel->update($user['id'], $updateData);

        $profile = $userModel->findById($user['id']);
        unset($profile['password']);

        Response::success('تم تحديث الملف الشخصي', $profile);
    } catch (Exception $e) {
        Response::error('فشل في تحديث الملف الشخصي', [], 500);
    }
}

/**
 * Change password
 */
function changePassword() {
    $user = Auth::requireAuth();
    if (!$user) return;

    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $validator->required('current_password', 'كلمة المرور الحالية مطلوبة');
    $validator->required('new_password', 'كلمة المرور الجديدة مطلوبة');
    $validator->minLength('new_password', 6, 'كلمة المرور يجب أن تكون 6 أحرف على الأقل');
    $validator->required('new_password_confirmation', 'تأكيد كلمة المرور مطلوب');
    $validator->matches('new_password_confirmation', $data['new_password'] ?? '', 'كلمة المرور غير متطابقة');

    if (!$validator->isValid()) {
        Response::error('بيانات غير صالحة', $validator->getErrors(), 422);
        return;
    }

    try {
        $userModel = new User();
        $currentUser = $userModel->findById($user['id']);

        if (!password_verify($data['current_password'], $currentUser['password'])) {
            Response::error('كلمة المرور الحالية غير صحيحة', ['current_password' => 'كلمة المرور غير صحيحة'], 422);
            return;
        }

        $userModel->update($user['id'], [
            'password' => password_hash($data['new_password'], PASSWORD_DEFAULT)
        ]);

        Response::success('تم تغيير كلمة المرور بنجاح');
    } catch (Exception $e) {
        Response::error('فشل في تغيير كلمة المرور', [], 500);
    }
}

/**
 * Forgot password - send reset link
 */
function forgotPassword() {
    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $validator->required('email', 'البريد الإلكتروني مطلوب');
    $validator->email('email', 'البريد الإلكتروني غير صالح');

    if (!$validator->isValid()) {
        Response::error('بيانات غير صالحة', $validator->getErrors(), 422);
        return;
    }

    try {
        $userModel = new User();
        $user = $userModel->findByEmail($data['email']);

        // Always return success to prevent email enumeration
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Save reset token (you would need to implement this in User model)
            // $userModel->savePasswordReset($data['email'], $token, $expires);

            // Send email (implement your email service)
            // EmailService::sendPasswordReset($data['email'], $token);
        }

        Response::success('إذا كان البريد الإلكتروني مسجلاً، ستصلك رسالة إعادة تعيين كلمة المرور');
    } catch (Exception $e) {
        Response::error('فشل في إرسال رابط إعادة التعيين', [], 500);
    }
}

/**
 * Reset password with token
 */
function resetPassword() {
    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $validator->required('token', 'رمز إعادة التعيين مطلوب');
    $validator->required('password', 'كلمة المرور الجديدة مطلوبة');
    $validator->minLength('password', 6, 'كلمة المرور يجب أن تكون 6 أحرف على الأقل');
    $validator->required('password_confirmation', 'تأكيد كلمة المرور مطلوب');
    $validator->matches('password_confirmation', $data['password'] ?? '', 'كلمة المرور غير متطابقة');

    if (!$validator->isValid()) {
        Response::error('بيانات غير صالحة', $validator->getErrors(), 422);
        return;
    }

    try {
        // Verify token and reset password (implement in User model)
        // $result = $userModel->resetPasswordWithToken($data['token'], $data['password']);

        Response::success('تم إعادة تعيين كلمة المرور بنجاح');
    } catch (Exception $e) {
        Response::error('رمز إعادة التعيين غير صالح أو منتهي الصلاحية', [], 400);
    }
}
