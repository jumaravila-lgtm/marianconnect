<?php

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(dirname(__DIR__)));
}
require_once '../includes/auth-check.php';
$db = getDB();

// Get current user data
$stmt = $db->prepare("SELECT * FROM admin_users WHERE admin_id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$user = $stmt->fetch();

// Handle avatar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_avatar'])) {
    try {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['avatar']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed)) {
                throw new Exception('Invalid file type. Only JPG, PNG, and GIF allowed.');
            }
            
            // Check file size (max 5MB)
            if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
                throw new Exception('File too large. Maximum size is 5MB.');
            }
            
            // Create upload directory if it doesn't exist
            $uploadDir = BASE_PATH . '/assets/uploads/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate unique filename
            $newFilename = 'avatar_' . $_SESSION['admin_id'] . '_' . time() . '.' . $ext;
            $uploadPath = $uploadDir . $newFilename;
            
            // Delete old avatar if exists
            if (!empty($user['avatar']) && file_exists(BASE_PATH . $user['avatar'])) {
                unlink(BASE_PATH . $user['avatar']);
            }
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadPath)) {
                $avatarPath = '/assets/uploads/avatars/' . $newFilename;
                
                // Update database
                $stmt = $db->prepare("UPDATE admin_users SET avatar = ? WHERE admin_id = ?");
                $stmt->execute([$avatarPath, $_SESSION['admin_id']]);
                
                $_SESSION['avatar'] = $avatarPath;
                
                logActivity($_SESSION['admin_id'], 'update', 'admin_users', $_SESSION['admin_id'], 'Updated profile avatar');
                setFlashMessage('success', 'Avatar updated successfully!');
                header('Location: profile.php');
                exit();
            } else {
                throw new Exception('Failed to upload file.');
            }
        } else {
            throw new Exception('No file uploaded or upload error.');
        }
    } catch (Exception $e) {
        setFlashMessage('error', $e->getMessage());
    }
}
// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $fullName = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address format');
        }
        
        // Check if email exists (excluding current user)
        $stmt = $db->prepare("SELECT admin_id FROM admin_users WHERE email = ? AND admin_id != ?");
        $stmt->execute([$email, $_SESSION['admin_id']]);
        if ($stmt->fetch()) {
            throw new Exception('Email already exists');
        }
        
        // Update profile
        $stmt = $db->prepare("UPDATE admin_users SET full_name = ?, email = ? WHERE admin_id = ?");
        $stmt->execute([$fullName, $email, $_SESSION['admin_id']]);
        
        // Update session
        $_SESSION['full_name'] = $fullName;
        
        logActivity($_SESSION['admin_id'], 'update', 'admin_users', $_SESSION['admin_id'], 'Updated own profile');
        setFlashMessage('success', 'Profile updated successfully!');
        header('Location: profile.php');
        exit();
        
    } catch (Exception $e) {
        setFlashMessage('error', $e->getMessage());
    }
}

$pageTitle = 'My Profile';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1>My Profile</h1>
        <p class="page-subtitle">View and manage your account information</p>
    </div>
    <div class="header-actions">
        <?php if ($_SESSION['role'] !== 'editor'): ?>
        <a href="change-password.php" class="btn btn-primary">
            <i class="fas fa-key"></i> Change Password
        </a>
        <?php else: ?>
        <div class="editor-note">
            <i class="fas fa-info-circle"></i>
            <span>To change your password, please contact an administrator</span>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="profile-grid">
