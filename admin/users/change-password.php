<?php
require_once '../includes/auth-check.php';

// Only super_admin and admin can change passwords
if (!in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    setFlashMessage('error', 'Only administrators can change passwords. Please contact your administrator if you need to change your password.');
    header('Location: profile.php');
    exit();
}

$db = getDB();

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            throw new Exception('All fields are required');
        }
        
        // Get user's current password hash
        $stmt = $db->prepare("SELECT password_hash FROM admin_users WHERE admin_id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $user = $stmt->fetch();
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password_hash'])) {
            throw new Exception('Current password is incorrect');
        }
        
        // Validate new password
        if (strlen($newPassword) < 8) {
            throw new Exception('New password must be at least 8 characters long');
        }
        
        // Check if passwords match
        if ($newPassword !== $confirmPassword) {
            throw new Exception('New passwords do not match');
        }
        
        // Check if new password is same as current
        if ($currentPassword === $newPassword) {
            throw new Exception('New password must be different from current password');
        }
        
        // Hash new password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $db->prepare("UPDATE admin_users SET password_hash = ? WHERE admin_id = ?");
        $stmt->execute([$newPasswordHash, $_SESSION['admin_id']]);
        
        logActivity($_SESSION['admin_id'], 'update', 'admin_users', $_SESSION['admin_id'], 'Changed password');
        setFlashMessage('success', 'Password changed successfully!');
        header('Location: profile.php');
        exit();
        
    } catch (Exception $e) {
        setFlashMessage('error', $e->getMessage());
    }
}

$pageTitle = 'Change Password';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1>Change Password</h1>
        <p class="page-subtitle">Update your account password</p>
    </div>
    <div class="header-actions">
        <a href="profile.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Profile
        </a>
    </div>
</div>

<div class="password-grid">
    <!-- Change Password Form -->
    <div class="form-card">
        <form method="POST" action="" id="changePasswordForm">
            <div class="form-section">
                <h3><i class="fas fa-lock"></i> Update Password</h3>
                
                <div class="form-group">
                    <label for="current_password">Current Password <span class="required">*</span></label>
                    <div class="password-input-group">
                        <input type="password" 
                               id="current_password" 
                               name="current_password" 
                               class="form-control" 
                               required
                               placeholder="Enter current password">
                        <button type="button" class="toggle-password" data-target="current_password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password <span class="required">*</span></label>
                    <div class="password-input-group">
                        <input type="password" 
                               id="new_password" 
                               name="new_password" 
                               class="form-control" 
                               required
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
                               required
                               minlength="8"
                               placeholder="Re-enter new password">
                        <button type="button" class="toggle-password" data-target="confirm_password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Change Password
                </button>
                <a href="profile.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>

    <!-- Security Tips -->
    <div class="tips-card">
        <h3><i class="fas fa-shield-alt"></i> Password Security Tips</h3>
        
        <div class="tip-item">
            <div class="tip-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="tip-content">
                <strong>Use a Strong Password</strong>
                <p>Include uppercase, lowercase, numbers, and special characters</p>
            </div>
        </div>

        <div class="tip-item">
            <div class="tip-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="tip-content">
                <strong>Avoid Common Passwords</strong>
                <p>Don't use easily guessable passwords like "password123"</p>
            </div>
        </div>

        <div class="tip-item">
            <div class="tip-icon">
                <i class="fas fa-user-secret"></i>
            </div>
            <div class="tip-content">
                <strong>Keep it Unique</strong>
                <p>Use a different password for each account</p>
            </div>
        </div>

        <div class="tip-item">
            <div class="tip-icon">
                <i class="fas fa-sync-alt"></i>
            </div>
            <div class="tip-content">
                <strong>Change Regularly</strong>
                <p>Update your password every 3-6 months</p>
            </div>
        </div>

        <div class="tip-item">
            <div class="tip-icon">
                <i class="fas fa-eye-slash"></i>
            </div>
            <div class="tip-content">
                <strong>Never Share</strong>
                <p>Keep your password confidential at all times</p>
            </div>
        </div>
    </div>
