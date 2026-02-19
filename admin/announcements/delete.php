<?php
require_once '../includes/auth-check.php';
$db = getDB();

// Get announcement ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlashMessage('error', 'Invalid announcement ID');
    redirect('index.php');
}

// Fetch announcement details
try {
    $stmt = $db->prepare("SELECT * FROM announcements WHERE announcement_id = ?");
    $stmt->execute([$id]);
    $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$announcement) {
        setFlashMessage('error', 'Announcement not found');
        redirect('index.php');
    }
} catch (PDOException $e) {
    setFlashMessage('error', 'Database error: ' . $e->getMessage());
    redirect('index.php');
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setFlashMessage('error', 'Invalid security token');
        redirect('index.php');
    }
    
    try {
        // Delete the announcement
        $stmt = $db->prepare("DELETE FROM announcements WHERE announcement_id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            // Log the activity
            logActivity($_SESSION['admin_id'], 'delete', 'announcements', $id, "Deleted announcement: {$announcement['title']}");
            
            setFlashMessage('success', 'Announcement deleted successfully!');
            redirect('index.php');
        } else {
            setFlashMessage('error', 'Failed to delete announcement');
            redirect('index.php');
        }
    } catch (PDOException $e) {
        setFlashMessage('error', 'Error deleting announcement: ' . $e->getMessage());
        redirect('index.php');
    }
}

$pageTitle = 'Delete Announcement';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Delete Announcement</h1>
</div>

<div class="delete-container">
    <div class="delete-card">
        <div class="delete-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <h2>Confirm Deletion</h2>
        <p class="delete-message">Are you sure you want to delete this announcement? This action cannot be undone.</p>
        
        <div class="announcement-preview">
            <div class="preview-item">
                <strong>Title:</strong>
                <span><?php echo escapeHtml($announcement['title']); ?></span>
            </div>
            
            <div class="preview-item">
                <strong>Type:</strong>
                <span class="badge badge-<?php echo $announcement['type']; ?>">
                    <?php echo ucfirst($announcement['type']); ?>
                </span>
            </div>
            
            <div class="preview-item">
                <strong>Priority:</strong>
                <span class="badge badge-priority-<?php echo $announcement['priority']; ?>">
                    <?php echo ucfirst($announcement['priority']); ?>
                </span>
            </div>
            
            <div class="preview-item">
                <strong>Status:</strong>
                <span class="badge badge-<?php echo $announcement['is_active'] ? 'active' : 'inactive'; ?>">
                    <?php echo $announcement['is_active'] ? 'Active' : 'Inactive'; ?>
                </span>
            </div>
            
            <div class="preview-item">
                <strong>Duration:</strong>
                <span><?php echo date('M d, Y', strtotime($announcement['start_date'])); ?> - <?php echo date('M d, Y', strtotime($announcement['end_date'])); ?></span>
            </div>
            
            <div class="preview-item">
                <strong>Target:</strong>
                <span><?php echo ucfirst($announcement['target_audience']); ?></span>
            </div>
            
            <div class="preview-item full-width">
                <strong>Content:</strong>
                <p class="preview-content"><?php echo escapeHtml($announcement['content']); ?></p>
            </div>
        </div>
        
        <form method="POST" action="" class="delete-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="delete-actions">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Announcement
                </button>
            </div>
        </form>
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
    margin: 0;
}

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

.delete-card h2 {
    font-size: 1.85rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: var(--admin-text);
}

.delete-message {
    font-size: 1.05rem;
    color: var(--admin-text-muted);
    margin-bottom: 2rem;
    line-height: 1.6;
}

/* Announcement Preview Section */
.announcement-preview {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border: 2px solid var(--admin-border);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    text-align: left;
    transition: all 0.3s;
}

.announcement-preview:hover {
    border-color: var(--admin-primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

/* Preview Items Grid Layout */
.preview-item {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #dee2e6;
    align-items: center;
}

.preview-item:last-child {
    border-bottom: none;
}

.preview-item.full-width {
    grid-template-columns: 1fr;
}

.preview-item strong {
    color: var(--admin-text);
    font-weight: 600;
    font-size: 0.9rem;
}

.preview-item span {
    color: var(--admin-text);
    font-weight: 500;
    font-size: 0.95rem;
}

.preview-content {
    margin-top: 0.5rem;
    padding: 1.25rem;
    background: white;
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    line-height: 1.7;
    color: #495057;
    font-size: 0.9rem;
}

/* Badges - Styled with Colors Like News Delete */
.badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

/* Type Badges */
.badge-general {
    background: #e3f2fd;
    color: #1565c0;
}

.badge-urgent {
    background: #ffebee;
    color: #c62828;
}

.badge-academic {
    background: #f3e5f5;
    color: #7b1fa2;
}

.badge-event {
    background: #e8f5e9;
    color: #2e7d32;
}

/* Priority Badges */
.badge-priority-low {
    background: #f5f5f5;
    color: #757575;
}

.badge-priority-medium {
    background: #fff3e0;
    color: #f57c00;
}

.badge-priority-high {
    background: #ffebee;
    color: #c62828;
}

/* Status Badges */
.badge-active {
    background: #e8f5e9;
    color: #2e7d32;
}

.badge-inactive {
    background: #fafafa;
    color: #9e9e9e;
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

/* Delete Actions */
.delete-actions {
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
    
    .delete-card h2 {
        font-size: 1.5rem;
    }
    
    .delete-icon {
        width: 70px;
        height: 70px;
        font-size: 2rem;
    }
    
    .announcement-preview {
        padding: 1.5rem;
    }
    
    .preview-item {
        grid-template-columns: 1fr;
        gap: 0.5rem;
        padding: 0.5rem 0;
    }
    
    .delete-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<?php include '../includes/admin-footer.php'; ?>
