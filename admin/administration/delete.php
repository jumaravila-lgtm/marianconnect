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
/* Delete Container - Centered Layout with Gradient Background */
.delete-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: calc(100vh - 200px);
    padding: 2rem;
    background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
}

.delete-card {
    background: white;
    border-radius: 16px;
    padding: 3rem;
    max-width: 650px;
    width: 100%;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
    text-align: center;
    border: 1px solid var(--admin-border);
}

/* Delete Icon with Pulse Animation */
.delete-icon {
    width: 90px;
    height: 90px;
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.75rem;
    margin: 0 auto 2rem;
    box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 12px 28px rgba(220, 53, 69, 0.4);
    }
}

.delete-card h1 {
    font-size: 1.85rem;
    margin-bottom: 2rem;
    color: var(--admin-text);
    font-weight: 700;
}

/* Member Preview Section */
.member-preview {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    border: 2px solid var(--admin-border);
    transition: all 0.3s;
}

.member-preview:hover {
    border-color: var(--admin-primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.member-preview img {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: 50%;
    margin: 0 auto 1rem;
    display: block;
    border: 4px solid white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.preview-initials {
    width: 150px;
    height: 150px;
    margin: 0 auto 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--admin-primary), #1976d2);
    color: white;
    font-size: 3.5rem;
    font-weight: 700;
    border: 4px solid white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.member-preview h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: var(--admin-text);
    font-weight: 700;
}

.member-position {
    font-size: 1.1rem;
    color: var(--admin-primary);
    font-weight: 600;
    margin-bottom: 1.5rem;
    padding: 0.5rem 1rem;
    background: rgba(0, 63, 135, 0.08);
    border-radius: 20px;
    display: inline-block;
}

/* Meta Information */
.member-preview .meta {
    display: flex;
    justify-content: center;
    flex-direction: column;
    gap: 1rem;
    font-size: 0.9rem;
    color: var(--admin-text-muted);
    text-align: left;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 2px solid var(--admin-border);
}

.member-preview .meta span {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: white;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s;
}

.member-preview .meta span:hover {
    background: #f8f9fa;
    transform: translateX(4px);
}

.member-preview .meta i {
    width: 20px;
    text-align: center;
    color: var(--admin-primary);
    font-size: 1rem;
}

/* Warning Message */
.warning-message {
    background: linear-gradient(135deg, #fff3cd 0%, #fffaed 100%);
    border: 2px solid #ffc107;
    border-radius: 10px;
    padding: 1.25rem 1.5rem;
    margin-bottom: 2rem;
    color: #856404;
    text-align: left;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.15);
}

.warning-message strong {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 1.05rem;
}

.warning-message strong::before {
    font-size: 1.2rem;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 2rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    min-width: 160px;
    justify-content: center;
}

.btn-secondary {
    background: white;
    color: var(--admin-primary);
    border: 2px solid var(--admin-primary);
}

.btn-secondary:hover {
    background: var(--admin-primary);
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(0, 63, 135, 0.25);
}

.btn-danger {
    background: var(--admin-danger);
    color: white;
    border: 2px solid var(--admin-danger);
}

.btn-danger:hover {
    background: #c82333;
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(220, 53, 69, 0.35);
}

.btn i {
    font-size: 1.1rem;
}

/* Responsive Design */
@media (max-width: 640px) {
    .delete-container {
        padding: 1rem;
    }
    
    .delete-card {
        padding: 2rem 1.5rem;
    }
    
    .delete-card h1 {
        font-size: 1.5rem;
    }
    
    .delete-icon {
        width: 70px;
        height: 70px;
        font-size: 2rem;
    }
    
    .member-preview {
        padding: 1.5rem;
    }
    
    .member-preview img,
    .preview-initials {
        width: 120px;
        height: 120px;
        font-size: 2.5rem;
    }
    
    .member-preview h3 {
        font-size: 1.25rem;
    }
    
    .member-position {
        font-size: 1rem;
    }
    
    .member-preview .meta {
        gap: 0.75rem;
    }
    
    .member-preview .meta span {
        padding: 0.5rem 0.75rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<?php include '../includes/admin-footer.php'; ?>
