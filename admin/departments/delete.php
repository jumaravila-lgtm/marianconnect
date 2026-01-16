<?php
require_once '../includes/auth-check.php';

// Restrict to Super Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    setFlashMessage('error', 'Access denied. Only Super Admins can manage Departments.');
    redirect('../index.php');
}

$db = getDB();
$departmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $db->prepare("SELECT * FROM departments WHERE department_id = ?");
$stmt->execute([$departmentId]);
$department = $stmt->fetch();

if (!$department) {
    setFlashMessage('error', 'Department not found.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Delete uploaded image if exists
        if (!empty($department['image'])) {
            $fullPath = '../../' . ltrim($department['image'], '/');
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
        
        $deleteStmt = $db->prepare("DELETE FROM departments WHERE department_id = ?");
        $result = $deleteStmt->execute([$departmentId]);
        
        if ($result) {
            logActivity($_SESSION['admin_id'], 'delete', 'departments', $departmentId, "Deleted department: {$department['name']}");
            setFlashMessage('success', 'Department deleted successfully.');
        } else {
            setFlashMessage('error', 'Failed to delete department.');
        }
    } catch (PDOException $e) {
        setFlashMessage('error', 'Database error: ' . $e->getMessage());
    }
    
    redirect('index.php');
}

$pageTitle = 'Delete Department';
include '../includes/admin-header.php';
?>

<div class="delete-container">
    <div class="delete-card">
        <div class="delete-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <h1>Delete Department?</h1>
        
        <div class="department-preview">
            <?php if (!empty($department['image'])): ?>
            <div class="dept-image">
                <img src="<?php echo escapeHtml('../../' . ltrim($department['image'], '/')); ?>" alt="<?php echo escapeHtml($department['name']); ?>">
            </div>
            <?php else: ?>
            <div class="dept-icon-placeholder">
                <i class="fas fa-building"></i>
            </div>
            <?php endif; ?>
            
            <h3><?php echo escapeHtml($department['name']); ?></h3>
            
            <div class="dept-details">
                <?php if (!empty($department['head_name'])): ?>
                <p><i class="fas fa-user-tie"></i> <strong>Department Head:</strong> <?php echo escapeHtml($department['head_name']); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($department['email'])): ?>
                <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <?php echo escapeHtml($department['email']); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($department['phone'])): ?>
                <p><i class="fas fa-phone"></i> <strong>Phone:</strong> <?php echo escapeHtml($department['phone']); ?></p>
                <?php endif; ?>
                
                <p><i class="fas fa-toggle-<?php echo $department['is_active'] ? 'on' : 'off'; ?>"></i> 
                   <strong>Status:</strong> 
                   <span class="status-badge status-<?php echo $department['is_active'] ? 'active' : 'inactive'; ?>">
                       <?php echo $department['is_active'] ? 'Active' : 'Inactive'; ?>
                   </span>
                </p>
            </div>
        </div>
        
        <div class="warning-message">
            <strong>⚠️ Warning:</strong> This action cannot be undone. The department<?php echo !empty($department['image']) ? ' and its image' : ''; ?> will be permanently deleted from the system.
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="form-actions">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Yes, Delete Department
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.delete-container{display:flex;align-items:center;justify-content:center;min-height:calc(100vh - 200px);padding:2rem}
.delete-card{background:white;border-radius:16px;padding:3rem;max-width:600px;width:100%;box-shadow:var(--admin-shadow-lg);text-align:center}
.delete-icon{width:80px;height:80px;background:linear-gradient(135deg,#dc3545,#c82333);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;margin:0 auto 2rem;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.05)}}
.delete-card h1{font-size:1.75rem;margin-bottom:2rem}
.department-preview{background:var(--admin-hover);padding:1.5rem;border-radius:12px;margin-bottom:2rem}
.dept-image{width:120px;height:120px;margin:0 auto 1rem;border-radius:50%;overflow:hidden;border:4px solid white;box-shadow:0 4px 12px rgba(0,0,0,0.1)}
.dept-image img{width:100%;height:100%;object-fit:cover}
.dept-icon-placeholder{width:120px;height:120px;background:linear-gradient(135deg,var(--admin-primary),var(--admin-primary-dark));color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:3rem;margin:0 auto 1rem;box-shadow:0 4px 12px rgba(0,0,0,0.1)}
.department-preview h3{font-size:1.25rem;margin-bottom:1rem;color:var(--admin-text-dark)}
.dept-details{text-align:left;margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--admin-border)}
.dept-details p{display:flex;align-items:flex-start;gap:0.75rem;margin-bottom:0.75rem;font-size:0.9rem;color:var(--admin-text-muted)}
.dept-details p:last-child{margin-bottom:0}
.dept-details i{color:var(--admin-primary);margin-top:0.2rem;min-width:16px}
.dept-details strong{color:var(--admin-text-dark);min-width:120px;display:inline-block}
.status-badge{display:inline-block;padding:0.25rem 0.75rem;border-radius:12px;font-size:0.75rem;font-weight:600;text-transform:uppercase}
.status-active{background:#d4edda;color:#155724}
.status-inactive{background:#f8d7da;color:#721c24}
.warning-message{background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:1rem;margin-bottom:2rem;color:#856404;text-align:left}
.form-actions{display:flex;gap:1rem;justify-content:center}
.form-actions .btn{padding:0.75rem 2rem}
.btn{display:inline-flex;align-items:center;gap:0.5rem;padding:0.75rem 1.5rem;border:none;border-radius:8px;font-weight:500;text-decoration:none;transition:all 0.3s;cursor:pointer;font-size:0.95rem}
.btn-secondary{background:#6c757d;color:white}
.btn-secondary:hover{background:#5a6268;transform:translateY(-2px);box-shadow:0 4px 12px rgba(108,117,125,0.3)}
.btn-danger{background:#dc3545;color:white}
.btn-danger:hover{background:#c82333;transform:translateY(-2px);box-shadow:0 4px 12px rgba(220,53,69,0.3)}
@media (max-width:768px){
    .delete-container{padding:1rem}
    .delete-card{padding:2rem 1.5rem}
    .form-actions{flex-direction:column}
    .form-actions .btn{width:100%}
    .dept-details strong{min-width:100px}
}
</style>

<?php include '../includes/admin-footer.php'; ?>
