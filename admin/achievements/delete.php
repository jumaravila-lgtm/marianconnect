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
    max-width: 700px;
    width: 100%;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.delete-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse 2s infinite;
}

.delete-icon i {
    font-size: 2.5rem;
    color: white;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

.delete-card h2 {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #2c3e50;
}

.delete-message {
    font-size: 1.05rem;
    color: #6c757d;
    margin-bottom: 2rem;
    line-height: 1.6;
}
.warning-message {
    background:#fff3cd;
    border:1px solid #ffc107;
    border-radius:8px;
    padding:1rem;
    margin-bottom:1.5rem;
    color:#856404;
    text-align:left
}
.warning-message strong{
    display:block;
    margin-bottom:0.5rem
}
.achievement-image {
    margin-bottom: 2rem;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #e9ecef;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.achievement-image img {
    width: 100%;
    max-height: 300px;
    object-fit: cover;
}

.achievement-preview {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: left;
}

.preview-item {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #dee2e6;
}

.preview-item:last-child {
    border-bottom: none;
}

.preview-item.full-width {
    grid-template-columns: 1fr;
}

.preview-item strong {
    color: #495057;
    font-weight: 600;
    font-size: 0.95rem;
}

.preview-item span {
    color: #212529;
    font-size: 0.95rem;
}

.preview-content {
    margin-top: 0.5rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    line-height: 1.6;
    color: #495057;
    font-size: 0.95rem;
}

.badge {
    display: inline-block;
    padding: 0.4rem 0.85rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
}

.badge-category {
    background: #9c27b0;
    color: white;
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

.delete-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
}

.btn {
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    transition: all 0.3s;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
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
