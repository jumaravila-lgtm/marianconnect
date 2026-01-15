<?php
require_once '../includes/auth-check.php';

// Only super_admin can access user management
if ($_SESSION['role'] !== 'super_admin') {
    setFlashMessage('error', 'You do not have permission to access this page.');
    header('Location: ../index.php');
    exit();
}

$db = getDB();

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    
    // Prevent deleting yourself
    if ($userId === $_SESSION['admin_id']) {
        setFlashMessage('error', 'You cannot delete your own account!');
    } else {
        try {
            $stmt = $db->prepare("SELECT username FROM admin_users WHERE admin_id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if ($user) {
                $stmt = $db->prepare("DELETE FROM admin_users WHERE admin_id = ?");
                $stmt->execute([$userId]);
                
                logActivity($_SESSION['admin_id'], 'delete', 'admin_users', $userId, 'Deleted user: ' . $user['username']);
                setFlashMessage('success', 'User deleted successfully!');
            }
        } catch (Exception $e) {
            setFlashMessage('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }
    header('Location: index.php');
    exit();
}

// Handle status toggle
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $userId = (int)$_GET['toggle'];
    
    try {
        $stmt = $db->prepare("SELECT is_active FROM admin_users WHERE admin_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            $newStatus = $user['is_active'] ? 0 : 1;
            $stmt = $db->prepare("UPDATE admin_users SET is_active = ? WHERE admin_id = ?");
            $stmt->execute([$newStatus, $userId]);
            
            logActivity($_SESSION['admin_id'], 'update', 'admin_users', $userId, 'Toggled user status');
            setFlashMessage('success', 'User status updated successfully!');
        }
    } catch (Exception $e) {
        setFlashMessage('error', 'Failed to update user status: ' . $e->getMessage());
    }
    header('Location: index.php');
    exit();
}

// Get all users
$users = $db->query("SELECT * FROM admin_users ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'Admin Users';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1>Admin Users</h1>
        <p class="page-subtitle">Manage administrator accounts</p>
    </div>
    <div class="header-actions">
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New User
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#e3f2fd;color:#1976d2">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo count($users); ?></div>
            <div class="stat-label">Total Users</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5e9;color:#388e3c">
            <i class="fas fa-user-check"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo count(array_filter($users, fn($u) => $u['is_active'])); ?></div>
            <div class="stat-label">Active Users</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff3e0;color:#f57c00">
            <i class="fas fa-user-shield"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo count(array_filter($users, fn($u) => $u['role'] === 'super_admin')); ?></div>
            <div class="stat-label">Super Admins</div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="table-card">
    <div class="table-header">
        <h3><i class="fas fa-list"></i> All Users</h3>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th style="width:150px;text-align:center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <div class="user-info">
                            <div class="user-avatar">
                                <?php if (!empty($user['avatar'])): ?>
                                    <img src="<?php echo SITE_URL . $user['avatar']; ?>" 
                                        alt="<?php echo escapeHtml($user['full_name']); ?>"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="user-avatar-fallback" style="display:none;">
                                        <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                                    </div>
                                <?php else: ?>
                                    <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong><?php echo escapeHtml($user['full_name']); ?></strong>
                                <?php if ($user['admin_id'] === $_SESSION['admin_id']): ?>
                                    <span class="badge badge-info">You</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <code><?php echo escapeHtml($user['username']); ?></code>
                    </td>
                    <td><?php echo escapeHtml($user['email']); ?></td>
                    <td>
                        <?php
                        $roleClass = 'badge-secondary';
                        $roleLabel = ucfirst(str_replace('_', ' ', $user['role']));
                        if ($user['role'] === 'super_admin') {
                            $roleClass = 'badge-danger';
                        } elseif ($user['role'] === 'admin') {
                            $roleClass = 'badge-primary';
                        }
                        ?>
                        <span class="badge <?php echo $roleClass; ?>">
                            <?php echo $roleLabel; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($user['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($user['last_login']): ?>
                            <small><?php echo timeAgo($user['last_login']); ?></small>
                        <?php else: ?>
                            <small class="text-muted">Never</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="edit.php?id=<?php echo $user['admin_id']; ?>" 
                               class="btn-icon btn-edit" 
                               title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            
                            <?php if ($user['admin_id'] !== $_SESSION['admin_id']): ?>
                            <a href="?toggle=<?php echo $user['admin_id']; ?>" 
                               class="btn-icon btn-toggle" 
                               title="Toggle Status"
                               onclick="return confirm('Toggle user status?')">
                                <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                            </a>
                            
                            <a href="?delete=<?php echo $user['admin_id']; ?>" 
                               class="btn-icon btn-delete confirm-delete" 
                               title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:2rem}
.page-subtitle{color:var(--admin-text-muted);margin-top:0.5rem}
.header-actions{display:flex;gap:0.75rem}

.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem;margin-bottom:2rem}
.stat-card{background:white;border-radius:12px;padding:1.5rem;box-shadow:var(--admin-shadow);display:flex;gap:1rem;align-items:center}
.stat-icon{width:60px;height:60px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0}
.stat-value{font-size:2rem;font-weight:700;color:var(--admin-text)}
.stat-label{font-size:0.9rem;color:var(--admin-text-muted)}

.table-card{background:white;border-radius:12px;padding:1.5rem;box-shadow:var(--admin-shadow)}
.table-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem}
.table-header h3{font-size:1.1rem;font-weight:600;display:flex;align-items:center;gap:0.5rem;color:var(--admin-text);margin:0}

.table-responsive{overflow-x:auto}
.data-table{width:100%;border-collapse:collapse}
.data-table th{background:#f8f9fa;padding:1rem;text-align:left;font-weight:600;border-bottom:2px solid var(--admin-border);white-space:nowrap}
.data-table td{padding:1rem;border-bottom:1px solid var(--admin-border);vertical-align:middle}
.data-table code{background:#f5f5f5;padding:0.25rem 0.5rem;border-radius:4px;font-size:0.85rem;font-family:monospace}

.user-info{display:flex;align-items:center;gap:0.75rem}
.user-avatar{width:40px;height:40px;border-radius:8px;background:var(--admin-primary);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.9rem}
.user-avatar img{width:100%;height:100%;object-fit:cover;border-radius:8px}
.user-avatar-fallback{width:100%;height:100%;display:flex;align-items:center;justify-content:center}
.badge{padding:0.25rem 0.75rem;border-radius:6px;font-size:0.85rem;font-weight:500;display:inline-block}
.badge-primary{background:#e3f2fd;color:#1976d2}
.badge-secondary{background:#e0e0e0;color:#616161}
.badge-success{background:#e8f5e9;color:#388e3c}
.badge-danger{background:#ffebee;color:#c62828}
.badge-warning{background:#fff3e0;color:#f57c00}
.badge-info{background:#e1f5fe;color:#0277bd;margin-left:0.5rem}

.action-buttons{display:flex;gap:0.5rem;justify-content:center}
.btn-icon{width:32px;height:32px;border-radius:6px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;transition:all 0.2s;font-size:0.85rem}
.btn-edit{background:#e3f2fd;color:#1976d2}
.btn-edit:hover{background:#1976d2;color:white}
.btn-toggle{background:#fff3e0;color:#f57c00}
.btn-toggle:hover{background:#f57c00;color:white}
.btn-delete{background:#ffebee;color:#c62828}
.btn-delete:hover{background:#c62828;color:white}

.text-muted{color:var(--admin-text-muted)}

.btn{padding:0.6rem 1.25rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s}
.btn-primary{background:var(--admin-primary);color:white}
.btn-primary:hover{background:#1565c0}

@media(max-width:768px){
    .page-header{flex-direction:column;gap:1rem}
    .header-actions{width:100%}
    .stats-grid{grid-template-columns:1fr}
}
</style>

<script>
// Confirm delete
document.querySelectorAll('.confirm-delete').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
});
</script>

<?php include '../includes/admin-footer.php'; ?>
