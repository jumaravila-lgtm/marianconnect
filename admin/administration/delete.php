<?php
/**
 * MARIANCONNECT - Delete Administration Member
 */

// Authentication check
require_once '../includes/auth-check.php';

$db = getDB();
$memberId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch member to delete
$stmt = $db->prepare("SELECT * FROM administration WHERE admin_member_id = ?");
$stmt->execute([$memberId]);
$member = $stmt->fetch();

if (!$member) {
    setFlashMessage('error', 'Administration member not found.');
    redirect('index.php');
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Delete associated image file
        if (!empty($member['featured_image'])) {
            deleteUploadedFile($member['featured_image']);
        }
        
        // Delete from database
        $deleteStmt = $db->prepare("DELETE FROM administration WHERE admin_member_id = ?");
        $result = $deleteStmt->execute([$memberId]);
        
        if ($result) {
            // Log activity
            logActivity($_SESSION['admin_id'], 'delete', 'administration', $memberId, "Deleted administration member: {$member['name']}");
            
            setFlashMessage('success', 'Administration member deleted successfully.');
        } else {
            setFlashMessage('error', 'Failed to delete administration member.');
        }
    } catch (PDOException $e) {
        setFlashMessage('error', 'Database error: ' . $e->getMessage());
    }
    
    redirect('index.php');
}

$pageTitle = 'Delete Administration Member';
include '../includes/admin-header.php';
?>

<div class="delete-container">
    <div class="delete-card">
        <div class="delete-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <h1>Delete Administration Member?</h1>
        
        <div class="member-preview">
            <?php if (!empty($member['featured_image'])): ?>
            <img src="<?php echo escapeHtml(getImageUrl($member['featured_image'])); ?>" 
                 alt="<?php echo escapeHtml($member['name']); ?>"
                 onerror="this.parentElement.innerHTML='<div class=\'preview-initials\'><?php echo strtoupper(substr($member['name'], 0, 1)); ?></div>'">
            <?php else: ?>
            <div class="preview-initials"><?php echo strtoupper(substr($member['name'], 0, 1)); ?></div>
            <?php endif; ?>
            
            <h3><?php echo escapeHtml($member['name']); ?></h3>
            <p class="member-position"><?php echo escapeHtml($member['position']); ?></p>
            
            <div class="meta">
                <?php if (!empty($member['email'])): ?>
                <span><i class="fas fa-envelope"></i> <?php echo escapeHtml($member['email']); ?></span>
                <?php endif; ?>
                
                <?php if (!empty($member['phone'])): ?>
                <span><i class="fas fa-phone"></i> <?php echo escapeHtml($member['phone']); ?></span>
                <?php endif; ?>
                
                <span><i class="fas fa-sort-numeric-up"></i> Display Order: <?php echo $member['display_order']; ?></span>
                
                <span>
                    <i class="fas fa-circle" style="color: <?php echo $member['is_active'] ? '#4caf50' : '#f44336'; ?>"></i>
                    <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                </span>
            </div>
        </div>
        
        <div class="warning-message">
            <strong>Warning:</strong> This action cannot be undone. The member's profile and photo will be permanently deleted from the administration page.
        </div>
        
        <form method="POST" action="" class="delete-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-actions">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Yes, Delete Member
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.delete-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: calc(100vh - 200px);
    padding: 2rem;
}

.delete-card {
    background: white;
    border-radius: 16px;
    padding: 3rem;
    max-width: 600px;
    width: 100%;
    box-shadow: var(--admin-shadow-lg);
    text-align: center;
}

.delete-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin: 0 auto 2rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

.delete-card h1 {
    font-size: 1.75rem;
    margin-bottom: 2rem;
    color: var(--admin-text);
}

.member-preview {
    background: var(--admin-hover);
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.member-preview img {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: 50%;
    margin: 0 auto 1rem;
    display: block;
    border: 4px solid white;
    box-shadow: var(--admin-shadow);
}

.preview-initials {
    width: 150px;
    height: 150px;
    margin: 0 auto 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--admin-primary), var(--admin-primary-light));
    color: white;
    font-size: 3rem;
    font-weight: 700;
    border: 4px solid white;
    box-shadow: var(--admin-shadow);
}

.member-preview h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: var(--admin-text);
}

.member-position {
    font-size: 1.1rem;
    color: var(--admin-primary);
    font-weight: 600;
    margin-bottom: 1rem;
}

.member-preview .meta {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    font-size: 0.9rem;
    color: var(--admin-text-muted);
    text-align: left;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--admin-border);
}

.member-preview .meta span {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.member-preview .meta i {
    width: 20px;
    text-align: center;
    color: var(--admin-primary);
}

.warning-message {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 2rem;
    color: #856404;
    text-align: left;
}

.warning-message strong {
    display: block;
    margin-bottom: 0.5rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
    cursor: pointer;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: var(--admin-shadow-lg);
}

.btn-danger {
    background: var(--admin-danger);
    color: white;
}

.btn-danger:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: var(--admin-shadow-lg);
}

@media (max-width: 768px) {
    .delete-card {
        padding: 2rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php include '../includes/admin-footer.php'; ?>
