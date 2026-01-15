<?php
/**
 * MARIANCONNECT - Authentication API
 * Handle login, logout, and session checks via AJAX
 */

header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../classes/Security.php';

// Set secure headers
Security::setSecureHeaders();

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $db = getDB();
    
    // Only allow POST requests
    if (!Security::isPostRequest()) {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    
    // Get action
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'login':
            // Verify CSRF token
            if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid CSRF token']);
                exit;
            }
            
            // Get credentials
            $username = Security::sanitize($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Username and password are required'
                ]);
                exit;
            }
            
            // Check rate limiting
            $rateLimit = Security::checkRateLimit($username, 5, 900); // 5 attempts per 15 minutes
            
            if (!$rateLimit['allowed']) {
                $resetTime = date('H:i:s', $rateLimit['reset_time']);
                echo json_encode([
                    'success' => false,
                    'error' => "Too many login attempts. Please try again after {$resetTime}"
                ]);
                exit;
            }
            
            // Get user from database
            $sql = "SELECT * FROM admin_users WHERE username = :username AND is_active = 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                Security::recordLoginAttempt($username);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid username or password',
                    'remaining_attempts' => $rateLimit['remaining'] - 1
                ]);
                exit;
            }
            
            // Verify password
            if (!Security::verifyPassword($password, $user['password_hash'])) {
                Security::recordLoginAttempt($username);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid username or password',
                    'remaining_attempts' => $rateLimit['remaining'] - 1
                ]);
                exit;
            }
            
            // Login successful - Reset rate limit
            Security::resetRateLimit($username);
            
            // Set session variables
            $_SESSION['admin_id'] = $user['admin_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            // Update last login
            $updateSql = "UPDATE admin_users SET last_login = NOW() WHERE admin_id = :id";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([':id' => $user['admin_id']]);
            
            // Log activity
            try {
                require_once '../includes/functions.php';
                logActivity($user['admin_id'], 'login', null, null, 'Admin logged in successfully');
            } catch (Exception $e) {
                // Silent fail for logging
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['admin_id'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role']
                ],
                'redirect' => '/marianconnect/admin/index.php'
            ]);
            break;
            
        case 'logout':
            // Verify CSRF token
            if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid CSRF token']);
                exit;
            }
            
            // Log activity before destroying session
            if (isset($_SESSION['admin_id'])) {
                try {
                    require_once '../includes/functions.php';
                    logActivity($_SESSION['admin_id'], 'logout', null, null, 'Admin logged out');
                } catch (Exception $e) {
                    // Silent fail for logging
                }
            }
            
            // Destroy session
            session_destroy();
            
            echo json_encode([
                'success' => true,
                'message' => 'Logout successful',
                'redirect' => '/marianconnect/admin/login.php'
            ]);
            break;
            
        case 'check_session':
            // Check if user is logged in
            $isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
            
            echo json_encode([
                'success' => true,
                'logged_in' => $isLoggedIn,
                'user' => $isLoggedIn ? [
                    'id' => $_SESSION['admin_id'],
                    'username' => $_SESSION['username'],
                    'full_name' => $_SESSION['full_name'],
                    'role' => $_SESSION['role']
                ] : null
            ]);
            break;
            
        case 'change_password':
            // Change password for logged-in user
            if (!isset($_SESSION['admin_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }
            
            // Verify CSRF token
            if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid CSRF token']);
                exit;
            }
            
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validate inputs
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'All fields are required'
                ]);
                exit;
            }
            
            if ($newPassword !== $confirmPassword) {
                echo json_encode([
                    'success' => false,
                    'error' => 'New passwords do not match'
                ]);
                exit;
            }
            
            // Validate password strength
            $passwordValidation = Security::validatePasswordStrength($newPassword);
            if (!$passwordValidation['valid']) {
                echo json_encode([
                    'success' => false,
                    'error' => implode(', ', $passwordValidation['errors'])
                ]);
                exit;
            }
            
            // Get current user
            $sql = "SELECT password_hash FROM admin_users WHERE admin_id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $_SESSION['admin_id']]);
            $user = $stmt->fetch();
            
            // Verify current password
            if (!Security::verifyPassword($currentPassword, $user['password_hash'])) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Current password is incorrect'
                ]);
                exit;
            }
            
            // Update password
            $newPasswordHash = Security::hashPassword($newPassword);
            $updateSql = "UPDATE admin_users SET password_hash = :password WHERE admin_id = :id";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([
                ':password' => $newPasswordHash,
                ':id' => $_SESSION['admin_id']
            ]);
            
            // Log activity
            try {
                require_once '../includes/functions.php';
                logActivity($_SESSION['admin_id'], 'update', 'admin_users', $_SESSION['admin_id'], 'Changed password');
            } catch (Exception $e) {
                // Silent fail for logging
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
?>
