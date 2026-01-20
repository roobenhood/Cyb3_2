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
        match($action) {
            'login' => $this->login(),
            'register' => $this->register(),
            'profile' => $this->profile(),
            'update' => $this->update(),
            'change_password' => $this->changePassword(),
            default => ApiResponse::error('إجراء غير صالح', [], 400)
        };
    }
    
    private function login(): void
    {
        $data = $this->getRequestData();
        $validator = new Validator($data);
        if (!$validator->validate(['email' => 'required|email', 'password' => 'required|min:6'])) { $validator->sendErrors(); }
        $userModel = $this->model('User');
        $user = $userModel->findByEmail($data['email']);
        if (!$user || !password_verify($data['password'], $user['password'])) { ApiResponse::error('بيانات الدخول غير صحيحة'); }
        $token = Auth::generateToken($user['id']);
        unset($user['password']);
        ApiResponse::success(['user' => $user, 'token' => $token], 'تم تسجيل الدخول بنجاح');
    }
    
    private function register(): void
    {
        $data = $this->getRequestData();
        $validator = new Validator($data);
        if (!$validator->validate(['name' => 'required|min:2', 'email' => 'required|email|unique:users,email', 'password' => 'required|min:6'])) { $validator->sendErrors(); }
        $userModel = $this->model('User');
        $userId = $userModel->create($data);
        $token = Auth::generateToken($userId);
        $user = $userModel->findById($userId);
        ApiResponse::created(['user' => $user, 'token' => $token], 'تم إنشاء الحساب بنجاح');
    }
    
    private function profile(): void { $user = Auth::requireAuth(); ApiResponse::success($user); }
    private function update(): void { $user = Auth::requireAuth(); $data = $this->getRequestData(); $this->model('User')->update($user['id'], array_intersect_key($data, array_flip(['name', 'phone', 'avatar']))); ApiResponse::success($this->model('User')->findById($user['id']), 'تم تحديث البيانات'); }
    private function changePassword(): void { $user = Auth::requireAuth(); $data = $this->getRequestData(); if (!$this->model('User')->verifyPassword($user['id'], $data['current_password'])) { ApiResponse::error('كلمة المرور الحالية غير صحيحة'); } $this->model('User')->updatePassword($user['id'], $data['new_password']); ApiResponse::success(null, 'تم تغيير كلمة المرور'); }
}
