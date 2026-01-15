<?php
/**
 * MARIANCONNECT - Admin Logout
 */

session_start();

// Load required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Log logout activity if user is logged in
if (isset($_SESSION['admin_id'])) {
    try {
        logActivity($_SESSION['admin_id'], 'logout', null, null, 'Admin logged out');
    } catch (Exception $e) {
        error_log("Logout logging error: " . $e->getMessage());
    }
}

// Clear all session data
$_SESSION = array();

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>
