<?php
require_once '../includes/auth-check.php';
$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlashMessage('error', 'Invalid slider ID');
    redirect('index.php');
}

try {
    $stmt = $db->prepare("SELECT * FROM homepage_sliders WHERE slider_id = ?");
    $stmt->execute([$id]);
    $slider = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$slider) {
        setFlashMessage('error', 'Slider not found');
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
        $stmt = $db->prepare("DELETE FROM homepage_sliders WHERE slider_id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            if ($slider['image_path']) {
                deleteUploadedFile($slider['image_path']);
            }
            
            logActivity($_SESSION['admin_id'], 'delete', 'homepage_sliders', $id, "Deleted slider: {$slider['title']}");
            setFlashMessage('success', 'Slider deleted successfully!');
            redirect('index.php');
        }
    } catch (PDOException $e) {
        setFlashMessage('error', 'Delete failed');
        redirect('index.php');
    }
}

$pageTitle = 'Delete Slider';
include '../includes/admin-header.php';
?>

<style>
/* Override any existing styles */
.admin-content {
    padding: 0 !important;
    max-width: 100% !important;
}
</style>

<div class="delete-container">
    <div class="delete-card">
        <div class="delete-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <h1>Delete Slider?</h1>
        
        <div class="article-preview">
            <?php if ($slider['image_path']): ?>
            <img src="<?php echo escapeHtml(getImageUrl($slider['image_path'])); ?>" alt="<?php echo escapeHtml($slider['title']); ?>">
            <?php endif; ?>
            
            <h3><?php echo escapeHtml($slider['title']); ?></h3>
            
            <p class="meta">
                <?php if ($slider['subtitle']): ?>
                <span><i class="fas fa-align-left"></i> <?php echo escapeHtml(substr($slider['subtitle'], 0, 50)) . '...'; ?></span>
                <?php endif; ?>
                <span><i class="fas fa-sort-numeric-down"></i> Order: <?php echo $slider['display_order']; ?></span>
                <?php if ($slider['is_active']): ?>
                <span><i class="fas fa-check-circle"></i> Active</span>
                <?php else: ?>
                <span><i class="fas fa-times-circle"></i> Inactive</span>
                <?php endif; ?>
            </p>
        </div>
        
        <div class="warning-message">
            <strong>Warning:</strong> This action cannot be undone. The slider and its associated image will be permanently deleted.
        </div>
        
        <form method="POST" action="" class="delete-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-actions">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Yes, Delete Slider
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

/* Article Preview */
.article-preview {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    border: 2px solid var(--admin-border);
    transition: all 0.3s;
}

.article-preview:hover {
    border-color: var(--admin-primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.article-preview img {
    width: 100%;
    max-height: 250px;
    object-fit: cover;
    border-radius: 10px;
    margin-bottom: 1.25rem;
    border: 2px solid var(--admin-border);
}

.article-preview h3 {
    font-size: 1.35rem;
    margin-bottom: 1rem;
    color: var(--admin-text);
    font-weight: 700;
    line-height: 1.4;
}

.article-preview .meta {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    font-size: 0.9rem;
    color: var(--admin-text-muted);
    flex-wrap: wrap;
}

.article-preview .meta span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: white;
    border-radius: 20px;
    font-weight: 500;
}

.article-preview .meta span i {
    color: var(--admin-primary);
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

/* Responsive */
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
    
    .article-preview {
        padding: 1.5rem;
    }
    
    .article-preview .meta {
        flex-direction: column;
        gap: 0.75rem;
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
