<?php
// --- START: ERROR REPORTING ---
// These lines will show the exact PHP error on the screen.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);// --- END: ERROR REPORTING ---

/**
 * Authentication API Endpoints
 * This version includes error reporting and a smart register function for Firebase sync.
 */

// IMPORTANT: These paths might need to be adjusted based on your server's file structure.
// If the paths are wrong, you will see a "Failed opening required" error.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../models/User.php';

$action = $_GET['action'] ?? 'none';

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
    default:
        // This is the response for any unrecognized or missing action.
        Response::error('إجراء غير صالح أو مفقود', [], 400);
}

/**
 * Handles a standard email & password login request.
 * Note: Under the new strategy, this function might not be called directly by the app anymore.
 */
function login() {
    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $validator->required('email', 'البريد الإلكتروني مطلوب')
              ->email('email', 'البريد الإلكتروني غير صالح')
              ->required('password', 'كلمة المرور مطلوبة');
    $validator->validate();

    $userModel = new User();
    $user = $userModel->findByEmail($data['email']);

    // Securely verify the password against the stored hash
    if (!$user || !password_verify($data['password'], $user['password'])) {
        Response::error('البريد الإلكتروني أو كلمة المرور غير صحيحة', [], 401);
    }

    if (!$user['is_active']) {
        Response::error('الحساب غير مفعل', [], 403);
    }

    $token = Auth::generateToken($user['id']);
    unset($user['password']);
    Response::success(['token' => $token, 'user' => $user], 'تم تسجيل الدخول بنجاح');
}

/**
 * Smart function for Firebase Sync.
 * It finds a user by email. If they exist, it returns their data.
 * If they don't exist, it creates them in the database.
 */
function register() {
    $data = json_decode(file_get_contents('php://input'), true);
    $userModel = new User();

    // An email is required to find or create a user.
    if (empty($data['email'])) {
        Response::error('البريد الإلكتروني مطلوب لمزامنة الحساب', [], 422);
        return;
    }

    $existingUser = $userModel->findByEmail($data['email']);

    if ($existingUser) {
        // --- USER EXISTS ---
        // The user is already in our database, just log them in and send their data back.
        if (!$existingUser['is_active']) {
            Response::error('الحساب غير مفعل', [], 403);
        }
        $token = Auth::generateToken($existingUser['id']);
        unset($existingUser['password']);
        Response::success(['token' => $token, 'user' => $existingUser], 'تم تسجيل الدخول بنجاح');
    } else {
        // --- NEW USER ---
        // The user was authenticated by Firebase but is not in our database yet. Create them now.
        $validator = new Validator($data);
        $validator->required('name', 'الاسم مطلوب')
                  ->required('email', 'البريد الإلكتروني مطلوب')
                  ->email('email', 'البريد الإلكتروني غير صالح')
                  ->required('password', 'كلمة المرور مطلوبة'); // This is the placeholder password (UID) from the app
        $validator->validate();

        $newUser = $userModel->create($data);
        if (!$newUser) {
             Response::error('فشل إنشاء المستخدم في قاعدة البيانات', [], 500);
             return;
        }

        $token = Auth::generateToken($newUser['id']);
        unset($newUser['password']);
        Response::created(['token' => $token, 'user' => $newUser], 'تم إنشاء الحساب بنجاح');
    }
}

function getProfile() {
    $user = Auth::requireAuth();
    if ($user) {
      unset($user['password']);
    }
    Response::success($user);
}

function updateProfile() {
    $user = Auth::requireAuth();
    $data = json_decode(file_get_contents('php://input'), true);
    unset($data['email'], $data['password']);

    $userModel = new User();
    $updatedUser = $userModel->update($user['id'], $data);
    unset($updatedUser['password']);

    Response::success($updatedUser, 'تم تحديث الملف الشخصي');
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
    $fullUser = $userModel->findById($user['id']);

    if (!password_verify($data['current_password'], $fullUser['password'])) {
        Response::error('كلمة المرور الحالية غير صحيحة', [], 400);
    }

    $userModel->updatePassword($user['id'], $data['new_password']);
    Response::success(null, 'تم تغيير كلمة المرور بنجاح');
}