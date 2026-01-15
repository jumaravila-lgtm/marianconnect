<?php
/**
 * MARIANCONNECT - Authentication Check
 * Include this file at the top of every admin page that requires authentication
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load required files (adjust path based on where this is included from)
$baseDir = dirname(dirname(__DIR__));
if (!function_exists('isLoggedIn')) {
    require_once $baseDir . '/config/database.php';
    require_once $baseDir . '/config/settings.php';
    require_once $baseDir . '/config/security.php';
    require_once $baseDir . '/includes/functions.php';
}

// Check if user is logged in
if (!isLoggedIn()) {
    // Store the requested URL for redirect after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Redirect to login page
    header("Location: " . SITE_URL . "/admin/login.php");
    exit();
}

// Update last activity time
$_SESSION['LAST_ACTIVITY'] = time();
?>
