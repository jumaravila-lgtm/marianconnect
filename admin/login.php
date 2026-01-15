<?php
/**
 * MARIANCONNECT - Admin Login
 */

// Start session first
session_start();

// Load configuration and functions
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Check login attempts
        $loginCheck = checkLoginAttempts($username);
        if (!$loginCheck['allowed']) {
            $error = $loginCheck['message'];
        } else {
            // Validate inputs
            if (empty($username) || empty($password)) {
                $error = 'Username and password are required.';
            } else {
                try {
                    $db = getDB();
                    
                    // Fetch user
                    $stmt = $db->prepare("
                        SELECT admin_id, username, password_hash, full_name, role, is_active, avatar 
                        FROM admin_users 
                        WHERE (username = ? OR email = ?) AND is_active = 1
                    ");
                    $stmt->execute([$username, $username]);
                    $user = $stmt->fetch();
                    
                    if ($user && password_verify($password, $user['password_hash'])) {
                        // Set session variables
                        $_SESSION['admin_id'] = $user['admin_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['avatar'] = $user['avatar'];
                        $_SESSION['logged_in'] = true;
                        
                        // Update last login
                        $updateStmt = $db->prepare("UPDATE admin_users SET last_login = NOW() WHERE admin_id = ?");
                        $updateStmt->execute([$user['admin_id']]);
                        
                        // Reset login attempts
                        resetLoginAttempts($username);
                        
                        // Log activity
                        logActivity($user['admin_id'], 'login', null, null, 'Admin logged in successfully');
                        
                        // Redirect to dashboard
                        header("Location: index.php");
                        exit();
                    } else {
                        $error = 'Invalid username or password.';
                        recordFailedLogin($username);
                    }
                } catch (Exception $e) {
                    error_log("Login error: " . $e->getMessage());
                    $error = 'An error occurred. Please try again later.';
                }
            }
        }
    }
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - MARIANCONNECT</title>
    
    <link rel="icon" type="image/x-icon" href="../assets/images/logo/favicon.ico">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #003f87, #002855);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            background: linear-gradient(135deg, #003f87, #4a90e2);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        /* Logo container */
        .login-logo {
            width: 120px;                /* Adjust the container size */
            height: 120px;
            margin: 0 auto 20px;         /* Center horizontally and space below */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Logo image */
        .login-logo img {
            width: 120px;
            height: auto;
            display: block;
            margin: 0 auto 20px;
            border-radius: 20%;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }

        .login-logo img:hover {
            transform: scale(1.05);
        }
        
        .login-header h1 {
            font-size: 1.75rem;
            margin-bottom: 5px;
        }
        
        .login-header p {
            font-size: 0.95rem;
            opacity: 0.9;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #003f87;
            box-shadow: 0 0 0 3px rgba(0, 63, 135, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #003f87, #002855);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 63, 135, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .login-footer {
            padding: 20px 30px;
            background: #f8f9fa;
            text-align: center;
            font-size: 0.85rem;
            color: #666;
        }
        
        .login-footer a {
            color: #003f87;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle input {
            padding-right: 45px;
        }
        
        .toggle-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            user-select: none;
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: -10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .remember-me input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }
        
        .forgot-password {
            color: #003f87;
            text-decoration: none;
            font-weight: 500;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo"><img src="../assets/images/logo/logo-main.png" alt="MARIANCONNECT Logo"></div>
            <h1>MARIANCONNECT</h1>
            <p>Admin Panel Login</p>
        </div>
        
        <div class="login-body">
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label class="form-label" for="username">Username or Email</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        placeholder="Enter your username or email"
                        required
                        autofocus
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="password-toggle">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="Enter your password"
                            required
                        >
                        <span class="toggle-icon" onclick="togglePassword()">👁️</span>
                    </div>
                </div>
                
                <div class="remember-forgot">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" value="1">
                        <span>Remember me</span>
                    </label>
                </div>
                
                <button type="submit" class="btn">
                    Login to Dashboard
                </button>
            </form>
        </div>
        
        <div class="login-footer">
            <p>&copy; <?php echo date('Y'); ?> St. Mary's College of Catbalogan. All rights reserved.</p>
            <p><a href="../index.php">← Back to Main Website</a></p>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }
        
        // Auto-hide error message after 5 seconds
        const errorAlert = document.querySelector('.alert-danger');
        if (errorAlert) {
            setTimeout(() => {
                errorAlert.style.transition = 'opacity 0.5s ease';
                errorAlert.style.opacity = '0';
                setTimeout(() => errorAlert.remove(), 500);
            }, 5000);
        }
    </script>
</body>
</html>
