<?php
require_once '../includes/auth-check.php';
$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlashMessage('error', 'Invalid facility ID');
    redirect('index.php');
}

try {
    $stmt = $db->prepare("SELECT * FROM facilities WHERE facility_id = ?");
    $stmt->execute([$id]);
    $facility = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$facility) {
        setFlashMessage('error', 'Facility not found');
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
        $stmt = $db->prepare("DELETE FROM facilities WHERE facility_id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            if ($facility['featured_image']) {
                deleteUploadedFile($facility['featured_image']);
            }
            
            logActivity($_SESSION['admin_id'], 'delete', 'facilities', $id, "Deleted facility: {$facility['name']}");
            setFlashMessage('success', 'Facility deleted!');
            redirect('index.php');
        }
    } catch (PDOException $e) {
        setFlashMessage('error', 'Delete failed');
        redirect('index.php');
    }
}

$pageTitle = 'Delete Facility';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Delete Facility</h1>
</div>

<div class="delete-container">
    <div class="delete-card">
        <div class="delete-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <h2>Confirm Deletion</h2>
        <p class="delete-message">Delete this facility? This cannot be undone.</p>
        
        <?php if ($facility['featured_image']): ?>
        <div class="facility-image">
            <img src="<?php echo escapeHtml(getImageUrl($facility['featured_image'])); ?>" alt="<?php echo escapeHtml($facility['name']); ?>">
        </div>
        <?php endif; ?>
        
        <div class="facility-preview">
            <div class="preview-item">
                <strong>Name:</strong>
                <span><?php echo escapeHtml($facility['name']); ?></span>
            </div>
            
            <div class="preview-item">
                <strong>Category:</strong>
                <span class="badge badge-category"><?php echo ucfirst($facility['category']); ?></span>
            </div>
            
            <?php if ($facility['location']): ?>
            <div class="preview-item">
                <strong>Location:</strong>
                <span><?php echo escapeHtml($facility['location']); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($facility['capacity']): ?>
            <div class="preview-item">
                <strong>Capacity:</strong>
                <span><?php echo escapeHtml($facility['capacity']); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="preview-item">
                <strong>Status:</strong>
                <?php if ($facility['is_available']): ?>
                    <span class="badge badge-available">Available</span>
                <?php else: ?>
                    <span class="badge badge-unavailable">Unavailable</span>
                <?php endif; ?>
            </div>
            
            <div class="preview-item full-width">
                <strong>Description:</strong>
                <p class="preview-content"><?php echo escapeHtml($facility['description']); ?></p>
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
.delete-container{max-width:700px;margin:2rem auto;padding:0 1rem}
.delete-card{background:white;border-radius:12px;padding:2.5rem;box-shadow:var(--admin-shadow);text-align:center}
.delete-icon{width:80px;height:80px;margin:0 auto 1.5rem;background:linear-gradient(135deg,#dc3545,#c82333);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;animation:pulse 2s infinite}
.delete-icon i{font-size:2.5rem;color:white}
@keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.05)}}
.delete-card h2{font-size:1.75rem;font-weight:600;margin-bottom:1rem}
.delete-message{font-size:1.1rem;color:var(--admin-text-muted);margin-bottom:2rem}
.facility-image{margin-bottom:2rem;border-radius:8px;overflow:hidden;border:2px solid var(--admin-border)}
.facility-image img{width:100%;max-height:300px;object-fit:cover}
.facility-preview{background:#f8f9fa;border:2px solid var(--admin-border);border-radius:8px;padding:1.5rem;margin-bottom:2rem;text-align:left}
.preview-item{display:grid;grid-template-columns:120px 1fr;gap:1rem;padding:0.75rem 0;border-bottom:1px solid var(--admin-border)}
.preview-item:last-child{border-bottom:none}
.preview-item.full-width{grid-template-columns:1fr}
.preview-item strong{color:var(--admin-text);font-weight:600}
.preview-item span{color:var(--admin-text-muted)}
.preview-content{margin-top:0.5rem;padding:1rem;background:white;border-radius:6px;line-height:1.6;color:var(--admin-text)}
.badge{display:inline-block;padding:0.35rem 0.75rem;border-radius:6px;font-size:0.85rem;font-weight:500}
.badge-category{background:#9c27b0;color:white}
.badge-available{background:#e8f5e9;color:#2e7d32}
.badge-unavailable{background:#ffebee;color:#c62828}
.delete-actions{display:flex;gap:1rem;justify-content:center;margin-top:1.5rem}
.btn{padding:0.75rem 2rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s}
.btn-secondary{background:#6c757d;color:white}
.btn-secondary:hover{background:#5a6268}
.btn-danger{background:#dc3545;color:white}
.btn-danger:hover{background:#c82333}
@media(max-width:768px){.delete-card{padding:1.5rem}.preview-item{grid-template-columns:1fr;gap:0.5rem}.delete-actions{flex-direction:column}.btn{width:100%;justify-content:center}}
</style>

<?php include '../includes/admin-footer.php';
