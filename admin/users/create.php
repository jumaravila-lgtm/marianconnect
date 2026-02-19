<?php
require_once '../includes/auth-check.php';

// Only super_admin can create users
if ($_SESSION['role'] !== 'super_admin') {
    setFlashMessage('error', 'You do not have permission to access this page.');
    header('Location: ../index.php');
    exit();
}

$db = getDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required = ['username', 'email', 'password', 'full_name', 'role'];
        $errors = validateRequired($required, $_POST);
        
        if (!empty($errors)) {
            throw new Exception(implode('<br>', $errors));
        }
        
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        $fullName = trim($_POST['full_name']);
        $role = $_POST['role'];
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address format');
        }
        
        // Validate password
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }
        
        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match');
        }
        
        // Check if username exists
        $stmt = $db->prepare("SELECT admin_id FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            throw new Exception('Username already exists');
        }
        
        // Check if email exists
        $stmt = $db->prepare("SELECT admin_id FROM admin_users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Email already exists');
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $db->prepare("
            INSERT INTO admin_users (username, email, password_hash, full_name, role, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$username, $email, $passwordHash, $fullName, $role]);
        
        $newUserId = $db->lastInsertId();
        
        logActivity($_SESSION['admin_id'], 'create', 'admin_users', $newUserId, 'Created new user: ' . $username);
        setFlashMessage('success', 'User created successfully!');
        header('Location: index.php');
        exit();
        
    } catch (Exception $e) {
        setFlashMessage('error', $e->getMessage());
    }
}

$pageTitle = 'Create New User';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1>Create New User</h1>
        <p class="page-subtitle">Add a new administrator account</p>
    </div>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
    </div>
</div>

<div class="form-card">
    <form method="POST" action="" id="createUserForm">
        <div class="form-section">
            <h3><i class="fas fa-user"></i> Account Information</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username <span class="required">*</span></label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           required
                           pattern="[a-zA-Z0-9_]+"
                           title="Only letters, numbers, and underscores allowed"
                           placeholder="john_doe">
                    <small class="form-help">Unique username for login (letters, numbers, underscore only)</small>
                </div>

                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           required
                           placeholder="user@example.com">
                    <small class="form-help">Valid email address</small>
                </div>
            </div>

            <div class="form-group" style="margin-top: 1.75rem;">
                <label for="full_name">Full Name <span class="required">*</span></label>
                <input type="text" 
                       id="full_name" 
                       name="full_name" 
                       class="form-control" 
                       required
                       placeholder="John Doe">
                <small class="form-help">User's complete name</small>
            </div>
        </div>

        <div class="form-section" style="margin-top: 1.75rem;">
            <h3><i class="fas fa-lock"></i> Password</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           required
                           minlength="8"
                           placeholder="••••••••">
                    <small class="form-help">Minimum 8 characters</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           class="form-control" 
                           required
                           minlength="8"
                           placeholder="••••••••">
                    <small class="form-help">Re-enter password</small>
                </div>
            </div>
            
            <div class="password-strength" id="passwordStrength"></div>
        </div>

        <div class="form-section">
            <h3><i class="fas fa-shield-alt"></i> Role & Permissions</h3>
            
            <div class="form-group">
                <label for="role">User Role <span class="required">*</span></label>
                <select id="role" name="role" class="form-control" required>
                    <option value="">Select Role</option>
                    <option value="editor">Editor - Can create and edit content</option>
                    <option value="admin">Admin - Full content management access</option>
                    <option value="super_admin">Super Admin - Full system access</option>
                </select>
                <small class="form-help">Determines what the user can access</small>
            </div>

            <div class="role-info">
                <h4><i class="fas fa-info-circle"></i> Role Descriptions</h4>
                <div class="role-item">
                    <strong>Editor:</strong>
                    <p>Can create, edit, and delete content (news, events, announcements, etc.)</p>
                </div>
                <div class="role-item">
                    <strong>Admin:</strong>
                    <p>Full content management + settings access (except user management)</p>
                </div>
                <div class="role-item">
                    <strong>Super Admin:</strong>
                    <p>Full system access including user management and critical settings</p>
                </div>
            </div>
        </div>

        <div class="form-actions" style="margin-top: 2rem;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Create User
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<style>
/* Page Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--admin-border);
}

.page-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--admin-text);
    margin: 0;
}

.page-subtitle {
    color: var(--admin-text-muted);
    margin-top: 0.5rem;
}

.btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: white;
    color: var(--admin-primary);
    border: 2px solid var(--admin-border);
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
    cursor: pointer;
}

.btn-secondary:hover {
    background: var(--admin-primary);
    color: white;
    border-color: var(--admin-primary);
    transform: translateY(-2px);
    box-shadow: var(--admin-shadow-lg);
}

/* Form Grid Layout */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 1.5rem;
    align-items: start;
}

/* Form Row */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
}

