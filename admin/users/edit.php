<?php
require_once '../includes/auth-check.php';

// Only super_admin can edit users
if ($_SESSION['role'] !== 'super_admin') {
    setFlashMessage('error', 'You do not have permission to access this page.');
    header('Location: ../index.php');
    exit();
}

$db = getDB();

// Get user ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'Invalid user ID');
    header('Location: index.php');
    exit();
}

$userId = (int)$_GET['id'];

// Get user data
$stmt = $db->prepare("SELECT * FROM admin_users WHERE admin_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    setFlashMessage('error', 'User not found');
    header('Location: index.php');
    exit();
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    try {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate password
        if (empty($newPassword) || empty($confirmPassword)) {
            throw new Exception('Both password fields are required');
        }
        
        if (strlen($newPassword) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }
        
        if ($newPassword !== $confirmPassword) {
            throw new Exception('Passwords do not match');
        }
        
        // Hash password
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $db->prepare("UPDATE admin_users SET password_hash = ? WHERE admin_id = ?");
        $stmt->execute([$passwordHash, $userId]);
        
        logActivity($_SESSION['admin_id'], 'update', 'admin_users', $userId, 'Reset password for user: ' . $user['username']);
        setFlashMessage('success', 'Password reset successfully!');
        header('Location: edit.php?id=' . $userId);
        exit();
        
    } catch (Exception $e) {
        setFlashMessage('error', $e->getMessage());
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        // Validate required fields
        $required = ['username', 'email', 'full_name', 'role'];
        $errors = validateRequired($required, $_POST);
        
        if (!empty($errors)) {
            throw new Exception(implode('<br>', $errors));
        }
        
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $fullName = trim($_POST['full_name']);
        $role = $_POST['role'];
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address format');
        }
        
        // Check if username exists (excluding current user)
        $stmt = $db->prepare("SELECT admin_id FROM admin_users WHERE username = ? AND admin_id != ?");
        $stmt->execute([$username, $userId]);
        if ($stmt->fetch()) {
            throw new Exception('Username already exists');
        }
        
        // Check if email exists (excluding current user)
        $stmt = $db->prepare("SELECT admin_id FROM admin_users WHERE email = ? AND admin_id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            throw new Exception('Email already exists');
        }
        
        // Update user
        $stmt = $db->prepare("
            UPDATE admin_users 
            SET username = ?, email = ?, full_name = ?, role = ?, is_active = ?
            WHERE admin_id = ?
        ");
        $stmt->execute([$username, $email, $fullName, $role, $isActive, $userId]);
        
        logActivity($_SESSION['admin_id'], 'update', 'admin_users', $userId, 'Updated user: ' . $username);
        setFlashMessage('success', 'User updated successfully!');
        header('Location: index.php');
        exit();
        
    } catch (Exception $e) {
        setFlashMessage('error', $e->getMessage());
    }
}

$pageTitle = 'Edit User';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1>Edit User</h1>
        <p class="page-subtitle">Modify user account details</p>
    </div>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
    </div>
</div>

