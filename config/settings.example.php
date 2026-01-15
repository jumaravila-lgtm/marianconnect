<?php
/**
 * MARIANCONNECT - Site Settings & Constants
 */

// Error Reporting (Set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Manila');

// Site Configuration
define('SITE_NAME', 'St. Mary\'s College of Catbalogan');
define('SITE_TAGLINE', 'Excellence in Catholic Education');
define('SITE_URL', 'http://localhost/marianconnect'); // Change to https://smcc.edu.ph in production
define('ADMIN_URL', SITE_URL . '/admin');

// Path Configuration
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/assets/uploads');
define('UPLOAD_URL', SITE_URL . '/assets/uploads');

// Database Configuration (fallback if database.php fails)
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'root');
define('DB_PASS', '');

// Security Settings
define('SESSION_LIFETIME', 7200); // 2 hours in seconds
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutes

// Upload Settings
define('MAX_UPLOAD_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_DOCUMENT_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// Pagination
define('ITEMS_PER_PAGE', 12);
define('ADMIN_ITEMS_PER_PAGE', 25);

// Image Dimensions
define('THUMBNAIL_WIDTH', 300);
define('THUMBNAIL_HEIGHT', 200);
define('MEDIUM_WIDTH', 800);
define('MEDIUM_HEIGHT', 600);

// Email Configuration (for contact form notifications)
define('ADMIN_EMAIL', 'your_admin_email@example.com');
define('FROM_EMAIL', 'noreply@example.com');
define('FROM_NAME', SITE_NAME);

// Social Media Links (can be overridden by database settings)
define('FACEBOOK_URL', 'https://facebook.com/smcc');
define('TWITTER_URL', '');
define('INSTAGRAM_URL', '');
define('YOUTUBE_URL', '');

// Google Services
define('GOOGLE_ANALYTICS_ID', ''); // Add your GA4 tracking ID
define('GOOGLE_MAPS_API_KEY', ''); // Add your Google Maps API key
define('RECAPTCHA_SITE_KEY', ''); // Add your reCAPTCHA site key
define('RECAPTCHA_SECRET_KEY', ''); // Add your reCAPTCHA secret key

// Maintenance Mode
define('MAINTENANCE_MODE', false);
define('MAINTENANCE_MESSAGE', 'Website is currently under maintenance. Please check back soon.');

// Cache Settings
define('CACHE_ENABLED', false);
define('CACHE_DURATION', 3600); // 1 hour

// API Settings
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 100); // requests per minute
define('API_RATE_LIMIT_WINDOW', 60); // seconds

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1); // Set to 1 in production with HTTPS
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_lifetime', SESSION_LIFETIME);
    session_name('MARIANCONNECT_SESSION');
    session_start();
}

// Auto-logout on session timeout
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_LIFETIME)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['LAST_ACTIVITY'] = time();

/**
 * Helper function to get full URL
 */
function url($path = '') {
    return SITE_URL . '/' . ltrim($path, '/');
}

/**
 * Helper function to get asset URL
 */
function asset($path = '') {
    return SITE_URL . '/assets/' . ltrim($path, '/');
}

/**
 * Helper function to get upload URL
 */
function upload($path = '') {
    return UPLOAD_URL . '/' . ltrim($path, '/');
}

/**
 * Check if site is in maintenance mode
 */
function checkMaintenanceMode() {
    if (MAINTENANCE_MODE && !isLoggedIn()) {
        include ROOT_PATH . '/pages/maintenance.php';
        exit;
    }
}
?>