/* Form Cards */
.form-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
    transition: all 0.3s;
}

.form-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.form-card h3 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--admin-border);
    color: var(--admin-text);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-card h3::before {
    content: '';
    width: 4px;
    height: 20px;
    background: var(--admin-primary);
    border-radius: 2px;
}

/* Form Groups */
.form-group {
    margin-bottom: 1.75rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 0.625rem;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--admin-text);
}

.form-group label.required::after,
.form-group .required::after {
    content: ' *';
    color: var(--admin-danger);
    font-weight: 700;
}

/* Form Controls */
.form-control {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid var(--admin-border);
    border-radius: 8px;
    font-size: 0.95rem;
    font-family: inherit;
    transition: all 0.3s ease;
    background: white;
}

.form-control:hover {
    border-color: #c5cdd8;
}

.form-control:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 4px rgba(0, 63, 135, 0.08);
}

textarea.form-control {
    resize: vertical;
    min-height: 150px;
    line-height: 1.6;
}

/* Form Text Helper */
.form-help,
.form-text {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.825rem;
    color: var(--admin-text-muted);
    line-height: 1.4;
}

/* Checkbox */
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    cursor: pointer;
    font-weight: 500;
    padding: 0.75rem;
    border-radius: 8px;
    transition: background 0.3s;
}

.checkbox-label:hover {
    background: var(--admin-hover);
}

.checkbox-label input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: var(--admin-primary);
}

/* Form Actions */
.form-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.btn-primary {
    padding: 1rem 1.5rem;
    background: var(--admin-primary);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-primary:hover {
    background: var(--admin-primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0, 63, 135, 0.25);
}

.btn-primary:active {
    transform: translateY(0);
}

.btn-block {
    width: 100%;
}

/* Sidebar Sticky */
.form-sidebar {
    position: sticky;
    top: 2rem;
}

/* Select Styling */
select.form-control {
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 12px;
    appearance: none;
    padding-right: 2.5rem;
}

/* Date Input Styling */
input[type="date"].form-control {
    cursor: pointer;
}

/* Password Strength Indicator */
.password-strength {
    margin-top: 1rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
    font-size: 0.9rem;
    display: none;
}

.password-strength.show {
    display: block;
}

.password-strength.weak {
    background: #ffebee;
    color: #c62828;
}

.password-strength.medium {
    background: #fff3e0;
    color: #f57c00;
}

.password-strength.strong {
    background: #e8f5e9;
    color: #388e3c;
}

/* Role Info Section */
.role-info {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-top: 1rem;
}

.role-info h4 {
    font-size: 0.95rem;
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--admin-text);
}

.role-item {
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--admin-border);
}

.role-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.role-item strong {
    display: block;
    color: var(--admin-text);
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.role-item p {
    margin: 0;
    color: var(--admin-text-muted);
    font-size: 0.85rem;
    line-height: 1.5;
}

/* Alert Styling */
.alert {
    padding: 1rem 1.25rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border-left: 4px solid;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border-left-color: #dc3545;
}

.alert ul {
    margin: 0;
    padding-left: 1.25rem;
}

.alert li {
    margin: 0.25rem 0;
}

/* Responsive */
@media (max-width: 1024px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-sidebar {
        position: static;
    }
    
    .page-header {
        flex-direction: column;
        gap: 1rem;
    }
}

@media (max-width: 768px) {
    .form-card {
        padding: 1.5rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .btn-secondary {
        width: 100%;
        justify-content: center;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<script>
// Password strength checker
const passwordInput = document.getElementById('password');
const strengthDiv = document.getElementById('passwordStrength');

passwordInput.addEventListener('input', function() {
    const password = this.value;
    
    if (password.length === 0) {
        strengthDiv.classList.remove('show');
        return;
    }
    
    let strength = 0;
    
    // Length check
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    
    // Complexity checks
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    strengthDiv.classList.add('show');
    strengthDiv.classList.remove('weak', 'medium', 'strong');
    
    if (strength <= 2) {
        strengthDiv.className = 'password-strength show weak';
        strengthDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Weak password - Add more characters or complexity';
    } else if (strength <= 4) {
        strengthDiv.className = 'password-strength show medium';
        strengthDiv.innerHTML = '<i class="fas fa-shield-alt"></i> Medium password - Consider adding special characters';
    } else {
        strengthDiv.className = 'password-strength show strong';
        strengthDiv.innerHTML = '<i class="fas fa-check-circle"></i> Strong password!';
    }
});

// Password match validation
const form = document.getElementById('createUserForm');
const confirmPassword = document.getElementById('confirm_password');

form.addEventListener('submit', function(e) {
    if (passwordInput.value !== confirmPassword.value) {
        e.preventDefault();
        alert('Passwords do not match!');
        confirmPassword.focus();
    }
});
</script>

<?php include '../includes/admin-footer.php'; ?>
