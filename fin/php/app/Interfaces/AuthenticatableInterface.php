<?php

namespace App\Interfaces;

interface AuthenticatableInterface
{
    /**
     * البحث بالبريد الإلكتروني
     */
    public function findByEmail(string $email): ?array;

    /**
     * إنشاء مستخدم جديد
     */
    public function create(array $data): array;

    /**
     * التحقق من كلمة المرور
     */
    public function verifyPassword(string $password, string $hash): bool;
}
