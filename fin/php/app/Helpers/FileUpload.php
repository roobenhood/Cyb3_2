<?php

namespace App\Helpers;

use App\Core\Config;
use App\Core\Path;

/**
 * FileUpload Helper
 * مساعد رفع الملفات
 */
class FileUpload
{
    private array $errors = [];
    private array $allowedExtensions;
    private int $maxSize;
    
    public function __construct()
    {
        $this->allowedExtensions = Config::get('app.upload.allowed_extensions', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        $this->maxSize = Config::get('app.upload.max_size', 5 * 1024 * 1024);
    }
    
    /**
     * رفع ملف واحد
     */
    public function upload(array $file, string $directory = 'images'): ?string
    {
        if (!$this->validateFile($file)) {
            return null;
        }
        
        $uploadDir = Path::uploads($directory);
        $this->createDirectory($uploadDir);
        
        $filename = $this->generateFilename($file['name']);
        $destination = $uploadDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $directory . '/' . $filename;
        }
        
        $this->errors[] = 'فشل في رفع الملف';
        return null;
    }
    
    /**
     * رفع ملفات متعددة
     */
    public function uploadMultiple(array $files, string $directory = 'images'): array
    {
        $uploadedFiles = [];
        
        // إعادة هيكلة مصفوفة الملفات
        $restructured = [];
        foreach ($files as $key => $values) {
            foreach ($values as $index => $value) {
                $restructured[$index][$key] = $value;
            }
        }
        
        foreach ($restructured as $file) {
            $result = $this->upload($file, $directory);
            if ($result) {
                $uploadedFiles[] = $result;
            }
        }
        
        return $uploadedFiles;
    }
    
    /**
     * التحقق من صحة الملف
     */
    private function validateFile(array $file): bool
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadErrorMessage($file['error']);
            return false;
        }
        
        // التحقق من الحجم
        if ($file['size'] > $this->maxSize) {
            $this->errors[] = 'حجم الملف كبير جداً';
            return false;
        }
        
        // التحقق من الامتداد
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            $this->errors[] = 'نوع الملف غير مسموح';
            return false;
        }
        
        return true;
    }
    
    /**
     * إنشاء المجلد إذا لم يكن موجوداً
     */
    private function createDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
    
    /**
     * توليد اسم ملف فريد
     */
    private function generateFilename(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        return uniqid() . '_' . time() . '.' . $extension;
    }
    
    /**
     * حذف ملف
     */
    public function delete(string $path): bool
    {
        $fullPath = Path::uploads($path);
        
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        
        return false;
    }
    
    /**
     * تغيير حجم الصورة
     */
    public function resize(string $path, int $width, int $height, bool $crop = false): bool
    {
        $fullPath = Path::uploads($path);
        
        if (!file_exists($fullPath)) {
            return false;
        }
        
        $info = getimagesize($fullPath);
        if (!$info) {
            return false;
        }
        
        $mime = $info['mime'];
        
        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($fullPath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($fullPath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($fullPath);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($fullPath);
                break;
            default:
                return false;
        }
        
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);
        
        if ($crop) {
            $ratio = max($width / $originalWidth, $height / $originalHeight);
            $newWidth = (int)($originalWidth * $ratio);
            $newHeight = (int)($originalHeight * $ratio);
            $x = (int)(($newWidth - $width) / 2);
            $y = (int)(($newHeight - $height) / 2);
        } else {
            $ratio = min($width / $originalWidth, $height / $originalHeight);
            $newWidth = (int)($originalWidth * $ratio);
            $newHeight = (int)($originalHeight * $ratio);
            $x = 0;
            $y = 0;
            $width = $newWidth;
            $height = $newHeight;
        }
        
        $newImage = imagecreatetruecolor($width, $height);
        
        // الحفاظ على الشفافية للـ PNG
        if ($mime === 'image/png') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }
        
        imagecopyresampled($newImage, $image, -$x, -$y, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        switch ($mime) {
            case 'image/jpeg':
                imagejpeg($newImage, $fullPath, 85);
                break;
            case 'image/png':
                imagepng($newImage, $fullPath, 8);
                break;
            case 'image/gif':
                imagegif($newImage, $fullPath);
                break;
            case 'image/webp':
                imagewebp($newImage, $fullPath, 85);
                break;
        }
        
        imagedestroy($image);
        imagedestroy($newImage);
        
        return true;
    }
    
    /**
     * الحصول على رسالة خطأ الرفع
     */
    private function getUploadErrorMessage(int $error): string
    {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'حجم الملف كبير جداً';
            case UPLOAD_ERR_PARTIAL:
                return 'لم يكتمل رفع الملف';
            case UPLOAD_ERR_NO_FILE:
                return 'لم يتم اختيار ملف';
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
            case UPLOAD_ERR_EXTENSION:
                return 'خطأ في الخادم';
            default:
                return 'خطأ غير معروف';
        }
    }
    
    /**
     * الحصول على الأخطاء
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
