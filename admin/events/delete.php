<?php
require_once '../includes/auth-check.php';

$db = getDB();
$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $db->prepare("SELECT * FROM events WHERE event_id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    setFlashMessage('error', 'Event not found.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!empty($event['featured_image'])) {
            deleteUploadedFile($event['featured_image']);
        }
        
        $deleteStmt = $db->prepare("DELETE FROM events WHERE event_id = ?");
        $result = $deleteStmt->execute([$eventId]);
        
        if ($result) {
            logActivity($_SESSION['admin_id'], 'delete', 'events', $eventId, "Deleted event: {$event['title']}");
            setFlashMessage('success', 'Event deleted successfully.');
        } else {
            setFlashMessage('error', 'Failed to delete event.');
        }
    } catch (PDOException $e) {
        setFlashMessage('error', 'Database error: ' . $e->getMessage());
    }
    
    redirect('index.php');
}

$pageTitle = 'Delete Event';
include '../includes/admin-header.php';
?>

<div class="delete-container">
    <div class="delete-card">
        <div class="delete-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <h1>Delete Event?</h1>
        
        <div class="event-preview">
            <?php if (!empty($event['featured_image'])): ?>
            <img src="<?php echo escapeHtml(getImageUrl($event['featured_image'])); ?>" alt="<?php echo escapeHtml($event['title']); ?>">
            <?php endif; ?>
            <h3><?php echo escapeHtml($event['title']); ?></h3>
            <p class="meta">
                <span><i class="fas fa-calendar"></i> <?php echo formatDate($event['event_date'], 'M j, Y'); ?></span>
                <span><i class="fas fa-map-marker-alt"></i> <?php echo escapeHtml($event['location']); ?></span>
                <span><i class="fas fa-tag"></i> <?php echo ucfirst($event['category']); ?></span>
            </p>
        </div>
        
        <div class="warning-message">
            <strong>⚠️ Warning:</strong> This action cannot be undone. The event and its associated image will be permanently deleted.
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="form-actions">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Yes, Delete Event
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.delete-container{display:flex;align-items:center;justify-content:center;min-height:calc(100vh - 200px);padding:2rem}
.delete-card{background:white;border-radius:16px;padding:3rem;max-width:600px;width:100%;box-shadow:var(--admin-shadow-lg);text-align:center}
.delete-icon{width:80px;height:80px;background:linear-gradient(135deg,#dc3545,#c82333);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;margin:0 auto 2rem;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.05)}}
.delete-card h1{font-size:1.75rem;margin-bottom:2rem}
.event-preview{background:var(--admin-hover);padding:1.5rem;border-radius:12px;margin-bottom:2rem}
.event-preview img{width:100%;max-height:200px;object-fit:cover;border-radius:8px;margin-bottom:1rem}
.event-preview h3{font-size:1.25rem;margin-bottom:0.75rem}
.event-preview .meta{display:flex;justify-content:center;gap:1rem;font-size:0.875rem;color:var(--admin-text-muted);flex-wrap:wrap}
.event-preview .meta span{display:flex;align-items:center;gap:0.5rem}
.warning-message{background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:1rem;margin-bottom:2rem;color:#856404;text-align:left}
.form-actions{display:flex;gap:1rem;justify-content:center}
.form-actions .btn{padding:0.75rem 2rem}
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s;
    cursor: pointer;
}
.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
    transform: translateY(-2px);
}
</style>

<?php include '../includes/admin-footer.php'; ?>
