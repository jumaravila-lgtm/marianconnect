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
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.page-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--admin-text);
}

.page-actions {
    display: flex;
    gap: 0.5rem;
}
.delete-container {
    max-width: 700px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.delete-card {
    background: white;
    border-radius: 12px;
    padding: 2.5rem;
    box-shadow: var(--admin-shadow);
    text-align: center;
}

.delete-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    background: #fff3cd;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.delete-icon i {
    font-size: 2.5rem;
    color: #856404;
}

.delete-card h2 {
    font-size: 1.75rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--admin-text);
}

.delete-message {
    font-size: 1.1rem;
    color: var(--admin-text-muted);
    margin-bottom: 2rem;
}

.announcement-preview {
    background: #f8f9fa;
    border: 2px solid var(--admin-border);
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: left;
}

.preview-item {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--admin-border);
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
}

.preview-item span {
    color: var(--admin-text-muted);
}

.preview-content {
    margin-top: 0.5rem;
    padding: 1rem;
    background: white;
    border-radius: 6px;
    line-height: 1.6;
    color: var(--admin-text);
}

.badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
}

.badge-general { background: #e3f2fd; color: #1565c0; }
.badge-urgent { background: #ffebee; color: #c62828; }
.badge-academic { background: #f3e5f5; color: #6a1b9a; }
.badge-event { background: #e8f5e9; color: #2e7d32; }

.badge-priority-low { background: #f1f8e9; color: #558b2f; }
.badge-priority-medium { background: #fff3e0; color: #ef6c00; }
.badge-priority-high { background: #ffebee; color: #c62828; }

.badge-active { background: #e8f5e9; color: #2e7d32; }
.badge-inactive { background: #fafafa; color: #757575; }

.delete-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 1.5rem;
}

.btn {
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(220,53,69,0.3);
}

@media (max-width: 768px) {
    .delete-card {
        padding: 1.5rem;
    }
    
    .preview-item {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .delete-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php include '../includes/admin-footer.php'; ?>
