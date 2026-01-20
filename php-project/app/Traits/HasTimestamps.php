<?php

namespace App\Traits;

trait HasTimestamps
{
    /**
     * إضافة timestamps للإدراج
     */
    protected function addCreatedTimestamps(array &$data): void
    {
        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
    }

    /**
     * تحديث updated_at
     */
    protected function addUpdatedTimestamp(array &$data): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
    }

    /**
     * تنسيق التاريخ للعرض
     */
    protected function formatDate(?string $date, string $format = 'Y-m-d H:i'): ?string
    {
        if (!$date) {
            return null;
        }
        
        return date($format, strtotime($date));
    }

    /**
     * تنسيق التاريخ بالعربية
     */
    protected function formatDateArabic(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        $timestamp = strtotime($date);
        $months = [
            'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
            'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
        ];

        $day = date('j', $timestamp);
        $month = $months[(int)date('n', $timestamp) - 1];
        $year = date('Y', $timestamp);

        return "{$day} {$month} {$year}";
    }
}
