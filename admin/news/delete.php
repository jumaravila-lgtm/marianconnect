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
.btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: var(--admin-primary);
    color: white;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
    cursor: pointer;
}
.btn-secondary:hover {
    background: var(--admin-primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--admin-shadow-lg);
}
.btn.btn-danger {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: var(--admin-danger);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s;
    cursor: pointer;
}
.btn.btn-danger:hover {
    background: maroon;
    transform: translateY(-2px);

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

.article-preview {
    background: var(--admin-hover);
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.article-preview img {
    width: 100%;
    max-height: 200px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.article-preview h3 {
    font-size: 1.25rem;
    margin-bottom: 0.75rem;
    color: var(--admin-text);
}

.article-preview .meta {
    display: flex;
    justify-content: center;
    gap: 1rem;
    font-size: 0.875rem;
    color: var(--admin-text-muted);
    flex-wrap: wrap;
}

.article-preview .meta span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
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

.form-actions .btn {
    padding: 0.75rem 2rem;
}
</style>

<?php include '../includes/admin-footer.php'; ?>
