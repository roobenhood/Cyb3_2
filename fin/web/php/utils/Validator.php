<?php
/**
 * Validator Helper
 * مساعد التحقق من المدخلات
 */

class Validator {
    private $data;
    private $errors = [];

    public function __construct($data = []) {
        $this->data = $data;
    }

    /**
     * Validate required field
     */
    public function required($field, $message = null) {
        $value = $this->getValue($field);
        if (empty($value) && $value !== '0' && $value !== 0) {
            $this->addError($field, $message ?? "حقل {$field} مطلوب");
        }
        return $this;
    }

    /**
     * Validate email format
     */
    public function email($field, $message = null) {
        $value = $this->getValue($field);
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, $message ?? 'البريد الإلكتروني غير صالح');
        }
        return $this;
    }

    /**
     * Validate minimum length
     */
    public function min($field, $length, $message = null) {
        $value = $this->getValue($field);
        if (!empty($value) && mb_strlen($value) < $length) {
            $this->addError($field, $message ?? "حقل {$field} يجب أن يكون {$length} أحرف على الأقل");
        }
        return $this;
    }

    /**
     * Validate maximum length
     */
    public function max($field, $length, $message = null) {
        $value = $this->getValue($field);
        if (!empty($value) && mb_strlen($value) > $length) {
            $this->addError($field, $message ?? "حقل {$field} يجب ألا يتجاوز {$length} حرف");
        }
        return $this;
    }

    /**
     * Validate numeric value
     */
    public function numeric($field, $message = null) {
        $value = $this->getValue($field);
        if (!empty($value) && !is_numeric($value)) {
            $this->addError($field, $message ?? "حقل {$field} يجب أن يكون رقماً");
        }
        return $this;
    }

    /**
     * Validate integer value
     */
    public function integer($field, $message = null) {
        $value = $this->getValue($field);
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->addError($field, $message ?? "حقل {$field} يجب أن يكون عدداً صحيحاً");
        }
        return $this;
    }

    /**
     * Validate minimum value
     */
    public function minValue($field, $min, $message = null) {
        $value = $this->getValue($field);
        if (!empty($value) && is_numeric($value) && $value < $min) {
            $this->addError($field, $message ?? "حقل {$field} يجب أن يكون {$min} على الأقل");
        }
        return $this;
    }

    /**
     * Validate maximum value
     */
    public function maxValue($field, $max, $message = null) {
        $value = $this->getValue($field);
        if (!empty($value) && is_numeric($value) && $value > $max) {
            $this->addError($field, $message ?? "حقل {$field} يجب ألا يتجاوز {$max}");
        }
        return $this;
    }

    /**
     * Validate value in list
     */
    public function in($field, $allowed, $message = null) {
        $value = $this->getValue($field);
        if (!empty($value) && !in_array($value, $allowed)) {
            $this->addError($field, $message ?? "قيمة {$field} غير مسموحة");
        }
        return $this;
    }

    /**
     * Validate matching fields
     */
    public function matches($field, $matchField, $message = null) {
        $value = $this->getValue($field);
        $matchValue = $this->getValue($matchField);
        if ($value !== $matchValue) {
            $this->addError($field, $message ?? "حقل {$field} غير متطابق");
        }
        return $this;
    }

    /**
     * Validate unique value in database
     */
    public function unique($field, $table, $column = null, $exceptId = null, $message = null) {
        $value = $this->getValue($field);
        $column = $column ?? $field;

        if (!empty($value)) {
            $pdo = db();
            $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
            $params = [$value];

            if ($exceptId) {
                $sql .= " AND id != ?";
                $params[] = $exceptId;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            if ($stmt->fetchColumn() > 0) {
                $this->addError($field, $message ?? "قيمة {$field} مستخدمة مسبقاً");
            }
        }
        return $this;
    }

    /**
     * Validate phone number
     */
    public function phone($field, $message = null) {
        $value = $this->getValue($field);
        if (!empty($value) && !preg_match('/^[0-9+\-\s\(\)]{8,20}$/', $value)) {
            $this->addError($field, $message ?? 'رقم الهاتف غير صالح');
        }
        return $this;
    }

    /**
     * Validate URL
     */
    public function url($field, $message = null) {
        $value = $this->getValue($field);
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, $message ?? 'الرابط غير صالح');
        }
        return $this;
    }

    /**
     * Validate date format
     */
    public function date($field, $format = 'Y-m-d', $message = null) {
        $value = $this->getValue($field);
        if (!empty($value)) {
            $d = DateTime::createFromFormat($format, $value);
            if (!$d || $d->format($format) !== $value) {
                $this->addError($field, $message ?? 'صيغة التاريخ غير صالحة');
            }
        }
        return $this;
    }

    /**
     * Custom validation rule
     */
    public function custom($field, $callback, $message) {
        $value = $this->getValue($field);
        if (!$callback($value, $this->data)) {
            $this->addError($field, $message);
        }
        return $this;
    }

    /**
     * Get field value
     */
    private function getValue($field) {
        return $this->data[$field] ?? null;
    }

    /**
     * Add error
     */
    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Check if validation failed
     */
    public function fails() {
        return !empty($this->errors);
    }

    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function errors() {
        return $this->errors;
    }

    /**
     * Get first error for a field
     */
    public function firstError($field = null) {
        if ($field) {
            return $this->errors[$field][0] ?? null;
        }
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }
        return null;
    }

    /**
     * Validate and respond if failed
     */
    public function validate() {
        if ($this->fails()) {
            Response::validationError($this->errors);
        }
    }

    /**
     * Sanitize string
     */
    public static function sanitize($value) {
        if (is_string($value)) {
            return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
        }
        return $value;
    }

    /**
     * Sanitize array
     */
    public static function sanitizeArray($data) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value);
            } else {
                $sanitized[$key] = self::sanitize($value);
            }
        }
        return $sanitized;
    }
}
