<?php

namespace App\Helpers;

use App\Core\ApiResponse;
use App\Database\Connection;

/**
 * Validator Helper
 * مساعد التحقق من المدخلات
 */
class Validator
{
    private array $errors = [];
    private array $data = [];
    
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    
    /**
     * التحقق من البيانات
     */
    public function validate(array $rules): bool
    {
        foreach ($rules as $field => $fieldRules) {
            $rulesArray = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);
            
            foreach ($rulesArray as $rule) {
                $this->applyRule($field, $rule);
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * تطبيق القاعدة
     */
    private function applyRule(string $field, string $rule): void
    {
        $value = $this->data[$field] ?? null;
        
        // تقسيم القاعدة والمعاملات
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $ruleParam = $parts[1] ?? null;
        
        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0' && $value !== 0) {
                    $this->addError($field, 'الحقل مطلوب');
                }
                break;
                
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'البريد الإلكتروني غير صالح');
                }
                break;
                
            case 'min':
                if (!empty($value) && strlen($value) < (int)$ruleParam) {
                    $this->addError($field, "يجب أن يكون على الأقل {$ruleParam} حرف");
                }
                break;
                
            case 'max':
                if (!empty($value) && strlen($value) > (int)$ruleParam) {
                    $this->addError($field, "يجب أن لا يتجاوز {$ruleParam} حرف");
                }
                break;
                
            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, 'يجب أن يكون رقماً');
                }
                break;
                
            case 'integer':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, 'يجب أن يكون رقماً صحيحاً');
                }
                break;
                
            case 'min_value':
                if (!empty($value) && (float)$value < (float)$ruleParam) {
                    $this->addError($field, "يجب أن يكون على الأقل {$ruleParam}");
                }
                break;
                
            case 'max_value':
                if (!empty($value) && (float)$value > (float)$ruleParam) {
                    $this->addError($field, "يجب أن لا يتجاوز {$ruleParam}");
                }
                break;
                
            case 'matches':
                if (!empty($value) && $value !== ($this->data[$ruleParam] ?? null)) {
                    $this->addError($field, 'الحقلان غير متطابقان');
                }
                break;
                
            case 'unique':
                if (!empty($value)) {
                    $params = explode(',', $ruleParam);
                    $table = $params[0];
                    $column = $params[1] ?? $field;
                    $exceptId = $params[2] ?? null;
                    
                    if ($this->checkUnique($table, $column, $value, $exceptId)) {
                        $this->addError($field, 'القيمة موجودة مسبقاً');
                    }
                }
                break;
                
            case 'exists':
                if (!empty($value)) {
                    $params = explode(',', $ruleParam);
                    $table = $params[0];
                    $column = $params[1] ?? 'id';
                    
                    if (!$this->checkExists($table, $column, $value)) {
                        $this->addError($field, 'القيمة غير موجودة');
                    }
                }
                break;
                
            case 'in':
                if (!empty($value)) {
                    $allowed = explode(',', $ruleParam);
                    if (!in_array($value, $allowed)) {
                        $this->addError($field, 'قيمة غير صالحة');
                    }
                }
                break;
                
            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, 'الرابط غير صالح');
                }
                break;
                
            case 'phone':
                if (!empty($value) && !preg_match('/^[\+]?[0-9]{10,15}$/', $value)) {
                    $this->addError($field, 'رقم الهاتف غير صالح');
                }
                break;
                
            case 'date':
                if (!empty($value) && !strtotime($value)) {
                    $this->addError($field, 'التاريخ غير صالح');
                }
                break;
        }
    }
    
    /**
     * التحقق من التفرد
     */
    private function checkUnique(string $table, string $column, $value, ?string $exceptId = null): bool
    {
        $db = Connection::getInstance()->getConnection();
        
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
        $params = [$value];
        
        if ($exceptId) {
            $sql .= " AND id != ?";
            $params[] = $exceptId;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * التحقق من الوجود
     */
    private function checkExists(string $table, string $column, $value): bool
    {
        $db = Connection::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
        $stmt->execute([$value]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * إضافة خطأ
     */
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
    
    /**
     * الحصول على الأخطاء
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * الحصول على أول خطأ
     */
    public function getFirstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }
        return null;
    }
    
    /**
     * إرسال استجابة خطأ التحقق
     */
    public function sendErrors(): void
    {
        ApiResponse::validationError($this->getFirstError() ?? 'بيانات غير صالحة', $this->errors);
    }
}