<div class="form-card">
    <form method="POST" action="">
        <div class="form-section">
            <h3><i class="fas fa-user"></i> Account Information</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username <span class="required">*</span></label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           value="<?php echo escapeHtml($user['username']); ?>"
                           required
                           pattern="[a-zA-Z0-9_]+"
                           title="Only letters, numbers, and underscores allowed">
                    <small class="form-help">Unique username for login</small>
                </div>

                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           value="<?php echo escapeHtml($user['email']); ?>"
                           required>
                    <small class="form-help">Valid email address</small>
                </div>
            </div>

            <div class="form-group">
                <label for="full_name">Full Name <span class="required">*</span></label>
                <input type="text" 
                       id="full_name" 
                       name="full_name" 
                       class="form-control" 
                       value="<?php echo escapeHtml($user['full_name']); ?>"
                       required>
                <small class="form-help">User's complete name</small>
            </div>
        </div>

        <div class="form-section">
            <h3><i class="fas fa-shield-alt"></i> Role & Status</h3>
            
            <div class="form-group">
                <label for="role">User Role <span class="required">*</span></label>
                <select id="role" name="role" class="form-control" required>
                    <option value="editor" <?php echo $user['role'] === 'editor' ? 'selected' : ''; ?>>Editor</option>
                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="super_admin" <?php echo $user['role'] === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                </select>
                <small class="form-help">Determines user permissions</small>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" 
                           name="is_active" 
                           <?php echo $user['is_active'] ? 'checked' : ''; ?>
                           <?php echo $userId === $_SESSION['admin_id'] ? 'disabled' : ''; ?>>
                    <span>Account is active</span>
                </label>
                <small class="form-help">Inactive users cannot log in</small>
                <?php if ($userId === $_SESSION['admin_id']): ?>
                    <small class="form-help text-warning">You cannot deactivate your own account</small>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-section">
            <h3><i class="fas fa-info-circle"></i> Account Details</h3>
            
            <div class="info-grid">
                <div class="info-item">
                    <strong>Account Created</strong>
                    <p><?php echo date('F j, Y g:i A', strtotime($user['created_at'])); ?></p>
                </div>
                <div class="info-item">
                    <strong>Last Updated</strong>
                    <p><?php echo date('F j, Y g:i A', strtotime($user['updated_at'])); ?></p>
                </div>
                <div class="info-item">
                    <strong>Last Login</strong>
                    <p><?php echo $user['last_login'] ? date('F j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?></p>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" name="update_profile" class="btn btn-primary">
                <i class="fas fa-save"></i> Update User
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>

    <!-- Password Reset Section -->
    <div class="password-reset-section">
        <h3><i class="fas fa-key"></i> Reset Password</h3>
        <p class="section-description">Reset the password for this user. The user will be able to log in with the new password immediately.</p>
        
        <button type="button" class="btn btn-warning" id="togglePasswordReset">
            <i class="fas fa-unlock-alt"></i> Reset Password
        </button>

        <div id="passwordResetForm" style="display:none;">
            <form method="POST" action="" id="resetPasswordForm">
                <div class="password-reset-fields">
                    <div class="form-group">
                        <label for="new_password">New Password <span class="required">*</span></label>
                        <div class="password-input-group">
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   class="form-control" 
                                   minlength="8"
                                   placeholder="Enter new password">
                            <button type="button" class="toggle-password" data-target="new_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="form-help">Minimum 8 characters</small>
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                        <div class="password-input-group">
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   class="form-control" 
                                   minlength="8"
                                   placeholder="Re-enter new password">
                            <button type="button" class="toggle-password" data-target="confirm_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="password-reset-actions">
                        <button type="submit" name="reset_password" class="btn btn-danger">
                            <i class="fas fa-key"></i> Reset Password
                        </button>
                        <button type="button" class="btn btn-secondary" id="cancelPasswordReset">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($userId !== $_SESSION['admin_id']): ?>
    <div class="danger-zone">
        <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
        <p>Once you delete a user account, there is no going back. Please be certain.</p>
        <a href="index.php?delete=<?php echo $userId; ?>" 
           class="btn btn-danger confirm-delete">
            <i class="fas fa-trash"></i> Delete This User
        </a>
    </div>
    <?php endif; ?>
</div>

<style>
.page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:2rem}
.page-subtitle{color:var(--admin-text-muted);margin-top:0.5rem}
.header-actions{display:flex;gap:0.75rem}

.form-card{background:white;border-radius:12px;padding:2rem;box-shadow:var(--admin-shadow)}

.form-section{margin-bottom:2.5rem;padding-bottom:2rem;border-bottom:1px solid var(--admin-border)}
.form-section:last-of-type{border-bottom:none;margin-bottom:0;padding-bottom:0}
.form-section h3{font-size:1.1rem;font-weight:600;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;color:var(--admin-text)}

