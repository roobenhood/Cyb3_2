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
     * Validate field matches another field
     */
    public function matches($field, $matchField, $message = null) {
        $value = $this->getValue($field);
        $matchValue = $this->getValue($matchField);
        if (!empty($value) && $value !== $matchValue) {
            $this->addError($field, $message ?? "حقل {$field} غير متطابق");
        }
        return $this;
    }

    /**
     * Validate unique value in database
     */
    public function unique($field, $table, $column, $exceptId = null, $message = null) {
        $value = $this->getValue($field);
        if (!empty($value)) {
            $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
            $params = [$value];

            if ($exceptId) {
                $sql .= " AND id != ?";
                $params[] = $exceptId;
            }

            $stmt = db()->prepare($sql);
            $stmt->execute($params);

            if ($stmt->fetchColumn() > 0) {
                $this->addError($field, $message ?? "قيمة {$field} مستخدمة مسبقاً");
            }
        }
        return $this;
    }

    /**
     * Validate exists in database
     */
    public function exists($field, $table, $column, $message = null) {
        $value = $this->getValue($field);
        if (!empty($value)) {
            $stmt = db()->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
            $stmt->execute([$value]);

            if ($stmt->fetchColumn() == 0) {
                $this->addError($field, $message ?? "قيمة {$field} غير موجودة");
            }
        }
        return $this;
    }

    /**
     * Validate in array
     */
    public function in($field, $values, $message = null) {
        $value = $this->getValue($field);
        if (!empty($value) && !in_array($value, $values)) {
            $this->addError($field, $message ?? "قيمة {$field} غير صالحة");
        }
        return $this;
    }

    /**
     * Validate URL
     */
    public function url($field, $message = null) {
        $value = $this->getValue($field);
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, $message ?? "رابط غير صالح");
        }
        return $this;
    }

    /**
     * Validate phone number (Saudi format)
     */
    public function phone($field, $message = null) {
        $value = $this->getValue($field);
        if (!empty($value)) {
            $value = preg_replace('/[^0-9]/', '', $value);
            if (!preg_match('/^(05|5|9665|\\+9665)\d{8}$/', $value)) {
                $this->addError($field, $message ?? "رقم الهاتف غير صالح");
            }
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
                $this->addError($field, $message ?? "تنسيق التاريخ غير صالح");
            }
        }
        return $this;
    }

    /**
     * Custom validation rule
     */
    public function custom($field, callable $callback, $message = null) {
        $value = $this->getValue($field);
        if (!$callback($value, $this->data)) {
            $this->addError($field, $message ?? "حقل {$field} غير صالح");
        }
        return $this;
    }

    /**
     * Get field value from data
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
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails() {
        return !$this->passes();
    }

    /**
     * Get all errors
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Get first error message
     */
    public function getFirstError() {
        foreach ($this->errors as $field => $messages) {
            return $messages[0];
        }
        return null;
    }

    /**
     * Validate and return response if failed
     */
    public function validate() {
        if ($this->fails()) {
            require_once __DIR__ . '/Response.php';
            Response::validationError($this->getFirstError(), $this->errors);
        }
    }
}
