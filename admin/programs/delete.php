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

<div class="page-header">
    <h1>Delete Program</h1>
</div>

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
                <div style="background: #9c27b0; color: white; padding: 0.35rem 0.75rem; border-radius: 6px; font-weight: 500; display: inline-block;">
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
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
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
.warning-message{background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:1rem;margin-bottom:1.5rem;color:#856404;text-align:left}
.warning-message strong{display:block;margin-bottom:0.5rem}
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
    margin-bottom: 1rem;
    color: var(--admin-text);
}

.delete-message {
    font-size: 1.05rem;
    color: var(--admin-text-muted);
    margin-bottom: 2rem;
}

.program-preview {
    background: var(--admin-hover);
    border: 2px solid var(--admin-border);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: left;
}

.preview-image {
    margin-bottom: 1.5rem;
    border-radius: 8px;
    overflow: hidden;
}

.preview-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.preview-item {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--admin-border);
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
}

.preview-item span {
    color: var(--admin-text);
}

.preview-content {
    margin-top: 0.5rem;
    padding: 1rem;
    background: white;
    border-radius: 6px;
    line-height: 1.6;
    color: var(--admin-text);
}

.badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
}

.badge-code {
    background: #e3f2fd;
    color: #1565c0;
}

.badge-level {
    background: #9c27b0;
    color: white;
}

.badge-active {
    background: #e8f5e9;
    color: #2e7d32;
}

.badge-inactive {
    background: #ffebee;
    color: #c62828;
}

.delete-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 1.5rem;
}

.btn {
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    transition: all 0.3s;
    font-size: 0.95rem;
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