.form-row{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}
.form-group{margin-bottom:1.5rem}
.form-group label{display:block;margin-bottom:0.5rem;font-weight:500;font-size:0.95rem;color:var(--admin-text)}
.form-group .required{color:#dc3545}
.form-control{width:100%;padding:0.75rem 1rem;border:2px solid var(--admin-border);border-radius:8px;font-size:0.95rem;transition:border-color 0.2s}
.form-control:focus{outline:none;border-color:var(--admin-primary)}
.form-help{display:block;margin-top:0.5rem;font-size:0.85rem;color:var(--admin-text-muted)}
.text-warning{color:#f57c00}

.checkbox-label{display:flex;align-items:center;gap:0.75rem;cursor:pointer;font-weight:500;font-size:0.95rem}
.checkbox-label input[type="checkbox"]{width:20px;height:20px;cursor:pointer}

.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem}
.info-item{padding:1rem;background:#f8f9fa;border-radius:8px}
.info-item strong{display:block;font-size:0.85rem;color:var(--admin-text-muted);margin-bottom:0.5rem;text-transform:uppercase;letter-spacing:0.5px}
.info-item p{margin:0;font-size:0.95rem;color:var(--admin-text);font-weight:500}

.form-actions{display:flex;gap:1rem;padding-top:2rem;border-top:1px solid var(--admin-border);margin-top:2rem}

.danger-zone{margin-top:2rem;padding:1.5rem;background:#ffebee;border:2px solid #ef5350;border-radius:12px}
.danger-zone h3{font-size:1rem;font-weight:600;margin-bottom:0.5rem;color:#c62828;display:flex;align-items:center;gap:0.5rem}
.danger-zone p{margin-bottom:1rem;color:#c62828;font-size:0.9rem}

.btn{padding:0.75rem 1.5rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s;font-size:0.95rem}
.btn-primary{background:var(--admin-primary);color:white}
.btn-primary:hover{background:#1565c0}
.btn-secondary{background:#6c757d;color:white}
.btn-secondary:hover{background:#5a6268}
.btn-danger{background:#dc3545;color:white}
.btn-danger:hover{background:#c62828}
.btn-warning{background:#ffc107;color:#856404}
.btn-warning:hover{background:#e0a800}

.password-reset-section{margin-top:2rem;padding:1.5rem;background:#fff9e6;border:2px solid #ffc107;border-radius:12px}
.password-reset-section h3{font-size:1rem;font-weight:600;margin-bottom:0.5rem;color:#856404;display:flex;align-items:center;gap:0.5rem}
.section-description{margin-bottom:1rem;color:#856404;font-size:0.9rem;line-height:1.5}
.password-reset-fields{margin-top:1.5rem;padding-top:1.5rem;border-top:2px solid #ffc107}
.password-reset-actions{display:flex;gap:1rem;margin-top:1rem}

.password-input-group{position:relative;display:flex}
.password-input-group .form-control{padding-right:50px}
.password-input-group .toggle-password{position:absolute;right:0;top:0;height:100%;width:50px;border:none;background:transparent;color:var(--admin-text-muted);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:color 0.2s}
.password-input-group .toggle-password:hover{color:var(--admin-primary)}

.password-strength{margin-top:0.75rem;padding:0.75rem;border-radius:8px;font-size:0.9rem;display:none}
.password-strength.show{display:block}
.password-strength.weak{background:#ffebee;color:#c62828}
.password-strength.medium{background:#fff3e0;color:#f57c00}
.password-strength.strong{background:#e8f5e9;color:#388e3c}

@media(max-width:768px){
    .page-header{flex-direction:column;gap:1rem}
    .form-row,.info-grid{grid-template-columns:1fr}
    .form-actions{flex-direction:column}
    .btn{width:100%}
}
</style>

<script>

// Toggle password reset form
const toggleBtn = document.getElementById('togglePasswordReset');
const passwordResetForm = document.getElementById('passwordResetForm');
const cancelBtn = document.getElementById('cancelPasswordReset');

toggleBtn?.addEventListener('click', function() {
    passwordResetForm.style.display = 'block';
    this.style.display = 'none';
});

cancelBtn?.addEventListener('click', function() {
    passwordResetForm.style.display = 'none';
    toggleBtn.style.display = 'inline-flex';
    document.getElementById('resetPasswordForm').reset();
    document.getElementById('passwordStrength').classList.remove('show');
});

// Toggle password visibility
document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', function() {
        const targetId = this.dataset.target;
        const input = document.getElementById(targetId);
        const icon = this.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
});

// Password strength checker
const newPasswordInput = document.getElementById('new_password');
const strengthDiv = document.getElementById('passwordStrength');

newPasswordInput?.addEventListener('input', function() {
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

// Password reset form validation
const resetForm = document.getElementById('resetPasswordForm');
const confirmPasswordInput = document.getElementById('confirm_password');

resetForm?.addEventListener('submit', function(e) {
    if (newPasswordInput.value !== confirmPasswordInput.value) {
        e.preventDefault();
        alert('Passwords do not match!');
        confirmPasswordInput.focus();
    }
});
</script>

<?php include '../includes/admin-footer.php'; ?>
