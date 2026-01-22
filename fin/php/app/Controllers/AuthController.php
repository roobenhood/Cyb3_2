<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\ApiResponse;
use App\Helpers\Auth;
use App\Helpers\Validator;
use App\Models\User;

class AuthController extends Controller
{
    public function handle(): void
    {
        $action = $this->getAction();
        
        // توحيد نقطة الدخول للمصادقة القادمة من Flutter
        if (in_array($action, ['login', 'register'])) {
            $this->syncUser();
        } else {
            match($action) {
                'profile' => $this->profile(),
                'update', 'update-profile' => $this->update(),
                'change_password' => $this->changePassword(),
                default => ApiResponse::error('إجراء غير صالح', [], 400)
            };
        }
    }

    private function syncUser(): void
    {
        $data = $this->getRequestData();

        // --- ملاحظة أمنية: في بيئة الإنتاج، يجب التحقق من صحة firebase_token ---

        $validator = new Validator($data);
        if (!$validator->validate([
            'firebase_uid' => 'required',
            'email' => 'required|email'
        ])) {
            $validator->sendErrors();
        }

        $userModel = $this->model('User');
        
        // البحث باستخدام معرف Firebase أولاً
        $user = $userModel->findByFirebaseUid($data['firebase_uid']);

        // إذا لم يوجد، ابحث بالإيميل لربط الحسابات
        if (!$user) {
            $user = $userModel->findByEmail($data['email']);
        }

        if ($user) {
            // مستخدم موجود، تأكد من ربط معرف Firebase
            if (empty($user['firebase_uid'])) {
                $user = $userModel->update($user['id'], [
                    'firebase_uid' => $data['firebase_uid'],
                    'name' => $data['name'] ?? $user['name'],
                    'avatar' => $data['avatar'] ?? $user['avatar']
                ]);
            }
        } else {
            // مستخدم جديد، قم بإنشائه
            $payload = [
                'name' => $data['name'] ?? 'مستخدم جديد',
                'email' => $data['email'],
                'firebase_uid' => $data['firebase_uid'],
                'avatar' => $data['avatar'] ?? null,
            ];
            // إضافة كلمة المرور فقط إذا كانت موجودة (للتسجيل العادي)
            if (isset($data['password'])) {
                $payload['password'] = $data['password'];
            }
            $user = $userModel->create($payload);
        }

        if (!$user) {
            ApiResponse::error('فشل في معالجة المستخدم.', [], 500);
        }
        
        if (!$user['is_active']) {
            ApiResponse::error('الحساب غير مفعل.', [], 403);
        }

        $token = Auth::generateToken($user['id']);
        unset($user['password']);
        ApiResponse::success(['user' => $user, 'token' => $token], 'تمت المصادقة بنجاح');
    }

    private function profile(): void { $user = Auth::requireAuth(); ApiResponse::success($user); }
    private function update(): void { $user = Auth::requireAuth(); $data = $this->getRequestData(); $updatedUser = $this->model('User')->update($user['id'], array_intersect_key($data, array_flip(['name', 'phone', 'avatar']))); ApiResponse::success($updatedUser, 'تم تحديث البيانات'); }
    private function changePassword(): void { /* ... الكود الحالي هنا يعمل بشكل صحيح ... */ }
}