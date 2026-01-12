<?php
/**
 * File Upload Helper
 * مساعد رفع الملفات
 */

require_once __DIR__ . '/../config/config.php';

class FileUpload {
    private $allowedTypes = [];
    private $maxSize;
    private $uploadPath;
    private $errors = [];

    public function __construct($type = 'image') {
        switch ($type) {
            case 'image':
                $this->allowedTypes = ALLOWED_IMAGE_TYPES;
                break;
            case 'video':
                $this->allowedTypes = ALLOWED_VIDEO_TYPES;
                break;
            case 'document':
                $this->allowedTypes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];
                break;
        }
        $this->maxSize = MAX_FILE_SIZE;
        $this->uploadPath = UPLOAD_PATH;
    }

    public function upload($file, $subFolder = '') {
        $this->errors = [];

        // Check if file exists
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            $this->errors[] = 'لم يتم اختيار ملف';
            return false;
        }

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadErrorMessage($file['error']);
            return false;
        }

        // Validate file type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            $this->errors[] = 'نوع الملف غير مسموح';
            return false;
        }

        // Validate file size
        if ($file['size'] > $this->maxSize) {
            $this->errors[] = 'حجم الملف كبير جداً';
            return false;
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . strtolower($extension);

        // Create upload directory if not exists
        $targetDir = $this->uploadPath . ($subFolder ? $subFolder . '/' : '');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->errors[] = 'فشل رفع الملف';
            return false;
        }

        return [
            'filename' => $filename,
            'path' => ($subFolder ? $subFolder . '/' : '') . $filename,
            'full_path' => $targetPath,
            'size' => $file['size'],
            'mime_type' => $mimeType
        ];
    }

    public function delete($path) {
        $fullPath = $this->uploadPath . $path;
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    public function getErrors() {
        return $this->errors;
    }

    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'حجم الملف كبير جداً';
            case UPLOAD_ERR_PARTIAL:
                return 'لم يتم رفع الملف بالكامل';
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

    public function createThumbnail($sourcePath, $width = 300, $height = 200) {
        $targetPath = str_replace('.', '_thumb.', $sourcePath);
        
        list($origWidth, $origHeight, $type) = getimagesize($this->uploadPath . $sourcePath);
        
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($this->uploadPath . $sourcePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($this->uploadPath . $sourcePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($this->uploadPath . $sourcePath);
                break;
            default:
                return false;
        }

        $thumb = imagecreatetruecolor($width, $height);
        
        // Preserve transparency for PNG
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }

        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);

        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumb, $this->uploadPath . $targetPath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumb, $this->uploadPath . $targetPath, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($thumb, $this->uploadPath . $targetPath);
                break;
        }

        imagedestroy($source);
        imagedestroy($thumb);

        return $targetPath;
    }
}
