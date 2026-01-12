<?php
/**
 * Authentication API Endpoints
 * نقاط نهاية API للمصادقة
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        register();
        break;
    case 'login':
        login();
        break;
    case 'logout':
        logout();
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
    default:
        Response::error('إجراء غير صالح', [], 400);
}

function register() {
    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $isValid = $validator->validate([
        'name' => 'required|min:2|max:100',
        'email' => 'required|email|max:255',
        'password' => 'required|password|min:6',
        'password_confirmation' => 'required|confirmed'
    ]);

    if (!$isValid) {
        Response::validationError($validator->getErrors());
    }

    $user = new User();

    // Check if email exists
    if ($user->emailExists($data['email'])) {
        Response::error('البريد الإلكتروني مستخدم بالفعل', ['email' => ['البريد الإلكتروني مستخدم بالفعل']], 422);
    }

    $user->name = Validator::sanitize($data['name']);
    $user->email = Validator::sanitize($data['email'], 'email');
    $user->password = $data['password'];
    $user->phone = Validator::sanitize($data['phone'] ?? '', 'string');
    $user->role = 'student';

    if ($user->create()) {
        $token = Auth::generateToken($user->id);
        Response::success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ],
            'token' => $token
        ], 'تم إنشاء الحساب بنجاح', 201);
    } else {
        Response::serverError('فشل إنشاء الحساب');
    }
}

function login() {
    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $isValid = $validator->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    if (!$isValid) {
        Response::validationError($validator->getErrors());
    }

    $user = new User();
    $userData = $user->findByEmail($data['email']);

    if (!$userData || !Auth::verifyPassword($data['password'], $userData['password'])) {
        Response::error('البريد الإلكتروني أو كلمة المرور غير صحيحة', [], 401);
    }

    if (!$userData['is_active']) {
        Response::error('الحساب معطل', [], 403);
    }

    $token = Auth::generateToken($userData['id']);

    Response::success([
        'user' => [
            'id' => $userData['id'],
            'name' => $userData['name'],
            'email' => $userData['email'],
            'phone' => $userData['phone'],
            'avatar' => $userData['avatar'],
            'role' => $userData['role']
        ],
        'token' => $token
    ], 'تم تسجيل الدخول بنجاح');
}

function logout() {
    // Since we use JWT, logout is handled client-side
    Response::success(null, 'تم تسجيل الخروج بنجاح');
}

function getProfile() {
    $userId = Auth::requireAuth();

    $user = new User();
    $userData = $user->findById($userId);

    if (!$userData) {
        Response::notFound('المستخدم غير موجود');
    }

    unset($userData['password']);
    Response::success($userData);
}

function updateProfile() {
    $userId = Auth::requireAuth();
    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $isValid = $validator->validate([
        'name' => 'required|min:2|max:100',
        'phone' => 'phone'
    ]);

    if (!$isValid) {
        Response::validationError($validator->getErrors());
    }

    $user = new User();
    $updateData = [
        'name' => Validator::sanitize($data['name']),
        'phone' => Validator::sanitize($data['phone'] ?? '', 'string')
    ];

    if ($user->update($userId, $updateData)) {
        $userData = $user->findById($userId);
        unset($userData['password']);
        Response::success($userData, 'تم تحديث الملف الشخصي بنجاح');
    } else {
        Response::serverError('فشل تحديث الملف الشخصي');
    }
}

function changePassword() {
    $userId = Auth::requireAuth();
    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $isValid = $validator->validate([
        'current_password' => 'required',
        'new_password' => 'required|password|min:6',
        'new_password_confirmation' => 'required|confirmed'
    ]);

    if (!$isValid) {
        Response::validationError($validator->getErrors());
    }

    $user = new User();
    $userData = $user->findById($userId);

    if (!Auth::verifyPassword($data['current_password'], $userData['password'])) {
        Response::error('كلمة المرور الحالية غير صحيحة', ['current_password' => ['كلمة المرور الحالية غير صحيحة']], 422);
    }

    if ($user->updatePassword($userId, $data['new_password'])) {
        Response::success(null, 'تم تغيير كلمة المرور بنجاح');
    } else {
        Response::serverError('فشل تغيير كلمة المرور');
    }
}
