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

        // Generate unique filename
        $filename = $this->generateFilename($extension);

        // Create subdirectory if specified
        $targetDir = $this->uploadDir;
        if ($subDir) {
            $targetDir .= $subDir . '/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
        }

        $targetPath = $targetDir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->errors[] = 'فشل في رفع الملف';
            return false;
        }

        // Return relative path
        $relativePath = ($subDir ? $subDir . '/' : '') . $filename;
        return $relativePath;
    }

    /**
     * Upload multiple files
     */
    public function uploadMultiple($files, $subDir = '') {
        $uploaded = [];

        // Restructure files array
        $fileList = [];
        if (isset($files['name']) && is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                $fileList[] = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];
            }
        } else {
            $fileList = [$files];
        }

        foreach ($fileList as $file) {
            $result = $this->upload($file, $subDir);
            if ($result) {
                $uploaded[] = $result;
            }
        }

        return $uploaded;
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
     * Check if file exists
     */
    public function exists($path) {
        return file_exists($this->uploadDir . $path);
    }

    /**
     * Get file URL
     */
    public function getUrl($path) {
        return UPLOAD_URL . $path;
    }

    /**
     * Generate unique filename
     */
    private function generateFilename($extension) {
        return date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    }

    /**
     * Get upload error message
     */
    private function getUploadError($code) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'حجم الملف يتجاوز الحد المسموح',
            UPLOAD_ERR_FORM_SIZE => 'حجم الملف يتجاوز الحد المسموح',
            UPLOAD_ERR_PARTIAL => 'تم رفع جزء من الملف فقط',
            UPLOAD_ERR_NO_FILE => 'لم يتم رفع أي ملف',
            UPLOAD_ERR_NO_TMP_DIR => 'مجلد الملفات المؤقتة مفقود',
            UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف',
            UPLOAD_ERR_EXTENSION => 'امتداد PHP أوقف الرفع',
        ];
        return $errors[$code] ?? 'خطأ غير معروف في الرفع';
    }

    /**
     * Get errors
     */
    public function errors() {
        return $this->errors;
    }

    /**
     * Validate image dimensions
     */
    public function validateImageDimensions($file, $minWidth = 0, $minHeight = 0, $maxWidth = 0, $maxHeight = 0) {
        $imageInfo = getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            $this->errors[] = 'الملف ليس صورة صالحة';
            return false;
        }

        list($width, $height) = $imageInfo;

        if ($minWidth && $width < $minWidth) {
            $this->errors[] = "عرض الصورة يجب أن يكون {$minWidth} بكسل على الأقل";
            return false;
        }

        if ($minHeight && $height < $minHeight) {
            $this->errors[] = "ارتفاع الصورة يجب أن يكون {$minHeight} بكسل على الأقل";
            return false;
        }

        if ($maxWidth && $width > $maxWidth) {
            $this->errors[] = "عرض الصورة يجب ألا يتجاوز {$maxWidth} بكسل";
            return false;
        }

        if ($maxHeight && $height > $maxHeight) {
            $this->errors[] = "ارتفاع الصورة يجب ألا يتجاوز {$maxHeight} بكسل";
            return false;
        }

        return true;
    }
}
