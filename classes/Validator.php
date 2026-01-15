<?php
/**
 * MARIANCONNECT - Validator Class
 * Comprehensive form validation with multiple rules
 */

class Validator {
    
    private $errors = [];
    private $data = [];
    
    /**
     * Constructor
     * 
     * @param array $data Data to validate (typically $_POST)
     */
    public function __construct($data = []) {
        $this->data = $data;
        $this->errors = [];
    }
    
    /**
     * Validate required field
     * 
     * @param string $field Field name
     * @param string $label Display label for error messages
     * @return self
     */
    public function required($field, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field][] = "{$label} is required";
        }
        
        return $this;
    }
    
    /**
     * Validate email format
     * 
     * @param string $field Field name
     * @param string $label Display label
     * @return self
     */
    public function email($field, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field][] = "{$label} must be a valid email address";
            }
        }
        
        return $this;
    }
    
    /**
     * Validate minimum length
     * 
     * @param string $field Field name
     * @param int $length Minimum length
     * @param string $label Display label
     * @return self
     */
    public function minLength($field, $length, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field][] = "{$label} must be at least {$length} characters long";
        }
        
        return $this;
    }
    
    /**
     * Validate maximum length
     * 
     * @param string $field Field name
     * @param int $length Maximum length
     * @param string $label Display label
     * @return self
     */
    public function maxLength($field, $length, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field][] = "{$label} must not exceed {$length} characters";
        }
        
        return $this;
    }
    
    /**
     * Validate exact length
     * 
     * @param string $field Field name
     * @param int $length Exact length
     * @param string $label Display label
     * @return self
     */
    public function exactLength($field, $length, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (isset($this->data[$field]) && strlen($this->data[$field]) !== $length) {
            $this->errors[$field][] = "{$label} must be exactly {$length} characters long";
        }
        
        return $this;
    }
    
    /**
     * Validate numeric value
     * 
     * @param string $field Field name
     * @param string $label Display label
     * @return self
     */
    public function numeric($field, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!is_numeric($this->data[$field])) {
                $this->errors[$field][] = "{$label} must be a number";
            }
        }
        
        return $this;
    }
    
    /**
     * Validate integer value
     * 
     * @param string $field Field name
     * @param string $label Display label
     * @return self
     */
    public function integer($field, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
                $this->errors[$field][] = "{$label} must be an integer";
            }
        }
        
        return $this;
    }
    
    /**
     * Validate minimum value
     * 
     * @param string $field Field name
     * @param float $min Minimum value
     * @param string $label Display label
     * @return self
     */
    public function min($field, $min, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (isset($this->data[$field]) && is_numeric($this->data[$field])) {
            if ($this->data[$field] < $min) {
                $this->errors[$field][] = "{$label} must be at least {$min}";
            }
        }
        
        return $this;
    }
    
    /**
     * Validate maximum value
     * 
     * @param string $field Field name
     * @param float $max Maximum value
     * @param string $label Display label
     * @return self
     */
    public function max($field, $max, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (isset($this->data[$field]) && is_numeric($this->data[$field])) {
            if ($this->data[$field] > $max) {
                $this->errors[$field][] = "{$label} must not exceed {$max}";
            }
        }
        
        return $this;
    }
    
    /**
     * Validate URL format
     * 
     * @param string $field Field name
     * @param string $label Display label
     * @return self
     */
    public function url($field, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
                $this->errors[$field][] = "{$label} must be a valid URL";
            }
        }
        
        return $this;
    }
    
    /**
     * Validate date format (Y-m-d)
     * 
     * @param string $field Field name
     * @param string $label Display label
     * @return self
     */
    public function date($field, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            $d = DateTime::createFromFormat('Y-m-d', $this->data[$field]);
            if (!$d || $d->format('Y-m-d') !== $this->data[$field]) {
                $this->errors[$field][] = "{$label} must be a valid date (YYYY-MM-DD)";
            }
        }
        
        return $this;
    }
    
    /**
     * Validate datetime format (Y-m-d H:i:s)
     * 
     * @param string $field Field name
     * @param string $label Display label
     * @return self
     */
    public function datetime($field, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            $d = DateTime::createFromFormat('Y-m-d H:i:s', $this->data[$field]);
            if (!$d || $d->format('Y-m-d H:i:s') !== $this->data[$field]) {
                $this->errors[$field][] = "{$label} must be a valid datetime (YYYY-MM-DD HH:MM:SS)";
            }
        }
        
        return $this;
    }
    
    /**
     * Validate time format (H:i:s)
     * 
     * @param string $field Field name
     * @param string $label Display label
     * @return self
     */
    public function time($field, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            $t = DateTime::createFromFormat('H:i:s', $this->data[$field]);
            if (!$t || $t->format('H:i:s') !== $this->data[$field]) {
                // Try without seconds
                $t = DateTime::createFromFormat('H:i', $this->data[$field]);
                if (!$t || $t->format('H:i') !== $this->data[$field]) {
                    $this->errors[$field][] = "{$label} must be a valid time (HH:MM or HH:MM:SS)";
                }
            }
        }
        
        return $this;
    }
    
    /**
     * Validate phone number (Philippine format)
     * 
     * @param string $field Field name
     * @param string $label Display label
     * @return self
     */
    public function phone($field, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            // Philippine phone formats: 09XX-XXX-XXXX or +639XX-XXX-XXXX
            $pattern = '/^(09|\+639)\d{9}$/';
            $cleanPhone = preg_replace('/[^0-9+]/', '', $this->data[$field]);
            
            if (!preg_match($pattern, $cleanPhone)) {
                $this->errors[$field][] = "{$label} must be a valid Philippine phone number";
            }
        }
        
        return $this;
    }
    
    /**
     * Validate alphabetic characters only
     * 
     * @param string $field Field name
     * @param string $label Display label
     * @return self
     */
    public function alpha($field, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!preg_match('/^[a-zA-Z\s]+$/', $this->data[$field])) {
                $this->errors[$field][] = "{$label} must contain only letters and spaces";
            }
        }
        
        return $this;
    }
    
    /**
     * Validate alphanumeric characters
     * 
     * @param string $field Field name
     * @param string $label Display label
     * @return self
     */
    public function alphanumeric($field, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!preg_match('/^[a-zA-Z0-9\s]+$/', $this->data[$field])) {
                $this->errors[$field][] = "{$label} must contain only letters, numbers, and spaces";
            }
        }
        
        return $this;
    }
    
    /**
     * Validate field matches another field (e.g., password confirmation)
     * 
     * @param string $field Field name
     * @param string $matchField Field to match against
     * @param string $label Display label
     * @return self
     */
    public function matches($field, $matchField, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $matchLabel = ucfirst(str_replace('_', ' ', $matchField));
        
        if (isset($this->data[$field]) && isset($this->data[$matchField])) {
            if ($this->data[$field] !== $this->data[$matchField]) {
                $this->errors[$field][] = "{$label} must match {$matchLabel}";
            }
        }
        
        return $this;
    }
    
    /**
     * Validate value is in allowed list
     * 
     * @param string $field Field name
     * @param array $allowed Allowed values
     * @param string $label Display label
     * @return self
     */
    public function in($field, $allowed, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!in_array($this->data[$field], $allowed)) {
                $this->errors[$field][] = "{$label} must be one of: " . implode(', ', $allowed);
            }
        }
        
        return $this;
    }
    
    /**
     * Validate with custom regex pattern
     * 
     * @param string $field Field name
     * @param string $pattern Regex pattern
     * @param string $message Custom error message
     * @param string $label Display label
     * @return self
     */
    public function pattern($field, $pattern, $message = null, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $message = $message ?? "{$label} format is invalid";
        
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!preg_match($pattern, $this->data[$field])) {
                $this->errors[$field][] = $message;
            }
        }
        
        return $this;
    }
    
    /**
     * Custom validation rule
     * 
     * @param string $field Field name
     * @param callable $callback Callback function that returns true if valid
     * @param string $message Error message
     * @return self
     */
    public function custom($field, $callback, $message) {
        if (isset($this->data[$field])) {
            if (!call_user_func($callback, $this->data[$field])) {
                $this->errors[$field][] = $message;
            }
        }
        
        return $this;
    }
    
    /**
     * Validate file upload
     * 
     * @param string $field Field name in $_FILES
     * @param array $allowedTypes Allowed MIME types
     * @param int $maxSize Maximum file size in bytes
     * @param string $label Display label
     * @return self
     */
    public function file($field, $allowedTypes = [], $maxSize = 5242880, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES[$field];
            
            // Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->errors[$field][] = "{$label} upload failed";
                return $this;
            }
            
            // Check file size
            if ($file['size'] > $maxSize) {
                $maxSizeMB = round($maxSize / 1048576, 2);
                $this->errors[$field][] = "{$label} must not exceed {$maxSizeMB}MB";
            }
            
            // Check file type
            if (!empty($allowedTypes)) {
                $fileType = mime_content_type($file['tmp_name']);
                if (!in_array($fileType, $allowedTypes)) {
                    $this->errors[$field][] = "{$label} must be one of these types: " . implode(', ', $allowedTypes);
                }
            }
        }
        
        return $this;
    }
    
    /**
     * Check if validation passed
     * 
     * @return bool
     */
    public function passes() {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed
     * 
     * @return bool
     */
    public function fails() {
        return !empty($this->errors);
    }
    
    /**
     * Get all errors
     * 
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get errors for specific field
     * 
     * @param string $field Field name
     * @return array
     */
    public function getFieldErrors($field) {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * Get first error for field
     * 
     * @param string $field Field name
     * @return string|null
     */
    public function getFirstError($field) {
        return $this->errors[$field][0] ?? null;
    }
    
    /**
     * Get all errors as flat array
     * 
     * @return array
     */
    public function getAllErrorsFlat() {
        $flat = [];
        foreach ($this->errors as $fieldErrors) {
            $flat = array_merge($flat, $fieldErrors);
        }
        return $flat;
    }
    
    /**
     * Add custom error
     * 
     * @param string $field Field name
     * @param string $message Error message
     * @return self
     */
    public function addError($field, $message) {
        $this->errors[$field][] = $message;
        return $this;
    }
}
?>
