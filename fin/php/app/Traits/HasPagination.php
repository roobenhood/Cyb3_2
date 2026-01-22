<?php

namespace App\Traits;

trait HasPagination
{
    protected int $perPage = 10;

    /**
     * الحصول على البيانات مع pagination
     */
    protected function paginate(string $query, array $params = [], int $page = 1, int $perPage = null): array
    {
        $perPage = $perPage ?? $this->perPage;
        $offset = ($page - 1) * $perPage;

        // الحصول على العدد الكلي
        $countQuery = preg_replace('/SELECT .+ FROM/i', 'SELECT COUNT(*) as total FROM', $query);
        $countQuery = preg_replace('/ORDER BY .+$/i', '', $countQuery);
        
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];

        // الحصول على البيانات
        $query .= " LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int)ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }

    /**
     * تعيين عدد العناصر في الصفحة
     */
    protected function setPerPage(int $perPage): void
    {
        $this->perPage = $perPage;
    }
}