<!-- Profile Card -->
<div class="profile-card">
    <div class="profile-avatar-section">
    <?php if (!empty($user['avatar'])): ?>
        <img src="<?php echo SITE_URL . $user['avatar']; ?>"
            alt="Profile" 
            class="profile-avatar-image"
            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="profile-avatar-fallback" style="display:none;">
                <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
            </div>
        <?php else: ?>
            <div class="profile-avatar-large">
                <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
            </div>
        <?php endif; ?>
        
        <!-- Avatar Upload Form -->
        <form method="POST" enctype="multipart/form-data" class="avatar-upload-form">
            <input type="file" 
                   id="avatarInput" 
                   name="avatar" 
                   accept="image/*" 
                   style="display:none;"
                   onchange="this.form.submit()">
            <button type="button" 
                    class="btn-change-avatar" 
                    onclick="document.getElementById('avatarInput').click()">
                <i class="fas fa-camera"></i> Change Photo
            </button>
            <input type="hidden" name="update_avatar" value="1">
        </form>
    </div>
    
    <h2><?php echo escapeHtml($user['full_name']); ?></h2>

    <!-- Edit Profile Form -->
    <div class="form-card">
        <form method="POST" action="">
            <div class="form-section">
                <h3><i class="fas fa-user-edit"></i> Edit Profile</h3>
                
                <div class="form-group">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" 
                           id="full_name" 
                           name="full_name" 
                           class="form-control" 
                           value="<?php echo escapeHtml($user['full_name']); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           value="<?php echo escapeHtml($user['email']); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" 
                           id="username" 
                           class="form-control" 
                           value="<?php echo escapeHtml($user['username']); ?>"
                           disabled>
                    <small class="form-help">Username cannot be changed</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Account Info -->
<div class="info-card">
    <h3><i class="fas fa-info-circle"></i> Account Information</h3>
    
    <div class="info-grid">
        <div class="info-item">
            <div class="info-icon" style="background:#e3f2fd;color:#1976d2">
                <i class="fas fa-user"></i>
            </div>
            <div class="info-content">
                <strong>Username</strong>
                <p><?php echo escapeHtml($user['username']); ?></p>
            </div>
        </div>

        <div class="info-item">
            <div class="info-icon" style="background:#e8f5e9;color:#388e3c">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="info-content">
                <strong>Email</strong>
                <p><?php echo escapeHtml($user['email']); ?></p>
            </div>
        </div>

        <div class="info-item">
            <div class="info-icon" style="background:#fff3e0;color:#f57c00">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div class="info-content">
                <strong>Role</strong>
                <p><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></p>
            </div>
        </div>

        <div class="info-item">
            <div class="info-icon" style="background:#f3e5f5;color:#7b1fa2">
                <i class="fas fa-calendar"></i>
            </div>
            <div class="info-content">
                <strong>Account Created</strong>
                <p><?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
            </div>
        </div>

        <div class="info-item">
            <div class="info-icon" style="background:#e1f5fe;color:#0277bd">
                <i class="fas fa-clock"></i>
            </div>
            <div class="info-content">
                <strong>Last Updated</strong>
                <p><?php echo timeAgo($user['updated_at']); ?></p>
            </div>
        </div>

        <div class="info-item">
            <div class="info-icon" style="background:#<?php echo $user['is_active'] ? 'e8f5e9' : 'ffebee'; ?>;color:#<?php echo $user['is_active'] ? '388e3c' : 'c62828'; ?>">
                <i class="fas fa-<?php echo $user['is_active'] ? 'check-circle' : 'times-circle'; ?>"></i>
            </div>
            <div class="info-content">
                <strong>Status</strong>
                <p><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></p>
            </div>
        </div>
    </div>
</div>

