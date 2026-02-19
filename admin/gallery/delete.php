<?php
require_once '../includes/auth-check.php';
$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($id === null || $id < 0) {
    setFlashMessage('error', 'Invalid gallery ID');
    redirect('index.php');
}

try {
    $stmt = $db->prepare("SELECT * FROM gallery WHERE gallery_id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        setFlashMessage('error', 'Image not found');
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
        $stmt = $db->prepare("DELETE FROM gallery WHERE gallery_id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            if ($item['image_path']) {
                deleteUploadedFile($item['image_path']);
            }
            if ($item['thumbnail_path']) {
                deleteUploadedFile($item['thumbnail_path']);
            }
            
            logActivity($_SESSION['admin_id'], 'delete', 'gallery', $id, "Deleted: {$item['title']}");
            setFlashMessage('success', 'Image deleted!');
            redirect('index.php');
        }
    } catch (PDOException $e) {
        setFlashMessage('error', 'Delete failed');
        redirect('index.php');
    }
}

$pageTitle = 'Delete Image';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Delete Image</h1>
</div>

<div class="delete-container">
    <div class="delete-card">
        <div class="delete-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <h2>Confirm Deletion</h2>
        
        <div class="item-preview">
            <div class="image-preview">
                <img src="<?php echo escapeHtml(asset($item['image_path'])); ?>" alt="<?php echo escapeHtml($item['title']); ?>" onerror="this.style.display='none'">
            </div>
        <div class="preview-item">
            <strong>Title:</strong>
                <span><?php echo escapeHtml($item['title']); ?></span>
        </div>
            <div class="preview-item">
                <strong>Category:</strong>
                <span class="badge badge-<?php echo $item['category']; ?>"><?php echo ucfirst($item['category']); ?></span>
            </div>
        </div>
        <div class="warning-message">
            <strong>Warning:</strong> This action cannot be undone. The image and its associated thumbnail will be permanently deleted.
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
/* Page Header - Hidden for centered layout */
.page-header {
    display: none;
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
    margin-bottom: 2rem;
    color: var(--admin-text);
    font-weight: 700;
}

/* Item Preview Section */
.item-preview {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    border: 2px solid var(--admin-border);
    transition: all 0.3s;
}

.item-preview:hover {
    border-color: var(--admin-primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

/* Image Preview */
.image-preview {
    margin-bottom: 1.5rem;
}

.image-preview img {
    width: 100%;
    max-height: 250px;
    object-fit: cover;
    border-radius: 10px;
    border: 2px solid var(--admin-border);
}

/* Preview Items */
.preview-item {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    flex-wrap: wrap;
    margin-bottom: 0.5rem;
}

.preview-item:last-child {
    margin-bottom: 0;
}

.preview-item strong {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--admin-text);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.preview-item span {
    font-size: 0.95rem;
    color: var(--admin-text-muted);
    font-weight: 500;
}

/* Category Badges with Gradient Styling */
.badge {
    display: inline-block;
    padding: 0.35rem 0.85rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: capitalize;
}

.badge-campus {
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    color: #1565c0;
    border: 2px solid #90caf9;
}

.badge-events {
    background: linear-gradient(135deg, #fff3e0, #ffe0b2);
    color: #e65100;
    border: 2px solid #ffb74d;
}

.badge-facilities {
    background: linear-gradient(135deg, #f3e5f5, #e1bee7);
    color: #6a1b9a;
    border: 2px solid #ce93d8;
}

.badge-students {
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
    color: #2e7d32;
    border: 2px solid #81c784;
}

.badge-achievements {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    color: #856404;
    border: 2px solid #ffd700;
}

.badge-other {
    background: linear-gradient(135deg, #e2e3e5, #d6d8db);
    color: #383d41;
    border: 2px solid #ced4da;
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
    
    .item-preview {
        padding: 1.5rem;
    }
    
    .image-preview img {
        max-height: 200px;
    }
    
    .preview-item {
        flex-direction: column;
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
