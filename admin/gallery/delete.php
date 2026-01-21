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
        <p class="delete-message">Delete this image? This cannot be undone.</p>
        
        <div class="image-preview">
            <img src="<?php echo escapeHtml(asset($item['image_path'])); ?>" alt="<?php echo escapeHtml($item['title']); ?>" onerror="this.style.display='none'">
        </div>
        
        <div class="item-preview">
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
.delete-container{max-width:600px;margin:2rem auto;padding:0 1rem}
.delete-card{background:white;border-radius:12px;padding:2.5rem;box-shadow:var(--admin-shadow);text-align:center}
.delete-icon{width:80px;height:80px;margin:0 auto 1.5rem;background:#fff3cd;border-radius:50%;display:flex;align-items:center;justify-content:center}
.delete-icon i{font-size:2.5rem;color:#856404}
.delete-card h2{font-size:1.75rem;font-weight:600;margin-bottom:1rem}
.delete-message{font-size:1.1rem;color:var(--admin-text-muted);margin-bottom:2rem}
.warning-message {background: #fff3cd;border: 1px solid #ffc107;border-radius: 8px;padding: 1rem;margin-bottom: 2rem;color: #856404;text-align: left;}
.warning-message strong {display: block;margin-bottom: 0.5rem;}
.image-preview{margin-bottom:2rem;border-radius:8px;overflow:hidden;border:2px solid var(--admin-border)}
.image-preview img{width:100%;max-height:300px;object-fit:contain}
.item-preview{background:#f8f9fa;border:2px solid var(--admin-border);border-radius:8px;padding:1.5rem;margin-bottom:2rem;text-align:left}
.preview-item{display:grid;grid-template-columns:100px 1fr;gap:1rem;padding:0.75rem 0;border-bottom:1px solid var(--admin-border)}
.preview-item:last-child{border-bottom:none}
.badge{display:inline-block;padding:0.35rem 0.75rem;border-radius:6px;font-size:0.85rem;font-weight:500}
.badge-campus{background:#e3f2fd;color:#1565c0}
.badge-events{background:#f3e5f5;color:#7b1fa2}
.badge-facilities{background:#fff3e0;color:#f57c00}
.badge-students{background:#e8f5e9;color:#388e3c}
.badge-achievements{background:#fce4ec;color:#c2185b}
.badge-other{background:#f5f5f5;color:#757575}
.delete-actions{display:flex;gap:1rem;justify-content:center;margin-top:1.5rem}
.btn{padding:0.75rem 2rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s}
.btn-secondary{background:#6c757d;color:white}
.btn-secondary:hover{background:#5a6268}
.btn-danger{background:#dc3545;color:white}
.btn-danger:hover{background:#c82333}
@media(max-width:768px){.delete-actions{flex-direction:column}.btn{width:100%;justify-content:center}}
</style>

<?php include '../includes/admin-footer.php'; ?>
