<?php
/**
 * Input Validation Class
 * فئة التحقق من المدخلات
 */

class Validator {
    private $errors = [];
    private $data = [];

    public function __construct($data = []) {
        $this->data = $data;
    }

    public function validate($rules) {
        foreach ($rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            $rulesArray = explode('|', $fieldRules);

            foreach ($rulesArray as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    private function applyRule($field, $value, $rule) {
        $params = [];
        if (strpos($rule, ':') !== false) {
            list($rule, $paramStr) = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
        }

        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, 'هذا الحقل مطلوب');
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'البريد الإلكتروني غير صحيح');
                }
                break;

            case 'min':
                $min = (int)$params[0];
                if (!empty($value) && strlen($value) < $min) {
                    $this->addError($field, "يجب أن يكون على الأقل {$min} أحرف");
                }
                break;

            case 'max':
                $max = (int)$params[0];
                if (!empty($value) && strlen($value) > $max) {
                    $this->addError($field, "يجب ألا يتجاوز {$max} أحرف");
                }
                break;

            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, 'يجب أن يكون رقماً');
                }
                break;

            case 'integer':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, 'يجب أن يكون عدداً صحيحاً');
                }
                break;

            case 'alpha':
                if (!empty($value) && !preg_match('/^[\p{L}\s]+$/u', $value)) {
                    $this->addError($field, 'يجب أن يحتوي على حروف فقط');
                }
                break;

            case 'alphanumeric':
                if (!empty($value) && !preg_match('/^[\p{L}\p{N}\s]+$/u', $value)) {
                    $this->addError($field, 'يجب أن يحتوي على حروف وأرقام فقط');
                }
                break;

            case 'phone':
                if (!empty($value) && !preg_match('/^[\d\s\-\+\(\)]{10,20}$/', $value)) {
                    $this->addError($field, 'رقم الهاتف غير صحيح');
                }
                break;

            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, 'الرابط غير صحيح');
                }
                break;

            case 'date':
                if (!empty($value) && !strtotime($value)) {
                    $this->addError($field, 'التاريخ غير صحيح');
                }
                break;

            case 'in':
                if (!empty($value) && !in_array($value, $params)) {
                    $this->addError($field, 'القيمة غير مسموحة');
                }
                break;

            case 'confirmed':
                $confirmField = $field . '_confirmation';
                $confirmValue = $this->data[$confirmField] ?? null;
                if ($value !== $confirmValue) {
                    $this->addError($field, 'التأكيد غير متطابق');
                }
                break;

            case 'unique':
                // This should be handled by the model/controller
                break;

            case 'password':
                if (!empty($value)) {
                    if (strlen($value) < 6) {
                        $this->addError($field, 'كلمة المرور يجب أن تكون 6 أحرف على الأقل');
                    }
                }
                break;

            case 'image':
                if (!empty($value) && is_array($value)) {
                    if (!in_array($value['type'], ALLOWED_IMAGE_TYPES)) {
                        $this->addError($field, 'نوع الصورة غير مسموح');
                    }
                }
                break;

            case 'file_size':
                $maxSize = (int)$params[0] * 1024 * 1024; // Convert to bytes
                if (!empty($value) && is_array($value) && $value['size'] > $maxSize) {
                    $this->addError($field, "حجم الملف يجب ألا يتجاوز {$params[0]} ميجابايت");
                }
                break;
        }
    }

    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getFirstError($field = null) {
        if ($field) {
            return $this->errors[$field][0] ?? null;
        }
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }
        return null;
    }

    public static function sanitize($value, $type = 'string') {
        switch ($type) {
            case 'string':
                return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            case 'email':
                return filter_var(trim($value), FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'url':
                return filter_var(trim($value), FILTER_SANITIZE_URL);
            case 'html':
                return strip_tags($value, '<p><br><strong><em><ul><ol><li><a>');
            default:
                return $value;
        }
    }

    public static function sanitizeArray($data, $rules = []) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            $type = $rules[$key] ?? 'string';
            $sanitized[$key] = self::sanitize($value, $type);
        }
        return $sanitized;
    }
}