<style>
.page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:2rem}
.page-subtitle{color:var(--admin-text-muted);margin-top:0.5rem}
.header-actions{display:flex;gap:0.75rem}
.profile-grid{display:grid;grid-template-columns:350px 1fr;gap:2rem;margin-bottom:2rem}
.profile-card{background:white;border-radius:12px;padding:2rem;box-shadow:var(--admin-shadow);text-align:center}
.profile-avatar-large{width:120px;height:120px;border-radius:50%;background:linear-gradient(135deg,var(--admin-primary),#1565c0);color:white;display:flex;align-items:center;justify-content:center;font-size:3rem;font-weight:700;margin:0 auto 1.5rem}
.profile-card h2{font-size:1.5rem;font-weight:700;color:var(--admin-text);margin-bottom:0.5rem}
.profile-role{margin-bottom:2rem}
.profile-avatar-section{position:relative;margin-bottom:1.5rem}
.profile-avatar-image{width:120px;height:120px;border-radius:50%;object-fit:cover;display:block;margin:0 auto;border:4px solid #fff;box-shadow:0 4px 12px rgba(0,0,0,0.15)}
.profile-avatar-fallback{width:120px;height:120px;border-radius:50%;background:linear-gradient(135deg,var(--admin-primary),#1565c0);color:white;display:flex;align-items:center;justify-content:center;font-size:3rem;font-weight:700;margin:0 auto}
.avatar-upload-form{margin-top:1rem}
.btn-change-avatar{background:#f0f0f0;border:2px solid #ddd;padding:0.5rem 1rem;border-radius:8px;cursor:pointer;font-size:0.9rem;font-weight:500;display:inline-flex;align-items:center;gap:0.5rem;transition:all 0.2s}
.btn-change-avatar:hover{background:var(--admin-primary);color:white;border-color:var(--admin-primary)}
.role-badge{padding:0.5rem 1rem;border-radius:8px;font-size:0.9rem;font-weight:600;display:inline-block}
.role-primary{background:#e3f2fd;color:#1976d2}
.role-secondary{background:#e0e0e0;color:#616161}
.role-danger{background:#ffebee;color:#c62828}
.profile-stats{display:grid;grid-template-columns:1fr 1fr;gap:1rem;padding-top:2rem;border-top:1px solid var(--admin-border)}
.stat-item{text-align:center}
.stat-value{font-size:1.1rem;font-weight:600;color:var(--admin-text);margin-bottom:0.25rem}
.stat-label{font-size:0.85rem;color:var(--admin-text-muted)}
.form-card{background:white;border-radius:12px;padding:2rem;box-shadow:var(--admin-shadow)}
.form-section{margin-bottom:2rem}
.form-section h3{font-size:1.1rem;font-weight:600;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;color:var(--admin-text)}
.form-group{margin-bottom:1.5rem}
.form-group label{display:block;margin-bottom:0.5rem;font-weight:500;font-size:0.95rem;color:var(--admin-text)}
.form-group .required{color:#dc3545}
.form-control{width:100%;padding:0.75rem 1rem;border:2px solid var(--admin-border);border-radius:8px;font-size:0.95rem;transition:border-color 0.2s}
.form-control:focus{outline:none;border-color:var(--admin-primary)}
.form-control:disabled{background:#f5f5f5;cursor:not-allowed}
.form-help{display:block;margin-top:0.5rem;font-size:0.85rem;color:var(--admin-text-muted)}
.form-actions{display:flex;gap:1rem;padding-top:2rem;border-top:1px solid var(--admin-border)}
.info-card{background:white;border-radius:12px;padding:2rem;box-shadow:var(--admin-shadow)}
.info-card h3{font-size:1.1rem;font-weight:600;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;color:var(--admin-text)}
.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem}
.info-item{display:flex;gap:1rem;padding:1.5rem;background:#f8f9fa;border-radius:12px;align-items:center}
.info-icon{width:50px;height:50px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0}
.info-content strong{display:block;font-size:0.85rem;color:var(--admin-text-muted);margin-bottom:0.25rem;text-transform:uppercase;letter-spacing:0.5px}
.info-content p{margin:0;font-size:0.95rem;color:var(--admin-text);font-weight:500}
.btn{padding:0.75rem 1.5rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s;font-size:0.95rem}
.btn-primary{background:var(--admin-primary);color:white}
.btn-primary:hover{background:#1565c0}
.editor-note{display:flex;align-items:center;gap:0.5rem;padding:0.75rem 1.25rem;background:#fff3e0;color:#f57c00;border-radius:8px;font-size:0.9rem;font-weight:500}
.editor-note i{font-size:1rem}

@media(max-width:1024px){
    .profile-grid{grid-template-columns:1fr}
}
@media(max-width:768px){
    .page-header{flex-direction:column;gap:1rem}
    .info-grid{grid-template-columns:1fr}
}
</style>

<?php include '../includes/admin-footer.php'; ?>
