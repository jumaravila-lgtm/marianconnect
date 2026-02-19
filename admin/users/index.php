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
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo count($users); ?></div>
            <div class="stat-label">Total Users</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5e9;color:#388e3c">
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo count(array_filter($users, fn($u) => $u['is_active'])); ?></div>
            <div class="stat-label">Active Users</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff3e0;color:#f57c00">
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
    margin: 0 0 0.5rem 0;
}

.page-subtitle {
    color: var(--admin-text-muted);
    font-size: 0.95rem;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.75rem;
    background: var(--admin-primary);
    color: white;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 63, 135, 0.15);
}

.btn:hover {
    background: var(--admin-primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 63, 135, 0.25);
}

.btn-primary {
    background: var(--admin-primary);
}

/* Statistics Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
    transition: all 0.3s;
}

.stat-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.stat-content {
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--admin-text);
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.9rem;
    color: var(--admin-text-muted);
    font-weight: 500;
}

/* Table Card */
.table-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
    overflow: hidden;
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 2px solid var(--admin-border);
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.table-header h3 {
    font-size: 1.1rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--admin-text);
    margin: 0;
}

.table-responsive {
    overflow-x: auto;
}

/* Data Table */
.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-bottom: 2px solid var(--admin-border);
}

.data-table th {
    padding: 1.25rem 1rem;
    text-align: left;
    font-weight: 700;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--admin-text);
    white-space: nowrap;
}

.data-table td {
    padding: 1.25rem 1rem;
    border-bottom: 1px solid var(--admin-border);
    vertical-align: middle;
}

.data-table tbody tr {
    transition: all 0.3s;
}

.data-table tbody tr:hover {
    background: #f8f9fa;
}

.data-table code {
    background: linear-gradient(135deg, #f5f5f5, #e9ecef);
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: #424242;
    border: 1px solid #dee2e6;
}

/* User Info */
.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar {
    width: 45px;
    height: 45px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--admin-primary), #1976d2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.95rem;
    box-shadow: 0 2px 8px rgba(0, 63, 135, 0.2);
    position: relative;
    overflow: hidden;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 10px;
}

.user-avatar-fallback {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--admin-primary), #1976d2);
}

.user-info strong {
    font-weight: 600;
    color: var(--admin-text);
    font-size: 0.95rem;
}

/* Badges */
.badge {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.badge-primary {
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    color: #1565c0;
}

.badge-secondary {
    background: linear-gradient(135deg, #f5f5f5, #eeeeee);
    color: #616161;
}

.badge-success {
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
    color: #2e7d32;
}

.badge-danger {
    background: linear-gradient(135deg, #ffebee, #ffcdd2);
    color: #c62828;
}

.badge-warning {
    background: linear-gradient(135deg, #fff3e0, #ffe0b2);
    color: #e65100;
}

.badge-info {
    background: linear-gradient(135deg, #e1f5fe, #b3e5fc);
    color: #0277bd;
    margin-left: 0.5rem;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.btn-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s;
    border: 2px solid transparent;
    font-size: 0.9rem;
}

.btn-edit {
    background: #f8f9fa;
    color: var(--admin-text);
}

.btn-edit:hover {
    background: var(--admin-primary);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 63, 135, 0.25);
}

.btn-toggle {
    background: #f8f9fa;
    color: var(--admin-text);
}

.btn-toggle:hover {
    background: #f57c00;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(245, 124, 0, 0.25);
}

.btn-delete {
    background: #f8f9fa;
    color: var(--admin-text);
}

.btn-delete:hover {
    background: var(--admin-danger);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.25);
}

/* Text Utilities */
.text-muted {
    color: var(--admin-text-muted);
    font-size: 0.85rem;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .header-actions {
        width: 100%;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .data-table {
        font-size: 0.85rem;
    }
    
    .data-table th,
    .data-table td {
        padding: 0.875rem 0.5rem;
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
    }
}
</style>
<?php include '../includes/admin-footer.php'; ?>
