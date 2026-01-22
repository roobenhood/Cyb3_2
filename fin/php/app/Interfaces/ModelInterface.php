<?php

namespace App\Interfaces;

interface ModelInterface
{
    /**
     * البحث بالمعرف
     */
    public function findById(int $id): ?array;

    /**
     * الحصول على جميع السجلات
     */
    public function getAll(): array;
}
