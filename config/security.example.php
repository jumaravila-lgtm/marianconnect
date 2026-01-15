<?php
/**
 * MARIANCONNECT - Security Functions
 */

/**
 * Sanitize HTML output
 */
function escapeHtml($data) {
    // Handle null or empty
    if ($data === null || $data === '') {
        return '';
    }
    
    // Handle arrays recursively
    if (is_array($data)) {
        return array_map('escapeHtml', $data);
    }
    
    // Handle objects
    if (is_object($data)) {
        return $data; // Return as-is or convert to string if needed
    }
    
    // Convert to string and escape
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize for JavaScript
 */
function escapeJs($string) {
    return json_encode($string);
}

/**
 * Sanitize URL
 */
function escapeUrl($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}

/**
 * Generate secure random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 2
    ]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Validate password strength
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long";
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
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return $errors;
}

/**
 * Prevent SQL Injection (Use PDO prepared statements instead)
 */
function sanitizeSQL($string) {
    return strip_tags(trim($string));
}

/**
 * Prevent XSS attacks
 */
function cleanXSS($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowedTypes = ALLOWED_IMAGE_TYPES, $maxSize = MAX_UPLOAD_SIZE) {
    $errors = [];
    
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        $errors[] = "No file uploaded";
        return $errors;
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "File upload error: " . $file['error'];
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        $errors[] = "File size exceeds maximum allowed size of " . formatBytes($maxSize);
    }
    
    // Check file type
    $fileType = mime_content_type($file['tmp_name']);
    if (!in_array($fileType, $allowedTypes)) {
        $errors[] = "Invalid file type. Allowed types: " . implode(', ', $allowedTypes);
    }
    
    // Check if file is actually an image (for image uploads)
    if (in_array($fileType, ALLOWED_IMAGE_TYPES)) {
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $errors[] = "File is not a valid image";
        }
    }
    
    return $errors;
}

/**
 * Secure file upload
 */
function secureFileUpload($file, $destination, $allowedTypes = ALLOWED_IMAGE_TYPES) {
    $errors = validateFileUpload($file, $allowedTypes);
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $destination . '/' . $filename;
    
    // Create directory if it doesn't exist
    if (!file_exists($destination)) {
        mkdir($destination, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => str_replace(ROOT_PATH, SITE_URL, $filepath)
        ];
    }
    
    return ['success' => false, 'errors' => ['Failed to move uploaded file']];
}

/**
 * Rate limiting
 */
function checkRateLimit($identifier, $limit = 10, $window = 60) {
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    $now = time();
    $key = md5($identifier);
    
    // Initialize or clean old entries
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = [];
    }
    
    // Remove old timestamps
    $_SESSION['rate_limit'][$key] = array_filter(
        $_SESSION['rate_limit'][$key],
        function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        }
    );
    
    // Check if limit exceeded
    if (count($_SESSION['rate_limit'][$key]) >= $limit) {
        return false;
    }
    
    // Add current timestamp
    $_SESSION['rate_limit'][$key][] = $now;
    
    return true;
}

/**
 * Prevent brute force attacks
 */
function checkLoginAttempts($username) {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    
    $now = time();
    $key = md5($username . getClientIP());
    
    // Initialize if not exists
    if (!isset($_SESSION['login_attempts'][$key])) {
        $_SESSION['login_attempts'][$key] = [
            'count' => 0,
            'locked_until' => 0
        ];
    }
    
    // Check if account is locked
    if ($_SESSION['login_attempts'][$key]['locked_until'] > $now) {
        $remaining = $_SESSION['login_attempts'][$key]['locked_until'] - $now;
        return [
            'allowed' => false,
            'message' => "Too many login attempts. Please try again in " . ceil($remaining / 60) . " minutes."
        ];
    }
    
    // Reset if lock period has passed
    if ($_SESSION['login_attempts'][$key]['locked_until'] > 0 && $_SESSION['login_attempts'][$key]['locked_until'] <= $now) {
        $_SESSION['login_attempts'][$key] = [
            'count' => 0,
            'locked_until' => 0
        ];
    }
    
    return ['allowed' => true];
}

/**
 * Record failed login attempt
 */
function recordFailedLogin($username) {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    
    $key = md5($username . getClientIP());
    
    if (!isset($_SESSION['login_attempts'][$key])) {
        $_SESSION['login_attempts'][$key] = [
            'count' => 0,
            'locked_until' => 0
        ];
    }
    
    $_SESSION['login_attempts'][$key]['count']++;
    
    // Lock account after max attempts
    if ($_SESSION['login_attempts'][$key]['count'] >= MAX_LOGIN_ATTEMPTS) {
        $_SESSION['login_attempts'][$key]['locked_until'] = time() + LOGIN_TIMEOUT;
    }
}

/**
 * Reset login attempts after successful login
 */
function resetLoginAttempts($username) {
    $key = md5($username . getClientIP());
    if (isset($_SESSION['login_attempts'][$key])) {
        unset($_SESSION['login_attempts'][$key]);
    }
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Format bytes to human readable
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Verify reCAPTCHA
 */
function verifyRecaptcha($token) {
    if (empty(RECAPTCHA_SECRET_KEY)) {
        return true; // Skip if not configured
    }
    
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $token,
        'remoteip' => getClientIP()
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $resultJson = json_decode($result, true);
    
    return isset($resultJson['success']) && $resultJson['success'] === true;
}

/**
 * Log security event
 */
function logSecurityEvent($event, $details = '') {
    $logFile = ROOT_PATH . '/logs/security.log';
    $logDir = dirname($logFile);
    
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = getClientIP();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $username = $_SESSION['username'] ?? 'Guest';
    
    $logEntry = "[{$timestamp}] {$event} | User: {$username} | IP: {$ip} | Details: {$details} | UA: {$userAgent}\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
?>
