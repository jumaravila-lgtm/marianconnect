<?php
require_once '../includes/auth-check.php';
$db = getDB();

// Get program ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlashMessage('error', 'Invalid program ID');
    redirect('index.php');
}

// Fetch program details
try {
    $stmt = $db->prepare("SELECT * FROM academic_programs WHERE program_id = ?");
    $stmt->execute([$id]);
    $program = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$program) {
        setFlashMessage('error', 'Program not found');
        redirect('index.php');
    }
} catch (PDOException $e) {
    setFlashMessage('error', 'Database error: ' . $e->getMessage());
    redirect('index.php');
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setFlashMessage('error', 'Invalid security token');
        redirect('index.php');
    }
    
    try {
        // Delete the program
        $stmt = $db->prepare("DELETE FROM academic_programs WHERE program_id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            // Delete associated image file
            if ($program['featured_image']) {
                deleteUploadedFile($program['featured_image']);
            }
            
            // Log the activity
            logActivity($_SESSION['admin_id'], 'delete', 'academic_programs', $id, "Deleted program: {$program['program_name']}");
            
            setFlashMessage('success', 'Program deleted successfully!');
            redirect('index.php');
        } else {
            setFlashMessage('error', 'Failed to delete program');
            redirect('index.php');
        }
    } catch (PDOException $e) {
        setFlashMessage('error', 'Error deleting program: ' . $e->getMessage());
        redirect('index.php');
    }
}

$pageTitle = 'Delete Program';
include '../includes/admin-header.php';
?>

<div class="delete-container">
    <div class="delete-card">
        <div class="delete-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <h2>Confirm Deletion</h2>
        <p class="delete-message">Are you sure you want to delete this program? This action cannot be undone.</p>
        
        <div class="program-preview">
            <?php if ($program['featured_image']): ?>
                <div class="preview-image">
                    <img src="<?php echo escapeHtml(getImageUrl($program['featured_image'])); ?>" alt="Program image">
                </div>
            <?php endif; ?>
            
            <div class="preview-item">
                <strong>Program Code:</strong>
                <span class="badge badge-code"><?php echo escapeHtml($program['program_code']); ?></span>
            </div>
            
            <div class="preview-item">
                <strong>Program Name:</strong>
                <span><?php echo escapeHtml($program['program_name']); ?></span>
            </div>
            
            <div class="preview-item">
                <strong>Level:</strong>
                <div style="background: #003f87; color: white; padding: 0.35rem 0.75rem; border-radius: 6px; font-weight: 500; display: inline-block;">
                    <?php
                    $levels = [
                        'elementary' => 'ELEMENTARY',
                        'junior_high' => 'JUNIOR HIGH',
                        'senior_high' => 'SENIOR HIGH',
                        'college' => 'COLLEGE'
                    ];
                    echo $levels[$program['level']];
                    ?>
                </div>
            </div>
            
            <div class="preview-item">
                <strong>Department:</strong>
                <span><?php echo escapeHtml($program['department'] ?? 'N/A'); ?></span>
            </div>
            
            <div class="preview-item">
                <strong>Duration:</strong>
                <span><?php echo escapeHtml($program['duration'] ?? 'N/A'); ?></span>
            </div>
            
            <div class="preview-item">
                <strong>Status:</strong>
                <span class="badge badge-<?php echo $program['is_active'] ? 'active' : 'inactive'; ?>">
                    <?php echo $program['is_active'] ? 'Active' : 'Inactive'; ?>
                </span>
            </div>
            
            <div class="preview-item full-width">
                <strong>Description:</strong>
                <p class="preview-content"><?php echo escapeHtml($program['description']); ?></p>
            </div>
        </div>
        <div class="warning-message">
            <strong>Warning:</strong> This action cannot be undone. The program and all its associated data will be permanently deleted.
        </div>
        <form method="POST" action="" class="delete-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="delete-actions">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Program
                </button>
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

/* Program Image Preview */
.preview-image {
    margin-bottom: 2rem;
}

.preview-image img {
    width: 100%;
    max-width: 400px;
    height: 200px;
    object-fit: cover;
    border-radius: 12px;
    border: 3px solid var(--admin-border);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

/* Program Preview Section */
.program-preview {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border: 2px solid var(--admin-border);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    text-align: left;
    transition: all 0.3s;
}

.program-preview:hover {
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

.badge-code {
    background: #e3f2fd;
    color: #1565c0;
}

.badge-level {
    background: #003f87;
    color: white;
}

.badge-active {
    background: #e8f5e9;
    color: #2e7d32;
}

.badge-inactive {
    background: #f5f5f5;
    color: #757575;
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
    
    .preview-image img {
        max-width: 100%;
        height: 150px;
    }
    
    .program-preview {
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

<?php include '../includes/admin-footer.php'; ?>
