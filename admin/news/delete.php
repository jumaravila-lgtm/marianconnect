<?php
/**
 * MARIANCONNECT - Delete News
 */

// Authentication check
require_once '../includes/auth-check.php';

$db = getDB();
$newsId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch news to delete
$stmt = $db->prepare("SELECT * FROM news WHERE news_id = ?");
$stmt->execute([$newsId]);
$news = $stmt->fetch();

if (!$news) {
    setFlashMessage('error', 'News article not found.');
    redirect('index.php');
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Delete associated image file
        if (!empty($news['featured_image'])) {
            deleteUploadedFile($news['featured_image']);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        // Delete from database
        $deleteStmt = $db->prepare("DELETE FROM news WHERE news_id = ?");
        $result = $deleteStmt->execute([$newsId]);
        
        if ($result) {
            // Log activity
            logActivity($_SESSION['admin_id'], 'delete', 'news', $newsId, "Deleted news: {$news['title']}");
            
            setFlashMessage('success', 'News article deleted successfully.');
        } else {
            setFlashMessage('error', 'Failed to delete news article.');
        }
    } catch (PDOException $e) {
        setFlashMessage('error', 'Database error: ' . $e->getMessage());
    }
    
    redirect('index.php');
}

$pageTitle = 'Delete News Article';
include '../includes/admin-header.php';
?>

<div class="delete-container">
    <div class="delete-card">
        <div class="delete-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <h1>Delete News Article?</h1>
        
        <div class="article-preview">
            <?php if (!empty($news['featured_image'])): ?>
            <img src="<?php echo escapeHtml(getImageUrl($news['featured_image'])); ?>" alt="<?php echo escapeHtml($news['title']); ?>">
            <?php endif; ?>
            <h3><?php echo escapeHtml($news['title']); ?></h3>
            <p class="meta">
                <span><i class="fas fa-tag"></i> <?php echo ucfirst($news['category']); ?></span>
                <span><i class="fas fa-calendar"></i> <?php echo formatDate($news['created_at']); ?></span>
                <span><i class="fas fa-eye"></i> <?php echo number_format($news['views']); ?> views</span>
            </p>
        </div>
        
        <div class="warning-message">
            <strong>Warning:</strong> This action cannot be undone. The article and its associated image will be permanently deleted.
        </div>
        
        <form method="POST" action="" class="delete-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-actions">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Yes, Delete Article
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
