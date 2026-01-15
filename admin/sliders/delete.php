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
            setFlashMessage('success', 'Slider deleted!');
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

<div class="page-header">
    <h1>Delete Slider</h1>
</div>

<div class="delete-container">
    <div class="delete-card">
        <div class="delete-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <h2>Confirm Deletion</h2>
        <p class="delete-message">Delete this homepage slider? This cannot be undone.</p>
        
        <div class="slider-image-preview">
            <img src="<?php echo escapeHtml(getImageUrl($slider['image_path'])); ?>" alt="<?php echo escapeHtml($slider['title']); ?>">
        </div>
        
        <div class="slider-preview">
            <div class="preview-item">
                <strong>Title:</strong>
                <span><?php echo escapeHtml($slider['title']); ?></span>
            </div>
            
            <?php if ($slider['subtitle']): ?>
            <div class="preview-item">
                <strong>Subtitle:</strong>
                <span><?php echo escapeHtml($slider['subtitle']); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($slider['button_text']): ?>
            <div class="preview-item">
                <strong>Button:</strong>
                <span class="badge badge-button"><?php echo escapeHtml($slider['button_text']); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="preview-item">
                <strong>Display Order:</strong>
                <span><?php echo $slider['display_order']; ?></span>
            </div>
            
            <div class="preview-item">
                <strong>Status:</strong>
                <?php if ($slider['is_active']): ?>
                    <span class="badge badge-active">Active</span>
                <?php else: ?>
                    <span class="badge badge-inactive">Inactive</span>
                <?php endif; ?>
            </div>
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

.page-actions {
    display: flex;
    gap: 0.5rem;
}
.delete-container{max-width:700px;margin:2rem auto;padding:0 1rem}
.delete-card{background:white;border-radius:12px;padding:2.5rem;box-shadow:var(--admin-shadow);text-align:center}
.delete-icon{width:80px;height:80px;margin:0 auto 1.5rem;background:#fff3cd;border-radius:50%;display:flex;align-items:center;justify-content:center}
.delete-icon i{font-size:2.5rem;color:#856404}
.delete-card h2{font-size:1.75rem;font-weight:600;margin-bottom:1rem}
.delete-message{font-size:1.1rem;color:var(--admin-text-muted);margin-bottom:2rem}
.slider-image-preview{margin-bottom:2rem;border-radius:8px;overflow:hidden;border:2px solid var(--admin-border)}
.slider-image-preview img{width:100%;max-height:300px;object-fit:cover}
.slider-preview{background:#f8f9fa;border:2px solid var(--admin-border);border-radius:8px;padding:1.5rem;margin-bottom:2rem;text-align:left}
.preview-item{display:grid;grid-template-columns:120px 1fr;gap:1rem;padding:0.75rem 0;border-bottom:1px solid var(--admin-border)}
.preview-item:last-child{border-bottom:none}
.preview-item strong{color:var(--admin-text);font-weight:600}
.preview-item span{color:var(--admin-text-muted)}
.badge{display:inline-block;padding:0.35rem 0.75rem;border-radius:6px;font-size:0.85rem;font-weight:500}
.badge-button{background:#fff3e0;color:#f57c00}
.badge-active{background:#e8f5e9;color:#2e7d32}
.badge-inactive{background:#ffebee;color:#c62828}
.delete-actions{display:flex;gap:1rem;justify-content:center;margin-top:1.5rem}
.btn{padding:0.75rem 2rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s}
.btn-secondary{background:#6c757d;color:white}
.btn-secondary:hover{background:#5a6268}
.btn-danger{background:#dc3545;color:white}
.btn-danger:hover{background:#c82333}
@media(max-width:768px){.delete-card{padding:1.5rem}.preview-item{grid-template-columns:1fr;gap:0.5rem}.delete-actions{flex-direction:column}.btn{width:100%;justify-content:center}}
</style>

<?php include '../includes/admin-footer.php'; ?>
