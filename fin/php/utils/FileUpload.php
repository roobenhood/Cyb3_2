<?php
/**
 * File Upload Helper
 * مساعد رفع الملفات
 */

require_once __DIR__ . '/../config/config.php';

class FileUpload {
    private $allowedExtensions;
    private $maxSize;
    private $uploadDir;
    private $errors = [];

    public function __construct($options = []) {
        $this->allowedExtensions = $options['extensions'] ?? ALLOWED_EXTENSIONS;
        $this->maxSize = $options['max_size'] ?? MAX_UPLOAD_SIZE;
        $this->uploadDir = $options['upload_dir'] ?? UPLOAD_DIR;

        // Create upload directory if not exists
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Upload single file
     */
    public function upload($file, $subDir = '') {
        $this->errors = [];

        // Check if file exists
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            $this->errors[] = 'لم يتم اختيار ملف';
            return false;
        }

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadError($file['error']);
            return false;
        }

        // Check file size
        if ($file['size'] > $this->maxSize) {
            $this->errors[] = 'حجم الملف كبير جداً (الحد الأقصى: ' . ($this->maxSize / 1024 / 1024) . 'MB)';
            return false;
        }

        // Get file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Check extension
        if (!in_array($extension, $this->allowedExtensions)) {
            $this->errors[] = 'نوع الملف غير مسموح';
            return false;
        }

        // Create subdirectory if needed
        $targetDir = $this->uploadDir;
        if ($subDir) {
            $targetDir .= $subDir . '/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
        }

        // Generate unique filename
        $newFilename = $this->generateFilename($extension);
        $targetPath = $targetDir . $newFilename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->errors[] = 'فشل في رفع الملف';
            return false;
        }

        // Return relative path
        $relativePath = ($subDir ? $subDir . '/' : '') . $newFilename;
        return [
            'filename' => $newFilename,
            'path' => $relativePath,
            'url' => UPLOAD_URL . $relativePath,
            'size' => $file['size'],
            'extension' => $extension,
            'original_name' => $file['name']
        ];
    }

    /**
     * Upload multiple files
     */
    public function uploadMultiple($files, $subDir = '') {
        $results = [];

        // Reorganize files array
        $fileCount = count($files['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];

            $result = $this->upload($file, $subDir);
            if ($result) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Delete file
     */
    public function delete($path) {
        $fullPath = $this->uploadDir . $path;
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    /**
     * Generate unique filename
     */
    private function generateFilename($extension) {
        return date('Y/m/') . uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    }

    /**
     * Get upload error message
     */
    private function getUploadError($code) {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'حجم الملف كبير جداً';
            case UPLOAD_ERR_PARTIAL:
                return 'تم رفع جزء من الملف فقط';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'مجلد الملفات المؤقتة غير موجود';
            case UPLOAD_ERR_CANT_WRITE:
                return 'فشل في كتابة الملف';
            case UPLOAD_ERR_EXTENSION:
                return 'تم إيقاف الرفع بواسطة إضافة';
            default:
                return 'خطأ غير معروف في رفع الملف';
        }
    }

    /**
     * Get errors
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Check if is image
     */
    public function isImage($path) {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    /**
     * Get file URL
     */
    public function getUrl($path) {
        return UPLOAD_URL . $path;
    }

    /**
     * Resize image
     */
    public function resizeImage($path, $width, $height, $quality = 85) {
        $fullPath = $this->uploadDir . $path;
        if (!file_exists($fullPath)) {
            return false;
        }

        $info = getimagesize($fullPath);
        if (!$info) {
            return false;
        }

        $mime = $info['mime'];
        $originalWidth = $info[0];
        $originalHeight = $info[1];

        // Calculate new dimensions
        $ratio = min($width / $originalWidth, $height / $originalHeight);
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);

        // Create image
        switch ($mime) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($fullPath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($fullPath);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($fullPath);
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($fullPath);
                break;
            default:
                return false;
        }

        $destination = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF
        if ($mime === 'image/png' || $mime === 'image/gif') {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 0, 0, 0, 127);
            imagefill($destination, 0, 0, $transparent);
        }

        imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        // Save image
        switch ($mime) {
            case 'image/jpeg':
                imagejpeg($destination, $fullPath, $quality);
                break;
            case 'image/png':
                imagepng($destination, $fullPath, (int)($quality / 10));
                break;
            case 'image/gif':
                imagegif($destination, $fullPath);
                break;
            case 'image/webp':
                imagewebp($destination, $fullPath, $quality);
                break;
        }

        imagedestroy($source);
        imagedestroy($destination);

        return true;
    }
}
