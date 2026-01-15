<?php
/**
 * MARIANCONNECT - Security Class
 * Handles XSS protection, CSRF tokens, input sanitization, and password hashing
 */

class Security {
    
    /**
     * Escape HTML to prevent XSS attacks
     * 
     * @param string $string Input string
     * @return string Escaped string
     */
    public static function escapeHtml($string) {
        if ($string === null || $string === '') {
            return '';
        }
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize input data
     * Removes extra spaces, slashes, and escapes special characters
     * 
     * @param mixed $data Input data (string or array)
     * @return mixed Sanitized data
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    /**
     * Clean input for database (additional layer)
     * Use with PDO prepared statements
     * 
     * @param string $string Input string
     * @return string Cleaned string
     */
    public static function cleanInput($string) {
        $string = trim($string);
        $string = stripslashes($string);
        $string = strip_tags($string);
        return $string;
    }
    
    /**
     * Generate CSRF token
     * 
     * @return string CSRF token
     */
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     * 
     * @param string $token Token to verify
     * @return bool True if valid, false otherwise
     */
    public static function verifyCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get CSRF token field for forms
     * 
     * @return string HTML input field
     */
    public static function getCSRFField() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
    
    /**
     * Hash password using bcrypt
     * 
     * @param string $password Plain text password
     * @return string Hashed password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    }
    
    /**
     * Verify password against hash
     * 
     * @param string $password Plain text password
     * @param string $hash Hashed password from database
     * @return bool True if password matches
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Validate password strength
     * 
     * @param string $password Password to validate
     * @param int $minLength Minimum length (default: 8)
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validatePasswordStrength($password, $minLength = 8) {
        $errors = [];
        
        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters long";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Prevent SQL Injection (reminder to use PDO)
     * This is just a utility - ALWAYS use PDO prepared statements
     * 
     * @param string $string Input string
     * @return string Escaped string
     */
    public static function escapeSql($string) {
        // Note: This is NOT a replacement for prepared statements
        // Always use PDO prepared statements for queries
        return addslashes($string);
    }
    
    /**
     * Check if request is POST
     * 
     * @return bool
     */
    public static function isPostRequest() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    /**
     * Check if request is GET
     * 
     * @return bool
     */
    public static function isGetRequest() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
    
    /**
     * Check if request is AJAX
     * 
     * @return bool
     */
    public static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Get client IP address
     * 
     * @return string
     */
    public static function getClientIP() {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Get first IP if multiple are provided
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                // Validate IP
                if (filter_var(trim($ip), FILTER_VALIDATE_IP)) {
                    return trim($ip);
                }
            }
        }
        
        return 'UNKNOWN';
    }
    
    /**
     * Rate limiting check for login attempts
     * 
     * @param string $identifier Unique identifier (email, username, or IP)
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $timeWindow Time window in seconds (default: 15 minutes)
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_time' => int]
     */
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 900) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'rate_limit_' . md5($identifier);
        $now = time();
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'first_attempt' => $now,
                'reset_time' => $now + $timeWindow
            ];
        }
        
        $data = $_SESSION[$key];
        
        // Reset if time window has passed
        if ($now >= $data['reset_time']) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'first_attempt' => $now,
                'reset_time' => $now + $timeWindow
            ];
            $data = $_SESSION[$key];
        }
        
        $remaining = $maxAttempts - $data['attempts'];
        $allowed = $remaining > 0;
        
        return [
            'allowed' => $allowed,
            'remaining' => max(0, $remaining),
            'reset_time' => $data['reset_time']
        ];
    }
    
    /**
     * Record login attempt
     * 
     * @param string $identifier Unique identifier
     */
    public static function recordLoginAttempt($identifier) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'rate_limit_' . md5($identifier);
        
        if (isset($_SESSION[$key])) {
            $_SESSION[$key]['attempts']++;
        }
    }
    
    /**
     * Reset rate limit for identifier
     * 
     * @param string $identifier Unique identifier
     */
    public static function resetRateLimit($identifier) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'rate_limit_' . md5($identifier);
        unset($_SESSION[$key]);
    }
    
    /**
     * Generate secure random token
     * 
     * @param int $length Length of token
     * @return string
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Validate email format
     * 
     * @param string $email Email address
     * @return bool
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate URL format
     * 
     * @param string $url URL to validate
     * @return bool
     */
    public static function validateUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Prevent clickjacking by setting X-Frame-Options header
     */
    public static function preventClickjacking() {
        header('X-Frame-Options: SAMEORIGIN');
    }
    
    /**
     * Set secure headers
     */
    public static function setSecureHeaders() {
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // Enforce HTTPS (uncomment when you have SSL)
        // header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    
    /**
     * Safely redirect to URL
     * 
     * @param string $url URL to redirect to
     * @param int $statusCode HTTP status code (default: 302)
     */
    public static function redirect($url, $statusCode = 302) {
        // Validate URL to prevent open redirect vulnerability
        if (!self::isInternalUrl($url)) {
            $url = '/'; // Redirect to home if external
        }
        
        header('Location: ' . $url, true, $statusCode);
        exit();
    }
    
    /**
     * Check if URL is internal (same domain)
     * 
     * @param string $url URL to check
     * @return bool
     */
    private static function isInternalUrl($url) {
        // Relative URLs are always internal
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            return true;
        }
        
        // Check if URL starts with current domain
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $currentDomain = $scheme . '://' . $host;
        
        return strpos($url, $currentDomain) === 0;
    }
}
?>
