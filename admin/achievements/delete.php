<?php
require_once '../includes/auth-check.php';
$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlashMessage('error', 'Invalid achievement ID');
    redirect('index.php');
}

try {
    $stmt = $db->prepare("SELECT * FROM achievements WHERE achievement_id = ?");
    $stmt->execute([$id]);
    $achievement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$achievement) {
        setFlashMessage('error', 'Achievement not found');
        redirect('index.php');
    }
} catch (PDOException $e) {
    setFlashMessage('error', 'Database error');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setFlashMessage('error', 'Invalid token');
        redirect('index.php');
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM achievements WHERE achievement_id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            if ($achievement['featured_image']) {
                deleteUploadedFile($achievement['featured_image']);
            }
            
            logActivity($_SESSION['admin_id'], 'delete', 'achievements', $id, "Deleted achievement: {$achievement['title']}");
            setFlashMessage('success', 'Achievement deleted!');
            redirect('index.php');
        }
    } catch (PDOException $e) {
        setFlashMessage('error', 'Delete failed');
        redirect('index.php');
    }
}

$pageTitle = 'Delete Achievement';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Delete Achievement</h1>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="delete-container">
    <div class="delete-card">
        <div class="delete-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <h2>Confirm Deletion</h2>
        <p class="delete-message">Delete this achievement? This cannot be undone.</p>
        
        <?php if ($achievement['featured_image']): ?>
        <div class="achievement-image">
            <img src="<?php echo escapeHtml(getImageUrl($achievement['featured_image'])); ?>" alt="<?php echo escapeHtml($achievement['title']); ?>">
        </div>
        <?php endif; ?>
        
        <div class="achievement-preview">
            <div class="preview-item">
                <strong>Title:</strong>
                <span><?php echo escapeHtml($achievement['title']); ?></span>
            </div>
            
            <div class="preview-item">
                <strong>Category:</strong>
                <div style="background: #9c27b0; color: white; padding: 0.35rem 0.75rem; border-radius: 6px; font-weight: 500; display: inline-block;">
                    <?php echo strtoupper(str_replace('_', ' ', $achievement['category'])); ?>
                </div>
            </div>
            
            <?php if ($achievement['recipient_name']): ?>
            <div class="preview-item">
                <strong>Recipient:</strong>
                <span><?php echo escapeHtml($achievement['recipient_name']); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="preview-item">
                <strong>Type:</strong>
                <span class="badge badge-type"><?php echo ucfirst($achievement['recipient_type']); ?></span>
            </div>
            
            <div class="preview-item">
                <strong>Level:</strong>
                <span class="badge badge-level-<?php echo $achievement['award_level']; ?>"><?php echo ucfirst($achievement['award_level']); ?></span>
            </div>
            
            <div class="preview-item">
                <strong>Date:</strong>
                <span><?php echo date('M d, Y', strtotime($achievement['achievement_date'])); ?></span>
            </div>
            
            <?php if ($achievement['is_featured']): ?>
            <div class="preview-item">
                <strong>Status:</strong>
                <span class="badge badge-featured"><i class="fas fa-star"></i> Featured</span>
            </div>
            <?php endif; ?>
            
            <div class="preview-item full-width">
                <strong>Description:</strong>
                <p class="preview-content"><?php echo escapeHtml($achievement['description']); ?></p>
            </div>
        </div>
        <div class="warning-message">
            <strong>Warning:</strong> This action cannot be undone. The achievement and its associated image will be permanently deleted.
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="delete-actions">
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
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

/* Delete Container - Centered Layout */
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

/* Delete Icon with Animation */
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

.delete-card h1,
.delete-card h2 {
    font-size: 1.85rem;
    margin-bottom: 1rem;
    color: var(--admin-text);
    font-weight: 700;
}

.delete-message {
    font-size: 1.05rem;
    color: var(--admin-text-muted);
    margin-bottom: 2rem;
    line-height: 1.6;
}

/* Achievement Image */
.achievement-image {
    margin-bottom: 2rem;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid var(--admin-border);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.achievement-image img {
    width: 100%;
    max-height: 250px;
    object-fit: cover;
}

/* Achievement Preview - Similar to Article Preview */
.achievement-preview {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    border: 2px solid var(--admin-border);
    transition: all 0.3s;
    text-align: left;
}

.achievement-preview:hover {
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
    font-size: 0.95rem;
    font-weight: 500;
}

.preview-content {
    margin-top: 0.5rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    line-height: 1.6;
    color: #495057;
    font-size: 0.9rem;
}

/* Badges */
.badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-type {
    background: #e3f2fd;
    color: #1565c0;
}

.badge-level-local {
    background: #f5f5f5;
    color: #757575;
}

.badge-level-regional {
    background: #e3f2fd;
    color: #1976d2;
}

.badge-level-national {
    background: #fff3e0;
    color: #f57c00;
}

.badge-level-international {
    background: #e8f5e9;
    color: #2e7d32;
}

.badge-featured {
    background: #ffd700;
    color: #000;
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

/* Responsive */
@media (max-width: 768px) {
    .delete-container {
        padding: 1rem;
    }
    
    .delete-card {
        padding: 2rem 1.5rem;
    }
    
    .delete-card h1,
    .delete-card h2 {
        font-size: 1.5rem;
    }
    
    .delete-icon {
        width: 70px;
        height: 70px;
        font-size: 2rem;
    }
    
    .achievement-preview {
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
    }
}
</style>

<?php include '../includes/admin-footer.php'; ?>
