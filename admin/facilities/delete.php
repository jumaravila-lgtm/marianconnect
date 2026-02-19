<?php
require_once '../includes/auth-check.php';
$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === null || $id < 0) {
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
                <div style="background: #9c27b0; color: white; padding: 0.35rem 0.75rem; border-radius: 6px; font-weight: 500; display: inline-block;">
                    <?php echo strtoupper($facility['category']); ?>
                </div>
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
        <div class="warning-message">
            <strong>Warning:</strong> This action cannot be undone. The facility and its associated image will be permanently deleted.
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

.delete-icon i {
    color: white;
}

.delete-card h2 {
    font-size: 1.85rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: var(--admin-text);
}

.delete-message {
    font-size: 1.05rem;
    color: var(--admin-text-muted);
    margin-bottom: 2rem;
    line-height: 1.6;
}

/* Facility Image */
.facility-image {
    margin-bottom: 2rem;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid var(--admin-border);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.facility-image img {
    width: 100%;
    max-height: 250px;
    object-fit: cover;
}

/* Facility Preview Section */
.facility-preview {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border: 2px solid var(--admin-border);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    text-align: left;
    transition: all 0.3s;
}

.facility-preview:hover {
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
    font-weight: 500;
    font-size: 0.95rem;
}

.preview-content {
    margin-top: 0.5rem;
    padding: 1.25rem;
    background: white;
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    line-height: 1.7;
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
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.badge-category {
    background: #9c27b0;
    color: white;
}

.badge-available {
    background: #e8f5e9;
    color: #2e7d32;
}

.badge-unavailable {
    background: #ffebee;
    color: #c62828;
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
    
    .facility-preview {
        padding: 1.5rem;
    }
    
    .preview-item {
        grid-template-columns: 1fr;
        gap: 0.5rem;
        padding: 0.5rem 0;
    }
    
    .delete-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<?php include '../includes/admin-footer.php';