</div>

<style>
.page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:2rem}
.page-subtitle{color:var(--admin-text-muted);margin-top:0.5rem}
.header-actions{display:flex;gap:0.75rem}

.password-grid{display:grid;grid-template-columns:1fr 400px;gap:2rem}

.form-card,.tips-card{background:white;border-radius:12px;padding:2rem;box-shadow:var(--admin-shadow)}

.form-section{margin-bottom:2rem}
.form-section h3{font-size:1.1rem;font-weight:600;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;color:var(--admin-text)}

.form-group{margin-bottom:1.5rem}
.form-group label{display:block;margin-bottom:0.5rem;font-weight:500;font-size:0.95rem;color:var(--admin-text)}
.form-group .required{color:#dc3545}

.password-input-group{position:relative;display:flex}
.password-input-group .form-control{padding-right:50px}
.password-input-group .toggle-password{position:absolute;right:0;top:0;height:100%;width:50px;border:none;background:transparent;color:var(--admin-text-muted);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:color 0.2s}
.password-input-group .toggle-password:hover{color:var(--admin-primary)}

.form-control{width:100%;padding:0.75rem 1rem;border:2px solid var(--admin-border);border-radius:8px;font-size:0.95rem;transition:border-color 0.2s}
.form-control:focus{outline:none;border-color:var(--admin-primary)}
.form-help{display:block;margin-top:0.5rem;font-size:0.85rem;color:var(--admin-text-muted)}

.password-strength{margin-top:0.75rem;padding:0.75rem;border-radius:8px;font-size:0.9rem;display:none}
.password-strength.show{display:block}
.password-strength.weak{background:#ffebee;color:#c62828}
.password-strength.medium{background:#fff3e0;color:#f57c00}
.password-strength.strong{background:#e8f5e9;color:#388e3c}

.form-actions{display:flex;gap:1rem;padding-top:2rem;border-top:1px solid var(--admin-border)}

.tips-card h3{font-size:1.1rem;font-weight:600;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;color:var(--admin-text)}

.tip-item{display:flex;gap:1rem;margin-bottom:1.5rem;padding-bottom:1.5rem;border-bottom:1px solid var(--admin-border)}
.tip-item:last-child{margin-bottom:0;padding-bottom:0;border-bottom:none}
.tip-icon{width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0}
.tip-item:nth-child(1) .tip-icon{background:#e8f5e9;color:#388e3c}
.tip-item:nth-child(2) .tip-icon{background:#ffebee;color:#c62828}
.tip-item:nth-child(3) .tip-icon{background:#e3f2fd;color:#1976d2}
.tip-item:nth-child(4) .tip-icon{background:#fff3e0;color:#f57c00}
.tip-item:nth-child(5) .tip-icon{background:#f3e5f5;color:#7b1fa2}
.tip-content strong{display:block;font-size:0.95rem;color:var(--admin-text);margin-bottom:0.25rem}
.tip-content p{margin:0;font-size:0.85rem;color:var(--admin-text-muted);line-height:1.5}

.btn{padding:0.75rem 1.5rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s;font-size:0.95rem}
.btn-primary{background:var(--admin-primary);color:white}
.btn-primary:hover{background:#1565c0}
.btn-secondary{background:#6c757d;color:white}
.btn-secondary:hover{background:#5a6268}

@media(max-width:1024px){
    .password-grid{grid-template-columns:1fr}
}
@media(max-width:768px){
    .page-header{flex-direction:column;gap:1rem}
    .form-actions{flex-direction:column}
    .btn{width:100%}
}
</style>

<script>
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

newPasswordInput.addEventListener('input', function() {
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

// Form validation
const form = document.getElementById('changePasswordForm');
const confirmPasswordInput = document.getElementById('confirm_password');

form.addEventListener('submit', function(e) {
    if (newPasswordInput.value !== confirmPasswordInput.value) {
        e.preventDefault();
        alert('New passwords do not match!');
        confirmPasswordInput.focus();
    }
});
</script>

<?php include '../includes/admin-footer.php'; ?>
